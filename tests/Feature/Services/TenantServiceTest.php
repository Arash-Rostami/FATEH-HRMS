<?php

namespace Tests\Feature\Services;

use App\Services\TenantService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    private function css(string $header, string $primary = '#4e5f66'): string
    {
        config(['app.theme_primary' => $primary, 'app.theme_header_bg' => $header]);

        return (new TenantService())->themeCss();
    }

    public function test_default_header_emits_nothing_whatever_the_tenant_primary_is(): void
    {
        $this->assertSame('', $this->css('#08191e'));
        $this->assertSame('', $this->css('#08191E', '#375E90'));
    }

    public function test_custom_header_is_scoped_to_default_theme_and_never_overrides_the_primary_palette(): void
    {
        $css = $this->css('#042f2e', '#0F766E');

        $this->assertStringContainsString('--header-bg:#042f2e', $css);
        $this->assertStringContainsString(':root:not([data-theme]){', $css);
        $this->assertStringContainsString(':root.dark:not([data-theme]){', $css);
        $this->assertStringNotContainsString('--md-sys-color-primary', $css);
        $this->assertStringNotContainsString('[data-theme=', $css);
    }

    public function test_short_hex_header_is_expanded(): void
    {
        $this->assertStringContainsString('--header-bg:#aabbcc', $this->css('#abc'));
    }

    public function test_invalid_headers_emit_nothing(): void
    {
        foreach (['red', '#12345', '#gggggg', 'url(x)', '#fff;}body{display:none'] as $bad) {
            $this->assertSame('', $this->css($bad));
        }
    }

    public function test_memo_does_not_leak_between_configs(): void
    {
        $a = $this->css('#042f2e');
        $b = $this->css('#1a2b3c');

        $this->assertNotSame($a, $b);
        $this->assertSame($a, $this->css('#042f2e'));
        $this->assertSame('', $this->css('#08191e'));
    }

    private function off(array $off): TenantService
    {
        config(['app.off' => $off]);

        return new TenantService();
    }

    private function chain(array $modules, array $off): TenantService
    {
        config(['app.tenant' => 'chain-' . uniqid(), 'modules' => $modules]);

        return $this->off($off);
    }

    public function test_off_is_false_for_an_empty_list(): void
    {
        $service = $this->off([]);

        $this->assertFalse($service->off('reservation'));
        $this->assertFalse($service->offName('dashboard.reservation.main'));
        $this->assertSame('', $service->offCss());
    }

    public function test_off_matches_ids_nav_aliases_and_view_prefixes(): void
    {
        $service = $this->off(['announce', 'taskboard', 'faq', 'reservation']);

        $this->assertTrue($service->off('announce'));
        $this->assertTrue($service->off('post'));
        $this->assertTrue($service->off('tasks'));
        $this->assertTrue($service->off('faqs'));
        $this->assertTrue($service->off('reservation:avantgarde'));
        $this->assertFalse($service->off('status'));
        $this->assertFalse($service->off('calendar:month'));
    }

    public function test_off_view_key_hides_only_that_view(): void
    {
        $service = $this->off(['status:chart']);

        $this->assertTrue($service->off('status:chart'));
        $this->assertFalse($service->off('status'));
        $this->assertFalse($service->off('status:grid'));
    }

    public function test_with_cascade_is_one_way_and_one_level(): void
    {
        $this->assertTrue($this->off(['taskboard'])->off('projects'));
        $this->assertTrue($this->off(['taskboard'])->off('tasksheet'));
        $this->assertTrue($this->off(['taskboard'])->offName('dashboard.project.kanban'));
        $this->assertFalse($this->off(['projects'])->off('taskboard'));
    }

    public function test_with_cascade_resolves_chains_regardless_of_registry_order(): void
    {
        $modules = [
            ['id' => 'zc', 'nav' => ['key' => 'zc-key', 'type' => 'route', 'target' => 'zc-target']],
            ['id' => 'zb', 'with' => ['zc']],
            ['id' => 'za', 'with' => ['zb']],
        ];

        $service = $this->chain($modules, ['za']);

        $this->assertTrue($service->off('zb'));
        $this->assertTrue($service->off('zc'));
        $this->assertTrue($service->off('zc-key'));
        $this->assertTrue($service->off('zc-target'));
        $this->assertFalse($this->chain(array_reverse($modules), ['zc'])->off('za'));
        $this->assertTrue($this->chain(array_reverse($modules), ['za'])->off('zc'));
        $this->assertFalse($this->chain($modules, ['zb'])->off('za'));
    }

    public function test_with_cascade_terminates_on_cycles(): void
    {
        $service = $this->chain([['id' => 'xa', 'with' => ['xb']], ['id' => 'xb', 'with' => ['xa']]], ['xa']);

        $this->assertTrue($service->off('xb'));
    }

    public function test_lookup_lists_expanded_keys(): void
    {
        $this->assertContains('tasksheet', array_keys($this->off(['taskboard'])->lookup()));
    }

    public function test_off_name_matches_the_module_slot_only(): void
    {
        $service = $this->off(['reservation', 'ads', 'gallery', 'faq', 'skills', 'status', 'profile']);

        $this->assertTrue($service->offName('filament.admin.resources.reservations.index'));
        $this->assertTrue($service->offName('filament.admin.resources.reservation-policies.index'));
        $this->assertTrue($service->offName('filament.admin.resources.galleries.index'));
        $this->assertTrue($service->offName('filament.admin.resources.f-a-q-s.index'));
        $this->assertTrue($service->offName('dashboard.reservation.main'));
        $this->assertTrue($service->offName('dashboard.tab.faqs'));
        $this->assertTrue($service->offName('ads'));
        $this->assertTrue($service->offName('dashboard.profile.skills'));
        $this->assertFalse($service->offName('downloads.file'));
        $this->assertFalse($service->offName('filament.admin.auth.login'));
        $this->assertFalse($service->offName('user-profile-information.update'));
        $this->assertFalse($service->offName('livewire.update'));
        $this->assertFalse($service->offName('dashboard.navbar.status-switcher'));
    }

    public function test_off_name_profile_second_segment_follows_its_own_module(): void
    {
        $this->assertTrue($this->off(['skills'])->offName('dashboard.profile.skills'));
        $this->assertFalse($this->off(['skills'])->offName('dashboard.profile.documents'));
        $this->assertTrue($this->off(['ths'])->offName('dashboard.profile.tickets'));
    }

    public function test_off_css_hides_only_its_own_links(): void
    {
        $css = $this->off(['reservation'])->offCss();

        $this->assertStringContainsString('{display:none!important}', $css);
        $this->assertStringContainsString('reservation', $css);
        $this->assertStringNotContainsString('/tasks', $css);
        $this->assertStringNotContainsString('tab=faqs', $css);
    }

    public function test_off_css_keeps_tasks_and_tasksheet_apart(): void
    {
        $css = $this->off(['tasksheet'])->offCss();

        $this->assertStringContainsString('a[href$="/tasksheet"]', $css);
        $this->assertStringNotContainsString('a[href$="/tasks"]', $css);
        $this->assertStringContainsString('a[href$="/tasks"]', $this->off(['taskboard'])->offCss());
    }

    public function test_off_css_hides_off_tab_links(): void
    {
        $this->assertStringContainsString('a[href*="tab=faqs&"],a[href$="tab=faqs"]', $this->off(['faq'])->offCss());
    }

    public function test_off_css_recomputes_when_a_route_is_added_without_a_cache_clear(): void
    {
        $service = $this->off(['reservation']);
        $before = $service->offCss();

        Route::get('zz-offcss-probe-reservation', fn () => '')->name('reservation.zzprobe');

        $after = $service->offCss();

        $this->assertStringNotContainsString('zz-offcss-probe-reservation', $before);
        $this->assertStringContainsString('a[href$="/zz-offcss-probe-reservation"]', $after);
        $this->assertSame($after, (new TenantService())->offCss());
    }

    public function test_module_aliases_hide_keys_that_carry_no_module_word(): void
    {
        $calendar = $this->off(['calendar']);

        $this->assertTrue($calendar->offName('shared-events'));
        $this->assertTrue($calendar->offName('special-days'));
        $this->assertFalse($calendar->offName('workflow'));
        $this->assertTrue($this->off(['projects'])->offName('workflow'));
        $this->assertFalse($this->off(['reservation'])->offName('shared-events'));
    }

    public function test_off_keys_lists_off_module_ids_and_raw_tool_words_for_the_menu(): void
    {
        $keys = $this->off(['radio', 'taskboard'])->offKeys();

        $this->assertContains('radio', $keys);
        $this->assertContains('taskboard', $keys);
        $this->assertContains('projects', $keys);
        $this->assertNotContains('reservation', $keys);
        $this->assertSame([], $this->off([])->offKeys());
    }

    public function test_small_tool_keys_work_without_a_registry_entry(): void
    {
        $service = $this->off(['radio', 'stopwatch']);

        $this->assertTrue($service->off('radio'));
        $this->assertTrue($service->off('stopwatch'));
        $this->assertFalse($service->off('calculator'));
    }

    public function test_off_css_survives_a_failing_cache_store(): void
    {
        Cache::shouldReceive('remember')->once()->andThrow(new RuntimeException('store down'));

        $this->assertStringContainsString('a[href$="/reservation"]', $this->off(['reservation'])->offCss());
    }
}
