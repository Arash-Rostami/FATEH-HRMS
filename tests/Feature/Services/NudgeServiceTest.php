<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\Menu\Contracts\MenuNudge;
use App\Services\Menu\NudgeService;
use App\Services\Cache\ModelCacheVersion;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    private function notificationsFor(string $key): \Illuminate\Support\Collection
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
            'id' => (string) \Illuminate\Support\Str::uuid(),
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

        $staleKey = ModelCacheVersion::key(\Illuminate\Notifications\DatabaseNotification::class, 'unread_menu_groups:' . $recipient->id);
        Cache::put($staleKey, 'stale-value', now()->addMinutes(15));

        NudgeService::register($this->nudge($key, fn() => [$recipient], fn() => true));
        NudgeService::reconcile($key, User::class, $subject->id);

        $freshKey = ModelCacheVersion::key(\Illuminate\Notifications\DatabaseNotification::class, 'unread_menu_groups:' . $recipient->id);

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
}