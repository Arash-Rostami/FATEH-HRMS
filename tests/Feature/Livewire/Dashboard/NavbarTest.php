<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\PresenceStatus;
use App\Livewire\Dashboard\Navbar\Actions\FetchWeatherAction;
use App\Livewire\Dashboard\Navbar\CommandPalette;
use App\Livewire\Dashboard\Navbar\QuickSettings;
use App\Livewire\Dashboard\Navbar\StatusSwitcher;
use App\Livewire\Dashboard\Navbar\Weather;
use App\Models\Onboarding;
use App\Models\Profile;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class NavbarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
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

    public function test_status_switcher_changes_presence()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(StatusSwitcher::class)
            ->call('changeStatus', 'busy')
            ->assertHasNoErrors();

        $this->assertSame('busy', $user->refresh()->presence->value);
    }

    public function test_quick_settings_focus_mode()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(QuickSettings::class)
            ->call('activateFocusMode', null)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'presence' => 'busy']);
    }

    public function test_weather_renders_without_network()
    {
        config(['services.openweather.keys' => '']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Weather::class)
            ->assertHasNoErrors();
    }

    public function test_command_palette_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->assertHasNoErrors()
            ->call('toggleMode')
            ->assertHasNoErrors();
    }

    public function test_hamburger_menu_x_data_attribute_is_well_formed_html()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $html = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            "/<div x-data='menu\(\{[^']*\}\)'\s+id=\"navbar\"/",
            $html,
            'x-data attribute must stay single-quoted and unbroken so the following id="navbar" attribute is not swallowed by a stray literal double-quote from @json().'
        );
    }

    public function test_status_switcher_mounts_with_current_presence_value()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($user)
            ->test(StatusSwitcher::class)
            ->assertSet('status', 'remote');
    }

    public function test_status_switcher_dispatches_status_switcher_updated_event_on_change()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(StatusSwitcher::class)
            ->call('changeStatus', 'busy')
            ->assertDispatched('statusSwitcher-updated', status: 'busy');
    }

    public function test_status_switcher_rejects_invalid_presence_value_without_changing_state()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(StatusSwitcher::class)
            ->call('changeStatus', 'not-a-status')
            ->assertHasNoErrors()
            ->assertNotDispatched('statusSwitcher-updated');

        $this->assertSame('onsite', $user->refresh()->presence->value);
    }

    public function test_status_switcher_can_set_each_presence_enum_case()
    {
        foreach (PresenceStatus::cases() as $case) {
            $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

            Livewire::actingAs($user)
                ->test(StatusSwitcher::class)
                ->call('changeStatus', $case->value)
                ->assertHasNoErrors();

            $this->assertSame($case->value, $user->refresh()->presence->value, "Presence case {$case->value} not reachable via changeStatus.");
        }
    }

    public function test_status_switcher_updates_local_status_from_event_listener()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(StatusSwitcher::class)
            ->dispatch('statusSwitcher-updated', status: 'angry')
            ->assertSet('status', 'angry');
    }

    public function test_quick_settings_disabling_focus_mode_sets_presence_to_onsite()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'busy']);

        Livewire::actingAs($user)
            ->test(QuickSettings::class)
            ->call('deactivateFocusMode', null)
            ->assertHasNoErrors();

        $this->assertSame('onsite', $user->refresh()->presence->value);
    }

    public function test_manual_status_change_clears_the_focus_marker_so_a_later_busy_is_not_focus()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(User::INDEFINITE_FOCUS_MINUTES)]);

        Livewire::actingAs($user)->test(StatusSwitcher::class)->call('changeStatus', 'remote');
        Livewire::actingAs($user)->test(StatusSwitcher::class)->call('changeStatus', 'busy');

        $fresh = $user->refresh();
        $this->assertNull($fresh->focus_until);
        $this->assertFalse($fresh->isFocusing());
    }

    public function test_quick_settings_enabling_focus_mode_dispatches_status_switcher_updated_with_busy()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(QuickSettings::class)
            ->call('activateFocusMode', null)
            ->assertDispatched('statusSwitcher-updated', status: 'busy');
    }

    public function test_quick_settings_disabling_focus_mode_dispatches_status_switcher_updated_with_onsite()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'busy']);

        Livewire::actingAs($user)
            ->test(QuickSettings::class)
            ->call('deactivateFocusMode', null)
            ->assertDispatched('statusSwitcher-updated', status: 'onsite');
    }

    public function test_command_palette_toggle_mode_flips_between_navigate_and_content()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->assertSet('mode', 'navigate')
            ->call('toggleMode')
            ->assertSet('mode', 'content')
            ->call('toggleMode')
            ->assertSet('mode', 'navigate');
    }

    public function test_command_palette_toggle_mode_re_runs_current_query_under_new_engine()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $component = Livewire::actingAs($user)->test(CommandPalette::class);
        $component->set('query', 'home');
        $this->assertNotEmpty($component->instance()->results);

        $component->call('toggleMode')
            ->assertSet('mode', 'content')
            ->assertSet('query', 'home');
    }

    public function test_command_palette_query_below_two_characters_yields_no_results()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->set('query', 'a')
            ->assertSet('results', []);
    }

    public function test_command_palette_navigate_mode_search_returns_home_tab_for_home_query()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $component = Livewire::actingAs($user)->test(CommandPalette::class);
        $component->set('query', 'home');

        $actions = array_column($component->instance()->results, 'action');
        $this->assertContains('tab:home', $actions);
    }

    public function test_command_palette_navigate_mode_drops_an_off_module_and_restores_it_when_on()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $component = Livewire::actingAs($user)->test(CommandPalette::class);

        try {
            config(['app.off' => ['calendar']]);
            $component->set('query', 'تقویم');
            $this->assertNotContains('tab:calendar', array_column($component->instance()->results, 'action'));

            config(['app.off' => []]);
            $component->set('query', 'تقو');
            $this->assertContains('tab:calendar', array_column($component->instance()->results, 'action'));
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_command_palette_content_mode_search_runs_without_error_for_unmatched_query()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $component = Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->call('toggleMode')
            ->assertSet('mode', 'content')
            ->set('query', 'zzz-no-such-term-999')
            ->assertHasNoErrors()
            ->assertSet('results', []);

        $this->assertStringContainsString('wire:key="palette-results-empty"', $component->html());
    }

    public function test_command_palette_search_all_history_flips_flag_and_re_runs_query(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $token = 'zzcp' . substr(uniqid(), -8);

        $recent = Ticket::factory()->create(['requester_id' => $user->id, 'request_subject' => $token]);
        $old = Ticket::factory()->create(['requester_id' => $user->id, 'request_subject' => $token]);
        Ticket::whereKey($old->id)->update(['created_at' => now()->subMonths(8)]);

        $component = Livewire::actingAs($user)->test(CommandPalette::class);
        $component->call('toggleMode')->set('query', $token);

        $ids = array_column($component->instance()->results, 'id');
        $this->assertContains($recent->id, $ids);
        $this->assertNotContains($old->id, $ids);

        $component->call('searchAllHistory')->assertSet('allHistory', true);

        $ids = array_column($component->instance()->results, 'id');
        $this->assertContains($recent->id, $ids);
        $this->assertContains($old->id, $ids);
    }

    public function test_command_palette_toggle_mode_resets_all_history_back_to_false(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->call('toggleMode')
            ->call('searchAllHistory')
            ->assertSet('allHistory', true)
            ->call('toggleMode')
            ->assertSet('mode', 'navigate')
            ->assertSet('allHistory', false);
    }

    public function test_command_palette_content_mode_chip_visibility_follows_all_history_state(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $token = 'zzcp' . substr(uniqid(), -8);
        Ticket::factory()->create(['requester_id' => $user->id, 'request_subject' => $token]);

        $component = Livewire::actingAs($user)->test(CommandPalette::class);
        $component->assertDontSee('جستجو در کل تاریخ فعال است');

        $component->call('toggleMode')
            ->set('query', $token)
            ->assertSee('جستجو در کل تاریخ (فراتر از ۶ ماه اخیر)')
            ->assertDontSee('جستجو در کل تاریخ فعال است');

        $component->call('searchAllHistory')
            ->assertDontSee('جستجو در کل تاریخ (فراتر از ۶ ماه اخیر)')
            ->assertSee('جستجو در کل تاریخ فعال است');
    }

    public function test_command_palette_select_result_with_event_action_dispatches_event_and_resets_palette()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->set('query', 'home')
            ->call('selectResult', 'event:logout')
            ->assertDispatched('logout')
            ->assertDispatched('close-command-palette')
            ->assertSet('query', '')
            ->assertSet('results', []);
    }

    public function test_command_palette_select_result_dispatches_add_recent_search_when_result_matches()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->set('query', 'logout')
            ->call('selectResult', 'event:logout')
            ->assertDispatched('add-recent-search');
    }

    public function test_command_palette_select_result_with_unknown_action_type_leaves_state_unchanged()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->set('query', 'home')
            ->call('selectResult', 'unknown:thing')
            ->assertHasNoErrors()
            ->assertSet('query', 'home');
    }

    public function test_command_palette_select_result_without_colon_separator_is_ignored()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(CommandPalette::class)
            ->set('query', 'home')
            ->call('selectResult', 'noseparator')
            ->assertHasNoErrors()
            ->assertSet('query', 'home');
    }

    public function test_fetch_weather_action_returns_default_payload_when_no_api_keys_configured()
    {
        config(['services.openweather.keys' => '']);

        $payload = (new FetchWeatherAction())->execute();

        $this->assertSame(['weather' => '', 'temperature' => 'N/A', 'description' => ''], $payload);
    }

    public function test_fetch_weather_action_returns_cached_payload_without_invoking_network()
    {
        config(['services.openweather.keys' => '', 'services.openweather.city' => 'Tehran']);
        $cached = ['weather' => 'Clear', 'temperature' => 21, 'description' => 'clear sky'];
        Cache::flexible('weather.tehran', [21600, 21600], fn() => $cached);

        $payload = (new FetchWeatherAction())->execute();

        $this->assertSame($cached, $payload);
    }

    public function test_fetch_weather_action_preserves_stale_cache_when_deferred_refresh_fails()
    {
        config(['services.openweather.keys' => 'fake-key', 'services.openweather.city' => 'Tehran']);

        $staleValue = ['weather' => 'Clear', 'temperature' => 21, 'description' => 'clear sky'];
        Cache::put('weather.tehran', $staleValue, 21600);
        Cache::put('illuminate:cache:flexible:created:weather.tehran', now()->subSeconds(15000)->getTimestamp(), 21600);

        $action = new class extends FetchWeatherAction {
            protected function fetch(string $city): array
            {
                throw new \RuntimeException('Simulated network failure.');
            }
        };

        $payload = $action->execute();
        $this->assertSame($staleValue, $payload);

        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();

        $this->assertSame($staleValue, Cache::get('weather.tehran'));
    }

    public function test_fetch_weather_action_sanitizes_api_key_out_of_thrown_exception_message()
    {
        config([
            'services.openweather.keys' => 'super-secret-key-12345',
            'services.openweather.city' => 'Tehran',
            'services.openweather.url' => 'http://127.0.0.1:1/weather',
        ]);

        $action = new class extends FetchWeatherAction {
            public function callFetch(string $city): array
            {
                return $this->fetch($city);
            }
        };

        try {
            $action->callFetch('Tehran');
            $this->fail('Expected a RuntimeException from the unreachable weather endpoint.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('super-secret-key-12345', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
    }

    public function test_fetch_weather_action_returns_default_when_cold_start_fetch_throws()
    {
        config(['services.openweather.keys' => 'fake-key', 'services.openweather.city' => 'Tehran']);

        $action = new class extends FetchWeatherAction {
            protected function fetch(string $city): array
            {
                throw new \RuntimeException('Simulated network failure.');
            }
        };

        $payload = $action->execute();

        $this->assertSame(['weather' => '', 'temperature' => 'N/A', 'description' => ''], $payload);
    }

    public function test_weather_computed_returns_default_payload_without_network()
    {
        config(['services.openweather.keys' => '']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $payload = Livewire::actingAs($user)->test(Weather::class)->instance()->weatherData;

        $this->assertSame(['weather' => '', 'temperature' => 'N/A', 'description' => ''], $payload);
    }

    public function test_account_dropdown_profile_link_uses_tab_query_key_not_active_tab(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('/profile?tab=info', $html);
        $this->assertStringNotContainsString('/profile?activeTab=info', $html);
    }

    private function onboardingLinkHtml(string $html): string
    {
        preg_match('/<a[^>]*tab=onboarding[\s\S]*?<\/a>/', $html, $matches);

        return $matches[0] ?? '';
    }

    private function profileLinkHtml(string $html): string
    {
        preg_match('/<a[^>]*tab=info[\s\S]*?<\/a>/', $html, $matches);

        return $matches[0] ?? '';
    }

    public function test_account_dropdown_shows_profile_completion_percentage_below_100(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->profileLinkHtml($html);

        $this->assertStringContainsString('account-profile-completion-badge', $link);
        $this->assertStringContainsString('٪', $link);
    }

    public function test_account_dropdown_hides_profile_completion_badge_when_profile_is_complete(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'id_card_number' => '0012345678',
            'field' => 'نرم‌افزار',
            'address' => 'تهران',
            'insurance' => 'بیمه',
            'emergency_phone' => '09120000000',
        ]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->profileLinkHtml($html);

        $this->assertStringNotContainsString('account-profile-completion-badge', $link);
    }

    private function releaseTriggerHtml(string $html): string
    {
        preg_match('/w-full flex items-center gap-3 px-3 py-2\.5 rounded-xl text-sm font-medium hover:bg-\[var\(--md-sys-color-surface-container-high\)\] transition-colors group">[\s\S]*?new_releases[\s\S]*?<\/button>/', $html, $matches);

        return $matches[0] ?? '';
    }

    public function test_account_dropdown_shows_onboarding_accent_for_own_active_recent_exclusive_record(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => true]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->onboardingLinkHtml($html);

        $this->assertStringContainsString('animate-pulse', $link);
        $this->assertStringContainsString('محتوای آنبوردینگ اختصاصی شما آماده است', $link);
    }

    public function test_account_dropdown_hides_onboarding_accent_when_no_onboarding_record_exists(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->onboardingLinkHtml($html);

        $this->assertStringNotContainsString('animate-pulse', $link);
        $this->assertStringNotContainsString('محتوای آنبوردینگ اختصاصی شما آماده است', $link);
    }

    public function test_account_dropdown_hides_onboarding_accent_when_only_generic_default_record_exists(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Onboarding::factory()->create(['user_id' => null, 'is_active' => true]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->onboardingLinkHtml($html);

        $this->assertStringNotContainsString('animate-pulse', $link);
        $this->assertStringNotContainsString('محتوای آنبوردینگ اختصاصی شما آماده است', $link);
    }

    public function test_account_dropdown_hides_onboarding_accent_when_exclusive_record_is_inactive(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->onboardingLinkHtml($html);

        $this->assertStringNotContainsString('animate-pulse', $link);
        $this->assertStringNotContainsString('محتوای آنبوردینگ اختصاصی شما آماده است', $link);
    }

    public function test_account_dropdown_hides_onboarding_accent_when_updated_at_is_null(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $onboarding = Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        Onboarding::whereKey($onboarding->id)->update(['updated_at' => null]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->onboardingLinkHtml($html);

        $this->assertStringNotContainsString('animate-pulse', $link);
        $this->assertStringNotContainsString('محتوای آنبوردینگ اختصاصی شما آماده است', $link);
    }

    public function test_account_dropdown_hides_onboarding_accent_when_exclusive_record_older_than_seven_days(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $onboarding = Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        Onboarding::whereKey($onboarding->id)->update(['updated_at' => now()->subDays(8)]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $link = $this->onboardingLinkHtml($html);

        $this->assertStringNotContainsString('animate-pulse', $link);
        $this->assertStringNotContainsString('محتوای آنبوردینگ اختصاصی شما آماده است', $link);
    }

    public function test_account_dropdown_onboarding_accent_cache_is_isolated_per_user(): void
    {
        $highlightedUser = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $plainUser = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Onboarding::factory()->create(['user_id' => $highlightedUser->id, 'is_active' => true]);

        $highlightedHtml = $this->actingAs($highlightedUser)->get(route('dashboard'))->getContent();
        $plainHtml = $this->actingAs($plainUser)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('animate-pulse', $this->onboardingLinkHtml($highlightedHtml));
        $this->assertStringNotContainsString('animate-pulse', $this->onboardingLinkHtml($plainHtml));
    }

    public function test_release_trigger_accent_falls_back_to_app_update_is_recent_when_no_show_prop_passed(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        config(['app.last_update' => now()->subDays(3)->toDateTimeString()]);
        $recentHtml = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $this->assertStringContainsString('animate-pulse', $this->releaseTriggerHtml($recentHtml));

        config(['app.last_update' => now()->subDays(10)->toDateTimeString()]);
        $oldHtml = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $this->assertStringNotContainsString('animate-pulse', $this->releaseTriggerHtml($oldHtml));
    }

    public function test_account_dropdown_onboarding_accent_cache_prevents_second_query_on_repeated_render(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => true]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('dashboard'));
        $firstRenderOnboardingQueries = collect(DB::getQueryLog())
            ->filter(fn($q) => str_contains($q['query'], 'onboardings'))
            ->count();

        DB::flushQueryLog();
        $this->actingAs($user)->get(route('dashboard'));
        $secondRenderOnboardingQueries = collect(DB::getQueryLog())
            ->filter(fn($q) => str_contains($q['query'], 'onboardings'))
            ->count();
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $firstRenderOnboardingQueries, 'First render should query the onboardings table on a cache miss.');
        $this->assertSame(0, $secondRenderOnboardingQueries, 'Second render within the cache window must not hit the onboardings table.');
    }
}