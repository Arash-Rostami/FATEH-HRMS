<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ThsResource\Pages\CreateTicket;
use App\Filament\Resources\ThsResource\Pages\EditTicket;
use App\Filament\Resources\ThsResource\Pages\ListTickets;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ThsResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::forget('user_all_options');
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function deptStaff(string $deptCode): User
    {
        Department::firstOrCreate(['code' => $deptCode], ['name' => $deptCode, 'description' => $deptCode]);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $deptCode, 'position' => 'employee']);

        return $user;
    }

    public function test_list_renders_and_shows_factory_ticket_row()
    {
        $admin = $this->developer();

        $ticket = Ticket::factory()->create([
            'request_subject' => '0-test-list-render',
            'assigned_to' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$ticket]);
    }

    public function test_create_ticket_persists_required_fields()
    {
        $admin = $this->developer();
        $requester = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'support',
                'request_area' => 'windows_office',
                'request_subject' => '0-test-create-ticket',
                'description' => '0-test-create-ticket-body',
                'priority' => 'low',
                'status' => 'open',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tickets', [
            'request_subject' => '0-test-create-ticket',
            'request_type' => 'support',
            'request_area' => 'windows_office',
            'requester_id' => $requester->id,
            'priority' => 'low',
            'status' => 'open',
        ]);
    }

    public function test_create_ticket_requires_request_subject()
    {
        $admin = $this->developer();
        $requester = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'support',
                'request_area' => 'windows_office',
                'description' => '0-test-missing-subject-body',
                'priority' => 'low',
                'status' => 'open',
            ])
            ->call('create')
            ->assertHasFormErrors(['request_subject' => 'required']);
    }

    public function test_editing_a_ticket_whose_request_type_is_stale_relative_to_its_departments_current_options_no_longer_fails_validation()
    {
        $admin = $this->developer();

        $deptCode = 'ZZ' . random_int(100, 999);
        Department::create([
            'code' => $deptCode,
            'name' => $deptCode,
            'description' => $deptCode,
            'ticket_options' => [
                ['request_type' => 'custom', 'area_key' => 'a', 'area_label' => 'A', 'icon' => 'heroicon-o-flag'],
            ],
        ]);

        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'status' => 'in-progress',
            'request_type' => 'development',
            'request_area' => null,
            'requester_id' => $requester->id,
            'effectiveness' => '3',
            'extra' => ['target_department' => $deptCode, 'department' => $deptCode],
        ]);

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertSuccessful()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('development', $ticket->fresh()->request_type);
    }

    public function test_effectiveness_is_optional_when_closing_from_the_admin_panel()
    {
        $admin = $this->developer();

        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'status' => 'in-progress',
            'effectiveness' => null,
            'requester_id' => $requester->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertSuccessful()
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->set('data.status', 'closed')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_workspace_close_refreshes_the_admin_form_without_losing_unsaved_edits()
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $assignee = $this->deptStaff($dept);
        $ticket = Ticket::factory()->create([
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'effectiveness' => null,
            'requester_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'extra' => ['target_department' => $dept],
        ]);

        $component = Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->set('data.effectiveness', '3');

        Ticket::find($ticket->id)->update(['status' => 'closed']);

        $component->dispatch('ths-ticket-updated')->assertSet('data.effectiveness', '3');

        $status = $component->get('data.status');
        $this->assertSame('closed', $status instanceof \BackedEnum ? $status->value : $status);
        $this->assertNotEmpty($component->get('data.completion_date'));
    }

    public function test_workspace_signal_keeps_an_unsaved_assignee_after_a_save_and_drops_a_stale_completion_date()
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $first = $this->deptStaff($dept);
        $second = $this->deptStaff($dept);
        $ticket = Ticket::factory()->create([
            'requester_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'status' => 'open',
            'assigned_to' => null,
            'effectiveness' => null,
            'extra' => ['target_department' => $dept],
        ]);

        $component = Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['assigned_to' => $first->id])
            ->call('save')
            ->assertHasNoFormErrors()
            ->set('data.assigned_to', $second->id);

        $component->dispatch('ths-ticket-updated')->assertSet('data.assigned_to', $second->id);

        $component->set('data.status', 'closed');
        $this->assertNotEmpty($component->get('data.completion_date'));

        DB::table('tickets')->where('id', $ticket->id)->update(['status' => 'open', 'assigned_to' => null]);

        $component->dispatch('ths-ticket-updated');
        $this->assertEmpty($component->get('data.completion_date'));
    }

    public function test_assigning_someone_outside_the_department_from_the_admin_form_shows_a_message_beside_the_field()
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $this->deptStaff($dept);
        $outsider = $this->deptStaff('O' . Str::upper(Str::random(3)));
        $ticket = Ticket::factory()->create([
            'requester_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $dept],
        ]);

        Livewire::actingAs($admin)->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['assigned_to' => $outsider->id])
            ->call('save')
            ->assertHasFormErrors(['assigned_to']);

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_closing_a_ticket_from_the_admin_panel_marks_its_linked_task_done()
    {
        $admin = $this->developer();

        $dept = 'T' . Str::upper(Str::random(3));
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $assignee = $this->deptStaff($dept);
        $ticket = Ticket::factory()->create([
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'effectiveness' => null,
            'requester_id' => $requester->id,
            'extra' => ['target_department' => $dept],
        ]);
        $task = Task::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
        ]);

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->set('data.status', 'closed')
            ->set('data.effectiveness', '5')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertSame('done', $task->fresh()->status, 'closing a ticket from the admin panel must advance its linked task to done');
    }

    public function test_delete_ticket_action_removes_row()
    {
        $admin = $this->developer();

        $ticket = Ticket::factory()->create([
            'request_subject' => '0-test-delete-ticket',
            'assigned_to' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->callTableAction(DeleteAction::class, $ticket);

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_priority_filter_scopes_list_to_matching_tickets()
    {
        $admin = $this->developer();

        $low = Ticket::factory()->create([
            'request_subject' => '0-test-priority-low',
            'priority' => 'low',
            'assigned_to' => $admin->id,
        ]);
        $high = Ticket::factory()->create([
            'request_subject' => '0-test-priority-high',
            'priority' => 'high',
            'assigned_to' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('priority', 'high')
            ->assertCanSeeTableRecords([$high])
            ->assertCanNotSeeTableRecords([$low]);
    }

    public function test_department_filter_scopes_list_to_matching_target_department()
    {
        $admin = $this->developer();

        $dept = 'ZZ' . random_int(100, 999);
        Department::firstOrCreate(['code' => $dept], ['name' => $dept, 'description' => $dept]);

        $matching = Ticket::factory()->create([
            'request_subject' => '0-test-dept-match',
            'extra' => ['target_department' => $dept],
        ]);
        $nonMatching = Ticket::factory()->create([
            'request_subject' => '0-test-dept-no-match',
            'extra' => ['target_department' => 'IT'],
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('target_department', $dept)
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$nonMatching]);
    }

    public function test_type_filter_scopes_list_to_matching_request_type()
    {
        $admin = $this->developer();

        $support = Ticket::factory()->create([
            'request_subject' => '0-test-type-support',
            'request_type' => 'support',
        ]);
        $access = Ticket::factory()->create([
            'request_subject' => '0-test-type-access',
            'request_type' => 'access',
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('request_type', 'support')
            ->assertCanSeeTableRecords([$support])
            ->assertCanNotSeeTableRecords([$access]);
    }

    public function test_assignee_filter_scopes_list_to_tickets_assigned_to_selected_user()
    {
        $admin = $this->developer();
        $assigneeA = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $assigneeB = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $matching = Ticket::factory()->create([
            'request_subject' => '0-test-assignee-a',
            'assigned_to' => $assigneeA->id,
        ]);
        $nonMatching = Ticket::factory()->create([
            'request_subject' => '0-test-assignee-b',
            'assigned_to' => $assigneeB->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('assigned_to', $assigneeA->id)
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$nonMatching]);
    }

    public function test_requester_filter_scopes_list_to_tickets_requested_by_selected_user()
    {
        $admin = $this->developer();
        $requesterA = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $requesterB = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $matching = Ticket::factory()->create([
            'request_subject' => '0-test-requester-a',
            'requester_id' => $requesterA->id,
        ]);
        $nonMatching = Ticket::factory()->create([
            'request_subject' => '0-test-requester-b',
            'requester_id' => $requesterB->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('requester_id', $requesterA->id)
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$nonMatching]);
    }

    public function test_unassigned_filter_scopes_list_by_assignment_state()
    {
        $admin = $this->developer();

        $assigned = Ticket::factory()->create([
            'request_subject' => '0-test-assigned',
            'assigned_to' => $admin->id,
        ]);
        $unassigned = Ticket::factory()->create([
            'request_subject' => '0-test-unassigned',
            'assigned_to' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('unassigned', false)
            ->assertCanSeeTableRecords([$unassigned])
            ->assertCanNotSeeTableRecords([$assigned]);
    }

    public function test_overdue_filter_scopes_list_to_open_tickets_past_deadline()
    {
        $admin = $this->developer();

        $overdue = Ticket::factory()->create([
            'request_subject' => '0-test-overdue',
            'status' => 'in-progress',
            'completion_deadline' => now()->subDays(2),
        ]);
        $notOverdue = Ticket::factory()->create([
            'request_subject' => '0-test-not-overdue',
            'status' => 'in-progress',
            'completion_deadline' => now()->addDays(2),
        ]);

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$overdue])
            ->assertCanNotSeeTableRecords([$notOverdue]);
    }

    public function test_created_at_filter_scopes_list_to_tickets_in_date_range()
    {
        $admin = $this->developer();

        $inRange = Ticket::factory()->create(['request_subject' => '0-test-created-in-range']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Ticket::factory()->create(['request_subject' => '0-test-created-out-of-range']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)
            ->test(ListTickets::class)
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_admin_create_ticket_with_assignee_creates_linked_task()
    {
        $admin = $this->developer();
        $requester = User::factory()->create();
        $dept = 'T' . Str::upper(Str::random(3));
        Department::create([
            'code' => $dept,
            'name' => $dept,
            'description' => $dept,
            'ticket_options' => [
                ['request_type' => 'custom', 'area_key' => 'a', 'area_label' => 'A', 'icon' => 'heroicon-o-flag'],
            ],
        ]);
        $assignee = $this->deptStaff($dept);

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm(['extra' => ['target_department' => $dept]])
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'custom',
                'request_area' => 'a',
                'request_subject' => '0-admin-create-with-assignee',
                'description' => '0-admin-create-body',
                'priority' => 'low',
                'status' => 'open',
                'assigned_to' => $assignee->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $ticket = Ticket::where('request_subject', '0-admin-create-with-assignee')->firstOrFail();

        $task = Task::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($task, 'admin creating a ticket with an assignee must spawn the linked taskboard task');
        $this->assertSame($requester->id, $task->user_id);
        $this->assertSame($assignee->id, $task->assigned_to);
        $this->assertSame('todo', $task->status, 'creating an assigned ticket starts its mirrored task at todo; the assignee moves it');
    }

    public function test_admin_create_ticket_without_assignee_creates_no_task()
    {
        $admin = $this->developer();
        $requester = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'support',
                'request_area' => 'windows_office',
                'request_subject' => '0-admin-create-no-assignee',
                'description' => '0-admin-create-no-assignee-body',
                'priority' => 'low',
                'status' => 'open',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $ticket = Ticket::where('request_subject', '0-admin-create-no-assignee')->firstOrFail();

        $this->assertDatabaseMissing('tasks', ['ticket_id' => $ticket->id]);
    }

    public function test_admin_edit_assignee_creates_linked_task_when_none_exists()
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $assignee = $this->deptStaff($dept);

        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'assigned_to' => null,
            'request_subject' => '0-admin-edit-assign',
            'extra' => ['target_department' => $dept],
        ]);

        $this->assertNull(Task::where('ticket_id', $ticket->id)->first());

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['assigned_to' => $assignee->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $task = Task::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($task, 'admin assigning a previously-unassigned ticket must spawn the linked task');
        $this->assertSame($requester->id, $task->user_id);
        $this->assertSame($assignee->id, $task->assigned_to);
    }

    public function test_admin_edit_reassign_updates_linked_task_assignee()
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $first = $this->deptStaff($dept);
        $second = $this->deptStaff($dept);

        $ticket = Ticket::factory()->create([
            'requester_id' => $admin->id,
            'status' => 'in-progress',
            'assigned_to' => $first->id,
            'request_subject' => '0-admin-reassign',
            'extra' => ['target_department' => $dept],
        ]);

        Task::create([
            'title' => 'seed',
            'description' => 'seed',
            'status' => 'todo',
            'user_id' => $admin->id,
            'assigned_to' => $first->id,
            'ticket_id' => $ticket->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['assigned_to' => $second->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Task::where('ticket_id', $ticket->id)->count(), 'reassignment must update the existing linked task, not duplicate');
        $this->assertSame($second->id, Task::where('ticket_id', $ticket->id)->first()->assigned_to);
    }

    public function test_admin_edit_clear_assignee_nulls_linked_task_assignee()
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $assignee = $this->deptStaff($dept);

        $ticket = Ticket::factory()->create([
            'requester_id' => $admin->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'request_subject' => '0-admin-clear-assignee',
            'extra' => ['target_department' => $dept],
        ]);

        Task::create([
            'title' => 'seed',
            'description' => 'seed',
            'status' => 'todo',
            'user_id' => $admin->id,
            'assigned_to' => $assignee->id,
            'ticket_id' => $ticket->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['assigned_to' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(Task::where('ticket_id', $ticket->id)->first(), 'clearing the assignee must remove the mirror task from the board');
        $this->assertNotNull(Task::withTrashed()->where('ticket_id', $ticket->id)->first()?->deleted_at);
    }

    public function test_admin_can_change_ticket_priority_via_edit_form()
    {
        $admin = $this->developer();

        $ticket = Ticket::factory()->create([
            'requester_id' => $admin->id,
            'status' => 'in-progress',
            'priority' => 'low',
            'request_subject' => '0-admin-change-priority',
        ]);

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['priority' => 'high'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('high', $ticket->fresh()->priority);
    }

    public function test_admin_edit_priority_change_resyncs_linked_task_priority_and_rank()
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $assignee = $this->deptStaff($dept);

        $ticket = Ticket::factory()->create([
            'requester_id' => $admin->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'priority' => 'low',
            'request_subject' => '0-admin-priority-resync',
            'extra' => ['target_department' => $dept],
        ]);

        $task = Task::create([
            'title' => 'seed',
            'description' => 'seed',
            'status' => 'todo',
            'user_id' => $admin->id,
            'assigned_to' => $assignee->id,
            'ticket_id' => $ticket->id,
            'priority' => 'low',
            'rank' => Task::rankForPriority(null, $assignee->id, 'todo', 'low'),
        ]);

        Task::create([
            'title' => 'sibling',
            'description' => 'sibling',
            'status' => 'todo',
            'user_id' => $admin->id,
            'assigned_to' => $assignee->id,
            'priority' => 'high',
            'rank' => Task::rankForPriority(null, $assignee->id, 'todo', 'high'),
        ]);

        $originalRank = $task->rank;

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['priority' => 'high'])
            ->call('save')
            ->assertHasNoFormErrors();

        $task->refresh();
        $this->assertSame('high', $task->priority->value);
        $this->assertSame('todo', $task->status, 'a resync for the same assignee keeps the status the assignee chose');
        $this->assertNotSame($originalRank, $task->rank);
    }

    public function test_create_ticket_target_department_change_resets_stale_request_type()
    {
        $admin = $this->developer();
        $requester = User::factory()->create();

        $dept = 'ZZ' . random_int(100, 999);
        Department::create([
            'code' => $dept,
            'name' => $dept,
            'description' => $dept,
            'ticket_options' => [
                ['request_type' => 'access', 'area_key' => 'x', 'area_label' => 'X'],
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'support',
            ])
            ->set('data.extra.target_department', $dept)
            ->assertSet('data.request_type', 'access');
    }

    public function test_create_ticket_target_department_change_keeps_request_type_when_still_valid()
    {
        $admin = $this->developer();
        $requester = User::factory()->create();

        $dept = 'ZZ' . random_int(100, 999);
        Department::create([
            'code' => $dept,
            'name' => $dept,
            'description' => $dept,
            'ticket_options' => [
                ['request_type' => 'support', 'area_key' => 'x', 'area_label' => 'X'],
                ['request_type' => 'access', 'area_key' => 'y', 'area_label' => 'Y'],
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'support',
            ])
            ->set('data.extra.target_department', $dept)
            ->assertSet('data.request_type', 'support');
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Ticket::factory()->create([
            'request_subject' => '0-test-group-render',
            'assigned_to' => $admin->id,
        ]);

        $component = Livewire::actingAs($admin)->test(ListTickets::class)->assertSuccessful();

        foreach (['status', 'assignee.name', 'request_type'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sorting_by_completion_date_and_deadline_orders_records_correctly()
    {
        $admin = $this->developer();
        $earlier = Ticket::factory()->create([
            'request_subject' => '0-sort-completion-a',
            'completion_date' => now()->subDays(30),
            'completion_deadline' => now()->subDays(30),
        ]);
        $later = Ticket::factory()->create([
            'request_subject' => '0-sort-completion-b',
            'completion_date' => now()->addDays(30),
            'completion_deadline' => now()->addDays(30),
        ]);

        $component = Livewire::actingAs($admin)->test(ListTickets::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('completion_date')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('completion_date', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('completion_deadline')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$earlier->id, $later->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));

        $component->sortTable('completion_deadline', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$later->id, $earlier->id], array_values(array_intersect($ids, [$earlier->id, $later->id])));
    }

    public function test_sorting_by_created_at_and_id_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = Ticket::factory()->create(['request_subject' => '0-sort-created-a']);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = Ticket::factory()->create(['request_subject' => '0-sort-created-b']);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListTickets::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_effectiveness_and_satisfaction_orders_records_correctly()
    {
        $admin = $this->developer();
        $low = Ticket::factory()->create([
            'request_subject' => '0-sort-eff-a',
            'effectiveness' => '1',
            'satisfaction_score' => 1,
        ]);
        $high = Ticket::factory()->create([
            'request_subject' => '0-sort-eff-b',
            'effectiveness' => '5',
            'satisfaction_score' => 5,
        ]);

        $component = Livewire::actingAs($admin)->test(ListTickets::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('effectiveness')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('effectiveness', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('satisfaction_score')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('satisfaction_score', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));
    }

    public function test_sorting_by_priority_status_and_request_type_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Ticket::factory()->create([
            'request_subject' => '0-sort-priority-a',
            'priority' => 'high',
            'status' => 'closed',
            'effectiveness' => '3',
            'request_type' => 'access',
        ]);
        $second = Ticket::factory()->create([
            'request_subject' => '0-sort-priority-b',
            'priority' => 'low',
            'status' => 'open',
            'request_type' => 'support',
        ]);

        $component = Livewire::actingAs($admin)->test(ListTickets::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('priority')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('priority', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('status')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('status', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('request_type')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])), 'sorted by label (پشتیبانی before دسترسی), not by raw key');

        $component->sortTable('request_type', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_requester_orders_records_correctly()
    {
        $admin = $this->developer();
        $requesterA = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'AAA-sort-requester']);
        $requesterB = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'ZZZ-sort-requester']);
        $first = Ticket::factory()->create(['request_subject' => '0-sort-requester-a', 'requester_id' => $requesterA->id]);
        $second = Ticket::factory()->create(['request_subject' => '0-sort-requester-b', 'requester_id' => $requesterB->id]);

        $component = Livewire::actingAs($admin)->test(ListTickets::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('requester.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('requester.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ThsResource\Pages\CreateTicket::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ThsResource\Pages\EditTicket::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ThsResource\Pages\ListTickets::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ThsResource\Pages\CreateTicket::class, \App\Filament\Resources\ThsResource\Pages\EditTicket::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ThsResource/Schemas/TicketTablePresenter.php'),
        ];

        $recordMethods = ['getTitleFromRecordUsing', 'getKeyFromRecordUsing', 'getDescriptionFromRecordUsing'];
        $queryMethods = ['modifyQueryUsing', 'groupQueryUsing', 'orderQueryUsing', 'scopeQueryByKeyUsing', 'scopeQueryUsing', 'query'];

        $offenders = [];

        foreach ([[$recordMethods, 'record'], [$queryMethods, 'query']] as [$methods, $expectedName]) {
            $pattern = '/\\b(' . implode('|', $methods) . ')\\s*\\(\\s*(?:static\\s+)?(?:function|fn)\\s*\\(([^)]*)\\)/s';

            foreach ($files as $path) {
                $contents = file_get_contents($path);

                if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $method = $match[1][0];
                    $paramList = $match[2][0];
                    $offset = $match[0][1];

                    $firstParam = trim(explode(',', $paramList)[0] ?? '');

                    if ($firstParam === '') {
                        continue;
                    }

                    if (!preg_match('/^(?<type>[^$]*)\\$(?<name>[A-Za-z_][A-Za-z0-9_]*)/', $firstParam, $m)) {
                        continue;
                    }

                    $hasType = trim($m['type']) !== '';
                    $name = $m['name'];

                    if ($name === $expectedName || $hasType) {
                        continue;
                    }

                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = basename($path) . ":{$line} {$method}(fn(\${$name}) => ...)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closure param must be named \$record/\$query (or carry an explicit type hint) so Filament's by-name evaluate() injection binds correctly:\n" . implode("\n", $offenders),
        );
    }

    public function test_closed_ticket_status_field_is_disabled_in_the_admin_form_while_open_ones_stay_editable(): void
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $staff = $this->deptStaff($dept);
        $mk = fn(string $status) => Ticket::factory()->create([
            'requester_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'status' => $status,
            'assigned_to' => $status === 'open' ? null : $staff->id,
            'effectiveness' => $status === 'closed' ? '4' : null,
            'extra' => ['target_department' => $dept],
        ]);

        Livewire::actingAs($admin)->test(EditTicket::class, ['record' => $mk('closed')->getRouteKey()])
            ->assertFormFieldDisabled('status');
        Livewire::actingAs($admin)->test(EditTicket::class, ['record' => $mk('open')->getRouteKey()])
            ->assertFormFieldEnabled('status');
    }

    public function test_edit_tracked_file_columns_delete_only_the_dropped_ticket_files(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new EditTicket());
        \Illuminate\Support\Facades\Storage::fake('public');
        foreach (['ticket/requester/old.pdf', 'ticket/requester/kept.pdf', 'ticket/assignee/a.pdf'] as $path) {
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'x');
        }
        $record = Ticket::factory()->create([
            'requester_files' => [['file' => 'ticket/requester/old.pdf'], ['file' => 'ticket/requester/kept.pdf']],
            'assignee_files' => [['file' => 'ticket/assignee/a.pdf']],
        ]);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['ticket/requester/old.pdf', 'ticket/requester/kept.pdf', 'ticket/assignee/a.pdf'], $before);

        $record->update(['requester_files' => [['file' => 'ticket/requester/kept.pdf']]]);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('ticket/requester/old.pdf');
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('ticket/requester/kept.pdf');
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('ticket/assignee/a.pdf');
    }

    private function labelFixture(): array
    {
        $dept = 'ZL' . random_int(1000, 9999);
        Department::create([
            'code' => $dept,
            'name' => $dept,
            'description' => $dept,
            'ticket_options' => [
                ['request_type' => 'support', 'area_key' => 'zz_b_area', 'area_label' => 'AAA-LBLZQ'],
                ['request_type' => 'support', 'area_key' => 'zz_a_area', 'area_label' => 'ZZZ-LBLZQ'],
            ],
        ]);
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $make = fn(string $area, array $extra = []) => Ticket::factory()->create([
            'requester_id' => $requester->id,
            'request_type' => 'support',
            'request_area' => $area,
            'extra' => ['target_department' => $dept, ...$extra],
        ]);

        return [$make('zz_b_area'), $make('zz_a_area'), $make('zz_orphan_key'), $dept, $requester];
    }

    private function legacyTicket(User $requester): Ticket
    {
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'request_type' => 'support', 'request_area' => null]);
        DB::table('tickets')->where('id', $ticket->id)->update(['extra' => null]);

        return Ticket::find($ticket->id);
    }

    public function test_area_column_is_visible_by_default_and_shows_labels_with_raw_key_for_orphans(): void
    {
        Cache::flush();
        $admin = $this->developer();
        [$b, , $orphan] = $this->labelFixture();

        $component = Livewire::actingAs($admin)->test(ListTickets::class);

        $this->assertFalse($component->instance()->getTable()->getColumn('request_area')->isToggledHiddenByDefault());
        $component
            ->assertTableColumnFormattedStateSet('request_area', 'AAA-LBLZQ', $b)
            ->assertTableColumnFormattedStateSet('request_area', 'zz_orphan_key', $orphan);
    }

    public function test_area_search_finds_ticket_by_label_and_by_raw_key(): void
    {
        Cache::flush();
        $admin = $this->developer();
        [$b, $a] = $this->labelFixture();

        Livewire::actingAs($admin)->test(ListTickets::class)
            ->searchTable('AAA-LBLZQ')
            ->assertCanSeeTableRecords([$b])
            ->assertCanNotSeeTableRecords([$a])
            ->searchTable('zz_a_area')
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }

    public function test_area_and_type_sort_by_label_not_by_raw_key(): void
    {
        Cache::flush();
        $admin = $this->developer();
        [$b, $a, , $dept] = $this->labelFixture();
        $ids = fn($component) => $component->instance()->getTableRecords()->pluck('id')->intersect([$a->id, $b->id])->values()->all();
        $component = Livewire::actingAs($admin)->test(ListTickets::class)->filterTable('request_area', ['zz_a_area', 'zz_b_area']);

        $this->assertSame([$b->id, $a->id], $ids($component->sortTable('request_area')));
        $this->assertSame([$a->id, $b->id], $ids($component->sortTable('request_area', 'desc')));
        $component->sortTable('request_type')->assertSuccessful();
    }

    public function test_filters_include_values_present_only_in_tickets_and_match_variant_spellings(): void
    {
        Cache::flush();
        $admin = $this->developer();
        [, , $orphan, , $requester] = $this->labelFixture();
        $arabic = Ticket::factory()->create(['requester_id' => $requester->id, 'request_type' => 'zz_ورود', 'request_area' => 'كيف_zz_var']);
        $persian = Ticket::factory()->create(['requester_id' => $requester->id, 'request_type' => 'zz_ورود', 'request_area' => 'کیف_zz_var']);
        Cache::flush();

        $areas = Ticket::requestAreaFilterOptions();
        $this->assertArrayHasKey('zz_orphan_key', $areas);
        $this->assertSame('zz_orphan_key', $areas['zz_orphan_key']);
        $this->assertArrayHasKey('zz_ورود', Ticket::requestTypeFilterOptions());
        $variantKey = collect(array_keys($areas))->first(fn($k) => in_array($k, ['كيف_zz_var', 'کیف_zz_var'], true));
        $this->assertNotNull($variantKey);

        Livewire::actingAs($admin)->test(ListTickets::class)
            ->filterTable('request_area', ['zz_orphan_key'])
            ->assertCanSeeTableRecords([$orphan])
            ->assertCanNotSeeTableRecords([$arabic, $persian])
            ->filterTable('request_area', [$variantKey])
            ->assertCanSeeTableRecords([$arabic, $persian])
            ->assertCanNotSeeTableRecords([$orphan])
            ->resetTableFilters()
            ->filterTable('request_type', ['zz_ورود'])
            ->assertCanSeeTableRecords([$arabic, $persian])
            ->assertCanNotSeeTableRecords([$orphan]);
    }

    public function test_exporter_gives_labels_and_both_key_columns_for_normal_and_legacy_rows(): void
    {
        Cache::flush();
        [$normal, , $orphan, , $requester] = $this->labelFixture();
        $legacy = $this->legacyTicket($requester);
        $map = array_fill_keys(['request_type', 'request_type_key', 'request_area', 'request_area_key'], true);
        $export = fn(Ticket $t) => (new \App\Filament\Resources\ThsResource\Exports\ThsExporter(new \Filament\Actions\Exports\Models\Export(), $map, []))($t);

        $this->assertSame([Ticket::$requestTypeOptions['support'], 'support', 'AAA-LBLZQ', 'zz_b_area'], $export($normal));
        $this->assertSame([Ticket::$requestTypeOptions['support'], 'support', 'zz_orphan_key', 'zz_orphan_key'], $export($orphan));
        [$type, $typeKey, $area, $areaKey] = $export($legacy);
        $this->assertSame([Ticket::$requestTypeOptions['support'], 'support'], [$type, $typeKey]);
        $this->assertEmpty($area);
        $this->assertEmpty($areaKey);
    }

    public function test_legacy_ticket_without_target_department_or_area_renders_in_the_list(): void
    {
        Cache::flush();
        $admin = $this->developer();
        $legacy = $this->legacyTicket(User::factory()->create(['status' => 'active', 'role' => 'user']));

        Livewire::actingAs($admin)->test(ListTickets::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$legacy]);
    }

    public function test_assignee_options_hold_no_null_label_when_assigned_user_was_deleted(): void
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $this->deptStaff($dept);
        $ghost = $this->deptStaff($dept);
        $ticket = Ticket::factory()->create([
            'requester_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'status' => 'open',
            'assigned_to' => $ghost->id,
            'extra' => ['target_department' => $dept],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->where('id', $ghost->id)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        Cache::forget('user_all_options');
        Cache::flush();

        $page = Livewire::actingAs($admin)->test(EditTicket::class, ['record' => $ticket->getRouteKey()])->assertSuccessful();

        $select = collect($page->instance()->getSchema('form')->getFlatComponents())->first(fn($c) => $c instanceof \Filament\Forms\Components\Select && $c->getName() === 'assigned_to');
        $options = $select->getOptions();

        $this->assertNotContains(null, $options, 'no null label may reach the select');
        $this->assertArrayNotHasKey($ghost->id, $options);
    }

    public function test_in_progress_requires_an_assignee_and_reopening_clears_it(): void
    {
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $staff = $this->deptStaff($dept);
        $ticket = Ticket::factory()->create([
            'requester_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $dept],
        ]);

        Livewire::actingAs($admin)->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['status' => 'in-progress'])
            ->call('save')
            ->assertHasFormErrors(['assigned_to']);

        Livewire::actingAs($admin)->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['assigned_to' => $staff->id])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('in-progress', Ticket::find($ticket->id)->status);

        Livewire::actingAs($admin)->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->fillForm(['status' => 'open'])
            ->call('save');
        $this->assertNull(Ticket::find($ticket->id)->assigned_to);
        $this->assertNull(Task::where('ticket_id', $ticket->id)->first());
    }

    public function test_view_modal_renders_the_ticket_and_department_insight_tabs_for_open_in_progress_and_closed_tickets(): void
    {
        app()->setLocale('fa');
        $admin = $this->developer();
        $dept = 'T' . Str::upper(Str::random(3));
        $staff = $this->deptStaff($dept);
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $make =fn(?int $assignee = null) => Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'assigned_to' => $assignee,
            'extra' => ['target_department' => $dept],
        ]);

        $open = $make();
        $progress = $make($staff->id);
        $closed = $make();
        DB::table('tickets')->where('id', $progress->id)->update(['status' => 'in-progress']);
        DB::table('tickets')->where('id', $closed->id)->update(['status' => 'closed', 'completion_date' => now()]);
        Cache::flush();

        foreach ([$open, $progress, $closed] as $ticket) {
            $page = Livewire::actingAs($admin)->test(ListTickets::class)
                ->mountTableAction('view', $ticket)
                ->assertSuccessful();
            $components = collect((new \ReflectionMethod($page->instance(), 'getMountedActionSchema'))->invoke($page->instance())->getFlatComponents());
            $labels = $components->filter(fn($c) => $c instanceof \Filament\Schemas\Components\Tabs\Tab)->map(fn($c) => (string)$c->getLabel());

            $this->assertContains(__('resources/ths/strings.insight.tab_ticket'), $labels);
            $this->assertContains(__('resources/ths/strings.insight.tab_department'), $labels);
            $this->assertStringContainsString(
                __('resources/ths/strings.insight.cards.owner'),
                $components->first(fn($c) => $c instanceof ViewEntry && $c->getName() === 'ticket_insights')->toHtml(),
            );
            $this->assertStringContainsString(
                __('resources/ths/strings.insight.dept.volume'),
                $components->first(fn($c) => $c instanceof ViewEntry && $c->getName() === 'department_insights')->toHtml(),
            );
        }
    }
}
