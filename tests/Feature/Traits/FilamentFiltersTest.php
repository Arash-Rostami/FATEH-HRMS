<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Models\Permission;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentFiltersTest extends TestCase
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_created_at_from_filter_hides_older_posts()
    {
        $admin = $this->developer();
        $old = Post::factory()->create(['user_id' => $admin->id, 'title' => '0-filter-old', 'created_at' => now()->subDays(10)]);
        $new = Post::factory()->create(['user_id' => $admin->id, 'title' => '0-filter-new', 'created_at' => now()->subDay()]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(5)->format('Y-m-d'), 'until' => null])
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_created_at_until_filter_hides_newer_posts()
    {
        $admin = $this->developer();
        $old = Post::factory()->create(['user_id' => $admin->id, 'title' => '0-filter-old', 'created_at' => now()->subDays(10)]);
        $new = Post::factory()->create(['user_id' => $admin->id, 'title' => '0-filter-new', 'created_at' => now()->subDay()]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => null, 'until' => now()->subDays(5)->format('Y-m-d')])
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$new]);
    }

    public function test_created_at_from_only_filter_works_without_until_key()
    {
        $admin = $this->developer();
        $old = Post::factory()->create(['user_id' => $admin->id, 'title' => '0-filter-old', 'created_at' => now()->subDays(10)]);
        $new = Post::factory()->create(['user_id' => $admin->id, 'title' => '0-filter-new', 'created_at' => now()->subDay()]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(5)->format('Y-m-d')])
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_created_at_filter_renders_jalali_picker_when_persian_dates_preference_defaults_on()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->assertSee('filamentJalaliFormComponent(', false);
    }

    public function test_created_at_filter_renders_native_gregorian_picker_when_persian_dates_preference_disabled()
    {
        $admin = $this->developer();
        $admin->update(['extra' => ['preferences' => ['persian_dates' => false]]]);

        Livewire::actingAs($admin->fresh())->test(ListPosts::class)
            ->assertSuccessful()
            ->assertDontSee('filamentJalaliFormComponent(', false);
    }
}