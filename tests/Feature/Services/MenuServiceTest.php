<?php

namespace Tests\Feature\Services;

use App\Models\Workflow;
use App\Models\TaskDetail;
use App\Enums\TaskActivityType;
use App\Filament\Resources\FeedResource\Enums\FeedCategory;
use App\Models\Ad;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\ChannelMember;
use App\Models\DMS;
use App\Models\Department;
use App\Models\Edge;
use App\Services\Menu\EdgeService;
use App\Services\Menu\PulseService;
use App\Models\EnergyTest;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Feed;
use App\Models\Message;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Project;
use App\Models\Profile;
use App\Models\Read;
use App\Models\Reminder;
use App\Models\Report;
use App\Models\Reply;
use App\Models\Reservation;
use App\Models\Suggestion;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ReminderDueNotification;
use App\Services\Menu\BadgeLegendCatalog;
use App\Services\Menu\BadgeSyncService;
use App\Services\Menu\Contracts\MenuBadge;
use App\Services\Menu\Indicators\ActiveAds;
use App\Services\Menu\Indicators\DmsBadge;
use App\Services\Menu\Indicators\EnergyTestBadge;
use App\Services\Menu\Indicators\PendingSuggestions;
use App\Services\Menu\Indicators\ReminderBadge;
use App\Services\Menu\Indicators\SharedEvents;
use App\Services\Menu\Indicators\SpecialDays;
use App\Services\Menu\Indicators\TasksImminent;
use App\Services\Menu\Indicators\TasksPendingApproval;
use App\Services\Menu\Indicators\TasksTodo;
use App\Services\Menu\Indicators\ThsBadge;
use App\Services\Menu\Indicators\UnreadFeeds;
use App\Services\Menu\Indicators\UnreadMessages;
use App\Services\Menu\Indicators\UnreadPosts;
use App\Services\Menu\NudgeService;
use App\Services\Menu\Notifications\AdNudge;
use App\Services\Menu\Notifications\ChannelNudge;
use App\Services\Menu\Notifications\ContactNudge;
use App\Services\Menu\Notifications\DmsNudge;
use App\Services\Menu\Notifications\FeedNudge;
use App\Services\Menu\Notifications\PhotoNudge;
use App\Services\Menu\Notifications\PostNudge;
use App\Services\Menu\Notifications\ProjectNudge;
use App\Services\Menu\Notifications\ReminderOverdueNudge;
use App\Services\Menu\Notifications\ReportNudge;
use App\Services\Menu\Notifications\SharedEventsNudge;
use App\Services\Menu\Notifications\SuggestionNudge;
use App\Services\Menu\Notifications\TaskNudge;
use App\Services\Menu\Notifications\ThsNudge;
use App\Services\Menu\Notifications\ThsReplyNudge;
use App\Services\Menu\Toasts\ChannelToast;
use App\Services\Menu\Toasts\ProjectToast;
use App\Services\Menu\Toasts\ReminderDueTodayEdge;
use App\Services\Menu\Toasts\TaskDueSoonToast;
use App\Services\Menu\StateService;
use App\Services\Cache\ModelCacheVersion;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MenuServiceTest extends TestCase
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

    private function insertNotification(string $menuKey, int $userId, ?string $readAt = null, string $itemId = '1'): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => FilamentDatabaseNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $userId,
            'data' => json_encode(['menu_key' => $menuKey, 'item_id' => $itemId, 'title' => 't', 'body' => 'b', 'actions' => []]),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $id;
    }

    private function notificationsFor(string $menuKey, int $userId): \Illuminate\Support\Collection
    {
        return DB::table('notifications')
            ->where('type', FilamentDatabaseNotification::class)
            ->where('notifiable_id', $userId)
            ->where('data->menu_key', $menuKey)
            ->get();
    }

    private function badge(string $key, bool $active): MenuBadge
    {
        return new class($key, $active) implements MenuBadge {
            public function __construct(private string $key, private bool $active)
            {
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function isActive(): bool
            {
                return $this->active;
            }

            public function getTitle(): string
            {
                return 'title-' . $this->key;
            }

            public function getBody(): string
            {
                return 'body-' . $this->key;
            }
        };
    }

    private function throwingBadge(string $key): MenuBadge
    {
        return new class($key) implements MenuBadge {
            public function __construct(private string $key)
            {
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function isActive(): bool
            {
                return true;
            }

            public function getTitle(): string
            {
                throw new \RuntimeException('boom');
            }

            public function getBody(): string
            {
                return 'b';
            }
        };
    }

    private function activeUser(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    public function test_badge_legend_catalog_groups_returns_five_groups_with_label_and_icon(): void
    {
        $groups = BadgeLegendCatalog::groups();

        $this->assertSame(['notifications', 'content', 'opportunities', 'tasks', 'compliance'], array_keys($groups));
        foreach ($groups as $group) {
            $this->assertNotEmpty($group['label']);
            $this->assertNotEmpty($group['icon']);
        }
    }

    public function test_badge_legend_catalog_get_returns_entry_by_key_and_null_for_unknown(): void
    {
        $ads = BadgeLegendCatalog::get('ads-controller');

        $this->assertNotNull($ads);
        $this->assertSame('opportunities', $ads['group']);
        $this->assertNotEmpty($ads['label']);
        $this->assertNull(BadgeLegendCatalog::get('does-not-exist'));
    }

    public function test_badge_legend_catalog_grouped_folds_all_entries_into_five_groups(): void
    {
        $grouped = BadgeLegendCatalog::grouped();

        $this->assertCount(5, $grouped);
        $this->assertSame(['notifications', 'content', 'opportunities', 'tasks', 'compliance'], array_column($grouped, 'id'));
        $total = array_sum(array_map(fn($g) => count($g['items']), $grouped));
        $this->assertSame(27, $total);
        $expectedLabels = array_map(fn($g) => BadgeLegendCatalog::groups()[$g['id']]['label'], $grouped);
        $this->assertSame($expectedLabels, array_column($grouped, 'label'));
        foreach ($grouped as $g) {
            foreach ($g['items'] as $item) {
                $this->assertSame($g['id'], $item['group']);
                $this->assertArrayHasKey('tone', $item);
                $this->assertArrayHasKey('label', $item);
            }
        }
    }

    public function test_badge_legend_catalog_grouped_partitions_configured_groups_into_subgroups(): void
    {
        $grouped = BadgeLegendCatalog::grouped();
        $defs = BadgeLegendCatalog::subgroups();

        $this->assertSame(['notifications', 'content', 'compliance', 'tasks'], array_keys($defs));
        $this->assertSame(['list', 'deadline', 'approval', 'projects', 'workflow', 'tickets', 'reminders', 'reminders-badge'], array_keys($defs['tasks']));
        $this->assertSame(['direct', 'channels'], array_keys($defs['notifications']));
        $this->assertSame(['calendar', 'media'], array_keys($defs['content']));
        $this->assertSame(['tracking', 'reports', 'tasksheet'], array_keys($defs['compliance']));

        foreach ($grouped as $group) {
            if (!isset($defs[$group['id']])) {
                $this->assertSame([], $group['subgroups'], "group {$group['id']} should have no subgroups");
                $this->assertLessThanOrEqual(2, count($group['items']), "flat group {$group['id']} has more than 2 items and must be subgrouped");
                continue;
            }

            $this->assertSame(array_keys($defs[$group['id']]), array_column($group['subgroups'], 'id'));

            $itemsBySubgroup = collect($group['subgroups'])->flatMap(fn($sg) => $sg['items']);
            $this->assertCount(count($group['items']), $itemsBySubgroup, "every item in the {$group['id']} group must land in exactly one subgroup");
            $this->assertSame(
                collect($group['items'])->pluck('label')->sort()->values()->all(),
                $itemsBySubgroup->pluck('label')->sort()->values()->all()
            );

            foreach ($group['subgroups'] as $subgroup) {
                $this->assertLessThanOrEqual(2, count($subgroup['items']), "subgroup {$group['id']}.{$subgroup['id']} has more than 2 items");
            }
        }
    }

    public function test_badge_sync_service_creates_unread_rows_for_active_indicators(): void
    {
        $user = $this->activeUser();
        $indicator = $this->badge('ads-controller', true);

        app(BadgeSyncService::class)->syncBatch($user, [$indicator], ['ads-controller' => true]);

        $rows = $this->notificationsFor('ads-controller', $user->id);
        $this->assertCount(1, $rows);
        $this->assertNull($rows->first()->read_at);
        $data = json_decode($rows->first()->data, true);
        $this->assertSame('ads-controller', $data['menu_key']);
    }

    public function test_badge_sync_service_deletes_rows_for_inactive_indicators(): void
    {
        $user = $this->activeUser();
        $this->insertNotification('ads-controller', $user->id);
        $indicator = $this->badge('ads-controller', false);

        app(BadgeSyncService::class)->syncBatch($user, [$indicator], ['ads-controller' => false]);

        $this->assertCount(0, $this->notificationsFor('ads-controller', $user->id));
    }

    public function test_badge_sync_service_leaves_existing_active_rows_untouched_and_does_not_duplicate(): void
    {
        $user = $this->activeUser();
        $this->insertNotification('ads-controller', $user->id);
        $indicator = $this->badge('ads-controller', true);

        app(BadgeSyncService::class)->syncBatch($user, [$indicator], ['ads-controller' => true]);

        $this->assertCount(1, $this->notificationsFor('ads-controller', $user->id));
    }

    public function test_badge_sync_service_preserves_read_rows_for_active_indicators_no_resurface(): void
    {
        $user = $this->activeUser();
        $this->insertNotification('ads-controller', $user->id, now()->toDateTimeString());
        $indicator = $this->badge('ads-controller', true);

        app(BadgeSyncService::class)->syncBatch($user, [$indicator], ['ads-controller' => true]);

        $rows = $this->notificationsFor('ads-controller', $user->id);
        $this->assertCount(1, $rows);
        $this->assertNotNull($rows->first()->read_at);
    }

    public function test_badge_sync_service_skips_throwing_indicator_and_still_partitions_the_rest(): void
    {
        $user = $this->activeUser();
        $good = $this->badge('ads-controller', true);
        $bad = $this->throwingBadge('suggestion-controller');

        app(BadgeSyncService::class)->syncBatch($user, [$good, $bad], ['ads-controller' => true, 'suggestion-controller' => true]);

        $this->assertCount(1, $this->notificationsFor('ads-controller', $user->id));
        $this->assertCount(0, $this->notificationsFor('suggestion-controller', $user->id));
    }

    public function test_active_ads_indicator_key_title_and_is_active_when_an_active_ad_exists(): void
    {
        $indicator = new ActiveAds();

        $this->assertSame('ads-controller', $indicator->getKey());
        $this->assertNotEmpty($indicator->getTitle());
        $this->assertNotEmpty($indicator->getBody());

        Ad::factory()->create(['active' => true]);

        $this->assertTrue($indicator->isActive());
    }

    public function test_pending_suggestions_indicator_is_false_without_an_auth_user(): void
    {
        $this->assertFalse((new PendingSuggestions())->isActive());
    }

    public function test_pending_suggestions_indicator_never_lights_for_a_regular_employee(): void
    {
        $user = $this->activeUser();
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'HR', 'position' => 'staff']);
        Suggestion::factory()->create(['stage' => 'awaiting_decision']);
        $this->actingAs($user);

        $this->assertFalse((new PendingSuggestions())->isActive());
    }

    public function test_pending_suggestions_indicator_lights_for_ceo_when_a_suggestion_awaits_decision(): void
    {
        $user = $this->activeUser();
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'HR', 'position' => 'ceo']);
        Suggestion::factory()->create(['stage' => 'awaiting_decision']);
        $this->actingAs($user);

        $this->assertTrue((new PendingSuggestions())->isActive());
    }

    public function test_shared_events_indicator_is_false_when_calendar_viewed_today(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        StateService::markViewed('calendar');

        $this->assertFalse((new SharedEvents())->isActive());
    }

    public function test_shared_events_indicator_lights_for_an_imminent_shared_event_owned_by_user(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id, 'date' => now()->addHours(2)]);
        EventShare::create(['event_id' => $event->id, 'user_id' => $this->activeUser()->id, 'shared_by' => $user->id]);

        $this->assertTrue((new SharedEvents())->isActive());
    }

    public function test_unread_posts_indicator_is_false_without_an_unread_post_nudge_row(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);

        $this->assertFalse((new UnreadPosts())->isActive());
    }

    public function test_unread_posts_indicator_lights_when_an_unread_post_nudge_row_exists_for_user(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        $this->insertNotification(Post::NUDGE_KEY, $user->id, null, '5');

        $this->assertTrue((new UnreadPosts())->isActive());
    }

    public function test_unread_feeds_indicator_is_false_without_an_unread_feed_nudge_row(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);

        $this->assertFalse((new UnreadFeeds())->isActive());
    }

    public function test_unread_feeds_indicator_lights_when_an_unread_feed_nudge_row_exists_for_user(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        $this->insertNotification(Feed::NUDGE_KEY, $user->id, null, '5');

        $this->assertTrue((new UnreadFeeds())->isActive());
    }

    public function test_unread_messages_indicator_is_false_without_an_unread_message(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);

        $this->assertFalse((new UnreadMessages())->isActive());
    }

    public function test_unread_messages_indicator_lights_when_user_has_an_unread_message(): void
    {
        $user = $this->activeUser();
        $sender = $this->activeUser();
        $this->actingAs($user);
        Message::factory()->create(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'read_at' => null]);

        $this->assertTrue((new UnreadMessages())->isActive());
    }

    public function test_unread_messages_indicator_ignores_a_message_from_a_deactivated_sender(): void
    {
        $user = $this->activeUser();
        $sender = User::factory()->create(['status' => 'inactive']);
        $this->actingAs($user);
        Message::factory()->create(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'read_at' => null]);

        $this->assertFalse((new UnreadMessages())->isActive());
    }

    public function test_special_days_indicator_is_false_when_calendar_viewed_today(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        StateService::markViewed('calendar');

        $this->assertFalse((new SpecialDays())->isActive());
    }

    public function test_special_days_indicator_lights_for_a_non_terminated_profile_with_today_birthdate(): void
    {
        Profile::factory()->create([
            'birthdate' => now()->subYears(30)->toDateString(),
            'employment_status' => 'working',
        ]);

        $this->assertTrue((new SpecialDays())->isActive());
    }

    public function test_tasks_todo_indicator_is_false_without_a_todo_task_for_user(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);

        $this->assertFalse((new TasksTodo())->isActive());
    }

    public function test_tasks_todo_indicator_lights_when_user_owns_a_todo_task(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        Task::factory()->create(['assigned_to' => $user->id, 'user_id' => $this->activeUser()->id, 'status' => 'todo']);

        $this->assertTrue((new TasksTodo())->isActive());
    }

    public function test_energy_test_badge_lights_when_user_has_no_recent_test(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);

        $this->assertTrue((new EnergyTestBadge())->isActive());
    }

    public function test_energy_test_badge_clears_when_user_completed_a_test_within_25_days(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        EnergyTest::factory()->forUser($user)->completedDaysAgo(5)->create();

        $this->assertFalse((new EnergyTestBadge())->isActive());
    }

    public function test_energy_test_badge_re_lights_when_last_test_is_older_than_25_days(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        EnergyTest::factory()->forUser($user)->completedDaysAgo(30)->create();

        $this->assertTrue((new EnergyTestBadge())->isActive());
    }

    public function test_ths_badge_is_false_without_an_actionable_ticket(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);

        $this->assertFalse((new ThsBadge())->isActive());
    }

    public function test_ths_badge_lights_for_an_in_progress_ticket_assigned_to_user(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        Ticket::factory()->create(['assigned_to' => $user->id, 'status' => 'in-progress']);

        $this->assertTrue((new ThsBadge())->isActive());
    }

    public function test_dms_badge_lights_when_a_live_document_needs_sign_from_user(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        DMS::factory()->create(['status' => 'live', 'owners' => ['ALL'], 'users' => []]);

        $this->assertTrue((new DmsBadge())->isActive());
    }

    public function test_reminder_badge_lights_only_when_badge_channel_is_enabled_on_a_due_reminder(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        $this->makeReminder($user, ['due_at' => now()->subDay(), 'channels' => ['inapp:edge,badge,nudge']]);

        $this->assertTrue((new ReminderBadge())->isActive());

        Reminder::query()->where('user_id', $user->id)->get()->each->delete();
        $this->makeReminder($user, ['due_at' => now()->subDay(), 'channels' => ['inapp:edge,nudge']]);

        $this->assertFalse((new ReminderBadge())->isActive());
    }

    public function test_reminder_signals_are_all_inactive_when_no_in_app_channel_is_selected(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        $overdue = $this->makeReminder($user, ['due_at' => now()->subDay(), 'channels' => ['email']]);
        $dueToday = $this->makeReminder($user, ['due_at' => now(), 'channels' => ['email']]);

        $this->assertFalse((new ReminderBadge())->isActive());
        $this->assertFalse((new ReminderOverdueNudge())->show($overdue, $user));
        $this->assertFalse((new ReminderDueTodayEdge())->show($dueToday, $user));
    }

    public function test_reminder_signals_are_all_suppressed_while_the_host_record_is_soft_deleted(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        $task = Task::factory()->ownedBy($user)->create();
        $task->delete();

        $overdue = $this->makeReminder($user, [
            'due_at' => now()->subDay(),
            'remindable_type' => Task::class,
            'remindable_id' => $task->id,
        ]);
        $dueToday = $this->makeReminder($user, [
            'due_at' => now(),
            'remindable_type' => Task::class,
            'remindable_id' => $task->id,
        ]);

        $this->assertFalse((new ReminderBadge())->isActive());
        $this->assertFalse((new ReminderOverdueNudge())->show($overdue, $user));
        $this->assertFalse((new ReminderDueTodayEdge())->show($dueToday, $user));
    }

    public function test_ad_nudge_shape_key_triggers_refresh_and_url(): void
    {
        $nudge = new AdNudge();
        $ad = new Ad();
        $ad->id = 7;

        $this->assertSame('ads-controller:nudge', $nudge->getKey());
        $this->assertSame([
            ['class' => Ad::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ], $nudge->triggers());
        $this->assertTrue($nudge->refresh());
        $this->assertStringContainsString('/ads', $nudge->url($ad));
        $this->assertStringContainsString('open=7', $nudge->url($ad));
    }

    public function test_ad_nudge_show_gates_on_active_and_title_uses_position(): void
    {
        $nudge = new AdNudge();
        $active = new Ad();
        $active->active = true;
        $active->position = 'Developer';
        $inactive = new Ad();
        $inactive->active = false;

        $this->assertTrue($nudge->show($active, $this->activeUser()));
        $this->assertFalse($nudge->show($inactive, $this->activeUser()));
        $this->assertSame('آگهی فعال: Developer', $nudge->title($active, $this->activeUser()));
    }

    public function test_channel_nudge_shape_and_badge_suppresses_create_false(): void
    {
        $nudge = new ChannelNudge();

        $this->assertSame('channels-controller:nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(Channel::class, $triggers[0]['class']);
        $this->assertSame(['deleted', 'forceDeleted'], $triggers[0]['on']);
        $this->assertSame(ChannelMessage::class, $triggers[1]['class']);
        $this->assertSame(['created', 'deleted'], $triggers[1]['on']);
    }

    public function test_channel_nudge_for_primes_body_distinguishing_invited_from_unread(): void
    {
        $owner = $this->activeUser();
        $invited = $this->activeUser();
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        ChannelMessage::factory()->inChannel($channel)->fromUser($owner)->create(['body' => 'hello']);
        DB::table('channel_members')->insert([
            'channel_id' => $channel->id, 'user_id' => $invited->id,
            'joined_at' => now(), 'entered_at' => null, 'last_read_message_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $nudge = new ChannelNudge();
        $recipients = $nudge->for($channel);
        $ids = $recipients->pluck('id')->all();

        $this->assertContains($owner->id, $ids);
        $this->assertContains($invited->id, $ids);
        $this->assertStringContainsString('دعوت به گروه', $nudge->title($channel, $invited));
        $this->assertStringContainsString('پیام جدید در گروه', $nudge->title($channel, $owner));
        $this->assertStringContainsString('دعوت شده‌اید', $nudge->body($channel, $invited));
        $this->assertStringContainsString('پیام خوانده‌نشده', $nudge->body($channel, $owner));
    }

    public function test_contact_nudge_shape_and_badge_suppresses_create_false(): void
    {
        $nudge = new ContactNudge();

        $this->assertSame('contacts-controller:nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(Message::class, $triggers[0]['class']);
        $this->assertContains('created', $triggers[0]['on']);
        $this->assertContains('forceDeleted', $triggers[0]['on']);
    }

    public function test_contact_nudge_for_primes_body_with_unread_counts_and_returns_empty_when_no_unread(): void
    {
        $sender = $this->activeUser();
        $recipient = $this->activeUser();
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => null]);

        $nudge = new ContactNudge();
        $recipients = $nudge->for($sender);

        $this->assertSame([$recipient->id], $recipients->pluck('id')->all());
        $this->assertStringContainsString('1 پیام خوانده‌نشده', $nudge->body($sender, $recipient));
        $this->assertSame('پیام جدید از: ' . $sender->name, $nudge->title($sender, $recipient));

        $empty = $this->activeUser();
        $nudge2 = new ContactNudge();
        $this->assertCount(0, $nudge2->for($empty));
    }

    public function test_dms_nudge_shape_and_badge_suppresses_create_false(): void
    {
        $nudge = new DmsNudge();
        $dms = new DMS();
        $dms->id = 3;

        $this->assertSame('dms-controller:nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(DMS::class, $triggers[0]['class']);
        $this->assertSame(Read::class, $triggers[1]['class']);
        $this->assertStringContainsString('/dms', $nudge->url($dms));
        $this->assertStringContainsString('open=3', $nudge->url($dms));
    }

    public function test_dms_nudge_for_primes_title_distinguishing_sign_from_read(): void
    {
        $user = $this->activeUser();
        $dms = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL'], 'users' => [], 'type' => true, 'title' => 'Doc-1']);

        $nudge = new DmsNudge();
        $nudge->for($dms);

        $this->assertStringContainsString('نیازمند تأیید', $nudge->title($dms, $user));
        $this->assertStringContainsString('نیازمند تأیید/امضای شماست', $nudge->body($dms, $user));

        Read::factory()->create(['document_id' => $dms->id, 'user_id' => $user->id, 'read' => true, 'read_count' => 0]);

        $nudge2 = new DmsNudge();
        $recipients = $nudge2->for($dms);
        $this->assertContains($user->id, $recipients->pluck('id')->all());
        $this->assertStringContainsString('نیازمند مطالعه', $nudge2->title($dms, $user));
        $this->assertStringContainsString('تأیید کرده‌اید', $nudge2->body($dms, $user));
    }

    public function test_feed_nudge_shape_key_triggers_and_url(): void
    {
        $nudge = new FeedNudge();
        $feed = new Feed();
        $feed->id = 9;

        $this->assertSame('feeds:nudge', $nudge->getKey());
        $this->assertSame([
            ['class' => Feed::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ], $nudge->triggers());
        $this->assertTrue($nudge->refresh());
        $url = $nudge->url($feed);
        $this->assertStringContainsString('tab=feed', $url);
        $this->assertStringContainsString('open=9', $url);
    }

    public function test_feed_nudge_title_falls_back_through_feed_category_label(): void
    {
        $nudge = new FeedNudge();
        $known = new Feed();
        $known->category = FeedCategory::General->value;
        $unknown = new Feed();
        $unknown->category = 'nonexistent-category';

        $this->assertStringContainsString(FeedCategory::General->getLabel(), $nudge->title($known, $this->activeUser()));
        $this->assertSame('خبر جدید', $nudge->title($unknown, $this->activeUser()));
    }

    public function test_photo_nudge_shape_key_triggers_and_url(): void
    {
        $nudge = new PhotoNudge();
        $photo = new Photo();
        $photo->id = 4;

        $this->assertSame('gallery-controller:nudge', $nudge->getKey());
        $this->assertSame([
            ['class' => Photo::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ], $nudge->triggers());
        $this->assertTrue($nudge->refresh());
        $url = $nudge->url($photo);
        $this->assertStringContainsString('tab=gallery', $url);
        $this->assertStringContainsString('open=4', $url);
    }

    public function test_photo_nudge_for_scopes_by_department_and_broadcasts_when_all_departments_empty(): void
    {
        $inCode = 'T' . Str::random(6);
        $outCode = 'T' . Str::random(6);
        Department::factory()->create(['code' => $inCode, 'name' => $inCode, 'description' => $inCode]);
        Department::factory()->create(['code' => $outCode, 'name' => $outCode, 'description' => $outCode]);
        $inDept = $this->activeUser();
        $outDept = $this->activeUser();
        Profile::factory()->create(['user_id' => $inDept->id, 'department_id' => $inCode]);
        Profile::factory()->create(['user_id' => $outDept->id, 'department_id' => $outCode]);

        $nudge = new PhotoNudge();
        $public = new Photo();
        $public->department_id = null;
        $public->departments = null;

        $this->assertContains($inDept->id, $nudge->for($public)->pluck('id')->all());

        $scoped = new Photo();
        $scoped->department_id = $inCode;
        $scoped->departments = [];

        $ids = $nudge->for($scoped)->pluck('id')->all();
        $this->assertContains($inDept->id, $ids);
        $this->assertNotContains($outDept->id, $ids);
    }

    public function test_photo_nudge_no_longer_adds_the_ma_department_to_a_restricted_audience(): void
    {
        $inCode = 'T' . Str::random(6);
        Department::factory()->create(['code' => $inCode, 'name' => $inCode, 'description' => $inCode]);
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        $inDept = $this->activeUser();
        $maUser = $this->activeUser();
        Profile::factory()->create(['user_id' => $inDept->id, 'department_id' => $inCode]);
        Profile::factory()->create(['user_id' => $maUser->id, 'department_id' => 'MA']);

        $photo = new Photo();
        $photo->department_id = $inCode;
        $photo->departments = null;

        $ids = (new PhotoNudge())->for($photo)->pluck('id')->all();

        $this->assertContains($inDept->id, $ids);
        $this->assertNotContains($maUser->id, $ids);
    }

    public function test_feed_post_photo_and_report_nudges_show_only_while_the_subject_is_active(): void
    {
        $user = $this->activeUser();

        foreach ([[new FeedNudge(), Feed::class], [new PostNudge(), Post::class], [new PhotoNudge(), Photo::class], [new ReportNudge(), Report::class]] as [$nudge, $model]) {
            $this->assertTrue($nudge->show(new $model(['active' => true]), $user), $model);
            $this->assertFalse($nudge->show(new $model(['active' => false]), $user), $model);
        }
    }

    public function test_audience_nudges_create_rows_only_for_audience_users_and_deactivation_deletes_them(): void
    {
        $inCode = 'T' . Str::random(6);
        $outCode = 'T' . Str::random(6);
        Department::factory()->create(['code' => $inCode, 'name' => $inCode, 'description' => $inCode]);
        Department::factory()->create(['code' => $outCode, 'name' => $outCode, 'description' => $outCode]);
        $inDept = $this->activeUser();
        $outDept = $this->activeUser();
        Profile::factory()->create(['user_id' => $inDept->id, 'department_id' => $inCode]);
        Profile::factory()->create(['user_id' => $outDept->id, 'department_id' => $outCode]);

        NudgeService::register(new FeedNudge());
        $feed = Feed::factory()->create(['departments' => [$inCode], 'active' => true]);
        $public = Feed::factory()->create(['departments' => null, 'active' => true]);
        NudgeService::reconcile('feeds:nudge', Feed::class, $feed->id);
        NudgeService::reconcile('feeds:nudge', Feed::class, $public->id);

        $rowsFor = fn(User $user, Feed $subject): int => $this->notificationsFor('feeds:nudge', $user->id)
            ->filter(fn($row) => (json_decode($row->data, true)['item_id'] ?? null) === (string) $subject->id)
            ->count();

        $this->assertSame(1, $rowsFor($inDept, $feed));
        $this->assertSame(0, $rowsFor($outDept, $feed));
        $this->assertSame(1, $rowsFor($outDept, $public));

        $feed->update(['active' => false]);
        NudgeService::reconcile('feeds:nudge', Feed::class, $feed->id);

        $this->assertSame(0, $rowsFor($inDept, $feed));
        $this->assertSame(1, $rowsFor($inDept, $public));
    }

    public function test_audience_nudge_payload_is_shared_byte_identically_and_audience_users_carry_only_the_id(): void
    {
        $first = $this->activeUser();
        $second = $this->activeUser();
        $third = $this->activeUser();

        NudgeService::register(new FeedNudge());
        $feed = Feed::factory()->create(['departments' => null, 'active' => true]);

        $audience = $feed->audienceUsers();
        $this->assertSame(['id'], array_keys($audience->first()->getAttributes()));
        $this->assertTrue($audience->pluck('id')->contains($first->id));

        NudgeService::reconcile('feeds:nudge', Feed::class, $feed->id);

        $data = collect([$first, $second, $third])
            ->map(fn(User $user) => $this->notificationsFor('feeds:nudge', $user->id)
                ->filter(fn($row) => (json_decode($row->data, true)['item_id'] ?? null) === (string) $feed->id)
                ->pluck('data')
                ->all());

        $this->assertCount(1, $data[0]);
        $this->assertSame($data[0], $data[1]);
        $this->assertSame($data[0], $data[2]);
    }

    public function test_deactivating_an_audience_item_bulk_deletes_every_row_and_bumps_the_notification_version(): void
    {
        $users = [$this->activeUser(), $this->activeUser(), $this->activeUser()];

        NudgeService::register(new FeedNudge());
        $feed = Feed::factory()->create(['departments' => null, 'active' => true]);
        NudgeService::reconcile('feeds:nudge', Feed::class, $feed->id);

        $rows = fn() => collect($users)->sum(fn(User $user) => $this->notificationsFor('feeds:nudge', $user->id)
            ->filter(fn($row) => (json_decode($row->data, true)['item_id'] ?? null) === (string) $feed->id)
            ->count());
        $this->assertSame(3, $rows());

        $before = ModelCacheVersion::version(DatabaseNotification::class);
        $feed->update(['active' => false]);
        NudgeService::reconcile('feeds:nudge', Feed::class, $feed->id);

        $this->assertSame(0, $rows());
        $this->assertNotSame($before, ModelCacheVersion::version(DatabaseNotification::class));
    }

    public function test_post_nudge_shape_key_triggers_and_url(): void
    {
        $nudge = new PostNudge();
        $post = new Post();
        $post->id = 12;

        $this->assertSame('posts-controller:nudge', $nudge->getKey());
        $this->assertSame([
            ['class' => Post::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ], $nudge->triggers());
        $this->assertTrue($nudge->refresh());
        $this->assertFalse($nudge->badgeSuppressesCreate(), 'Post has no "mark all read on tab open" fallback, so a user with another unread post must still get this one');
        $url = $nudge->url($post);
        $this->assertStringContainsString('tab=post', $url);
        $this->assertStringContainsString('open=12', $url);
    }

    public function test_post_nudge_still_creates_a_row_for_a_user_with_an_unread_posts_controller_badge(): void
    {
        $user = $this->activeUser();
        $post = Post::factory()->create();
        $this->insertNotification('posts-controller', $user->id, null);

        NudgeService::register(new PostNudge());
        NudgeService::reconcile('posts-controller:nudge', Post::class, $post->id);

        $rows = $this->notificationsFor('posts-controller:nudge', $user->id)
            ->filter(fn($row) => (json_decode($row->data, true)['item_id'] ?? null) === (string) $post->id);
        $this->assertCount(1, $rows, 'a user with an unread aggregate posts badge must still get an individual row for a new post');
    }

    public function test_post_nudge_title_prepends_prefix_and_sanitizes_subject_title(): void
    {
        $nudge = new PostNudge();
        $post = new Post();
        $post->title = 'اعلان ویژه';

        $title = $nudge->title($post, $this->activeUser());
        $this->assertStringStartsWith('اطلاعیه جدید: ', $title);
        $this->assertStringContainsString('اعلان ویژه', $title);
    }

    public function test_report_nudge_shape_key_triggers_and_url(): void
    {
        $nudge = new ReportNudge();
        $report = new Report();
        $report->id = 8;

        $this->assertSame('reports-controller:nudge', $nudge->getKey());
        $this->assertSame([
            ['class' => Report::class, 'on' => ['created', 'updated', 'deleted'], 'subject' => null],
        ], $nudge->triggers());
        $this->assertTrue($nudge->refresh());
        $url = $nudge->url($report);
        $this->assertStringContainsString('tab=reports', $url);
        $this->assertStringContainsString('open=8', $url);
    }

    public function test_report_nudge_show_gates_on_active_and_title_falls_back_to_organization(): void
    {
        $nudge = new ReportNudge();
        $published = new Report();
        $published->active = true;
        $published->title = 'Q1';
        $draft = new Report();
        $draft->active = false;

        $this->assertTrue($nudge->show($published, $this->activeUser()));
        $this->assertFalse($nudge->show($draft, $this->activeUser()));
        $this->assertStringContainsString('سازمان', $nudge->title($published, $this->activeUser()));
        $this->assertStringContainsString('Q1', $nudge->title($published, $this->activeUser()));
    }

    public function test_shared_events_nudge_shape_badge_suppresses_create_false_and_triggers(): void
    {
        $nudge = new SharedEventsNudge();
        $event = new Event();
        $event->id = 6;

        $this->assertSame('shared-events:nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(EventShare::class, $triggers[0]['class']);
        $this->assertSame(['created', 'deleted'], $triggers[0]['on']);
        $this->assertSame(Event::class, $triggers[1]['class']);
        $this->assertSame(['updated', 'deleted'], $triggers[1]['on']);
        $url = $nudge->url($event);
        $this->assertStringContainsString('tab=calendar', $url);
        $this->assertStringContainsString('open=6', $url);
    }

    public function test_shared_events_nudge_show_and_for_primes_body_for_owner_and_recipient(): void
    {
        $owner = $this->activeUser();
        $recipient = $this->activeUser();
        $event = Event::factory()->create(['user_id' => $owner->id, 'date' => now()->addDay(), 'title' => 'Picnic']);
        EventShare::create(['event_id' => $event->id, 'user_id' => $recipient->id, 'shared_by' => $owner->id]);

        $nudge = new SharedEventsNudge();
        $recipients = $nudge->for($event);
        $ids = $recipients->pluck('id')->all();
        $this->assertContains($owner->id, $ids);
        $this->assertContains($recipient->id, $ids);

        $this->assertTrue($nudge->show($event, $owner));
        $this->assertTrue($nudge->show($event, $recipient));
        $this->assertStringContainsString('رویداد شما به اشتراک گذاشته شد', $nudge->title($event, $owner));
        $this->assertStringContainsString('رویداد مشترک', $nudge->title($event, $recipient));
        $this->assertStringContainsString('توسط شما', $nudge->body($event, $owner));
        $this->assertStringContainsString('توسط یکی از همکاران', $nudge->body($event, $recipient));

        $past = Event::factory()->create(['user_id' => $owner->id, 'date' => now()->subDay()]);
        $this->assertFalse($nudge->show($past, $owner));
    }

    public function test_suggestion_nudge_shape_key_triggers_and_url(): void
    {
        $nudge = new SuggestionNudge();
        $suggestion = new Suggestion();
        $suggestion->id = 11;

        $this->assertSame('suggestion-controller:nudge', $nudge->getKey());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(Suggestion::class, $triggers[0]['class']);
        $this->assertSame(['created', 'updated', 'deleted'], $triggers[0]['on']);
        $this->assertSame(\App\Models\Review::class, $triggers[1]['class']);
        $this->assertSame(['created', 'updated'], $triggers[1]['on']);
        $this->assertStringContainsString('/suggestion', $nudge->url($suggestion));
        $this->assertStringContainsString('open=11', $nudge->url($suggestion));
    }

    public function test_suggestion_nudge_for_scopes_recipients_to_suggestion_departments_and_show_uses_attention(): void
    {
        $inCode = 'T' . Str::random(6);
        $outCode = 'T' . Str::random(6);
        Department::factory()->create(['code' => $inCode, 'name' => $inCode, 'description' => $inCode]);
        Department::factory()->create(['code' => $outCode, 'name' => $outCode, 'description' => $outCode]);
        $inDept = $this->activeUser();
        $outDept = $this->activeUser();
        Profile::factory()->create(['user_id' => $inDept->id, 'department_id' => $inCode, 'position' => 'staff']);
        Profile::factory()->create(['user_id' => $outDept->id, 'department_id' => $outCode, 'position' => 'staff']);
        $suggestion = Suggestion::factory()->create(['departments' => [$inCode], 'stage' => 'pending']);

        $nudge = new SuggestionNudge();
        $ids = $nudge->for($suggestion)->pluck('id')->all();
        $this->assertContains($inDept->id, $ids);
        $this->assertNotContains($outDept->id, $ids);
        $this->assertFalse($nudge->show($suggestion, $inDept));
    }

    public function test_task_nudge_shape_key_triggers_and_url(): void
    {
        $nudge = new TaskNudge();
        $task = new Task();
        $task->id = 14;

        $this->assertSame('tasks-controller:nudge', $nudge->getKey());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(Task::class, $triggers[0]['class']);
        $this->assertSame(['created', 'updated', 'deleted', 'restored', 'forceDeleted'], $triggers[0]['on']);
        $this->assertSame(\App\Models\Reply::class, $triggers[1]['class']);
        $this->assertStringContainsString('/tasks', $nudge->url($task));
        $this->assertStringContainsString('open=14', $nudge->url($task));
    }

    public function test_task_nudge_for_uses_assignee_or_creator_as_owner_and_title_branches(): void
    {
        $owner = $this->activeUser();
        $creator = $this->activeUser();
        $task = Task::factory()->create(['assigned_to' => $owner->id, 'user_id' => $creator->id, 'status' => 'todo', 'title' => 'Build']);

        $nudge = new TaskNudge();
        $recipients = $nudge->for($task);

        $this->assertSame([$owner->id], $recipients->pluck('id')->all());
        $this->assertStringContainsString('وظیفه جدید', $nudge->title($task, $owner));
        $this->assertStringContainsString('به شما ارجاع شد', $nudge->body($task, $owner));
        $this->assertTrue($nudge->show($task, $owner));
        $this->assertFalse($nudge->show($task, $creator));

        $selfTask = Task::factory()->create(['assigned_to' => $creator->id, 'user_id' => $creator->id, 'status' => 'todo']);
        $this->assertFalse($nudge->show($selfTask, $creator));

        $unassigned = Task::factory()->create(['assigned_to' => null, 'user_id' => $creator->id, 'status' => 'todo', 'title' => 'Solo']);
        $this->assertCount(0, $nudge->for($unassigned));
    }

    public function test_ths_nudge_shape_badge_suppresses_create_false_and_triggers(): void
    {
        $nudge = new ThsNudge();
        $ticket = new Ticket();
        $ticket->id = 21;

        $this->assertSame('ths-controller:nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(Ticket::class, $triggers[0]['class']);
        $this->assertSame(['created', 'updated', 'deleted'], $triggers[0]['on']);
        $this->assertCount(1, $triggers);
        $this->assertStringContainsString('/ths', $nudge->url($ticket));
        $this->assertStringContainsString('open=21', $nudge->url($ticket));
    }

    public function test_ths_reply_nudge_has_its_own_key_and_never_shares_the_ticket_key(): void
    {
        $nudge = new ThsReplyNudge();

        $this->assertSame('ths-controller:reply-nudge', $nudge->getKey());
        $this->assertNotSame((new ThsNudge())->getKey(), $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $triggers = $nudge->triggers();
        $this->assertSame(\App\Models\Reply::class, $triggers[0]['class']);
        $this->assertSame(['created', 'deleted'], $triggers[0]['on']);
        $this->assertSame(Ticket::class, $triggers[1]['class']);
        $this->assertSame(['updated'], $triggers[1]['on']);
    }

    public function test_ths_reply_nudge_reply_trigger_uses_the_reply_as_subject_and_resolves_the_ticket_for_every_hook(): void
    {
        $assignee = $this->activeUser();
        $requester = $this->activeUser();
        $ticket = Ticket::factory()->create([
            'assigned_to' => $assignee->id, 'requester_id' => $requester->id,
            'status' => 'in-progress', 'request_subject' => 'Help',
        ]);
        $reply = $ticket->addReply($assignee, 'looking');

        $nudge = new ThsReplyNudge();
        $resolver = $nudge->triggers()[0]['subject'];

        $this->assertTrue($reply->is($resolver($reply)));
        $foreign = new \App\Models\Reply(['repliable_type' => Task::class]);
        $this->assertNull($resolver($foreign));
        $this->assertStringContainsString('open=' . $ticket->id, $nudge->url($reply));
        $this->assertContains($requester->id, $nudge->for($reply)->pluck('id')->all());
        $this->assertStringContainsString('پاسخ جدید: Help', $nudge->title($reply, $requester));
        $this->assertTrue($nudge->show($reply, $requester));
        $this->assertTrue($nudge->show($reply, $assignee));
    }

    public function test_ths_nudge_for_uses_current_action_recipient_and_title_branches_by_status(): void
    {
        $assignee = $this->activeUser();
        $requester = $this->activeUser();
        $ticket = Ticket::factory()->create([
            'assigned_to' => $assignee->id, 'requester_id' => $requester->id,
            'status' => 'in-progress', 'request_subject' => 'Help',
        ]);

        $nudge = new ThsNudge();
        $recipients = $nudge->for($ticket);

        $this->assertContains($assignee->id, $recipients->pluck('id')->all());
        $this->assertStringContainsString('تیکت محول‌شده به شما', $nudge->title($ticket, $assignee));
        $this->assertStringContainsString('محول شده است', $nudge->body($ticket, $assignee));
        $this->assertTrue($nudge->show($ticket, $assignee));
    }

    public function test_task_overdue_nudge_shape_key_triggers_url_and_badge_suppress_false(): void
    {
        $nudge = new TaskNudge('overdue');
        $task = new Task();
        $task->id = 33;

        $this->assertSame('tasks-controller:overdue-nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(Task::class, $triggers[0]['class']);
        $this->assertSame(['updated', 'deleted', 'restored', 'forceDeleted'], $triggers[0]['on']);
        $this->assertStringContainsString('/tasks', $nudge->url($task));
        $this->assertStringContainsString('open=33', $nudge->url($task));
    }

    public function test_task_overdue_nudge_show_is_true_only_while_urgency_state_is_overdue(): void
    {
        $owner = $this->activeUser();
        $nudge = new TaskNudge('overdue');

        $overdue = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => now()->subDays(2), 'title' => 'Late']);
        $this->assertSame([$owner->id], $nudge->for($overdue)->pluck('id')->all());
        $this->assertTrue($nudge->show($overdue, $owner));
        $this->assertStringContainsString('سررسید گذشته', $nudge->title($overdue, $owner));
        $this->assertStringContainsString('Late', $nudge->body($overdue, $owner));

        $future = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => now()->addDays(2)]);
        $this->assertCount(0, $nudge->for($future));
        $this->assertFalse($nudge->show($future, $owner));

        $doneButLate = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'done', 'deadline' => now()->subDays(2)]);
        $this->assertCount(0, $nudge->for($doneButLate));
        $this->assertFalse($nudge->show($doneButLate, $owner));

        $noDeadline = Task::factory()->create(['assigned_to' => $owner->id, 'status' => 'todo', 'deadline' => null]);
        $this->assertCount(0, $nudge->for($noDeadline));
        $this->assertFalse($nudge->show($noDeadline, $owner));
    }

    public function test_badge_legend_catalog_includes_edge_toast_entries_for_channels_and_projects(): void
    {
        $channelEdge = BadgeLegendCatalog::get('channels-controller:edge');
        $projectEdge = BadgeLegendCatalog::get('projects-controller:edge');

        $this->assertNotNull($channelEdge);
        $this->assertSame('notifications', $channelEdge['group']);
        $this->assertSame('alternate_email', $channelEdge['icon']);
        $this->assertNotEmpty($channelEdge['label']);

        $this->assertNotNull($projectEdge);
        $this->assertSame('tasks', $projectEdge['group']);
        $this->assertSame('group_add', $projectEdge['icon']);
        $this->assertNotEmpty($projectEdge['label']);
    }

    public function test_channel_toast_shape_key_triggers_and_url(): void
    {
        $toast = new ChannelToast();
        $channel = new Channel();
        $channel->id = 7;

        $this->assertSame('channels-controller:edge', $toast->getKey());
        $triggers = $toast->triggers();
        $this->assertCount(4, $triggers);
        $this->assertSame(Channel::class, $triggers[0]['class']);
        $this->assertSame(['deleted', 'forceDeleted'], $triggers[0]['on']);
        $this->assertSame(ChannelMessage::class, $triggers[3]['class']);
        $this->assertSame(['created', 'deleted'], $triggers[3]['on']);
        $url = $toast->url($channel);
        $this->assertStringContainsString('/channels', $url);
        $this->assertStringContainsString('open=7', $url);
    }

    public function test_project_toast_shape_key_triggers_and_url(): void
    {
        $toast = new ProjectToast();
        $project = new Project();
        $project->id = 5;
        $project->name = 'Alpha';

        $this->assertSame('projects-controller:edge', $toast->getKey());
        $triggers = $toast->triggers();
        $this->assertCount(4, $triggers);
        $this->assertSame(Project::class, $triggers[0]['class']);
        $this->assertSame(['created', 'deleted', 'forceDeleted'], $triggers[0]['on']);
        $this->assertSame(Reply::class, $triggers[1]['class']);
        $url = $toast->url($project);
        $this->assertStringContainsString('/projects', $url);
        $this->assertStringContainsString('open=5', $url);
    }

    public function test_task_approval_nudge_shape_key_triggers_url_and_badge_suppress_false(): void
    {
        $nudge = new TaskNudge('approval');
        $task = new Task();
        $task->id = 41;

        $this->assertSame('tasks-controller:approval-nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $this->assertTrue($nudge->refresh());
        $triggers = $nudge->triggers();
        $this->assertSame(Task::class, $triggers[0]['class']);
        $this->assertSame(['updated', 'deleted', 'restored', 'forceDeleted'], $triggers[0]['on']);
        $this->assertStringContainsString('/tasks', $nudge->url($task));
        $this->assertStringContainsString('open=41', $nudge->url($task));
    }

    public function test_task_approval_nudge_show_for_and_body_route_to_the_project_owner(): void
    {
        $owner = $this->activeUser();
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null, 'title' => 'Final polish']);
        $nudge = new TaskNudge('approval');

        $this->assertSame([$owner->id], $nudge->for($task)->pluck('id')->all());
        $this->assertTrue($nudge->show($task, $owner));
        $this->assertStringContainsString('وظیفهٔ منتظر تأیید', $nudge->title($task, $owner));
        $this->assertStringContainsString('Final polish', $nudge->body($task, $owner));
        $this->assertStringContainsString('منتظر تأیید شماست', $nudge->body($task, $owner));

        Task::find($task->id)->forceFill(['approved_at' => now()])->save();

        $this->assertCount(0, $nudge->for($task->fresh()));
        $this->assertFalse($nudge->show($task->fresh(), $owner));
    }

    public function test_task_approval_nudge_title_escalates_after_24_and_48_hours_pending(): void
    {
        $owner = $this->activeUser();
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        $nudge = new TaskNudge('approval');

        $fresh = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null, 'title' => 'Fresh approval']);
        $dayOld = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null, 'title' => 'Day old approval']);
        $twoDayOld = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null, 'title' => 'Two day old approval']);
        Task::find($dayOld->id)->forceFill(['completed_at' => now()->subHours(25)])->save();
        Task::find($twoDayOld->id)->forceFill(['completed_at' => now()->subHours(49)])->save();

        $nudge->for($fresh);
        $this->assertSame('وظیفهٔ منتظر تأیید: Fresh approval', $nudge->title($fresh, $owner));
        $nudge->for($dayOld->fresh());
        $this->assertSame('یادآوری: وظیفهٔ منتظر تأیید: Day old approval', $nudge->title($dayOld->fresh(), $owner));
        $nudge->for($twoDayOld->fresh());
        $this->assertSame('⏰ فوری: وظیفهٔ منتظر تأیید: Two day old approval', $nudge->title($twoDayOld->fresh(), $owner));
    }

    public function test_task_approval_nudge_never_fires_when_the_project_owner_completes_their_own_task(): void
    {
        $owner = $this->activeUser();
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('todo')->create(['approved_at' => null]);

        $this->actingAs($owner);
        Task::find($task->id)->update(['status' => 'done']);

        $nudge = new TaskNudge('approval');
        $this->assertCount(0, $nudge->for($task->fresh()));
        $this->assertFalse($nudge->show($task->fresh(), $owner));
        $this->assertNotNull($task->fresh()->approved_at);
    }

    public function test_tasks_imminent_indicator_shape_and_dark_without_auth_window_or_ownership(): void
    {
        $indicator = new TasksImminent();

        $this->assertSame('tasks-deadline', $indicator->getKey());
        $this->assertSame('وظیفهٔ نزدیک به سررسید', $indicator->getTitle());
        $this->assertNotEmpty($indicator->getBody());
        $this->assertFalse($indicator->isActive());

        $other = $this->activeUser();
        Task::factory()->create(['assigned_to' => $other->id, 'user_id' => $other->id, 'status' => 'todo', 'deadline' => now()->subDays(1)]);

        $user = $this->activeUser();
        $this->actingAs($user);
        $this->assertFalse((new TasksImminent())->isActive());

        Task::factory()->create(['assigned_to' => $user->id, 'user_id' => $user->id, 'status' => 'todo', 'deadline' => now()->addDays(10)]);
        $this->assertFalse((new TasksImminent())->isActive());
    }

    public function test_tasks_imminent_indicator_lights_for_due_and_overdue_tasks_of_assignee_and_creator(): void
    {
        $assignee = $this->activeUser();
        $this->actingAs($assignee);
        Task::factory()->create(['assigned_to' => $assignee->id, 'user_id' => $this->activeUser()->id, 'status' => 'todo', 'deadline' => now()->addDays(2)]);
        $this->assertTrue((new TasksImminent())->isActive());

        $creator = $this->activeUser();
        $this->actingAs($creator);
        Task::factory()->create(['assigned_to' => null, 'user_id' => $creator->id, 'status' => 'in-progress', 'deadline' => now()->subDays(2)]);
        $this->assertTrue((new TasksImminent())->isActive());
    }

    public function test_tasks_imminent_indicator_excludes_done_and_pending_statuses(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);

        Task::factory()->create(['assigned_to' => $user->id, 'user_id' => $user->id, 'status' => 'done', 'deadline' => now()->subDays(2)]);
        $this->assertFalse((new TasksImminent())->isActive());

        Task::factory()->create(['assigned_to' => $user->id, 'user_id' => $user->id, 'status' => 'pending', 'deadline' => now()->addDays(1)]);
        $this->assertFalse((new TasksImminent())->isActive());

        Task::factory()->create(['assigned_to' => $user->id, 'user_id' => $user->id, 'status' => 'todo', 'deadline' => now()->subDays(2)]);
        $this->assertTrue((new TasksImminent())->isActive());
    }

    public function test_tasks_pending_approval_indicator_shape_and_dark_without_auth(): void
    {
        $indicator = new TasksPendingApproval();

        $this->assertSame('tasks-pending-approval', $indicator->getKey());
        $this->assertSame('وظیفهٔ منتظر تأیید', $indicator->getTitle());
        $this->assertNotEmpty($indicator->getBody());
        $this->assertFalse($indicator->isActive());
    }

    public function test_tasks_pending_approval_indicator_lights_for_owner_when_a_done_task_awaits_approval(): void
    {
        $owner = $this->activeUser();
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);
        $this->actingAs($owner);

        $this->assertTrue((new TasksPendingApproval())->isActive());
    }

    public function test_tasks_pending_approval_indicator_stays_dark_for_rival_owner_missing_flag_approved_or_open_tasks(): void
    {
        $owner = $this->activeUser();
        $rival = $this->activeUser();
        $this->actingAs($owner);

        $rivalProject = Project::factory()->create([
            'owner_id' => $rival->id, 'member_ids' => [], 'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        Task::factory()->forProject($rivalProject)->ownedBy($rival)->withStatus('done')->create(['approved_at' => null]);
        $this->assertFalse((new TasksPendingApproval())->isActive());

        $noFlag = Project::factory()->create([
            'owner_id' => $owner->id, 'member_ids' => [], 'departments' => [],
            'settings' => ['requires_approval' => false],
        ]);
        Task::factory()->forProject($noFlag)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);
        $this->assertFalse((new TasksPendingApproval())->isActive());

        $gated = Project::factory()->create([
            'owner_id' => $owner->id, 'member_ids' => [], 'departments' => [],
            'settings' => ['requires_approval' => true],
        ]);
        Task::factory()->forProject($gated)->ownedBy($owner)->withStatus('in-progress')->create(['approved_at' => null]);
        $this->assertFalse((new TasksPendingApproval())->isActive());

        Task::factory()->forProject($gated)->ownedBy($owner)->withStatus('done')->create(['approved_at' => now()]);
        $this->assertFalse((new TasksPendingApproval())->isActive());

        $pending = Task::factory()->forProject($gated)->ownedBy($owner)->withStatus('done')->create();
        Task::whereKey($pending->id)->toBase()->update(['approved_at' => null]);
        $this->assertTrue((new TasksPendingApproval())->isActive());
    }

    public function test_project_nudge_shape_key_triggers_and_url(): void
    {
        $nudge = new ProjectNudge();
        $project = new Project();
        $project->id = 5;
        $project->name = 'Alpha';
        $user = $this->activeUser();

        $this->assertSame('projects-controller:nudge', $nudge->getKey());
        $triggers = $nudge->triggers();
        $this->assertCount(2, $triggers);
        $this->assertSame(Project::class, $triggers[0]['class']);
        $this->assertSame(['created', 'deleted', 'forceDeleted'], $triggers[0]['on']);
        $this->assertNull($triggers[0]['subject']);
        $this->assertSame(Reply::class, $triggers[1]['class']);
        $this->assertSame(['created'], $triggers[1]['on']);
        $this->assertIsCallable($triggers[1]['subject']);
        $url = $nudge->url($project);
        $this->assertStringContainsString('/projects', $url);
        $this->assertStringContainsString('open=5', $url);
        $this->assertTrue($nudge->refresh());
        $this->assertFalse($nudge->show($project, $user));
    }

    public function test_project_nudge_trigger_subject_gates_on_repliable_type_activity_type_and_added_payload(): void
    {
        $resolveSubject = (new ProjectNudge())->triggers()[1]['subject'];

        $taskReply = new Reply(['repliable_type' => Task::class, 'type' => TaskActivityType::Assignment, 'payload' => ['added' => [1]]]);
        $this->assertNull($resolveSubject($taskReply));

        $comment = new Reply(['repliable_type' => Project::class, 'type' => TaskActivityType::Comment, 'payload' => ['added' => [1]]]);
        $this->assertNull($resolveSubject($comment));

        $emptyAdded = new Reply(['repliable_type' => Project::class, 'type' => TaskActivityType::Assignment, 'payload' => []]);
        $this->assertNull($resolveSubject($emptyAdded));

        $owner = $this->activeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $assignment = Reply::factory()->create([
            'repliable_type' => Project::class,
            'repliable_id' => $project->id,
            'user_id' => $owner->id,
            'type' => TaskActivityType::Assignment,
            'payload' => ['added' => [$owner->id]],
        ]);

        $this->assertSame($project->id, $resolveSubject($assignment)->id);
    }

    public function test_project_nudge_for_prefers_assignment_reply_payload_and_drops_inactive_members(): void
    {
        $owner = $this->activeUser();
        $memberOnly = $this->activeUser();
        $added = $this->activeUser();
        $inactive = User::factory()->create(['role' => 'user', 'status' => 'inactive']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$memberOnly->id, $added->id, $inactive->id],
            'departments' => [],
        ]);
        Project::whereKey($project->id)->toBase()->update(['created_at' => now()->subHour()]);
        $project = $project->fresh();
        Reply::factory()->create([
            'repliable_type' => Project::class,
            'repliable_id' => $project->id,
            'user_id' => $owner->id,
            'type' => TaskActivityType::Assignment,
            'payload' => ['added' => [$added->id, $inactive->id]],
        ]);

        $ids = (new ProjectNudge())->for($project)->pluck('id')->all();

        $this->assertSame([$added->id], $ids);
    }

    public function test_project_nudge_for_falls_back_to_member_ids_excluding_owner(): void
    {
        $owner = $this->activeUser();
        $member = $this->activeUser();
        $inactive = User::factory()->create(['role' => 'user', 'status' => 'inactive']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$owner->id, $member->id, $inactive->id],
            'departments' => [],
        ]);

        $ids = (new ProjectNudge())->for($project)->pluck('id')->all();
        $this->assertSame([$member->id], $ids);

        $memberless = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);
        $this->assertCount(0, (new ProjectNudge())->for($memberless));
    }

    public function test_task_due_soon_toast_shape_key_triggers_url_and_icon(): void
    {
        $toast = new TaskDueSoonToast();
        $task = new Task();
        $task->id = 17;
        $task->title = 'Renew domain';
        $user = $this->activeUser();

        $this->assertSame('tasks-controller:due-soon-edge', $toast->getKey());
        $this->assertSame([
            ['class' => Task::class, 'on' => ['created', 'updated'], 'subject' => null],
        ], $toast->triggers());
        $url = $toast->url($task);
        $this->assertStringContainsString('/tasks', $url);
        $this->assertStringContainsString('open=17', $url);
        $this->assertSame('وظیفهٔ نزدیک به سررسید: Renew domain', $toast->title($task, $user));
        $this->assertStringContainsString('Renew domain', $toast->body($task, $user));
        $this->assertSame('schedule', $toast->icon($task, $user));
    }

    public function test_task_due_soon_toast_show_gates_on_null_done_past_and_24h_boundary(): void
    {
        $toast = new TaskDueSoonToast();
        $user = $this->activeUser();

        $this->assertFalse($toast->show(new Task(['status' => 'todo', 'deadline' => null]), $user));
        $this->assertFalse($toast->show(new Task(['status' => 'done', 'deadline' => now()->addHours(12)]), $user));
        $this->assertFalse($toast->show(new Task(['status' => 'pending', 'deadline' => now()->addHours(12)]), $user));
        $this->assertFalse($toast->show(new Task(['status' => 'todo', 'deadline' => now()->subHours(2)]), $user));
        $this->assertFalse($toast->show(new Task(['status' => 'todo', 'deadline' => now()->addHours(25)]), $user));
        $this->assertTrue($toast->show(new Task(['status' => 'todo', 'deadline' => now()->addHours(24)]), $user));
        $this->assertTrue($toast->show(new Task(['status' => 'in-progress', 'deadline' => now()->addHours(5)]), $user));
    }

    public function test_task_due_soon_toast_for_returns_active_assignee_or_creator(): void
    {
        $toast = new TaskDueSoonToast();
        $assignee = $this->activeUser();
        $creator = $this->activeUser();
        $inactive = User::factory()->create(['role' => 'user', 'status' => 'inactive']);

        $assigned = new Task();
        $assigned->assigned_to = $assignee->id;
        $assigned->user_id = $creator->id;
        $this->assertSame([$assignee->id], $toast->for($assigned)->pluck('id')->all());

        $solo = new Task();
        $solo->assigned_to = null;
        $solo->user_id = $creator->id;
        $this->assertSame([$creator->id], $toast->for($solo)->pluck('id')->all());

        $deactivated = new Task();
        $deactivated->assigned_to = $inactive->id;
        $deactivated->user_id = $creator->id;
        $this->assertCount(0, $toast->for($deactivated));
    }

    private function makeReminder(User $user, array $overrides = []): Reminder
    {
        return Reminder::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test reminder',
            'due_at' => now(),
            'recurs' => 'none',
            'channels' => ['inapp:edge,badge,nudge'],
        ], $overrides));
    }

    public function test_reminder_overdue_nudge_shape_key_triggers_url_and_badge_suppress_false(): void
    {
        $nudge = new ReminderOverdueNudge();
        $user = $this->activeUser();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $reminder = $this->makeReminder($user, ['remindable_type' => Task::class, 'remindable_id' => $task->id]);

        $this->assertSame('reminders-controller:due-nudge', $nudge->getKey());
        $this->assertFalse($nudge->badgeSuppressesCreate());
        $triggers = $nudge->triggers();
        $this->assertSame(Reminder::class, $triggers[0]['class']);
        $this->assertSame(['created', 'updated', 'deleted'], $triggers[0]['on']);
        $this->assertStringContainsString('/tasks', $nudge->url($reminder));
        $this->assertStringContainsString('open=' . $task->id, $nudge->url($reminder));

        $bare = $this->makeReminder($user);
        $this->assertNull($nudge->url($bare));
    }

    public function test_reminder_url_builders_resolve_a_route_for_every_remindable_type(): void
    {
        $nudge = new ReminderOverdueNudge();
        $edge = new ReminderDueTodayEdge();
        $user = $this->activeUser();

        $ticket = Ticket::factory()->create();
        $project = Project::factory()->create();
        $dms = DMS::factory()->create();
        $reservation = Reservation::factory()->create();

        $cases = [
            [Ticket::class, $ticket->id, '/ths'],
            [Project::class, $project->id, '/projects'],
            [DMS::class, $dms->id, '/dms'],
            [Reservation::class, $reservation->id, '/reservation'],
        ];

        foreach ($cases as [$type, $id, $path]) {
            $reminder = $this->makeReminder($user, ['remindable_type' => $type, 'remindable_id' => $id]);

            $this->assertStringContainsString($path, $nudge->url($reminder));
            $this->assertStringContainsString('open=' . $id, $nudge->url($reminder));
            $this->assertStringContainsString($path, $edge->url($reminder));
            $this->assertStringContainsString('open=' . $id, $edge->url($reminder));

            $mailUrl = (new ReminderDueNotification($reminder))->toMail($user)->message->url;
            $this->assertStringContainsString($path, $mailUrl);
            $this->assertStringContainsString('open=' . $id, $mailUrl);
        }
    }

    public function test_reminder_overdue_nudge_show_is_true_for_due_or_overdue_reminders_and_false_when_completed_or_snoozed(): void
    {
        $nudge = new ReminderOverdueNudge();
        $user = $this->activeUser();

        $overdue = $this->makeReminder($user, ['due_at' => now()->subDay()]);
        $this->assertTrue($nudge->show($overdue, $user));

        $dueToday = $this->makeReminder($user, ['due_at' => now()]);
        $this->assertTrue($nudge->show($dueToday, $user));

        $completed = $this->makeReminder($user, ['due_at' => now()->subDay(), 'completed_at' => now()]);
        $this->assertFalse($nudge->show($completed, $user));

        $snoozed = $this->makeReminder($user, ['due_at' => now()->subDay(), 'snoozed_until' => now()->addHour()]);
        $this->assertFalse($nudge->show($snoozed, $user));

        $nudgeExcluded = $this->makeReminder($user, ['due_at' => now()->subDay(), 'channels' => ['inapp:edge,badge']]);
        $this->assertFalse($nudge->show($nudgeExcluded, $user));
    }

    public function test_reminder_due_today_edge_show_is_true_only_when_due_strictly_today_unlike_the_broader_nudge_window(): void
    {
        $edge = new ReminderDueTodayEdge();
        $nudge = new ReminderOverdueNudge();
        $user = $this->activeUser();

        $overdueByADay = $this->makeReminder($user, ['due_at' => now()->subDay()]);
        $this->assertTrue($nudge->show($overdueByADay, $user));
        $this->assertFalse($edge->show($overdueByADay, $user));

        $dueToday = $this->makeReminder($user, ['due_at' => now()]);
        $this->assertTrue($nudge->show($dueToday, $user));
        $this->assertTrue($edge->show($dueToday, $user));

        $edgeExcluded = $this->makeReminder($user, ['due_at' => now(), 'channels' => ['inapp:badge,nudge']]);
        $this->assertFalse($edge->show($edgeExcluded, $user));
    }

    public function test_edge_for_user_hides_off_module_rows_without_deleting_them_and_shows_them_again_when_on(): void
    {
        $user = $this->activeUser();
        $keys = ['channels-controller:edge', 'projects-controller:edge'];

        foreach ($keys as $i => $key) {
            Edge::create([
                'user_id' => $user->id,
                'edge_key' => $key,
                'subject_type' => Channel::class,
                'subject_id' => (string) (900 + $i),
                'title' => 'edge-title',
            ]);
        }

        try {
            config(['app.off' => ['projects']]);
            $this->assertSame(['channels-controller:edge'], array_column(EdgeService::forUser($user->id), 'key'));
            $this->assertSame(2, Edge::query()->where('user_id', $user->id)->count());

            config(['app.off' => []]);
            $this->assertEqualsCanonicalizing($keys, array_column(EdgeService::forUser($user->id), 'key'));
        } finally {
            config(['app.off' => []]);
        }
    }

    private function nudgeTask(User $doer, User $creator, array $attrs = [], ?Project $project = null): Task
    {
        $task = Task::factory()->create([
            'user_id' => $creator->id,
            'assigned_to' => $doer->id,
            'status' => 'in-progress',
            'deadline' => now()->addDays(10),
            'project_id' => $project?->id,
            ...$attrs,
        ]);
        Reply::where('repliable_type', Task::class)->where('repliable_id', $task->id)->delete();

        return Task::find($task->id);
    }

    private function taskReply(Task $task, ?User $actor, TaskActivityType $type, array $payload = [], ?string $body = null): Reply
    {
        return Reply::factory()->create([
            'repliable_type' => Task::class,
            'repliable_id' => $task->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'payload' => $payload,
            'body' => $body,
        ]);
    }

    public function test_task_activity_nudge_wording_per_event_and_the_actor_is_never_notified(): void
    {
        $creator = $this->activeUser();
        $doer = $this->activeUser();
        $nudge = new TaskNudge();

        $cases = [
            ['status_change', ['from' => 'todo', 'to' => 'in-progress'], 'وضعیت تغییر کرد'],
            ['status_change', ['from' => 'in-progress', 'to' => 'done'], 'وظیفه تکمیل شد'],
            ['status_change', ['from' => 'done', 'to' => 'todo'], 'بازگشایی شد'],
            ['approval', ['approved' => true], 'تأیید شد'],
            ['comment', [], 'پاسخ جدید'],
        ];

        foreach ($cases as [$type, $payload, $expected]) {
            $task = $this->nudgeTask($doer, $creator);
            $this->taskReply($task, $doer, TaskActivityType::from($type), $payload, $type === 'comment' ? 'hi' : null);

            $ids = $nudge->for($task)->pluck('id')->all();

            $this->assertSame([$creator->id], $ids, $type . ' ' . $expected);
            $this->assertStringContainsString($expected, $nudge->title($task, $creator));
            $this->assertFalse($nudge->show($task, $doer));
        }
    }

    public function test_task_activity_nudge_with_a_null_actor_notifies_everyone_involved(): void
    {
        $creator = $this->activeUser();
        $doer = $this->activeUser();
        $task = $this->nudgeTask($doer, $creator);
        $this->taskReply($task, null, TaskActivityType::StatusChange, ['from' => 'todo', 'to' => 'in-progress']);

        $ids = (new TaskNudge())->for($task)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$creator->id, $doer->id], $ids);
    }

    public function test_task_activity_nudge_ignores_quiet_events_and_system_approvals(): void
    {
        $creator = $this->activeUser();
        $doer = $this->activeUser();
        $task = $this->nudgeTask($doer, $creator);

        foreach (['archive', 'deadline_change', 'priority_change', 'label_change', 'project_change', 'meta_change', 'settings_change', 'workflow_step', 'department_change'] as $quiet) {
            $this->taskReply($task, $creator, TaskActivityType::from($quiet), ['from' => 'a', 'to' => 'b']);
        }
        $this->taskReply($task, null, TaskActivityType::Approval, ['approved' => true, 'system' => true, 'reason' => 'approval_disabled']);

        $this->assertCount(0, (new TaskNudge())->for($task));
    }

    public function test_task_activity_nudge_reassignment_helpers_and_accountable_bells(): void
    {
        $creator = $this->activeUser();
        $doer = $this->activeUser();
        $newDoer = $this->activeUser();
        $helper = $this->activeUser();
        $oldHelper = $this->activeUser();
        $nudge = new TaskNudge();

        $task = $this->nudgeTask($doer, $creator);
        $this->taskReply($task, $creator, TaskActivityType::Assignment, ['from' => $doer->id, 'to' => $newDoer->id]);
        $nudge->for($task);
        $this->assertStringContainsString('گرفته شد', $nudge->title($task, $doer));
        $this->assertStringContainsString('وظیفه جدید', $nudge->title($task, $newDoer));

        $this->taskReply($task, $creator, TaskActivityType::Assignment, ['added' => [$helper->id], 'removed' => [$oldHelper->id]]);
        $nudge->for($task);
        $this->assertStringContainsString('اضافه شدید', $nudge->title($task, $helper));
        $this->assertStringContainsString('حذف شدید', $nudge->title($task, $oldHelper));

        $this->taskReply($task, $creator, TaskActivityType::ResponsibleChange, ['from' => $oldHelper->id, 'to' => $helper->id]);
        $nudge->for($task);
        $this->assertStringContainsString('جوابگوی', $nudge->title($task, $helper));
        $this->assertStringContainsString('حذف شدید', $nudge->title($task, $oldHelper));
    }

    public function test_task_overdue_nudge_escalates_to_the_accountable_only_after_the_escalation_window(): void
    {
        $owner = $this->activeUser();
        $doer = $this->activeUser();
        $accountable = $this->activeUser();
        $nudge = new TaskNudge('overdue');
        $project = fn(array $settings) => Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$doer->id, $accountable->id], 'departments' => [], 'settings' => $settings]);
        $overdue = function (Project $p, int $days) use ($doer, $owner, $accountable) {
            $task = $this->nudgeTask($doer, $owner, ['deadline' => now()->subDays($days)], $p);
            TaskDetail::factory()->create(['task_id' => $task->id, 'responsible_user_id' => $accountable->id, 'collaborators' => []]);

            return Task::find($task->id);
        };

        $recent = $overdue($project([]), 2);
        $this->assertSame([$doer->id], $nudge->for($recent)->pluck('id')->all());

        $stale = $overdue($project([]), 4);
        $this->assertEqualsCanonicalizing([$doer->id, $accountable->id], $nudge->for($stale)->pluck('id')->all());
        $this->assertStringContainsString('تأخیر طولانی', $nudge->title($stale, $accountable));

        $off = $overdue($project(['escalation_days' => 0]), 30);
        $this->assertSame([$doer->id], $nudge->for($off)->pluck('id')->all());
    }

    public function test_task_approval_nudge_falls_back_from_inactive_owner_to_accountable(): void
    {
        $owner = $this->activeUser();
        $accountable = $this->activeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$accountable->id], 'departments' => [], 'settings' => ['requires_approval' => true]]);
        $task = Task::factory()->forProject($project)->ownedBy($owner)->withStatus('done')->create(['approved_at' => null]);
        Task::whereKey($task->id)->toBase()->update(['approved_at' => null]);
        TaskDetail::factory()->create(['task_id' => $task->id, 'responsible_user_id' => $accountable->id, 'collaborators' => []]);
        $nudge = new TaskNudge('approval');

        $this->assertSame([$owner->id], $nudge->for(Task::find($task->id))->pluck('id')->all());

        $owner->forceFill(['status' => 'inactive'])->saveQuietly();
        $this->assertSame([$accountable->id], $nudge->for(Task::find($task->id))->pluck('id')->all());

        $accountable->forceFill(['status' => 'inactive'])->saveQuietly();
        $this->assertCount(0, $nudge->for(Task::find($task->id)));
    }

    public function test_project_invite_nudge_names_the_owner_includes_department_members_and_keeps_earlier_invitees(): void
    {
        $dept = Department::factory()->create();
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active', 'name' => 'Olivia Owner']);
        $direct = $this->activeUser();
        $deptUser = $this->activeUser();
        $earlier = $this->activeUser();
        $quietMember = $this->activeUser();
        Profile::factory()->for($deptUser)->create(['department_id' => $dept->code]);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [$direct->id, $earlier->id, $quietMember->id],
            'departments' => [$dept->code],
        ]);
        Project::whereKey($project->id)->toBase()->update(['created_at' => now()->subHour()]);
        Reply::where('repliable_type', Project::class)->where('repliable_id', $project->id)->delete();
        Reply::factory()->create([
            'repliable_type' => Project::class,
            'repliable_id' => $project->id,
            'user_id' => $owner->id,
            'type' => TaskActivityType::Assignment,
            'payload' => ['added' => [$direct->id], 'added_departments' => [$dept->code]],
        ]);
        $this->insertNotification('projects-controller:nudge', $earlier->id, null, (string) $project->id);
        $nudge = new ProjectNudge();

        $ids = $nudge->for($project->fresh())->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$direct->id, $deptUser->id, $earlier->id], $ids);
        $this->assertNotContains($quietMember->id, $ids);
        $this->assertStringContainsString('Olivia Owner', $nudge->title($project, $direct));
    }

    public function test_project_step_nudge_bells_for_turn_rejection_force_removal_and_cancellation(): void
    {
        $owner = $this->activeUser();
        $a = $this->activeUser();
        $b = $this->activeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$a->id, $b->id], 'departments' => []]);
        $step = fn(string $label, array $people) => ['label' => $label, 'assignee_user_ids' => $people, 'type' => 'action', 'reject_to_step' => null];
        $workflow = Workflow::factory()->active()->create([
            'project_id' => $project->id,
            'owner_id' => $owner->id,
            'name' => 'Cycle',
            'steps' => [$step('first', [$a->id]), $step('second', [$b->id])],
            'current_step' => 1,
        ]);
        $nudge = new ProjectNudge('step');
        $emit = fn(array $payload, ?User $actor = null) => Reply::factory()->create([
            'repliable_type' => Project::class,
            'repliable_id' => $project->id,
            'user_id' => ($actor ?? $owner)->id,
            'type' => TaskActivityType::WorkflowStep,
            'payload' => ['workflow' => $workflow->id, ...$payload],
        ]);

        $this->assertSame('workflow:nudge', $nudge->getKey());

        $emit(['event' => 'advanced', 'step' => 0]);
        $wf = Workflow::find($workflow->id);
        $this->assertSame([$b->id], $nudge->for($wf)->pluck('id')->all());
        $this->assertStringContainsString('نوبت شماست', $nudge->title($wf, $b));

        $emit(['event' => 'advanced', 'step' => 0, 'forced' => true]);
        $nudge->for($wf);
        $this->assertStringContainsString('رد شد', $nudge->title($wf, $a));
        $this->assertStringContainsString('نوبت شماست', $nudge->title($wf, $b));

        $emit(['event' => 'rejected', 'note' => 'fix it']);
        $nudge->for($wf);
        $this->assertStringContainsString('برگشت خورد', $nudge->title($wf, $b));
        $this->assertSame('fix it', $nudge->body($wf, $b));

        $emit(['event' => 'reassigned', 'step' => 1, 'added' => [], 'removed' => [$a->id]]);
        $nudge->for($wf);
        $this->assertStringContainsString('حذف شدید', $nudge->title($wf, $a));

        $emit(['event' => 'cancelled', 'reason' => 'manual']);
        $this->assertSame([$b->id], $nudge->for($wf)->pluck('id')->all());
        $this->assertStringContainsString('لغو شد', $nudge->title($wf, $b));

        $emit(['event' => 'cancelled', 'reason' => 'project_deleted']);
        $this->assertCount(0, $nudge->for($wf));
    }

    public function test_project_step_nudge_never_notifies_the_actor(): void
    {
        $owner = $this->activeUser();
        $a = $this->activeUser();
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [$a->id], 'departments' => []]);
        $workflow = Workflow::factory()->active()->create([
            'project_id' => $project->id,
            'owner_id' => $owner->id,
            'steps' => [['label' => 'only', 'assignee_user_ids' => [$a->id], 'type' => 'action', 'reject_to_step' => null]],
        ]);
        Reply::factory()->create([
            'repliable_type' => Project::class,
            'repliable_id' => $project->id,
            'user_id' => $a->id,
            'type' => TaskActivityType::WorkflowStep,
            'payload' => ['workflow' => $workflow->id, 'event' => 'started'],
        ]);

        $this->assertCount(0, (new ProjectNudge('step'))->for($workflow));
    }

    public function test_pulse_fingerprint_moves_only_the_parts_that_changed_and_names_their_events(): void
    {
        $user = $this->activeUser();
        $before = PulseService::fingerprint($user);

        $this->assertSame([], PulseService::changedEvents($before, PulseService::fingerprint($user)));

        Reminder::create(['user_id' => $user->id, 'title' => 'pulse', 'due_at' => now(), 'recurs' => 'none', 'channels' => ['inapp:edge,badge,nudge']]);
        Edge::create(['user_id' => $user->id, 'edge_key' => 'reminders-controller:due-today-edge', 'subject_type' => Reminder::class, 'subject_id' => '1', 'title' => 'pulse']);

        $this->assertSame(['edges-changed', 'reminder-changed', 'home-stats-changed'], PulseService::changedEvents($before, PulseService::fingerprint($user)));
    }

    public function test_pulse_fingerprint_sees_time_based_changes_without_any_write(): void
    {
        $user = $this->activeUser();
        $midnight = now()->addDay()->startOfDay();
        Reminder::create(['user_id' => $user->id, 'title' => 'pulse', 'due_at' => $midnight->copy()->addHour(), 'recurs' => 'none', 'channels' => ['inapp:badge']]);
        Event::factory()->create(['user_id' => $user->id, 'private' => false, 'remind_hours' => 24, 'date' => $midnight->copy()->addDay()]);
        $before = PulseService::fingerprint($user);

        Carbon::setTestNow($midnight->copy()->addHour()->addMinutes(5));

        try {
            $after = PulseService::fingerprint($user);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(['reminder-changed', 'event-reminder-changed'], PulseService::changedEvents($before, $after));
    }

    public function test_reminder_badge_nudge_and_toast_all_wait_for_the_due_time_of_day(): void
    {
        $user = $this->activeUser();
        $this->actingAs($user);
        Carbon::setTestNow('2026-03-10 10:00:00');

        try {
            $reminder = $this->makeReminder($user, ['due_at' => '2026-03-10 15:00:00']);

            $this->assertFalse((new ReminderBadge())->isActive());
            $this->assertFalse((new ReminderOverdueNudge())->show($reminder, $user));
            $this->assertFalse((new ReminderDueTodayEdge())->show($reminder, $user));

            Carbon::setTestNow('2026-03-10 15:00:01');

            $this->assertTrue((new ReminderBadge())->isActive());
            $this->assertTrue((new ReminderOverdueNudge())->show($reminder, $user));
            $this->assertTrue((new ReminderDueTodayEdge())->show($reminder, $user));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dismissing_the_reminder_toast_completes_the_reminder_for_its_owner_only(): void
    {
        $owner = $this->activeUser();
        $other = $this->activeUser();
        EdgeService::register(new ReminderDueTodayEdge());
        $reminder = $this->makeReminder($owner, ['due_at' => now()->subMinute()]);
        Edge::create(['user_id' => $owner->id, 'edge_key' => 'reminders-controller:due-today-edge', 'subject_type' => Reminder::class, 'subject_id' => (string) $reminder->id, 'title' => 'toast']);
        Edge::create(['user_id' => $other->id, 'edge_key' => 'reminders-controller:due-today-edge', 'subject_type' => Reminder::class, 'subject_id' => (string) $reminder->id, 'title' => 'toast']);

        EdgeService::dismiss($other->id, 'reminders-controller:due-today-edge', $reminder->id);
        $this->assertNull($reminder->fresh()->completed_at);

        EdgeService::dismiss($owner->id, 'reminders-controller:due-today-edge', $reminder->id);
        $this->assertNotNull($reminder->fresh()->completed_at);
    }

    public function test_pulse_fingerprint_follows_the_bell_unread_set(): void
    {
        $user = $this->activeUser();
        $before = PulseService::fingerprint($user);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(), 'type' => 'Filament\Notifications\DatabaseNotification', 'notifiable_type' => User::class,
            'notifiable_id' => $user->id, 'data' => json_encode(['format' => 'filament', 'menu_key' => 'pulse:nudge']),
            'read_at' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(['databaseNotificationsSent'], PulseService::changedEvents($before, PulseService::fingerprint($user)));
    }
}
