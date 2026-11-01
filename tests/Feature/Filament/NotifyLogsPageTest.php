<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Pages\ListNotifyLogs;
use App\Filament\Resources\NotifyRuleResource\Pages\ListNotifyRules;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyLogTablePresenter;
use App\Models\Department;
use App\Models\Link;
use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotifyLogsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        Cache::forget('notify:trackables');
        Cache::forget('notify:rules');
        Once::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget('notify:rules');
        Once::flush();
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

    private function adminWith(string $module, array $actions): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => false, 'abilities' => [['module' => $module, 'actions' => $actions]]]);

        return $user;
    }

    private function makeRule(string $name = 'logs page rule'): NotifyRule
    {
        return NotifyRule::create([
            'name' => $name,
            'model' => Link::class,
            'events' => ['created'],
            'channels' => ['mail'],
            'subject' => 'Hello {model}',
            'lines' => ['A line'],
            'is_active' => true,
        ]);
    }

    private function log(array $attributes = []): NotifyLog
    {
        $log = (new NotifyLog)->forceFill(array_merge([
            'source' => NotifyLog::SOURCE_RULE,
            'count' => 1,
            'event' => 'created',
            'record_id' => '1',
            'recipient' => 'person@example.com',
            'channel' => 'mail',
            'status' => NotifyLog::STATUS_SENT,
            'created_at' => now(),
        ], $attributes));
        $log->save();

        return $log;
    }

    private function summaryOf(NotifyRule $rule, string $runId, array $overrides = []): NotifyLog
    {
        return $this->log(['notify_rule_id' => $rule->id, 'run_id' => $runId, 'status' => NotifyLog::STATUS_SUMMARY, 'channel' => 'all', 'recipient' => 'run summary', 'count' => 10, ...$overrides]);
    }

    private function scoped(NotifyRule $rule, ?User $admin = null)
    {
        return Livewire::actingAs($admin ?? $this->developer())
            ->test(ListNotifyLogs::class)
            ->filterTable('notify_rule_id', $rule->id);
    }

    private function allStatuses(NotifyRule $rule, ?User $admin = null)
    {
        return $this->scoped($rule, $admin)->filterTable('status', null);
    }

    public function test_page_renders_for_a_permitted_admin_and_is_forbidden_for_others(): void
    {
        Livewire::actingAs($this->developer())->test(ListNotifyLogs::class)->assertSuccessful();

        Livewire::actingAs($this->adminWith('notify_rule', ['view']))->test(ListNotifyLogs::class)->assertSuccessful();

        Livewire::actingAs($this->adminWith('department', ['view']))->test(ListNotifyLogs::class)->assertForbidden();
    }

    public function test_tenant_switch_hides_the_page_behind_a_404(): void
    {
        $admin = $this->developer();
        $url = NotifyRuleResource::getUrl('logs');

        $this->actingAs($admin)->get($url)->assertSuccessful()->assertSee(__('resources/notify_rule/strings.log.heading'));

        config(['app.off' => ['notify']]);

        $this->assertTrue(tenantOffName('filament.admin.resources.notify-rules.logs'));
        $this->actingAs($admin)->get($url)->assertNotFound();
    }

    public function test_default_view_lists_only_summary_rows_newest_first(): void
    {
        $rule = $this->makeRule();
        $old = $this->summaryOf($rule, (string) Str::ulid(), ['created_at' => now()->subDays(3)]);
        $new = $this->summaryOf($rule, (string) Str::ulid());
        $sent = $this->log(['notify_rule_id' => $rule->id, 'run_id' => $new->run_id]);
        $failed = $this->log(['notify_rule_id' => $rule->id, 'run_id' => $new->run_id, 'status' => NotifyLog::STATUS_FAILED, 'error' => 'boom']);
        $skipped = $this->log(['notify_rule_id' => $rule->id, 'run_id' => $new->run_id, 'status' => NotifyLog::STATUS_SKIPPED]);

        $this->scoped($rule)
            ->assertSet('tableFilters.status.value', NotifyLog::STATUS_SUMMARY)
            ->assertCanSeeTableRecords([$new, $old], inOrder: true)
            ->assertCanNotSeeTableRecords([$sent, $failed, $skipped]);
    }

    public function test_status_and_problem_filters_split_the_rows(): void
    {
        $rule = $this->makeRule();
        $runId = (string) Str::ulid();
        $summary = $this->summaryOf($rule, $runId);
        $sent = $this->log(['notify_rule_id' => $rule->id, 'run_id' => $runId]);
        $failed = $this->log(['notify_rule_id' => $rule->id, 'run_id' => $runId, 'status' => NotifyLog::STATUS_FAILED, 'error' => 'boom']);
        $skipped = $this->log(['notify_rule_id' => $rule->id, 'run_id' => $runId, 'status' => NotifyLog::STATUS_SKIPPED, 'error' => 'no address']);

        $this->scoped($rule)
            ->filterTable('status', NotifyLog::STATUS_SENT)
            ->assertCanSeeTableRecords([$sent])
            ->assertCanNotSeeTableRecords([$summary, $failed, $skipped])
            ->filterTable('status', NotifyLog::STATUS_SKIPPED)
            ->assertCanSeeTableRecords([$skipped])
            ->assertCanNotSeeTableRecords([$summary, $sent, $failed]);

        $this->allStatuses($rule)
            ->assertCanSeeTableRecords([$summary, $sent, $failed, $skipped])
            ->filterTable('problems', true)
            ->assertCanSeeTableRecords([$failed, $skipped])
            ->assertCanNotSeeTableRecords([$summary, $sent])
            ->filterTable('problems', false)
            ->assertCanSeeTableRecords([$summary, $sent])
            ->assertCanNotSeeTableRecords([$failed, $skipped]);
    }

    public function test_source_channel_and_event_filters_narrow_the_rows(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule();
        $recipient = User::factory()->create(['status' => 'active']);
        $mailCreated = $this->log(['notify_rule_id' => $rule->id, 'user_id' => $recipient->id, 'channel' => 'mail', 'event' => 'created']);
        $smsDeleted = $this->log(['notify_rule_id' => $rule->id, 'user_id' => $recipient->id, 'channel' => 'sms', 'event' => 'deleted', 'recipient' => '*******4567']);
        $audit = $this->log(['source' => NotifyLog::SOURCE_STATUS, 'sender_id' => $admin->id, 'user_id' => $recipient->id, 'channel' => 'sms', 'recipient' => '*******4567']);

        $this->allStatuses($rule, $admin)
            ->filterTable('channel', 'sms')
            ->assertCanSeeTableRecords([$smsDeleted])
            ->assertCanNotSeeTableRecords([$mailCreated])
            ->filterTable('channel', 'mail')
            ->assertCanSeeTableRecords([$mailCreated])
            ->assertCanNotSeeTableRecords([$smsDeleted])
            ->filterTable('channel', null)
            ->filterTable('event', 'deleted')
            ->assertCanSeeTableRecords([$smsDeleted])
            ->assertCanNotSeeTableRecords([$mailCreated]);

        Livewire::actingAs($admin)->test(ListNotifyLogs::class)
            ->filterTable('status', null)
            ->filterTable('user_id', $recipient->id)
            ->filterTable('source', NotifyLog::SOURCE_STATUS)
            ->assertCanSeeTableRecords([$audit])
            ->assertCanNotSeeTableRecords([$mailCreated, $smsDeleted])
            ->filterTable('source', NotifyLog::SOURCE_RULE)
            ->assertCanSeeTableRecords([$mailCreated, $smsDeleted])
            ->assertCanNotSeeTableRecords([$audit]);
    }

    public function test_rule_and_user_filters_narrow_the_rows(): void
    {
        $admin = $this->developer();
        $ruleA = $this->makeRule('rule A');
        $ruleB = $this->makeRule('rule B');
        $alice = User::factory()->create(['status' => 'active']);
        $bob = User::factory()->create(['status' => 'active']);
        $aliceRow = $this->log(['notify_rule_id' => $ruleA->id, 'user_id' => $alice->id]);
        $bobRow = $this->log(['notify_rule_id' => $ruleA->id, 'user_id' => $bob->id]);
        $otherRuleRow = $this->log(['notify_rule_id' => $ruleB->id, 'user_id' => $alice->id]);

        $this->allStatuses($ruleA, $admin)
            ->assertCanSeeTableRecords([$aliceRow, $bobRow])
            ->assertCanNotSeeTableRecords([$otherRuleRow])
            ->filterTable('user_id', $alice->id)
            ->assertCanSeeTableRecords([$aliceRow])
            ->assertCanNotSeeTableRecords([$bobRow, $otherRuleRow]);
    }

    public function test_department_filter_matches_the_recipients_department(): void
    {
        $rule = $this->makeRule();
        $deptA = Department::factory()->create(['code' => strtoupper(Str::random(8))]);
        $deptB = Department::factory()->create(['code' => strtoupper(Str::random(8))]);
        $inA = User::factory()->create(['status' => 'active']);
        $inB = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $inA->id, 'department_id' => $deptA->code]);
        Profile::factory()->create(['user_id' => $inB->id, 'department_id' => $deptB->code]);
        $rowA = $this->log(['notify_rule_id' => $rule->id, 'user_id' => $inA->id]);
        $rowB = $this->log(['notify_rule_id' => $rule->id, 'user_id' => $inB->id]);
        $contactRow = $this->log(['notify_rule_id' => $rule->id, 'user_id' => null, 'recipient' => 'external@example.com']);

        $this->allStatuses($rule)
            ->filterTable('department', $deptA->code)
            ->assertCanSeeTableRecords([$rowA])
            ->assertCanNotSeeTableRecords([$rowB, $contactRow]);
    }

    public function test_date_range_filter_includes_the_whole_last_day(): void
    {
        $rule = $this->makeRule();
        $old = $this->log(['notify_rule_id' => $rule->id, 'created_at' => now()->subDays(40)]);
        $lateOnLastDay = $this->log(['notify_rule_id' => $rule->id, 'created_at' => now()->subDays(35)->setTime(23, 30)]);
        $recent = $this->log(['notify_rule_id' => $rule->id, 'created_at' => now()]);

        $this->allStatuses($rule)
            ->filterTable('created_at', ['from' => now()->subDays(45)->toDateString(), 'until' => now()->subDays(35)->toDateString()])
            ->assertCanSeeTableRecords([$old, $lateOnLastDay])
            ->assertCanNotSeeTableRecords([$recent])
            ->filterTable('created_at', ['from' => now()->subDays(1)->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old, $lateOnLastDay]);
    }

    public function test_recipient_search_finds_the_row(): void
    {
        $rule = $this->makeRule();
        $needle = $this->log(['notify_rule_id' => $rule->id, 'recipient' => 'needle-person@example.com']);
        $hay = $this->log(['notify_rule_id' => $rule->id, 'recipient' => 'someone-else@example.com']);

        $this->allStatuses($rule)
            ->searchTable('needle-person')
            ->assertCanSeeTableRecords([$needle])
            ->assertCanNotSeeTableRecords([$hay]);
    }

    public function test_every_grouping_renders_and_the_run_group_shows_the_run_code(): void
    {
        $rule = $this->makeRule();
        $runId = (string) Str::ulid();
        $this->summaryOf($rule, $runId);
        $this->log(['notify_rule_id' => $rule->id, 'run_id' => $runId]);

        $component = $this->allStatuses($rule);

        foreach (['notify_rule_id', 'run_id', 'channel', 'source', 'status'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }

        $component->set('tableGrouping', 'run_id')->assertSee(NotifyLogTablePresenter::runCode($runId));
    }

    public function test_summary_view_shows_run_totals_and_only_problem_rows(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule();
        $ali = User::factory()->create(['name' => 'Ali Problem', 'status' => 'active']);
        $sara = User::factory()->create(['name' => 'Sara Skipped', 'status' => 'active']);
        $runId = (string) Str::ulid();
        $otherRunId = (string) Str::ulid();
        $reason = 'sms gateway is not configured';

        $summary = $this->summaryOf($rule, $runId, ['error' => $reason]);
        $base = ['notify_rule_id' => $rule->id, 'run_id' => $runId];
        $this->log([...$base, 'user_id' => $ali->id, 'recipient' => 'ali@example.com']);
        $this->log([...$base, 'user_id' => $sara->id, 'recipient' => 'sara@example.com']);
        $this->log([...$base, 'count' => 5, 'recipient' => '5 recipients']);
        $this->log([...$base, 'status' => NotifyLog::STATUS_FAILED, 'user_id' => $ali->id, 'error' => 'boom']);
        $this->log([...$base, 'status' => NotifyLog::STATUS_FAILED, 'count' => 2, 'recipient' => '2 recipients', 'error' => 'timeout']);
        $this->log([...$base, 'status' => NotifyLog::STATUS_SKIPPED, 'user_id' => $sara->id, 'error' => 'no address']);
        $this->log(['notify_rule_id' => $rule->id, 'run_id' => $otherRunId, 'count' => 4]);
        $this->log(['notify_rule_id' => $rule->id, 'run_id' => $otherRunId, 'status' => NotifyLog::STATUS_FAILED, 'count' => 9, 'error' => 'other run']);

        Livewire::actingAs($admin)->test(ListNotifyLogs::class)
            ->mountTableAction('view', $summary)
            ->assertSchemaComponentStateSet('run_sent', 7)
            ->assertSchemaComponentStateSet('run_failed', 3)
            ->assertSchemaComponentStateSet('run_skipped', 1)
            ->assertSchemaComponentStateSet('targeted', 10)
            ->assertSchemaComponentStateSet('run_error', $reason)
            ->assertSchemaComponentStateSet('problems', [
                'Ali Problem ('.__('resources/notify_rule/strings.badges.failed').'): boom',
                '2 recipients ('.__('resources/notify_rule/strings.badges.failed').'): timeout',
                'Sara Skipped ('.__('resources/notify_rule/strings.badges.skipped').'): no address',
            ])
            ->assertSchemaComponentVisible('problems')
            ->assertSchemaComponentHidden('recipient')
            ->assertSchemaComponentHidden('channel')
            ->assertSchemaComponentHidden('count')
            ->assertSchemaComponentHidden('error')
            ->assertSchemaComponentHidden('sender_id');
    }

    public function test_summary_view_lists_at_most_fifty_problem_rows_but_totals_everything(): void
    {
        $rule = $this->makeRule();
        $runId = (string) Str::ulid();
        $summary = $this->summaryOf($rule, $runId, ['count' => 60]);
        NotifyLog::insert(array_map(fn (int $i) => [
            'notify_rule_id' => $rule->id,
            'run_id' => $runId,
            'source' => NotifyLog::SOURCE_RULE,
            'count' => 1,
            'event' => 'created',
            'record_id' => '1',
            'recipient' => "person{$i}@example.com",
            'channel' => 'mail',
            'status' => NotifyLog::STATUS_FAILED,
            'error' => 'boom',
            'created_at' => now(),
        ], range(1, 60)));

        $failed = __('resources/notify_rule/strings.badges.failed');

        Livewire::actingAs($this->developer())->test(ListNotifyLogs::class)
            ->mountTableAction('view', $summary)
            ->assertSchemaComponentStateSet('run_failed', 60)
            ->assertSchemaComponentStateSet('run_sent', 0)
            ->assertSchemaComponentStateSet('problems', array_map(fn (int $i) => "person{$i}@example.com ({$failed}): boom", range(1, 50)));
    }

    public function test_summary_without_a_run_id_shows_zero_totals_instead_of_failing(): void
    {
        $rule = $this->makeRule();
        $orphan = $this->log(['notify_rule_id' => $rule->id, 'status' => NotifyLog::STATUS_SUMMARY, 'channel' => 'all', 'run_id' => null, 'count' => 3]);

        Livewire::actingAs($this->developer())->test(ListNotifyLogs::class)
            ->mountTableAction('view', $orphan)
            ->assertSchemaComponentStateSet('run_sent', 0)
            ->assertSchemaComponentStateSet('run_failed', 0)
            ->assertSchemaComponentStateSet('targeted', 3);
    }

    public function test_single_rows_show_their_own_fields_and_hide_the_run_totals(): void
    {
        $admin = $this->developer();
        $rule = $this->makeRule();
        $recipient = User::factory()->create(['status' => 'active']);
        $ruleRow = $this->log(['notify_rule_id' => $rule->id, 'user_id' => $recipient->id, 'recipient' => 'row@example.com', 'record_id' => '77']);
        $audit = $this->log(['source' => NotifyLog::SOURCE_STATUS, 'sender_id' => $admin->id, 'user_id' => $recipient->id, 'channel' => 'sms', 'recipient' => '*******4567', 'status' => NotifyLog::STATUS_FAILED, 'error' => 'gateway down']);

        Livewire::actingAs($admin)->test(ListNotifyLogs::class)
            ->filterTable('status', null)
            ->mountTableAction('view', $ruleRow)
            ->assertSchemaComponentStateSet('recipient', 'row@example.com')
            ->assertSchemaComponentStateSet('record_id', '77')
            ->assertSchemaComponentVisible('rule.name')
            ->assertSchemaComponentVisible('event')
            ->assertSchemaComponentHidden('sender_id')
            ->assertSchemaComponentHidden('run_sent')
            ->assertSchemaComponentHidden('problems')
            ->assertSchemaComponentHidden('targeted')
            ->unmountTableAction()
            ->mountTableAction('view', $audit)
            ->assertSchemaComponentStateSet('sender_id', $admin->id)
            ->assertSchemaComponentStateSet('error', 'gateway down')
            ->assertSchemaComponentStateSet('recipient', '*******4567')
            ->assertSchemaComponentHidden('rule.name')
            ->assertSchemaComponentHidden('event')
            ->assertSchemaComponentHidden('run_failed');
    }

    public function test_the_page_is_read_only(): void
    {
        $component = Livewire::actingAs($this->developer())->test(ListNotifyLogs::class)->assertSuccessful();
        $table = $component->instance()->getTable();

        $this->assertSame(['view'], array_keys($table->getFlatRecordActions()));
        $this->assertSame([], $table->getFlatBulkActions());
        $this->assertSame([], $table->getHeaderActions());
        $this->assertSame(['switch_rules', 'switch_logs', 'moduleGuide'], array_map(fn ($action) => $action->getName(), array_values($component->instance()->getCachedHeaderActions())));
    }

    public function test_both_pages_carry_the_view_toggle_with_the_active_side_highlighted(): void
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(ListNotifyRules::class)
            ->assertActionHasLabel('switch_rules', 'قوانین اعلان')
            ->assertActionHasLabel('switch_logs', 'گزارش ارسال')
            ->assertActionHasUrl('switch_logs', NotifyRuleResource::getUrl('logs'))
            ->assertActionHasColor('switch_rules', 'primary')
            ->assertActionHasColor('switch_logs', 'gray')
            ->assertActionExists('create');

        Livewire::actingAs($admin)->test(ListNotifyLogs::class)
            ->assertActionHasLabel('switch_rules', 'قوانین اعلان')
            ->assertActionHasLabel('switch_logs', 'گزارش ارسال')
            ->assertActionHasUrl('switch_rules', NotifyRuleResource::getUrl('index'))
            ->assertActionHasColor('switch_logs', 'primary')
            ->assertActionHasColor('switch_rules', 'gray')
            ->assertActionDoesNotExist('create');
    }
}
