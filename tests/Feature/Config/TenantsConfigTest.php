<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

class TenantsConfigTest extends TestCase
{
    public function test_every_known_tenant_has_the_same_key_set(): void
    {
        $fateh = array_diff_key(config('tenants.fateh'), ['modules' => 0]);
        $persol = array_diff_key(config('tenants.persol'), ['modules' => 0]);

        $this->assertSame([], array_diff_key($fateh, $persol));
        $this->assertSame([], array_diff_key($persol, $fateh));
    }

    public function test_behavioral_flags_are_booleans_for_every_tenant(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            foreach (['admin_reverse', 'user_reverse', 'admin_auth_reverse', 'user_auth_reverse', 'admin_use_company', 'user_use_company'] as $flag) {
                $this->assertIsBool(config("tenants.{$tenant}.logo_display.{$flag}"), "{$tenant}.logo_display.{$flag} should be a bool");
            }
        }
    }

    public function test_deliberate_flag_differences_between_tenants_are_pinned(): void
    {
        $this->assertFalse(config('tenants.fateh.logo_display.admin_reverse'));
        $this->assertFalse(config('tenants.fateh.logo_display.user_reverse'));
        $this->assertFalse(config('tenants.fateh.logo_display.admin_auth_reverse'));
        $this->assertFalse(config('tenants.fateh.logo_display.user_auth_reverse'));
        $this->assertFalse(config('tenants.fateh.logo_display.admin_use_company'));
        $this->assertTrue(config('tenants.fateh.logo_display.user_use_company'));

        $this->assertTrue(config('tenants.persol.logo_display.admin_reverse'));
        $this->assertTrue(config('tenants.persol.logo_display.user_reverse'));
        $this->assertFalse(config('tenants.persol.logo_display.admin_auth_reverse'));
        $this->assertFalse(config('tenants.persol.logo_display.user_auth_reverse'));
        $this->assertFalse(config('tenants.persol.logo_display.admin_use_company'));
        $this->assertFalse(config('tenants.persol.logo_display.user_use_company'));
    }

    public function test_asset_derived_keys_resolve_to_non_empty_strings(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            foreach (['company.logo', 'app.logo_light', 'app.logo_dark', 'app.favicon', 'background.user', 'background.admin'] as $key) {
                $this->assertNotEmpty(config("tenants.{$tenant}.{$key}"), "{$tenant}.{$key} should resolve to a non-empty path");
            }
        }
    }

    public function test_company_social_links_resolve_to_non_empty_strings(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $social = config("tenants.{$tenant}.company.social");

            $this->assertNotEmpty($social['instagram']);
            $this->assertNotEmpty($social['linkedin']);
        }
    }

    public function test_company_phone_is_persol_only_and_blank_for_fateh(): void
    {
        $this->assertSame('021-26208352', config('tenants.persol.company.phone'));
        $this->assertSame('', config('tenants.fateh.company.phone'));
    }

    public function test_app_and_company_identity_are_distinct_per_tenant(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $app = config("tenants.{$tenant}.app");
            $company = config("tenants.{$tenant}.company");

            $this->assertNotEmpty($app['name']);
            $this->assertNotEmpty($app['name_en']);
            $this->assertNotEmpty($company['name']);
            $this->assertNotEmpty($company['name_en']);
            $this->assertNotSame($app['name'], $company['name'], "{$tenant}: app identity and company identity should never collapse to the same value");
        }
    }

    public function test_videos_resolves_to_a_non_empty_array_of_paths(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $videos = config("tenants.{$tenant}.videos");

            $this->assertIsArray($videos);
            $this->assertNotEmpty($videos);
            $this->assertContainsOnly('string', $videos);
        }
    }

    public function test_backdrop_images_resolves_to_a_non_empty_array_of_paths(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $images = config("tenants.{$tenant}.backdrop.images");

            $this->assertIsArray($images);
            $this->assertNotEmpty($images);
            $this->assertContainsOnly('string', $images);
        }
    }

    public function test_backdrop_mode_and_width_are_pinned_per_tenant(): void
    {
        $this->assertSame('tabs', config('tenants.fateh.backdrop.mode'));
        $this->assertSame('85%', config('tenants.fateh.backdrop.width'));

        $this->assertSame('time', config('tenants.persol.backdrop.mode'));
        $this->assertSame('100%', config('tenants.persol.backdrop.width'));

        $this->assertNull(config('tenants.fateh.backdrop.fit'));
        $this->assertSame('top', config('tenants.persol.backdrop.fit'));
    }

    public function test_backdrop_opacity_is_a_fraction_for_every_tenant(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $opacity = config("tenants.{$tenant}.backdrop.opacity");

            $this->assertIsFloat($opacity);
            $this->assertGreaterThanOrEqual(0, $opacity);
            $this->assertLessThanOrEqual(1, $opacity);
        }
    }

    public function test_backdrop_filter_keys_default_to_neutral_values_for_every_tenant(): void
    {
        foreach (['fateh', 'persol'] as $tenant) {
            $this->assertSame(0, config("tenants.{$tenant}.backdrop.blur"));
            $this->assertSame(1, config("tenants.{$tenant}.backdrop.brightness"));
            $this->assertSame(1, config("tenants.{$tenant}.backdrop.contrast"));
            $this->assertSame(0, config("tenants.{$tenant}.backdrop.grayscale"));
        }
    }

    public function test_every_tenant_module_key_is_a_module_id_or_one_of_its_views(): void
    {
        $modules = collect(config('modules'))->keyBy('id');

        $this->assertNotEmpty($modules);

        foreach (config('tenants') as $slug => $tenant) {
            foreach (array_keys($tenant['modules'] ?? []) as $key) {
                [$id, $view] = array_pad(explode(':', $key, 2), 2, null);

                $this->assertTrue($modules->has($id) || in_array($key, ['calculator', 'radio', 'stopwatch', 'password-reset'], true), "tenant '{$slug}' key '{$key}' is not a module id");

                if ($view !== null) {
                    $this->assertContains($view, $modules[$id]['views'] ?? [], "tenant '{$slug}' key '{$key}' is not a view of '{$id}'");
                }
            }
        }
    }

    public function test_each_tenant_has_its_own_default_primary_colour(): void
    {
        $this->assertSame('#4e5f66', config('tenants.fateh.theme.primary'));
        $this->assertSame('#375E90', config('tenants.persol.theme.primary'));
    }

    public function test_each_tenant_picks_an_existing_palette_as_its_default_selection(): void
    {
        $this->assertSame('default', config('tenants.fateh.theme.palette'));
        $this->assertSame('blue', config('tenants.persol.theme.palette'));
        $this->assertSame(config('tenants.' . config('app.tenant') . '.theme.palette'), config('app.theme_palette'));
    }

    public function test_no_tenant_switches_off_a_modules_default_view(): void
    {
        $defaults = collect(config('modules'))
            ->filter(fn ($module) => !empty($module['views']))
            ->map(fn ($module) => "{$module['id']}:{$module['views'][0]}")
            ->all();

        $this->assertNotEmpty($defaults);

        foreach (config('tenants') as $slug => $tenant) {
            foreach ($tenant['modules'] ?? [] as $key => $on) {
                $this->assertNotContains($key, $defaults, "tenant '{$slug}' hides default view '{$key}'");
            }
        }
    }

    public function test_password_reset_is_off_for_fateh_and_on_for_persol(): void
    {
        $tenants = require base_path('config/tenants.php');

        $this->assertFalse($tenants['fateh']['modules']['password-reset']);
        $this->assertArrayNotHasKey('password-reset', $tenants['persol']['modules']);
    }
}
