<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendNotifyChunk;
use App\Models\Link;
use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\User;
use App\Services\Notify\Contracts\NotifyChannel;
use App\Services\Notify\Message;
use App\Services\Notify\NotifyRegistry;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use ReflectionProperty;
use Tests\TestCase;

class SendNotifyChunkTest extends TestCase
{
    private NotifyRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        DB::beginTransaction();
        $this->rule = NotifyRule::create([
            'name' => 'chunk test rule',
            'model' => Link::class,
            'events' => ['updated'],
            'channels' => ['mail', 'sms'],
            'subject' => 's',
            'lines' => ['l'],
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        $this->setChannels(null);
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

    private function setChannels(?array $channels): void
    {
        (new ReflectionProperty(NotifyRegistry::class, 'channels'))->setValue(null, $channels);
    }

    private function fakeMail(Closure $sendMany): void
    {
        $this->fakeChannel('mail', $sendMany);
    }

    private function fakeSms(Closure $sendMany): void
    {
        $this->fakeChannel('sms', $sendMany);
    }

    private function fakeChannel(string $key, Closure $sendMany): void
    {
        $this->setChannels([$key => new class($key, $sendMany) implements NotifyChannel {
            public function __construct(private string $key, private Closure $sendMany) {}

            public function key(): string
            {
                return $this->key;
            }

            public function label(): string
            {
                return $this->key;
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function normalize(string $address): string
            {
                return $address;
            }

            public function addressFor(User $user): ?string
            {
                return $user->email;
            }

            public function accepts(string $contact): bool
            {
                return true;
            }

            public function send(Message $message, string $address): bool
            {
                return true;
            }

            public function sendMany(Message $message, array $addresses): array
            {
                return ($this->sendMany)($addresses);
            }
        }]);
    }

    private function chunk(string $channel, array $addresses, bool $detailed): void
    {
        (new SendNotifyChunk(
            $this->rule->id,
            'RUN0000000000000000000001',
            $channel,
            new Message('subject', ['line'], 'rule', 'Link', 'updated'),
            $addresses,
            $detailed,
            'updated',
            '42',
        ))->handle();
    }

    private function rows(string $status): Collection
    {
        return NotifyLog::where('run_id', 'RUN0000000000000000000001')->where('status', $status)->get();
    }

    public function test_a_channel_switched_off_while_the_chunk_waited_is_one_failed_row_for_the_whole_chunk(): void
    {
        config(['app.off' => ['notify:sms']]);

        $this->chunk('sms', ['09120000001' => 5, '09120000002' => null], true);

        $failed = $this->rows(NotifyLog::STATUS_FAILED);
        $this->assertCount(1, $failed);
        $this->assertSame(2, (int)$failed[0]->count);
        $this->assertNull($failed[0]->user_id);
        $this->assertSame('sms', $failed[0]->channel);
        $this->assertSame(__('resources/notify_rule/strings.batch.channel_off'), $failed[0]->error);
        $this->assertSame(0, $this->rows(NotifyLog::STATUS_SENT)->count());
    }

    public function test_an_unconfigured_channel_sends_nothing_and_writes_one_failed_row_with_the_reason(): void
    {
        Http::fake();
        config(['services.sms.provider' => 'kavenegar', 'services.kavenegar.key' => null]);

        $this->chunk('sms', ['09120000001' => 5, '09120000002' => 6, '09120000003' => null], true);

        Http::assertNothingSent();
        $failed = $this->rows(NotifyLog::STATUS_FAILED);
        $this->assertCount(1, $failed);
        $this->assertSame(3, (int)$failed[0]->count);
        $this->assertStringContainsString('پیکربندی نشده', $failed[0]->error);
        $this->assertSame('42', $failed[0]->record_id);
        $this->assertSame('updated', $failed[0]->event);
        $this->assertSame($this->rule->id, (int)$failed[0]->notify_rule_id);
        $this->assertSame('rule', $failed[0]->source);
    }

    public function test_partial_failures_become_failed_rows_per_person_and_successes_follow_the_detail_flag(): void
    {
        $this->fakeMail(fn(array $addresses) => ['b@example.com' => 'mailbox full'] + array_fill_keys($addresses, null));
        $addresses = ['a@example.com' => 1, 'b@example.com' => 2, 'c@example.com' => null];

        $this->chunk('mail', $addresses, true);

        $failed = $this->rows(NotifyLog::STATUS_FAILED);
        $this->assertCount(1, $failed);
        $this->assertSame('b@example.com', $failed[0]->recipient);
        $this->assertSame(2, (int)$failed[0]->user_id);
        $this->assertSame('mailbox full', $failed[0]->error);
        $this->assertEqualsCanonicalizing(['a@example.com', 'c@example.com'], $this->rows(NotifyLog::STATUS_SENT)->pluck('recipient')->all());

        NotifyLog::where('run_id', 'RUN0000000000000000000001')->delete();

        $this->chunk('mail', $addresses, false);

        $this->assertSame('b@example.com', $this->rows(NotifyLog::STATUS_FAILED)->sole()->recipient);
        $sent = $this->rows(NotifyLog::STATUS_SENT)->sole();
        $this->assertSame(2, (int)$sent->count);
        $this->assertNull($sent->user_id);
        $this->assertSame(__('resources/notify_rule/strings.batch.recipients', ['count' => 2]), $sent->recipient);
    }

    public function test_an_address_the_channel_gives_no_result_for_is_a_failed_row_not_a_silent_success(): void
    {
        $this->fakeMail(fn(array $addresses) => ['a@example.com' => null]);

        $this->chunk('mail', ['a@example.com' => 1, 'b@example.com' => 2], true);

        $this->assertSame('b@example.com', $this->rows(NotifyLog::STATUS_FAILED)->sole()->recipient);
        $this->assertSame(__('resources/notify_rule/strings.batch.unknown_result'), $this->rows(NotifyLog::STATUS_FAILED)->sole()->error);
        $this->assertSame('a@example.com', $this->rows(NotifyLog::STATUS_SENT)->sole()->recipient);
    }

    public function test_a_killed_chunk_still_leaves_one_failed_row_through_the_failed_hook(): void
    {
        $job = new SendNotifyChunk($this->rule->id, 'RUN0000000000000000000001', 'mail', new Message('s', ['l'], 'rule', 'Link', 'updated'), ['a@example.com' => 1, 'b@example.com' => 2], false, 'updated', '42');

        $job->failed(new \RuntimeException('worker killed'));

        $failed = $this->rows(NotifyLog::STATUS_FAILED)->sole();
        $this->assertSame(2, (int)$failed->count);
        $this->assertSame('worker killed', $failed->error);
    }

    public function test_a_gateway_wide_refusal_on_a_big_chunk_collapses_into_one_failed_row(): void
    {
        $this->fakeMail(fn(array $addresses) => array_fill_keys($addresses, 'relay refused'));

        $this->chunk('mail', ['a@example.com' => 1, 'b@example.com' => 2, 'c@example.com' => 3], false);

        $failed = $this->rows(NotifyLog::STATUS_FAILED)->sole();
        $this->assertSame(3, (int)$failed->count);
        $this->assertNull($failed->user_id);
        $this->assertSame('relay refused', $failed->error);
        $this->assertSame(0, $this->rows(NotifyLog::STATUS_SENT)->count());
    }

    public function test_a_throwable_is_one_failed_row_with_a_capped_error_that_never_contains_the_gateway_key(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        $this->fakeMail(fn() => throw new \RuntimeException('https://api.kavenegar.com/v1/SECRETKEY/sms/send.json ' . str_repeat('x', 800)));

        $this->chunk('mail', ['a@example.com' => 1, 'b@example.com' => 2], true);

        $failed = $this->rows(NotifyLog::STATUS_FAILED)->sole();
        $this->assertSame(2, (int)$failed->count);
        $this->assertStringNotContainsString('SECRETKEY', $failed->error);
        $this->assertStringContainsString('***', $failed->error);
        $this->assertLessThanOrEqual(500, mb_strlen($failed->error));
    }

    public function test_kavenegar_entry_failures_map_back_to_the_right_people(): void
    {
        config(['services.sms.provider' => 'kavenegar', 'services.kavenegar.key' => 'KEY']);
        Http::fake(['api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 200, 'message' => 'تایید شد'],
            'entries' => [
                ['receptor' => '09121111111', 'status' => 6],
                ['receptor' => '989122222222', 'status' => 10],
            ],
        ])]);

        $this->chunk('sms', ['09121111111' => 7, '09122222222' => 8], true);

        Http::assertSentCount(1);
        $failed = $this->rows(NotifyLog::STATUS_FAILED)->sole();
        $this->assertSame('*******1111', $failed->recipient);
        $this->assertSame(7, (int)$failed->user_id);
        $this->assertSame('*******2222', $this->rows(NotifyLog::STATUS_SENT)->sole()->recipient);
    }

    public function test_sms_rows_with_a_user_mask_the_number_while_extra_contacts_keep_it_in_full(): void
    {
        $this->fakeSms(fn(array $addresses) => array_fill_keys($addresses, null));

        $this->chunk('sms', ['09121234567' => 9, '09127654321' => null], true);

        $sent = $this->rows(NotifyLog::STATUS_SENT)->keyBy(fn($row) => $row->user_id ?? 'contact');
        $this->assertCount(2, $sent);
        $this->assertSame('*******4567', $sent[9]->recipient);
        $this->assertSame('09127654321', $sent['contact']->recipient);
    }

    public function test_the_job_never_retries_and_takes_its_timeout_from_config(): void
    {
        config(['notify.timeout' => 33]);

        $job = new SendNotifyChunk(1, 'r', 'mail', new Message('s', [], 'r', 'm', 'created'), [], true, null, null);

        $this->assertSame(1, $job->tries);
        $this->assertSame(33, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
    }
}
