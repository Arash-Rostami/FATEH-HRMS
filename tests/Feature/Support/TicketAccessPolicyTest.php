<?php

namespace Tests\Feature\Support;

use App\Models\Department;
use App\Models\Profile;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketAccessPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
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

    private function deptUser(string $dept, string $position): User
    {
        Department::firstOrCreate(['code' => $dept], ['name' => $dept, 'description' => $dept]);
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $dept,
            'position' => $position,
        ]);

        return $user;
    }

    private function departmentHead(): User
    {
        $code = 'T' . Str::upper(Str::random(3));
        Department::create(['code' => $code, 'name' => $code, 'description' => $code]);
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code, 'position' => 'employee']);

        return $user;
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        return Ticket::factory()->create(array_merge([
            'status' => 'open',
            'assigned_to' => null,
            'effectiveness' => null,
            'extra' => [],
        ], $overrides));
    }

    public function test_can_view_returns_true_for_requester(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket(['requester_id' => $requester->id]);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $requester));
    }

    public function test_can_view_returns_true_for_assignee(): void
    {
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket(['assigned_to' => $assignee->id, 'status' => 'in-progress']);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $assignee));
    }

    public function test_can_view_returns_true_for_head_of_target_department(): void
    {
        $head = $this->departmentHead();
        $ticket = $this->makeTicket(['extra' => ['target_department' => $head->profile->department_id]]);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $head));
    }

    public function test_can_view_returns_false_for_unrelated_stranger(): void
    {
        $head = $this->departmentHead();
        $stranger = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket(['extra' => ['target_department' => $head->profile->department_id]]);

        $this->assertFalse(TicketAccessPolicy::canView($ticket, $stranger));
    }

    public function test_can_view_returns_false_for_null_user(): void
    {
        $ticket = $this->makeTicket();

        $this->assertFalse(TicketAccessPolicy::canView($ticket, null));
    }

    public function test_can_reply_returns_true_when_status_is_not_closing(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket(['requester_id' => $requester->id, 'status' => 'in-progress']);

        $this->assertTrue(TicketAccessPolicy::canReply($ticket, $requester));
    }

    public function test_can_reply_returns_false_on_closing_status_but_view_stays_true(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'closed',
            'effectiveness' => '5',
        ]);

        $this->assertFalse(TicketAccessPolicy::canReply($ticket, $requester));
        $this->assertFalse(TicketAccessPolicy::canReply($ticket, $assignee));
        $this->assertTrue(TicketAccessPolicy::canView($ticket, $requester));
        $this->assertTrue(TicketAccessPolicy::canView($ticket, $assignee));
    }

    public function test_can_assign_returns_true_only_for_head_of_target_department(): void
    {
        $head = $this->departmentHead();
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $head));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $requester));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $assignee));
    }

    public function test_can_assign_returns_false_for_null_user(): void
    {
        $head = $this->departmentHead();
        $ticket = $this->makeTicket(['extra' => ['target_department' => $head->profile->department_id]]);

        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, null));
    }

    public function test_can_set_effectiveness_returns_true_for_assignee_and_head_only(): void
    {
        $head = $this->departmentHead();
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($ticket, $assignee));
        $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($ticket, $head));
        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($ticket, $requester));
    }

    public function test_can_set_effectiveness_returns_false_for_null_user(): void
    {
        $ticket = $this->makeTicket();

        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($ticket, null));
    }

    public function test_can_close_mirrors_can_set_effectiveness(): void
    {
        $head = $this->departmentHead();
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket([
            'requester_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $this->assertTrue(TicketAccessPolicy::canClose($ticket, $assignee));
        $this->assertTrue(TicketAccessPolicy::canClose($ticket, $head));
        $this->assertFalse(TicketAccessPolicy::canClose($ticket, $requester));
        $this->assertFalse(TicketAccessPolicy::canClose($ticket, null));
    }

    public function test_head_check_uses_explicit_target_department_id_when_present(): void
    {
        $head = $this->departmentHead();
        $otherHead = $this->deptUser('PS', 'employee');
        $ticket = $this->makeTicket(['extra' => ['target_department' => $head->profile->department_id]]);

        $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $head));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $otherHead));
    }

    public function test_head_check_falls_back_to_default_target_department_when_explicit_id_absent(): void
    {
        $psHead = $this->deptUser('PS', 'chairman');
        $ticket = $this->makeTicket(['extra' => []]);

        $this->assertSame('PS', $ticket->targetDepartmentId);
        $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $psHead));
    }

    public function test_head_check_returns_false_when_no_target_department_resolves(): void
    {
        $this->markTestSkipped('requires a DB with no active ranked users in PS or BS; the live dev DB seeds PS users so defaultTargetDepartment() cannot return null without mutating master data');
    }

    public function test_head_check_returns_false_when_department_has_no_head_without_crashing(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->makeTicket(['extra' => ['target_department' => 'ZZ']]);

        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $user));
        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($ticket, $user));
        $this->assertFalse(TicketAccessPolicy::canView($ticket, $user));
    }

    public function test_elevated_roles_bypass_every_gate_for_tickets_they_are_not_involved_in(): void
    {
        $head = $this->departmentHead();
        $ticket = $this->makeTicket([
            'status' => 'in-progress',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        foreach (['admin', 'developer'] as $role) {
            $elevated = User::factory()->create(['status' => 'active', 'role' => $role]);

            $this->assertTrue(TicketAccessPolicy::canView($ticket, $elevated), $role);
            $this->assertTrue(TicketAccessPolicy::canReply($ticket, $elevated), $role);
            $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $elevated), $role);
            $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($ticket, $elevated), $role);
            $this->assertTrue(TicketAccessPolicy::canClose($ticket, $elevated), $role);
        }
    }

    public function test_null_user_never_has_access(): void
    {
        $ticket = $this->makeTicket();

        $this->assertFalse(TicketAccessPolicy::canView($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canReply($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canClose($ticket, null));
    }
}