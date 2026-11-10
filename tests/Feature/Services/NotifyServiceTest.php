<?php

namespace Tests\Feature\Services;

use App\Enums\NotifyEvent;
use App\Jobs\SendNotifyRuleJob;
use App\Mail\NotifyMail;
use App\Models\Concerns\HasNotifyEvents;
use App\Models\DMS;
use App\Models\EnergyTest;
use App\Models\Link;
use App\Models\Message;
use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\Permission;
use App\Models\Reminder;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use App\Services\Notify\Message as NotifyMessage;
use App\Services\Notify\NotifyRegistry;
use App\Services\Notify\NotifyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotifyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::forget(NotifyService::CACHE_KEY);
        Cache::forget('notify:trackables');
        Bus::fake();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget(NotifyService::CACHE_KEY);
        Cache::forget('notify:trackables');
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

    private function rule(int $id, array $overrides = []): NotifyRule
    {
        $rule = new NotifyRule(array_merge([
            'name' => 'rule ' . $id,
            'model' => Link::class,
            'events' => ['created'],
            'channels' => ['mail'],
            'subject' => 'subject',
            'lines' => ['line'],
            'is_active' => true,
        ], $overrides));
        $rule->id = $id;

        return $rule;
    }

    private function prime(NotifyRule ...$rules): void
    {
        Cache::put(NotifyService::CACHE_KEY, collect($rules)->groupBy('model'), now()->addHour());
    }

    private function dispatchedRuleIds(): array
    {
        return Bus::dispatched(SendNotifyRuleJob::class)->map(fn(SendNotifyRuleJob $job) => $job->ruleId)->sort()->values()->all();
    }

    private function changedLink(array $changes): Link
    {
        $link = Link::factory()->make();
        $link->syncOriginal();
        $link->forceFill($changes)->syncChanges();

        return $link;
    }

    public function test_no_matching_rule_means_zero_queries_and_no_dispatch(): void
    {
        $this->prime($this->rule(901, ['events' => ['deleted']]));
        $link = Link::factory()->make();

        DB::connection()->enableQueryLog();
        NotifyService::handle($link, NotifyEvent::Created);
        NotifyService::handle(new User(), NotifyEvent::Created);
        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();

        $this->assertSame([], $queries);
        Bus::assertNotDispatched(SendNotifyRuleJob::class);
    }

    public function test_enum_cast_attributes_become_their_plain_value_in_the_placeholder_values(): void
    {
        $model = new class extends Model {
            use HasNotifyEvents;

            protected $guarded = [];

            protected $casts = ['kind' => NotifyEvent::class];
        };
        $model->setRawAttributes(['kind' => 'created', 'name' => 'x']);

        $this->assertSame('created', $model->notifyValues()['kind']);
    }

    public function test_matching_event_dispatches_one_job_per_live_rule_with_an_event_time_payload(): void
    {
        $this->prime(
            $this->rule(911),
            $this->rule(912, ['events' => ['created', 'updated']]),
            $this->rule(913, ['events' => ['deleted']]),
        );

        NotifyService::handle(Link::factory()->make(['url_title' => 'Snapshot title']), NotifyEvent::Created);

        $this->assertSame([911, 912], $this->dispatchedRuleIds());
        Bus::assertDispatched(SendNotifyRuleJob::class, fn(SendNotifyRuleJob $job) => $job->payload['event'] === 'created'
            && $job->payload['model'] === Link::class
            && $job->payload['label'] === Link::notifyLabel()
            && !array_key_exists('values', $job->payload)
            && !str_contains(json_encode($job->payload), 'Snapshot title'));
    }

    public function test_the_light_payload_for_updated_has_no_values_while_deleted_keeps_the_snapshot_and_send_test_payloads_stay_full(): void
    {
        $this->prime($this->rule(915, ['events' => ['updated', 'deleted']]));
        $link = $this->changedLink(['url_title' => 'Gone soon']);

        NotifyService::handle($link, NotifyEvent::Updated);
        NotifyService::handle($link, NotifyEvent::Deleted);

        $payloads = Bus::dispatched(SendNotifyRuleJob::class)->map(fn(SendNotifyRuleJob $job) => $job->payload)->keyBy('event');
        $this->assertArrayNotHasKey('values', $payloads['updated']);
        $this->assertSame(['url_title'], $payloads['updated']['changed']);
        $this->assertSame('Gone soon', $payloads['deleted']['values']['url_title']);
        $this->assertSame('Gone soon', NotifyService::payloadFor($link, NotifyEvent::Updated)['values']['url_title']);
    }

    public function test_window_and_inactive_flag_exclude_rules_at_fire_time(): void
    {
        $this->prime(
            $this->rule(921),
            $this->rule(922, ['active_from' => now()->addDay()]),
            $this->rule(923, ['active_until' => now()->subDay()]),
            $this->rule(924, ['is_active' => false]),
            $this->rule(925, ['active_from' => now()->subDay(), 'active_until' => now()->addDay()]),
        );

        NotifyService::handle(Link::factory()->make(), NotifyEvent::Created);

        $this->assertSame([921, 925], $this->dispatchedRuleIds());
    }

    public function test_updated_with_watch_dispatches_only_when_a_watched_field_changed(): void
    {
        $this->prime(
            $this->rule(931, ['events' => ['updated'], 'extra' => ['watch' => ['url_title']]]),
            $this->rule(932, ['events' => ['updated']]),
            $this->rule(933, ['events' => ['updated'], 'extra' => ['watch' => []]]),
        );

        NotifyService::handle($this->changedLink(['sequence' => 7]), NotifyEvent::Updated);
        $this->assertSame([932, 933], $this->dispatchedRuleIds());

        Bus::fake();
        NotifyService::handle($this->changedLink(['url_title' => 'changed']), NotifyEvent::Updated);
        $this->assertSame([931, 932, 933], $this->dispatchedRuleIds());
    }

    public function test_payload_values_come_from_real_columns_only_and_private_scores_are_excluded(): void
    {
        $energy = new EnergyTest(['user_id' => 1, 'mind_score' => 7, 'overall_score' => 8]);
        $this->assertArrayNotHasKey('mind_score', $energy->notifyValues());
        $this->assertArrayNotHasKey('overall_score', $energy->notifyValues());
        $this->assertArrayNotHasKey('mind_score', array_flip(EnergyTest::notifyPlaceholders()));

        $task = new Task(['title' => 'Plan']);
        $this->assertSame('Plan', $task->notifyValues()['title']);
        $this->assertArrayNotHasKey('assignee_name', $task->notifyValues());
        $this->assertArrayNotHasKey('can_delete', $task->notifyValues());
    }

    public function test_a_reminder_relays_its_title_and_notes_capped_at_500_characters(): void
    {
        $reminder = new Reminder(['title' => 'Pay the invoice', 'notes' => str_repeat('n', 900)]);

        $values = $reminder->notifyValues();

        $this->assertSame('Pay the invoice', $values['title']);
        $this->assertSame(str_repeat('n', 500) . '...', $values['notes']);
    }

    public function test_bookkeeping_only_updates_fire_nothing_even_for_rules_without_a_watch_list(): void
    {
        $this->prime($this->rule(961, ['events' => ['updated']]));

        NotifyService::handle($this->changedLink(['notified_at' => now(), 'updated_at' => now()]), NotifyEvent::Updated);
        Bus::assertNotDispatched(SendNotifyRuleJob::class);

        NotifyService::handle($this->changedLink(['url_title' => 'real change']), NotifyEvent::Updated);
        $this->assertSame([961], $this->dispatchedRuleIds());
    }

    public function test_tenant_switches_turn_off_the_whole_service_or_a_single_channel(): void
    {
        $this->prime($this->rule(951));

        config(['app.off' => ['notify']]);
        NotifyService::handle(Link::factory()->make(), NotifyEvent::Created);
        Bus::assertNotDispatched(SendNotifyRuleJob::class);
        $this->assertSame([], NotifyRegistry::channelOptions());
        $this->assertTrue(tenantOffName('filament.admin.resources.notify-rules.index'));

        config(['app.off' => ['notify:sms']]);
        NotifyService::handle(Link::factory()->make(), NotifyEvent::Created);
        $this->assertSame([951], $this->dispatchedRuleIds());
        $this->assertSame(['mail'], array_keys(NotifyRegistry::channelOptions()));
        $this->assertNull(NotifyRegistry::channel('sms'));
    }

    public function test_watch_list_is_ignored_for_created_events(): void
    {
        $this->prime($this->rule(941, ['events' => ['created'], 'extra' => ['watch' => ['url_title']]]));

        NotifyService::handle(Link::factory()->make(), NotifyEvent::Created);

        $this->assertSame([941], $this->dispatchedRuleIds());
    }

    public function test_saving_and_deleting_a_rule_flushes_the_cache_and_only_active_rules_are_cached(): void
    {
        $base = ['name' => 'on', 'model' => Link::class, 'events' => ['created'], 'channels' => ['mail'], 'subject' => 's', 'lines' => ['l']];
        $active = NotifyRule::create($base + ['is_active' => true]);
        $off = NotifyRule::create(array_merge($base, ['name' => 'off', 'is_active' => false]));

        $cachedIds = NotifyService::rulesFor(Link::class)->pluck('id')->all();
        $this->assertContains($active->id, $cachedIds);
        $this->assertNotContains($off->id, $cachedIds);
        $this->assertTrue(Cache::has(NotifyService::CACHE_KEY));

        $active->update(['name' => 'renamed']);
        $this->assertFalse(Cache::has(NotifyService::CACHE_KEY));

        NotifyService::rulesFor(Link::class);
        $this->assertTrue(Cache::has(NotifyService::CACHE_KEY));
        $active->delete();
        $this->assertFalse(Cache::has(NotifyService::CACHE_KEY));
    }

    public function test_trackables_hide_the_models_of_modules_the_tenant_switched_off(): void
    {
        config(['app.off' => ['reservation', 'dms']]);
        Cache::forget('notify:trackables');

        $trackables = NotifyRegistry::trackables();

        $this->assertArrayNotHasKey(Reservation::class, $trackables);
        $this->assertArrayNotHasKey(DMS::class, $trackables);
        $this->assertArrayHasKey(Task::class, $trackables);
        $this->assertArrayHasKey(Link::class, $trackables);
    }

    public function test_the_rule_form_only_offers_models_the_admin_can_open_themselves(): void
    {
        Cache::forget('notify:trackables');

        $this->assertSame([], NotifyRegistry::permitted());

        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true]);
        $this->actingAs($admin);

        $this->assertArrayHasKey(Link::class, NotifyRegistry::permitted());
    }

    public function test_trackables_include_models_with_the_trait_and_exclude_recursion_and_hot_models(): void
    {
        $trackables = NotifyRegistry::trackables();

        $this->assertArrayHasKey(Link::class, $trackables);

        foreach ([NotifyRule::class, NotifyLog::class, User::class, Message::class] as $excluded) {
            $this->assertArrayNotHasKey($excluded, $trackables, $excluded);
        }
    }

    public function test_one_off_rules_never_enter_the_rules_cache_and_never_fire_from_model_saves(): void
    {
        $oneOff = NotifyRule::create([
            'name' => 'one-off',
            'model' => NotifyRule::ONE_OFF,
            'events' => [],
            'channels' => ['mail'],
            'subject' => 's',
            'lines' => ['l'],
            'is_active' => true,
        ]);
        $tracked = NotifyRule::create([
            'name' => 'tracked',
            'model' => Link::class,
            'events' => ['created'],
            'channels' => ['mail'],
            'subject' => 's',
            'lines' => ['l'],
            'is_active' => true,
        ]);

        $this->assertContains($tracked->id, NotifyService::rulesFor(Link::class)->pluck('id')->all());
        $this->assertNotContains($oneOff->id, NotifyService::rulesFor(Link::class)->pluck('id')->all());
        $this->assertSame([], NotifyService::rulesFor(NotifyRule::ONE_OFF)->all());
        $this->assertNotContains(NotifyRule::ONE_OFF, Cache::get(NotifyService::CACHE_KEY)->keys()->all());

        NotifyService::handle(Link::factory()->make(), NotifyEvent::Created);

        $this->assertContains($tracked->id, $this->dispatchedRuleIds());
        $this->assertNotContains($oneOff->id, $this->dispatchedRuleIds());
    }

    public function test_pruning_removes_only_one_off_rules_older_than_ninety_days(): void
    {
        $make = fn (string $model, int $days) => tap(NotifyRule::create([
            'name' => $model.$days,
            'model' => $model,
            'events' => [],
            'channels' => ['mail'],
            'subject' => 's',
            'lines' => ['l'],
            'is_active' => true,
        ]), fn (NotifyRule $r) => NotifyRule::whereKey($r->id)->update(['created_at' => now()->subDays($days)]));

        $oldOneOff = $make(NotifyRule::ONE_OFF, 91);
        $freshOneOff = $make(NotifyRule::ONE_OFF, 89);
        $oldTracked = $make(Link::class, 200);

        (new NotifyRule)->pruneAll();

        $this->assertNull(NotifyRule::find($oldOneOff->id));
        $this->assertNotNull(NotifyRule::find($freshOneOff->id));
        $this->assertNotNull(NotifyRule::find($oldTracked->id));
    }

    public function test_sample_payload_refuses_a_model_that_is_no_longer_trackable(): void
    {
        $this->expectException(\RuntimeException::class);

        NotifyService::samplePayload($this->rule(951, ['model' => NotifyLog::class]));
    }

    public function test_notify_mail_attaches_only_existing_files_and_defaults_to_no_attachments(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'notify');
        file_put_contents($file, '%PDF-1.4 test');

        try {
            $plain = new NotifyMail(new NotifyMessage('S', ['L'], 'r', 'M', 'e'));
            $withFiles = new NotifyMail(new NotifyMessage('S', ['L'], 'r', 'M', 'e', 'plain', null, null, [$file, '/no/such/file.pdf', 5]));

            $this->assertSame([], (new NotifyMessage('S', ['L'], 'r', 'M', 'e'))->attachments);
            $this->assertCount(0, $plain->attachments());
            $this->assertCount(1, $withFiles->attachments());
        } finally {
            unlink($file);
        }
    }

    public function test_correspondence_mail_message_carries_its_private_link_and_pdf_attachment(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'correspondence-mail');
        file_put_contents($file, '%PDF-1.4 test');

        try {
            $message = new NotifyMessage(
                subject: 'نامه از Fateh',
                lines: ['برای مشاهده از پیوند امن استفاده کنید.'],
                ruleName: 'correspondence',
                modelLabel: 'مکاتبه',
                event: 'ارسال',
                template: 'styled',
                url: 'https://example.test/c/12-token',
                action: 'مشاهده نامه',
                attachments: [$file],
            );
            $mail = new NotifyMail($message);

            $this->assertSame('correspondence', $message->ruleName);
            $this->assertSame('https://example.test/c/12-token', $message->url);
            $this->assertSame('مشاهده نامه', $message->action);
            $this->assertCount(1, $mail->attachments());
            $this->assertStringNotContainsString('بدنه نامه', $message->text());
        } finally {
            unlink($file);
        }
    }
}
