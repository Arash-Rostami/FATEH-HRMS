<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Home;
use App\Livewire\Dashboard\Tab\Presentation\TabPresenter;
use App\Models\ChannelMessage;
use App\Models\Comment;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Reaction;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
        Livewire::withoutLazyLoading();
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

    private function statValue(array $stats, string $key): int
    {
        $entry = collect($stats)->firstWhere('key', $key);

        return $entry['value'] ?? -1;
    }

    private function eventsStatFor(User $user): int
    {
        Livewire::withoutLazyLoading();
        return $this->statValue(Livewire::actingAs($user)->test(Home::class)->viewData('stats'), 'events');
    }

    public function test_home_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Home::class)
            ->assertHasNoErrors();
    }

    public function test_modules_computed_returns_config_modules_array()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $home = Livewire::actingAs($user)->test(Home::class)->instance();

        $this->assertSame(Config::get('modules', []), $home->modules);
    }

    public function test_render_passes_shortcuts_with_tab_route_and_url_types()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $shortcuts = Livewire::actingAs($user)->test(Home::class)->viewData('shortcuts');
        $types = array_column($shortcuts, 'type');

        $this->assertContains('tab', $types);
        $this->assertContains('route', $types);
        $this->assertContains('url', $types);
    }

    public function test_shortcuts_include_links_entry()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $shortcuts = Livewire::actingAs($user)->test(Home::class)->viewData('shortcuts');

        $this->assertNotNull(collect($shortcuts)->firstWhere('key', 'links'));
    }

    public function test_gadget_catalog_excludes_home_and_appends_embed_flag_to_every_src()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $home = Livewire::actingAs($user)->test(Home::class);
        $catalog = $home->viewData('gadgetCatalog');
        $homeTitle = collect($home->viewData('shortcuts'))->firstWhere('key', 'home')['title'] ?? null;

        $this->assertNotNull($homeTitle);
        $this->assertNotEmpty($catalog);
        $this->assertNull(collect($catalog)->firstWhere('title', $homeTitle));
        foreach ($catalog as $item) {
            $this->assertStringContainsString('embed=1', $item['src']);
        }
    }

    public function test_shortcuts_cover_every_module_nav_entry_with_matching_type_and_target()
    {
        $user = User::factory()->create(['status' => 'active']);

        $navModules = collect(Config::get('modules', []))->filter(fn ($m) => isset($m['nav']));

        Livewire::withoutLazyLoading();
        $home = Livewire::actingAs($user)->test(Home::class);
        $shortcuts = $home->viewData('shortcuts');
        $byKey = collect($shortcuts)->keyBy('key');

        $this->assertCount(count($shortcuts), $byKey, 'shortcut keys contain duplicates');
        $this->assertSame($navModules->count() + 1, count($shortcuts), 'every navigable module must appear as a shortcut plus the home entry');
        $this->assertSame('home', $shortcuts[0]['key']);

        foreach ($navModules as $module) {
            $nav = $module['nav'];
            $key = $nav['key'] ?? $module['id'];
            $shortcut = $byKey->get($key);

            $this->assertNotNull($shortcut, "module '{$module['id']}' has no shortcut under key '{$key}'");
            $this->assertSame($nav['type'], $shortcut['type'], "shortcut '{$key}' type mismatch");
            $this->assertSame($nav['target'], $shortcut['target'], "shortcut '{$key}' target mismatch");
            $this->assertSame(
                $nav['type'] === 'tab' ? null : route($nav['target']),
                $shortcut['url'],
                "shortcut '{$key}' url mismatch"
            );
        }

        $this->assertCount(count($shortcuts) - 1, $home->viewData('gadgetCatalog'), 'gadget catalog must mirror every shortcut except home');
    }

    public function test_stats_view_data_contains_documents_tickets_tasks_events_messages_reminders_keys()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($user)->test(Home::class)->viewData('stats');

        $this->assertSame(
            ['documents', 'tickets', 'tasks', 'events', 'messages', 'reminders'],
            array_column($stats, 'key')
        );
    }

    public function test_stats_use_tab_route_and_event_nav_types()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $stats = collect(Livewire::actingAs($user)->test(Home::class)->viewData('stats'))->keyBy('key');

        $this->assertSame(['type' => 'tab', 'tab' => 'calendar'], $stats['events']['nav']);
        $this->assertSame(['type' => 'route', 'name' => 'dms'], $stats['documents']['nav']);
        $this->assertSame(['type' => 'route', 'name' => 'ths'], $stats['tickets']['nav']);
        $this->assertSame(['type' => 'route', 'name' => 'tasks'], $stats['tasks']['nav']);
        $this->assertSame(['type' => 'route', 'name' => 'contact'], $stats['messages']['nav']);
        $this->assertSame(['type' => 'event', 'name' => 'open-reminders'], $stats['reminders']['nav']);
    }

    public function test_messages_stat_counts_unread_direct_messages()
    {
        $user = User::factory()->create(['status' => 'active']);
        $sender = User::factory()->create(['status' => 'active']);
        $baseline = $this->statValue(Livewire::actingAs($user)->test(Home::class)->viewData('stats'), 'messages');

        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $user->id, 'read_at' => null]);
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $user->id, 'read_at' => now()]);

        Cache::flush();

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($user)->test(Home::class)->viewData('stats');

        $this->assertSame($baseline + 1, $this->statValue($stats, 'messages'));
    }

    public function test_tasks_stat_sums_todo_in_progress_and_pending_and_excludes_done()
    {
        $user = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('todo')->create();
        Task::factory()->ownedBy($user)->withStatus('in-progress')->create();
        Task::factory()->ownedBy($user)->withStatus('pending')->create();
        Task::factory()->ownedBy($user)->withStatus('done')->create();

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($user)->test(Home::class)->viewData('stats');

        $this->assertSame(3, $this->statValue($stats, 'tasks'));
    }

    public function test_tickets_stat_counts_open_and_in_progress_tickets_for_the_authenticated_user()
    {
        $user = User::factory()->create(['status' => 'active']);

        Ticket::factory()->create(['requester_id' => $user->id, 'status' => 'open']);
        Ticket::factory()->create(['requester_id' => $user->id, 'status' => 'in-progress']);
        Ticket::factory()->create(['requester_id' => $user->id, 'status' => 'closed', 'effectiveness' => 'resolved']);

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($user)->test(Home::class)->viewData('stats');

        $this->assertSame(2, $this->statValue($stats, 'tickets'));
    }

    public function test_events_stat_includes_user_owned_upcoming_event()
    {
        $user = User::factory()->create(['status' => 'active']);

        $baseline = $this->eventsStatFor($user);

        Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addDays(2),
            'private' => true,
        ]);

        Cache::flush();

        $this->assertSame($baseline + 1, $this->eventsStatFor($user));
    }

    public function test_events_stat_excludes_events_beyond_one_week_window()
    {
        $user = User::factory()->create(['status' => 'active']);

        $baseline = $this->eventsStatFor($user);

        Event::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addDays(10),
            'private' => true,
        ]);

        Cache::flush();

        $this->assertSame($baseline, $this->eventsStatFor($user));
    }

    public function test_events_stat_includes_public_event_owned_by_another_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $baseline = $this->eventsStatFor($user);

        Event::factory()->create([
            'user_id' => $other->id,
            'date' => now()->addDays(2),
            'private' => false,
        ]);

        Cache::flush();

        $this->assertSame($baseline + 1, $this->eventsStatFor($user));
    }

    public function test_events_stat_includes_private_event_shared_with_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $baseline = $this->eventsStatFor($user);

        $event = Event::factory()->create([
            'user_id' => $other->id,
            'date' => now()->addDays(2),
            'private' => true,
        ]);
        EventShare::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'shared_by' => $other->id,
        ]);

        Cache::flush();

        $this->assertSame($baseline + 1, $this->eventsStatFor($user));
    }

    public function test_events_stat_excludes_other_users_private_event_not_shared_with_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $baseline = $this->eventsStatFor($user);

        Event::factory()->create([
            'user_id' => $other->id,
            'date' => now()->addDays(2),
            'private' => true,
        ]);

        Cache::flush();

        $this->assertSame($baseline, $this->eventsStatFor($user));
    }

    public function test_stats_are_scoped_to_the_authenticated_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        $eventsBaseline = $this->eventsStatFor($user);

        Task::factory()->ownedBy($other)->withStatus('todo')->create();
        Ticket::factory()->create(['requester_id' => $other->id, 'status' => 'open']);
        Event::factory()->create([
            'user_id' => $other->id,
            'date' => now()->addDays(2),
            'private' => true,
        ]);

        Cache::flush();

        Livewire::withoutLazyLoading();
        $stats = Livewire::actingAs($user)->test(Home::class)->viewData('stats');

        $this->assertSame(0, $this->statValue($stats, 'tasks'));
        $this->assertSame(0, $this->statValue($stats, 'tickets'));
        $this->assertSame($eventsBaseline, $this->statValue($stats, 'events'));
    }

    public function test_stats_cache_serves_the_stale_value_until_a_stamp_source_write_invalidates_it()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $first = Livewire::actingAs($user)->test(Home::class);
        $this->assertSame(0, $this->statValue($first->viewData('stats'), 'tasks'));

        Task::factory()->ownedBy($user)->withStatus('todo')->create();
        Task::resetStatusCountsCache();

        Livewire::withoutLazyLoading();
        $second = Livewire::actingAs($user)->test(Home::class);
        $this->assertSame(0, $this->statValue($second->viewData('stats'), 'tasks'));

        Reminder::create(['user_id' => $user->id, 'title' => 'stamp flip', 'due_at' => now(), 'recurs' => 'none', 'channels' => ['inapp:badge']]);

        Livewire::withoutLazyLoading();
        $third = Livewire::actingAs($user)->test(Home::class);
        $this->assertSame(1, $this->statValue($third->viewData('stats'), 'tasks'));
    }

    public function test_stat_stamp_is_idle_stable_and_flips_only_on_channel_message_reminder_and_event_writes()
    {
        $user = User::factory()->create(['status' => 'active']);

        $idle = TabPresenter::statStamp($user->id);
        $this->assertSame($idle, TabPresenter::statStamp($user->id));

        ChannelMessage::factory()->fromUser($user)->create();
        $this->assertNotSame($idle, TabPresenter::statStamp($user->id));

        $afterMessage = TabPresenter::statStamp($user->id);
        Reminder::create(['user_id' => $user->id, 'title' => 'stamp', 'due_at' => now(), 'recurs' => 'none', 'channels' => ['inapp:badge']]);
        $this->assertNotSame($afterMessage, TabPresenter::statStamp($user->id));

        $afterReminder = TabPresenter::statStamp($user->id);
        Event::factory()->create(['user_id' => $user->id, 'private' => false, 'date' => now()->addDays(2)]);
        $this->assertNotSame($afterReminder, TabPresenter::statStamp($user->id));

        $afterEvent = TabPresenter::statStamp($user->id);
        Comment::factory()->create();
        Reaction::factory()->create();
        User::factory()->create(['status' => 'active']);
        $this->assertSame($afterEvent, TabPresenter::statStamp($user->id));
    }

    public function test_stat_stamp_flips_on_menu_state_flush_and_on_the_channel_read_bump()
    {
        $user = User::factory()->create(['status' => 'active']);
        $before = TabPresenter::statStamp($user->id);

        StateService::flush();
        $afterFlush = TabPresenter::statStamp($user->id);
        $this->assertNotSame($before, $afterFlush);

        ModelCacheVersion::bump("channel-read:{$user->id}");
        $this->assertNotSame($afterFlush, TabPresenter::statStamp($user->id));
    }

    public function test_greeting_falls_back_to_the_full_name_with_no_display_name_override()
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'سید علی رستمی']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Home::class)
            ->assertHasNoErrors()
            ->assertSeeHtml('سید علی رستمی');
    }

    public function test_greeting_uses_the_display_name_override_when_set()
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'سید علی رستمی']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $profile->syncDetails(['display_name' => 'علی']);

        Livewire::withoutLazyLoading();
        $test = Livewire::actingAs($user->refresh())->test(Home::class)->assertHasNoErrors();
        $test->assertSeeHtml('علی');
        $test->assertDontSeeHtml('سید علی رستمی');
    }

    public function test_team_pulse_shows_online_colleagues_ranked_by_position_and_excludes_self_and_offline()
    {
        $me = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $manager = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => 'مدیر تست']);
        Profile::factory()->create(['user_id' => $manager->id, 'position' => 'manager']);

        $employee = User::factory()->create(['status' => 'active', 'presence' => 'remote', 'name' => 'کارمند تست']);
        Profile::factory()->create(['user_id' => $employee->id, 'position' => 'employee']);

        $offline = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => now()->subHours(1)]);

        Cache::flush();
        Livewire::withoutLazyLoading();
        $pulse = Livewire::actingAs($me)->test(Home::class)->viewData('teamPulse');
        $ids = $pulse->pluck('id')->all();

        $this->assertNotContains($me->id, $ids);
        $this->assertNotContains($offline->id, $ids);
        $this->assertContains($employee->id, $ids);

        $ranked = app(TabPresenter::class)->rankedOnlineUsers();

        $managerIndex = $ranked->search(fn(User $u) => $u->id === $manager->id);
        $employeeIndex = $ranked->search(fn(User $u) => $u->id === $employee->id);

        $this->assertLessThan($employeeIndex, $managerIndex);
    }

    public function test_team_pulse_still_renders_each_online_colleague_avatar_with_status_badge_and_no_redundant_dot()
    {
        $me = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $busy = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'name' => 'همکار مشغول', 'last_seen' => now()]);

        Cache::flush();
        Livewire::withoutLazyLoading();
        $html = Livewire::actingAs($me)->test(Home::class)->html();

        $this->assertStringContainsString('home-team-pulse-' . $busy->id, $html);
        $this->assertStringContainsString('title="مشغول — عدم مزاحمت"', $html);
    }

    public function test_team_pulse_excludes_online_colleagues_on_leave_or_mission_but_keeps_moods()
    {
        $me = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $onLeave = User::factory()->create(['status' => 'active', 'presence' => 'leave', 'last_seen' => now()]);
        $onMission = User::factory()->create(['status' => 'active', 'presence' => 'mission', 'last_seen' => now()]);
        $angry = User::factory()->create(['status' => 'active', 'presence' => 'angry', 'last_seen' => now()]);

        Cache::flush();
        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($me)->test(Home::class)->viewData('teamPulse')->pluck('id')->all();

        $this->assertNotContains($onLeave->id, $ids);
        $this->assertNotContains($onMission->id, $ids);
        $this->assertContains($angry->id, $ids);
    }

    public function test_team_pulse_returns_more_than_eighteen_online_colleagues_uncapped()
    {
        $me = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Cache::flush();
        Livewire::withoutLazyLoading();
        $baseline = Livewire::actingAs($me)->test(Home::class)->viewData('teamPulse')->count();

        User::factory()->count(20)->create(['status' => 'active', 'presence' => 'onsite']);

        Cache::flush();
        Livewire::withoutLazyLoading();
        $pulse = Livewire::actingAs($me)->test(Home::class)->viewData('teamPulse');

        $this->assertSame($baseline + 20, $pulse->count());
        $this->assertGreaterThan(18, $pulse->count());
    }

    public function test_remaining_team_skips_the_first_n_ranked_online_colleagues()
    {
        $me = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        User::factory()->count(6)->create(['status' => 'active', 'presence' => 'onsite']);

        Cache::flush();
        Livewire::withoutLazyLoading();
        $home = Livewire::actingAs($me)->test(Home::class);
        $home->call('openTeamSearch', 3);

        $fullPulse = app(TabPresenter::class)->teamPulse();
        $expected = $fullPulse->skip(3)->values()->pluck('id')->all();
        $remaining = $home->instance()->remainingTeam();

        $this->assertSame($expected, $remaining->pluck('id')->all());
        $this->assertCount($fullPulse->count() - 3, $remaining);
    }

    public function test_remaining_team_filters_by_casual_name_case_insensitively()
    {
        $me = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $alice = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => 'Alice Wonderland']);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'name' => 'Bob Marley']);

        Cache::flush();
        Livewire::withoutLazyLoading();
        $home = Livewire::actingAs($me)->test(Home::class);
        $home->set('teamSkip', 0);
        $home->set('teamSearch', 'ALICE');

        $remaining = $home->instance()->remainingTeam();

        $this->assertSame([$alice->id], $remaining->pluck('id')->all());
    }

    public function test_load_more_team_increments_team_show_count_by_ten_each_call()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Home::class)
            ->assertSet('teamShowCount', 10)
            ->call('loadMoreTeam')
            ->assertSet('teamShowCount', 20)
            ->call('loadMoreTeam')
            ->assertSet('teamShowCount', 30);
    }

    public function test_updated_team_search_resets_team_show_count_to_ten()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Home::class)
            ->call('loadMoreTeam')
            ->assertSet('teamShowCount', 20)
            ->set('teamSearch', 'a')
            ->assertSet('teamShowCount', 10);
    }

    public function test_open_team_search_resets_search_and_show_count_sets_skip_and_dispatches_event()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Home::class)
            ->set('teamSearch', 'x')
            ->call('loadMoreTeam')
            ->call('openTeamSearch', 15)
            ->assertSet('teamSearch', '')
            ->assertSet('teamShowCount', 10)
            ->assertSet('teamSkip', 15)
            ->assertDispatched('open-team-search');
    }

    public function test_stats_cache_key_is_namespaced_per_user()
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);

        Task::factory()->ownedBy($user)->withStatus('todo')->create();

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Home::class);

        Livewire::withoutLazyLoading();
        $otherStats = Livewire::actingAs($other)->test(Home::class)->viewData('stats');

        $this->assertSame(0, $this->statValue($otherStats, 'tasks'));
    }

    public function test_ui_title_count_renders_persian_digits(): void
    {
        $this->blade('<x-ui.title icon="dashboard" title="probe" :count="17" countLabel="ماژول"/>')
            ->assertSee(convertToPersian('17'))
            ->assertDontSee('17');
    }
}
