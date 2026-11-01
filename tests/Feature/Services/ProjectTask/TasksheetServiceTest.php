<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Models\Reply;
use App\Enums\TaskActivityType;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\TaskMetrics;
use App\Services\ProjectTask\TasksheetService;
use Carbon\Carbon;
use App\Services\ProjectTask\TasksheetShareService;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers TasksheetService::report() event-sourcing correctness: reopen-still-counts,
 * actor attribution, project exclusion-by-construction, zero-state, current-deadline
 * on-time%, and the window-relevant narrative denominator — plus TasksheetShareService::shareWithManager()
 * recipient resolution, guard failures, and notification bodies.
 */
class TasksheetServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Auth::logout();
        Cache::flush();
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

    private function window(): array
    {
        return [now()->startOfDay(), now()->endOfDay()];
    }

    public function test_task_completed_then_reopened_in_window_no_longer_counts_as_completed(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create();

        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);
        Task::find($task->id)->update(['status' => 'todo']);

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(0, $report['scorecard']['completed']['value']);
    }

    public function test_completion_is_attributed_to_the_events_actor_not_the_current_assignee(): void
    {
        $personA = User::factory()->create(['status' => 'active']);
        $personB = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->create([
            'user_id' => $personA->id,
            'assigned_to' => $personA->id,
            'status' => 'todo',
        ]);

        Auth::login($personA);
        Task::find($task->id)->update(['status' => 'done']);
        Task::find($task->id)->update(['assigned_to' => $personB->id]);

        [$start, $end] = $this->window();
        $service = app(TasksheetService::class);

        $reportA = $service->report($personA->fresh(), $start, $end);
        $reportB = $service->report($personB->fresh(), $start, $end);

        $this->assertSame(1, $reportA['scorecard']['completed']['value']);
        $this->assertSame(0, $reportB['scorecard']['completed']['value']);
    }

    public function test_project_owner_with_no_personal_task_activity_in_window_is_excluded_from_own_projects_list(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $standaloneTask = Task::factory()->ownedBy($owner)->withStatus('todo')->create();
        Auth::login($owner);
        Task::find($standaloneTask->id)->update(['status' => 'done']);

        $project = Project::factory()->create(['owner_id' => $owner->id]);
        Task::factory()->forProject($project)->create([
            'user_id' => $other->id,
            'assigned_to' => $other->id,
            'status' => 'todo',
        ]);

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($owner->fresh(), $start, $end);

        $this->assertSame(1, $report['scorecard']['completed']['value']);
        $this->assertSame([], $report['projects']);
        $this->assertNotNull($report['standalone']);
    }

    public function test_empty_window_for_brand_new_user_returns_zero_state_without_throwing(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($user, $start, $end);

        $this->assertSame(0, $report['scorecard']['completed']['value']);
        $this->assertSame([], $report['projects']);
        $this->assertNull($report['standalone']);
        $this->assertFalse($report['has_baseline']);
    }

    public function test_on_time_percent_uses_current_deadline_not_the_deadline_at_completion_time(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create([
            'deadline' => now()->addDay(),
        ]);

        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);
        Task::whereKey($task->id)->toBase()->update(['deadline' => now()->subDay()]);

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(1, $report['scorecard']['on_time_percent']['sample']);
        $this->assertSame(0.0, $report['scorecard']['on_time_percent']['value']);
    }

    public function test_narrative_denominator_is_the_window_relevant_candidate_count_not_an_unbounded_all_time_count(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $otherOwner = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($subject)->withStatus('todo')->count(3)->create();

        $foreignTask = Task::factory()->create([
            'user_id' => $otherOwner->id,
            'assigned_to' => $otherOwner->id,
            'status' => 'todo',
        ]);
        app(ActivityLogger::class)->comment($foreignTask, $subject, 'یادداشت روی وظیفهٔ دیگری');

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertStringContainsString('۰ از ۳', $report['narrative']);
        $this->assertStringNotContainsString('۰ از ۴', $report['narrative']);
    }

    public function test_task_only_commented_on_is_excluded_from_scorecard_and_from_the_live_project_drilldown(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $task = Task::factory()->forProject($project)->create([
            'user_id' => $owner->id,
            'assigned_to' => $owner->id,
            'status' => 'todo',
            'deadline' => now()->subDay(),
        ]);

        $logger = app(ActivityLogger::class);
        $logger->comment($task, $subject, 'یادداشت روی وظیفهٔ دیگری');
        $logger->system($task, $owner, TaskActivityType::Approval, ['approved' => true]);

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(0, $report['scorecard']['still_overdue']);
        $this->assertSame(0, $report['scorecard']['approvals_received']['value']);

        $this->assertNull(collect($report['projects'])->firstWhere('project_id', $project->id));
    }

    public function test_task_completed_reopened_and_completed_again_counts_once_using_the_latest_event(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create();

        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);
        Task::find($task->id)->update(['status' => 'todo']);
        Task::find($task->id)->update(['status' => 'done']);

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(1, $report['scorecard']['completed']['value']);
    }

    public function test_soft_deleted_project_mid_window_still_groups_the_completed_task(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $subject->id]);
        $task = Task::factory()->forProject($project)->create([
            'user_id' => $subject->id,
            'assigned_to' => $subject->id,
            'status' => 'todo',
        ]);

        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);
        $project->delete();

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $group = collect($report['projects'])->firstWhere('project_id', $project->id);
        $this->assertNotNull($group);
        $this->assertTrue($group['is_archived']);
        $this->assertSame(1, $group['completed']);
    }

    public function test_completing_a_task_you_do_not_own_is_credited_to_its_doer_not_to_you(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $subject = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $task = Task::factory()->forProject($project)->create([
            'user_id' => $owner->id,
            'assigned_to' => $owner->id,
            'status' => 'todo',
        ]);

        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(0, $report['scorecard']['completed']['value']);
        $this->assertNull(collect($report['projects'])->firstWhere('project_id', $project->id));

        $ownerReport = app(TasksheetService::class)->report($owner->fresh(), $start, $end);
        $this->assertSame(1, $ownerReport['scorecard']['completed']['value']);
    }

    public function test_baseline_delta_is_suppressed_when_the_prior_window_has_fewer_than_three_completions(): void
    {
        $subject = User::factory()->create(['status' => 'active']);

        $this->completeTaskAt($subject, now()->subDay()->setTime(1, 0));
        $this->completeTaskAt($subject, now()->subDay()->setTime(2, 0));

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertFalse($report['has_baseline']);
    }

    public function test_baseline_delta_is_shown_when_the_prior_window_has_at_least_three_completions(): void
    {
        $subject = User::factory()->create(['status' => 'active']);

        $this->completeTaskAt($subject, now()->subDay()->setTime(1, 0));
        $this->completeTaskAt($subject, now()->subDay()->setTime(2, 0));
        $this->completeTaskAt($subject, now()->subDay()->setTime(3, 0));

        [$start, $end] = $this->window();
        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertTrue($report['has_baseline']);
    }

    public function test_report_is_invalidated_when_a_new_completion_bumps_the_model_cache_version(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        [$start, $end] = $this->window();

        $reportBefore = app(TasksheetService::class)->report($subject->fresh(), $start, $end);
        $this->assertSame(0, $reportBefore['scorecard']['completed']['value']);

        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create();
        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);

        $reportAfter = app(TasksheetService::class)->report($subject->fresh(), $start, $end);
        $this->assertSame(1, $reportAfter['scorecard']['completed']['value']);
    }

    public function test_activity_feed_groups_events_by_day_and_falls_back_to_system_actor_name(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create();

        $logger = app(ActivityLogger::class);
        $logger->system($task, null, TaskActivityType::Approval, ['approved' => true]);
        Auth::login($subject);
        $logger->comment($task, $subject, 'یادداشت تست');

        [$start, $end] = $this->window();
        $feed = app(TasksheetService::class)->activityFeed($subject->fresh(), $start, $end, 1, 30);

        $this->assertCount(1, $feed['days']);
        $actorNames = array_column($feed['days'][0]['items'], 'actor_name');
        $this->assertContains('سیستم', $actorNames);
        $this->assertContains($subject->name, $actorNames);
        $this->assertFalse($feed['has_more']);
    }

    private function completeTaskAt(User $subject, \Carbon\Carbon $completedAt): void
    {
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create();
        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);

        DB::table('replies')
            ->where('repliable_id', $task->id)
            ->where('repliable_type', Task::class)
            ->where('type', TaskActivityType::StatusChange->value)
            ->update(['created_at' => $completedAt]);
    }

    private function userInDept(string $deptCode, string $position): User
    {
        Department::firstOrCreate(['code' => $deptCode], ['name' => $deptCode, 'description' => '']);
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $deptCode, 'position' => $position]);

        return $user;
    }

    private function tasksheetNotificationsFor(int $userId): Collection
    {
        return DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('type', FilamentDatabaseNotification::class)
            ->get();
    }

    public function test_share_with_an_explicit_recipient_persists_the_notification_for_that_recipient(): void
    {
        $subject = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $manager = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $result = app(TasksheetShareService::class)->shareWithManager($subject, $manager);

        $this->assertTrue($result['success']);
        $this->assertTrue($manager->is($result['manager']));
        $this->assertSame('گزارش برای گیرندهٔ انتخاب‌شده ارسال شد.', $result['message']);

        $rows = $this->tasksheetNotificationsFor($manager->id);
        $this->assertCount(1, $rows);
        $data = json_decode($rows[0]->data, true);
        $this->assertSame('گزارش تسک‌شیت به اشتراک گذاشته شد', $data['title']);
        $this->assertSame("{$subject->name} یک گزارش تسک‌شیت با شما به اشتراک گذاشت.", $data['body']);
    }

    public function test_share_without_a_recipient_resolves_the_departments_highest_ranking_user(): void
    {
        $subject = $this->userInDept('ZZT', 'employee');
        $manager = $this->userInDept('ZZT', 'manager');
        $this->userInDept('ZZT', 'supervisor');

        $result = app(TasksheetShareService::class)->shareWithManager($subject);

        $this->assertTrue($result['success']);
        $this->assertTrue($manager->is($result['manager']), 'the manager rank must beat the supervisor rank');
        $this->assertSame('گزارش برای مدیرِ همین کاربر ارسال شد.', $result['message']);
        $this->assertCount(1, $this->tasksheetNotificationsFor($manager->id));
    }

    public function test_share_fails_when_no_manager_is_resolvable(): void
    {
        $service = app(TasksheetShareService::class);

        $subjectWithoutDept = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $result = $service->shareWithManager($subjectWithoutDept);
        $this->assertFalse($result['success']);
        $this->assertNull($result['manager']);
        $this->assertSame('مدیر این کاربر به‌طور خودکار شناسایی نشد.', $result['message']);

        $loneSubject = $this->userInDept('ZZU', 'contractor');
        $result = $service->shareWithManager($loneSubject);
        $this->assertFalse($result['success']);
        $this->assertNull($result['manager']);
        $this->assertSame('مدیر این کاربر به‌طور خودکار شناسایی نشد.', $result['message']);
    }

    public function test_share_rejects_the_subject_as_their_own_recipient(): void
    {
        $subject = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $result = app(TasksheetShareService::class)->shareWithManager($subject, $subject);

        $this->assertFalse($result['success']);
        $this->assertNull($result['manager']);
        $this->assertSame('گیرنده نمی‌تواند خود کاربر باشد.', $result['message']);
        $this->assertCount(0, $this->tasksheetNotificationsFor($subject->id), 'the self-share guard must never persist a notification');
    }

    public function test_share_requested_by_another_user_uses_the_management_body_not_the_self_share_body(): void
    {
        $subject = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $manager = User::factory()->create(['status' => 'active', 'role' => 'user']);

        app(TasksheetShareService::class)->shareWithManager($subject, $manager, $requester);

        $rows = $this->tasksheetNotificationsFor($manager->id);
        $this->assertCount(1, $rows);
        $this->assertSame(
            "گزارش تسک‌شیت {$subject->name} توسط مدیریت با شما به اشتراک گذاشته شد.",
            json_decode($rows[0]->data, true)['body']
        );
    }

    private function doneEvent(Task $task, ?User $actor, ?int $owner = null): void
    {
        Reply::factory()->create([
            'repliable_type' => Task::class,
            'repliable_id' => $task->id,
            'user_id' => $actor?->id,
            'body' => null,
            'type' => TaskActivityType::StatusChange,
            'payload' => ['from' => 'in-progress', 'to' => 'done', ...($owner ? ['owner' => $owner] : [])],
        ]);
    }

    public function test_completion_credit_follows_payload_owner_with_a_legacy_fallback_to_the_task_doer(): void
    {
        $doer = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $bystander = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $legacy = Task::factory()->create(['user_id' => $bystander->id, 'assigned_to' => $doer->id, 'status' => 'done', 'approved_at' => now()]);
        $stamped = Task::factory()->create(['user_id' => $bystander->id, 'assigned_to' => $bystander->id, 'status' => 'done', 'approved_at' => now()]);
        $this->doneEvent($legacy, $bystander);
        $this->doneEvent($stamped, $bystander, $doer->id);
        [$start, $end] = $this->window();
        $service = app(TasksheetService::class);

        $this->assertSame(2, $service->report($doer->fresh(), $start, $end)['scorecard']['completed']['value']);
        $this->assertSame(0, $service->report($bystander->fresh(), $start, $end)['scorecard']['completed']['value']);
    }

    public function test_tasks_delegated_away_are_not_in_the_delegators_owned_scope(): void
    {
        $creator = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'user']);
        Task::factory()->create(['user_id' => $creator->id, 'assigned_to' => $assignee->id, 'status' => 'in-progress', 'deadline' => now()->addDays(20)]);
        [$start, $end] = $this->window();
        $service = app(TasksheetService::class);

        $this->assertSame(0, $service->report($creator->fresh(), $start, $end)['scorecard']['in_progress']);
        $this->assertSame(1, $service->report($assignee->fresh(), $start, $end)['scorecard']['in_progress']);
    }

    public function test_task_finished_after_noon_on_its_due_day_counts_as_on_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 15:00:00'));
        $subject = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create(['deadline' => Carbon::parse('2026-10-02 12:00:00')]);
        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);
        [$start, $end] = $this->window();

        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(100.0, (float) $report['scorecard']['on_time_percent']['value']);
        Carbon::setTestNow();
    }

    public function test_upcoming_deadline_window_uses_the_due_soon_constant(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 09:00:00'));
        $subject = User::factory()->create(['status' => 'active']);
        Task::factory()->ownedBy($subject)->withStatus('todo')->create(['deadline' => now()->addDays(TaskMetrics::DUE_SOON_DAYS - 1)]);
        Task::factory()->ownedBy($subject)->withStatus('todo')->create(['deadline' => now()->addDays(TaskMetrics::DUE_SOON_DAYS + 2)]);
        [$start, $end] = $this->window();

        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(1, $report['scorecard']['upcoming_deadline']);
        Carbon::setTestNow();
    }

    public function test_project_row_flags_ignore_tasks_the_subject_does_not_own(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $mate = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $ownTask = Task::factory()->forProject($project)->create(['user_id' => $owner->id, 'assigned_to' => $owner->id, 'status' => 'todo']);
        Auth::login($owner);
        Task::find($ownTask->id)->update(['status' => 'done']);
        $theirs = Task::factory()->forProject($project)->create(['user_id' => $mate->id, 'assigned_to' => $mate->id, 'status' => 'in-progress', 'deadline' => now()->subDays(2)]);
        app(ActivityLogger::class)->comment($theirs, $owner, 'نظر');
        [$start, $end] = $this->window();

        $report = app(TasksheetService::class)->report($owner->fresh(), $start, $end);

        $group = collect($report['projects'])->firstWhere('project_id', $project->id);
        $this->assertNotNull($group);
        $this->assertSame(0, $group['still_overdue']);
        $this->assertSame(0, $group['in_progress']);
        $this->assertSame(0, $report['scorecard']['still_overdue']);
    }

    public function test_trashed_completed_tasks_stay_in_the_report_with_a_trashed_marker(): void
    {
        $subject = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($subject)->withStatus('todo')->create();
        Auth::login($subject);
        Task::find($task->id)->update(['status' => 'done']);
        Task::find($task->id)->delete();
        [$start, $end] = $this->window();

        $report = app(TasksheetService::class)->report($subject->fresh(), $start, $end);

        $this->assertSame(1, $report['scorecard']['completed']['value']);
        $this->assertTrue($report['standalone']['tasks'][0]['trashed']);
    }

    public function test_baseline_window_is_the_equal_length_period_ending_just_before_the_start(): void
    {
        $start = \Carbon\Carbon::parse('2031-05-10 00:00:00');
        $end = \Carbon\Carbon::parse('2031-05-16 23:59:59');

        $baseline = TasksheetService::baselineWindow($start, $end);

        $this->assertTrue($baseline['end']->lt($start));
        $this->assertSame('2031-05-09 23:59:59', $baseline['end']->toDateTimeString());
        $this->assertSame((int) $start->diffInSeconds($end), (int) $baseline['start']->diffInSeconds($baseline['end']));
    }

    public function test_a_non_subject_viewer_gets_personal_tasks_masked_and_comment_bodies_hidden(): void
    {
        $subject = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $viewer = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $task = Task::factory()->ownedBy($subject)->withStatus('in-progress')->create(['title' => 'Private plan', 'deadline' => now()->addDays(20)]);
        Auth::login($subject);
        app(ActivityLogger::class)->comment($task, $subject, 'confidential body');
        [$start, $end] = $this->window();
        $service = app(TasksheetService::class);

        $own = $service->report($subject->fresh(), $start, $end, $subject);
        $seen = $service->report($subject->fresh(), $start, $end, $viewer);

        $this->assertSame('Private plan', $own['standalone']['tasks'][0]['title']);
        $this->assertSame('وظیفه شخصی', $seen['standalone']['tasks'][0]['title']);
        $this->assertTrue($seen['standalone']['tasks'][0]['masked']);

        $ownFeed = $service->activityFeed($subject->fresh(), $start, $end, 1, 30, $subject);
        $seenFeed = $service->activityFeed($subject->fresh(), $start, $end, 1, 30, $viewer);
        $dump = fn(array $feed) => json_encode($feed['days'], JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('confidential body', $dump($ownFeed));
        $this->assertStringNotContainsString('confidential body', $dump($seenFeed));
        $this->assertStringNotContainsString('Private plan', $dump($seenFeed));
    }

    public function test_project_members_viewing_a_report_see_that_projects_tasks_unmasked(): void
    {
        $subject = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $member = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $outsider = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $project = Project::factory()->create(['owner_id' => $subject->id, 'member_ids' => [$member->id], 'departments' => []]);
        Task::factory()->forProject($project)->create(['user_id' => $subject->id, 'assigned_to' => $subject->id, 'status' => 'in-progress', 'title' => 'Shared work', 'deadline' => now()->addDays(20)]);
        [$start, $end] = $this->window();
        $service = app(TasksheetService::class);

        $asMember = $service->report($subject->fresh(), $start, $end, $member);
        $asOutsider = $service->report($subject->fresh(), $start, $end, $outsider);

        $this->assertSame('Shared work', $asMember['projects'][0]['tasks'][0]['title']);
        $this->assertSame($project->name, $asMember['projects'][0]['project_name']);
        $this->assertSame('پروژهٔ محدود', $asOutsider['projects'][0]['project_name']);
        $this->assertSame('وظیفهٔ محدود', $asOutsider['projects'][0]['tasks'][0]['title']);
    }

    public function test_manager_is_the_department_head_and_a_heads_manager_is_the_parent_head(): void
    {
        $parentHead = $this->userInDept('ZZP', 'manager');
        $this->userInDept('ZZP', 'employee');
        $headA = $this->userInDept('ZZC', 'manager');
        $headB = $this->userInDept('ZZC', 'manager');
        $member = $this->userInDept('ZZC', 'employee');
        Department::where('code', 'ZZC')->update(['subordinate_to' => 'ZZP']);
        Cache::flush();

        $this->assertEqualsCanonicalizing([$headA->id, $headB->id], TasksheetShareService::managersFor($member->fresh())->pluck('id')->all());
        $this->assertSame($headA->id < $headB->id ? $headA->id : $headB->id, TasksheetShareService::managerFor($member->fresh())->id);
        $this->assertSame([$parentHead->id], TasksheetShareService::managersFor($headA->fresh())->pluck('id')->all());
    }
}
