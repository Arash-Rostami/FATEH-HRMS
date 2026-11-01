<?php

namespace Tests\Feature\Models;

use App\Models\Task;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

enum TicketTestStatus: string
{
    case Closed = 'closed';
    case Open = 'open';
    case InProgress = 'in-progress';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}

class TicketModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    public function test_get_custom_admin_area_label_html_renders_label_and_icon_svg(): void
    {
        $html = Ticket::getCustomAdminAreaLabelHtml('support', 'windows_office');

        $this->assertStringContainsString('ویندوز و نرم‌افزار', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function test_is_closing_status_returns_true_for_closed_string(): void
    {
        $this->assertTrue(Ticket::isClosingStatus('closed'));
    }

    public function test_is_closing_status_returns_true_for_backed_enum_with_closed_value(): void
    {
        $this->assertTrue(Ticket::isClosingStatus(TicketTestStatus::Closed));
    }

    public function test_is_closing_status_returns_false_for_non_closed_string(): void
    {
        $this->assertFalse(Ticket::isClosingStatus('open'));
        $this->assertFalse(Ticket::isClosingStatus('in-progress'));
    }

    public function test_status_set_mutator_normalizes_to_valid_status_or_defaults_to_open(): void
    {
        $ticket = new Ticket();
        $ticket->status = 'OPEN';
        $this->assertSame('open', $ticket->getAttributes()['status']);

        $ticket->status = 'IN-PROGRESS';
        $this->assertSame('in-progress', $ticket->getAttributes()['status']);

        $ticket->status = 'invalid';
        $this->assertSame('open', $ticket->getAttributes()['status']);
    }

    public function test_status_set_mutator_extracts_value_from_backed_enum(): void
    {
        $ticket = new Ticket();
        $ticket->status = TicketTestStatus::InProgress;

        $this->assertSame('in-progress', $ticket->getAttributes()['status']);
    }

    public function test_priority_set_mutator_normalizes_to_valid_priority_or_defaults_to_low(): void
    {
        $ticket = new Ticket();
        $ticket->priority = 'HIGH';
        $this->assertSame('high', $ticket->getAttributes()['priority']);

        $ticket->priority = 'invalid';
        $this->assertSame('low', $ticket->getAttributes()['priority']);
    }

    public function test_satisfaction_score_set_mutator_returns_null_for_out_of_range(): void
    {
        $ticket = new Ticket();
        $ticket->satisfaction_score = 6;
        $this->assertNull($ticket->getAttributes()['satisfaction_score']);

        $ticket->satisfaction_score = -1;
        $this->assertNull($ticket->getAttributes()['satisfaction_score']);
    }

    public function test_satisfaction_score_set_mutator_returns_float_for_valid_range(): void
    {
        $ticket = new Ticket();
        $ticket->satisfaction_score = 4.5;
        $this->assertSame(4.5, (float) $ticket->getAttributes()['satisfaction_score']);
    }

    public function test_target_department_id_accessor_reads_from_extra_json(): void
    {
        $ticket = new Ticket();
        $ticket->extra = ['target_department' => 'PS'];

        $this->assertSame('PS', $ticket->targetDepartmentId);
    }

    public function test_target_department_id_accessor_returns_null_when_not_set(): void
    {
        $ticket = new Ticket();
        $ticket->extra = [];

        $this->assertNull($ticket->targetDepartmentId);
    }

    public function test_department_id_accessor_reads_from_extra_json(): void
    {
        $ticket = new Ticket();
        $ticket->extra = ['department' => 'HR'];

        $this->assertSame('HR', $ticket->departmentId);
    }

    public function test_creating_ticket_sets_target_department_key_in_extra(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'extra' => [],
        ]);

        $this->assertArrayHasKey('target_department', $ticket->fresh()->extra);
    }

    public function test_creating_ticket_defaults_department_from_requester_profile(): void
    {
        $dept = Department::firstOrCreate(['code' => 'ZZ-TD1'], ['name' => 'ZZ-TD1', 'description' => 'ZZ-TD1']);
        $requester = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $requester->id, 'department_id' => $dept->code]);

        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'extra' => [],
        ]);

        $this->assertSame($dept->code, $ticket->fresh()->extra['department'] ?? null);
    }

    public function test_creating_ticket_leaves_department_unset_when_requester_has_no_profile(): void
    {
        $requester = User::factory()->create(['status' => 'active']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'extra' => [],
        ]);

        $this->assertArrayNotHasKey('department', $ticket->fresh()->extra);
    }

    public function test_saving_ticket_clears_assigned_to_reverts_in_progress_to_open(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
            'effectiveness' => null,
        ]);

        $ticket->assigned_to = null;
        $ticket->save();

        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_saving_ticket_assigns_to_changes_open_to_in_progress(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        Department::firstOrCreate(['code' => 'TMA'], ['name' => 'TMA', 'description' => 'TMA']);
        Profile::factory()->create(['user_id' => $assignee->id, 'department_id' => 'TMA', 'position' => 'employee']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => null,
            'status' => 'open',
            'extra' => ['target_department' => 'TMA'],
        ]);

        $ticket->assigned_to = $assignee->id;
        $ticket->save();

        $this->assertSame('in-progress', $ticket->fresh()->status);
    }

    public function test_saving_ticket_closing_requires_effectiveness(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ERR-T001');

        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
            'effectiveness' => null,
        ]);

        $ticket->status = 'closed';
        $ticket->save();
    }

    public function test_saving_ticket_closing_composes_action_result_from_replies(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'name' => 'Alice']);
        $assignee = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
        ]);

        $ticket->replies()->create(['user_id' => $assignee->id, 'body' => 'Fixed the issue']);

        $ticket->effectiveness = 'effective';
        $ticket->status = 'closed';
        $ticket->save();

        $this->assertStringContainsString('Fixed the issue', $ticket->fresh()->action_result);
    }

    public function test_compose_action_result_from_replies_joins_all_replies(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active', 'name' => 'Bob']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
        ]);

        $ticket->replies()->create(['user_id' => $assignee->id, 'body' => 'First']);
        $ticket->replies()->create(['user_id' => $assignee->id, 'body' => 'Second']);

        $result = $ticket->composeActionResultFromReplies();

        $this->assertStringContainsString('First', $result);
        $this->assertStringContainsString('Second', $result);
    }

    public function test_current_action_recipient_returns_assignee_for_in_progress(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
        ]);

        $recipient = $ticket->currentActionRecipient();

        $this->assertNotNull($recipient);
        $this->assertSame($assignee->id, $recipient->id);
    }

    public function test_current_action_recipient_returns_requester_for_closed(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
        ]);
        $ticket->replies()->create(['user_id' => $assignee->id, 'body' => 'Done']);
        $ticket->effectiveness = 'effective';
        $ticket->status = 'closed';
        $ticket->save();

        $recipient = $ticket->fresh()->currentActionRecipient();

        $this->assertNotNull($recipient);
        $this->assertSame($requester->id, $recipient->id);
    }

    public function test_request_type_set_mutator_extracts_value_from_backed_enum(): void
    {
        $ticket = new Ticket();
        $ticket->request_type = TicketTestStatus::Open;

        $this->assertSame('open', $ticket->getAttributes()['request_type']);
    }

    public function test_request_type_set_mutator_returns_empty_string_for_non_scalar(): void
    {
        $ticket = new Ticket();
        $ticket->request_type = ['array'];

        $this->assertSame('', $ticket->getAttributes()['request_type']);
    }

    public function test_deleting_removes_attachment_and_requester_and_assignee_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ticket/attachment.pdf', 'content');
        Storage::disk('public')->put('ticket/requester/a.pdf', 'content');
        Storage::disk('public')->put('ticket/assignee/b.pdf', 'content');
        $requester = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'attachment' => 'ticket/attachment.pdf',
            'requester_files' => [['file' => 'ticket/requester/a.pdf']],
            'assignee_files' => [['file' => 'ticket/assignee/b.pdf']],
        ]);

        $ticket->delete();

        Storage::disk('public')->assertMissing('ticket/attachment.pdf');
        Storage::disk('public')->assertMissing('ticket/requester/a.pdf');
        Storage::disk('public')->assertMissing('ticket/assignee/b.pdf');
    }

    public function test_deleting_with_no_files_does_not_error(): void
    {
        Storage::fake('public');
        $requester = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'attachment' => null,
            'requester_files' => null,
            'assignee_files' => null,
        ]);

        $ticket->delete();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_get_custom_material_icon_for_area_translates_custom_heroicon_to_matching_material_symbol(): void
    {
        $dept = 'ZZ' . random_int(100, 999);
        Department::create([
            'code' => $dept,
            'name' => $dept,
            'description' => $dept,
            'ticket_options' => [
                ['request_type' => 'custom', 'area_key' => 'custom_area', 'area_label' => 'Custom', 'icon' => 'heroicon-o-computer-desktop'],
            ],
        ]);

        $this->assertSame('desk', Ticket::getCustomMaterialIconForArea('custom_area', $dept));
        $this->assertSame('heroicon-o-computer-desktop', Ticket::getCustomHeroiconForArea('custom_area', $dept));
    }

    public function test_get_custom_material_icon_for_area_falls_back_to_default_lookup_when_custom_icon_is_not_a_known_heroicon(): void
    {
        $dept = 'ZZ' . random_int(100, 999);
        Department::create([
            'code' => $dept,
            'name' => $dept,
            'description' => $dept,
            'ticket_options' => [
                ['request_type' => 'custom', 'area_key' => 'windows_office', 'area_label' => 'Custom', 'icon' => 'heroicon-o-flag'],
            ],
        ]);

        $this->assertSame(Ticket::getMaterialIconForArea('windows_office'), Ticket::getCustomMaterialIconForArea('windows_office', $dept));
        $this->assertNotSame('heroicon-o-flag', Ticket::getCustomMaterialIconForArea('windows_office', $dept));
    }

    public function test_get_custom_material_icon_for_area_falls_back_to_default_lookup_when_no_custom_options_exist(): void
    {
        $this->assertSame(
            Ticket::getMaterialIconForArea('windows_office'),
            Ticket::getCustomMaterialIconForArea('windows_office', null)
        );
    }

    private function labelDept(array $options, ?string $code = null): string
    {
        $code ??= 'ZL' . random_int(1000, 9999);
        Department::create(['code' => $code, 'name' => $code, 'description' => $code, 'ticket_options' => $options]);

        return $code;
    }

    private function labelTicket(?string $type, ?string $area, ?string $dept): Ticket
    {
        $ticket = new Ticket();
        $ticket->request_type = $type;
        $ticket->request_area = $area;
        $ticket->extra = $dept ? ['target_department' => $dept] : [];

        return $ticket;
    }

    public function test_area_label_prefers_target_department_exact_type_then_any_type_of_that_department(): void
    {
        $dept = $this->labelDept([
            ['request_type' => 'support', 'area_key' => 'zz_shared', 'area_label' => 'Support-Shared'],
            ['request_type' => 'access', 'area_key' => 'zz_shared', 'area_label' => 'Access-Shared'],
            ['request_type' => 'development', 'area_key' => 'zz_only_dev', 'area_label' => 'Dev-Only'],
        ]);

        $this->assertSame('Support-Shared', $this->labelTicket('support', 'zz_shared', $dept)->areaLabel);
        $this->assertSame('Access-Shared', $this->labelTicket('access', 'zz_shared', $dept)->areaLabel);
        $this->assertSame('Dev-Only', $this->labelTicket('support', 'zz_only_dev', $dept)->areaLabel, 'type mismatch falls back to any type of the department');
    }

    public function test_area_label_falls_back_to_system_then_other_department_then_raw_key_and_never_not_found(): void
    {
        $other = $this->labelDept([['request_type' => 'support', 'area_key' => 'zz_foreign', 'area_label' => 'Foreign-Label']]);
        $dept = $this->labelDept([['request_type' => 'support', 'area_key' => 'zz_mine', 'area_label' => 'Mine']]);

        $this->assertSame(Ticket::$requestAreaOptions['support']['vpn'], $this->labelTicket('support', 'vpn', $dept)->areaLabel);
        $this->assertSame(Ticket::$requestAreaOptions['access']['email'], $this->labelTicket('support', 'email', $dept)->areaLabel, 'system label of another type');
        $this->assertSame('Foreign-Label', $this->labelTicket('support', 'zz_foreign', $dept)->areaLabel);
        $this->assertSame('zz_orphan_key', $this->labelTicket('support', 'zz_orphan_key', $dept)->areaLabel);
        $this->assertNotSame('یافت نشد', $this->labelTicket('nope', 'zz_orphan_key', $dept)->areaLabel);
        $this->assertSame('', $this->labelTicket('support', null, $dept)->areaLabel);
        $this->assertSame('', $this->labelTicket('support', '  ', $dept)->areaLabel);
    }

    public function test_area_label_matches_persian_arabic_variants_zwnj_whitespace_and_case(): void
    {
        $dept = $this->labelDept([['request_type' => 'support', 'area_key' => 'کیف_zz', 'area_label' => 'Variant-Label']]);

        $this->assertSame('Variant-Label', $this->labelTicket('support', 'كيف_zz', $dept)->areaLabel);
        $this->assertSame('Variant-Label', $this->labelTicket('support', "کیف\u{200C}_zz ", $dept)->areaLabel);
        $this->assertSame(Ticket::$requestAreaOptions['support']['vpn'], $this->labelTicket(' SUPPORT ', ' VPN ', $dept)->areaLabel);
    }

    public function test_type_label_order_system_then_target_custom_then_any_department_custom_then_raw(): void
    {
        $other = $this->labelDept([['request_type' => 'zz_other_type', 'area_key' => 'a', 'area_label' => 'A']]);
        $dept = $this->labelDept([['request_type' => 'zz_my_type', 'area_key' => 'a', 'area_label' => 'A']]);

        $this->assertSame(Ticket::$requestTypeOptions['support'], $this->labelTicket('support', null, $dept)->typeLabel);
        $this->assertSame('zz_my_type', $this->labelTicket('zz_my_type', null, $dept)->typeLabel);
        $this->assertSame('zz_other_type', $this->labelTicket('zz_other_type', null, $dept)->typeLabel);
        $this->assertSame('zz_nowhere', $this->labelTicket('zz_nowhere', null, $dept)->typeLabel);
        $this->assertSame('', $this->labelTicket(null, null, $dept)->typeLabel);
    }

    public function test_legacy_ticket_without_stored_target_resolves_through_the_default_departments_options(): void
    {
        $default = Ticket::defaultTargetDepartment();
        if (!$default) {
            $this->markTestSkipped('no default target department (PS/BS head) on this DB');
        }

        $department = Department::find($default);
        $department->update(['ticket_options' => [['request_type' => 'support', 'area_key' => 'zz_legacy', 'area_label' => 'Legacy-Default-Label']]]);

        $legacy = $this->labelTicket('support', 'zz_legacy', null);

        $this->assertSame($default, $legacy->effectiveTargetDepartment());
        $this->assertSame('Legacy-Default-Label', $legacy->areaLabel);
        $this->assertSame('', $this->labelTicket('support', null, null)->areaLabel);
    }

    public function test_department_ticket_option_edits_are_reflected_immediately_without_stale_memo(): void
    {
        $dept = $this->labelDept([['request_type' => 'support', 'area_key' => 'zz_live', 'area_label' => 'Before']]);
        $ticket = $this->labelTicket('support', 'zz_live', $dept);

        $this->assertSame('Before', $ticket->areaLabel);

        Department::find($dept)->update(['ticket_options' => [['request_type' => 'support', 'area_key' => 'zz_live', 'area_label' => 'After']]]);

        $this->assertSame('After', $this->labelTicket('support', 'zz_live', $dept)->areaLabel);
        $this->assertSame('After', Ticket::getCustomRequestAreaLabel('support', 'zz_live', $dept));
    }

    public function test_custom_icon_rule_target_then_system_then_other_department_only_for_keys_custom_everywhere(): void
    {
        $other = $this->labelDept([
            ['request_type' => 'support', 'area_key' => 'zz_icon_foreign', 'area_label' => 'F', 'icon' => 'heroicon-o-computer-desktop'],
            ['request_type' => 'support', 'area_key' => 'vpn', 'area_label' => 'V', 'icon' => 'heroicon-o-computer-desktop'],
        ]);
        $mine = $this->labelDept([['request_type' => 'support', 'area_key' => 'zz_icon_mine', 'area_label' => 'M', 'icon' => 'heroicon-o-printer']]);

        $this->assertSame('heroicon-o-printer', Ticket::getCustomHeroiconForArea('zz_icon_mine', $mine));
        $this->assertSame('heroicon-o-computer-desktop', Ticket::getCustomHeroiconForArea('zz_icon_foreign', $mine), 'key custom everywhere borrows the other department icon');
        $this->assertSame(Ticket::getHeroiconForArea('vpn'), Ticket::getCustomHeroiconForArea('vpn', $mine), 'system key never borrows another department icon');
        $this->assertSame('heroicon-o-computer-desktop', Ticket::getCustomHeroiconForArea('vpn', $other), 'own department icon wins');
    }

    public function test_urgency_state_marks_overdue_ticket_as_high_score(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'completion_deadline' => now()->subDays(3),
        ]);

        $state = $ticket->urgency_state;

        $this->assertSame('overdue', $state['kind']);
        $this->assertSame(1.0, $state['score']);
    }

    public function test_urgency_state_returns_zero_for_closed_ticket_regardless_of_deadline(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'closed',
            'effectiveness' => 'effective',
            'completion_deadline' => now()->subDays(10),
        ]);

        $state = $ticket->urgency_state;

        $this->assertNull($state['kind']);
        $this->assertSame(0.0, $state['score']);
    }

    public function test_urgency_state_with_null_deadline_and_priority_does_not_throw(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'completion_deadline' => null,
            'priority' => null,
        ]);

        $state = $ticket->urgency_state;

        $this->assertIsArray($state);
        $this->assertSame(0.0, $state['score']);
    }

    public function test_urgency_state_idle_only_ticket_has_nonzero_score(): void
    {
        $requester = User::factory()->create(['status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'priority' => 'low',
            'completion_deadline' => null,
        ]);

        DB::table('tickets')->where('id', $ticket->id)->update(['updated_at' => now()->subDays(8)]);

        $state = $ticket->fresh()->urgency_state;

        $this->assertSame('idle', $state['kind']);
        $this->assertEqualsWithDelta(0.45, $state['score'], 0.0001);
    }

    private function mirrorFixture(array $ticket = []): array
    {
        $code = 'M' . strtoupper(\Illuminate\Support\Str::random(3));
        Department::create(['code' => $code, 'name' => $code, 'description' => $code]);
        $staff = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $other = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $code, 'position' => 'employee']);
        Profile::factory()->create(['user_id' => $other->id, 'department_id' => $code, 'position' => 'employee']);
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assigned_to' => null,
            'status' => 'open',
            'extra' => ['target_department' => $code],
            ...$ticket,
        ]);

        return [Ticket::find($ticket->id), $staff, $other, $requester];
    }

    public function test_assigning_creates_a_todo_mirror_task_with_the_ticket_deadline(): void
    {
        $deadline = now()->addDays(9)->startOfDay();
        [$ticket, $staff, , $requester] = $this->mirrorFixture(['completion_deadline' => $deadline]);

        $ticket->update(['assigned_to' => $staff->id]);

        $task = Task::where('ticket_id', $ticket->id)->first();
        $this->assertSame('todo', $task->status);
        $this->assertTrue($task->deadline->isSameDay($deadline));
        $this->assertSame($staff->id, $task->assigned_to);
        $this->assertSame($requester->id, $task->user_id);
    }

    public function test_unassigning_removes_the_mirror_task_and_reassigning_restores_the_same_one(): void
    {
        [$ticket, $staff, $other] = $this->mirrorFixture();
        $ticket->update(['assigned_to' => $staff->id]);
        $taskId = Task::where('ticket_id', $ticket->id)->value('id');

        Ticket::find($ticket->id)->update(['assigned_to' => null]);
        $this->assertNull(Task::where('ticket_id', $ticket->id)->first());
        $this->assertNotNull(Task::withTrashed()->find($taskId)->deleted_at);

        Ticket::find($ticket->id)->update(['assigned_to' => $other->id]);
        $restored = Task::where('ticket_id', $ticket->id)->first();
        $this->assertSame($taskId, $restored->id);
        $this->assertSame($other->id, $restored->assigned_to);
        $this->assertSame('todo', $restored->status);
    }

    public function test_reassigning_resets_a_started_mirror_to_todo_and_never_touches_a_done_one(): void
    {
        [$ticket, $staff, $other] = $this->mirrorFixture();
        $ticket->update(['assigned_to' => $staff->id]);
        $task = Task::where('ticket_id', $ticket->id)->first();
        Task::syncingFromTicket(fn() => $task->update(['status' => 'in-progress']));

        Ticket::find($ticket->id)->update(['priority' => 'high']);
        $this->assertSame('in-progress', $task->fresh()->status);

        Ticket::find($ticket->id)->update(['assigned_to' => $other->id]);
        $this->assertSame('todo', $task->fresh()->status);

        Task::syncingFromTicket(fn() => $task->fresh()->update(['status' => 'done']));
        DB::table('tasks')->where('id', $task->id)->update(['assigned_to' => $other->id]);
        Ticket::find($ticket->id)->update(['priority' => 'low']);
        $this->assertSame('done', $task->fresh()->status);
    }

    public function test_restoring_a_mirror_task_outside_a_ticket_sync_is_refused(): void
    {
        [$ticket, $staff] = $this->mirrorFixture();
        $ticket->update(['assigned_to' => $staff->id]);
        $task = Task::where('ticket_id', $ticket->id)->first();
        Task::syncingFromTicket(fn() => $task->delete());

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Task::withTrashed()->find($task->id)->restore();
    }

    public function test_closed_ticket_is_final_even_when_an_admin_tries_to_reopen_it(): void
    {
        [$ticket, $staff] = $this->mirrorFixture();
        $ticket->update(['assigned_to' => $staff->id]);
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->actingAs($admin);
        Ticket::find($ticket->id)->update(['effectiveness' => 'effective', 'status' => 'closed']);

        foreach (['open', 'in-progress'] as $status) {
            try {
                Ticket::find($ticket->id)->update(['status' => $status]);
                $this->fail('closed ticket must not reopen');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('status', $e->errors());
            }
        }

        $this->assertSame('closed', Ticket::find($ticket->id)->status);
    }

    public function test_completion_date_is_stamped_on_both_close_paths_and_closing_finishes_the_mirror_task(): void
    {
        [$closed, $staff] = $this->mirrorFixture();
        $closed->update(['assigned_to' => $staff->id]);
        Ticket::find($closed->id)->update(['effectiveness' => 'effective', 'status' => 'closed']);

        [$withdrawn, $staff2] = $this->mirrorFixture();
        $withdrawn->update(['assigned_to' => $staff2->id]);
        Ticket::find($withdrawn->id)->update(['status' => 'closed', 'action_result' => Ticket::WITHDRAWN_RESULT]);

        $this->assertNotNull(Ticket::find($closed->id)->completion_date);
        $this->assertNotNull(Ticket::find($withdrawn->id)->completion_date);
        $this->assertSame('done', Task::where('ticket_id', $closed->id)->value('status'));
        $this->assertSame('done', Task::where('ticket_id', $withdrawn->id)->value('status'));
    }

    public function test_classification_helpers_fall_back_to_defaults_and_never_drop_unknown_keys()
    {
        $types = Ticket::allRequestTypeOptions();
        $areas = Ticket::allRequestAreaOptions();

        $this->assertSame('پشتیبانی', $types['support']);
        $this->assertArrayHasKey('vpn', $areas);
        $this->assertArrayNotHasKey('', $areas);
        $this->assertSame('پشتیبانی', Ticket::getCustomRequestTypeLabel('support', null));
        $this->assertSame('custom-type', Ticket::getCustomRequestTypeLabel('custom-type', null));
        $this->assertSame(Ticket::$requestAreaOptions['support']['vpn'], Ticket::getCustomRequestAreaLabel('support', 'vpn', null));
    }
}
