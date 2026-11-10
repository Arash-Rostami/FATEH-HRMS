<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GenerateCorrespondenceJob;
use App\Models\Correspondence;
use App\Models\User;
use App\Services\Ai\AiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenerateCorrespondenceJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        config(['app.off' => [], 'queue.default' => 'database']);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }

    private function generatingLetter(string $run = 'run-token', array $extra = []): Correspondence
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        return Correspondence::forceCreate($extra + [
            'user_id' => $user->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_GENERATING,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'mode' => Correspondence::MODE_SMART,
            'subject' => '',
            'spec' => ['blocks' => [['type' => 'text', 'text' => '']]],
            'versions' => [['ai' => true, 'run' => $run, 'kind' => 'generate', 'prompt' => 'A valid prompt.']],
        ]);
    }

    public function test_timeout_stays_below_retry_after_with_the_default_and_a_lower_override(): void
    {
        $base = 2 * ((int) AiClient::setting('correspondence', 'timeout') + (int) AiClient::setting('correspondence', 'connect_timeout')) + (int) config('ai.correspondence.job.padding');
        $margin = (int) config('ai.correspondence.job.retry_margin');
        $retryAfter = (int) config('queue.connections.database.retry_after');
        $expected = min($base, $retryAfter - $margin);

        $this->assertSame($expected, (new GenerateCorrespondenceJob(1, 'x'))->timeout);

        config(['queue.connections.database.retry_after' => 55]);
        $this->assertSame(min($base, 55 - $margin), (new GenerateCorrespondenceJob(1, 'y'))->timeout);
    }

    public function test_disabled_ai_marks_the_current_run_failed_without_calling_the_gateway(): void
    {
        $letter = $this->generatingLetter();
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'app.off' => ['correspondence:ai']]);

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $this->assertSame(Correspondence::STATUS_FAILED, $letter->fresh()->status);
        $this->assertSame('disabled', $letter->fresh()->fail_reason);
    }

    public function test_stale_job_run_does_not_mutate_a_newer_generation(): void
    {
        $letter = $this->generatingLetter('new-run');

        (new GenerateCorrespondenceJob((int) $letter->id, 'old-run'))->handle(new AiClient());

        $this->assertSame(Correspondence::STATUS_GENERATING, $letter->fresh()->status);
        $this->assertSame('new-run', $letter->fresh()->lastVersion()['run']);
    }

    private function gateway(array $payload, string $finish = 'stop'): void
    {
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'ai.correspondence.model' => 'test-model']);
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($payload, JSON_UNESCAPED_UNICODE)], 'finish_reason' => $finish]],
            'usage' => ['total_tokens' => 42],
        ])]);
    }

    private function output(array $paragraphs, string $subject = 'درخواست تجهیزات'): array
    {
        return ['subject' => $subject, 'blocks' => [['type' => 'text', 'heading' => null, 'paragraphs' => $paragraphs, 'header' => [], 'rows' => []]]];
    }

    public function test_the_timeout_follows_the_formula_with_the_real_default_and_clamps_to_retry_after(): void
    {
        $base = 2 * (120 + 10) + 30;
        $this->assertSame(310, config('queue.connections.database.retry_after'));
        $this->assertSame($base, (new GenerateCorrespondenceJob(1, 'x'))->timeout);

        foreach ([[310, 290], [300, 290], [295, 285], [180, 170]] as [$retryAfter, $expected]) {
            config(['queue.connections.database.retry_after' => $retryAfter]);
            $job = new GenerateCorrespondenceJob(1, 'x');
            $this->assertSame($expected, $job->timeout);
            $this->assertSame($expected + 30, $job->uniqueFor);
        }

        config(['queue.connections.database.retry_after' => 5]);
        $this->assertSame($base, (new GenerateCorrespondenceJob(1, 'x'))->timeout);
    }

    public function test_non_normal_classification_or_unusable_type_marks_the_run_disabled(): void
    {
        $this->gateway($this->output(['متن']));
        $secret = $this->generatingLetter('run-token', ['classification' => Correspondence::CLASSIFICATION_CONFIDENTIAL]);
        $memo = $this->generatingLetter('run-token', ['type' => 'memo']);
        $incoming = $this->generatingLetter('run-token', ['type' => 'incoming']);

        foreach ([$secret, $memo, $incoming] as $letter) {
            (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());
            $this->assertSame(Correspondence::STATUS_FAILED, $letter->fresh()->status);
            $this->assertSame('disabled', $letter->fresh()->fail_reason);
        }

        Http::assertNothingSent();
    }

    public function test_a_valid_answer_is_stored_as_a_new_version_with_the_subject_and_tokens(): void
    {
        $this->gateway($this->output(['متن آزمایشی نامه']));
        $letter = $this->generatingLetter();

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $fresh = $letter->fresh();
        $this->assertSame(Correspondence::STATUS_DRAFT, $fresh->status);
        $this->assertSame('درخواست تجهیزات', $fresh->subject);
        $this->assertSame('متن آزمایشی نامه', $fresh->spec['blocks'][0]['text']);
        $this->assertSame(42, (int) $fresh->tokens_total);
        $this->assertNull($fresh->fail_reason);
        $this->assertSame('test-model', $fresh->model);
        $this->assertNotEmpty($fresh->content_hash);
        $version = $fresh->lastVersion();
        $this->assertSame(42, $version['tokens']);
        $this->assertSame('run-token', $version['run']);
        $this->assertEquals($fresh->spec, $version['spec']);
        $this->assertSame('درخواست تجهیزات', $version['subject']);
        Http::assertSentCount(1);
    }

    public function test_a_run_started_from_a_saved_letter_returns_it_to_saved(): void
    {
        $this->gateway($this->output(['متن آزمایشی نامه']));
        $letter = $this->generatingLetter();
        $letter->forceFill(['versions' => [['ai' => true, 'run' => 'run-token', 'kind' => 'generate', 'prompt' => 'A valid prompt.', 'from' => Correspondence::STATUS_SAVED]]])->saveQuietly();

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $this->assertSame(Correspondence::STATUS_SAVED, $letter->fresh()->status);
    }

    public function test_descriptive_blanks_become_the_canonical_marker_and_the_hint_is_kept(): void
    {
        $long = '[' . str_repeat('م', 90) . ']';
        $this->gateway($this->output(['لطفاً [شماره قرارداد] را بنویسید', "مقدار {$long} باقی بماند"]));
        $letter = $this->generatingLetter();

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $fresh = $letter->fresh();
        $text = $fresh->spec['blocks'][0]['text'];
        $this->assertStringContainsString('لطفاً […] را بنویسید', $text);
        $this->assertStringNotContainsString('شماره قرارداد', $text);
        $this->assertStringContainsString($long, $text);
        $this->assertSame(['شماره قرارداد'], $fresh->lastVersion()['blanks']);
        $this->assertSame(1, $fresh->lastVersion()['placeholders']);
    }

    public function test_refine_replaces_only_the_target_block_and_keeps_its_heading_and_the_subject(): void
    {
        $this->gateway($this->output(['بند بازنویسی‌شده'], 'هر چیز'));
        $letter = $this->generatingLetter('run-token', [
            'subject' => 'موضوع نامه',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'بند اول'], ['type' => 'text', 'heading' => 'عنوان', 'text' => 'بند دوم']]],
            'versions' => [['ai' => true, 'run' => 'run-token', 'kind' => 'refine', 'block' => 1, 'instruction' => 'کوتاه کن', 'prompt' => 'A valid prompt.']],
        ]);

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $fresh = $letter->fresh();
        $this->assertSame('بند اول', $fresh->spec['blocks'][0]['text']);
        $this->assertSame('بند بازنویسی‌شده', $fresh->spec['blocks'][1]['text']);
        $this->assertSame('عنوان', $fresh->spec['blocks'][1]['heading']);
        $this->assertSame('موضوع نامه', $fresh->subject);
    }

    public function test_a_failure_marks_the_current_run_failed_and_refunds_the_quota(): void
    {
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'ai.correspondence.model' => 'test-model']);
        Http::fake(['*' => Http::response('', 503)]);
        $letter = $this->generatingLetter('run-token', ['versions' => [['ai' => true, 'run' => 'run-token', 'kind' => 'generate', 'prompt' => 'A valid prompt.', 'quota_day' => now()->format('Y-m-d')]]]);
        Cache::forget(Correspondence::quotaKey((int) $letter->user_id));
        Correspondence::consumeQuota((int) $letter->user_id);

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $this->assertSame(Correspondence::STATUS_FAILED, $letter->fresh()->status);
        $this->assertSame('http_5xx', $letter->fresh()->fail_reason);
        $this->assertSame(0, Correspondence::quotaUsed((int) $letter->user_id));
        Cache::forget(Correspondence::quotaKey((int) $letter->user_id));
    }

    public function test_too_large_and_blocked_failures_keep_the_spent_quota(): void
    {
        $this->gateway($this->output(['متن']), 'length');
        $letter = $this->generatingLetter('run-token', ['versions' => [['ai' => true, 'run' => 'run-token', 'kind' => 'generate', 'prompt' => 'A valid prompt.', 'quota_day' => now()->format('Y-m-d')]]]);
        Cache::forget(Correspondence::quotaKey((int) $letter->user_id));
        Correspondence::consumeQuota((int) $letter->user_id);

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $this->assertSame('too_large', $letter->fresh()->fail_reason);
        $this->assertSame(1, Correspondence::quotaUsed((int) $letter->user_id));
        Cache::forget(Correspondence::quotaKey((int) $letter->user_id));
    }

    public function test_an_older_run_cannot_fail_a_newer_replacement_when_it_errors_late(): void
    {
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'ai.correspondence.model' => 'test-model']);
        $letter = $this->generatingLetter('old-run');
        Http::fake(function () use ($letter) {
            DB::table('correspondences')->where('id', $letter->id)->update(['versions' => json_encode([['ai' => true, 'run' => 'new-run', 'kind' => 'generate', 'prompt' => 'A valid prompt.']])]);

            return Http::response('', 500);
        });

        (new GenerateCorrespondenceJob((int) $letter->id, 'old-run'))->handle(new AiClient());

        $this->assertSame(Correspondence::STATUS_GENERATING, $letter->fresh()->status);
        $this->assertNull($letter->fresh()->fail_reason);
    }

    public function test_an_unparseable_answer_fails_the_run_as_invalid_json_after_the_allowed_calls(): void
    {
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'ai.correspondence.model' => 'test-model']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'not json at all'], 'finish_reason' => 'stop']], 'usage' => ['total_tokens' => 5]])]);
        $letter = $this->generatingLetter();

        (new GenerateCorrespondenceJob((int) $letter->id, 'run-token'))->handle(new AiClient());

        $this->assertSame('invalid_json', $letter->fresh()->fail_reason);
        Http::assertSentCount((int) config('ai.correspondence.provider.max_calls'));
    }

    public function test_the_failed_hook_marks_only_the_current_run(): void
    {
        $current = $this->generatingLetter('run-token');
        $other = $this->generatingLetter('newer');

        (new GenerateCorrespondenceJob((int) $current->id, 'run-token'))->failed(null);
        (new GenerateCorrespondenceJob((int) $other->id, 'older'))->failed(null);

        $this->assertSame('exception', $current->fresh()->fail_reason);
        $this->assertSame(Correspondence::STATUS_GENERATING, $other->fresh()->status);
    }

    public function test_the_unique_id_names_the_letter_and_the_run(): void
    {
        $this->assertSame('7:abc', (new GenerateCorrespondenceJob(7, 'abc'))->uniqueId());
    }
}
