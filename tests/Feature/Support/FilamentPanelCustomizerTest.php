<?php

namespace Tests\Feature\Support;

use App\Models\User;
use App\Support\FilamentPanelCustomizer;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\HtmlString;
use ReflectionMethod;
use Tests\TestCase;

class FilamentPanelCustomizerTest extends TestCase
{
    public function test_pref_returns_default_when_unauthenticated(): void
    {
        $this->assertFalse($this->invoke('pref', ['sidebar_collapsible', false]));
        $this->assertTrue($this->invoke('pref', ['breadcrumbs', true]));
        $this->assertFalse($this->invoke('pref', ['top_nav', false]));
    }

    public function test_pref_returns_user_preference_when_authenticated_and_falls_back_to_default_for_missing_keys(): void
    {
        $user = new User();
        $user->extra = ['preferences' => ['top_nav' => true, 'sidebar_collapsible' => false]];

        $this->actingAs($user);

        $this->assertTrue($this->invoke('pref', ['top_nav', false]));
        $this->assertFalse($this->invoke('pref', ['sidebar_collapsible', true]));
        $this->assertTrue($this->invoke('pref', ['spa_enabled', true]), 'missing key must fall back to default true');
        $this->assertFalse($this->invoke('pref', ['user_menu_topbar', false]), 'missing key must fall back to default false');
    }

    public function test_pref_tolerates_a_user_with_no_preferences_bucket(): void
    {
        $user = new User();
        $user->extra = [];

        $this->actingAs($user);

        $this->assertTrue($this->invoke('pref', ['breadcrumbs', true]));
        $this->assertFalse($this->invoke('pref', ['topbar', false]));
    }

    public function test_tenant_background_style_is_empty_off_the_login_route(): void
    {
        app()->instance('request', Request::create('/admin/dashboard', 'GET'));

        $this->assertSame('', (string) $this->invoke('tenantBackgroundStyle'));
    }

    public function test_tenant_background_style_emits_css_variable_with_asset_url_on_the_login_route(): void
    {
        config(['app.admin_background_image' => 'tenants/fateh/bg.jpg']);

        $route = new Route(['GET'], '/admin/login', []);
        $route->name('filament.admin.auth.login');
        $request = Request::create('/admin/login', 'GET');
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);

        $html = (string) $this->invoke('tenantBackgroundStyle');

        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('--tenant-bg-image:url("', $html);
        $this->assertStringContainsString('/tenants/fateh/bg.jpg', $html);
    }

    public function test_logo_html_emits_img_tag_with_name_en_alt_and_tenant_logo_for_both_modes(): void
    {
        config([
            'app.name_en' => 'FatehTest',
            'app.admin_use_company_logo' => false,
            'app.admin_reverse_logo' => false,
            'app.app_logo_light' => 'tenants/fateh/light.png',
            'app.app_logo_dark' => 'tenants/fateh/dark.png',
        ]);

        $light = (string) $this->invoke('logoHtml', [false]);
        $dark = (string) $this->invoke('logoHtml', [true]);

        foreach ([$light, $dark] as $html) {
            $this->assertStringContainsString('<img', $html);
            $this->assertStringContainsString('alt="FatehTest"', $html);
            $this->assertStringContainsString('title="FatehTest"', $html);
            $this->assertStringContainsString('style="height:2rem;width:auto;margin:auto"', $html);
        }

        $this->assertStringContainsString('/tenants/fateh/light.png', $light);
        $this->assertStringContainsString('/tenants/fateh/dark.png', $dark);
        $this->assertNotSame($light, $dark, 'dark and light modes must resolve to different logo assets when reverse-logo is off');
    }

    public function test_logo_html_uses_the_auth_specific_flag_on_the_login_route_independent_of_the_normal_flag(): void
    {
        config([
            'app.name_en' => 'FatehTest',
            'app.admin_use_company_logo' => false,
            'app.admin_reverse_logo' => false,
            'app.admin_auth_reverse_logo' => true,
            'app.app_logo_light' => 'tenants/fateh/light.png',
            'app.app_logo_dark' => 'tenants/fateh/dark.png',
        ]);

        $route = new Route(['GET'], '/admin/login', []);
        $route->name('filament.admin.auth.login');
        $request = Request::create('/admin/login', 'GET');
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);

        $light = (string) $this->invoke('logoHtml', [false]);
        $dark = (string) $this->invoke('logoHtml', [true]);

        $this->assertStringContainsString('/tenants/fateh/dark.png', $light, 'admin_auth_reverse_logo must swap the light-mode logo to dark on the login route');
        $this->assertStringContainsString('/tenants/fateh/light.png', $dark, 'admin_auth_reverse_logo must swap the dark-mode logo to light on the login route');
    }

    private function invoke(string $method, array $args = []): mixed
    {
        $m = new ReflectionMethod(FilamentPanelCustomizer::class, $method);
        $m->setAccessible(true);

        return $m->invoke(null, ...$args);
    }
}