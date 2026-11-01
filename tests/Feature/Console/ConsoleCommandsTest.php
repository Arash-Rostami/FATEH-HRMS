<?php

namespace Tests\Feature\Console;

use App\Jobs\ReconcileEdge;
use App\Jobs\ReconcileNudge;
use App\Jobs\ReconcileReminderDue;
use App\Models\Channel;
use App\Models\Edge;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReminderDueNotification;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Carbon::setTestNow('2026-01-15 12:00:00');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Carbon::setTestNow(null);
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

    private function makeStale(string $stage, array $departments, int $user_id, int $hoursOld = 49): Suggestion
    {
        $suggestion = Suggestion::factory()->create([
            'stage' => $stage,
            'departments' => $departments,
            'user_id' => $user_id,
        ]);
        DB::table('suggestions')->where('id', $suggestion->id)->update([
            'updated_at' => Carbon::now()->subHours($hoursOld)->toDateTimeString(),
        ]);

        return $suggestion->fresh();
    }

    private function makeFresh(string $stage, array $departments, int $user_id): Suggestion
    {
        return Suggestion::factory()->create([
            'stage' => $stage,
            'departments' => $departments,
            'user_id' => $user_id,
        ]);
    }

    private function makeNotification(string $menuKey, User $user, int $daysOld): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => FilamentDatabaseNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['menu_key' => $menuKey]),
            'read_at' => null,
            'created_at' => Carbon::now()->subDays($daysOld)->toDateTimeString(),
            'updated_at' => Carbon::now()->subDays($daysOld)->toDateTimeString(),
        ]);

        return $id;
    }

    public function test_auto_resolve_stale_team_remarks_with_single_ma_department_fills_neutral_and_transitions_to_awaiting_decision(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('team_remarks', ['MA'], $user->id);

        Artisan::call('suggestions:auto-resolve-stale');

        $suggestion->refresh();
        $this->assertSame('awaiting_decision', $suggestion->stage);
        $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first();
        $this->assertNotNull($review);
        $this->assertSame('neutral', $review->feedback);
        $this->assertSame($user->id, $review->user_id);
        $this->assertFalse((bool) $review->complete);
        $this->assertStringContainsString('عدم پاسخ', $review->comments);
    }

    public function test_auto_resolve_stale_team_remarks_with_multiple_departments_transitions_to_dept_remarks(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('team_remarks', ['HR', 'IT'], $user->id);

        Artisan::call('suggestions:auto-resolve-stale');

        $suggestion->refresh();
        $this->assertSame('dept_remarks', $suggestion->stage);
        $this->assertSame(1, Review::where('suggestion_id', $suggestion->id)->count());
        $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'HR')->first();
        $this->assertNotNull($review);
        $this->assertSame('neutral', $review->feedback);
        $this->assertNull(Review::where('suggestion_id', $suggestion->id)->where('department_id', 'IT')->first());
    }

    public function test_auto_resolve_stale_team_remarks_preserves_incomplete_home_feedback(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('team_remarks', ['HR'], $user->id);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'HR',
            'feedback' => 'incomplete',
            'referral' => [],
            'user_id' => $user->id,
        ]);
        $reviewsBefore = Review::where('suggestion_id', $suggestion->id)->count();

        Artisan::call('suggestions:auto-resolve-stale');

        $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'HR')->first();
        $this->assertSame('incomplete', $review->feedback);
        $this->assertSame($reviewsBefore, Review::where('suggestion_id', $suggestion->id)->count());
    }

    public function test_auto_resolve_stale_team_remarks_preserves_existing_home_verdict(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('team_remarks', ['HR'], $user->id);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'HR',
            'feedback' => 'disagree',
            'referral' => [],
            'user_id' => $user->id,
        ]);

        Artisan::call('suggestions:auto-resolve-stale');

        $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'HR')->first();
        $this->assertSame('disagree', $review->feedback);
    }

    public function test_auto_resolve_stale_team_remarks_with_empty_departments_is_no_op(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('team_remarks', [], $user->id);
        $reviewsBefore = Review::where('suggestion_id', $suggestion->id)->count();

        Artisan::call('suggestions:auto-resolve-stale');

        $suggestion->refresh();
        $this->assertSame('team_remarks', $suggestion->stage);
        $this->assertSame($reviewsBefore, Review::where('suggestion_id', $suggestion->id)->count());
    }

    public function test_auto_resolve_fresh_team_remarks_is_not_touched(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeFresh('team_remarks', ['MA'], $user->id);
        $reviewsBefore = Review::where('suggestion_id', $suggestion->id)->count();

        Artisan::call('suggestions:auto-resolve-stale');

        $suggestion->refresh();
        $this->assertSame('team_remarks', $suggestion->stage);
        $this->assertSame($reviewsBefore, Review::where('suggestion_id', $suggestion->id)->count());
    }

    public function test_auto_resolve_stale_dept_remarks_with_no_reviews_fills_neutral_and_transitions_to_awaiting_decision(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('dept_remarks', ['HR', 'IT'], $user->id);

        Artisan::call('suggestions:auto-resolve-stale');

        $suggestion->refresh();
        $this->assertSame('awaiting_decision', $suggestion->stage);
        foreach (['HR', 'IT'] as $dept) {
            $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', $dept)->first();
            $this->assertNotNull($review);
            $this->assertSame('neutral', $review->feedback);
            $this->assertSame($user->id, $review->user_id);
        }
    }

    public function test_auto_resolve_stale_dept_remarks_preserves_existing_agree_and_fills_missing_neutral(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('dept_remarks', ['MA', 'HR'], $user->id);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'MA',
            'feedback' => 'agree',
            'referral' => [],
            'user_id' => $user->id,
        ]);

        Artisan::call('suggestions:auto-resolve-stale');

        $suggestion->refresh();
        $this->assertSame('accepted', $suggestion->stage);
        $maReview = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first();
        $this->assertSame('agree', $maReview->feedback);
        $hrReview = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'HR')->first();
        $this->assertSame('neutral', $hrReview->feedback);
    }

    public function test_auto_resolve_stale_dept_remarks_preserves_incomplete_feedback(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('dept_remarks', ['HR'], $user->id);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'HR',
            'feedback' => 'incomplete',
            'referral' => [],
            'user_id' => $user->id,
        ]);
        $reviewsBefore = Review::where('suggestion_id', $suggestion->id)->count();

        Artisan::call('suggestions:auto-resolve-stale');

        $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'HR')->first();
        $this->assertSame('incomplete', $review->feedback);
        $this->assertSame($reviewsBefore, Review::where('suggestion_id', $suggestion->id)->count());
    }

    public function test_auto_resolve_stale_dept_remarks_overwrites_unknown_feedback_with_neutral(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('dept_remarks', ['HR'], $user->id);
        Review::factory()->create([
            'suggestion_id' => $suggestion->id,
            'department_id' => 'HR',
            'feedback' => 'unknown',
            'referral' => [],
            'user_id' => $user->id,
        ]);

        Artisan::call('suggestions:auto-resolve-stale');

        $review = Review::where('suggestion_id', $suggestion->id)->where('department_id', 'HR')->first();
        $this->assertSame('neutral', $review->feedback);
    }

    public function test_auto_resolve_does_not_touch_already_terminal_stages(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suggestion = $this->makeStale('accepted', ['MA'], $user->id);
        $reviewsBefore = Review::where('suggestion_id', $suggestion->id)->count();

        Artisan::call('suggestions:auto-resolve-stale');

        $suggestion->refresh();
        $this->assertSame('accepted', $suggestion->stage);
        $this->assertSame($reviewsBefore, Review::where('suggestion_id', $suggestion->id)->count());
    }

    public function test_auto_resolve_with_only_fresh_rows_leaves_them_untouched(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $fresh = $this->makeFresh('team_remarks', ['MA'], $user->id);
        $freshReviews = Review::where('suggestion_id', $fresh->id)->count();

        $exit = Artisan::call('suggestions:auto-resolve-stale');

        $this->assertSame(0, $exit);
        $this->assertSame($freshReviews, Review::where('suggestion_id', $fresh->id)->count());
        $fresh->refresh();
        $this->assertSame('team_remarks', $fresh->stage);
    }

    public function test_auto_resolve_mixed_stale_and_fresh_suggestions_only_transitions_stale_ones(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $staleTeam = $this->makeStale('team_remarks', ['MA'], $user->id);
        $freshTeam = $this->makeFresh('team_remarks', ['MA'], $user->id);
        $staleDept = $this->makeStale('dept_remarks', ['HR', 'IT'], $user->id);

        Artisan::call('suggestions:auto-resolve-stale');

        $this->assertSame('awaiting_decision', $staleTeam->fresh()->stage);
        $this->assertSame('team_remarks', $freshTeam->fresh()->stage);
        $this->assertSame('awaiting_decision', $staleDept->fresh()->stage);
    }

    public function test_prune_stale_nudges_removes_old_nudge_rows_but_keeps_fresh_nudges_and_stale_bare_badge_rows(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $staleNudge = $this->makeNotification('dms:tickets:nudge', $user, 40);
        $freshNudge = $this->makeNotification('dms:tickets:nudge', $user, 5);
        $staleBadge = $this->makeNotification('dms:tickets', $user, 40);

        $exit = Artisan::call('notifications:prune-stale');

        $this->assertSame(0, $exit);
        $this->assertFalse(DB::table('notifications')->where('id', $staleNudge)->exists());
        $this->assertTrue(DB::table('notifications')->where('id', $freshNudge)->exists());
        $this->assertTrue(DB::table('notifications')->where('id', $staleBadge)->exists());
    }

    public function test_prune_stale_nudges_respects_custom_days_window(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $tenDayNudge = $this->makeNotification('dms:tickets:nudge', $user, 10);

        Artisan::call('notifications:prune-stale', ['--days' => 7]);

        $this->assertFalse(DB::table('notifications')->where('id', $tenDayNudge)->exists());
    }

    public function test_prune_stale_nudges_also_catches_legacy_reply_nudge_namespace_but_not_its_bare_badge_sibling(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $staleReplyNudge = $this->makeNotification('channels:reply-nudge', $user, 40);
        $staleReplyBadge = $this->makeNotification('channels:reply', $user, 40);

        Artisan::call('notifications:prune-stale');

        $this->assertFalse(DB::table('notifications')->where('id', $staleReplyNudge)->exists());
        $this->assertTrue(DB::table('notifications')->where('id', $staleReplyBadge)->exists());
    }

    public function test_prune_stale_nudges_rejects_non_positive_days(): void
    {
        $exit = Artisan::call('notifications:prune-stale', ['--days' => 0]);

        $this->assertSame(1, $exit);
    }

    public function test_prune_completed_reminders_removes_old_completed_rows_but_keeps_fresh_completed_and_active_reminders(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $staleCompleted = $this->makeReminder($user, ['completed_at' => now()->subDays(100)]);
        $freshCompleted = $this->makeReminder($user, ['completed_at' => now()->subDays(5)]);
        $staleActive = $this->makeReminder($user, ['due_at' => now()->subDays(100)]);

        $exit = Artisan::call('reminders:prune-completed');

        $this->assertSame(0, $exit);
        $this->assertFalse(Reminder::query()->whereKey($staleCompleted->id)->exists());
        $this->assertTrue(Reminder::query()->whereKey($freshCompleted->id)->exists());
        $this->assertTrue(Reminder::query()->whereKey($staleActive->id)->exists());
    }

    public function test_prune_completed_reminders_respects_custom_days_window(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $tenDayCompleted = $this->makeReminder($user, ['completed_at' => now()->subDays(10)]);

        Artisan::call('reminders:prune-completed', ['--days' => 7]);

        $this->assertFalse(Reminder::query()->whereKey($tenDayCompleted->id)->exists());
    }

    public function test_prune_completed_reminders_rejects_non_positive_days(): void
    {
        $exit = Artisan::call('reminders:prune-completed', ['--days' => 0]);

        $this->assertSame(1, $exit);
    }

    private function makeEdge(User $user, string $subjectId, ?int $dismissedDaysAgo, string $key = 'channels-controller:edge'): Edge
    {
        return Edge::create([
            'user_id' => $user->id,
            'edge_key' => $key,
            'subject_type' => Channel::class,
            'subject_id' => $subjectId,
            'title' => 'edge-title',
            'dismissed_at' => $dismissedDaysAgo === null ? null : now()->subDays($dismissedDaysAgo),
        ]);
    }

    public function test_prune_stale_edges_reevaluates_each_stale_dismissed_subject_once_and_skips_fresh_and_active_rows(): void
    {
        Queue::fake();
        [$a, $b, $c, $d] = User::factory()->count(4)->create(['status' => 'active']);
        $this->makeEdge($a, '101', 40);
        $this->makeEdge($b, '101', 60);
        $this->makeEdge($c, '102', 5);
        $this->makeEdge($d, '103', null);

        $exit = Artisan::call('edges:prune-stale');

        $this->assertSame(0, $exit);
        $this->assertCount(1, Queue::pushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === '101' && $job->subjectClass === Channel::class));
        $this->assertSame(4, Edge::query()->whereIn('user_id', [$a->id, $b->id, $c->id, $d->id])->count());
    }

    public function test_prune_stale_edges_deletes_rows_of_unregistered_rules_only(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $orphan = $this->makeEdge($user, '201', null, 'never:registered:edge');
        $registered = $this->makeEdge($user, '202', null);

        Artisan::call('edges:prune-stale');

        $this->assertFalse(Edge::query()->whereKey($orphan->id)->exists());
        $this->assertTrue(Edge::query()->whereKey($registered->id)->exists());
    }

    public function test_prune_stale_edges_keeps_rows_of_off_module_rules(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active']);
        $offRule = $this->makeEdge($user, '203', null);
        $orphan = $this->makeEdge($user, '204', null, 'never:registered:edge');

        try {
            config(['app.off' => ['channel']]);
            Artisan::call('edges:prune-stale');
        } finally {
            config(['app.off' => []]);
        }

        $this->assertTrue(Edge::query()->whereKey($offRule->id)->exists());
        $this->assertFalse(Edge::query()->whereKey($orphan->id)->exists());
    }

    public function test_prune_stale_edges_deletes_stale_dismissed_row_whose_subject_no_longer_exists(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $gone = $this->makeEdge($user, '999999999', 40);

        Artisan::call('edges:prune-stale');

        $this->assertFalse(Edge::query()->whereKey($gone->id)->exists());
    }

    public function test_prune_stale_edges_keeps_stale_dismissed_row_whose_subject_is_still_eligible_and_leaves_it_dismissed(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $invitee = User::factory()->create(['status' => 'active']);
        $channel = Channel::create(['name' => 'Keep', 'slug' => 'keep-slug', 'type' => 'open', 'owner_id' => $owner->id]);
        $channel->memberUsers()->attach($invitee->id, [
            'joined_at' => now(), 'entered_at' => null, 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $edge = $this->makeEdge($invitee, (string) $channel->id, 40);

        Artisan::call('edges:prune-stale');

        $fresh = Edge::query()->find($edge->id);
        $this->assertNotNull($fresh);
        $this->assertNotNull($fresh->dismissed_at);
        $this->assertSame(1, Edge::query()->where('user_id', $invitee->id)->where('edge_key', 'channels-controller:edge')->count());
    }

    public function test_prune_stale_edges_rejects_non_positive_days(): void
    {
        $exit = Artisan::call('edges:prune-stale', ['--days' => 0]);

        $this->assertSame(1, $exit);
    }

    public function test_reset_stale_presence_resets_only_offline_mood_statuses_and_clears_focus(): void
    {
        $staleBusy = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addHour(), 'last_seen' => now()->subHours(13)]);
        $staleAngry = User::factory()->create(['status' => 'active', 'presence' => 'angry', 'last_seen' => null]);
        $recentGrumpy = User::factory()->create(['status' => 'active', 'presence' => 'grumpy', 'last_seen' => now()->subHours(2)]);
        $staleLeave = User::factory()->create(['status' => 'active', 'presence' => 'leave', 'last_seen' => now()->subDays(5)]);
        $staleMission = User::factory()->create(['status' => 'active', 'presence' => 'mission', 'last_seen' => now()->subDays(5)]);
        $staleRemote = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'last_seen' => now()->subDays(5)]);

        $exit = Artisan::call('presence:reset-stale');

        $this->assertSame(0, $exit);
        $this->assertSame('onsite', $staleBusy->fresh()->presence->value);
        $this->assertNull($staleBusy->fresh()->focus_until);
        $this->assertSame('onsite', $staleAngry->fresh()->presence->value);
        $this->assertSame('grumpy', $recentGrumpy->fresh()->presence->value);
        $this->assertSame('leave', $staleLeave->fresh()->presence->value);
        $this->assertSame('mission', $staleMission->fresh()->presence->value);
        $this->assertSame('remote', $staleRemote->fresh()->presence->value);
    }

    public function test_reset_stale_presence_honours_hours_option_and_rejects_non_positive(): void
    {
        $busy = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'last_seen' => now()->subHours(3)]);

        Artisan::call('presence:reset-stale', ['--hours' => 24]);
        $this->assertSame('busy', $busy->fresh()->presence->value);

        Artisan::call('presence:reset-stale', ['--hours' => 2]);
        $this->assertSame('onsite', $busy->fresh()->presence->value);

        $this->assertSame(1, Artisan::call('presence:reset-stale', ['--hours' => 0]));
    }

    public function test_nudge_overdue_tasks_dispatches_reconcile_for_overdue_tasks_only(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $overdue = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => now()->subDays(2)]);
        $future = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => now()->addDays(2)]);
        $doneOverdue = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'done', 'deadline' => now()->subDays(2)]);
        $pendingOverdue = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'pending', 'deadline' => now()->subDays(2)]);

        Queue::fake();

        $exit = Artisan::call('tasks:sweep-overdue');

        $this->assertSame(0, $exit);
        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $overdue->id);
        Queue::assertNotPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $future->id);
        Queue::assertNotPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $doneOverdue->id);
        Queue::assertNotPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $pendingOverdue->id);
    }

    public function test_nudge_overdue_tasks_also_sweeps_the_due_soon_edge_toast_for_tasks_entering_the_window_without_a_save(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $dueSoon = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => now()->addHours(12)]);
        $farOut = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => now()->addDays(2)]);
        $alreadyOverdue = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => now()->subDays(2)]);
        $doneDueSoon = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'done', 'deadline' => now()->addHours(12)]);

        Queue::fake();

        $exit = Artisan::call('tasks:sweep-overdue');

        $this->assertSame(0, $exit);
        Queue::assertPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $dueSoon->id);
        Queue::assertNotPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $farOut->id);
        Queue::assertNotPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $alreadyOverdue->id);
        Queue::assertNotPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $doneDueSoon->id);
    }

    private function makeReminder(User $user, array $overrides = []): Reminder
    {
        return Reminder::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test reminder',
            'due_at' => now(),
            'recurs' => 'none',
            'channels' => ['app'],
        ], $overrides));
    }

    public function test_nudge_due_reminders_dispatches_reconcile_nudge_for_overdue_and_due_today_active_reminders_only(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $overdue = $this->makeReminder($user, ['due_at' => now()->subDay()]);
        $dueToday = $this->makeReminder($user, ['due_at' => now()]);
        $future = $this->makeReminder($user, ['due_at' => now()->addDay()]);
        $completed = $this->makeReminder($user, ['due_at' => now()->subDay(), 'completed_at' => now()]);
        $snoozed = $this->makeReminder($user, ['due_at' => now()->subDay(), 'snoozed_until' => now()->addHour()]);

        Queue::fake();

        $exit = Artisan::call('reminders:sweep-due');

        $this->assertSame(0, $exit);
        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $overdue->id);
        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $dueToday->id);
        Queue::assertNotPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $future->id);
        Queue::assertNotPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $completed->id);
        Queue::assertNotPushed(ReconcileNudge::class, fn(ReconcileNudge $job) => $job->subjectId === $snoozed->id);
    }

    public function test_nudge_due_reminders_dispatches_reconcile_edge_only_for_strictly_due_today_reminders(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $dueToday = $this->makeReminder($user, ['due_at' => now()]);
        $overdue = $this->makeReminder($user, ['due_at' => now()->subDay()]);

        Queue::fake();

        Artisan::call('reminders:sweep-due');

        Queue::assertPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $dueToday->id);
        Queue::assertNotPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $overdue->id);
    }

    public function test_with_notify_off_the_three_in_app_reminder_channels_and_the_existing_email_keep_working_but_nothing_is_texted(): void
    {
        config(['app.off' => ['notify']]);
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->makeReminder($user, ['due_at' => now(), 'channels' => ['inapp:edge,badge,nudge', 'email', 'sms']]);

        Queue::fake();
        Notification::fake();

        Artisan::call('reminders:sweep-due');

        Queue::assertPushed(ReconcileEdge::class, fn(ReconcileEdge $job) => $job->subjectId === $reminder->id);
        Queue::assertNotPushed(\App\Jobs\SendReminderSms::class);
        Notification::assertSentTo($user, \App\Notifications\ReminderDueNotification::class);
        $this->assertNotNull($reminder->fresh()->notified_at);
        $this->assertSame(['inapp:edge,badge,nudge', 'email', 'sms'], $reminder->fresh()->channels);
        $this->assertTrue(\App\Models\Reminder::channelEnabled($reminder->fresh()->channels, 'badge'));
        $this->assertTrue(\App\Models\Reminder::channelEnabled($reminder->fresh()->channels, 'nudge'));
        $this->assertTrue(\App\Models\Reminder::channelEnabled($reminder->fresh()->channels, 'edge'));
    }

    public function test_sweep_queues_the_reminder_sms_once_and_skips_it_when_the_tenant_switched_sms_off(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->makeReminder($user, ['due_at' => now(), 'channels' => ['sms']]);

        Queue::fake();
        Notification::fake();

        Artisan::call('reminders:sweep-due');
        Artisan::call('reminders:sweep-due');

        Queue::assertPushed(\App\Jobs\SendReminderSms::class, 1);
        Queue::assertPushed(\App\Jobs\SendReminderSms::class, fn ($job) => $job->reminderId === $reminder->id);
        Notification::assertNothingSent();

        $off = $this->makeReminder($user, ['due_at' => now(), 'channels' => ['sms']]);
        config(['app.off' => ['notify:sms']]);
        Queue::fake();

        Artisan::call('reminders:sweep-due');

        Queue::assertNotPushed(\App\Jobs\SendReminderSms::class);
        $this->assertNull($off->fresh()->notified_at);
    }

    public function test_nudge_due_reminders_sends_the_due_today_email_exactly_once_across_two_command_runs(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->makeReminder($user, ['due_at' => now(), 'channels' => ['app', 'email']]);

        Queue::fake();
        Notification::fake();

        Artisan::call('reminders:sweep-due');
        Artisan::call('reminders:sweep-due');

        Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
        $this->assertNotNull($reminder->fresh()->notified_at);
    }

    public function test_reminder_email_uses_the_notify_styled_mail_and_respects_the_mail_switch(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $reminder = $this->makeReminder($user, ['title' => 'جلسهٔ هفتگی', 'notes' => 'یادداشت مهم']);
        $notification = new ReminderDueNotification($reminder);

        $mail = $notification->toMail($user);
        $html = $mail->render();

        $this->assertTrue($mail->hasTo($user->email));
        $this->assertSame('styled', $mail->message->template);
        $this->assertStringContainsString('جلسهٔ هفتگی', $html);
        $this->assertStringContainsString('یادداشت مهم', $html);
        $this->assertSame(['mail'], $notification->via($user));

        config(['app.off' => ['notify']]);

        $this->assertSame(['mail'], $notification->via($user));
    }

    public function test_nudge_due_reminders_does_not_email_when_the_host_record_is_soft_deleted(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $task = Task::factory()->ownedBy($user)->create();
        $task->delete();
        $reminder = $this->makeReminder($user, [
            'due_at' => now(),
            'channels' => ['email'],
            'remindable_type' => Task::class,
            'remindable_id' => $task->id,
        ]);

        Queue::fake();
        Notification::fake();

        Artisan::call('reminders:sweep-due');

        Notification::assertNothingSent();
        $this->assertNull($reminder->fresh()->notified_at);
    }

    public function test_sweep_due_queues_the_delayed_due_time_reconcile_only_for_reminders_falling_due_within_24_hours(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $soon = $this->makeReminder($user, ['due_at' => now()->addHours(3)]);
        $later = $this->makeReminder($user, ['due_at' => now()->addDays(3)]);
        $snoozed = $this->makeReminder($user, ['due_at' => now()->subHour(), 'snoozed_until' => now()->addHours(2)]);
        $done = $this->makeReminder($user, ['due_at' => now()->addHours(3), 'completed_at' => now()]);

        Cache::flush();
        Queue::fake();

        Artisan::call('reminders:sweep-due');

        Queue::assertPushed(ReconcileReminderDue::class, fn (ReconcileReminderDue $job) => $job->reminderId === $soon->id);
        Queue::assertPushed(ReconcileReminderDue::class, fn (ReconcileReminderDue $job) => $job->reminderId === $snoozed->id);
        Queue::assertNotPushed(ReconcileReminderDue::class, fn (ReconcileReminderDue $job) => $job->reminderId === $later->id);
        Queue::assertNotPushed(ReconcileReminderDue::class, fn (ReconcileReminderDue $job) => $job->reminderId === $done->id);
    }

    public function test_sweep_due_never_queues_the_same_due_moment_twice(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $soon = $this->makeReminder($user, ['due_at' => now()->addHours(3)]);

        Cache::flush();
        Queue::fake();

        Artisan::call('reminders:sweep-due');
        Artisan::call('reminders:sweep-due');

        $this->assertCount(1, Queue::pushed(ReconcileReminderDue::class, fn (ReconcileReminderDue $job) => $job->reminderId === $soon->id));
    }

    public function test_sweep_due_toast_and_email_wait_for_the_reminder_time_of_day(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Carbon::setTestNow('2026-03-10 10:00:00');

        try {
            $reminder = $this->makeReminder($user, ['due_at' => '2026-03-10 15:00:00', 'channels' => ['app', 'email']]);

            Queue::fake();
            Notification::fake();

            Artisan::call('reminders:sweep-due');

            Queue::assertNotPushed(ReconcileEdge::class, fn (ReconcileEdge $job) => $job->subjectId === $reminder->id);
            Queue::assertNotPushed(ReconcileNudge::class, fn (ReconcileNudge $job) => $job->subjectId === $reminder->id);
            Notification::assertNothingSent();

            Carbon::setTestNow('2026-03-10 15:05:00');

            Artisan::call('reminders:sweep-due');

            Queue::assertPushed(ReconcileEdge::class, fn (ReconcileEdge $job) => $job->subjectId === $reminder->id);
            Queue::assertPushed(ReconcileNudge::class, fn (ReconcileNudge $job) => $job->subjectId === $reminder->id);
            Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_sweep_due_still_emails_a_reminder_that_fell_due_in_the_last_hour_of_the_previous_day(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Carbon::setTestNow('2026-03-10 23:10:00');

        try {
            $reminder = $this->makeReminder($user, ['due_at' => '2026-03-10 23:30:00', 'channels' => ['app', 'email']]);

            Queue::fake();
            Notification::fake();

            Artisan::call('reminders:sweep-due');
            Notification::assertNothingSent();

            Carbon::setTestNow('2026-03-11 00:05:00');

            Artisan::call('reminders:sweep-due');

            Notification::assertSentToTimes($user, ReminderDueNotification::class, 1);
            $this->assertNotNull($reminder->fresh()->notified_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function approvedDoneTask(int $daysAgo, ?Project $project = null): Task
    {
        $owner = User::factory()->create(['status' => 'active']);
        $factory = Task::factory()->ownedBy($owner)->withStatus('done');
        if ($project) {
            $factory = $factory->forProject($project);
        }
        $task = $factory->create(['title' => 'Archive sweep probe ' . Str::random(6)]);
        Task::find($task->id)->forceFill(['approved_at' => Carbon::now()->subDays($daysAgo), 'updated_at' => Carbon::now()->subDays($daysAgo)])->save();

        return $task->fresh();
    }

    public function test_archive_stale_done_archives_approved_done_tasks_older_than_the_default_45_days(): void
    {
        $stale = $this->approvedDoneTask(60);
        $exit = Artisan::call('tasks:archive-stale-done');

        $this->assertSame(0, $exit);
        $this->assertNotNull($stale->fresh()->archived_at);
    }

    public function test_archive_stale_done_leaves_recently_approved_done_tasks_unarchived(): void
    {
        $fresh = $this->approvedDoneTask(10);
        Artisan::call('tasks:archive-stale-done');

        $this->assertNull($fresh->fresh()->archived_at);
    }

    public function test_archive_stale_done_honors_a_projects_auto_archive_days_override(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['auto_archive_days' => 7],
        ]);
        $overrideStale = $this->approvedDoneTask(10, $project);
        $overrideFresh = $this->approvedDoneTask(5, $project);

        Artisan::call('tasks:archive-stale-done');

        $this->assertNotNull($overrideStale->fresh()->archived_at);
        $this->assertNull($overrideFresh->fresh()->archived_at);
    }

    public function test_archive_stale_done_uses_the_45_day_default_when_the_project_has_no_override(): void
    {
        $borderline = $this->approvedDoneTask(40);

        Artisan::call('tasks:archive-stale-done');

        $this->assertNull($borderline->fresh()->archived_at);
    }

    public function test_archive_stale_done_excludes_pending_approval_tasks(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true, 'auto_archive_days' => 7],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);

        Artisan::call('tasks:archive-stale-done');

        $this->assertNull($task->fresh()->archived_at);
    }

    public function test_archive_stale_done_handles_project_less_tasks_with_the_default_window(): void
    {
        $orphan = $this->approvedDoneTask(60, null);
        $recentOrphan = $this->approvedDoneTask(30, null);

        Artisan::call('tasks:archive-stale-done');

        $this->assertNotNull($orphan->fresh()->archived_at);
        $this->assertNull($recentOrphan->fresh()->archived_at);
    }

    public function test_normalize_collaborators_command_rewrites_text_ids_as_numbers(): void
    {
        $task = Task::factory()->create();
        DB::table('task_details')->updateOrInsert(['task_id' => $task->id], ['collaborators' => '["4","6"]']);

        Artisan::call('tasks:normalize-collaborators');

        $this->assertSame([4, 6], json_decode(DB::table('task_details')->where('task_id', $task->id)->value('collaborators'), true));
    }

    public function test_normalize_collaborators_command_rewrites_project_member_ids_as_numbers(): void
    {
        $project = \App\Models\Project::factory()->create();
        DB::table('projects')->where('id', $project->id)->update(['member_ids' => '["4","6","4"]']);

        Artisan::call('tasks:normalize-collaborators');

        $this->assertSame([4, 6], json_decode(DB::table('projects')->where('id', $project->id)->value('member_ids'), true));
    }

    public function test_exports_prune_old_deletes_only_rows_and_directories_older_than_the_cutoff(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $user = User::factory()->create(['status' => 'active']);
        $make = fn (string $createdAt) => tap(\Filament\Actions\Exports\Models\Export::create([
            'file_disk' => 'local', 'file_name' => 'x', 'exporter' => 'x', 'total_rows' => 1, 'user_id' => $user->id,
        ]), function ($export) use ($createdAt) {
            DB::table('exports')->where('id', $export->id)->update(['created_at' => $createdAt]);
            \Illuminate\Support\Facades\Storage::disk('local')->put($export->getFileDirectory() . '/export.csv', 'a,b');
        });
        $old = $make(now()->subDays(40)->toDateTimeString());
        $recent = $make(now()->subDays(5)->toDateTimeString());

        $this->assertSame(0, Artisan::call('exports:prune-old', ['--days' => 30]));

        $this->assertDatabaseMissing('exports', ['id' => $old->id]);
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($old->getFileDirectory() . '/export.csv');
        $this->assertDatabaseHas('exports', ['id' => $recent->id]);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($recent->getFileDirectory() . '/export.csv');
    }

    public function test_exports_prune_old_rejects_a_non_positive_days_option(): void
    {
        $this->assertSame(1, Artisan::call('exports:prune-old', ['--days' => 0]));
    }
}
