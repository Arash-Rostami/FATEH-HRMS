<?php

namespace Tests\Feature\Config;

use App\Filament\Widgets\ModulesGuideWidget;
use App\Livewire\Dashboard\Tabs;
use App\Services\TenantService;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class ModulesConfigTest extends TestCase
{
    private const CATEGORIES_ORDER = [
        'محتوا و ارتباطات',
        'عملیات و منابع',
        'سیستم‌ها و ابزارها',
        'کاربران و سازمان',
    ];

    public function test_every_module_has_required_content_keys_and_non_empty_values(): void
    {
        foreach (config('modules', []) as $module) {
            foreach (['id', 'icon', 'title', 'summary'] as $key) {
                $this->assertNotEmpty($module[$key] ?? null, "module '{$module['id']}' is missing '{$key}'");
            }
        }
    }

    public function test_module_ids_are_unique(): void
    {
        $ids = array_column(config('modules', []), 'id');

        $this->assertCount(count($ids), array_unique($ids));
    }

    public function test_every_module_id_has_a_widget_meta_and_icon_mapping(): void
    {
        $widget = new ModulesGuideWidget();
        $meta = (new ReflectionMethod($widget, 'moduleMeta'))->invoke($widget);
        $icons = (new ReflectionMethod($widget, 'moduleFilamentIcons'))->invoke($widget);

        foreach (array_column(config('modules', []), 'id') as $id) {
            $this->assertArrayHasKey($id, $meta, "module '{$id}' has no ModulesGuideWidget::moduleMeta() entry");
            $this->assertArrayHasKey($id, $icons, "module '{$id}' has no ModulesGuideWidget::moduleFilamentIcons() entry");
        }
    }

    public function test_every_meta_major_category_is_one_of_the_four_known_categories(): void
    {
        $widget = new ModulesGuideWidget();
        $meta = (new ReflectionMethod($widget, 'moduleMeta'))->invoke($widget);

        foreach ($meta as $id => $entry) {
            $this->assertContains(
                $entry['major_category'] ?? null,
                self::CATEGORIES_ORDER,
                "module '{$id}' has an unrecognized major_category"
            );
        }
    }

    public function test_every_nav_block_has_a_valid_type_and_a_non_empty_target(): void
    {
        foreach (config('modules', []) as $module) {
            if (!isset($module['nav'])) {
                continue;
            }

            $this->assertContains(
                $module['nav']['type'] ?? null,
                ['tab', 'route', 'url'],
                "module '{$module['id']}' nav has an unrecognized type"
            );
            $this->assertIsString($module['nav']['target'] ?? null, "module '{$module['id']}' nav target must be a string");
            $this->assertNotEmpty(trim($module['nav']['target']), "module '{$module['id']}' nav target is empty");
        }
    }

    public function test_every_route_and_url_nav_target_is_a_registered_route_name(): void
    {
        foreach (config('modules', []) as $module) {
            if (!isset($module['nav']) || $module['nav']['type'] === 'tab') {
                continue;
            }

            $this->assertTrue(
                Route::has($module['nav']['target']),
                "module '{$module['id']}' nav target '{$module['nav']['target']}' is not a registered route name"
            );
        }
    }

    public function test_every_tab_nav_target_resolves_to_a_real_dashboard_tab(): void
    {
        $tabs = app(Tabs::class)->tabs;

        foreach (config('modules', []) as $module) {
            if (!isset($module['nav']) || $module['nav']['type'] !== 'tab') {
                continue;
            }

            $this->assertArrayHasKey(
                $module['nav']['target'],
                $tabs,
                "module '{$module['id']}' nav target '{$module['nav']['target']}' is not a dashboard tab"
            );
        }
    }

    public function test_derived_shortcut_keys_are_unique_and_none_collides_with_home(): void
    {
        $keys = [];

        foreach (config('modules', []) as $module) {
            if (isset($module['nav'])) {
                $keys[] = $module['nav']['key'] ?? $module['id'];
            }
        }

        $this->assertGreaterThan(0, count($keys));
        $this->assertCount(count($keys), array_unique($keys), 'shortcut keys (nav.key ?? id) contain duplicates');
        $this->assertNotContains('home', $keys, "a module nav key collides with the reserved 'home' shortcut");
    }

    public function test_with_and_views_declarations_point_at_real_modules_and_ids_are_not_reserved_words(): void
    {
        $ids = array_column(config('modules'), 'id');

        foreach (config('modules') as $module) {
            $this->assertNotContains($module['id'], ['auth', 'login', 'logout', 'register', 'user', 'users', 'admin', 'api', 'livewire', 'filament', 'dashboard', 'app', 'home'], "module id '{$module['id']}' is a reserved word");
            $this->assertNotEmpty($module['views'] ?? ['x']);

            foreach ($module['with'] ?? [] as $with) {
                $this->assertContains(strtok($with, ':'), $ids, "module '{$module['id']}' lists unknown 'with' target '{$with}'");
            }
        }
    }

    public function test_alias_words_never_equal_another_modules_id_or_nav_words(): void
    {
        $owned = [];

        foreach (config('modules') as $module) {
            foreach (array_filter([$module['id'], $module['nav']['key'] ?? null, $module['nav']['target'] ?? null]) as $word) {
                $owned[$word] = $module['id'];
            }
        }

        foreach (config('modules') as $module) {
            foreach ($module['aliases'] ?? [] as $alias) {
                $this->assertTrue(!isset($owned[$alias]) || $owned[$alias] === $module['id'], "alias '{$alias}' of '{$module['id']}' belongs to '" . ($owned[$alias] ?? '') . "'");
            }
        }
    }

    public function test_every_route_name_belongs_to_at_most_one_module_when_that_module_is_off(): void
    {
        $owners = [];
        $modules = config('modules');
        $tenant = config('app.tenant');

        try {
            config(['app.tenant' => 'contract-test', 'modules' => array_map(fn ($m) => array_diff_key($m, ['with' => 0]), $modules)]);

            foreach ($modules as $module) {
                config(['app.off' => [$module['id']]]);

                foreach (array_keys(Route::getRoutes()->getRoutesByName()) as $name) {
                    if (tenantOffName($name)) {
                        $owners[$name][] = $module['id'];
                    }
                }
            }
        } finally {
            config(['app.off' => [], 'app.tenant' => $tenant, 'modules' => $modules]);
        }

        foreach ($owners as $name => $claimants) {
            $this->assertCount(1, $claimants, "route '{$name}' is claimed by several modules: " . implode(', ', $claimants));
        }
    }

    public function test_correspondence_module_registers_its_user_and_admin_entry_points_and_ai_view(): void
    {
        $module = collect(config('modules'))->firstWhere('id', 'correspondence');

        $this->assertNotNull($module);
        $this->assertSame('route', data_get($module, 'nav.type'));
        $this->assertSame('correspondence', data_get($module, 'nav.target'));
        $this->assertContains('ai', $module['views'] ?? []);
    }

    public function test_notify_module_cascades_off_into_correspondence_but_its_mail_view_alone_does_not(): void
    {
        $notify = collect(config('modules'))->firstWhere('id', 'notify');
        $this->assertContains('correspondence', $notify['with'] ?? []);

        $off = config('app.off');

        try {
            config(['app.off' => ['notify']]);
            $lookup = app(TenantService::class)->lookup();
            $this->assertArrayHasKey('correspondence', $lookup);
            $this->assertTrue(tenantOff('correspondence'));
            $this->assertTrue(tenantOff('correspondence:ai'));
            $this->assertFalse(correspondenceMailOn());

            config(['app.off' => ['notify:mail']]);
            $this->assertArrayNotHasKey('correspondence', app(TenantService::class)->lookup());
            $this->assertFalse(tenantOff('correspondence'));
            $this->assertFalse(correspondenceMailOn());
        } finally {
            config(['app.off' => $off]);
        }
    }

    public function test_knowledge_is_one_route_module_with_reports_and_media_views_and_no_legacy_modules(): void
    {
        $modules = collect(config('modules'));
        $module = $modules->firstWhere('id', 'knowledge');

        $this->assertNotNull($module);
        $this->assertNull($modules->firstWhere('id', 'reports'));
        $this->assertNull($modules->firstWhere('id', 'media'));
        $this->assertSame('route', data_get($module, 'nav.type'));
        $this->assertSame('knowledge', data_get($module, 'nav.target'));
        $this->assertSame(['reports', 'media'], $module['views']);
        $this->assertEqualsCanonicalizing(['reports', 'media', 'media-controller'], $module['aliases']);
    }

    public function test_knowledge_off_hides_everything_and_its_media_view_hides_only_the_media_surfaces(): void
    {
        $off = config('app.off');

        try {
            config(['app.off' => ['knowledge']]);

            foreach (['knowledge', 'knowledge:media', 'reports', 'media', 'media-controller'] as $key) {
                $this->assertTrue(tenantOff($key), $key);
            }

            foreach (['knowledge', 'reports-controller', 'media.progress', 'media.notes', 'filament.admin.resources.reports.index', 'filament.admin.resources.media.index', 'filament.admin.resources.knowledge-progress.index'] as $name) {
                $this->assertTrue(tenantOffName($name), $name);
            }

            config(['app.off' => ['knowledge:media']]);

            $this->assertTrue(tenantOff('knowledge:media'));
            $this->assertFalse(tenantOff('knowledge'));
            $this->assertFalse(tenantOff('reports'));
            $this->assertFalse(tenantOffName('knowledge'));
            $this->assertFalse(tenantOffName('reports-controller'));

            config(['app.off' => ['knowledge:reports']]);

            $this->assertFalse(tenantOff('knowledge'));
            $this->assertFalse(tenantOff('knowledge:reports'));
        } finally {
            config(['app.off' => $off]);
        }
    }
}
