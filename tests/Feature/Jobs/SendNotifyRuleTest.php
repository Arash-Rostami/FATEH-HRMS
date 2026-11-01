<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendNotifyChunk;
use App\Jobs\SendNotifyRule;
use App\Mail\NotifyMail;
use App\Models\Link;
use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\Profile;
use App\Models\User;
use App\Services\Notify\Contracts\NotifyChannel;
use App\Services\Notify\Message;
use App\Services\Notify\NotifyRegistry;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use ReflectionProperty;
use Tests\TestCase;

class SendNotifyRuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        Mail::fake();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Carbon::setTestNow();
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

    private function activeUser(): User
    {
        return User::factory()->create(['status' => 'active']);
    }

    private function makeRule(array $overrides = []): NotifyRule
    {
        return NotifyRule::create(array_merge([
            'name' => 'job test rule',
            'model' => Link::class,
            'events' => ['updated'],
            'channels' => ['mail'],
            'subject' => 'New {model} #{id} by {actor}',
            'lines' => ['Title: {url_title}', 'Label: {عنوان}'],
            'is_active' => true,
        ], $overrides));
    }

    private function payload(): array
    {
        return [
            'model' => Link::class,
            'id' => 42,
            'event' => 'updated',
            'label' => 'Link',
            'values' => ['url_title' => 'Quarterly'],
            'changed' => ['url_title'],
            'actor' => 'Admin',
            'at' => now()->toDateTimeString(),
        ];
    }

    private function run_(int $ruleId, ?array $payload = null): void
    {
        app()->call([new SendNotifyRule($ruleId, $payload ?? $this->payload()), 'handle']);
    }

    private function fakeChannel(Closure $sendMany, ?Closure $addressFor = null): NotifyChannel
    {
        return new class($sendMany, $addressFor) implements NotifyChannel {
            public function __construct(private Closure $sendMany, private ?Closure $addressFor) {}

            public function key(): string
            {
                return 'mail';
            }

            public function label(): string
            {
                return 'mail';
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
                return $this->addressFor ? ($this->addressFor)($user) : $user->email;
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
        };
    }

    private function logs(NotifyRule $rule, string $status): Collection
    {
        return NotifyLog::where('notify_rule_id', $rule->id)->where('status', $status)->get();
    }

    public function test_sends_one_mail_per_resolved_address_and_logs_user_event_and_record(): void
    {
        $a = $this->activeUser();
        $b = $this->activeUser();
        $inactive = User::factory()->create(['status' => 'inactive']);
        $rule = $this->makeRule([
            'user_ids' => [$a->id, $b->id, $inactive->id],
            'extra' => ['contacts' => ['external@example.com', $a->email, '09120000000']],
        ]);

        $this->run_($rule->id);

        Mail::assertSent(NotifyMail::class, 3);
        foreach ([$a->email, $b->email, 'external@example.com'] as $address) {
            Mail::assertSent(NotifyMail::class, fn(NotifyMail $m) => $m->hasTo($address)
                && $m->message->subject === 'New Link #42 by Admin'
                && $m->message->lines === ['Title: Quarterly', 'Label: Quarterly']);
        }
        Mail::assertNotSent(NotifyMail::class, fn(NotifyMail $m) => $m->hasTo($inactive->email));

        $sent = $this->logs($rule, NotifyLog::STATUS_SENT)->keyBy('recipient');
        $this->assertCount(3, $sent);
        $this->assertSame($a->id, (int)$sent[$a->email]->user_id);
        $this->assertSame($b->id, (int)$sent[$b->email]->user_id);
        $this->assertNull($sent['external@example.com']->user_id);

        foreach ($sent as $log) {
            $this->assertSame(1, (int)$log->count);
            $this->assertSame('mail', $log->channel);
            $this->assertSame('updated', $log->event);
            $this->assertSame('42', $log->record_id);
            $this->assertSame(NotifyLog::SOURCE_RULE, $log->source);
            $this->assertNull($log->error);
        }

        $summary = $this->logs($rule, NotifyLog::STATUS_SUMMARY);
        $this->assertCount(1, $summary);
        $this->assertSame(4, (int)$summary[0]->count);
        $this->assertSame('all', $summary[0]->channel);
        $this->assertNull($summary[0]->error);
        $this->assertSame(1, NotifyLog::where('notify_rule_id', $rule->id)->distinct()->count('run_id'));

        $skipped = $this->logs($rule, NotifyLog::STATUS_SKIPPED);
        $this->assertCount(1, $skipped);
        $this->assertSame($inactive->id, (int)$skipped[0]->user_id);
        $this->assertSame($inactive->name, $skipped[0]->recipient);
        $this->assertSame(__('resources/notify_rule/strings.batch.skip_inactive'), $skipped[0]->error);
    }

    public function test_a_failing_send_is_logged_as_failed_and_does_not_stop_the_remaining_recipients(): void
    {
        $this->setChannels(['mail' => $this->fakeChannel(fn(array $addresses) => [$addresses[0] => 'smtp down for ' . $addresses[0]] + array_fill_keys($addresses, null))]);
        $a = $this->activeUser();
        $b = $this->activeUser();
        $rule = $this->makeRule(['user_ids' => [$a->id, $b->id]]);

        $this->run_($rule->id);

        $failed = $this->logs($rule, NotifyLog::STATUS_FAILED);
        $this->assertCount(1, $failed);
        $this->assertSame('smtp down for ' . $failed[0]->recipient, $failed[0]->error);
        $this->assertSame(1, (int)$failed[0]->count);
        $this->assertNotNull($failed[0]->user_id);

        $sent = $this->logs($rule, NotifyLog::STATUS_SENT);
        $this->assertCount(1, $sent);
        $this->assertNotSame($failed[0]->recipient, $sent[0]->recipient);
    }

    public function test_runs_above_the_detail_threshold_write_one_aggregated_sent_row_per_chunk(): void
    {
        config(['notify.detail_below' => 1, 'notify.chunk.mail' => 2]);
        $users = [$this->activeUser(), $this->activeUser(), $this->activeUser()];
        $rule = $this->makeRule(['user_ids' => array_map(fn($u) => $u->id, $users)]);

        $this->run_($rule->id);

        Mail::assertSent(NotifyMail::class, 3);
        $sent = $this->logs($rule, NotifyLog::STATUS_SENT);
        $this->assertSame([1, 2], $sent->pluck('count')->map(fn($c) => (int)$c)->sort()->values()->all());

        foreach ($sent as $row) {
            $this->assertNull($row->user_id);
            $this->assertSame(__('resources/notify_rule/strings.batch.recipients', ['count' => (int)$row->count]), $row->recipient);
            $this->assertSame('42', $row->record_id);
        }

        $this->assertSame(3, (int)$this->logs($rule, NotifyLog::STATUS_SUMMARY)[0]->count);
    }

    public function test_a_run_over_the_ceiling_sends_nothing_and_writes_only_a_summary_with_the_reason(): void
    {
        config(['notify.ceiling' => 2]);
        $rule = $this->makeRule(['user_ids' => [$this->activeUser()->id, $this->activeUser()->id, $this->activeUser()->id]]);

        $this->run_($rule->id);

        Mail::assertNothingSent();
        $rows = NotifyLog::where('notify_rule_id', $rule->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(NotifyLog::STATUS_SUMMARY, $rows[0]->status);
        $this->assertSame(3, (int)$rows[0]->count);
        $this->assertSame(__('resources/notify_rule/strings.batch.ceiling', ['count' => 2]), $rows[0]->error);
    }

    public function test_unreachable_people_are_logged_as_skipped_with_a_reason_and_reachable_ones_still_get_the_sms(): void
    {
        config(['services.sms.provider' => 'log']);
        $good = $this->activeUser();
        Profile::factory()->create(['user_id' => $good->id, 'cellphone' => '09121111111']);
        $noMobile = $this->activeUser();
        Profile::factory()->create(['user_id' => $noMobile->id, 'cellphone' => null]);
        $badMobile = $this->activeUser();
        Profile::factory()->create(['user_id' => $badMobile->id, 'cellphone' => '12-34']);
        $rule = $this->makeRule(['channels' => ['sms'], 'user_ids' => [$good->id, $noMobile->id, $badMobile->id]]);

        $this->run_($rule->id);

        $skipped = $this->logs($rule, NotifyLog::STATUS_SKIPPED)->keyBy('user_id');
        $this->assertCount(2, $skipped);
        $this->assertSame(__('resources/notify_rule/strings.batch.skip_missing', ['channel' => 'پیامک']), $skipped[$noMobile->id]->error);
        $this->assertSame(__('resources/notify_rule/strings.batch.skip_invalid', ['channel' => 'پیامک']), $skipped[$badMobile->id]->error);
        $this->assertSame($noMobile->name, $skipped[$noMobile->id]->recipient);
        $this->assertSame('sms', $skipped[$noMobile->id]->channel);

        $sent = $this->logs($rule, NotifyLog::STATUS_SENT);
        $this->assertCount(1, $sent);
        $this->assertSame('*******1111', $sent[0]->recipient);
        $this->assertSame($good->id, (int)$sent[0]->user_id);
        $this->assertSame(3, (int)$this->logs($rule, NotifyLog::STATUS_SUMMARY)[0]->count);
    }

    public function test_a_light_payload_loads_fresh_values_and_a_deleted_snapshot_keeps_its_own(): void
    {
        $link = Link::withoutEvents(fn() => Link::factory()->create(['url_title' => 'Fresh title']));
        $user = $this->activeUser();
        $rule = $this->makeRule(['user_ids' => [$user->id], 'lines' => ['Title: {url_title}']]);
        $light = array_merge(array_diff_key($this->payload(), ['values' => 1]), ['id' => $link->id]);

        $this->run_($rule->id, $light);

        Mail::assertSent(NotifyMail::class, fn(NotifyMail $m) => $m->message->lines === ['Title: Fresh title']);

        Link::withoutEvents(fn() => $link->forceDelete());
        $deleted = ['event' => 'deleted', 'values' => ['url_title' => 'Snapshot title']] + $light;

        $this->run_($rule->id, $deleted);

        Mail::assertSent(NotifyMail::class, fn(NotifyMail $m) => $m->message->lines === ['Title: Snapshot title']);
        $this->assertSame(1, $this->logs($rule, NotifyLog::STATUS_SENT)->where('event', 'deleted')->count());
    }

    public function test_a_record_deleted_before_the_job_ran_sends_nothing_and_says_why(): void
    {
        $user = $this->activeUser();
        $rule = $this->makeRule(['user_ids' => [$user->id]]);
        $gone = array_merge(array_diff_key($this->payload(), ['values' => 1]), ['id' => 999999999]);

        $this->run_($rule->id, $gone);

        Mail::assertNothingSent();
        $summary = $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole();
        $this->assertSame(__('resources/notify_rule/strings.batch.record_gone'), $summary->error);
    }

    public function test_a_record_that_no_longer_matches_the_conditions_sends_nothing_and_says_why(): void
    {
        $link = Link::withoutEvents(fn() => Link::factory()->create(['url_title' => 'Changed']));
        $user = $this->activeUser();
        $rule = $this->makeRule([
            'user_ids' => [$user->id],
            'conditions' => ['match' => 'all', 'rules' => [['field' => 'url_title', 'op' => 'eq', 'value' => 'Mine']]],
        ]);
        $light = array_merge(array_diff_key($this->payload(), ['values' => 1]), ['id' => $link->id]);

        $this->run_($rule->id, $light);

        Mail::assertNothingSent();
        $this->assertSame(0, $this->logs($rule, NotifyLog::STATUS_SENT)->count());
        $summary = $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole();
        $this->assertSame(0, (int)$summary->count);
        $this->assertSame(__('resources/notify_rule/strings.batch.conditions_changed'), $summary->error);

        Link::withoutEvents(fn() => $link->update(['url_title' => 'Mine']));

        $this->run_($rule->id, $light);

        Mail::assertSent(NotifyMail::class, 1);
    }

    public function test_a_killed_coordinator_marks_only_its_own_run_and_leaves_an_earlier_success_alone(): void
    {
        $user = $this->activeUser();
        $rule = $this->makeRule(['user_ids' => [$user->id]]);
        $this->run_($rule->id);
        $earlier = $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole();

        $killed = new SendNotifyRule($rule->id, $this->payload());
        $killed->failed(new \RuntimeException('timeout'));

        $this->assertNull($earlier->fresh()->error);
        $own = NotifyLog::where('run_id', $killed->runId)->sole();
        $this->assertSame(NotifyLog::STATUS_SUMMARY, $own->status);
        $this->assertSame(__('resources/notify_rule/strings.batch.run_failed'), $own->error);
    }

    public function test_a_killed_coordinator_that_already_wrote_its_summary_updates_that_row_instead_of_adding_one(): void
    {
        $user = $this->activeUser();
        $rule = $this->makeRule(['user_ids' => [$user->id]]);
        $job = new SendNotifyRule($rule->id, $this->payload());
        NotifyLog::insert([[
            'notify_rule_id' => $rule->id, 'run_id' => $job->runId, 'source' => 'rule', 'count' => 1,
            'recipient' => 'x', 'channel' => 'all', 'status' => NotifyLog::STATUS_SUMMARY, 'created_at' => now(),
        ]]);

        $job->failed(new \RuntimeException('timeout'));

        $this->assertSame(1, NotifyLog::where('run_id', $job->runId)->count());
        $this->assertSame(__('resources/notify_rule/strings.batch.run_failed'), NotifyLog::where('run_id', $job->runId)->sole()->error);
    }

    public function test_an_internal_failure_becomes_a_summary_error_and_never_crashes_the_job(): void
    {
        $this->setChannels(['mail' => $this->fakeChannel(fn(array $a) => [], fn() => throw new \RuntimeException('boom'))]);
        $rule = $this->makeRule(['user_ids' => [$this->activeUser()->id]]);

        $this->run_($rule->id);

        $rows = NotifyLog::where('notify_rule_id', $rule->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(NotifyLog::STATUS_SUMMARY, $rows[0]->status);
        $this->assertSame(0, (int)$rows[0]->count);
        $this->assertSame(__('resources/notify_rule/strings.batch.run_failed'), $rows[0]->error);
    }

    public function test_styled_template_renders_with_the_tenant_primary_colour_and_plain_stays_default(): void
    {
        $user = $this->activeUser();
        $styled = $this->makeRule(['user_ids' => [$user->id], 'extra' => ['template' => 'styled']]);
        $plain = $this->makeRule(['user_ids' => [$user->id]]);

        $this->run_($styled->id);
        $this->run_($plain->id);

        $primary = app(\App\Services\TenantService::class)->mailPalette()['primary'];
        Mail::assertSent(NotifyMail::class, fn(NotifyMail $m) => $m->message->template === 'styled' && str_contains($m->render(), $primary));
        Mail::assertSent(NotifyMail::class, fn(NotifyMail $m) => $m->message->template === 'plain' && !str_contains($m->render(), $primary));
    }

    public function test_persian_subjects_are_direction_isolated_and_english_ones_are_untouched(): void
    {
        $user = $this->activeUser();
        $rule = $this->makeRule(['user_ids' => [$user->id], 'subject' => '[آزمایشی] سلام در {id}']);

        $this->run_($rule->id);

        Mail::assertSent(NotifyMail::class, fn(NotifyMail $m) => $m->message->subject === "\u{202B}[آزمایشی] سلام در 42\u{202C}");
        $this->assertSame('Hello', \App\Services\Notify\MessageBuilder::isolate('Hello'));
        $this->assertSame("\u{202B}سلام Hello\u{202C}", \App\Services\Notify\MessageBuilder::isolate("\u{202B}سلام Hello\u{202C}"));
    }

    public function test_an_unconfigured_sms_service_is_one_failed_row_for_the_whole_chunk_instead_of_a_false_sent(): void
    {
        config(['services.sms.provider' => 'kavenegar', 'services.kavenegar.key' => null]);
        $ids = [];

        foreach (['09120000001', '09120000002'] as $mobile) {
            $ids[] = $user = $this->activeUser();
            Profile::factory()->create(['user_id' => $user->id, 'cellphone' => $mobile]);
        }

        $rule = $this->makeRule(['channels' => ['sms'], 'user_ids' => array_map(fn($u) => $u->id, $ids)]);

        $this->run_($rule->id);

        $this->assertSame(0, $this->logs($rule, NotifyLog::STATUS_SENT)->count());
        $failed = $this->logs($rule, NotifyLog::STATUS_FAILED);
        $this->assertCount(1, $failed);
        $this->assertSame('sms', $failed[0]->channel);
        $this->assertSame(2, (int)$failed[0]->count);
        $this->assertNull($failed[0]->user_id);
        $this->assertStringContainsString('پیکربندی نشده', $failed[0]->error);
    }

    public function test_chunks_are_dispatched_per_channel_with_delays_that_run_across_channels(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-01-01 00:00:00');
        config(['notify.chunk.mail' => 2, 'notify.chunk.sms' => 2, 'notify.delay' => 3]);
        $contacts = [];

        foreach (range(1, 5) as $i) {
            $contacts[] = "chunk{$i}@example.com";
        }

        foreach (range(11, 13) as $i) {
            $contacts[] = "091200000{$i}";
        }

        $rule = $this->makeRule(['channels' => ['mail', 'sms'], 'extra' => ['contacts' => $contacts]]);

        $this->run_($rule->id);

        Queue::assertPushed(SendNotifyChunk::class, 5);
        $jobs = Queue::pushed(SendNotifyChunk::class)->values();
        $delays = $jobs->map(fn(SendNotifyChunk $j) => $j->delay->getTimestamp() - now()->getTimestamp())->all();
        $this->assertSame([0, 3, 6, 9, 12], $delays);
        $this->assertSame(['mail', 'mail', 'mail', 'sms', 'sms'], $jobs->pluck('channelKey')->all());
        $this->assertSame([2, 2, 1, 2, 1], $jobs->map(fn(SendNotifyChunk $j) => count($j->addresses))->all());
        $this->assertSame(1, $jobs->pluck('runId')->unique()->count());
        $this->assertSame(8, (int)$this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole()->count);
    }

    public function test_two_users_sharing_one_email_get_one_mail_and_the_second_is_logged_as_a_duplicate_skip(): void
    {
        $received = [];
        $this->setChannels(['mail' => $this->fakeChannel(function (array $addresses) use (&$received) {
            $received = array_merge($received, $addresses);

            return array_fill_keys($addresses, null);
        }, fn() => 'shared@example.com')]);
        $rule = $this->makeRule(['user_ids' => [$this->activeUser()->id, $this->activeUser()->id]]);

        $this->run_($rule->id);

        $this->assertSame(['shared@example.com'], $received);
        $this->assertSame(1, $this->logs($rule, NotifyLog::STATUS_SENT)->count());
        $skipped = $this->logs($rule, NotifyLog::STATUS_SKIPPED)->sole();
        $this->assertStringContainsString('تکراری', $skipped->error);
        $summary = $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole();
        $this->assertSame(2, (int)$summary->count);
        $this->assertNull($summary->error);
    }

    public function test_a_rule_whose_channels_are_all_unavailable_says_so_instead_of_a_silent_empty_run(): void
    {
        $this->setChannels([]);
        $rule = $this->makeRule(['user_ids' => [$this->activeUser()->id]]);

        $this->run_($rule->id);

        $this->assertSame(__('resources/notify_rule/strings.batch.no_channel'), $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole()->error);
    }

    public function test_summary_targets_distinct_people_while_sent_rows_count_deliveries_per_channel(): void
    {
        config(['services.sms.provider' => 'log']);
        $users = [];

        foreach (['09121110001', '09121110002'] as $mobile) {
            $users[] = $user = $this->activeUser();
            Profile::factory()->create(['user_id' => $user->id, 'cellphone' => $mobile]);
        }

        $rule = $this->makeRule(['channels' => ['mail', 'sms'], 'user_ids' => array_map(fn($u) => $u->id, $users)]);

        $this->run_($rule->id);

        $this->assertSame(2, (int)$this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole()->count);
        $sent = $this->logs($rule, NotifyLog::STATUS_SENT);
        $this->assertSame(4, (int)$sent->sum('count'));
        $this->assertSame(2, $sent->where('channel', 'mail')->count());
        $this->assertSame(2, $sent->where('channel', 'sms')->count());
    }

    public function test_the_ceiling_counts_distinct_people_not_channel_entries_duplicates_or_skips(): void
    {
        config(['services.sms.provider' => 'log', 'notify.ceiling' => 2]);
        $reachable = $this->activeUser();
        Profile::factory()->create(['user_id' => $reachable->id, 'cellphone' => '09121110003']);
        $inactive = User::factory()->create(['status' => 'inactive']);
        $rule = $this->makeRule([
            'channels' => ['mail', 'sms'],
            'user_ids' => [$reachable->id, $inactive->id],
            'extra' => ['contacts' => [$reachable->email, '09121110003']],
        ]);

        $this->run_($rule->id);

        $summary = $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole();
        $this->assertSame(2, (int)$summary->count);
        $this->assertNull($summary->error);
        $this->assertSame(2, $this->logs($rule, NotifyLog::STATUS_SENT)->count());

        config(['notify.ceiling' => 1]);
        NotifyLog::where('notify_rule_id', $rule->id)->delete();

        $this->run_($rule->id);

        $rows = NotifyLog::where('notify_rule_id', $rule->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(2, (int)$rows[0]->count);
        $this->assertSame(__('resources/notify_rule/strings.batch.ceiling', ['count' => 1]), $rows[0]->error);
    }

    public function test_a_dispatch_failing_midway_marks_the_summary_failed_and_never_throws(): void
    {
        config(['notify.chunk.mail' => 1]);
        $dispatched = 0;
        $bus = \Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')->andReturnUsing(function () use (&$dispatched) {
            if (++$dispatched === 2) {
                throw new \RuntimeException('queue down');
            }

            return 0;
        });
        app()->instance(Dispatcher::class, $bus);
        $rule = $this->makeRule(['extra' => ['contacts' => ['a@example.com', 'b@example.com', 'c@example.com']]]);

        $this->run_($rule->id);

        $this->assertSame(2, $dispatched);
        $summary = $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole();
        $this->assertSame(3, (int)$summary->count);
        $this->assertSame(__('resources/notify_rule/strings.batch.run_failed'), $summary->error);
    }

    public function test_a_one_off_rule_runs_the_whole_pipeline_without_loading_a_record_and_logs_a_null_event(): void
    {
        Queue::fake();
        $user = $this->activeUser();
        $rule = $this->makeRule([
            'model' => NotifyRule::ONE_OFF,
            'events' => [],
            'channels' => ['mail', 'sms'],
            'subject' => 'Notice from {actor}',
            'lines' => ['Sent {model}'],
            'user_ids' => [$user->id],
            'extra' => ['contacts' => ['outside@example.com', '09121234567']],
        ]);

        $this->run_($rule->id, [
            'model' => NotifyRule::ONE_OFF,
            'id' => $rule->id,
            'label' => 'manual send',
            'values' => [],
            'changed' => [],
            'actor' => 'Admin',
            'at' => now()->toDateTimeString(),
        ]);

        $summary = $this->logs($rule, NotifyLog::STATUS_SUMMARY)->sole();
        $this->assertNull($summary->error);
        $this->assertNull($summary->event);
        $this->assertSame((string) $rule->id, $summary->record_id);
        $this->assertSame(3, (int) $summary->count);

        Queue::assertPushed(SendNotifyChunk::class, 2);
        $jobs = Queue::pushed(SendNotifyChunk::class)->keyBy('channelKey');
        $this->assertEqualsCanonicalizing([$user->email, 'outside@example.com'], array_keys($jobs['mail']->addresses));
        $this->assertSame(['09121234567'], array_keys($jobs['sms']->addresses));
        $this->assertSame('Notice from Admin', $jobs['mail']->message->subject);
        $this->assertSame(['Sent manual send'], $jobs['mail']->message->lines);
        $this->assertNull($jobs['mail']->event);
    }

    public function test_inactive_or_missing_rule_is_a_no_op(): void
    {
        $user = $this->activeUser();
        $rule = $this->makeRule(['user_ids' => [$user->id], 'is_active' => false]);

        $this->run_($rule->id);
        $this->run_($rule->id + 100000);

        Mail::assertNothingSent();
        $this->assertSame(0, NotifyLog::where('notify_rule_id', $rule->id)->count());
    }
}
