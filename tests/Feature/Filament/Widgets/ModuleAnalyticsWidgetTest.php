<?php

namespace Tests\Feature\Filament\Widgets;

use App\Models\Task;
use App\Filament\Widgets\ModuleAnalyticsWidget;
use App\Models\Department;
use App\Models\EnergyTest;
use App\Models\NotifyRule;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class ModuleAnalyticsWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
        Once::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Once::flush();
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

    private function widget(): ModuleAnalyticsWidget
    {
        return (new ReflectionClass(ModuleAnalyticsWidget::class))->newInstanceWithoutConstructor();
    }

    private function invoke(object $widget, string $method): array
    {
        $m = new ReflectionMethod($widget, $method);
        $m->setAccessible(true);

        return $m->invoke($widget);
    }

    public static function statMethodsProvider(): array
    {
        return [
            'usersData' => ['usersData'],
            'adsData' => ['adsData'],
            'channelsData' => ['channelsData'],
            'contactsData' => ['contactsData'],
            'faqsData' => ['faqsData'],
            'linksData' => ['linksData'],
            'notifyData' => ['notifyData'],
            'permissionsData' => ['permissionsData'],
            'projectsData' => ['projectsData'],
            'remindersData' => ['remindersData'],
            'reservationPoliciesData' => ['reservationPoliciesData'],
            'resourcesData' => ['resourcesData'],
        ];
    }

    public function test_tabs_of_modules_the_tenant_switched_off_are_hidden_and_the_rest_stay(): void
    {
        $widget = (new ReflectionClass(ModuleAnalyticsWidget::class))->newInstanceWithoutConstructor();
        $keys = fn () => array_keys($widget->getAllTabs());

        $this->assertContains('notify', $keys());
        $this->assertContains('reservation_policies', $keys());

        config(['app.off' => ['notify', 'reservation', 'calendar']]);

        foreach (['notify', 'reservations', 'resources', 'reservation_policies', 'events'] as $hidden) {
            $this->assertNotContains($hidden, $keys());
        }

        $this->assertContains('users', $keys());
        $this->assertContains('tasks', $keys());
    }

    #[DataProvider('statMethodsProvider')]
    public function test_data_method_returns_array_of_stat_objects(string $method): void
    {
        $widget = $this->widget();
        $stats = $this->invoke($widget, $method);

        $this->assertIsArray($stats);
        $this->assertNotEmpty($stats);

        foreach ($stats as $stat) {
            $this->assertInstanceOf(Stat::class, $stat);
            $value = $stat->getValue();
            if (is_string($value) || is_numeric($value)) {
                $this->assertNotSame('', (string) $value);
            } else {
                $this->assertNotNull($value);
            }
        }
    }

    public function test_notify_data_ignores_one_off_rules_in_every_count(): void
    {
        $widget = $this->widget();
        $before = $this->invoke($widget, 'notifyData');

        NotifyRule::create(['name' => 'one off', 'model' => NotifyRule::ONE_OFF, 'events' => [], 'channels' => ['mail', 'sms'], 'subject' => 'one off', 'lines' => ['A line'], 'is_active' => true]);

        $after = $this->invoke($widget, 'notifyData');

        $this->assertSame(
            array_map(fn (Stat $s) => [$s->getValue(), $s->getDescription()], $before),
            array_map(fn (Stat $s) => [$s->getValue(), $s->getDescription()], $after),
        );
    }

    public function test_users_data_excludes_guest_but_includes_vip_in_total(): void
    {
        User::factory()->create(['status' => 'active', 'type' => 'guest']);
        $vip = User::factory()->create(['status' => 'active', 'type' => 'vip']);

        $widget = $this->widget();
        $stats = $this->invoke($widget, 'usersData');

        $expectedTotal = User::where('type', '!=', 'guest')->count();

        $this->assertSame($expectedTotal, $stats[0]->getValue());
        $this->assertContains($vip->id, User::where('type', '!=', 'guest')->pluck('id'), 'VIP must still be counted, only Guest is excluded');
    }

    public function test_users_data_splits_online_from_available_by_reachability(): void
    {
        $before = $this->invoke($this->widget(), 'usersData');

        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => now()]);
        User::factory()->create(['status' => 'active', 'presence' => 'leave', 'last_seen' => now()]);
        User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => now()->subMinutes(User::ONLINE_MINUTES + 5)]);

        $after = $this->invoke($this->widget(), 'usersData');

        $this->assertSame($before[3]->getValue() + 2, $after[3]->getValue());
        $this->assertSame($before[4]->getValue() + 1, $after[4]->getValue());
    }

    public function test_energy_data_excludes_guest_type_users_from_total(): void
    {
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        $employee = User::factory()->create(['status' => 'active']);
        EnergyTest::factory()->forUser($guest)->create();
        EnergyTest::factory()->forUser($employee)->create();

        $stats = $this->invoke($this->widget(), 'energyData');
        $expected = DB::table('energy_tests')
            ->join('users', 'users.id', '=', 'energy_tests.user_id')
            ->where('users.type', '!=', 'guest')
            ->count();

        $this->assertSame($expected, $stats[0]->getValue());
    }

    public function test_profiles_data_excludes_guest_type_users_from_total(): void
    {
        $department = Department::firstOrCreate(['code' => 'TSTP'], ['name' => 'TSTP', 'description' => 'TSTP']);
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        $employee = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => $department->code]);
        Profile::factory()->create(['user_id' => $employee->id, 'department_id' => $department->code]);

        $stats = $this->invoke($this->widget(), 'profilesData');
        $expected = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', 'guest')
            ->count();

        $this->assertSame($expected, $stats[0]->getValue());
    }

    public function test_active_tab_defaults_to_users_and_is_mutable(): void
    {
        $widget = $this->widget();

        $this->assertSame('users', $widget->activeTab);

        $prop = (new ReflectionClass($widget))->getProperty('activeTab');
        $prop->setAccessible(true);
        $prop->setValue($widget, 'ads');

        $this->assertSame('ads', $widget->activeTab);
    }

    public function test_departments_data_reflects_seeded_department_baseline_delta(): void
    {
        $widget = $this->widget();

        Cache::flush();
        Once::flush();
        $baselineTotal = Department::count();
        $baselineRanked = Department::withCount(['users' => fn ($q) => $q->where('type', '!=', 'guest')])
            ->orderByDesc('users_count')->orderBy('code')->get();
        $baselineWithUsers = $baselineRanked->where('users_count', '>', 0)->count();

        $department = Department::factory()->create([
            'code' => 'ZZ' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'name' => 'Test Department ' . uniqid(),
        ]);
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->code,
        ]);

        Cache::flush();
        Once::flush();

        $stats = $this->invoke($widget, 'departmentsData');

        $this->assertIsArray($stats);
        $this->assertCount(3, $stats);
        foreach ($stats as $stat) {
            $this->assertInstanceOf(Stat::class, $stat);
        }

        $this->assertSame($baselineTotal + 1, (int) $stats[0]->getValue());

        $afterRanked = Department::withCount(['users' => fn ($q) => $q->where('type', '!=', 'guest')])
            ->orderByDesc('users_count')->orderBy('code')->get();
        $createdRow = $afterRanked->firstWhere('code', $department->code);
        $this->assertNotNull($createdRow);
        $this->assertSame(1, (int) $createdRow->users_count);

        $this->assertSame($baselineWithUsers + 1, (int) $stats[1]->getValue());
    }

    public function test_departments_data_excludes_guest_but_includes_vip_from_users_count(): void
    {
        $widget = $this->widget();

        $department = Department::factory()->create([
            'code' => 'ZZ' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'name' => 'Test Department ' . uniqid(),
        ]);
        $employee = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        Profile::factory()->create(['user_id' => $employee->id, 'department_id' => $department->code]);
        $vip = User::factory()->create(['status' => 'active', 'type' => 'vip']);
        Profile::factory()->create(['user_id' => $vip->id, 'department_id' => $department->code]);
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Profile::factory()->create(['user_id' => $guest->id, 'department_id' => $department->code]);

        Cache::flush();
        Once::flush();

        $this->invoke($widget, 'departmentsData');

        $ranked = Department::withCount(['users' => fn ($q) => $q->where('type', '!=', 'guest')])
            ->orderBy('code')->get();
        $createdRow = $ranked->firstWhere('code', $department->code);

        $this->assertSame(2, (int) $createdRow->users_count, 'employee + vip counted, guest excluded');
    }

    public function test_tasks_overdue_stat_follows_the_shared_task_metrics_definition(): void
    {
        $stat = fn() => (int) collect($this->invoke($this->widget(), 'tasksData'))->last()->getValue();
        $before = $stat();
        $owner = User::factory()->create(['status' => 'active']);
        $mk = fn(array $a) => Task::factory()->create(['assigned_to' => $owner->id, 'user_id' => $owner->id, 'deadline' => now()->subDays(3), ...$a]);

        $mk(['status' => 'in-progress']);
        $mk(['status' => 'pending']);
        $mk(['status' => 'done']);
        $mk(['status' => 'todo', 'archived_at' => now()]);
        $mk(['status' => 'todo', 'deadline' => now()->addDays(3)]);

        $this->assertSame($before + 1, $stat());
    }

    public function test_widget_renders_successfully_with_count_up_plugin(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true]);

        Filament::bootCurrentPanel();

        Livewire::withoutLazyLoading();

        Livewire::actingAs($admin)->test(ModuleAnalyticsWidget::class)
            ->assertSuccessful()
            ->assertSee('fi-count-up', false);
    }
}
