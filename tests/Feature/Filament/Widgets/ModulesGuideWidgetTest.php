<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\ModulesGuideWidget;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class ModulesGuideWidgetTest extends TestCase
{
    private const CATEGORIES_ORDER = [
        'محتوا و ارتباطات',
        'عملیات و منابع',
        'سیستم‌ها و ابزارها',
        'کاربران و سازمان',
    ];

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

    private function widget(): ModulesGuideWidget
    {
        return (new ReflectionClass(ModulesGuideWidget::class))->newInstanceWithoutConstructor();
    }

    private function grouped(ModulesGuideWidget $widget): Collection
    {
        return $widget->getGroupedModules();
    }

    public function test_get_grouped_modules_returns_collection_with_md5_keys_and_expected_shape(): void
    {
        $grouped = $this->grouped($this->widget());

        $this->assertInstanceOf(Collection::class, $grouped);
        $this->assertNotEmpty($grouped);

        foreach ($grouped as $key => $group) {
            $this->assertSame(32, strlen((string) $key), 'every key must be an md5 hash');
            $this->assertIsArray($group);
            $this->assertArrayHasKey('name', $group);
            $this->assertArrayHasKey('modules', $group);
            $this->assertIsString($group['name']);
            $this->assertContains($group['name'], self::CATEGORIES_ORDER);
            $this->assertNotEmpty($group['modules']);
        }
    }

    public function test_group_order_follows_predefined_category_order(): void
    {
        $grouped = $this->grouped($this->widget());

        $present = $grouped->pluck('name')->values()->all();
        $expected = array_values(array_filter(
            self::CATEGORIES_ORDER,
            fn(string $category) => in_array($category, $present, true)
        ));

        $this->assertSame($expected, $present, 'groups must appear in the predefined Persian category order');
    }

    public function test_every_config_module_appears_in_exactly_one_group(): void
    {
        $grouped = $this->grouped($this->widget());

        $groupedIds = [];
        foreach ($grouped as $group) {
            foreach ($group['modules'] as $module) {
                $groupedIds[] = $module['id'] ?? null;
            }
        }

        $configIds = collect(config('modules', []))->pluck('id')->all();

        $this->assertEqualsCanonicalizing($configIds, array_values(array_filter($groupedIds, fn($id) => $id !== null)));
        $this->assertSame(count($configIds), count($groupedIds), 'no module may be duplicated across groups');
    }

    public function test_module_with_no_meta_entry_lands_in_default_category(): void
    {
        $original = config('modules', []);
        $injected = array_merge($original, [
            ['id' => '__test_orphan', 'icon' => 'x', 'title' => 't', 'summary' => 's'],
        ]);

        try {
            config(['modules' => $injected]);
            $grouped = $this->grouped($this->widget());

            $orphanGroup = null;
            foreach ($grouped as $group) {
                foreach ($group['modules'] as $module) {
                    if (($module['id'] ?? null) === '__test_orphan') {
                        $orphanGroup = $group['name'];
                        break 2;
                    }
                }
            }

            $this->assertNotNull($orphanGroup, 'injected orphan module must appear in a group');
            $this->assertSame('محتوا و ارتباطات', $orphanGroup, 'module with no major_category defaults to محتوا و ارتباطات');
        } finally {
            config(['modules' => $original]);
        }
    }

    public function test_module_meta_and_module_filament_icons_return_arrays(): void
    {
        $widget = $this->widget();

        $meta = (new ReflectionMethod($widget, 'moduleMeta'))->invoke($widget);
        $icons = (new ReflectionMethod($widget, 'moduleFilamentIcons'))->invoke($widget);

        $this->assertIsArray($meta);
        $this->assertIsArray($icons);
        $this->assertNotEmpty($meta);
        $this->assertNotEmpty($icons);

        foreach ($meta as $id => $entry) {
            $this->assertArrayHasKey('major_category', $entry, "meta entry '{$id}' must carry major_category");
            $this->assertArrayHasKey('admin_tip', $entry, "meta entry '{$id}' must carry admin_tip");
        }
        foreach ($icons as $id => $entry) {
            $this->assertArrayHasKey('filament_icon', $entry, "icon entry '{$id}' must carry filament_icon");
        }
    }

    public function test_widget_renders_successfully(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true]);

        Livewire::actingAs($admin)->test(ModulesGuideWidget::class)->assertSuccessful();
    }
}