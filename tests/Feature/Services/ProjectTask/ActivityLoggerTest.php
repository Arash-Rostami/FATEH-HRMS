<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Enums\TaskActivityType;
use App\Filament\Resources\TaskResource\Enums\TaskPriority;
use App\Filament\Resources\TaskResource\Enums\TaskState;
use App\Filament\Resources\TaskResource\Enums\TaskStatus;
use App\Jobs\ReconcileNudge;
use App\Jobs\SyncTaskCalendarEventJob;
use App\Models\Department;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\Menu\NudgeService;
use App\Services\ProjectTask\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        NudgeService::reset();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        DB::rollBack();
        NudgeService::reset();
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

    public function test_comment_creates_a_reply_typed_as_comment_when_body_is_present(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['project_id' => null]);

        $reply = app(ActivityLogger::class)->comment($task, $user, 'hello world');

        $this->assertSame(TaskActivityType::Comment, $reply->type);
        $this->assertSame('hello world', $reply->body);
    }

    public function test_comment_with_only_files_is_typed_as_attachment(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['project_id' => null]);
        $file = \Illuminate\Http\UploadedFile::fake()->create('note.pdf', 10);

        $reply = app(ActivityLogger::class)->comment($task, $user, '', [$file]);

        $this->assertSame(TaskActivityType::Attachment, $reply->type);
        $this->assertNotEmpty($reply->files);
    }

    public function test_system_creates_a_reply_with_null_body_and_actor_id(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['project_id' => null]);

        $reply = app(ActivityLogger::class)->system($task, $user, TaskActivityType::StatusChange, [
            'from' => 'todo',
            'to' => 'done',
        ]);

        $this->assertNull($reply->body);
        $this->assertSame($user->id, $reply->user_id);
        $this->assertSame(TaskActivityType::StatusChange, $reply->type);
        $this->assertSame(['from' => 'todo', 'to' => 'done'], $reply->payload);
    }

    public function test_render_dispatches_to_the_matching_renderer_by_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create(['project_id' => null]);
        $logger = app(ActivityLogger::class);

        $statusReply = $logger->system($task, $user, TaskActivityType::StatusChange, ['from' => 'todo', 'to' => 'done']);
        $archiveReply = $logger->system($task, $user, TaskActivityType::Archive, ['archived' => true]);

        $statusRendered = $logger->render($statusReply);
        $archiveRendered = $logger->render($archiveReply);

        $this->assertStringContainsString('وضعیت', $statusRendered['body']);
        $this->assertStringContainsString('بایگانی', $archiveRendered['body']);
        $this->assertNotSame($statusRendered['icon'], $archiveRendered['icon']);
    }

    public function test_feed_for_unions_task_replies_and_project_replies_in_chronological_order(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);
        $otherTask = Task::factory()->ownedBy($owner)->create(['project_id' => null]);

        $logger = app(ActivityLogger::class);
        $taskReply = $logger->comment($task, $owner, 'task comment');
        $projectReply = $logger->system($project, $owner, TaskActivityType::Assignment, ['added' => [], 'removed' => []]);
        $unrelatedReply = $logger->comment($otherTask, $owner, 'unrelated comment');

        $feedIds = $logger->feedFor($project)->pluck('id')->all();

        $this->assertContains($taskReply->id, $feedIds);
        $this->assertContains($projectReply->id, $feedIds);
        $this->assertNotContains($unrelatedReply->id, $feedIds);
    }

    public function test_feed_for_excludes_replies_of_soft_deleted_tasks(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $task = Task::factory()->ownedBy($owner)->create(['project_id' => $project->id]);

        $logger = app(ActivityLogger::class);
        $reply = $logger->comment($task, $owner, 'about to vanish');

        $task->delete();

        $feedIds = $logger->feedFor($project)->pluck('id')->all();

        $this->assertNotContains($reply->id, $feedIds);
    }

    public function test_log_mapped_changes_batches_multiple_field_changes_into_a_single_insert(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['priority' => null, 'labels' => []]);
        $this->actingAs($user);

        DB::enableQueryLog();
        Task::find($task->id)->update(['status' => 'in-progress', 'priority' => 'high', 'labels' => ['x']]);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $inserts = collect($log)->filter(fn($q) => stripos($q['query'], 'insert into `replies`') !== false);
        $this->assertCount(1, $inserts, 'three changed mapped fields in one save must produce exactly one batched insert');
        $this->assertSame(4, $task->replies()->count());
    }

    public function test_task_driven_change_dispatches_reconcile_nudge_from_log_mapped_changes_after_the_rows_exist(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $this->actingAs($user);

        Task::find($task->id)->update(['status' => 'in-progress']);

        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectClass === Task::class && $job->subjectId === $task->id);
    }

    public function test_log_mapped_changes_dispatches_reconcile_nudge_when_task_detail_changes(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $detail = $task->detail()->create(['collaborators' => []]);
        $this->actingAs($user);
        $collaborator = User::factory()->create(['status' => 'active']);

        TaskDetail::find($detail->id)->update(['collaborators' => [$collaborator->id]]);

        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectClass === Task::class && $job->subjectId === $task->id);
    }

    public function test_log_mapped_changes_does_not_dispatch_reconcile_nudge_when_nothing_mapped_changed(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['rank' => 'm']);
        $this->actingAs($user);

        Task::find($task->id)->update(['rank' => 'z']);

        Queue::assertNotPushed(ReconcileNudge::class);
    }

    public function test_meta_change_logs_a_meta_change_activity_row(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $detail = $task->detail()->create(['meta' => ['vendor' => 'old']]);
        $this->actingAs($user);

        TaskDetail::find($detail->id)->update(['meta' => ['vendor' => 'new', 'extra' => 'x']]);

        $reply = $task->replies()->where('type', TaskActivityType::MetaChange->value)->firstOrFail();
        $this->assertEquals(['added' => ['extra'], 'removed' => [], 'changed' => ['vendor']], $reply->payload);
    }

    public function test_meta_key_removal_is_reported_in_payload(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $detail = $task->detail()->create(['meta' => ['vendor' => 'old', 'extra' => 'x']]);
        $this->actingAs($user);

        TaskDetail::find($detail->id)->update(['meta' => ['vendor' => 'old']]);

        $reply = $task->replies()->where('type', TaskActivityType::MetaChange->value)->firstOrFail();
        $this->assertEquals(['added' => [], 'removed' => ['extra'], 'changed' => []], $reply->payload);
    }

    public function test_meta_save_without_effective_change_does_not_log(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $detail = $task->detail()->create(['meta' => ['vendor' => 'old']]);
        $this->actingAs($user);

        TaskDetail::find($detail->id)->update(['meta' => ['vendor' => 'old']]);

        $this->assertSame(0, $task->replies()->where('type', TaskActivityType::MetaChange->value)->count());
    }

    public function test_project_settings_change_logs_a_settings_change_activity_row(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $user->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 24],
        ]);
        $this->actingAs($user);

        Project::find($project->id)->update([
            'settings' => ['sla' => 48, 'deadline' => now()->addDays(5)->format('Y-m-d')],
        ]);

        $reply = Reply::query()
            ->where('repliable_type', Project::class)
            ->where('repliable_id', $project->id)
            ->where('type', TaskActivityType::SettingsChange->value)
            ->whereNotNull('payload->changed')
            ->firstOrFail();

        $this->assertEquals(['sla', 'deadline'], $reply->payload['changed']);
        $this->assertSame($user->id, $reply->user_id);
    }

    public function test_project_create_does_not_log_settings_change(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $project = Project::factory()->create([
            'owner_id' => $user->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 24],
        ]);

        $this->assertSame(
            0,
            Reply::query()->where('repliable_type', Project::class)
                ->where('repliable_id', $project->id)
                ->where('type', TaskActivityType::SettingsChange->value)
                ->whereNotNull('payload->changed')
                ->count(),
        );
    }

    public function test_render_dispatches_meta_and_settings_renderers(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create();
        $logger = app(ActivityLogger::class);

        $metaReply = $logger->system($task, $user, TaskActivityType::MetaChange, [
            'added' => ['vendor'],
            'removed' => [],
            'changed' => ['sla'],
        ]);
        $settingsReply = $logger->system($task, $user, TaskActivityType::SettingsChange, [
            'changed' => ['sla', 'deadline'],
        ]);

        $metaRendered = $logger->render($metaReply);
        $settingsRendered = $logger->render($settingsReply);

        $this->assertSame('تغییر دیتای سفارشی', $metaRendered['label']);
        $this->assertStringContainsString('vendor', $metaRendered['body']);
        $this->assertSame('تغییر تنظیمات پروژه', $settingsRendered['label']);
        $this->assertStringContainsString('SLA', $settingsRendered['body']);
        $this->assertStringContainsString('سقف مهلت', $settingsRendered['body']);
    }

    private function renderReply(TaskActivityType $type, array $attributes = []): array
    {
        return app(ActivityLogger::class)->render(new Reply(['type' => $type] + $attributes));
    }

    public function test_render_covers_every_activity_type_with_its_matching_renderer_label(): void
    {
        $labels = [
            'comment' => 'نظر',
            'status_change' => 'تغییر وضعیت',
            'assignment' => 'واگذاری',
            'archive' => 'بایگانی',
            'approval' => 'تأیید مدیر پروژه',
            'attachment' => 'پیوست',
            'responsible_change' => 'تغییر جوابگو',
            'department_change' => 'تغییر دپارتمان',
            'state_change' => 'تعیین تکلیف',
            'deadline_change' => 'تغییر مهلت',
            'priority_change' => 'تغییر اولویت',
            'label_change' => 'تغییر برچسب‌ها',
            'project_change' => 'تغییر پروژه',
            'meta_change' => 'تغییر دیتای سفارشی',
            'settings_change' => 'تغییر تنظیمات پروژه',
            'workflow_step' => 'چرخه کاری',
        ];
        $payloads = [
            'comment' => ['body' => 'متن نظر'],
            'status_change' => ['payload' => ['from' => 'todo', 'to' => 'done']],
            'assignment' => ['payload' => ['to' => null]],
            'archive' => ['payload' => ['archived' => true]],
            'approval' => ['payload' => ['approved' => true]],
            'attachment' => ['files' => [['name' => 'a.pdf']]],
            'responsible_change' => ['payload' => ['to' => null]],
            'department_change' => ['payload' => ['to' => null]],
            'state_change' => ['payload' => ['to' => null]],
            'deadline_change' => ['payload' => ['from' => null, 'to' => null]],
            'priority_change' => ['payload' => ['to' => null]],
            'label_change' => ['payload' => ['added' => ['x'], 'removed' => []]],
            'project_change' => ['payload' => ['from' => null, 'to' => null]],
            'meta_change' => ['payload' => ['added' => ['a'], 'removed' => [], 'changed' => []]],
            'settings_change' => ['payload' => ['changed' => ['sla']]],
            'workflow_step' => ['payload' => ['event' => 'started', 'step' => 0, 'label' => 'مرحله اول', 'cycle' => 'چرخه آزمایشی']],
        ];

        foreach (TaskActivityType::cases() as $type) {
            $rendered = $this->renderReply($type, $payloads[$type->value]);

            $this->assertSame($labels[$type->value], $rendered['label']);
            $this->assertNotSame('', $rendered['icon']);
            $this->assertNotSame('', $rendered['body']);
        }
    }

    public function test_status_change_renderer_falls_back_to_unknown_label_for_unresolvable_status(): void
    {
        $cleared = $this->renderReply(TaskActivityType::StatusChange, ['payload' => ['from' => null, 'to' => 'done']]);
        $invalid = $this->renderReply(TaskActivityType::StatusChange, ['payload' => ['from' => 'todo', 'to' => 'bogus']]);

        $this->assertSame('وضعیت از «نامشخص» به «' . TaskStatus::Done->getLabel() . '» تغییر کرد.', $cleared['body']);
        $this->assertSame('وضعیت از «' . TaskStatus::Todo->getLabel() . '» به «نامشخص» تغییر کرد.', $invalid['body']);
    }

    public function test_deadline_change_renderer_branches_between_set_changed_and_removed(): void
    {
        $from = toJalali('2026-08-10', 'j F Y');
        $to = toJalali('2026-08-20', 'j F Y');

        $set = $this->renderReply(TaskActivityType::DeadlineChange, ['payload' => ['from' => null, 'to' => '2026-08-20']]);
        $changed = $this->renderReply(TaskActivityType::DeadlineChange, ['payload' => ['from' => '2026-08-10', 'to' => '2026-08-20']]);
        $removed = $this->renderReply(TaskActivityType::DeadlineChange, ['payload' => ['from' => '2026-08-10', 'to' => null]]);

        $this->assertSame("مهلت انجام «{$to}» تعیین شد.", $set['body']);
        $this->assertSame("مهلت انجام از «{$from}» به «{$to}» تغییر کرد.", $changed['body']);
        $this->assertSame('مهلت انجام حذف شد.', $removed['body']);
    }

    public function test_assignment_renderer_covers_handover_removal_and_audience_add(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $handover = $this->renderReply(TaskActivityType::Assignment, ['payload' => ['to' => $user->id]]);
        $removal = $this->renderReply(TaskActivityType::Assignment, ['payload' => ['to' => null]]);
        $audience = $this->renderReply(TaskActivityType::Assignment, ['payload' => ['added' => [$user->id], 'removed' => []]]);

        $this->assertSame("مسئولیت وظیفه به «{$user->name}» واگذار شد.", $handover['body']);
        $this->assertSame('مسئول وظیفه حذف شد.', $removal['body']);
        $this->assertSame('عضو افزوده شد: ' . $user->name, $audience['body']);
    }

    public function test_responsible_and_department_renderers_resolve_target_labels_or_fall_back_to_removed(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Department::create(['code' => 'ZZT', 'name' => 'دپارتمان آزمایشی', 'description' => 'برچسب دپارتمان آزمایشی']);

        $responsible = $this->renderReply(TaskActivityType::ResponsibleChange, ['payload' => ['to' => $user->id]]);
        $responsibleGone = $this->renderReply(TaskActivityType::ResponsibleChange, ['payload' => ['to' => 99999999]]);
        $department = $this->renderReply(TaskActivityType::DepartmentChange, ['payload' => ['to' => 'ZZT']]);
        $departmentGone = $this->renderReply(TaskActivityType::DepartmentChange, ['payload' => ['to' => 'ZZQ']]);

        $this->assertSame("جوابگوی وظیفه به «{$user->name}» تغییر کرد.", $responsible['body']);
        $this->assertSame('جوابگوی وظیفه حذف شد.', $responsibleGone['body']);
        $this->assertSame('دپارتمان وظیفه به «برچسب دپارتمان آزمایشی» تغییر کرد.', $department['body']);
        $this->assertSame('دپارتمان وظیفه حذف شد.', $departmentGone['body']);
    }

    public function test_state_change_renderer_branches_icon_and_body_per_state(): void
    {
        $perState = [
            'extension' => ['calendar_month', TaskState::Extension->getLabel()],
            'suspension' => ['pause_circle', TaskState::Suspension->getLabel()],
            'completion' => ['check_circle', TaskState::Completion->getLabel()],
        ];

        foreach ($perState as $value => [$icon, $label]) {
            $rendered = $this->renderReply(TaskActivityType::StateChange, ['payload' => ['to' => $value]]);

            $this->assertSame($icon, $rendered['icon']);
            $this->assertSame("تعیین تکلیف وظیفه به «{$label}» تغییر کرد.", $rendered['body']);
        }

        $removed = $this->renderReply(TaskActivityType::StateChange, ['payload' => ['to' => null]]);
        $this->assertSame('rule', $removed['icon']);
        $this->assertSame('تعیین تکلیف وظیفه حذف شد.', $removed['body']);
    }

    public function test_priority_change_renderer_resolves_enum_label_and_falls_back_to_removed(): void
    {
        $set = $this->renderReply(TaskActivityType::PriorityChange, ['payload' => ['to' => 'high']]);
        $cleared = $this->renderReply(TaskActivityType::PriorityChange, ['payload' => ['to' => 'bogus']]);

        $this->assertSame('اولویت وظیفه به «' . TaskPriority::High->getLabel() . '» تغییر کرد.', $set['body']);
        $this->assertSame('اولویت وظیفه حذف شد.', $cleared['body']);
    }

    public function test_project_change_renderer_names_the_from_and_to_projects(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $from = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'name' => 'پروژه مبدأ']);
        $to = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => [], 'name' => 'پروژه مقصد']);

        $moved = $this->renderReply(TaskActivityType::ProjectChange, ['payload' => ['from' => $from->id, 'to' => $to->id]]);
        $attached = $this->renderReply(TaskActivityType::ProjectChange, ['payload' => ['from' => null, 'to' => $to->id]]);
        $detached = $this->renderReply(TaskActivityType::ProjectChange, ['payload' => ['from' => $from->id, 'to' => null]]);

        $this->assertSame("وظیفه از پروژه «{$from->name}» به «{$to->name}» منتقل شد.", $moved['body']);
        $this->assertSame("وظیفه به پروژه «{$to->name}» منتقل شد.", $attached['body']);
        $this->assertSame("وظیفه از پروژه «{$from->name}» خارج شد.", $detached['body']);
    }

    public function test_label_and_attachment_renderers_join_multiple_entries(): void
    {
        $labels = $this->renderReply(TaskActivityType::LabelChange, ['payload' => ['added' => ['فوری', 'بازنگری'], 'removed' => ['منتظر']]]);
        $attachments = $this->renderReply(TaskActivityType::Attachment, ['files' => [['name' => 'a.pdf'], ['name' => 'b.pdf']]]);

        $this->assertSame('برچسب افزوده شد: فوری، بازنگری — برچسب حذف شد: منتظر', $labels['body']);
        $this->assertSame('پیوست شد: a.pdf، b.pdf', $attachments['body']);
    }

    public function test_archive_and_approval_renderers_branch_on_their_payload_flag(): void
    {
        $this->assertSame('وظیفه بایگانی شد.', $this->renderReply(TaskActivityType::Archive, ['payload' => ['archived' => true]])['body']);
        $this->assertSame('وظیفه از بایگانی خارج شد.', $this->renderReply(TaskActivityType::Archive, ['payload' => ['archived' => false]])['body']);
        $this->assertSame('وظیفه توسط مدیر پروژه تأیید شد.', $this->renderReply(TaskActivityType::Approval, ['payload' => ['approved' => true]])['body']);
        $this->assertSame('تأیید وظیفه برداشته شد.', $this->renderReply(TaskActivityType::Approval, ['payload' => ['approved' => false]])['body']);
    }

    public function test_workflow_step_renderer_branches_body_per_event_and_appends_the_note_when_present(): void
    {
        $payload = ['step' => 0, 'label' => 'بازبینی', 'cycle' => 'چرخه استاندارد'];

        $started = $this->renderReply(TaskActivityType::WorkflowStep, ['payload' => $payload + ['event' => 'started']]);
        $advanced = $this->renderReply(TaskActivityType::WorkflowStep, ['payload' => $payload + ['event' => 'advanced']]);
        $rejected = $this->renderReply(TaskActivityType::WorkflowStep, ['payload' => $payload + ['event' => 'rejected']]);
        $completed = $this->renderReply(TaskActivityType::WorkflowStep, ['payload' => $payload + ['event' => 'completed']]);
        $cancelled = $this->renderReply(TaskActivityType::WorkflowStep, ['payload' => $payload + ['event' => 'cancelled']]);

        $this->assertSame('چرخه «چرخه استاندارد» با مرحله «بازبینی» آغاز شد.', $started['body']);
        $this->assertSame('مرحله «بازبینی» در چرخه «چرخه استاندارد» تکمیل شد.', $advanced['body']);
        $this->assertSame('مرحله «بازبینی» در چرخه «چرخه استاندارد» رد شد.', $rejected['body']);
        $this->assertSame('چرخه «چرخه استاندارد» تکمیل شد.', $completed['body']);
        $this->assertSame('چرخه «چرخه استاندارد» لغو شد.', $cancelled['body']);

        $withNote = $this->renderReply(TaskActivityType::WorkflowStep, ['payload' => $payload + ['event' => 'advanced', 'note' => 'یادداشت آزمایشی']]);
        $this->assertSame('مرحله «بازبینی» در چرخه «چرخه استاندارد» تکمیل شد. یادداشت: یادداشت آزمایشی', $withNote['body']);
    }

    public function test_updating_a_mapped_task_detail_field_logs_the_mapped_activity_type(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null, 'deadline' => null]);
        $detail = $task->detail()->create(['collaborators' => []]);
        $this->actingAs($user);
        Queue::fake();

        TaskDetail::find($detail->id)->update(['state' => 'suspension']);

        $reply = $task->replies()->where('type', TaskActivityType::StateChange->value)->firstOrFail();
        $this->assertEquals(['from' => null, 'to' => 'suspension'], $reply->payload);
        $this->assertSame($user->id, $reply->user_id);
        Queue::assertNotPushed(SyncTaskCalendarEventJob::class);
    }

    public function test_updating_an_unmapped_task_field_inserts_no_activity_row(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null, 'deadline' => null, 'rank' => 'm']);
        $this->actingAs($user);
        Queue::fake();

        Task::find($task->id)->update(['rank' => 'z']);

        $this->assertSame(1, $task->replies()->count());
        Queue::assertNotPushed(SyncTaskCalendarEventJob::class);
    }

    public function test_task_calendar_trigger_change_dispatches_sync_task_calendar_event_job(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null, 'deadline' => null]);
        $this->actingAs($user);
        Queue::fake();
        $this->travel(6)->seconds();

        $deadline = now()->addDays(20)->setTime(9, 0);
        Task::find($task->id)->update(['deadline' => $deadline]);

        Queue::assertPushed(SyncTaskCalendarEventJob::class, fn(SyncTaskCalendarEventJob $job) => $job->taskId === $task->id);
        $reply = $task->replies()->where('type', TaskActivityType::DeadlineChange->value)->firstOrFail();
        $this->assertEquals(['from' => null, 'to' => $deadline->toDateString()], $reply->payload);
    }

    public function test_task_change_outside_calendar_triggers_does_not_dispatch_sync_job(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null, 'deadline' => null]);
        $this->actingAs($user);
        Queue::fake();

        Task::find($task->id)->update(['priority' => 'high']);

        $reply = $task->replies()->where('type', TaskActivityType::PriorityChange->value)->firstOrFail();
        $this->assertEquals(['from' => null, 'to' => 'high'], $reply->payload);
        Queue::assertNotPushed(SyncTaskCalendarEventJob::class);
    }

    public function test_task_detail_calendar_trigger_change_dispatches_sync_job_for_the_parent_task(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($user)->withStatus('todo')->create(['project_id' => null, 'deadline' => null]);
        $detail = $task->detail()->create(['collaborators' => []]);
        $responsible = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);

        TaskDetail::find($detail->id)->update(['responsible_user_id' => $responsible->id]);

        Queue::assertPushed(SyncTaskCalendarEventJob::class, fn(SyncTaskCalendarEventJob $job) => $job->taskId === $task->id);
        $reply = $task->replies()->where('type', TaskActivityType::ResponsibleChange->value)->firstOrFail();
        $this->assertEquals(['from' => null, 'to' => $responsible->id], $reply->payload);
    }
}
