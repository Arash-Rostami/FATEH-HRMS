<?php

namespace Tests\Feature\Providers;

use App\Models\Onboarding;
use App\Models\User;
use App\View\Composers\AiAssistantComposer;
use App\View\Composers\AuthShellComposer;
use App\View\Composers\LeftNavComposer;
use App\View\Composers\ModuleChunkComposer;
use App\View\Composers\ReleaseModulesComposer;
use App\View\Composers\ScreenSaverComposer;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ViewServiceProviderTest extends TestCase
{
    // regression guard: layouts/app.blade.php's data-max-scope / lazy-chunk @vite entry used to
    // be computed by an inline @php block directly in the view; moved to ModuleChunkComposer
    // (registered via View::composer('layouts.app', ...) in ViewServiceProvider) on 2026-10-06
    // for testability. These assertions prove the composer actually fires and resolves the same
    // scope/chunk values the inline block used to — not just that the class compiles.

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

    public function test_a_plain_module_route_gets_its_own_scope_and_lazy_chunk(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $html = $this->actingAs($user)->get('/dms')->getContent();

        $this->assertStringContainsString('data-max-scope="dms"', $html);

        // Vite outputs a hashed filename (assets/dms-XXXXXXXX.js), never the literal source
        // path — resolve the real built filename from the manifest so this stays correct
        // across rebuilds instead of asserting on a source-path substring Vite never emits.
        $manifestPath = public_path('build/manifest.json');
        if (is_file($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            $entry = $manifest['resources/js/components/alpine/modules/dms.js']['file'] ?? null;
            if ($entry) {
                $this->assertStringContainsString($entry, $html);
            }
        }
    }

    public function test_dashboard_route_resolves_scope_from_the_tab_query_param_not_the_route_name(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $home = $this->actingAs($user)->get('/dashboard')->getContent();
        $this->assertStringContainsString('data-max-scope="hero"', $home);

        $feed = $this->actingAs($user)->get('/dashboard?tab=feed')->getContent();
        $this->assertStringContainsString('data-max-scope="feed"', $feed);

        $calendar = $this->actingAs($user)->get('/dashboard?tab=calendar')->getContent();
        $this->assertStringContainsString('data-max-scope="calendar"', $calendar);
    }

    public function test_the_chat_font_picker_gets_the_registry_fonts_and_their_font_face_css_from_its_composer(): void
    {
        $html = view('livewire.dashboard.messaging.font-picker')->render();

        $this->assertStringContainsString('@font-face', $html);
        $this->assertStringContainsString("setMessageFont('vazirmatn')", $html);
        $this->assertStringNotContainsString("setMessageFont('btitr')", $html);
        $this->assertStringNotContainsString("setMessageFont('iranyekan')", $html);
    }

    public function test_dashboard_route_never_loads_a_lazy_module_chunk(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $html = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringNotContainsString('components/alpine/modules/', $html);
    }

    public function test_embed_mode_drops_the_loading_line_and_the_pulse_component_but_normal_pages_keep_them(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $normal = $this->actingAs($user)->get('/dms')->getContent();
        $this->assertStringContainsString('class="loading-line"', $normal);
        $this->assertStringContainsString('x-data="pulse(', $normal);
        $this->assertStringContainsString('id="content-shell"', $normal);

        $embed = $this->actingAs($user)->get('/dms?embed=1')->getContent();
        $this->assertStringNotContainsString('class="loading-line"', $embed);
        $this->assertStringNotContainsString('x-data="pulse(', $embed);
        $this->assertStringContainsString('id="content-shell"', $embed);
        $this->assertStringContainsString('window.__tenantBackdrop', $embed);
    }

    public function test_layout_composer_supplies_shell_flags_backdrop_and_vite_entries(): void
    {
        $factory = app('view');
        $composer = new ModuleChunkComposer();

        request()->setRouteResolver(fn () => (new Route('GET', '/x', []))->name('dms'));
        $view = $factory->make('layouts.app');
        $composer->compose($view);
        $data = $view->getData();

        $this->assertSame('dms', $data['moduleChunk']);
        $this->assertFalse($data['embed']);
        $this->assertTrue($data['chrome']);
        $this->assertSame([
            'resources/css/app.css',
            'resources/js/app.js',
            'resources/js/components/alpine/modules/dms.js',
        ], $data['viteEntries']);
        $this->assertSame(
            array_map('asset', config('app.backdrop.images')),
            $data['backdrop']['images']
        );

        request()->setRouteResolver(fn () => (new Route('GET', '/x', []))->name('ads'));
        $view = $factory->make('layouts.app');
        $composer->compose($view);

        $this->assertNull($view->getData()['moduleChunk']);
        $this->assertSame(['resources/css/app.css', 'resources/js/app.js'], $view->getData()['viteEntries']);
    }

    public function test_knowledge_route_loads_its_lazy_chunk_and_only_the_media_tab_has_a_maximize_scope(): void
    {
        $composer = new ModuleChunkComposer();

        request()->setRouteResolver(fn () => (new Route('GET', '/knowledge', []))->name('knowledge'));
        $view = app('view')->make('layouts.app');
        $composer->compose($view);
        $data = $view->getData();

        $this->assertSame('knowledge', $data['moduleChunk']);
        $this->assertContains('resources/js/components/alpine/modules/knowledge.js', $data['viteEntries']);
        $this->assertNull($data['maxScope']);

        request()->merge(['tab' => 'media']);
        $view = app('view')->make('layouts.app');
        $composer->compose($view);

        $this->assertSame('media', $view->getData()['maxScope']);
    }

    public function test_layout_composer_hides_the_chrome_for_embed_and_for_the_minimal_layout_section(): void
    {
        $factory = app('view');
        $composer = new ModuleChunkComposer();

        request()->merge(['embed' => '1']);
        $view = $factory->make('layouts.app');
        $composer->compose($view);
        $this->assertTrue($view->getData()['embed']);
        $this->assertFalse($view->getData()['chrome']);

        request()->replace([]);
        $factory->startSection('minimal_layout', 'true');
        try {
            $view = $factory->make('layouts.app');
            $composer->compose($view);
            $this->assertFalse($view->getData()['embed']);
            $this->assertFalse($view->getData()['chrome']);
        } finally {
            $factory->flushState();
        }

        $view = $factory->make('layouts.app');
        $composer->compose($view);
        $this->assertTrue($view->getData()['chrome']);
    }

    public function test_screen_saver_composer_resolves_the_enabled_flag_and_timeout_from_user_preferences(): void
    {
        $composer = new ScreenSaverComposer();
        $read = function (?User $user) use ($composer) {
            $user ? $this->actingAs($user) : auth()->logout();
            $view = view('components.ui.loaders.screen-saver');
            $composer->compose($view);

            return $view->getData();
        };

        $guest = $read(null);
        $this->assertFalse($guest['isScreenSaverEnabled']);

        $user = User::factory()->create(['status' => 'active']);
        $default = $read($user);
        $this->assertTrue($default['isScreenSaverEnabled']);
        $this->assertSame(60, $default['timeout']);

        $user->extra = ['preferences' => ['screen_saver' => false, 'screen_saver_timeout' => '120']];
        $custom = $read($user);
        $this->assertFalse($custom['isScreenSaverEnabled']);
        $this->assertSame(120, $custom['timeout']);
    }

    public function test_ai_assistant_composer_builds_the_embed_url_only_when_the_assistant_is_on(): void
    {
        config([
            'app.ai_assistant_access' => true,
            'services.ai_assistant.url' => 'https://ai.test/',
            'app.name_en' => 'My App',
            'app.ai_assistant_lang' => 'fa',
        ]);
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $composer = new AiAssistantComposer();

        $view = view('components.dashboard.ai-assistant');
        $this->actingAs($user);
        $composer->compose($view);
        $this->assertSame('https://ai.test/?user=my-app_' . $user->id . '&lang=fa&role=user', $view->getData()['aiAssistantBaseSrc']);
        $this->assertTrue($view->getData()['aiAccess']);

        config(['services.ai_assistant.url' => '']);
        $off = view('components.dashboard.ai-assistant');
        $composer->compose($off);
        $this->assertNull($off->getData()['aiAssistantBaseSrc']);
    }

    public function test_left_nav_composer_supplies_the_four_module_links_in_order(): void
    {
        $view = view('components.dashboard.navbars.left');
        (new LeftNavComposer())->compose($view);
        $items = $view->getData()['navItems'];

        $this->assertSame(['tasks', 'projects', 'dms', 'ths'], array_column($items, 'id'));
        $this->assertSame(['taskboard', 'project', 'dms', 'ths'], array_column($items, 'module'));
        $this->assertSame(url('/tasks'), $items[0]['href']);
        $this->assertSame('animate-delay-500', $items[3]['delay']);
    }

    public function test_release_modules_composer_never_lists_a_module_the_tenant_switched_off(): void
    {
        $view = view('components.dashboard.modal.release');
        (new ReleaseModulesComposer())->compose($view);
        $modules = $view->getData()['allModules'];

        $this->assertInstanceOf(Collection::class, $modules);
        foreach ($modules as $module) {
            $this->assertFalse(tenantOff($module['id']));
        }
    }

    public function test_auth_shell_composer_feeds_the_layout_videos_and_the_card_logos(): void
    {
        $composer = new AuthShellComposer();

        $layout = view('layouts.auth');
        $composer->compose($layout);
        $this->assertSame(array_map('asset', array_values(config('app.videos', []))), $layout->getData()['videos']);

        $card = view('components.auth.card-no-signup');
        $composer->compose($card);
        $this->assertSame(asset(tenantLogo(false, 'user', true)), $card->getData()['lightLogo']);
        $this->assertSame(asset(tenantLogo(true, 'user', true)), $card->getData()['darkLogo']);
        $this->assertSame(config('app.name_en', 'WinterrA'), $card->getData()['appName']);
    }

    public function test_release_note_component_keeps_the_color_class_the_check_icon_and_raw_html_body(): void
    {
        $html = Blade::render('<x-dashboard.modal.release-note color="secondary" title="T1" :desc="$desc"/>', ['desc' => '<b>D</b>']);

        $this->assertStringContainsString('text-[var(--md-sys-color-secondary)]', $html);
        $this->assertStringContainsString('>check_circle</span>', $html);
        $this->assertStringContainsString('<strong class="text-xs flex-1 text-[var(--md-sys-color-on-surface)] text-right">T1</strong>', $html);
        $this->assertStringContainsString('<b>D</b>', $html);
    }

    public function test_the_dashboard_left_nav_and_the_screen_saver_view_render_through_their_composers(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $dashboard = $this->actingAs($user)->get('/dashboard')->getContent();
        $this->assertStringContainsString('data-module="taskboard"', $dashboard);
        $this->assertStringContainsString('data-module="ths"', $dashboard);

        $this->assertStringContainsString('fs-overlay', view('components.ui.loaders.screen-saver')->render());

        $user->extra = ['preferences' => ['screen_saver' => false]];
        $this->assertStringNotContainsString('fs-overlay', view('components.ui.loaders.screen-saver')->render());
    }

    public function test_account_component_highlights_a_recently_updated_exclusive_onboarding(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => true, 'updated_at' => now()]);

        $html = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('محتوای آنبوردینگ اختصاصی شما آماده است', $html);
    }

    public function test_occasion_component_surfaces_a_birthday_message_on_the_users_birthdate(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->profile()->create(['birthdate' => now()->subYears(30)]);

        $html = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('تولدتان مبارک ' . $user->name . '!', $html);
    }

    public function test_module_chunks_component_exposes_every_module_asset_path(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $html = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('window.__moduleChunks', $html);
        $this->assertStringContainsString('"dms":', $html);
        $this->assertStringContainsString('"tasksheet":', $html);
    }
}
