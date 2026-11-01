<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\UnreadNotifications;
use App\Models\DMS;
use App\Models\Read;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class UnreadNotificationsTest extends TestCase
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

    private function makeUser(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function makeComponent(User $user): UnreadNotifications
    {
        $this->actingAs($user);

        return new UnreadNotifications();
    }

    private function seedNotifications(User $user, string $key, int $count, array $titles = []): array
    {
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $id = (string) Str::uuid();
            DB::table('notifications')->insert([
                'id' => $id,
                'type' => FilamentDatabaseNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'format' => 'filament',
                    'menu_key' => $key,
                    'title' => $titles[$i] ?? "{$key} row {$i}",
                ]),
                'read_at' => null,
                'created_at' => now()->subSeconds(100 - $i),
                'updated_at' => now()->subSeconds(100 - $i),
            ]);
            $ids[] = $id;
        }

        return $ids;
    }

    private function seedDmsDoc(User $user, string $mode): DMS
    {
        $doc = DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => [],
            'users' => [(string) $user->id],
            'extra' => [],
            'tags' => [],
        ]);

        if ($mode === 'read') {
            Read::create(['document_id' => $doc->id, 'user_id' => $user->id, 'read' => true, 'read_count' => 0]);
        }

        return $doc;
    }

    public function test_get_notifications_query_applies_unread_scope_on_user_panel()
    {
        $user = $this->makeUser();
        $unreadId = (string) Str::uuid();
        $readId = (string) Str::uuid();

        foreach ([$unreadId => null, $readId => now()] as $id => $readAt) {
            DB::table('notifications')->insert([
                'id' => $id,
                'type' => FilamentDatabaseNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(['format' => 'filament', 'menu_key' => 'test:scope']),
                'read_at' => $readAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $ids = $this->makeComponent($user)->getNotificationsQuery()->pluck('id');

        $this->assertContains($unreadId, $ids->all());
        $this->assertNotContains($readId, $ids->all());
    }

    public function test_get_notifications_query_includes_read_rows_on_admin_panel()
    {
        $user = $this->makeUser();
        $this->seedNotifications($user, 'test:admin', 2);
        DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('data->menu_key', 'test:admin')
            ->limit(1)
            ->update(['read_at' => now()]);

        Filament::setCurrentPanel('admin');

        $this->assertSame(2, $this->makeComponent($user)->getNotificationsQuery()->count());
    }

    public function test_is_paginated_is_false_on_user_panel_and_true_on_admin_panel()
    {
        $component = $this->makeComponent($this->makeUser());
        $this->assertFalse($component->isPaginated());

        Filament::setCurrentPanel('admin');

        $this->assertTrue($component->isPaginated());
    }

    public function test_user_panel_bell_has_no_poll_of_its_own_because_the_pulse_drives_it()
    {
        $component = $this->makeComponent($this->makeUser());

        $this->assertNull($component->getPollingInterval());

        Filament::setCurrentPanel('admin');

        $this->assertNotNull($component->getPollingInterval());
    }

    public function test_over_threshold_group_folds_into_a_single_aggregate_row()
    {
        $user = $this->makeUser();
        $titles = ['Fold One', 'Fold Two', 'Fold Three', 'Fold Four', 'Latest Notice'];
        $foldIds = $this->seedNotifications($user, 'test:fold', 5, $titles);
        $soloId = $this->seedNotifications($user, 'test:other', 1)[0];

        $rows = $this->makeComponent($user)->getNotifications();

        $this->assertSame(2, $rows->count());
        $ids = $rows->pluck('id')->all();
        $this->assertContains('agg:test:fold', $ids);
        $this->assertContains($soloId, $ids);

        $aggregate = $rows->firstWhere('id', 'agg:test:fold');
        $this->assertSame(FilamentDatabaseNotification::class, $aggregate->type);
        $this->assertEquals('5 اعلان جدید', $aggregate->data['title']);
        $this->assertEquals('آخرین: Latest Notice', $aggregate->data['body']);

        foreach ($foldIds as $id) {
            $this->assertNotContains($id, $ids);
        }
    }

    public function test_four_row_group_does_not_fold_at_threshold_boundary()
    {
        $user = $this->makeUser();
        $ids = $this->seedNotifications($user, 'test:boundary', 4);

        $rows = $this->makeComponent($user)->getNotifications();

        $this->assertSame(4, $rows->count());
        $this->assertEqualsCanonicalizing($ids, $rows->pluck('id')->all());
        $this->assertSame(0, $rows->filter(fn ($row) => str_starts_with((string) $row->id, 'agg:'))->count());
    }

    public function test_dms_group_folds_into_sign_and_read_aggregate_rows()
    {
        $user = $this->makeUser();
        $baseSign = DMS::needsSignCount($user->id);
        $baseRead = DMS::needsReadCount($user->id);
        $this->seedDmsDoc($user, 'sign');
        $this->seedDmsDoc($user, 'read');
        $this->seedNotifications($user, 'dms-controller:nudge', 5);

        $rows = $this->makeComponent($user)->getNotifications();

        $this->assertSame(2, $rows->count());
        $sign = $rows->firstWhere('id', 'agg:dms-controller:nudge:sign');
        $read = $rows->firstWhere('id', 'agg:dms-controller:nudge:read');

        $this->assertNotNull($sign);
        $this->assertNotNull($read);
        $this->assertEquals(($baseSign + 1) . ' سند نیازمند تأیید شماست', $sign->data['title']);
        $this->assertEquals(($baseRead + 1) . ' سند نیازمند مطالعه شماست', $read->data['title']);
    }

    public function test_dms_group_with_zero_pending_counts_keeps_raw_rows_unfolded()
    {
        $user = $this->makeUser();

        DMS::visibleTo($user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id)->where('read', true))
            ->pluck('id')
            ->each(fn ($docId) => Read::create([
                'document_id' => $docId,
                'user_id' => $user->id,
                'read' => true,
                'read_count' => 1,
            ]));
        Read::where('user_id', $user->id)->where('read', true)->where('read_count', 0)->update(['read_count' => 1]);

        $this->assertSame(0, DMS::needsSignCount($user->id));
        $this->assertSame(0, DMS::needsReadCount($user->id));

        $ids = $this->seedNotifications($user, 'dms-controller:nudge', 5);

        $component = $this->makeComponent($user);
        $rows = $component->getNotifications();

        $this->assertSame(5, $rows->count());
        $this->assertEqualsCanonicalizing($ids, $rows->pluck('id')->all());
        $this->assertSame(5, $component->getUnreadNotificationsCount());
    }

    public function test_removing_an_aggregate_id_marks_every_row_of_the_menu_key_read()
    {
        $user = $this->makeUser();
        $this->seedNotifications($user, 'test:dismiss', 5);

        $this->makeComponent($user)->removeNotification('agg:test:dismiss');

        $this->assertSame(5, DB::table('notifications')->where('notifiable_id', $user->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_removing_a_dms_aggregate_id_strips_the_mode_suffix_before_matching()
    {
        $user = $this->makeUser();
        $this->seedNotifications($user, 'dms-controller:nudge', 5);

        $this->makeComponent($user)->removeNotification('agg:dms-controller:nudge:sign');

        $this->assertSame(5, DB::table('notifications')->where('notifiable_id', $user->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_removing_a_plain_notification_id_deletes_it_for_that_user()
    {
        $user = $this->makeUser();
        $id = $this->seedNotifications($user, 'test:remove', 1)[0];

        $this->makeComponent($user)->removeNotification($id);

        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $user->id)->where('id', $id)->count());
    }

    public function test_unread_count_folds_a_generic_over_threshold_group_to_one()
    {
        $user = $this->makeUser();
        $this->seedNotifications($user, 'test:fold', 5);
        $this->seedNotifications($user, 'test:other', 1);

        $this->assertSame(2, $this->makeComponent($user)->getUnreadNotificationsCount());
    }

    public function test_unread_count_folds_dms_group_to_the_sign_and_read_pair()
    {
        $user = $this->makeUser();
        $this->seedDmsDoc($user, 'sign');
        $this->seedDmsDoc($user, 'read');
        $this->seedNotifications($user, 'dms-controller:nudge', 5);
        $this->seedNotifications($user, 'test:other', 1);

        $this->assertSame(3, $this->makeComponent($user)->getUnreadNotificationsCount());
    }

    public function test_clear_action_is_hidden_and_mark_all_read_action_carries_the_persian_label()
    {
        $component = $this->makeComponent($this->makeUser());

        $this->assertTrue($component->clearNotificationsAction()->isHidden());
        $this->assertSame('حذف همه اعلانات', $component->markAllNotificationsAsReadAction()->getLabel());
    }

    public function test_unread_count_is_zero_when_live_total_is_zero_even_with_a_stale_cached_group_entry()
    {
        $user = $this->makeUser();
        $this->seedNotifications($user, 'test:stale', 5);
        $this->assertSame(1, $this->makeComponent($user)->getUnreadNotificationsCount());

        DB::table('notifications')->where('notifiable_id', $user->id)->update(['read_at' => now()]);

        $this->assertSame(0, $this->makeComponent($user)->getUnreadNotificationsCount());
    }

    public function test_off_module_rows_and_their_count_are_hidden_without_deleting_and_return_when_on()
    {
        $user = $this->makeUser();
        $offIds = $this->seedNotifications($user, 'contacts-controller:nudge', 5);
        $this->seedNotifications($user, 'test:other', 1);
        $this->seedNotifications($user, 'channels-controller:nudge', 2);

        try {
            config(['app.off' => ['contact']]);
            $component = $this->makeComponent($user);
            $keys = $component->getNotifications()->map(fn ($n) => $n->data['menu_key'] ?? $n->id)->all();

            $this->assertNotContains('contacts-controller:nudge', $keys);
            $this->assertNotContains('agg:contacts-controller:nudge', $keys);
            $this->assertSame(3, $component->getUnreadNotificationsCount());
            $this->assertSame(5, DB::table('notifications')->whereIn('id', $offIds)->whereNull('read_at')->count());

            config(['app.off' => []]);
            $on = $this->makeComponent($user);

            $this->assertSame(4, $on->getUnreadNotificationsCount());
            $this->assertContains('agg:contacts-controller:nudge', $on->getNotifications()->pluck('id')->all());
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_render_dispatches_bell_unread_lit_true_when_rows_remain()
    {
        $user = $this->makeUser();
        $this->seedNotifications($user, 'test:lit', 2);
        $this->actingAs($user);

        Livewire::test(UnreadNotifications::class)->assertDispatched('bell-unread', lit: true);
    }

    public function test_render_dispatches_bell_unread_lit_false_when_none_remain()
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        Livewire::test(UnreadNotifications::class)->assertDispatched('bell-unread', lit: false);
    }
}
