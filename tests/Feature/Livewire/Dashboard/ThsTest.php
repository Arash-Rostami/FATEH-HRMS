<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Livewire\Dashboard\Ths\Main;
use App\Livewire\Dashboard\Ths\Workspace;
use App\Livewire\Dashboard\TaskBoard\Main as TaskBoardMain;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Menu\NudgeService;
use App\Services\Menu\StateService;
use App\Support\TicketAccessPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ThsTest extends TestCase
{
    private function deptUser(string $dept, string $position): User
    {
        Department::firstOrCreate(['code' => $dept], ['name' => $dept]);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'role' => 'user']);
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

        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code, 'position' => 'employee']);

        return $user;
    }

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

    public function test_ths_module_crud_lifecycle()
    {
        $requester = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->set('ticket.requestType', 'support')
            ->set('ticket.priority', 'medium')
            ->set('ticket.subject', 'Tinker CRUD probe')
            ->set('ticket.description', 'Minimal description for automated probe.')
            ->set('ticket.requestArea', 'vpn')
            ->call('submitTicket')
            ->assertHasNoErrors();

        $ticket = Ticket::where('requester_id', $requester->id)->where('request_subject', 'Tinker CRUD probe')->firstOrFail();
        $this->assertSame('open', $ticket->status);
        $this->assertSame('vpn', $ticket->request_area);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->call('viewTicket', $ticket->id)
            ->assertHasNoErrors()
            ->assertSet('modalTab', 'request');

        $ticket->update(['status' => 'closed', 'satisfaction_score' => null, 'effectiveness' => '5']);

        Livewire::withoutLazyLoading();

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->assertSet('activeTab', 'rate')
            ->call('rate', 4)
            ->call('submitRating')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertSame(4.0, (float) $ticket->satisfaction_score);
    }

    public function test_submit_ticket_persists_attachment_when_file_added()
    {
        Storage::fake('public');

        $requester = $this->deptUser('PS', 'expert');
        $key = Str::random(13);
        $file = UploadedFile::fake()->image('screenshot.png', 20, 20);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->set('ticket.requestType', 'support')
            ->set('ticket.priority', 'low')
            ->set('ticket.subject', 'Attachment probe')
            ->set('ticket.description', 'Submitting a ticket with a file attachment.')
            ->set('ticket.requestArea', 'vpn')
            ->set('ticket.files.' . $key, $file)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $ticket = Ticket::where('requester_id', $requester->id)
            ->where('request_subject', 'Attachment probe')
            ->firstOrFail();

        $this->assertNotNull($ticket->requester_files, 'requester_files should be set when a file is attached.');
        $this->assertCount(1, $ticket->requester_files);
        $path = $ticket->requester_files[0]['file'] ?? null;
        $this->assertNotNull($path, 'stored file path should be present.');
        $this->assertTrue(
            Str::startsWith($path, 'ticket/requester/'),
            'file should be stored under ticket/requester/, got: ' . $path
        );
        Storage::disk('public')->assertExists($path);
    }

    public function test_workspace_lets_requester_and_assignee_reply_two_way()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
        ]);

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', 'در حال بررسی هستم.')
            ->call('postReply')
            ->assertHasNoErrors();

        Livewire::actingAs($requester)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', 'متشکرم، منتظر می‌مانم.')
            ->call('postReply')
            ->assertHasNoErrors();

        $fresh = $ticket->fresh();
        $this->assertSame(2, $fresh->replies()->count());
        $this->assertSame($assignee->id, $fresh->replies->get(0)->user_id);
        $this->assertSame($requester->id, $fresh->replies->get(1)->user_id);
    }

    public function test_unrelated_user_cannot_reply_via_tampered_call()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $unrelated = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
        ]);

        Livewire::actingAs($unrelated)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->assertStatus(403);

        $this->assertSame(0, $ticket->fresh()->replies()->count());
    }

    public function test_workspace_ticket_id_is_locked_against_client_tampering()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $otherRequester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress']);
        $otherTicket = Ticket::factory()->create(['requester_id' => $otherRequester->id, 'status' => 'in-progress']);

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($requester)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('ticketId', $otherTicket->id);
    }

    public function test_department_head_can_assign_open_ticket_and_status_flips_to_in_progress()
    {
        $head = $this->departmentHead();
        $staff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $staff->id)
            ->call('assign')
            ->assertHasNoErrors();

        $fresh = $ticket->fresh();
        $this->assertSame($staff->id, $fresh->assigned_to);
        $this->assertSame('in-progress', $fresh->status, 'assigning a staff member must auto-flip status to in-progress');
    }

    public function test_assignable_users_includes_presence_for_the_picker_avatar_dot()
    {
        $head = $this->departmentHead();
        $staff = User::factory()->create(['status' => 'active', 'presence' => 'leave', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $candidate = Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->instance()->assignableUsers->firstWhere('id', $staff->id);

        $this->assertNotNull($candidate);
        $this->assertSame(\App\Enums\PresenceStatus::Leave, $candidate->presence);
    }

    public function test_assignee_picker_shows_unreachable_tag_and_real_online_state_for_leave_staff()
    {
        $head = $this->departmentHead();
        $onLeave = User::factory()->create(['status' => 'active', 'presence' => 'leave', 'role' => 'user', 'last_seen' => now(), 'name' => 'مرخصی نشین']);
        $present = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user', 'last_seen' => null, 'name' => 'حاضر نشین']);
        foreach ([$onLeave, $present] as $staff) {
            Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);
        }

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $html = Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])->html();

        $this->assertSame(1, substr_count($html, 'پاسخ‌گو نیست'), 'only the leave user carries the tag, and assigning is never blocked');
        $this->assertStringContainsString('title="آنلاین"', $html);
        $this->assertStringContainsString('title="آفلاین · هرگز"', $html);
    }

    public function test_assigning_a_ticket_creates_a_linked_task_visible_to_both_requester_and_assignee()
    {
        $head = $this->departmentHead();
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $staff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'assigned_to' => null,
            'request_subject' => 'مشکل در دسترسی به سیستم',
            'description' => 'صفحه ورود بعد از بروزرسانی اخیر خطا می‌دهد.',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $staff->id)
            ->call('assign')
            ->assertHasNoErrors();

        $task = Task::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($task, 'assigning a ticket must create a linked task');
        $this->assertSame($requester->id, $task->user_id);
        $this->assertSame($staff->id, $task->assigned_to);
        $this->assertStringContainsString($ticket->description, $task->description, 'task description must carry the requester notes so multiple same-day tickets stay distinguishable');
        $this->assertNotNull($task->detail, 'linked task must still get its detail row like any other task');

        Livewire::withoutLazyLoading();
        Livewire::actingAs($requester)
            ->test(TaskBoardMain::class)
            ->call('switchTab', 'assigned-tasks')
            ->assertSee($task->title);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($staff)
            ->test(TaskBoardMain::class)
            ->assertSee($task->title);
    }

    public function test_assigning_a_high_priority_ticket_with_no_linked_task_creates_one_with_matching_priority_and_rank()
    {
        $head = $this->departmentHead();
        $staff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'priority' => 'high',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $staff->id)
            ->call('assign')
            ->assertHasNoErrors();

        $task = Task::where('ticket_id', $ticket->id)->firstOrFail();
        $this->assertSame('high', $task->priority->value);
        $this->assertNotNull($task->rank);
    }

    public function test_ticket_priority_escalation_updates_the_linked_task_priority_and_repositions_its_rank()
    {
        $head = $this->departmentHead();
        $staff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'priority' => 'low',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $staff->id)
            ->call('assign')
            ->assertHasNoErrors();

        $task = Task::where('ticket_id', $ticket->id)->firstOrFail();
        $lowerSibling = Task::factory()->create([
            'user_id' => $staff->id,
            'assigned_to' => $staff->id,
            'status' => 'todo',
            'project_id' => null,
            'priority' => 'low',
            'rank' => \App\Services\ProjectTask\RankGenerator::between($task->rank, null),
        ]);

        $ticket->update(['priority' => 'high']);
        app(\App\Livewire\Dashboard\Ths\Actions\AssignTicketAction::class)->syncForAdmin($ticket->fresh(), $staff->id);

        $task->refresh();
        $lowerSibling->refresh();
        $this->assertSame('high', $task->priority->value);
        $this->assertTrue(strcmp($task->rank, $lowerSibling->rank) < 0, 'the escalated task must now rank ahead of the lower-priority sibling');
    }

    public function test_resaving_a_ticket_whose_priority_did_not_change_leaves_the_linked_task_rank_untouched()
    {
        $head = $this->departmentHead();
        $staff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $staff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'priority' => 'medium',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $staff->id)
            ->call('assign')
            ->assertHasNoErrors();

        $task = Task::where('ticket_id', $ticket->id)->firstOrFail();
        $rankBefore = $task->rank;

        app(\App\Livewire\Dashboard\Ths\Actions\AssignTicketAction::class)->syncForAdmin($ticket->fresh(), $staff->id);

        $this->assertSame($rankBefore, $task->fresh()->rank, 'rank must be byte-identical when the ticket priority did not change');
    }

    public function test_reassigning_a_ticket_updates_the_existing_linked_task_instead_of_duplicating()
    {
        $head = $this->departmentHead();
        $firstStaff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $firstStaff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);
        $secondStaff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $secondStaff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $firstStaff->id)->call('assign');

        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->fresh()->id])
            ->set('assigneeId', (string) $secondStaff->id)->call('assign');

        $this->assertSame(1, Task::where('ticket_id', $ticket->id)->count(), 'reassignment must update the existing linked task, not create a duplicate');
        $this->assertSame($secondStaff->id, Task::where('ticket_id', $ticket->id)->first()->assigned_to);
    }

    public function test_assign_rejects_a_user_outside_the_target_department()
    {
        $head = $this->departmentHead();
        $outsider = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $outsider->id)
            ->call('assign')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'کاربر انتخاب‌شده متعلق به این واحد سازمانی نیست.', type: 'error');

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_assign_rejects_reassignment_of_a_closed_ticket()
    {
        $head = $this->departmentHead();
        $originalStaff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $originalStaff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);
        $newStaff = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $newStaff->id, 'department_id' => $head->profile->department_id, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'closed',
            'assigned_to' => $originalStaff->id,
            'effectiveness' => '5',
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $newStaff->id)
            ->call('assign')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'امکان تغییر مسئول تیکت بسته‌شده وجود ندارد.', type: 'error');

        $this->assertSame($originalStaff->id, $ticket->fresh()->assigned_to);
    }

    public function test_effectiveness_settable_by_assignee_and_close_requires_it_first()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'effectiveness' => null,
        ]);

        $component = Livewire::actingAs($assignee)->test(Workspace::class, ['ticketId' => $ticket->id]);

        $component->call('closeTicket');
        $this->assertSame('in-progress', $ticket->fresh()->status, 'closing without effectiveness must be blocked');

        $component->call('setEffectiveness', '4');
        $this->assertSame('4', $ticket->fresh()->effectiveness);

        $component->call('closeTicket');
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_closing_a_ticket_marks_its_linked_task_done()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'effectiveness' => null,
        ]);
        $task = Task::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $requester->id,
            'assigned_to' => $assignee->id,
            'status' => 'in-progress',
        ]);

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->call('setEffectiveness', '5')
            ->call('closeTicket');

        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertSame('done', $task->fresh()->status, 'closing the ticket must advance its linked task to done');
    }

    public function test_closing_composes_action_result_from_full_reply_thread()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'effectiveness' => null,
        ]);

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', 'مشکل بررسی و رفع شد.')
            ->call('postReply');

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->call('setEffectiveness', '5')
            ->call('closeTicket');

        $this->assertStringContainsString('مشکل بررسی و رفع شد.', $ticket->fresh()->action_result);
    }

    public function test_reply_attachment_captures_metadata_before_the_temp_file_is_moved()
    {
        Storage::fake('public');

        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['status' => 'in-progress', 'assigned_to' => $assignee->id]);
        $file = UploadedFile::fake()->image('proof.png', 30, 30);

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', 'مستندات پیوست شد.')
            ->set('replyForm.files.0', $file)
            ->call('postReply')
            ->assertHasNoErrors();

        $reply = $ticket->fresh()->replies()->first();
        $this->assertCount(1, $reply->files);
        $stored = $reply->files[0];

        $this->assertSame('proof.png', $stored['name']);
        $this->assertStringContainsString('image/', $stored['mime']);
        $this->assertGreaterThan(0, $stored['size']);
        $this->assertStringStartsWith('ticket/replies/', $stored['path']);
        Storage::disk('public')->assertExists($stored['path']);
    }

    public function test_reply_with_only_attachment_and_no_body_passes_validation()
    {
        Storage::fake('public');

        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['status' => 'in-progress', 'assigned_to' => $assignee->id]);
        $file = UploadedFile::fake()->image('proof.png', 30, 30);

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', '')
            ->set('replyForm.files.0', $file)
            ->call('postReply')
            ->assertHasNoErrors();

        $this->assertSame(1, $ticket->fresh()->replies()->count());
    }

    public function test_remove_reply_attachment_drops_a_pending_upload()
    {
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['status' => 'in-progress', 'assigned_to' => $assignee->id]);
        $file1 = UploadedFile::fake()->image('a.png', 20, 20);
        $file2 = UploadedFile::fake()->image('b.png', 20, 20);

        $component = Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.files', [$file1, $file2]);

        $this->assertCount(2, $component->instance()->replyForm->files);

        $component->call('removeReplyAttachment', 0);
        $this->assertCount(1, $component->instance()->replyForm->files);
        $this->assertSame('b.png', $component->instance()->replyForm->files[0]->getClientOriginalName());
    }

    public function test_actionable_filter_lists_open_department_tickets_and_in_progress_assignments()
    {
        $head = $this->departmentHead();
        $openForHead = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);
        $unrelatedOpen = Ticket::factory()->create(['status' => 'open', 'assigned_to' => null]);

        $inbox = Livewire::actingAs($head)->test(Main::class)->instance()->inboxTickets;

        $this->assertNotNull($inbox->firstWhere('id', $openForHead->id));
        $this->assertNull($inbox->firstWhere('id', $unrelatedOpen->id));
    }

    public function test_list_filter_toggles_between_mine_and_actionable_sharing_one_table()
    {
        $head = $this->departmentHead();
        $mine = Ticket::factory()->create(['requester_id' => $head->id, 'status' => 'open', 'assigned_to' => null]);
        $actionable = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($head)->test(Main::class);
        $component->assertSet('listFilter', 'mine');
        $component->call('switchTab', 'log')->assertSet('activeTab', 'log');
        $this->assertStringContainsString($presenter->formatId($mine->toArray()), $component->html());

        $component->call('setListFilter', 'actionable')->assertSet('listFilter', 'actionable');
        $html = $component->html();
        $this->assertStringContainsString($presenter->formatId($actionable->toArray()), $html, 'the actionable filter must show the same formatted ticket id as the mine filter, not just a bare date');
        $this->assertStringNotContainsString($presenter->formatId($mine->toArray()), $html);
    }

    public function test_ticket_table_only_renders_when_log_tab_is_active()
    {
        $requester = $this->deptUser('PS', 'expert');
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'assigned_to' => null,
        ]);
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();
        $formattedId = $presenter->formatId($ticket->toArray());

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class);
        $component->assertSet('activeTab', 'new');
        $component->assertDontSee($formattedId);

        $component->call('switchTab', 'log')->assertSet('activeTab', 'log');
        $component->assertSee($formattedId);
    }

    public function test_single_toggle_button_flips_between_mine_and_actionable()
    {
        $head = $this->departmentHead();

        $component = Livewire::actingAs($head)->test(Main::class);
        $component->assertSet('listFilter', 'mine');

        $component->call('toggleActionableFilter')->assertSet('listFilter', 'actionable');
        $component->call('toggleActionableFilter')->assertSet('listFilter', 'mine');
    }

    public function test_inactive_user_is_never_treated_as_an_actionable_department_head()
    {
        $code = 'T' . Str::upper(Str::random(3));
        Department::create(['code' => $code, 'name' => $code, 'description' => $code]);
        $inactiveHead = User::factory()->create(['status' => 'inactive', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $inactiveHead->id, 'department_id' => $code, 'position' => 'employee']);

        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $code],
        ]);

        $this->assertFalse(
            Ticket::query()->actionableBy($inactiveHead)->whereKey($ticket->id)->exists(),
            'an inactive user must never be treated as their department\'s actionable head, matching highestRankingInDepartment\'s active-only filter',
        );
        $this->assertFalse(Ticket::hasUnclosedActionFor($inactiveHead->id));
    }

    public function test_view_ticket_uses_policy_so_department_head_can_open_unassigned_ticket()
    {
        $head = $this->departmentHead();
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        Livewire::actingAs($head)
            ->test(Main::class)
            ->call('viewTicket', $ticket->id)
            ->assertSet('selectedTicket.id', $ticket->id);
    }

    public function test_modal_renders_a_ticket_with_no_request_area_without_crashing()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'request_area' => null]);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->call('viewTicket', $ticket->id)
            ->call('switchTab', 'request')
            ->assertSuccessful();
    }

    public function test_view_ticket_captures_the_target_departments_code_so_the_modal_can_show_its_custom_area_label(): void
    {
        $department = Department::create([
            'code' => 'ZZTHSMODAL',
            'name' => 'آزمون مودال',
            'description' => 'آزمون مودال',
            'ticket_options' => [
                ['request_type' => 'support', 'area_key' => 'custom_area', 'area_label' => 'ناحیهٔ سفارشی مودال', 'icon' => 'science'],
            ],
        ]);
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'request_type' => 'support',
            'request_area' => 'custom_area',
            'extra' => ['target_department' => $department->code],
        ]);

        $component = Livewire::actingAs($requester)->test(Main::class)->call('viewTicket', $ticket->id);

        $component->assertSet('selectedTicket.target_department_code', 'ZZTHSMODAL');
        $component->call('switchTab', 'request')->assertSee('ناحیهٔ سفارشی مودال');
    }

    public function test_list_row_and_modal_show_type_label_and_area_label_for_custom_and_legacy_tickets(): void
    {
        $department = Department::create([
            'code' => 'ZZTHSROW',
            'name' => 'آزمون ردیف',
            'description' => 'آزمون ردیف',
            'ticket_options' => [
                ['request_type' => 'zz_rowtype', 'area_key' => 'row_area', 'area_label' => 'ناحیهٔ ردیف آزمون'],
            ],
        ]);
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $custom = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'request_type' => 'zz_rowtype',
            'request_area' => 'row_area',
            'extra' => ['target_department' => $department->code],
        ]);
        $legacy = Ticket::factory()->create(['requester_id' => $requester->id, 'request_type' => 'access', 'request_area' => 'email']);
        DB::table('tickets')->where('id', $legacy->id)->update(['extra' => null]);

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class)->call('switchTab', 'log');
        $html = $component->html();
        $this->assertStringContainsString('ناحیهٔ ردیف آزمون', $html);
        $this->assertStringContainsString('zz_rowtype', $html);
        $this->assertStringContainsString(Ticket::$requestAreaOptions['access']['email'], $html);
        $this->assertStringContainsString(Ticket::$requestTypeOptions['access'], $html);

        $component->call('viewTicket', $custom->id)->call('switchTab', 'request')
            ->assertSee('ناحیهٔ ردیف آزمون')
            ->assertSee('zz_rowtype');

        Livewire::actingAs($requester)->test(Main::class)->call('viewTicket', $legacy->id)->call('switchTab', 'request')
            ->assertSee(Ticket::$requestAreaOptions['access']['email'])
            ->assertSee(Ticket::$requestTypeOptions['access']);
    }

    public function test_focus_record_ignores_unrelated_users()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $unrelated = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);

        Livewire::actingAs($unrelated)
            ->test(Main::class)
            ->call('focusRecord', $ticket->id)
            ->assertSet('selectedTicket', null);
    }

    public function test_focus_record_deep_link_opens_directly_on_the_reply_tab()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->call('focusRecord', $ticket->id)
            ->assertSet('modalTab', 'response');
    }

    public function test_effectiveness_is_final_once_the_ticket_is_closed()
    {
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['status' => 'closed', 'assigned_to' => $assignee->id, 'effectiveness' => '3']);

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->call('setEffectiveness', '5');

        $fresh = $ticket->fresh();
        $this->assertSame('3', $fresh->effectiveness, 'a closed ticket is final: nobody revises the recorded effectiveness');
        $this->assertSame('closed', $fresh->status);
    }

    public function test_reply_nudge_notifies_the_other_participant_not_the_author()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id]);

        $ticket->addReply($assignee, 'من در حال بررسی هستم.');
        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);

        $requesterNotif = $requester->fresh()->notifications()->latest()->first();
        $this->assertNotNull($requesterNotif, 'requester must be notified of the assignee reply');
        $this->assertStringContainsString('پاسخ جدید', $requesterNotif->data['title']);

        // the assignee is BOTH the reply author AND the in-progress lifecycle recipient here;
        // the lifecycle-recipient path is a pre-existing, unrelated entitlement (e.g. a raw
        // Ticket update would still legitimately notify them) -- what must NOT happen is a
        // reply-driven duplicate/refresh purely because they authored it.
        $assigneeNotif = $assignee->fresh()->notifications()->latest()->first();
        if ($assigneeNotif) {
            $this->assertStringNotContainsString('پاسخ جدید', $assigneeNotif->data['title'], 'the reply author must never receive reply-copy about their own message');
        }

        $requester->notifications()->delete();
        $assignee->notifications()->delete();

        $ticket->addReply($requester, 'سوال دیگری دارم.');
        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);

        $assigneeNotif2 = $assignee->fresh()->notifications()->latest()->first();
        $this->assertNotNull($assigneeNotif2, 'assignee must be notified when the requester replies back');

        $requesterSelfNotif = $requester->fresh()->notifications()->latest()->first();
        $this->assertNull($requesterSelfNotif, 'requester (reply author, and not the in-progress lifecycle recipient) must not self-notify');
    }

    public function test_third_party_department_head_reply_notifies_both_requester_and_assignee()
    {
        $head = $this->departmentHead();
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $ticket->addReply($head, 'من هم این تیکت را بررسی می‌کنم.');
        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);

        $this->assertNotNull($assignee->fresh()->notifications()->latest()->first(), 'assignee must be notified even when a third party (not requester/assignee) replies');
        $this->assertNotNull($requester->fresh()->notifications()->latest()->first(), 'requester must be notified even when a third party (not requester/assignee) replies');
        $this->assertNull($head->fresh()->notifications()->latest()->first(), 'the third-party reply author must not self-notify');
    }

    public function test_reply_notification_lives_under_its_own_reply_key_keyed_on_the_reply_id()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id]);

        $reply = $ticket->addReply($assignee, 'در حال بررسی.');
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $reply->id);

        $this->assertSame(2, DB::table('notifications')->where('data->menu_key', 'ths-controller:reply-nudge')->where('data->item_id', (string) $reply->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('data->menu_key', 'ths-controller:reply-nudge')->where('data->item_id', (string) $ticket->id)->whereIn('notifiable_id', [$requester->id, $assignee->id])->count());
    }

    public function test_requester_cannot_reply_once_ticket_closed()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'closed',
            'assigned_to' => $assignee->id,
            'effectiveness' => '5',
        ]);

        Livewire::actingAs($requester)
            ->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', 'یک سوال دیگر دارم')
            ->call('postReply')
            ->assertDispatched('toast', message: 'دسترسی لازم برای این عملیات وجود ندارد.', type: 'error');

        $this->assertSame(0, $ticket->fresh()->replies()->count());
    }

    public function test_policy_requester_can_always_view()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $requester));
    }

    public function test_policy_assignee_can_always_view()
    {
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['status' => 'in-progress', 'assigned_to' => $assignee->id]);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $assignee));
    }

    public function test_policy_unrelated_user_cannot_view_open_ticket_outside_their_department()
    {
        $head = $this->departmentHead();
        $unrelated = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $this->assertFalse(TicketAccessPolicy::canView($ticket, $unrelated));
    }

    public function test_policy_non_head_cannot_assign()
    {
        $head = $this->departmentHead();
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $requester));
    }

    public function test_policy_elevated_role_cannot_self_assign_or_self_set_effectiveness_on_own_ticket_but_still_can_on_others(): void
    {
        $head = $this->departmentHead();
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);

        $ownTicket = Ticket::factory()->create([
            'requester_id' => $admin->id,
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);
        $othersTicket = Ticket::factory()->create([
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        // real bug, fixed 2026-10-05: an admin/developer requester could delegate/assign their OWN ticket
        // to staff, bypassing the department-head-only rule, because the elevated-role bypass didn't
        // exclude the case where the elevated user is themselves the requester.
        $this->assertFalse(TicketAccessPolicy::canAssign($ownTicket, $admin), 'an elevated-role requester must not self-assign their own ticket');
        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($ownTicket, $admin), 'an elevated-role requester must not self-set-effectiveness on their own ticket');

        // the elevated bypass must still work normally for a ticket the admin did NOT request
        $this->assertTrue(TicketAccessPolicy::canAssign($othersTicket, $admin), 'an elevated role must still be able to manage tickets it did not request');
        $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($othersTicket, $admin));
    }

    public function test_policy_reply_forbidden_once_closed()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'closed',
            'assigned_to' => $assignee->id,
            'effectiveness' => '5',
        ]);

        $this->assertFalse(TicketAccessPolicy::canReply($ticket, $requester));
        $this->assertFalse(TicketAccessPolicy::canReply($ticket, $assignee));
        $this->assertTrue(TicketAccessPolicy::canView($ticket, $requester), 'closed tickets stay viewable (read-only history)');
    }

    public function test_policy_effectiveness_settable_by_assignee_or_department_head_only()
    {
        $head = $this->departmentHead();
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($ticket, $assignee));
        $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($ticket, $head));
        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($ticket, $requester));
    }

    public function test_policy_requester_assigned_to_their_own_ticket_can_still_set_effectiveness()
    {
        $head = $this->departmentHead();
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $requester->id,
            'extra' => ['target_department' => $head->profile->department_id],
        ]);

        $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($ticket, $requester), 'a requester who was legitimately assigned their own ticket must still act as its assignee');
    }

    public function test_policy_can_close_mirrors_can_set_effectiveness()
    {
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
        ]);

        $this->assertTrue(TicketAccessPolicy::canClose($ticket, $assignee));
        $this->assertFalse(TicketAccessPolicy::canClose($ticket, $requester));
    }

    public function test_policy_null_user_never_has_access()
    {
        $ticket = Ticket::factory()->create(['status' => 'open', 'assigned_to' => null]);

        $this->assertFalse(TicketAccessPolicy::canView($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canReply($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($ticket, null));
        $this->assertFalse(TicketAccessPolicy::canClose($ticket, null));
    }

    public function test_submit_ticket_stores_mime_guessed_extension_not_raw_client_extension()
    {
        Storage::fake('public');

        $requester = $this->deptUser('PS', 'expert');
        $key = Str::random(13);
        $file = UploadedFile::fake()->image('photo.png', 20, 20)->mimeType('image/gif');

        $this->assertSame('png', $file->getClientOriginalExtension());
        $this->assertSame('gif', $file->extension());

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->set('ticket.requestType', 'support')
            ->set('ticket.priority', 'low')
            ->set('ticket.subject', 'Mime-ext probe')
            ->set('ticket.description', 'Submitting a ticket whose client filename extension differs from its mime.')
            ->set('ticket.requestArea', 'vpn')
            ->set('ticket.files.' . $key, $file)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $ticket = Ticket::where('requester_id', $requester->id)
            ->where('request_subject', 'Mime-ext probe')
            ->firstOrFail();

        $path = $ticket->requester_files[0]['file'];
        $this->assertStringEndsNotWith('.' . $file->getClientOriginalExtension(), $path, 'stored attachment must not keep the raw attacker-controlled client extension');
        $this->assertStringEndsWith('.' . $file->extension(), $path, 'stored attachment must use the mime-guessed extension');
        Storage::disk('public')->assertExists($path);
    }

    public function test_priority_meta_maps_low_medium_high_to_primary_secondary_error_and_unknown_to_null()
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $low = $presenter->priorityMeta('low');
        $this->assertSame('text-[var(--md-sys-color-primary)]', $low['color']);
        $this->assertSame('low_priority', $low['icon']);
        $this->assertSame('عادی', $low['title']);
        $this->assertStringContainsString('primary-container', $low['bg']);

        $medium = $presenter->priorityMeta('medium');
        $this->assertSame('text-[var(--md-sys-color-secondary)]', $medium['color']);
        $this->assertSame('drag_handle', $medium['icon']);
        $this->assertSame('فوری', $medium['title']);
        $this->assertStringContainsString('secondary-container', $medium['bg']);

        $high = $presenter->priorityMeta('high');
        $this->assertSame('text-[var(--md-sys-color-error)]', $high['color']);
        $this->assertSame('priority_high', $high['icon']);
        $this->assertSame('خیلی فوری', $high['title']);
        $this->assertStringContainsString('error-container', $high['bg']);

        $this->assertNull($presenter->priorityMeta('urgent'));
        $this->assertNull($presenter->priorityMeta(''));
    }

    public function test_status_meta_maps_open_in_progress_closed_and_unknown_to_null()
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $open = $presenter->statusMeta('open');
        $this->assertSame('pending', $open['icon']);
        $this->assertSame('text-[var(--md-sys-color-primary)]', $open['textColor']);
        $this->assertSame('bg-[var(--md-sys-color-primary-container)]', $open['bg']);
        $this->assertSame('باز', $open['title']);
        $this->assertTrue($open['pulse']);
        $this->assertFalse($open['spin']);

        $progress = $presenter->statusMeta('in-progress');
        $this->assertSame('sync', $progress['icon']);
        $this->assertSame('text-[var(--md-sys-color-tertiary)]', $progress['textColor']);
        $this->assertSame('bg-[var(--md-sys-color-tertiary-container)]', $progress['bg']);
        $this->assertSame('در حال بررسی', $progress['title']);
        $this->assertFalse($progress['pulse']);
        $this->assertTrue($progress['spin']);

        $closed = $presenter->statusMeta('closed');
        $this->assertSame('check_circle', $closed['icon']);
        $this->assertSame('text-[var(--md-sys-color-secondary)]', $closed['textColor']);
        $this->assertSame('bg-[var(--md-sys-color-secondary-container)]', $closed['bg']);
        $this->assertSame('بسته شده', $closed['title']);
        $this->assertFalse($closed['pulse']);
        $this->assertFalse($closed['spin']);

        $this->assertNull($presenter->statusMeta('reopened'));
        $this->assertNull($presenter->statusMeta(''));
    }

    public function test_request_area_label_and_icon_resolve_the_departments_custom_option_not_just_the_generic_fallback(): void
    {
        Department::create([
            'code' => 'ZZTHSAREA',
            'name' => 'آزمون ناحیه',
            'description' => 'آزمون ناحیه',
            'ticket_options' => [
                ['request_type' => 'support', 'area_key' => 'custom_area', 'area_label' => 'ناحیهٔ سفارشی آزمون', 'icon' => 'heroicon-o-wrench-screwdriver'],
            ],
        ]);

        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $this->assertSame('ناحیهٔ سفارشی آزمون', $presenter->requestAreaLabel('support', 'custom_area'), 'without department context a custom-only key falls back to any department label');
        $this->assertSame('orphan_zz_key', $presenter->requestAreaLabel('support', 'orphan_zz_key'));
        $this->assertSame('—', $presenter->requestAreaLabel('support', null));

        $this->assertSame('ناحیهٔ سفارشی آزمون', $presenter->requestAreaLabel('support', 'custom_area', 'ZZTHSAREA'));
        $this->assertSame('build', $presenter->requestAreaIcon('custom_area', 'ZZTHSAREA'), 'icon stored as a ResourceTypeIcon enum value, resolved to its Material Symbols glyph');
    }

    public function test_deadline_chip_is_null_without_a_deadline_or_once_closed_with_no_completion_date()
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $this->assertNull($presenter->deadlineChip(null, null, 'open'));
        $this->assertNull($presenter->deadlineChip(now()->addDay(), null, 'closed'));
    }

    public function test_deadline_chip_flags_overdue_vs_on_time_for_an_open_ticket()
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $overdue = $presenter->deadlineChip(now()->subDay(), null, 'open');
        $this->assertSame('event_busy', $overdue['icon']);
        $this->assertStringContainsString('سررسید گذشته', $overdue['text']);
        $this->assertStringContainsString('error', $overdue['classes']);

        $onTime = $presenter->deadlineChip(now()->addWeek(), null, 'in-progress');
        $this->assertSame('schedule', $onTime['icon']);
        $this->assertStringContainsString('تا سررسید', $onTime['text']);
        $this->assertStringContainsString('success', $onTime['classes']);
    }

    public function test_deadline_chip_reports_final_late_or_early_verdict_once_completed()
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();
        $deadline = now()->subDays(2);

        $late = $presenter->deadlineChip($deadline, now(), 'closed');
        $this->assertSame('event_busy', $late['icon']);
        $this->assertStringContainsString('با تأخیر', $late['text']);

        $early = $presenter->deadlineChip(now()->addDays(2), now(), 'closed');
        $this->assertSame('check_circle', $early['icon']);
        $this->assertStringContainsString('پیش از موعد', $early['text']);
    }

    public function test_status_steps_marks_done_active_and_upcoming_relative_to_current_status()
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $steps = $presenter->statusSteps('in-progress');

        $this->assertCount(3, $steps);
        $this->assertSame('done', $steps[0]['state']);
        $this->assertSame('active', $steps[1]['state']);
        $this->assertSame('upcoming', $steps[2]['state']);

        $openSteps = $presenter->statusSteps('open');
        $this->assertSame('active', $openSteps[0]['state']);
        $this->assertSame('upcoming', $openSteps[1]['state']);
        $this->assertSame('upcoming', $openSteps[2]['state']);

        $closedSteps = $presenter->statusSteps('closed');
        $this->assertSame('done', $closedSteps[0]['state']);
        $this->assertSame('done', $closedSteps[1]['state']);
        $this->assertSame('active', $closedSteps[2]['state']);
    }

    public function test_ticket_table_shows_reply_count_and_attachment_badge_only_when_present()
    {
        $requester = $this->deptUser('PS', 'expert');
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $bare = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'open',
            'assigned_to' => null,
        ]);
        $active = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'requester_files' => [['file' => 'ths/proof.png']],
        ]);

        Livewire::actingAs($assignee)
            ->test(Workspace::class, ['ticketId' => $active->id])
            ->set('replyForm.body', 'در حال بررسی هستم.')
            ->call('postReply')
            ->assertHasNoErrors();

        $component = Livewire::actingAs($requester)->test(Main::class)->call('switchTab', 'log');

        $component->assertSeeHtml('title="تعداد پاسخ‌ها"');
        $component->assertSeeHtml('title="فایل پیوست دارد"');

        $rows = $component->instance()->tickets()->getCollection();
        $bareRow = $rows->firstWhere('id', $bare->id);
        $activeRow = $rows->firstWhere('id', $active->id);
        $this->assertSame(0, $bareRow->replies_count);
        $this->assertSame(1, $activeRow->replies_count);
    }

    private function jalaliParts(\Carbon\CarbonInterface $date): array
    {
        $j = \Morilog\Jalali\Jalalian::fromCarbon($date);

        return [$j->getYear(), $j->getMonth(), $j->getDay()];
    }

    private function stampTicket(Ticket $ticket, string $created, string $updated): Ticket
    {
        $ticket->forceFill(['created_at' => $created, 'updated_at' => $updated])->saveQuietly();

        return $ticket;
    }

    private function spanIds($component): array
    {
        return $component->instance()->tickets()->getCollection()->pluck('id')->all();
    }

    public function test_date_span_filter_narrows_tickets_by_updated_at()
    {
        $requester = $this->deptUser('PS', 'expert');

        $old = $this->stampTicket(Ticket::factory()->create(['requester_id' => $requester->id]), now()->subDays(10)->format('Y-m-d H:i:s'), now()->subDays(10)->format('Y-m-d H:i:s'));
        $new = Ticket::factory()->create(['requester_id' => $requester->id]);

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(6));
        [$ty, $tm, $td] = $this->jalaliParts(now());

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan')
            ->assertHasNoErrors();

        $ids = $this->spanIds($component);

        $this->assertContains($new->id, $ids);
        $this->assertNotContains($old->id, $ids);
    }

    public function test_date_span_basis_created_filters_on_created_at_instead()
    {
        $requester = $this->deptUser('PS', 'expert');

        $ticket = $this->stampTicket(Ticket::factory()->create(['requester_id' => $requester->id]), now()->subDays(10)->format('Y-m-d H:i:s'), now()->format('Y-m-d H:i:s'));

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(6));
        [$ty, $tm, $td] = $this->jalaliParts(now());

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan');

        $this->assertContains($ticket->id, $this->spanIds($component));

        $component->call('setDateBasis', 'created');

        $this->assertNotContains($ticket->id, $this->spanIds($component));
    }

    public function test_partial_and_invalid_date_spans_are_ignored()
    {
        $requester = $this->deptUser('PS', 'expert');

        $old = $this->stampTicket(Ticket::factory()->create(['requester_id' => $requester->id]), now()->subDays(10)->format('Y-m-d H:i:s'), now()->subDays(10)->format('Y-m-d H:i:s'));

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(6));

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->call('applyDateSpan')
            ->assertDispatched('toast');

        $this->assertContains($old->id, $this->spanIds($component));

        $component->call('clearDateSpan')
            ->set('fromYear', $fy)->set('fromMonth', 7)->set('fromDay', 31)
            ->set('toYear', $fy)->set('toMonth', 12)->set('toDay', 29)
            ->call('applyDateSpan')
            ->assertDispatched('toast');

        $this->assertContains($old->id, $this->spanIds($component));
    }

    public function test_clear_date_span_restores_all_tickets()
    {
        $requester = $this->deptUser('PS', 'expert');

        $old = $this->stampTicket(Ticket::factory()->create(['requester_id' => $requester->id]), now()->subDays(10)->format('Y-m-d H:i:s'), now()->subDays(10)->format('Y-m-d H:i:s'));

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(6));
        [$ty, $tm, $td] = $this->jalaliParts(now());

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan');

        $this->assertNotContains($old->id, $this->spanIds($component));

        $ids = $this->spanIds($component->call('clearDateSpan'));

        $this->assertContains($old->id, $ids);
    }

    public function test_load_more_restores_applied_date_parts_instead_of_serving_unapplied_edits()
    {
        $requester = $this->deptUser('PS', 'expert');

        $old = $this->stampTicket(Ticket::factory()->create(['requester_id' => $requester->id]), now()->subDays(10)->format('Y-m-d H:i:s'), now()->subDays(10)->format('Y-m-d H:i:s'));
        Ticket::factory()->create(['requester_id' => $requester->id]);

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(6));
        [$ty, $tm, $td] = $this->jalaliParts(now());
        [$ty2, $tm2, $td2] = $this->jalaliParts(now()->subDays(8));

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan');

        $this->assertNotContains($old->id, $this->spanIds($component));

        $component->set('toYear', $ty2)->set('toMonth', $tm2)->set('toDay', $td2)
            ->call('loadMore')
            ->assertSet('toDay', $td)
            ->assertSet('perPage', 10);

        $this->assertNotContains($old->id, $this->spanIds($component));
    }

    public function test_mine_queue_renders_unified_toolbar_with_date_span_and_floating_search()
    {
        $requester = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->set('activeTab', 'log')
            ->assertSee('فیلتر بازه تاریخ')
            ->assertSee('حذف فیلتر')
            ->assertSeeHtml("setDateBasis('created')")
            ->assertSeeHtml('wire:key="ths-actions-toolbar"')
            ->assertSeeHtml('toggleHeaderDeclutter()')
            ->assertSeeHtml('wire:model.live.debounce.500ms="ticketSearch"')
            ->assertSeeHtml('data-declutter-hide')
            ->assertDontSeeHtml('wire:key="ths-search-row"')
            ->assertDontSeeHtml('toggleSearch')
            ->call('setListFilter', 'actionable')
            ->assertSeeHtml('wire:key="ths-actions-toolbar"')
            ->assertSeeHtml('toggleMaximize()')
            ->assertSee("'max-widget': max || maxLeaving", false)
            ->assertDontSee('فیلتر بازه تاریخ')
            ->assertDontSeeHtml('wire:key="ths-date-span"');
    }

    public function test_mine_queue_sort_orders_by_whitelisted_column_and_resets()
    {
        $requester = $this->deptUser('PS', 'expert');
        $first = Ticket::factory()->create(['requester_id' => $requester->id, 'request_subject' => 'Alpha', 'created_at' => now()->subDay()]);
        $second = Ticket::factory()->create(['requester_id' => $requester->id, 'request_subject' => 'Zulu', 'created_at' => now()]);

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)
            ->test(Main::class)
            ->set('activeTab', 'log');

        $this->assertSame([$second->id, $first->id], $component->instance()->tickets()->pluck('id')->all());

        $component->call('sortBy', 'subject');
        $this->assertSame([$first->id, $second->id], $component->instance()->tickets()->pluck('id')->all());

        $component->call('sortBy', 'subject');
        $this->assertSame([$second->id, $first->id], $component->instance()->tickets()->pluck('id')->all());

        $high = Ticket::factory()->create(['requester_id' => $requester->id, 'priority' => 'high']);
        $low = Ticket::factory()->create(['requester_id' => $requester->id, 'priority' => 'low']);
        $component->call('sortBy', 'priority');
        $ids = $component->instance()->tickets()->pluck('id')->all();
        $this->assertLessThan(array_search($high->id, $ids), array_search($low->id, $ids));

        $component->call('sortBy', 'password')->assertSet('sort', 'priority');

        $component->call('resetSort')->assertSet('sort', 'created')->assertSet('sortDir', 'desc');
    }

    public function test_empty_date_span_shows_actionable_clear_filter_state()
    {
        $requester = $this->deptUser('PS', 'expert');

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(6));
        [$ty, $tm, $td] = $this->jalaliParts(now());

        Livewire::withoutLazyLoading();

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->set('activeTab', 'log')
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan')
            ->assertSee('تیکتی در این بازه تاریخ یافت نشد')
            ->assertSee('حذف فیلتر تاریخ')
            ->assertSeeHtml('wire:key="ths-empty-datespan"');
    }

    public function test_search_matches_the_displayed_formatted_id_and_not_the_legacy_six_digit_padded_form()
    {
        $requester = $this->deptUser('PS', 'expert');
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();
        $formattedId = $presenter->formatId($ticket->toArray());

        $component = Livewire::actingAs($requester)->test(Main::class);

        $component->set('ticketSearch', $formattedId);
        $this->assertContains($ticket->id, $this->spanIds($component), 'the search must find the ticket by the exact id the UI displays');

        $component->set('ticketSearch', 'TN-' . now()->format('ym') . '-' . str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT));
        $this->assertSame([], $this->spanIds($component), 'the legacy TN- six-padded form exists nowhere in the UI and must match nothing');
    }

    public function test_search_with_a_literal_percent_character_does_not_match_everything()
    {
        $requester = $this->deptUser('PS', 'expert');
        Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);

        $component = Livewire::actingAs($requester)->test(Main::class);
        $component->set('ticketSearch', '%');

        $this->assertSame([], $this->spanIds($component), 'a lone % must be treated as a literal character, not a wildcard');
    }

    public function test_deep_linking_an_accessible_ticket_opens_the_modal_instead_of_dispatching_record_focus()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['open' => $ticket->id]);

        Livewire::actingAs($requester)
            ->test(Main::class)
            ->assertNotDispatched('record-focus')
            ->assertSet('selectedTicket.id', $ticket->id);
    }

    public function test_submit_rating_requeries_the_next_unrated_closed_ticket_so_the_rate_tab_stays_alive()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $first = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'closed', 'satisfaction_score' => null, 'effectiveness' => '4']);
        $second = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'closed', 'satisfaction_score' => null, 'effectiveness' => '4']);

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class);
        $component->assertSet('activeTab', 'rate')
            ->call('rate', 4)
            ->call('submitRating')
            ->assertHasNoErrors();

        $this->assertSame(4.0, (float) $first->fresh()->satisfaction_score);
        $this->assertSame($second->id, $component->instance()->ticketToRate->id, 'the rate tab must stay alive with the next unrated closed ticket instead of vanishing');
    }

    public function test_the_rating_form_is_reset_between_tickets_so_one_tickets_score_and_comment_never_leak_into_the_next()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $first = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'closed', 'satisfaction_score' => null, 'effectiveness' => '4']);
        $second = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'closed', 'satisfaction_score' => null, 'effectiveness' => '4']);

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class);
        $component->call('rate', 2)
            ->set('rating.comment', 'کند بود')
            ->call('submitRating')
            ->call('rate', 5)
            ->call('submitRating');

        $this->assertSame(5.0, (float) $second->fresh()->satisfaction_score, 'the second ticket must get its own score, not the first ticket\'s leaked one');
        $this->assertNull($second->fresh()->extra['satisfaction_comment'] ?? null, 'the first ticket\'s comment must not leak into the second ticket');
    }

    public function test_a_bogus_tab_query_param_falls_back_to_new_and_switch_tab_ignores_unknown_tabs()
    {
        $requester = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();
        Livewire::withQueryParams(['tab' => 'garbage']);

        $component = Livewire::actingAs($requester)->test(Main::class);
        $component->assertSet('activeTab', 'new');
        $component->call('switchTab', 'garbage')->assertSet('activeTab', 'new');
    }

    public function test_the_ths_ticket_updated_event_refreshes_the_open_modal_snapshot_and_keeps_the_response_tab()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);

        $component = Livewire::actingAs($requester)->test(Main::class);
        $component->call('viewTicket', $ticket->id)
            ->call('switchTab', 'response')
            ->assertSet('modalTab', 'response');

        $ticket->update(['status' => 'in-progress']);

        $component->dispatch('ths-ticket-updated');

        $this->assertSame('in-progress', $component->instance()->selectedTicket['status'], 'the open modal snapshot must reflect the DB change after the event');
        $this->assertSame('response', $component->instance()->modalTab, 'refreshing the snapshot must not yank the user off the response tab');
    }

    public function test_view_ticket_on_an_inaccessible_ticket_toasts_and_nulls_the_selected_ticket()
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $unrelated = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);

        Livewire::actingAs($unrelated)
            ->test(Main::class)
            ->call('viewTicket', $ticket->id)
            ->assertDispatched('toast', message: 'تیکت موردنظر در دسترس نیست.', type: 'error')
            ->assertSet('selectedTicket', null);
    }

    private function staffIn(string $code, string $position = 'employee'): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $code, 'position' => $position]);

        return $user;
    }

    private function openTicketFor(string $code, ?User $requester = null): Ticket
    {
        $ticket = Ticket::factory()->create([
            'requester_id' => ($requester ?? User::factory()->create(['status' => 'active', 'role' => 'user']))->id,
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $code],
        ]);

        return Ticket::find($ticket->id);
    }

    public function test_every_tied_top_rank_user_is_a_head_and_can_assign(): void
    {
        $headA = $this->departmentHead();
        $code = $headA->profile->department_id;
        $headB = $this->staffIn($code);
        $junior = $this->staffIn($code, 'intern');
        $ticket = $this->openTicketFor($code);

        $heads = User::headsOfDepartment($code)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$headA->id, $headB->id], $heads);
        $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $headA));
        $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $headB));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $junior));
    }

    public function test_first_head_to_assign_wins_and_the_second_sees_the_already_assigned_message(): void
    {
        $headA = $this->departmentHead();
        $code = $headA->profile->department_id;
        $headB = $this->staffIn($code);
        $first = $this->staffIn($code, 'intern');
        $second = $this->staffIn($code, 'intern');
        $ticket = $this->openTicketFor($code);

        $late = Livewire::actingAs($headB)->test(Workspace::class, ['ticketId' => $ticket->id]);
        Livewire::actingAs($headA)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $first->id)->call('assign');

        $late->set('assigneeId', (string) $second->id)->call('assign')
            ->assertDispatched('toast', message: 'این تیکت قبلاً محول شده است.', type: 'error');

        $this->assertSame($first->id, Ticket::find($ticket->id)->assigned_to);
    }

    public function test_requester_can_withdraw_their_ticket_which_closes_it_and_finishes_the_mirror_task(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $staff = $this->staffIn($code, 'intern');
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->openTicketFor($code, $requester);
        Ticket::find($ticket->id)->update(['assigned_to' => $staff->id]);

        Livewire::actingAs($staff)->test(Workspace::class, ['ticketId' => $ticket->id])->call('withdrawTicket');
        $this->assertSame('in-progress', Ticket::find($ticket->id)->status);

        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->call('withdrawTicket')->assertDispatched('toast', message: 'درخواست شما لغو شد.', type: 'success');

        $fresh = Ticket::find($ticket->id);
        $this->assertSame('closed', $fresh->status);
        $this->assertSame(Ticket::WITHDRAWN_RESULT, $fresh->action_result);
        $this->assertNotNull($fresh->completion_date);
        $this->assertSame('done', Task::where('ticket_id', $ticket->id)->value('status'));
    }

    public function test_assignee_release_returns_the_ticket_to_the_queue_and_drops_the_mirror_task(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $staff = $this->staffIn($code, 'intern');
        $ticket = $this->openTicketFor($code);
        Ticket::find($ticket->id)->update(['assigned_to' => $staff->id]);
        $this->assertNotNull(Task::where('ticket_id', $ticket->id)->first());

        Livewire::actingAs($staff)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->call('releaseTicket')
            ->assertDispatched('toast', type: 'error');
        $this->assertSame($staff->id, Ticket::find($ticket->id)->assigned_to);

        Livewire::actingAs($staff)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', 'cannot handle this')
            ->call('releaseTicket')
            ->assertDispatched('toast', message: 'تیکت به صف بازگردانده شد.', type: 'success');

        $fresh = Ticket::find($ticket->id);
        $this->assertNull($fresh->assigned_to);
        $this->assertSame('open', $fresh->status);
        $this->assertNull(Task::where('ticket_id', $ticket->id)->first());
        $this->assertStringContainsString('cannot handle this', $fresh->replies()->latest('id')->value('body'));
    }

    public function test_head_reroutes_the_ticket_to_another_department_and_reopens_it(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $targetHead = $this->departmentHead();
        $targetCode = $targetHead->profile->department_id;
        $staff = $this->staffIn($code, 'intern');
        $ticket = $this->openTicketFor($code);
        Ticket::find($ticket->id)->update(['assigned_to' => $staff->id]);

        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('rerouteTo', $targetCode)
            ->set('replyForm.body', 'wrong team')
            ->call('rerouteTicket')
            ->assertDispatched('toast', message: 'تیکت ارجاع داده شد.', type: 'success');

        $fresh = Ticket::find($ticket->id);
        $this->assertSame($targetCode, $fresh->targetDepartmentId);
        $this->assertSame('open', $fresh->status);
        $this->assertNull($fresh->assigned_to);
        $this->assertNull(Task::where('ticket_id', $ticket->id)->first());

        $same = $this->openTicketFor($code);
        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $same->id])
            ->set('rerouteTo', $code)->set('replyForm.body', 'again')->call('rerouteTicket')
            ->assertDispatched('toast', type: 'error');
        $this->assertSame(0, $same->replies()->count());
    }

    private function closedHistory(string $dept, int $count): void
    {
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);

        foreach (range(1, $count) as $i) {
            $ticket = Ticket::factory()->create([
                'requester_id' => $requester->id,
                'request_type' => 'support',
                'request_area' => 'vpn',
                'status' => 'open',
                'assigned_to' => null,
                'extra' => ['target_department' => $dept],
            ]);
            $created = now()->subDays(10);
            DB::table('tickets')->where('id', $ticket->id)->update([
                'status' => 'closed',
                'created_at' => $created,
                'completion_date' => $created->copy()->addDays(2),
            ]);
        }

        Cache::flush();
    }

    public function test_create_form_shows_the_typical_duration_after_an_area_is_picked_and_hides_it_when_history_is_thin(): void
    {
        app()->setLocale('fa');
        $requester = $this->deptUser('PS', 'expert');
        $rich = 'T' . Str::upper(Str::random(4));
        $thin = 'T' . Str::upper(Str::random(4));
        $this->closedHistory($rich, 5);
        $this->closedHistory($thin, 4);
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->set('ticket.targetDepartment', $rich)
            ->set('ticket.requestType', 'support')
            ->assertDontSee('معمولاً حدود ۲ روز');

        $component->set('ticket.requestArea', 'vpn')->assertSee('معمولاً حدود ۲ روز');

        $component->set('ticket.targetDepartment', $thin)
            ->set('ticket.requestType', 'support')
            ->set('ticket.requestArea', 'vpn')
            ->assertDontSee('معمولاً حدود ۲ روز');
    }

    public function test_modal_strip_shows_age_assignee_response_and_typical_time_and_omits_a_deleted_assignee(): void
    {
        app()->setLocale('fa');
        $dept = 'T' . Str::upper(Str::random(4));
        $this->closedHistory($dept, 5);
        $staff = $this->deptUser('PS', 'expert');
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'request_type' => 'support',
            'request_area' => 'vpn',
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $dept],
        ]);
        DB::table('tickets')->where('id', $ticket->id)->update(['status' => 'in-progress', 'assigned_to' => $staff->id]);
        Cache::flush();
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->call('viewTicket', $ticket->id)
            ->call('switchTab', 'request')
            ->assertSee('ثبت‌شده')
            ->assertSee('مسئول: ' . $staff->name)
            ->assertSee('منتظر اولین پاسخ')
            ->assertSee('معمولاً حدود ۲ روز');

        $ticket->addReply($staff, 'answer');
        $component->call('viewTicket', $ticket->id)->assertSee('پاسخ پس از');

        DB::table('tickets')->where('id', $ticket->id)->update(['assigned_to' => 999999999]);
        $component->call('viewTicket', $ticket->id)->assertDontSee('محول‌شده به ' . $staff->name);
    }

    public function test_ths_dates_follow_the_user_panel_convention(): void
    {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-10-16 12:00:00', config('app.timezone')));
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open']);
        DB::table('tickets')->where('id', $ticket->id)->update([
            'status' => 'closed',
            'assigned_to' => $assignee->id,
            'created_at' => '2026-10-10 08:30:00',
            'completion_deadline' => '2026-10-12 08:30:00',
            'completion_date' => '2026-10-11 09:45:00',
        ]);
        $old = $ticket->addReply($assignee, 'old reply');
        $today = $ticket->addReply($requester, 'today reply');
        DB::table('replies')->where('id', $old->id)->update(['created_at' => '2026-10-10 10:20:00']);
        DB::table('replies')->where('id', $today->id)->update(['created_at' => '2026-10-16 09:15:00']);

        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->assertSee('۱۸ مهر ۱۴۰۵')
            ->assertSee('امروز')
            ->assertSee('۱۴۰۵/۰۷/۱۸ ۱۰:۲۰')
            ->assertSee('۰۹:۱۵')
            ->assertDontSee('شنبه');

        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();
        $row = $ticket->fresh()->toArray();
        $this->assertSame('۱۸ مهر ۱۴۰۵', $presenter->statusSteps('closed', $row)[0]['meta']);
        $this->assertSame('مهلت: ۲۰ مهر ۱۴۰۵', $presenter->statusSteps('in-progress', $row)[1]['meta']);
        $this->assertSame('۱۹ مهر ۱۴۰۵', $presenter->statusSteps('closed', $row)[2]['meta']);
        $this->assertSame('۱۴۰۵/۰۷/۱۸ ۱۱:۰۵', $presenter->replyTime(\Illuminate\Support\Carbon::parse('2026-10-10 11:05:00', config('app.timezone'))));
        $this->assertSame('۰۹:۱۵', $presenter->replyTime(\Illuminate\Support\Carbon::parse('2026-10-16 09:15:00', config('app.timezone'))));
        $this->assertStringContainsString('پیش', $presenter->formatTimestamp(['created_at' => '2026-10-10 08:30:00'], 'created_at'));
        $this->assertSame('۶ مرداد ۱۴۰۵', $presenter->formatTimestamp(['created_at' => '2026-07-28 10:00:00'], 'created_at'));

        \Illuminate\Support\Carbon::setTestNow();
    }

    private function customDepartment(bool $withHead): array
    {
        $code = 'D' . Str::upper(Str::random(4));
        Department::create([
            'code' => $code, 'name' => $code, 'description' => $code,
            'ticket_options' => [['request_type' => 'support', 'area_key' => 'a1', 'area_label' => 'A1']],
        ]);
        $head = $withHead ? $this->staffIn($code) : null;
        Cache::flush();

        return [$code, $head];
    }

    private function threadBodies(Ticket $ticket): array
    {
        return $ticket->replies()->pluck('body')->all();
    }

    private function nudgeRows(User $user, string $key = 'ths-controller:reply-nudge')
    {
        return DB::table('notifications')->where('notifiable_id', $user->id)->where('data->menu_key', $key)->get();
    }

    public function test_every_reply_gets_its_own_bell_row_so_a_dismissed_ticket_still_rings_again(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id]);

        $first = $ticket->addReply($assignee, 'one');
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $first->id);
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $first->id);
        $this->assertCount(1, $this->nudgeRows($requester));

        DB::table('notifications')->where('notifiable_id', $requester->id)->update(['read_at' => now()]);

        $second = $ticket->addReply($assignee, 'two');
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $second->id);

        $rows = $this->nudgeRows($requester);
        $this->assertCount(2, $rows);
        $this->assertCount(1, $rows->whereNull('read_at'));
        $this->assertSame(
            [(string) $first->id, (string) $second->id],
            $rows->map(fn($r) => json_decode($r->data, true)['item_id'])->sort()->values()->all()
        );
        $this->assertStringContainsString('open=' . $ticket->id, json_encode(json_decode($rows->last()->data, true)['actions']));
    }

    private function replyThread(int $replies = 1): array
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id, 'effectiveness' => '5']);
        DB::table('notifications')->whereIn('notifiable_id', [$requester->id, $assignee->id])->delete();

        $made = [];
        for ($i = 0; $i < $replies; $i++) {
            $made[] = $reply = $ticket->addReply($assignee, 'r' . $i);
            NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $reply->id);
        }

        return [$ticket, $requester, $assignee, $made];
    }

    private function updateAndPrune(Ticket $ticket, array $attributes, bool $events = true): void
    {
        $fresh = $ticket->fresh();
        $events ? $fresh->update($attributes) : Ticket::withoutEvents(fn() => $fresh->update($attributes));
        (new \App\Services\Menu\Notifications\ThsReplyNudge())->triggers()[1]['subject']($fresh);
    }

    public function test_a_reply_id_equal_to_a_ticket_id_never_collides_and_ticket_reconciles_leave_reply_rows_alone(): void
    {
        [$ticket, $requester, $assignee, [$reply]] = $this->replyThread();
        $sharedId = $reply->id;
        $this->assertFalse(DB::table('tickets')->where('id', $sharedId)->exists());
        DB::table('tickets')->where('id', $ticket->id)->update(['id' => $sharedId]);
        DB::table('replies')->where('id', $reply->id)->update(['repliable_id' => $sharedId]);
        DB::table('notifications')->whereIn('notifiable_id', [$requester->id, $assignee->id])->delete();

        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $sharedId);
        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $sharedId);

        foreach ([$requester, $assignee] as $user) {
            $this->assertCount(1, $this->nudgeRows($user, 'ths-controller:reply-nudge'));
            $this->assertCount(1, $this->nudgeRows($user, 'ths-controller:nudge'));
            $this->assertSame((string) $sharedId, json_decode($this->nudgeRows($user)->first()->data, true)['item_id']);
        }

        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $sharedId);
        $this->assertCount(1, $this->nudgeRows($requester, 'ths-controller:reply-nudge'));

        DB::table('replies')->where('id', $sharedId)->delete();
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $sharedId);
        $this->assertCount(0, $this->nudgeRows($requester, 'ths-controller:reply-nudge'));
        $this->assertCount(1, $this->nudgeRows($requester, 'ths-controller:nudge'));
    }

    public function test_closing_a_ticket_prunes_reply_rows_to_the_rating_requester_then_rating_clears_them(): void
    {
        [$ticket, $requester, $assignee] = $this->replyThread(2);
        $this->assertCount(2, $this->nudgeRows($requester));
        $this->assertCount(2, $this->nudgeRows($assignee));

        $this->updateAndPrune($ticket, ['status' => 'closed']);

        $this->assertCount(2, $this->nudgeRows($requester));
        $this->assertCount(0, $this->nudgeRows($assignee));

        $this->updateAndPrune($ticket, ['satisfaction_score' => 4]);

        $this->assertCount(0, $this->nudgeRows($requester));
    }

    public function test_withdrawing_a_ticket_prunes_every_reply_row(): void
    {
        [$ticket, $requester, $assignee] = $this->replyThread(2);

        $this->updateAndPrune($ticket, ['status' => 'closed', 'action_result' => Ticket::WITHDRAWN_RESULT]);

        $this->assertCount(0, $this->nudgeRows($requester));
        $this->assertCount(0, $this->nudgeRows($assignee));
    }

    public function test_reassigning_a_ticket_prunes_the_old_assignees_reply_rows_without_adding_rows_for_the_new_one(): void
    {
        [$ticket, $requester, $assignee] = $this->replyThread(2);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);

        $this->updateAndPrune($ticket, ['assigned_to' => $other->id], false);

        $this->assertCount(2, $this->nudgeRows($requester));
        $this->assertCount(0, $this->nudgeRows($assignee));
        $this->assertCount(0, $this->nudgeRows($other));
    }

    public function test_an_unrelated_ticket_edit_leaves_reply_rows_untouched(): void
    {
        [$ticket, $requester, $assignee] = $this->replyThread(2);

        $this->updateAndPrune($ticket, ['additional_notes' => 'x']);

        $this->assertCount(2, $this->nudgeRows($requester));
        $this->assertCount(2, $this->nudgeRows($assignee));
    }

    public function test_deleting_a_ticket_or_a_reply_removes_its_reply_rows_without_orphans(): void
    {
        [$ticket, $requester, $assignee, [$first, $second]] = $this->replyThread(2);

        $second->delete();
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $second->id);
        $this->assertSame([(string) $first->id], $this->nudgeRows($requester)->map(fn($r) => json_decode($r->data, true)['item_id'])->all());

        $ticket->delete();
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $first->id);
        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);

        $this->assertCount(0, $this->nudgeRows($requester));
        $this->assertCount(0, $this->nudgeRows($assignee));
        $this->assertCount(0, $this->nudgeRows($requester, 'ths-controller:nudge'));
    }

    public function test_the_bell_counts_reply_rows_and_ticket_rows_and_folds_each_key_on_its_own(): void
    {
        [$ticket, $requester] = $this->replyThread(1);
        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);

        $this->assertSame(2, $requester->unreadNotifications()->count());
        $this->assertSame(2, Livewire::actingAs($requester)->test(\App\Livewire\Dashboard\UnreadNotifications::class)->instance()->getUnreadNotificationsCount());

        $this->nudgeRows($requester)->each(fn($r) => DB::table('notifications')->where('id', $r->id)->update(['read_at' => now()]));
        $this->assertSame(1, $requester->unreadNotifications()->count());
    }

    public function test_headless_departments_are_hidden_from_the_create_form_and_the_reroute_list_and_rejected(): void
    {
        [$headed] = $this->customDepartment(true);
        [$headless] = $this->customDepartment(false);
        $requester = $this->deptUser('PS', 'expert');
        Livewire::withoutLazyLoading();

        $options = Livewire::actingAs($requester)->test(Main::class)->get('departmentOptions');
        $this->assertArrayHasKey($headed, $options);
        $this->assertArrayNotHasKey($headless, $options);

        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $ticket = $this->openTicketFor($code);

        $workspace = Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id]);
        $this->assertTrue($workspace->instance()->departmentOptions->has($code));
        $this->assertFalse($workspace->instance()->departmentOptions->has($headless));

        $workspace->set('rerouteTo', $headless)->set('replyForm.body', 'move')->call('rerouteTicket')
            ->assertDispatched('toast', message: 'واحد انتخاب‌شده مسئولی ندارد و امکان ارجاع به آن وجود ندارد.', type: 'error');

        $fresh = Ticket::find($ticket->id);
        $this->assertSame($code, $fresh->targetDepartmentId);
        $this->assertSame(0, $fresh->replies()->count());
    }

    public function test_tampered_target_department_outside_the_offered_list_is_rejected_on_submit(): void
    {
        [$headed] = $this->customDepartment(true);
        [$headless] = $this->customDepartment(false);
        $requester = $this->deptUser('PS', 'expert');
        Livewire::withoutLazyLoading();

        Livewire::actingAs($requester)->test(Main::class)
            ->set('ticket.targetDepartment', $headless)
            ->set('ticket.requestType', 'support')
            ->set('ticket.requestArea', 'a1')
            ->set('ticket.priority', 'low')
            ->set('ticket.subject', 'Tampered target')
            ->set('ticket.description', 'Should never be stored.')
            ->call('submitTicket')
            ->assertHasErrors(['ticket.targetDepartment']);

        $this->assertSame(0, Ticket::where('request_subject', 'Tampered target')->count());
    }

    public function test_duplicate_subject_within_the_guard_window_shows_a_message_and_creates_nothing(): void
    {
        $requester = $this->deptUser('PS', 'expert');
        Livewire::withoutLazyLoading();
        Cache::flush();

        $fill = fn($c) => $c->set('ticket.targetDepartment', 'N/A')->set('ticket.requestType', 'support')->set('ticket.requestArea', 'vpn')->set('ticket.priority', 'low')
            ->set('ticket.subject', 'Duplicate probe')->set('ticket.description', 'Same subject twice.');

        $component = Livewire::actingAs($requester)->test(Main::class);
        $fill($component)->call('submitTicket')->assertHasNoErrors();

        $fill($component)->call('submitTicket')
            ->assertHasErrors(['ticket.subject'])
            ->assertSee('درخواستی با همین موضوع چند لحظه پیش ثبت شد.');

        $this->assertSame(1, Ticket::where('requester_id', $requester->id)->where('request_subject', 'Duplicate probe')->count());
    }

    public function test_clearing_an_attachment_slot_does_not_block_the_submit(): void
    {
        Storage::fake('public');
        $requester = $this->deptUser('PS', 'expert');
        $kept = Str::random(13);
        $cleared = Str::random(13);

        Livewire::actingAs($requester)->test(Main::class)
            ->set('ticket.requestType', 'support')->set('ticket.requestArea', 'vpn')->set('ticket.priority', 'low')
            ->set('ticket.subject', 'Cleared slot probe')->set('ticket.description', 'One slot emptied with the X.')
            ->set('ticket.files.' . $kept, UploadedFile::fake()->image('keep.png', 20, 20))
            ->set('ticket.files.' . $cleared, null)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $ticket = Ticket::where('request_subject', 'Cleared slot probe')->firstOrFail();
        $this->assertCount(1, $ticket->requester_files);
    }

    public function test_assignee_files_are_shown_to_the_requester_in_the_modal(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class)
            ->call('viewTicket', $ticket->id)
            ->assertDontSeeHtml('wire:key="ths-modal-assignee-files"');

        $ticket->update(['assignee_files' => [['file' => 'ticket/assignee/answer.pdf', 'name' => 'answer.pdf', 'mime' => 'application/pdf', 'size' => 10]]]);

        $component->call('viewTicket', $ticket->id)
            ->assertSee('فایل‌های ضمیمهٔ پاسخ')
            ->assertSeeHtml('wire:key="ths-modal-assignee-files"');
    }

    public function test_department_tab_supports_search_sort_and_the_search_bar(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $mk = fn(string $subject, $at) => Ticket::factory()->create([
            'requester_id' => User::factory()->create(['status' => 'active', 'role' => 'user'])->id,
            'status' => 'open', 'assigned_to' => null, 'request_subject' => $subject, 'created_at' => $at,
            'extra' => ['target_department' => $code],
        ]);
        $alpha = $mk('Alpha dept', now()->subDay());
        $zulu = $mk('Zulu dept', now());
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($head)->test(Main::class)
            ->set('activeTab', 'log')
            ->call('setListFilter', 'department')
            ->assertSeeHtml('wire:key="ths-search-filters"')
            ->assertSeeHtml('wire:key="ths-date-span"');

        $this->assertSame([$zulu->id, $alpha->id], $component->instance()->departmentTickets()->pluck('id')->all());

        $component->set('ticketSearch', 'Alpha');
        $this->assertSame([$alpha->id], $component->instance()->departmentTickets()->pluck('id')->all());

        $component->set('ticketSearch', '')->call('sortBy', 'subject');
        $this->assertSame([$alpha->id, $zulu->id], $component->instance()->departmentTickets()->pluck('id')->all());
    }

    public function test_release_and_reroute_note_warns_that_the_requester_can_see_it(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->openTicketFor($code, $requester);
        $warning = 'این توضیح برای درخواست‌دهنده نیز قابل مشاهده است.';

        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])->assertSee($warning);
        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])->assertDontSee($warning);
    }

    public function test_assigning_writes_a_system_reply_each_time_and_tells_the_requester(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $first = $this->staffIn($code, 'intern');
        $second = $this->staffIn($code, 'intern');
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = $this->openTicketFor($code, $requester);

        $component = Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('assigneeId', (string) $first->id)->call('assign');
        $component->set('assigneeId', (string) $second->id)->call('assign');

        $this->assertSame(['تخصیص به ' . $first->name, 'تخصیص به ' . $second->name], $this->threadBodies($ticket));
        $this->assertSame($second->id, Ticket::find($ticket->id)->assigned_to);

        $reply = $ticket->replies()->latest('id')->first();
        $this->assertSame($head->id, $reply->user_id);
        NudgeService::reconcile('ths-controller:reply-nudge', \App\Models\Reply::class, $reply->id);
        $this->assertCount(1, $this->nudgeRows($requester));

        $ticket->fresh()->update(['effectiveness' => '5']);
        $closed = $ticket->fresh();
        $closed->update(['status' => 'closed']);
        $this->assertStringContainsString('تخصیص به ' . $second->name, $closed->fresh()->action_result);
    }

    public function test_closing_without_effectiveness_raises_a_validation_error_instead_of_a_bare_exception(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'open', 'assigned_to' => null, 'effectiveness' => null]);

        try {
            $ticket->update(['status' => 'closed']);
            $this->fail('closing without effectiveness must be rejected');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('effectiveness', $e->errors());
            $this->assertStringContainsString('ERR-T001', $e->errors()['effectiveness'][0]);
        }

        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_a_department_head_can_release_an_in_progress_ticket_but_a_bystander_cannot(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $staff = $this->staffIn($code, 'intern');
        $bystander = $this->staffIn($code, 'intern');
        $ticket = $this->openTicketFor($code);
        Ticket::find($ticket->id)->update(['assigned_to' => $staff->id]);
        $ticket = Ticket::find($ticket->id);

        $this->assertTrue(TicketAccessPolicy::canRelease($ticket, $head));
        $this->assertTrue(TicketAccessPolicy::canRelease($ticket, $staff));
        $this->assertFalse(TicketAccessPolicy::canRelease($ticket, $bystander));

        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('replyForm.body', 'head returns it')
            ->call('releaseTicket')
            ->assertDispatched('toast', message: 'تیکت به صف بازگردانده شد.', type: 'success');

        $fresh = Ticket::find($ticket->id);
        $this->assertNull($fresh->assigned_to);
        $this->assertSame('open', $fresh->status);
    }

    public function test_rating_prompt_shows_the_formatted_id_and_stars_carry_aria_labels(): void
    {
        $requester = $this->deptUser('PS', 'expert');
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null, 'effectiveness' => '5', 'extra' => ['target_department' => 'PS']]);
        $ticket->update(['status' => 'closed']);
        Livewire::withoutLazyLoading();

        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        Livewire::actingAs($requester)->test(Main::class)
            ->assertSee($presenter->formatId($ticket->fresh()->toArray()))
            ->assertSeeHtml('aria-label="امتیاز 5 از ۵"');
    }

    private function ticketArray(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'status' => 'open',
            'requester_id' => 0,
            'assigned_to' => null,
            'assignee' => null,
            'satisfaction_score' => null,
            'action_result' => null,
            'completion_date' => null,
            'completion_deadline' => null,
            'created_at' => now()->subDays(2)->toDateTimeString(),
            'target_department_code' => null,
            'timeline' => ['response' => 'منتظر اولین پاسخ', 'typical' => 'معمولاً حدود ۲ روز'],
        ], $overrides);
    }

    public function test_ball_banner_follows_the_viewers_role_and_the_ticket_status(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user', 'name' => 'کارشناس آزمون']);
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $open = $this->ticketArray(['requester_id' => $requester->id, 'target_department_code' => $code]);
        $this->assertSame('در انتظار تخصیص توسط سرپرست واحد', $presenter->ballState($open, $requester)['title']);
        $this->assertSame('معمولاً حدود ۲ روز', $presenter->ballState($open, $requester)['sub']);
        $headBall = $presenter->ballState($open, $head);
        $this->assertSame('نوبت شماست — کارشناس را تخصیص دهید', $headBall['title']);
        $this->assertSame(['تخصیص', 'response'], $headBall['cta']);

        $progress = $this->ticketArray(['status' => 'in-progress', 'requester_id' => $requester->id, 'assigned_to' => $assignee->id, 'assignee' => ['name' => $assignee->name], 'target_department_code' => $code]);
        $this->assertSame('نوبت شماست — پاسخ دهید یا ببندید', $presenter->ballState($progress, $assignee)['title']);
        $this->assertSame('در دست کارشناس آزمون', $presenter->ballState($progress, $requester)['title']);
        $this->assertSame('در دست کارشناس آزمون', $presenter->ballState($progress, $head)['title']);

        $closed = $this->ticketArray(['status' => 'closed', 'requester_id' => $requester->id, 'assigned_to' => $assignee->id, 'completion_date' => now()->toDateTimeString()]);
        $rate = $presenter->ballState($closed, $requester);
        $this->assertSame('لطفاً ارزیابی کنید', $rate['title']);
        $this->assertSame('rate', $rate['cta'][1]);
        $this->assertSame('بسته شد', $presenter->ballState($closed, $assignee)['title']);
        $this->assertSame('بسته شد', $presenter->ballState(array_merge($closed, ['satisfaction_score' => 4]), $requester)['title']);

        $withdrawn = $presenter->ballState(array_merge($closed, ['action_result' => Ticket::WITHDRAWN_RESULT]), $requester);
        $this->assertSame('لغو شد', $withdrawn['title']);
        $this->assertSame('error', $withdrawn['tone']);
        $this->assertNull($withdrawn['cta']);
    }

    public function test_a_human_reply_starting_with_a_system_prefix_renders_as_a_normal_bubble(): void
    {
        $head = $this->departmentHead();
        $staff = $this->staffIn($head->profile->department_id, 'intern');
        $ticket = $this->openTicketFor($head->profile->department_id);
        $ticket->update(['assigned_to' => $staff->id, 'status' => 'in-progress']);

        $component = Livewire::actingAs($staff)->test(Workspace::class, ['ticketId' => $ticket->id]);

        foreach ([
            \App\Livewire\Dashboard\Ths\Actions\AssignTicketAction::REPLY_PREFIX . 'علی',
            '  ' . \App\Livewire\Dashboard\Ths\Actions\RerouteTicketAction::REPLY_PREFIX . 'واحد: چون',
            \App\Livewire\Dashboard\Ths\Actions\ReleaseTicketAction::REPLY_PREFIX . 'چون',
            'پاسخ معمولی',
        ] as $body) {
            $component->set('replyForm.body', $body)->call('postReply');
        }

        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();
        $replies = $ticket->replies()->get();

        $this->assertCount(4, $replies);
        $this->assertSame(['reply', 'reply', 'reply', 'reply'], $replies->map(fn ($r) => $presenter->replyKind($r))->all());
        $this->assertSame(1, mb_strpos($replies[0]->body, "\u{2060}"));
        $this->assertSame(1, mb_strpos($replies[1]->body, "\u{2060}"));
        $this->assertSame(1, mb_strpos($replies[2]->body, "\u{2060}"));
        $this->assertFalse(mb_strpos($replies[3]->body, "\u{2060}"));
        $this->assertStringNotContainsString('data-ths-system', $component->html());
    }

    public function test_reply_kind_detects_system_entries_from_the_shared_prefixes(): void
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $this->assertSame('assign', $presenter->replyKind(['body' => \App\Livewire\Dashboard\Ths\Actions\AssignTicketAction::REPLY_PREFIX . 'علی']));
        $this->assertSame('reroute', $presenter->replyKind(['body' => \App\Livewire\Dashboard\Ths\Actions\RerouteTicketAction::REPLY_PREFIX . 'واحد: چون']));
        $this->assertSame('release', $presenter->replyKind(['body' => \App\Livewire\Dashboard\Ths\Actions\ReleaseTicketAction::REPLY_PREFIX . 'چون']));
        $this->assertSame('reply', $presenter->replyKind(['body' => 'پاسخ معمولی']));
        $this->assertNotNull($presenter->systemEntryMeta('assign'));
        $this->assertNull($presenter->systemEntryMeta('reply'));
    }

    public function test_deadline_ring_is_null_without_a_deadline_and_tracks_on_time_late_and_closed_outcomes(): void
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();

        $this->assertNull($presenter->deadlineRing($this->ticketArray()));

        $onTrack = $presenter->deadlineRing($this->ticketArray(['created_at' => now()->subDays(2)->toDateTimeString(), 'completion_deadline' => now()->addDays(2)->toDateTimeString()]));
        $this->assertFalse($onTrack['late']);
        $this->assertEqualsWithDelta(50, $onTrack['percent'], 1);
        $this->assertSame('var(--md-sys-color-primary)', $onTrack['color']);
        $this->assertStringEndsWith('٪', $onTrack['label']);

        $late = $presenter->deadlineRing($this->ticketArray(['created_at' => now()->subDays(3)->toDateTimeString(), 'completion_deadline' => now()->subDay()->toDateTimeString()]));
        $this->assertTrue($late['late']);
        $this->assertSame(100, $late['percent']);
        $this->assertSame('var(--md-sys-color-error)', $late['color']);

        $closedOnTime = $presenter->deadlineRing($this->ticketArray(['status' => 'closed', 'created_at' => now()->subDays(3)->toDateTimeString(), 'completion_deadline' => now()->addDay()->toDateTimeString(), 'completion_date' => now()->toDateTimeString()]));
        $this->assertSame(['percent' => 100, 'late' => false, 'label' => '✓', 'color' => 'var(--md-sys-color-success)'], $closedOnTime);

        $closedLate = $presenter->deadlineRing($this->ticketArray(['status' => 'closed', 'created_at' => now()->subDays(3)->toDateTimeString(), 'completion_deadline' => now()->subDay()->toDateTimeString(), 'completion_date' => now()->toDateTimeString()]));
        $this->assertSame('!', $closedLate['label']);
        $this->assertTrue($closedLate['late']);
    }

    public function test_status_steps_carry_per_step_meta_and_flag_a_withdrawn_ticket(): void
    {
        $presenter = new \App\Livewire\Dashboard\Ths\Presentation\TicketPresenter();
        $steps = $presenter->statusSteps('closed', $this->ticketArray(['status' => 'closed', 'completion_deadline' => now()->toDateTimeString(), 'completion_date' => now()->toDateTimeString(), 'action_result' => Ticket::WITHDRAWN_RESULT]));

        $this->assertNotNull($steps[0]['meta']);
        $this->assertStringStartsWith('مهلت: ', $steps[1]['meta']);
        $this->assertSame(['error', 'لغو شده', 'cancel'], [$steps[2]['state'], $steps[2]['label'], $steps[2]['icon']]);
        $this->assertNull($presenter->statusSteps('open')[0]['meta']);
    }

    public function test_modal_renders_the_details_panel_toggle_on_the_tab_row_the_sheet_handle_and_the_ball_banner(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null, 'completion_deadline' => now()->addDays(3)]);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($requester)->test(Main::class)
            ->call('viewTicket', $ticket->id)
            ->assertSeeHtml('aria-labelledby="ths-modal-title"')
            ->assertSeeHtml('aria-controls="ths-modal-info"')
            ->assertSeeHtml(':aria-expanded="info"')
            ->assertSeeHtml('data-ths-info-toggle')
            ->assertSeeHtml('id="ths-modal-info"')
            ->assertSeeHtml('wire:key="tab-request"')
            ->assertSeeHtml('wire:key="tab-response"')
            ->assertSeeHtml('wire:key="tab-info"')
            ->assertSeeHtml('wire:key="tab-files"')
            ->assertSeeHtml('data-ths-handle')
            ->assertSeeHtml('wire:key="ths-modal-ball"')
            ->assertSeeHtml('wire:key="ths-info-ring"')
            ->assertSeeHtml('wire:key="ths-modal-strip"')
            ->assertSee('در انتظار تخصیص توسط سرپرست واحد')
            ->assertSee('توضیحات تیکت')
            ->assertSee('مشخصات')
            ->assertSee('پیوست‌ها');
    }

    public function test_quick_replies_render_only_for_assignee_head_or_elevated_viewers(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $staff = $this->staffIn($code, 'intern');
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = $this->openTicketFor($code, $requester);
        $chip = 'wire:key="ths-quick-replies"';

        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])->assertDontSeeHtml($chip);
        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])->assertSeeHtml($chip)->assertSee('در حال بررسی هستم');

        Ticket::find($ticket->id)->update(['assigned_to' => $staff->id]);
        Livewire::actingAs($staff)->test(Workspace::class, ['ticketId' => $ticket->id])->assertSeeHtml($chip)->assertSee('مشکل برطرف شد؛ لطفاً تأیید کنید');
        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])->assertDontSeeHtml($chip);
    }

    public function test_assign_release_and_reroute_replies_render_as_centered_system_entries(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $targetHead = $this->departmentHead();
        $staff = $this->staffIn($code, 'intern');
        $ticket = $this->openTicketFor($code);

        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])->set('assigneeId', (string) $staff->id)->call('assign');
        Livewire::actingAs($staff)->test(Workspace::class, ['ticketId' => $ticket->id])->set('replyForm.body', 'نمی‌توانم')->call('releaseTicket');
        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])->set('rerouteTo', $targetHead->profile->department_id)->set('replyForm.body', 'واحد اشتباه')->call('rerouteTicket');

        $html = Livewire::actingAs($targetHead)->test(Workspace::class, ['ticketId' => $ticket->id])->html();

        $this->assertStringContainsString('data-ths-system="assign"', $html);
        $this->assertStringContainsString('data-ths-system="release"', $html);
        $this->assertStringContainsString('data-ths-system="reroute"', $html);
        $this->assertStringContainsString('توسط ' . $head->name, $html);
        $this->assertSame(3, substr_count($html, 'data-ths-system="'));
    }

    public function test_thread_groups_replies_under_day_separators(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id]);
        $old = $ticket->addReply($assignee, 'قدیمی');
        $oldDay = now()->subDays(3);
        DB::table('replies')->where('id', $old->id)->update(['created_at' => $oldDay, 'updated_at' => $oldDay]);
        $ticket->addReply($requester, 'امروزی اول');
        $ticket->addReply($assignee, 'امروزی دوم');

        $html = Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])->html();

        $this->assertSame(2, substr_count($html, 'wire:key="ths-day-'));
        $this->assertStringContainsString('wire:key="ths-day-' . $oldDay->toDateString() . '"', $html);
        $this->assertStringContainsString('wire:key="ths-day-' . now()->toDateString() . '"', $html);
        $this->assertStringContainsString('>امروز<', $html);
    }

    public function test_composer_renders_for_a_replying_viewer_and_the_closed_notice_otherwise(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id, 'effectiveness' => '4']);

        Livewire::actingAs($assignee)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->assertSeeHtml('wire:key="ths-reply-composer"')
            ->assertSeeHtml('data-ths-composer')
            ->assertSeeHtml('aria-label="متن پاسخ"')
            ->assertSeeHtml('wire:submit.prevent="postReply"')
            ->assertSeeHtml("uploadMultiple('replyForm.files'")
            ->assertSeeHtml('wire:key="ths-drop-overlay"')
            ->assertSee('Ctrl+Enter')
            ->assertSeeHtml('wire:key="ths-workspace-effectiveness"')
            ->assertSeeHtml('wire:key="ths-close-ticket"')
            ->assertDontSeeHtml('wire:key="ths-reply-closed"');

        $ticket->update(['status' => 'closed', 'satisfaction_score' => 5, 'extra' => ['satisfaction_comment' => 'عالی بود']]);

        Livewire::actingAs($assignee)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->assertSeeHtml('wire:key="ths-reply-closed"')
            ->assertSee('این تیکت بسته شده و امکان ارسال پاسخ جدید وجود ندارد.')
            ->assertSeeHtml('wire:key="ths-modal-rating"')
            ->assertSee('رضایت درخواست‌کننده')
            ->assertSee('عالی بود')
            ->assertDontSeeHtml('data-ths-composer')
            ->assertDontSeeHtml('wire:key="ths-drop-overlay"');

        Ticket::withoutEvents(fn() => $ticket->fresh()->update(['action_result' => Ticket::WITHDRAWN_RESULT]));
        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->assertSee('این تیکت لغو شده و امکان ارسال پاسخ جدید وجود ندارد.')
            ->assertSee('رضایت شما');
    }

    public function test_effectiveness_and_rating_share_one_outcome_bar_above_the_composer(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id, 'effectiveness' => null]);

        $open = Livewire::actingAs($assignee)->test(Workspace::class, ['ticketId' => $ticket->id])->html();
        $this->assertMatchesRegularExpression('/wire:key="ths-workspace-outcome".*wire:key="ths-workspace-effectiveness".*wire:key="ths-workspace-effect-form".*wire:key="ths-close-ticket".*wire:key="ths-reply-composer"/su', $open);
        $this->assertStringContainsString("wire:click=\"setEffectiveness('4')\"", $open);
        $this->assertStringContainsString('wire:confirm="این تیکت بسته شود؟"', $open);
        $this->assertStringNotContainsString('wire:key="ths-modal-rating"', $open);

        $ticket->update(['effectiveness' => '3']);
        $ticket->fresh()->update(['status' => 'closed', 'satisfaction_score' => 4, 'extra' => ['satisfaction_comment' => 'نظر کوتاه']]);

        $closed = Livewire::actingAs($assignee)->test(Workspace::class, ['ticketId' => $ticket->id])->html();
        preg_match('/wire:key="ths-workspace-outcome".*?wire:key="ths-reply-closed"/su', $closed, $bar);

        $this->assertNotEmpty($bar, 'the outcome bar must sit directly above the closed notice');
        $this->assertStringContainsString('wire:key="ths-workspace-effect-set"', $bar[0]);
        $this->assertStringContainsString('wire:key="ths-modal-rating"', $bar[0]);
        $this->assertStringContainsString('wire:key="ths-workspace-outcome-sep"', $bar[0]);
        $this->assertStringContainsString('title="نظر کوتاه"', $bar[0]);
        $this->assertStringNotContainsString('wire:key="ths-close-ticket"', $bar[0]);
        $this->assertSame(1, substr_count($closed, 'wire:key="ths-workspace-outcome"'));

        $ticket->fresh()->update(['satisfaction_score' => null]);
        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])->assertSeeHtml('wire:key="ths-rating-pending"');
        Livewire::actingAs($assignee)->test(Workspace::class, ['ticketId' => $ticket->id])->assertDontSeeHtml('wire:key="ths-modal-rating"');
    }

    public function test_the_dialog_sits_above_its_backdrop_and_only_the_backdrop_and_close_button_close_it(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);
        Livewire::withoutLazyLoading();

        $html = Livewire::actingAs($requester)->test(Main::class)->call('viewTicket', $ticket->id)->html();
        $modal = substr($html, strpos($html, '<template wire:key="ths-modal" x-teleport="body">'));

        $this->assertStringContainsString('class="absolute inset-0 z-0 bg-[var(--md-sys-color-primary)]/60"', $modal);
        $this->assertStringContainsString('class="relative z-10 isolate w-full', $modal);
        $this->assertSame(2, substr_count($modal, 'x-on:click="closeModal()"'), 'only the backdrop and the close button may close the modal on click');
        $this->assertStringNotContainsString('x-on:click.outside="closeModal', $modal);
        $this->assertStringContainsString('x-on:click="toggleMaximize()"', $modal);
        $this->assertStringContainsString('x-on:click="info ? closeInfo() : openInfo()"', $modal);

        preg_match_all('/<button(?![^>]*type="button")[^>]*>/u', substr($modal, 0, strpos($modal, 'data-ths-pane')), $untyped);
        $this->assertSame([], $untyped[0], 'every header / tab-row button must be type="button"');
    }

    public function test_the_rendered_modal_never_carries_a_duplicate_wire_key(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $staff = $this->staffIn($code, 'intern');
        $ticket = $this->openTicketFor($code);
        Ticket::find($ticket->id)->update(['assigned_to' => $staff->id, 'completion_deadline' => now()->addDay(), 'requester_files' => [['file' => 'ths/a.png'], ['file' => 'ths/b.pdf']]]);
        Ticket::find($ticket->id)->addReply($staff, 'سلام');
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($head)->test(Main::class)->call('switchTab', 'log')->call('viewTicket', $ticket->id);

        foreach (['request', 'response'] as $tab) {
            $html = $component->call('switchTab', $tab)->html();
            $modal = substr($html, strpos($html, 'wire:key="ths-modal"'));
            preg_match_all('/wire:key="([^"]+)"/u', $modal, $m);

            $this->assertNotEmpty($m[1]);
            $this->assertSame([], array_keys(array_filter(array_count_values($m[1]), fn($c) => $c > 1)), "duplicate wire:key inside the modal on the {$tab} tab");
        }
    }

    public function test_the_modal_keeps_the_former_teleport_and_stacking_layers_below_toasts_and_confirmations(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);
        Livewire::withoutLazyLoading();

        $html = Livewire::actingAs($requester)->test(Main::class)->call('viewTicket', $ticket->id)->html();
        $modal = substr($html, strpos($html, '<template wire:key="ths-modal" x-teleport="body">'));

        $this->assertStringContainsString('<template wire:key="ths-modal" x-teleport="body">', $html);
        $this->assertStringContainsString('class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-6"', $modal);
        $this->assertStringContainsString('class="absolute inset-0 z-0 bg-[var(--md-sys-color-primary)]/60"', $modal);
        $this->assertStringContainsString('class="relative z-10 isolate w-full max-w-4xl max-h-[90vh] rounded-2xl bg-[var(--md-sys-color-surface)]', $modal);
        $this->assertStringContainsString('x-data="thsModal()"', $modal);
        $this->assertStringContainsString("'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving", $modal);
        $this->assertStringContainsString('class="max-backdrop ', $modal);
        $this->assertStringContainsString('x-on:click="toggleMaximize()"', $modal);
        $this->assertStringNotContainsString('max = !max', $modal);
        $this->assertStringNotContainsString("max ? 'h-full", $modal);
        $this->assertStringNotContainsString('sm:max-w-5xl', $modal);
        $this->assertStringContainsString('role="dialog"', $modal);
        $this->assertStringContainsString('aria-modal="true"', $modal);
        $this->assertStringNotContainsString('data-ths-pane class="flex-1 min-w-0 min-h-0 overflow-y-auto overscroll-contain custom-scrollbar [container-type:size]" style', $modal);
        $this->assertMatchesRegularExpression('/id="ths-modal-info"[^>]*class="absolute inset-y-0 left-0 z-30 /', $modal);

        $css = file_get_contents(resource_path('css/core/dashboard.css'));
        preg_match('/\.toast-floating\s*\{[^}]*z-index:\s*(\d+)/s', $css, $toast);
        preg_match('/\.custom-modal\s*\{[^}]*z-index:\s*(\d+)/s', $css, $confirm);
        $this->assertGreaterThan(100, (int) $toast[1], 'toasts must stack above the ticket modal');
        $this->assertGreaterThanOrEqual(100, (int) $confirm[1], 'the confirmation dialog must never stack below the ticket modal');
        $this->assertStringContainsString('z-[10002]', file_get_contents(resource_path('views/components/ui/modals/confirmation.blade.php')));

        foreach (['panel', 'dialog', 'slideover'] as $shell) {
            $this->assertStringContainsString('z-[100]', file_get_contents(resource_path("views/components/ui/modals/{$shell}.blade.php")), "the ticket modal shares the z-[100] layer with x-ui.modals.{$shell}");
        }
    }

    public function test_status_filter_narrows_every_list_and_resets_paging(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $mineOpen = Ticket::factory()->create(['requester_id' => $head->id, 'status' => 'open', 'assigned_to' => null]);
        $mineClosed = Ticket::factory()->create(['requester_id' => $head->id, 'status' => 'closed', 'effectiveness' => '4']);
        $deptOpen = $this->openTicketFor($code);
        $deptClosed = Ticket::factory()->create(['status' => 'closed', 'effectiveness' => '4', 'extra' => ['target_department' => $code]]);
        $assignedToHead = Ticket::factory()->create(['status' => 'in-progress', 'assigned_to' => $head->id, 'extra' => ['target_department' => $code]]);

        $component = Livewire::actingAs($head)->test(Main::class)->set('perPage', 30)->set('inboxPerPage', 30);

        $component->set('statusFilter', 'closed')->assertSet('perPage', 10)->assertSet('inboxPerPage', 10);
        $this->assertSame([$mineClosed->id], $component->instance()->tickets()->pluck('id')->all());
        $this->assertSame([$deptClosed->id], $component->instance()->departmentTickets()->pluck('id')->all());
        $this->assertSame([$mineClosed->id], $component->instance()->inboxTickets()->pluck('id')->all(), 'the closed ticket awaiting the head\'s own rating is the only closed actionable row');

        $component->set('statusFilter', 'open');
        $this->assertSame([$mineOpen->id], $component->instance()->tickets()->pluck('id')->all());
        $this->assertSame([$deptOpen->id], $component->instance()->departmentTickets()->pluck('id')->all());
        $this->assertSame([$deptOpen->id], $component->instance()->inboxTickets()->pluck('id')->all());

        $component->set('statusFilter', 'in-progress');
        $this->assertSame([$assignedToHead->id], $component->instance()->inboxTickets()->pluck('id')->all());

        $component->set('statusFilter', 'all');
        $this->assertEqualsCanonicalizing([$mineOpen->id, $mineClosed->id], $component->instance()->tickets()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$deptOpen->id, $assignedToHead->id, $mineClosed->id], $component->instance()->inboxTickets()->pluck('id')->all());
    }

    public function test_search_term_narrows_the_actionable_list_too(): void
    {
        $head = $this->departmentHead();
        $code = $head->profile->department_id;
        $alpha = $this->openTicketFor($code);
        $zulu = $this->openTicketFor($code);
        Ticket::whereKey($alpha->id)->update(['request_subject' => 'Alpha actionable']);
        Ticket::whereKey($zulu->id)->update(['request_subject' => 'Zulu actionable']);

        $component = Livewire::actingAs($head)->test(Main::class)->call('setListFilter', 'actionable');
        $this->assertEqualsCanonicalizing([$alpha->id, $zulu->id], $component->instance()->inboxTickets()->pluck('id')->all());

        $component->set('ticketSearch', 'Alpha');
        $this->assertSame([$alpha->id], $component->instance()->inboxTickets()->pluck('id')->all());
    }

    public function test_status_chips_render_inside_the_filters_panel_on_every_list_and_no_inline_select_remains(): void
    {
        $head = $this->departmentHead();
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($head)->test(Main::class)->set('activeTab', 'log');

        foreach (['mine', 'actionable', 'department'] as $filter) {
            $component->call('setListFilter', $filter)
                ->assertSeeHtml('wire:key="ths-search-filters"')
                ->assertSee('وضعیت تیکت')
                ->assertSeeHtml('wire:key="ths-status-chip-all"')
                ->assertSeeHtml('wire:key="ths-status-chip-open"')
                ->assertSeeHtml('wire:key="ths-status-chip-in-progress"')
                ->assertSeeHtml('wire:key="ths-status-chip-closed"')
                ->assertSeeHtml("\$set('statusFilter', 'closed')")
                ->assertDontSeeHtml('wire:key="ths-status-filter"')
                ->assertDontSeeHtml('option-popover');
        }
    }

    public function test_rate_tab_opens_for_a_ticket_that_closed_after_the_page_loaded(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'open', 'assigned_to' => null]);
        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($requester)->test(Main::class)->assertSet('ticketToRate', null);

        $component->call('switchTab', 'rate')->assertSet('activeTab', 'new');

        $ticket->update(['status' => 'closed', 'effectiveness' => '4', 'satisfaction_score' => null, 'action_result' => null]);

        $component->call('switchTab', 'rate')->assertSet('activeTab', 'rate');
        $this->assertSame($ticket->id, $component->instance()->ticketToRate->id);
    }

    public function test_effectiveness_chip_marks_only_the_stored_value_as_checked(): void
    {
        $requester = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'user']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
            'effectiveness' => '4',
        ]);

        $html = Livewire::actingAs($assignee)->test(Workspace::class, ['ticketId' => $ticket->id])->html();

        $this->assertSame(1, substr_count($html, 'aria-checked="true"'));
        $this->assertSame(4, substr_count($html, 'aria-checked="false"'));
    }

    public function test_view_ticket_snapshot_carries_only_names_of_requester_and_assignee_but_keeps_avatars(): void
    {
        $requester = $this->deptUser('PS', 'expert');
        $assignee = $this->deptUser('PS', 'expert');
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'assigned_to' => $assignee->id,
        ]);

        $component = Livewire::actingAs($requester)->test(Main::class)->call('viewTicket', $ticket->id);

        foreach (['requester' => $requester, 'assignee' => $assignee] as $key => $user) {
            $this->assertEqualsCanonicalizing(['id', 'name'], array_keys($component->get("selectedTicket.$key")));
            $component->assertSet("selectedTicket.$key.name", $user->name);
            $this->assertNotEmpty($component->get("selectedTicket.{$key}_avatar"));
        }

        $component->assertDontSee($requester->email);
    }

    public function test_workspace_thread_query_count_does_not_grow_with_the_number_of_authors(): void
    {
        $requester = $this->deptUser('PS', 'expert');
        $assignee = $this->deptUser('PS', 'expert');

        $queriesFor = function (int $authors) use ($requester, $assignee) {
            $ticket = Ticket::factory()->create([
                'requester_id' => $requester->id,
                'status' => 'in-progress',
                'assigned_to' => $assignee->id,
            ]);

            for ($i = 0; $i < $authors; $i++) {
                $ticket->replies()->create(['user_id' => $this->deptUser('PS', 'expert')->id, 'body' => "reply $i"]);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id]);
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $queriesFor(1);

        $this->assertSame($queriesFor(2), $queriesFor(6));
    }

    public function test_row_restores_a_maximised_table_and_shows_a_targeted_open_indicator(): void
    {
        $requester = $this->deptUser('PS', 'expert');
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id]);
        Livewire::withoutLazyLoading();

        Livewire::actingAs($requester)->test(Main::class)->call('switchTab', 'log')
            ->assertSeeHtml('x-on:click="if (max) toggleMaximize()"')
            ->assertSeeHtml('wire:click="viewTicket(' . $ticket->id . ')"')
            ->assertSeeHtml('wire:target="viewTicket(' . $ticket->id . ')"');
    }

    public function test_reply_body_wraps_long_unbroken_strings(): void
    {
        $requester = $this->deptUser('PS', 'expert');
        $assignee = $this->deptUser('PS', 'expert');
        $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'status' => 'in-progress', 'assigned_to' => $assignee->id]);
        $ticket->replies()->create(['user_id' => $assignee->id, 'body' => 'https://example.test/' . str_repeat('a', 200)]);

        Livewire::actingAs($requester)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->assertSeeHtml('[overflow-wrap:anywhere]');
    }

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function freeDepartment(bool $free = true): Department
    {
        $code = 'F' . Str::upper(Str::random(4));

        return Department::create([
            'code' => $code,
            'name' => $code,
            'description' => $code,
            'ticket_options' => [['request_type' => 'support', 'area_key' => 'x', 'area_label' => 'X', 'icon' => 'heroicon-o-wrench']],
            'free_assignment' => $free,
        ]);
    }

    private function headedDepartment(bool $free = false): array
    {
        $code = 'H' . Str::upper(Str::random(4));
        $department = Department::create([
            'code' => $code,
            'name' => $code,
            'description' => $code,
            'free_assignment' => $free,
        ]);

        return [$department, $this->memberIn($code, [], 'employee')];
    }

    private function memberIn(string $dept, array $overrides = [], string $position = 'intern'): User
    {
        $user = User::factory()->create(array_merge([
            'status' => 'active', 'presence' => 'onsite', 'role' => 'user', 'type' => 'employee',
        ], $overrides));
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $dept, 'position' => $position]);

        return $user;
    }

    public function test_offered_list_includes_headless_department_only_while_its_flag_is_on(): void
    {
        $dept = $this->freeDepartment(true);

        $this->assertTrue(Department::freeAssignment($dept->code));
        $this->assertTrue(Department::isTicketable($dept->code));
        $this->assertTrue(Department::getOfferedTicketOptions()->has($dept->code));

        $dept->update(['free_assignment' => false]);

        $this->assertFalse(Department::freeAssignment($dept->code));
        $this->assertFalse(Department::isTicketable($dept->code));
        $this->assertFalse(Department::getOfferedTicketOptions()->has($dept->code));
    }

    public function test_ticket_can_be_sent_to_a_headless_flagged_department(): void
    {
        $dept = $this->freeDepartment();
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $validator = Validator::make(['targetDepartment' => $dept->code], [
            'targetDepartment' => ['nullable', 'string', Rule::in([...Department::getOfferedTicketOptions()->keys(), 'N/A', ''])],
        ]);
        $this->assertTrue($validator->passes());

        $ticket = Ticket::create([
            'requester_id' => $requester->id,
            'request_type' => 'support',
            'request_subject' => 'ارسال به واحد آزاد',
            'description' => 'تست تخصیص آزاد',
            'priority' => 'low',
            'status' => 'open',
            'extra' => ['target_department' => $dept->code],
        ]);

        $this->assertSame($dept->code, $ticket->effectiveTargetDepartment());
        $this->assertSame('open', $ticket->status);
    }

    public function test_admin_created_tickets_targeting_the_flagged_department_reach_members(): void
    {
        $dept = $this->freeDepartment();
        $member = $this->memberIn($dept->code);
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $ticket = Ticket::create([
            'requester_id' => $requester->id,
            'request_type' => 'support',
            'request_subject' => 'تیکت ادمین',
            'description' => 'تست تخصیص آزاد',
            'priority' => 'low',
            'status' => 'open',
            'extra' => ['target_department' => $dept->code],
        ]);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $member));
        $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $member));
        $this->assertTrue($ticket->currentActionRecipients()->contains('id', $member->id));
    }

    public function test_after_free_assignment_change_flushes_menu_state_and_rebuilds_member_recipients(): void
    {
        $dept = $this->freeDepartment(false);
        $member = $this->memberIn($dept->code);
        $ticket = $this->openTicketFor($dept->code);

        $this->assertFalse($ticket->currentActionRecipients()->contains('id', $member->id));

        $before = StateService::version();
        Department::afterFreeAssignmentChange($dept->code);
        $this->assertNotSame($before, StateService::version());

        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);
        $this->assertSame(0, $this->nudgeRows($member, 'ths-controller:nudge')->count());

        $dept->update(['free_assignment' => true]);

        $fresh = Ticket::find($ticket->id);
        $this->assertTrue($fresh->currentActionRecipients()->contains('id', $member->id));

        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);
        $count = $this->nudgeRows($member, 'ths-controller:nudge')->count();
        $this->assertNotSame(0, $count);

        NudgeService::reconcile('ths-controller:nudge', Ticket::class, $ticket->id);
        $this->assertSame($count, $this->nudgeRows($member, 'ths-controller:nudge')->count());
    }

    public function test_member_powers_are_open_only_while_heads_keep_theirs(): void
    {
        $dept = $this->freeDepartment();
        $member = $this->memberIn($dept->code);
        $colleague = $this->memberIn($dept->code);
        $ticket = $this->openTicketFor($dept->code);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $member));
        $this->assertTrue(TicketAccessPolicy::canAssign($ticket, $member));
        $this->assertTrue(TicketAccessPolicy::canReroute($ticket, $member));

        $ticket->update(['assigned_to' => $colleague->id]);
        $fresh = Ticket::find($ticket->id);

        $this->assertSame('in-progress', $fresh->status);
        $this->assertFalse(TicketAccessPolicy::canView($fresh, $member));
        $this->assertFalse(TicketAccessPolicy::canRelease($fresh, $member));
        $this->assertFalse(TicketAccessPolicy::canClose($fresh, $member));
        $this->assertFalse(TicketAccessPolicy::canSetEffectiveness($fresh, $member));

        [$headed, $head] = $this->headedDepartment();
        $headTicket = $this->openTicketFor($headed->code);

        $this->assertTrue(TicketAccessPolicy::canView($headTicket, $head));
        $this->assertTrue(TicketAccessPolicy::canAssign($headTicket, $head));

        $headTicket->update(['assigned_to' => $head->id]);
        $headFresh = Ticket::find($headTicket->id);

        $this->assertTrue(TicketAccessPolicy::canView($headFresh, $head));
        $this->assertTrue(TicketAccessPolicy::canRelease($headFresh, $head));
        $this->assertTrue(TicketAccessPolicy::canSetEffectiveness($headFresh, $head));
    }

    public function test_requester_member_is_excluded_from_the_pool_and_recipients(): void
    {
        $dept = $this->freeDepartment();
        $member = $this->memberIn($dept->code);
        $ticket = $this->openTicketFor($dept->code, $member);

        $this->assertTrue(TicketAccessPolicy::canView($ticket, $member));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $member));
        $this->assertFalse(Ticket::query()->actionableBy($member)->whereKey($ticket->id)->exists());
        $this->assertFalse($ticket->currentActionRecipients()->contains('id', $member->id));
    }

    public function test_guest_and_inactive_members_fail_the_gate(): void
    {
        $dept = $this->freeDepartment();
        $guest = $this->memberIn($dept->code, ['type' => 'guest']);
        $inactive = $this->memberIn($dept->code, ['status' => 'inactive']);
        $ticket = $this->openTicketFor($dept->code);

        $this->assertFalse(TicketAccessPolicy::canView($ticket, $guest));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $guest));
        $this->assertFalse(TicketAccessPolicy::canView($ticket, $inactive));
        $this->assertFalse(TicketAccessPolicy::canAssign($ticket, $inactive));
        $this->assertFalse(Ticket::query()->actionableBy($guest)->whereKey($ticket->id)->exists());
        $this->assertFalse(Ticket::query()->actionableBy($inactive)->whereKey($ticket->id)->exists());
    }

    public function test_recipients_fall_back_from_heads_to_members_minus_requester(): void
    {
        $dept = $this->freeDepartment();
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->openTicketFor($dept->code, $requester);
        $member = $this->memberIn($dept->code);

        $recipients = $ticket->currentActionRecipients();
        $this->assertTrue($recipients->contains('id', $member->id));
        $this->assertFalse($recipients->contains('id', $requester->id));

        [$headed, $head] = $this->headedDepartment(true);
        $staff = $this->memberIn($headed->code);
        $headedTicket = $this->openTicketFor($headed->code);

        $headRecipients = $headedTicket->currentActionRecipients();
        $this->assertTrue($headRecipients->contains('id', $head->id));
        $this->assertFalse($headRecipients->contains('id', $staff->id));
    }

    public function test_department_options_cache_reflects_a_profile_department_move(): void
    {
        $dept = $this->freeDepartment();
        $other = $this->freeDepartment(false);
        $member = $this->memberIn($dept->code);

        $this->assertTrue(User::getCachedDepartmentOptions($dept->code)->has($member->id));

        $member->profile->update(['department_id' => $other->code]);

        $this->assertFalse(User::getCachedDepartmentOptions($dept->code)->has($member->id));
        $this->assertTrue(User::getCachedDepartmentOptions($other->code)->has($member->id));
    }

    public function test_reroute_into_a_headless_flagged_department_succeeds(): void
    {
        [$source, $head] = $this->headedDepartment();
        $ticket = $this->openTicketFor($source->code);
        $target = $this->freeDepartment();

        $this->assertTrue(Department::withHeads(Department::getCachedOptions())->has($target->code));

        Livewire::withoutLazyLoading();

        Livewire::actingAs($head)->test(Workspace::class, ['ticketId' => $ticket->id])
            ->set('rerouteTo', $target->code)
            ->set('replyForm.body', 'ارجاع به واحد آزاد')
            ->call('rerouteTicket');

        $fresh = Ticket::find($ticket->id);
        $this->assertSame($target->code, $fresh->targetDepartmentId);
        $this->assertSame('open', $fresh->status);
    }

    public function test_pool_rows_all_pass_can_view_until_a_colleague_claims(): void
    {
        $dept = $this->freeDepartment();
        $member = $this->memberIn($dept->code);
        $colleague = $this->memberIn($dept->code);
        $own = $this->openTicketFor($dept->code, $member);
        $queued1 = $this->openTicketFor($dept->code);
        $queued2 = $this->openTicketFor($dept->code);

        $pool = Ticket::query()->actionableBy($member)->pluck('id');
        $this->assertTrue($pool->contains($queued1->id));
        $this->assertTrue($pool->contains($queued2->id));
        $this->assertFalse($pool->contains($own->id));

        foreach (Ticket::query()->actionableBy($member)->get() as $row) {
            $this->assertTrue(TicketAccessPolicy::canView($row, $member));
        }

        $queued1->update(['assigned_to' => $colleague->id]);

        $this->assertFalse(Ticket::query()->actionableBy($member)->whereKey($queued1->id)->exists());
        $this->assertFalse(TicketAccessPolicy::canView(Ticket::find($queued1->id), $member));
    }

    public function test_flip_off_is_blocked_headless_with_open_tickets_and_the_form_surfaces_it(): void
    {
        $dept = $this->freeDepartment();
        $ticket = $this->openTicketFor($dept->code);

        try {
            $dept->update(['free_assignment' => false]);
            $this->fail('free_assignment flip-off should be blocked for a headless department with open tickets');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['data.free_assignment' => [__('resources/department/strings.errors.free_assignment_blocked')]],
                $e->errors()
            );
        }

        $this->assertTrue((bool) $dept->fresh()->free_assignment);

        $admin = $this->developer();

        Livewire::actingAs($admin)->test(EditDepartment::class, ['record' => $dept->getRouteKey()])
            ->fillForm(['free_assignment' => false])
            ->call('save')
            ->assertHasFormErrors(['free_assignment']);

        $this->assertTrue((bool) $dept->fresh()->free_assignment);
    }

    public function test_flip_off_stays_blocked_after_a_release_reopens_the_ticket(): void
    {
        $dept = $this->freeDepartment();
        $member = $this->memberIn($dept->code);
        $ticket = $this->openTicketFor($dept->code);

        $ticket->update(['assigned_to' => $member->id]);
        $this->assertSame('in-progress', $ticket->fresh()->status);

        try {
            $dept->update(['free_assignment' => false]);
            $this->fail('an in-progress ticket should still block the flip-off');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('data.free_assignment', $e->errors());
        }

        $ticket->update(['assigned_to' => null]);
        $this->assertSame('open', $ticket->fresh()->status);

        try {
            $dept->update(['free_assignment' => false]);
            $this->fail('a released-back-to-open ticket should still block the flip-off');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('data.free_assignment', $e->errors());
        }

        $this->assertTrue((bool) $dept->fresh()->free_assignment);
    }

    public function test_flip_off_is_allowed_with_a_head_or_without_open_tickets(): void
    {
        $headless = $this->freeDepartment();

        $this->assertTrue($headless->update(['free_assignment' => false]));
        $this->assertFalse(Department::freeAssignment($headless->code));

        [$headed, $head] = $this->headedDepartment(true);
        $ticket = $this->openTicketFor($headed->code);
        $ticket->update(['assigned_to' => $head->id]);

        $this->assertTrue($headed->update(['free_assignment' => false]));
        $this->assertFalse(Department::freeAssignment($headed->code));
    }

    public function test_legacy_ticket_without_a_target_is_not_captured_by_a_free_department(): void
    {
        $dept = $this->freeDepartment();
        $member = $this->memberIn($dept->code);
        $created = $this->openTicketFor($dept->code);

        DB::table('tickets')->where('id', $created->id)->update(['extra' => json_encode([])]);
        $legacy = Ticket::find($created->id);

        $this->assertNull($legacy->targetDepartmentId);
        $this->assertNotSame($dept->code, $legacy->effectiveTargetDepartment());
        $this->assertFalse(TicketAccessPolicy::canView($legacy, $member));
        $this->assertFalse(TicketAccessPolicy::canAssign($legacy, $member));
        $this->assertFalse(TicketAccessPolicy::canReroute($legacy, $member));
        $this->assertFalse(Ticket::query()->actionableBy($member)->whereKey($legacy->id)->exists());
        $this->assertFalse($legacy->currentActionRecipients()->contains('id', $member->id));
    }
}
