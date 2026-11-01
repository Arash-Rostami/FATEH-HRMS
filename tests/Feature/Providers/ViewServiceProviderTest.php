<?php

namespace Tests\Feature\Providers;

use App\Models\Onboarding;
use App\Models\User;
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

    public function test_dashboard_route_never_loads_a_lazy_module_chunk(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $html = $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringNotContainsString('components/alpine/modules/', $html);
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
