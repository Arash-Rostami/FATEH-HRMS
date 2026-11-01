<?php

namespace Tests\Feature\Traits;

use App\Livewire\Admin\ManagePreferences;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

class FilamentPreferencesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    public function test_mount_hydrates_form_from_stored_preferences(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['breadcrumbs' => true]],
        ]);

        $component = Livewire::actingAs($user)->test(ManagePreferences::class);

        $this->assertSame(true, $component->instance()->data['breadcrumbs']);
    }

    public function test_save_persists_form_state_into_user_extra_preferences(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['sidebar_collapsible' => false]],
        ]);

        $component = Livewire::actingAs($user)->test(ManagePreferences::class);
        $component->set('data.sidebar_collapsible', true);
        $component->call('save')->assertHasNoFormErrors();

        $this->assertSame(true, $user->fresh()->extra['preferences']['sidebar_collapsible']);
    }

    public function test_get_toggles_count_returns_count_of_preference_fields(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => []],
        ]);

        $component = Livewire::actingAs($user)->test(ManagePreferences::class);
        $instance = $component->instance();

        $reflection = new ReflectionClass($instance);
        $method = $reflection->getMethod('getPreferenceFields');
        $method->setAccessible(true);
        $fields = $method->invoke($instance);

        $this->assertSame(count($fields), $instance->getTogglesCount());
    }

    public function test_nav_dock_disables_conflicting_sidebar_toggles(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['nav_dock' => true]],
        ]);

        Livewire::actingAs($user)->test(ManagePreferences::class)
            ->assertFormFieldIsDisabled('sidebar_collapsible')
            ->assertFormFieldIsDisabled('sidebar_fully_collapsible')
            ->assertFormFieldIsDisabled('top_nav');
    }

    public function test_sidebar_toggles_stay_enabled_when_nav_dock_is_off(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['nav_dock' => false]],
        ]);

        Livewire::actingAs($user)->test(ManagePreferences::class)
            ->assertFormFieldIsEnabled('sidebar_collapsible')
            ->assertFormFieldIsEnabled('sidebar_fully_collapsible')
            ->assertFormFieldIsEnabled('top_nav');
    }

    public function test_topbar_pinned_disabled_when_topbar_is_off(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['topbar' => false]],
        ]);

        Livewire::actingAs($user)->test(ManagePreferences::class)
            ->assertFormFieldIsDisabled('topbar_pinned');
    }

    public function test_topbar_pinned_enabled_when_topbar_is_on(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['topbar' => true]],
        ]);

        Livewire::actingAs($user)->test(ManagePreferences::class)
            ->assertFormFieldIsEnabled('topbar_pinned');
    }

    public function test_save_preserves_disabled_fields_instead_of_wiping_them(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['topbar' => true, 'topbar_pinned' => false, 'breadcrumbs' => true]],
        ]);

        $component = Livewire::actingAs($user)->test(ManagePreferences::class);
        $component->set('data.topbar', false);
        $component->call('save')->assertHasNoFormErrors();

        $preferences = $user->fresh()->extra['preferences'];

        $this->assertSame(false, $preferences['topbar']);
        $this->assertSame(false, $preferences['topbar_pinned']);
    }

    public function test_sidebar_fully_collapsible_disabled_when_sidebar_collapsible_is_on(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['sidebar_collapsible' => true]],
        ]);

        Livewire::actingAs($user)->test(ManagePreferences::class)
            ->assertFormFieldIsDisabled('sidebar_fully_collapsible');
    }

    public function test_nav_dock_disabled_when_top_nav_is_on(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['top_nav' => true]],
        ]);

        Livewire::actingAs($user)->test(ManagePreferences::class)
            ->assertFormFieldIsDisabled('nav_dock');
    }

    public function test_user_menu_topbar_disabled_when_topbar_is_off(): void
    {
        $user = User::factory()->create([
            'extra' => ['preferences' => ['topbar' => false]],
        ]);

        Livewire::actingAs($user)->test(ManagePreferences::class)
            ->assertFormFieldIsDisabled('user_menu_topbar');
    }
}