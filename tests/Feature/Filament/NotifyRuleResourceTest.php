<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\NotifyRuleResource\Pages\CreateNotifyRule;
use App\Filament\Resources\NotifyRuleResource\Pages\EditNotifyRule;
use App\Filament\Resources\NotifyRuleResource\Pages\ListNotifyRules;
use App\Filament\Resources\NotifyRuleResource\RelationManagers\LogsRelationManager;
use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyLogTablePresenter;
use App\Jobs\SendNotifyRule;
use App\Mail\NotifyMail;
use App\Models\Link;
use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\Notify\NotifyService;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class NotifyRuleResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::forget('notify:trackables');
        Cache::forget('notify:rules');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget('notify:rules');
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function makeRule(array $overrides = []): NotifyRule
    {
        return NotifyRule::create(array_merge([
            'name' => 'resource test rule',
            'model' => Link::class,
            'events' => ['created'],
            'channels' => ['mail'],
            'subject' => 'Hello {model}',
            'lines' => ['A line'],
            'is_active' => true,
        ], $overrides));
    }

    private function dateFields(string $prefix, Carbon $at): array
    {
        $j = Jalalian::fromCarbon($at);

        return [
            "{$prefix}_date" => $at->format('Y-m-d'),
            "{$prefix}_date_year" => $j->getYear(),
            "{$prefix}_date_month" => $j->getMonth(),
            "{$prefix}_date_day" => $j->getDay(),
            "{$prefix}_time" => $at->format('H:i'),
        ];
    }

    public function test_list_renders_and_delete_action_removes_the_rule(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule();

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$rule])
            ->callTableAction(DeleteAction::class, $rule);

        $this->assertDatabaseMissing('notify_rules', ['id' => $rule->id]);
    }

    public function test_create_persists_the_rule_with_events_channels_and_lines(): void
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm([
                'name' => 'created from the form',
                'model' => Link::class,
                'events' => ['created', 'deleted'],
                'channels' => ['mail'],
                'subject' => 'New {model}',
                'lines' => [['text' => 'first line']],
                'user_ids' => [$admin->id],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $rule = NotifyRule::where('name', 'created from the form')->firstOrFail();
        $this->assertSame(Link::class, $rule->model);
        $this->assertEqualsCanonicalizing(['created', 'deleted'], $rule->events);
        $this->assertSame(['mail'], $rule->channels);
        $this->assertSame(['first line'], array_values($rule->lines));
        $this->assertNull($rule->active_from);
        $this->assertNull($rule->active_until);
    }

    public function test_create_needs_at_least_one_recipient_unless_an_audience_is_chosen(): void
    {
        $admin = $this->developer();
        $base = [
            'name' => 'recipient rule',
            'model' => Link::class,
            'events' => ['created'],
            'channels' => ['mail'],
            'subject' => 'x',
            'lines' => [['text' => 'y']],
        ];

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm($base)
            ->call('create')
            ->assertHasFormErrors(['user_ids' => 'required', 'extra.contacts' => 'required']);

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm($base + ['extra' => ['contacts' => ['outside@example.com']]])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm(array_merge($base, ['name' => 'all staff rule', 'extra' => ['audience' => 'all']]))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_editing_an_old_rule_without_recipients_demands_one_and_a_departments_audience_needs_no_users(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule();

        Livewire::actingAs($admin)->test(EditNotifyRule::class, ['record' => $rule->getRouteKey()])
            ->fillForm(['name' => 'still no one'])
            ->call('save')
            ->assertHasFormErrors(['user_ids' => 'required']);

        $dept = \App\Models\Department::factory()->create();

        Livewire::actingAs($admin)->test(EditNotifyRule::class, ['record' => $rule->getRouteKey()])
            ->fillForm(['extra' => ['audience' => 'departments', 'departments' => [$dept->code]]])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_create_rejects_a_window_that_ends_before_it_starts(): void
    {
        $admin = $this->developer();
        $from = Carbon::parse('2027-03-10 09:00');
        $until = Carbon::parse('2027-03-01 09:00');

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm([
                'name' => 'bad window rule',
                'user_ids' => [$admin->id],
                'model' => Link::class,
                'events' => ['created'],
                'channels' => ['mail'],
                'subject' => 'x',
                'lines' => [['text' => 'y']],
                ...$this->dateFields('active_from', $from),
                ...$this->dateFields('active_until', $until),
            ])
            ->call('create')
            ->assertHasErrors(['data.active_until_date_day']);

        $this->assertDatabaseMissing('notify_rules', ['name' => 'bad window rule']);
    }

    public function test_edit_updates_the_rule_and_a_valid_window_is_stored(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule(['user_ids' => [$admin->id]]);
        $from = Carbon::parse('2027-03-01 00:00');
        $until = Carbon::parse('2027-03-10 23:59');

        Livewire::actingAs($admin)->test(EditNotifyRule::class, ['record' => $rule->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'name' => 'renamed rule',
                ...$this->dateFields('active_from', $from),
                ...$this->dateFields('active_until', $until),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $rule->fresh();
        $this->assertSame('renamed rule', $fresh->name);
        $this->assertSame('2027-03-01 00:00', $fresh->active_from->format('Y-m-d H:i'));
        $this->assertSame('2027-03-10 23:59', $fresh->active_until->format('Y-m-d H:i'));
    }

    public function test_edit_keeps_contacts_and_unknown_extra_keys_and_drops_a_stale_watch_list(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule([
            'events' => ['created'],
            'extra' => ['contacts' => ['keep@example.com', '09121234567'], 'template' => 'styled', 'future_key' => 'kept', 'watch' => ['url_title']],
        ]);

        Livewire::actingAs($admin)->test(EditNotifyRule::class, ['record' => $rule->getRouteKey()])
            ->fillForm(['name' => 'renamed again'])
            ->call('save')
            ->assertHasNoFormErrors();

        $extra = $rule->fresh()->extra;
        $this->assertSame(['keep@example.com', '09121234567'], $extra['contacts']);
        $this->assertSame('styled', $extra['template']);
        $this->assertSame('kept', $extra['future_key']);
        $this->assertArrayNotHasKey('watch', $extra);
    }

    public function test_create_stores_a_condition_row_and_rejects_an_illegal_one_on_the_conditions_path(): void
    {
        $admin = $this->developer();
        $base = [
            'model' => Link::class,
            'events' => ['created'],
            'channels' => ['mail'],
            'subject' => 'x',
            'lines' => [['text' => 'y']],
            'user_ids' => [$admin->id],
        ];

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm($base + [
                'name' => 'conditional rule',
                'conditions' => ['match' => 'any', 'rules' => [['field' => 'url_title', 'op' => 'contains', 'value' => ' Mine ']]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertEquals(
            ['match' => 'any', 'rules' => [['field' => 'url_title', 'op' => 'contains', 'value' => 'Mine']]],
            NotifyRule::where('name', 'conditional rule')->firstOrFail()->conditions,
        );

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm($base + [
                'name' => 'blank value rule',
                'conditions' => ['match' => 'all', 'rules' => [['field' => 'url_title', 'op' => 'eq', 'value' => '']]],
            ])
            ->call('create')
            ->assertHasErrors(['data.conditions.rules']);

        Livewire::actingAs($admin)->test(CreateNotifyRule::class)
            ->fillForm($base + [
                'name' => 'illegal op rule',
                'conditions' => ['match' => 'all', 'rules' => [['field' => 'url_title', 'op' => 'gt', 'value' => '1']]],
            ])
            ->call('create')
            ->assertHasErrors();

        $this->assertDatabaseMissing('notify_rules', ['name' => 'blank value rule']);
        $this->assertDatabaseMissing('notify_rules', ['name' => 'illegal op rule']);
    }

    public function test_edit_hydrates_stored_conditions_and_saves_them_unchanged(): void
    {
        $admin = $this->developer();
        $conditions = ['match' => 'any', 'rules' => [['field' => 'url_title', 'op' => 'eq', 'value' => 'Mine']]];
        $rule = $this->makeRule(['user_ids' => [$admin->id], 'conditions' => $conditions]);

        $component = Livewire::actingAs($admin)->test(EditNotifyRule::class, ['record' => $rule->getRouteKey()])
            ->assertSuccessful();

        $this->assertSame('any', $component->get('data.conditions.match'));
        $row = array_values($component->get('data.conditions.rules'))[0];
        $this->assertSame(['url_title', 'eq', 'Mine'], [$row['field'], $row['op'], $row['value']]);

        $component->fillForm(['name' => 'renamed conditional'])->call('save')->assertHasNoFormErrors();

        $this->assertEquals($conditions, $rule->fresh()->conditions);
    }

    public function test_logs_relation_manager_lists_only_this_rules_logs(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule();
        $other = $this->makeRule(['name' => 'other rule']);
        $mine = NotifyLog::create(['notify_rule_id' => $rule->id, 'user_id' => $admin->id, 'event' => 'created', 'record_id' => '7', 'recipient' => 'mine@example.com', 'channel' => 'mail', 'status' => 'sent']);
        $theirs = NotifyLog::create(['notify_rule_id' => $other->id, 'recipient' => 'theirs@example.com', 'channel' => 'mail', 'status' => 'failed', 'error' => 'boom']);
        $theirSummary = NotifyLog::create(['notify_rule_id' => $other->id, 'recipient' => 'summary', 'channel' => 'all', 'status' => 'summary', 'count' => 2]);
        $mySummary = NotifyLog::create(['notify_rule_id' => $rule->id, 'recipient' => 'summary', 'channel' => 'all', 'status' => 'summary', 'count' => 3]);
        $myFailed = NotifyLog::create(['notify_rule_id' => $rule->id, 'recipient' => 'bad@example.com', 'channel' => 'mail', 'status' => 'failed', 'error' => 'boom']);
        $mySkipped = NotifyLog::create(['notify_rule_id' => $rule->id, 'recipient' => 'skipped person', 'channel' => 'mail', 'status' => 'skipped', 'error' => 'no address']);

        $component = Livewire::actingAs($admin)
            ->test(LogsRelationManager::class, ['ownerRecord' => $rule, 'pageClass' => EditNotifyRule::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$mySummary])
            ->assertCanNotSeeTableRecords([$mine, $myFailed, $mySkipped, $theirs, $theirSummary]);

        $component->filterTable('status', null)
            ->assertCanSeeTableRecords([$mine, $mySummary, $myFailed, $mySkipped])
            ->assertCanNotSeeTableRecords([$theirs, $theirSummary]);

        $component->filterTable('problems', true)
            ->assertCanSeeTableRecords([$myFailed, $mySkipped])
            ->assertCanNotSeeTableRecords([$mine, $mySummary]);
    }

    public function test_logs_relation_manager_is_read_only(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule();

        $table = Livewire::actingAs($admin)
            ->test(LogsRelationManager::class, ['ownerRecord' => $rule, 'pageClass' => EditNotifyRule::class])
            ->instance()->getTable();

        $this->assertSame([], $table->getFlatRecordActions());
        $this->assertSame([], $table->getFlatBulkActions());
        $this->assertSame([], $table->getHeaderActions());
    }

    public function test_logs_relation_manager_needs_view_permission_not_just_update(): void
    {
        $rule = $this->makeRule();
        $updater = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create(['user_id' => $updater->id, 'is_super_admin' => false, 'abilities' => [['module' => 'notify_rule', 'actions' => ['update']]]]);
        $viewer = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create(['user_id' => $viewer->id, 'is_super_admin' => false, 'abilities' => [['module' => 'notify_rule', 'actions' => ['view']]]]);

        $this->actingAs($updater);
        $this->assertFalse(LogsRelationManager::canViewForRecord($rule, EditNotifyRule::class));

        $this->actingAs($viewer);
        $this->assertTrue(LogsRelationManager::canViewForRecord($rule, EditNotifyRule::class));
    }

    public function test_send_test_mails_only_the_acting_admin_and_writes_no_log_rows(): void
    {
        Mail::fake();
        $admin = $this->developer();
        $colleague = User::factory()->create(['status' => 'active']);
        $rule = $this->makeRule(['user_ids' => [$colleague->id], 'extra' => ['contacts' => ['external@example.com']]]);

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->callTableAction('sendTestMail', $rule);

        Mail::assertSent(NotifyMail::class, 1);
        Mail::assertSent(NotifyMail::class, fn(NotifyMail $m) => $m->hasTo($admin->email)
            && str_starts_with($m->message->subject, __('resources/notify_rule/strings.actions.test_prefix')));
        $this->assertSame(0, NotifyLog::where('notify_rule_id', $rule->id)->count());
    }

    public function test_send_message_creates_one_off_rule_and_dispatches_the_coordinator_once(): void
    {
        Bus::fake();
        $admin = $this->developer();
        $before = NotifyRule::count();

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->mountAction('sendMessage')
            ->set('mountedActions.0.data.lines', [['text' => 'hello everyone']])
            ->fillForm([
                'channels' => ['mail'],
                'extra' => ['audience' => 'users', 'contacts' => ['outside@example.com']],
                'subject' => 'Plain notice',
            ])
            ->assertWizardCurrentStep(1)
            ->goToNextWizardStep()
            ->assertHasNoActionErrors()
            ->assertWizardCurrentStep(2)
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertNotified(__('resources/notify_rule/strings.manual.queued'));

        $this->assertSame($before + 1, NotifyRule::count());
        $rule = NotifyRule::oneOff()->latest('id')->firstOrFail();
        $this->assertSame(NotifyRule::ONE_OFF, $rule->model);
        $this->assertSame([], $rule->events);
        $this->assertTrue($rule->is_active);
        $this->assertSame(['mail'], $rule->channels);
        $this->assertSame(['outside@example.com'], $rule->extra['contacts']);

        Bus::assertDispatchedTimes(SendNotifyRule::class, 1);
        Bus::assertDispatched(SendNotifyRule::class, fn (SendNotifyRule $job) => $job->ruleId === $rule->id
            && $job->payload['model'] === NotifyRule::ONE_OFF
            && $job->payload['values'] === []
            && ! array_key_exists('event', $job->payload)
            && $job->payload['actor'] === $admin->name);
    }

    public function test_send_message_without_a_recipient_creates_and_dispatches_nothing(): void
    {
        Bus::fake();
        $admin = $this->developer();
        $before = NotifyRule::count();

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->mountAction('sendMessage')
            ->set('mountedActions.0.data.lines', [['text' => 'hello']])
            ->fillForm([
                'channels' => ['mail'],
                'subject' => 'Plain notice',
            ])
            ->callMountedAction()
            ->assertHasActionErrors(['user_ids' => 'required', 'extra.contacts' => 'required']);

        $this->assertSame($before, NotifyRule::count());
        Bus::assertNotDispatched(SendNotifyRule::class);
    }

    public function test_send_message_step_one_blocks_without_a_channel_or_recipient(): void
    {
        Bus::fake();

        Livewire::actingAs($this->developer())->test(ListNotifyRules::class)
            ->mountAction('sendMessage')
            ->goToNextWizardStep()
            ->assertHasActionErrors(['channels' => 'required', 'user_ids' => 'required', 'extra.contacts' => 'required']);

        Bus::assertNotDispatched(SendNotifyRule::class);
    }

    public function test_send_message_step_two_blocks_without_a_subject_or_lines(): void
    {
        Bus::fake();
        $before = NotifyRule::count();

        Livewire::actingAs($this->developer())->test(ListNotifyRules::class)
            ->mountAction('sendMessage')
            ->set('mountedActions.0.data.lines', [])
            ->fillForm(['channels' => ['mail'], 'extra' => ['audience' => 'all']])
            ->goToNextWizardStep()
            ->assertHasNoActionErrors()
            ->assertWizardCurrentStep(2)
            ->callMountedAction()
            ->assertHasActionErrors(['subject' => 'required', 'lines' => 'required']);

        $this->assertSame($before, NotifyRule::count());
        Bus::assertNotDispatched(SendNotifyRule::class);
    }

    public function test_send_message_summary_line_names_the_audience_and_channels(): void
    {
        app()->setLocale('fa');
        $page = Livewire::actingAs($this->developer())->test(ListNotifyRules::class)
            ->mountAction('sendMessage')
            ->fillForm(['channels' => ['mail'], 'extra' => ['audience' => 'all']]);
        $schema = (new \ReflectionMethod($page->instance(), 'getMountedActionSchema'))->invoke($page->instance());
        $summary = (string) collect($schema->getFlatComponents())
            ->filter(fn ($c) => $c instanceof \Filament\Schemas\Components\Text)
            ->map(fn ($c) => (string) $c->getContent())
            ->first(fn ($text) => str_starts_with($text, 'ارسال به:'));

        $this->assertStringContainsString(__('resources/notify_rule/strings.audience.all'), $summary);
        $this->assertStringContainsString(\App\Services\Notify\NotifyRegistry::channelOptions()['mail'], $summary);
    }

    public function test_send_message_with_an_audience_needs_no_individual_recipient(): void
    {
        Bus::fake();
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->mountAction('sendMessage')
            ->set('mountedActions.0.data.lines', [['text' => 'hello']])
            ->fillForm([
                'channels' => ['mail'],
                'extra' => ['audience' => 'all'],
                'subject' => 'Company notice',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        Bus::assertDispatchedTimes(SendNotifyRule::class, 1);
    }

    public function test_a_queue_failure_removes_the_rule_and_shows_a_danger_notification(): void
    {
        $admin = $this->developer();
        RateLimiter::clear('notify-manual:' . $admin->id);
        $bus = \Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')->andThrow(new \RuntimeException('queue down'));
        app()->instance(Dispatcher::class, $bus);
        $before = NotifyRule::count();

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->mountAction('sendMessage')
            ->set('mountedActions.0.data.lines', [['text' => 'hello']])
            ->fillForm([
                'channels' => ['mail'],
                'extra' => ['audience' => 'all'],
                'subject' => 'Company notice',
            ])
            ->callMountedAction()
            ->assertNotified(__('resources/notify_rule/strings.manual.failed'))
            ->assertNotNotified(__('resources/notify_rule/strings.manual.throttled'));

        $this->assertSame($before, NotifyRule::count());
        RateLimiter::clear('notify-manual:' . $admin->id);
    }

    public function test_the_one_off_modal_lists_only_placeholders_that_resolve_for_a_manual_send(): void
    {
        app()->setLocale('fa');
        $page = Livewire::actingAs($this->developer())->test(ListNotifyRules::class)->mountAction('sendMessage');
        $schema = (new \ReflectionMethod($page->instance(), 'getMountedActionSchema'))->invoke($page->instance());
        $hint = (string) collect($schema->getFlatComponents())->first(fn ($c) => $c instanceof \Filament\Forms\Components\TextInput && $c->getName() === 'subject')->getChildSchema(\Filament\Forms\Components\Field::BELOW_CONTENT_SCHEMA_KEY)->toHtml();

        $this->assertStringContainsString('{انجام‌دهنده}', $hint);
        $this->assertStringContainsString('{تاریخ و ساعت}', $hint);
        $this->assertStringNotContainsString('{نوع تغییر}', $hint);
    }

    public function test_the_styled_mail_draws_the_event_pill_only_when_there_is_an_event(): void
    {
        $render = fn (string $event) => (new NotifyMail(new \App\Services\Notify\Message('S', ['L'], 'r', 'M', $event, 'styled')))->render();

        $this->assertStringNotContainsString('border-radius:999px', $render(''));
        $this->assertStringContainsString('border-radius:999px', $render('سررسید'));
    }

    public function test_the_logs_rule_filter_searches_rules_by_name_with_a_cap(): void
    {
        $this->makeRule(['name' => 'zz-needle-rule']);
        foreach (range(1, 55) as $i) {
            $this->makeRule(['name' => "zz-bulk-{$i}"]);
        }
        $search = NotifyLogTablePresenter::ruleFilter()->getSearchResultsUsingCallback();

        $this->assertSame(['zz-needle-rule'], array_values($search('zz-needle')));
        $this->assertCount(50, $search('zz-bulk'));
    }

    public function test_a_second_send_within_a_minute_is_blocked_and_creates_and_dispatches_nothing(): void
    {
        Bus::fake();
        $admin = $this->developer();
        RateLimiter::clear('notify-manual:' . $admin->id);
        $payload = ['channels' => ['mail'], 'extra' => ['audience' => 'all'], 'subject' => 'Notice'];

        $component = Livewire::actingAs($admin)->test(ListNotifyRules::class);

        foreach (range(1, 3) as $_) {
            $component->mountAction('sendMessage')
                ->set('mountedActions.0.data.lines', [['text' => 'hello']])
                ->fillForm($payload)
                ->callMountedAction()
                ->assertNotified(__('resources/notify_rule/strings.manual.queued'));
        }

        $count = NotifyRule::oneOff()->count();

        $component->mountAction('sendMessage')
            ->set('mountedActions.0.data.lines', [['text' => 'hello']])
            ->fillForm($payload)
            ->callMountedAction()
            ->assertNotified(__('resources/notify_rule/strings.manual.throttled'));

        $this->assertSame($count, NotifyRule::oneOff()->count());
        Bus::assertDispatchedTimes(SendNotifyRule::class, 3);
        RateLimiter::clear('notify-manual:' . $admin->id);
    }

    public function test_the_active_toggle_is_disabled_for_a_one_off_row_only(): void
    {
        $admin = $this->developer();
        $oneOff = $this->makeRule(['model' => NotifyRule::ONE_OFF, 'events' => [], 'user_ids' => [$admin->id]]);
        $normal = $this->makeRule(['user_ids' => [$admin->id]]);

        $component = Livewire::actingAs($admin)->test(ListNotifyRules::class);
        $table = $component->instance()->getTable();

        $this->assertTrue($table->getColumn('is_active')->record($oneOff)->isDisabled());
        $this->assertFalse($table->getColumn('is_active')->record($normal)->isDisabled());
    }

    public function test_the_view_modal_of_an_audience_based_one_off_shows_the_audience_label(): void
    {
        $admin = $this->developer();
        $oneOff = $this->makeRule(['model' => NotifyRule::ONE_OFF, 'events' => [], 'extra' => ['audience' => 'all']]);

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->mountTableAction('view', $oneOff)
            ->assertSee(__('resources/notify_rule/strings.audience.all'));
    }

    public function test_one_off_saves_and_deletes_keep_the_rules_cache_but_a_normal_rule_clears_it(): void
    {
        Cache::put(NotifyService::CACHE_KEY, 'sentinel', 600);

        $oneOff = $this->makeRule(['model' => NotifyRule::ONE_OFF, 'events' => []]);
        $this->assertSame('sentinel', Cache::get(NotifyService::CACHE_KEY));

        $oneOff->delete();
        $this->assertSame('sentinel', Cache::get(NotifyService::CACHE_KEY));

        $this->makeRule();
        $this->assertNull(Cache::get(NotifyService::CACHE_KEY));
    }

    public function test_a_one_off_rule_cannot_be_edited_but_can_be_viewed_and_deleted(): void
    {
        $admin = $this->developer();
        $oneOff = $this->makeRule(['model' => NotifyRule::ONE_OFF, 'events' => [], 'user_ids' => [$admin->id]]);
        $normal = $this->makeRule(['user_ids' => [$admin->id]]);

        $this->actingAs($admin);
        $this->assertFalse(NotifyRuleResource::canEdit($oneOff));
        $this->assertTrue(NotifyRuleResource::canEdit($normal));
        $this->assertTrue(NotifyRuleResource::canDelete($oneOff));

        Livewire::test(ListNotifyRules::class)
            ->assertTableActionHidden('edit', $oneOff)
            ->assertTableActionVisible('edit', $normal)
            ->assertTableActionVisible('view', $oneOff);

        Livewire::test(EditNotifyRule::class, ['record' => $oneOff->getRouteKey()])->assertForbidden();
    }

    public function test_the_rule_form_still_renders_its_recipient_and_message_fields(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule(['user_ids' => [$admin->id]]);

        $component = Livewire::actingAs($admin)->test(EditNotifyRule::class, ['record' => $rule->getRouteKey()])->assertSuccessful();

        foreach (['extra.audience', 'user_ids', 'extra.contacts', 'extra.template', 'subject', 'lines'] as $field) {
            $component->assertFormFieldExists($field);
        }
    }

    public function test_each_test_button_appears_only_for_a_channel_the_rule_uses_and_the_sms_one_never_sends_mail(): void
    {
        Mail::fake();
        $admin = $this->developer();
        $mailOnly = $this->makeRule();
        $smsOnly = $this->makeRule(['channels' => ['sms']]);

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->assertTableActionVisible('sendTestMail', $mailOnly)
            ->assertTableActionHidden('sendTestSms', $mailOnly)
            ->assertTableActionHidden('sendTestMail', $smsOnly)
            ->assertTableActionVisible('sendTestSms', $smsOnly)
            ->callTableAction('sendTestSms', $smsOnly);

        Mail::assertNothingSent();
        $this->assertSame(0, NotifyLog::where('notify_rule_id', $smsOnly->id)->count());
    }
}
