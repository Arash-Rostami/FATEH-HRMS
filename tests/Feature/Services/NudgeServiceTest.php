<?php

namespace Tests\Feature\Services;

use App\Models\AiReport;
use App\Models\Correspondence;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use App\Services\Menu\Notifications\AiReportNudge;
use App\Services\Menu\Notifications\CorrespondenceNudge;
use App\Services\Menu\Notifications\ThsNudge;
use App\Services\Menu\Notifications\ThsReplyNudge;
use App\Services\Menu\NudgeService;
use App\Services\Cache\ModelCacheVersion;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NudgeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        NudgeService::reset();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        NudgeService::reset();
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

    private function nudge(string $key, callable $for, callable $show, bool $refresh = false): MenuNudge
    {
        return new class($key, $for, $show, $refresh) implements MenuNudge {
            public function __construct(
                private string   $keyFn,
                private          $forFn,
                private          $showFn,
                private bool     $refreshFn,
            )
            {
            }

            public function getKey(): string
            {
                return $this->keyFn;
            }

            public function triggers(): array
            {
                return [];
            }

            public function show($subject, User $user): bool
            {
                return ($this->showFn)($subject, $user);
            }

            public function for($subject)
            {
                return ($this->forFn)($subject);
            }

            public function title($subject, User $user): string
            {
                return 'title';
            }

            public function body($subject, User $user): string
            {
                return 'body';
            }

            public function refresh(): bool
            {
                return $this->refreshFn;
            }
        };
    }

    private function nudgeWithUrl(string $key, callable $for, callable $show, callable $url, bool $refresh = false): MenuNudge
    {
        return new class($key, $for, $show, $url, $refresh) implements MenuNudge {
            public function __construct(
                private string $keyFn,
                private        $forFn,
                private        $showFn,
                private        $urlFn,
                private bool   $refreshFn,
            ) {
            }

            public function getKey(): string
            {
                return $this->keyFn;
            }

            public function triggers(): array
            {
                return [];
            }

            public function show($subject, User $user): bool
            {
                return ($this->showFn)($subject, $user);
            }

            public function for($subject)
            {
                return ($this->forFn)($subject);
            }

            public function title($subject, User $user): string
            {
                return 'title';
            }

            public function body($subject, User $user): string
            {
                return 'body';
            }

            public function refresh(): bool
            {
                return $this->refreshFn;
            }

            public function url($subject): ?string
            {
                return ($this->urlFn)($subject);
            }
        };
    }

    private function notificationsFor(string $key): Collection
    {
        return DB::table('notifications')->where('type', FilamentDatabaseNotification::class)->where('data->menu_key', $key)->get();
    }

    public function test_register_is_idempotent_and_registering_twice_does_not_duplicate_recipient_rows(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $u2 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudge($key, fn() => [$u1, $u2], fn() => true));
        NudgeService::register($this->nudge($key, fn() => [$u1, $u2], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $this->assertSame(2, $this->notificationsFor($key)->count());
    }

    public function test_reconcile_creates_one_notification_per_recipient_with_key_and_item_id(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $u2 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudge($key, fn() => [$u1, $u2], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $rows = $this->notificationsFor($key);
        $this->assertSame(2, $rows->count());
        $this->assertEqualsCanonicalizing([$u1->id, $u2->id], $rows->pluck('notifiable_id')->all());
        foreach ($rows as $row) {
            $data = json_decode($row->data, true);
            $this->assertSame($key, $data['menu_key']);
            $this->assertSame((string)$subject->id, $data['item_id']);
        }
    }

    public function test_reconcile_skips_a_recipient_with_an_unread_bare_badge_row_by_default(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $suppressed = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => FilamentDatabaseNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $suppressed->id,
            'data' => json_encode(['menu_key' => 'test']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        NudgeService::register($this->nudge($key, fn() => [$suppressed], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $this->assertSame(0, $this->notificationsFor($key)->count(), 'a recipient with an unread bare badge is skipped by default');
    }

    public function test_reconcile_deletes_notifications_when_subject_no_longer_exists(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudge($key, fn() => [$u1], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);
        $this->assertSame(1, $this->notificationsFor($key)->count());

        $subject->delete();
        NudgeService::reconcile($key, User::class, $subject->id);

        $this->assertSame(0, $this->notificationsFor($key)->count());
    }

    public function test_reconcile_prunes_notifications_for_users_no_longer_in_for_recipients(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $u2 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudge($key, fn() => [$u1, $u2], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);
        $this->assertSame(2, $this->notificationsFor($key)->count());

        NudgeService::reset();
        NudgeService::register($this->nudge($key, fn() => [$u1], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $rows = $this->notificationsFor($key);
        $this->assertSame(1, $rows->count());
        $this->assertSame($u1->id, $rows->first()->notifiable_id);
    }

    public function test_reconcile_does_not_create_notification_when_show_returns_false_and_deletes_existing_one(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudge($key, fn() => [$u1], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);
        $this->assertSame(1, $this->notificationsFor($key)->count());

        NudgeService::reset();
        NudgeService::register($this->nudge($key, fn() => [$u1], fn() => false));
        NudgeService::reconcile($key, User::class, $subject->id);

        $this->assertSame(0, $this->notificationsFor($key)->count());
    }

    public function test_reconcile_is_a_silent_no_op_for_an_unregistered_rule_key(): void
    {
        $subject = User::factory()->create(['status' => 'active']);

        NudgeService::reconcile('unregistered:key', User::class, $subject->id);

        $this->assertSame(0, DB::table('notifications')->where('data->menu_key', 'unregistered:key')->count());
        $this->assertTrue(true);
    }

    public function test_reconcile_adds_view_action_url_when_nudge_provides_one(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudgeWithUrl(
            $key,
            fn() => [$u1],
            fn() => true,
            fn($s) => "/example/{$s->id}",
        ));
        NudgeService::reconcile($key, User::class, $subject->id);

        $row = $this->notificationsFor($key)->first();
        $data = json_decode($row->data, true);
        $viewAction = collect($data['actions'])->firstWhere('name', 'view');

        $this->assertNotNull($viewAction);
        $this->assertSame("/example/{$subject->id}", $viewAction['url']);
    }

    public function test_reconcile_omits_view_action_when_nudge_has_no_url_method(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudge($key, fn() => [$u1], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $row = $this->notificationsFor($key)->first();
        $data = json_decode($row->data, true);
        $actionNames = collect($data['actions'])->pluck('name')->all();

        $this->assertNotContains('view', $actionNames);
        $this->assertContains('read', $actionNames);
    }

    public function test_reconcile_bulk_insert_writes_a_row_for_every_recipient_not_just_one(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $recipients = User::factory()->count(4)->create(['status' => 'active']);
        $key = 'test:nudge:bulk-all';

        NudgeService::register($this->nudge($key, fn() => $recipients->all(), fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $rows = $this->notificationsFor($key);
        $this->assertSame(4, $rows->count());
        $this->assertEqualsCanonicalizing($recipients->pluck('id')->all(), $rows->pluck('notifiable_id')->all());
    }

    public function test_reconcile_bumps_database_notification_cache_version_after_bulk_insert(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge:cache-bump';

        $staleKey = ModelCacheVersion::key(DatabaseNotification::class, 'unread_menu_groups:' . $recipient->id);
        Cache::put($staleKey, 'stale-value', now()->addMinutes(15));

        NudgeService::register($this->nudge($key, fn() => [$recipient], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $freshKey = ModelCacheVersion::key(DatabaseNotification::class, 'unread_menu_groups:' . $recipient->id);

        $this->assertNotSame($staleKey, $freshKey, 'ModelCacheVersion::bump after bulk insert must rotate the unread_menu_groups cache key');
        $this->assertNull(Cache::get($freshKey), 'the freshly-rotated key must not resolve to the stale primed value');
    }

    public function test_reconcile_refreshes_view_action_url_on_unread_row_when_url_changes(): void
    {
        $subject = User::factory()->create(['status' => 'active']);
        $u1 = User::factory()->create(['status' => 'active']);
        $key = 'test:nudge';

        NudgeService::register($this->nudgeWithUrl(
            $key,
            fn() => [$u1],
            fn() => true,
            fn($s) => "/v1/{$s->id}",
            refresh: true,
        ));
        NudgeService::reconcile($key, User::class, $subject->id);

        NudgeService::reset();
        NudgeService::register($this->nudgeWithUrl(
            $key,
            fn() => [$u1],
            fn() => true,
            fn($s) => "/v2/{$s->id}",
            refresh: true,
        ));
        NudgeService::reconcile($key, User::class, $subject->id);

        $row = $this->notificationsFor($key)->first();
        $data = json_decode($row->data, true);
        $viewAction = collect($data['actions'])->firstWhere('name', 'view');

        $this->assertSame("/v2/{$subject->id}", $viewAction['url']);
    }

    public function test_correspondence_nudge_plans_pending_signatures_for_the_approver(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $approver = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = Correspondence::forceCreate([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'subject' => 'نامه نیازمند امضا',
            'awaiting_user_id' => $approver->id,
            'shared_at' => now(),
            'parties' => [['user_id' => $approver->id, 'role' => 'approver', 'signed_at' => null]],
            'recipients' => [$approver->id],
        ]);
        $nudge = new CorrespondenceNudge();

        $recipients = $nudge->for($letter);

        $this->assertTrue($recipients->contains('id', $approver->id));
        $this->assertTrue($nudge->show($letter, $approver));
        $this->assertStringContainsString('امضای نامه', $nudge->title($letter, $approver));
        $this->assertStringContainsString('نامهٔ «نامه نیازمند امضا»', $nudge->body($letter, $approver));
        $this->assertSame('correspondence-controller:nudge', $nudge->getKey());
        $this->assertSame(route('correspondence', ['open' => $letter->id]), $nudge->url($letter));
    }

    public function test_correspondence_nudge_masks_confidential_subjects_and_notifies_fyi_only_for_urgent_approval(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $fyi = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = Correspondence::forceCreate([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_APPROVED,
            'classification' => Correspondence::CLASSIFICATION_SECRET,
            'subject' => 'محرمانه',
            'urgency' => Correspondence::URGENCY_URGENT,
            'parties' => [['user_id' => $fyi->id, 'role' => 'fyi']],
            'recipients' => [$fyi->id],
        ]);
        $nudge = new CorrespondenceNudge();
        $recipients = $nudge->for($letter);

        $this->assertTrue($recipients->contains('id', $author->id));
        $this->assertTrue($recipients->contains('id', $fyi->id));
        $this->assertStringNotContainsString('محرمانه', $nudge->title($letter, $fyi));
        $this->assertStringContainsString(__('resources/correspondence/strings.confidential_subject'), $nudge->title($letter, $fyi));
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
    }

    public function test_correspondence_nudge_trigger_ignores_irrelevant_edits_and_tracks_its_plan_columns(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = Correspondence::forceCreate([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'subject' => 'Before',
            'parties' => [],
            'recipients' => [],
        ]);
        $subject = (new CorrespondenceNudge())->triggers()[0]['subject'];
        $irrelevant = $letter->fresh();
        $irrelevant->type = 'certificate';
        $irrelevant->save();

        $this->assertNull($subject($irrelevant));

        $relevant = $irrelevant->fresh();
        $relevant->urgency = Correspondence::URGENCY_URGENT;
        $relevant->save();
        $this->assertSame((int) $relevant->id, (int) $subject($relevant)->id);
    }

    public function test_ths_nudge_plans_the_requester_alone_for_a_closed_ticket_awaiting_rating(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'closed',
            'satisfaction_score' => null,
            'action_result' => null,
        ]);
        $nudge = new ThsNudge();

        $this->assertSame([$requester->id], $nudge->for($ticket)->pluck('id')->all());
        $this->assertTrue($nudge->show($ticket, $requester));
        $this->assertSame('درخواست شما بسته شد', $nudge->title($ticket, $requester));
        $this->assertSame(route('ths', ['open' => $ticket->id]), $nudge->url($ticket));
    }

    public function test_ths_nudge_goes_silent_once_the_ticket_is_closed_and_rated(): void
    {
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'status' => 'closed',
            'satisfaction_score' => 5,
        ]);

        $this->assertFalse((new ThsNudge())->show($ticket, $requester));
        $this->assertFalse((new ThsReplyNudge())->show($ticket->addReply($requester, 'thanks'), $requester));
        $this->assertCount(0, (new ThsNudge())->for($ticket));
    }

    public function test_ths_nudge_hides_the_users_own_latest_reply_unless_the_next_action_is_theirs(): void
    {
        $assignee = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $ticket = Ticket::factory()->create([
            'assigned_to' => $assignee->id,
            'requester_id' => $requester->id,
            'status' => 'in-progress',
        ]);
        $ticket->addReply($requester, 'any news?');
        $nudge = new ThsNudge();

        $this->assertFalse($nudge->show($ticket->fresh(), $requester));
        $this->assertTrue($nudge->show($ticket->fresh(), $assignee));

        $ticket->addReply($assignee, 'working on it');
        $this->assertTrue($nudge->show($ticket->fresh(), $assignee));
        $this->assertTrue($nudge->show($ticket->fresh(), $requester));
    }

    public function test_ths_reply_nudge_notifies_the_other_party_and_the_assignee_but_not_the_reply_author(): void
    {
        $assignee = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $requester = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $ticket = Ticket::factory()->create([
            'assigned_to' => $assignee->id,
            'requester_id' => $requester->id,
            'status' => 'in-progress',
            'request_subject' => 'Help',
        ]);
        $reply = $ticket->addReply($requester, 'any news?');
        $nudge = new ThsReplyNudge();

        $recipients = $nudge->for($reply)->pluck('id')->all();

        $this->assertContains($assignee->id, $recipients);
        $this->assertNotContains($requester->id, $recipients);
        $this->assertTrue($nudge->show($reply, $assignee));
        $this->assertFalse($nudge->show($reply, $requester));
        $this->assertFalse($nudge->refresh());
    }

    private function sharedReport(User $owner, array $attributes = []): AiReport
    {
        return AiReport::create([
            'user_id' => $owner->id,
            'status' => AiReport::STATUS_READY,
            'active' => true,
            'saved_at' => now(),
            'spec' => ['lang' => 'fa', 'title' => 'گزارش', 'pages' => []],
            'versions' => [],
            'title' => 'گزارش فروش',
            ...$attributes,
        ]);
    }

    public function test_ai_report_nudge_plans_one_bell_for_each_unseen_active_recipient_and_never_the_owner(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $unseen = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $seen = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $inactive = User::factory()->create(['role' => 'user', 'status' => 'inactive']);
        $report = $this->sharedReport($owner, ['recipients' => [$unseen->id, $seen->id, $inactive->id, $owner->id], 'seen_by' => [$seen->id]]);
        $nudge = new AiReportNudge();

        $recipients = $nudge->for($report)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$unseen->id, $seen->id], $recipients);
        $this->assertTrue($nudge->show($report, $unseen));
        $this->assertFalse($nudge->show($report, $seen));
        $this->assertSame('ai-report-controller:nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertFalse($nudge->refresh());
        $this->assertStringContainsString($owner->name, $nudge->body($report, $unseen));
        $this->assertStringContainsString('signature=', $nudge->url($report));
    }

    public function test_ai_report_nudge_plans_nobody_for_drafts_inactive_failed_or_unshared_reports(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $recipient = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $nudge = new AiReportNudge();

        foreach ([['saved_at' => null], ['active' => false], ['status' => AiReport::STATUS_FAILED], ['status' => AiReport::STATUS_GENERATING], ['recipients' => []]] as $override) {
            $report = $this->sharedReport($owner, ['recipients' => [$recipient->id], ...$override]);

            $this->assertCount(0, $nudge->for($report));
            $this->assertFalse($nudge->show($report, $recipient));
        }
    }

    public function test_ai_report_nudge_reconcile_creates_removes_and_never_resurrects_the_bell(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $recipient = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $report = $this->sharedReport($owner, ['recipients' => [$recipient->id]]);
        $key = 'ai-report-controller:nudge';
        $rows = fn () => DatabaseNotification::query()->where('data->menu_key', $key)->where('data->item_id', (string) $report->id);

        NudgeService::register(new AiReportNudge());
        NudgeService::reconcile($key, AiReport::class, $report->id);
        $this->assertSame([$recipient->id], $rows()->pluck('notifiable_id')->all());

        $rows()->update(['read_at' => now()]);
        NudgeService::reconcile($key, AiReport::class, $report->id);
        $this->assertSame(1, $rows()->count());
        $this->assertNotNull($rows()->value('read_at'));

        $rows()->update(['read_at' => null]);
        $report->update(['seen_by' => [$recipient->id]]);
        NudgeService::reconcile($key, AiReport::class, $report->id);
        $this->assertSame(0, $rows()->count());

        $report->update(['seen_by' => []]);
        NudgeService::reconcile($key, AiReport::class, $report->id);
        $this->assertSame(1, $rows()->count());

        $report->update(['recipients' => []]);
        NudgeService::reconcile($key, AiReport::class, $report->id);
        $this->assertSame(0, $rows()->count());

        $report->update(['recipients' => [$recipient->id]]);
        NudgeService::reconcile($key, AiReport::class, $report->id);
        $report->delete();
        NudgeService::reconcile($key, AiReport::class, $report->id);
        $this->assertSame(0, $rows()->count());
    }

    public function test_ai_report_nudge_is_not_registered_when_the_module_is_off_for_the_tenant(): void
    {
        config(['app.off' => ['ai-report']]);
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $recipient = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $report = $this->sharedReport($owner, ['recipients' => [$recipient->id]]);

        NudgeService::register(new AiReportNudge());
        NudgeService::reconcile('ai-report-controller:nudge', AiReport::class, $report->id);

        $this->assertSame(0, DatabaseNotification::query()->where('data->menu_key', 'ai-report-controller:nudge')->count());
    }

    public function test_correspondence_nudge_plan_follows_status_signed_state_and_urgency(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $signed = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $unsigned = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $fyi = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $base = [
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'subject' => 'Plan',
            'route_mode' => Correspondence::ROUTE_PARALLEL,
            'parties' => [
                ['user_id' => $signed->id, 'role' => 'approver', 'signed_at' => now()->toDateTimeString()],
                ['user_id' => $unsigned->id, 'role' => 'approver', 'signed_at' => null],
                ['user_id' => $fyi->id, 'role' => 'fyi'],
            ],
            'recipients' => [$signed->id, $unsigned->id, $fyi->id],
        ];
        $nudge = new CorrespondenceNudge();

        $shared = Correspondence::forceCreate($base + ['status' => Correspondence::STATUS_SHARED]);
        $ids = $nudge->for($shared)->pluck('id')->all();
        $this->assertSame([$unsigned->id], $ids);
        $this->assertNotContains($author->id, $ids);

        $draft = Correspondence::forceCreate($base + ['status' => Correspondence::STATUS_DRAFT]);
        $this->assertCount(0, $nudge->for($draft));

        $rejected = Correspondence::forceCreate($base + ['status' => Correspondence::STATUS_REJECTED]);
        $this->assertSame([$author->id], $nudge->for($rejected)->pluck('id')->all());
        $this->assertStringContainsString('رد شد', $nudge->title($rejected, $author));

        $approved = Correspondence::forceCreate($base + ['status' => Correspondence::STATUS_APPROVED]);
        $this->assertSame([$author->id], $nudge->for($approved)->pluck('id')->all());
        $this->assertStringContainsString('تأیید شد', $nudge->title($approved, $author));
    }

    public function test_correspondence_nudge_trigger_tracks_a_route_mode_change_because_the_plan_depends_on_it(): void
    {
        $author = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $letter = Correspondence::forceCreate([
            'user_id' => $author->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_SHARED,
            'classification' => Correspondence::CLASSIFICATION_NORMAL,
            'route_mode' => Correspondence::ROUTE_SEQUENTIAL,
            'subject' => 'Route',
            'parties' => [],
            'recipients' => [],
        ]);
        $subject = (new CorrespondenceNudge())->triggers()[0]['subject'];
        $changed = $letter->fresh();
        $changed->route_mode = Correspondence::ROUTE_PARALLEL;
        $changed->save();

        $this->assertSame((int) $letter->id, (int) $subject($changed)->id);
    }
}
