<?php

namespace Tests\Feature\Services;

use App\Jobs\ReconcileNudgeJob;
use App\Livewire\Dashboard\Correspondence\Actions\MarkOpenedAction;
use App\Models\AiReport;
use App\Models\User;
use App\Models\Correspondence;
use App\Models\Ticket;
use App\Services\Menu\Indicators\CorrespondenceBadge;
use App\Services\Menu\Indicators\ThsBadge;
use App\Services\Menu\Indicators\UnseenAiReports;
use App\Services\Menu\Indicators\UnseenLaunchpads;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StateServiceTest extends TestCase
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
        DB::rollBack();
        Cache::flush();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string)file_get_contents($env)) as $line) {
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

    public function test_lit_peeks_the_cached_map_and_defaults_to_true_when_unknown(): void
    {
        $this->assertTrue(StateService::lit(7, 'media-controller'));

        $version = StateService::version();
        $this->assertTrue(StateService::lit(7, 'media-controller'));

        Cache::put("menu_state:v{$version}:u7", ['media-controller' => false, 'ths-controller' => true], 60);
        $this->assertFalse(StateService::lit(7, 'media-controller'));
        $this->assertTrue(StateService::lit(7, 'ths-controller'));
        $this->assertTrue(StateService::lit(7, 'unknown-key'));
        $this->assertTrue(StateService::lit(8, 'media-controller'));

        Cache::put("menu_state:v{$version}:u7", ['media-controller' => true], 60);
        $this->assertTrue(StateService::lit(7, 'media-controller'));
    }

    public function test_viewed_today_is_false_for_guest_and_before_marking(): void
    {
        $this->assertFalse(StateService::viewedToday('home'));

        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $this->assertFalse(StateService::viewedToday('home'));
    }

    public function test_mark_viewed_makes_tab_viewed_today_for_authed_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        StateService::markViewed('home');

        $this->assertTrue(StateService::viewedToday('home'));
    }

    public function test_mark_viewed_is_idempotent_and_does_not_overwrite_the_cached_date(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        StateService::markViewed('home');
        $key = "tab_viewed:home:{$user->id}";
        $first = Cache::get($key);

        StateService::markViewed('home');
        $this->assertSame($first, Cache::get($key));
    }

    public function test_flush_bumps_version_cache_key_to_a_new_precise_timestamp(): void
    {
        Cache::forever('menu_state:version', 1000);

        StateService::flush();

        $this->assertNotSame(1000, Cache::get('menu_state:version'));
    }

    public function test_viewed_today_is_per_tab_and_per_user(): void
    {
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        $this->actingAs($user1);
        StateService::markViewed('home');
        $this->assertTrue(StateService::viewedToday('home'));
        $this->assertFalse(StateService::viewedToday('links'));

        $this->actingAs($user2);
        $this->assertFalse(StateService::viewedToday('home'));
    }

    public function test_correspondence_badge_covers_waiting_signatures_and_recent_unseen_letters(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $viewer = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $badge = app(CorrespondenceBadge::class);

        $this->assertFalse($badge->isActive());
        $this->actingAs($viewer);
        $waiting = Correspondence::forceCreate([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
            'awaiting_user_id' => $viewer->id,
            'shared_at' => now(),
            'created_at' => now(),
            'parties' => [],
        ]);
        $this->assertTrue($badge->isActive());

        $waiting->forceFill(['status' => Correspondence::STATUS_DONE, 'awaiting_user_id' => null])->save();
        $unseen = Correspondence::forceCreate([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
            'shared_at' => now(),
            'created_at' => now(),
            'parties' => [['user_id' => $viewer->id, 'role' => 'fyi', 'opened_at' => null, 'signed_at' => null]],
            'recipients' => [$viewer->id],
        ]);
        $this->assertTrue($badge->isActive());
        $unseen->forceFill(['parties' => [['user_id' => $viewer->id, 'role' => 'fyi', 'opened_at' => now()->toIso8601String()]]])->save();
        $this->assertFalse($badge->isActive());

        Correspondence::forceCreate([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'shared_at' => now()->subMonths(7),
            'created_at' => now()->subMonths(7),
            'parties' => [['user_id' => $viewer->id, 'role' => 'approver', 'opened_at' => null, 'signed_at' => null]],
            'recipients' => [$viewer->id],
        ]);
        $this->assertFalse($badge->isActive());
    }

    private function sharedLetter(User $author, array $overrides): Correspondence
    {
        return Correspondence::forceCreate(array_replace([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
            'shared_at' => now(),
            'created_at' => now(),
            'parties' => [],
            'recipients' => [],
        ], $overrides));
    }

    public function test_correspondence_badge_lights_for_an_unsigned_parallel_approver_and_not_once_she_has_signed(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $viewer = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $badge = app(CorrespondenceBadge::class);
        $this->actingAs($viewer);
        $parallel = fn (array $party, $sharedAt) => $this->sharedLetter($author, [
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'shared_at' => $sharedAt,
            'parties' => [array_replace(['user_id' => $viewer->id, 'role' => 'approver', 'opened_at' => now()->toIso8601String()], $party)],
            'recipients' => [$viewer->id],
        ]);

        $signed = $parallel(['signed_at' => now()->toIso8601String()], now()->subDays(40));
        $this->assertFalse($badge->isActive());

        $signed->forceFill(['parties' => [['user_id' => $viewer->id, 'role' => 'approver', 'opened_at' => now()->toIso8601String()]]])->save();
        $this->assertTrue($badge->isActive());
    }

    public function test_correspondence_badge_ignores_unseen_letters_older_than_thirty_days_and_signed_or_opened_ones(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $viewer = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $badge = app(CorrespondenceBadge::class);
        $this->actingAs($viewer);
        $party = ['user_id' => $viewer->id, 'role' => 'fyi'];

        $this->sharedLetter($author, ['shared_at' => now()->subDays(31), 'parties' => [$party], 'recipients' => [$viewer->id]]);
        $this->sharedLetter($author, ['parties' => [[...$party, 'signed_at' => now()->toIso8601String()]], 'recipients' => [$viewer->id]]);
        $this->sharedLetter($author, ['status' => Correspondence::STATUS_APPROVED, 'parties' => [$party], 'recipients' => [$viewer->id]]);
        $this->assertFalse($badge->isActive());

        $this->sharedLetter($author, ['shared_at' => now()->subDays(29), 'parties' => [$party], 'recipients' => [$viewer->id]]);
        $this->assertTrue($badge->isActive());
    }

    public function test_correspondence_badge_lights_for_an_unopened_routed_incoming_letter_and_clears_once_opened(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $viewer = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $badge = app(CorrespondenceBadge::class);
        $this->actingAs($viewer);
        $party = ['user_id' => $viewer->id, 'role' => 'fyi'];

        $letter = $this->sharedLetter($author, ['status' => Correspondence::STATUS_ROUTED, 'direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'parties' => [$party], 'recipients' => [$viewer->id]]);
        $this->assertTrue($badge->isActive());

        $letter->forceFill(['parties' => [[...$party, 'opened_at' => now()->toIso8601String()]]])->save();
        $this->assertFalse($badge->isActive());
    }

    public function test_opening_a_routed_incoming_letter_stamps_it_and_reconciles_the_bell(): void
    {
        Bus::fake();
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $viewer = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = $this->sharedLetter($author, ['status' => Correspondence::STATUS_ROUTED, 'direction' => Correspondence::DIRECTION_IN, 'type' => 'incoming', 'parties' => [['user_id' => $viewer->id, 'role' => 'fyi']], 'recipients' => [$viewer->id]]);

        app(MarkOpenedAction::class)->execute($letter, (int) $viewer->id);

        $this->assertNotEmpty($letter->fresh()->parties[0]['opened_at']);
        Bus::assertDispatched(ReconcileNudgeJob::class, fn (ReconcileNudgeJob $job) => $job->ruleKey === 'correspondence-controller:nudge' && (int) $job->subjectId === (int) $letter->id);
    }

    public function test_ths_and_ai_report_indicators_are_registered_in_the_menu_state_map(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($user);

        $state = app(StateService::class)->get();

        $this->assertArrayHasKey('ths-controller', $state);
        $this->assertArrayHasKey('ai-report-controller', $state);
        $this->assertArrayHasKey('launchpad-controller', $state);
        $this->assertSame('ths-controller', (new ThsBadge())->getKey());
        $this->assertSame('ai-report-controller', (new UnseenAiReports())->getKey());
        $this->assertSame('launchpad-controller', (new UnseenLaunchpads())->getKey());
    }

    public function test_ths_badge_lights_for_a_requester_awaiting_rating_and_clears_once_rated(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($requester);
        $badge = new ThsBadge();

        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'closed',
            'satisfaction_score' => null,
            'action_result' => null,
        ]);
        $this->assertTrue($badge->isActive());

        $ticket->forceFill(['satisfaction_score' => 5])->saveQuietly();
        $this->assertFalse($badge->isActive());
    }

    public function test_ths_badge_ignores_a_withdrawn_ticket_and_is_off_for_guests(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'closed',
            'satisfaction_score' => null,
            'action_result' => Ticket::WITHDRAWN_RESULT,
        ]);
        $badge = new ThsBadge();

        $this->actingAs($requester);
        $this->assertFalse($badge->isActive());

        auth()->logout();
        $this->assertFalse($badge->isActive());
    }

    public function test_ths_badge_clears_for_the_assignee_when_the_ticket_leaves_in_progress(): void
    {
        $assignee = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($assignee);
        $badge = new ThsBadge();
        $ticket = Ticket::factory()->create(['assigned_to' => $assignee->id, 'status' => 'in-progress']);

        $this->assertTrue($badge->isActive());

        $ticket->forceFill(['status' => 'closed', 'satisfaction_score' => 4])->saveQuietly();
        $this->assertFalse($badge->isActive());
    }

    public function test_unseen_ai_reports_badge_lights_for_a_shared_ready_report_and_clears_when_opened(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $recipient = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $badge = new UnseenAiReports();
        $report = AiReport::create([
            'user_id' => $owner->id,
            'status' => AiReport::STATUS_READY,
            'active' => true,
            'saved_at' => now(),
            'recipients' => [$recipient->id],
            'spec' => ['lang' => 'fa', 'title' => 'گزارش', 'pages' => []],
        ]);

        $this->actingAs($recipient);
        $this->assertTrue($badge->isActive());

        $this->actingAs($owner);
        $this->assertFalse($badge->isActive());

        $report->markSeenBy($recipient->id);
        $this->actingAs($recipient);
        $this->assertFalse($badge->isActive());
    }

    public function test_unseen_ai_reports_badge_ignores_drafts_and_inactive_reports_and_guests(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $recipient = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $base = [
            'user_id' => $owner->id,
            'status' => AiReport::STATUS_READY,
            'active' => true,
            'saved_at' => now(),
            'recipients' => [$recipient->id],
            'spec' => ['lang' => 'fa', 'title' => 'گزارش', 'pages' => []],
        ];
        AiReport::create([...$base, 'saved_at' => null]);
        AiReport::create([...$base, 'active' => false]);
        $badge = new UnseenAiReports();

        $this->actingAs($recipient);
        $this->assertFalse($badge->isActive());

        AiReport::create($base);
        $this->assertTrue($badge->isActive());

        auth()->logout();
        $this->assertFalse($badge->isActive());
    }
}
