<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Ads\Main;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
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

    public function test_ads_listing_and_filter_smoke()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $active = Ad::factory()->create(['active' => true, 'position' => 'Backend Engineer']);
        $archived = Ad::factory()->create(['active' => false, 'position' => 'Legacy Role']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertHasNoErrors()
            ->assertSet('activeFilter', 'active')
            ->assertSet('ads', fn($ads) => $ads->contains('id', $active->id) && !$ads->contains('id', $archived->id))
            ->assertSet('stats.active', 1)
            ->assertSet('stats.archived', 1)
            ->call('setFilter', 'archived')
            ->assertHasNoErrors()
            ->assertSet('activeFilter', 'archived')
            ->assertSet('ads', fn($ads) => $ads->contains('id', $archived->id) && !$ads->contains('id', $active->id))
            ->call('setFilter', 'active')
            ->assertSet('activeFilter', 'active')
            ->set('search', 'Backend')
            ->assertHasNoErrors()
            ->assertSet('ads', fn($ads) => $ads->contains('id', $active->id))
            ->set('open', $active->id)
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->first()->is($active));
    }

    public function test_default_active_filter_shows_only_active_ads()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->count(2)->create(['active' => true]);
        Ad::factory()->create(['active' => false]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('activeFilter', 'active')
            ->assertSet('ads', fn($ads) => $ads->count() === 2);
    }

    public function test_set_filter_archived_shows_only_inactive_ads()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->create(['active' => true]);
        Ad::factory()->count(2)->create(['active' => false]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setFilter', 'archived')
            ->assertSet('activeFilter', 'archived')
            ->assertSet('ads', fn($ads) => $ads->count() === 2);
    }

    public function test_set_filter_active_returns_to_active_ads()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->count(2)->create(['active' => true]);
        Ad::factory()->create(['active' => false]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setFilter', 'archived')
            ->assertSet('activeFilter', 'archived')
            ->call('setFilter', 'active')
            ->assertSet('activeFilter', 'active')
            ->assertSet('ads', fn($ads) => $ads->count() === 2);
    }

    public function test_search_matches_position_column()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $match = Ad::factory()->create(['active' => true, 'position' => 'Backend Engineer']);
        $other = Ad::factory()->create(['active' => true, 'position' => 'Frontend Developer']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->count() === 2)
            ->set('search', 'Backend Engineer')
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $match->id) && !$ads->contains('id', $other->id));
    }

    public function test_search_matches_skill_column()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $match = Ad::factory()->create(['active' => true, 'skill' => 'Laravel Artisan']);
        $other = Ad::factory()->create(['active' => true, 'skill' => 'Vue Craftsmanship']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->count() === 2)
            ->set('search', 'Laravel Artisan')
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $match->id) && !$ads->contains('id', $other->id));
    }

    public function test_search_matches_certificate_column()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $match = Ad::factory()->create(['active' => true, 'certificate' => 'CCNP Routing']);
        $other = Ad::factory()->create(['active' => true, 'certificate' => 'PMP Management']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->count() === 2)
            ->set('search', 'CCNP Routing')
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $match->id) && !$ads->contains('id', $other->id));
    }

    public function test_search_respects_the_active_archived_filter()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $active = Ad::factory()->create(['active' => true, 'position' => 'Backend Engineer', 'skill' => 'PHP']);
        $archived = Ad::factory()->create(['active' => false, 'position' => 'Backend Engineer', 'skill' => 'PHP']);

        // regression guard: ungrouped orWhere(skill/certificate) let archived rows through the active filter
        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('search', 'Backend Engineer')
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $active->id) && !$ads->contains('id', $archived->id))
            ->set('activeFilter', 'archived')
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $archived->id) && !$ads->contains('id', $active->id));
    }

    public function test_search_with_falsy_zero_string_does_not_narrow_listing()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->count(2)->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->count() === 2)
            ->set('search', '0')
            ->assertHasNoErrors()
            ->assertSet('ads', fn($ads) => $ads->count() === 2);
    }

    public function test_updated_search_hook_exits_focus_mode()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $ad = Ad::factory()->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('open', $ad->id)
            ->assertSet('open', $ad->id)
            ->set('search', 'anything')
            ->assertSet('open', null);
    }

    public function test_focus_mode_pins_ads_to_single_record()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $target = Ad::factory()->create(['active' => true, 'position' => 'Target Role']);
        Ad::factory()->create(['active' => true, 'position' => 'Other Role']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->count() === 2)
            ->set('open', $target->id)
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->first()->is($target))
            ->assertSet('open', $target->id);
    }

    public function test_set_filter_exits_focus_mode()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $ad = Ad::factory()->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->set('open', $ad->id)
            ->assertSet('open', $ad->id)
            ->call('setFilter', 'archived')
            ->assertSet('open', null)
            ->assertSet('activeFilter', 'archived');
    }

    public function test_stats_active_count_matches_database()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->count(3)->create(['active' => true]);
        Ad::factory()->create(['active' => false]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('stats.active', Ad::active()->count())
            ->assertSet('stats.active', 3);
    }

    public function test_stats_archived_count_matches_database()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->create(['active' => true]);
        Ad::factory()->count(4)->create(['active' => false]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('stats.archived', Ad::inactive()->count())
            ->assertSet('stats.archived', 4);
    }

    public function test_empty_state_when_no_ads_exist()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertHasNoErrors()
            ->assertSet('ads', fn($ads) => $ads->isEmpty())
            ->assertSet('stats.active', 0)
            ->assertSet('stats.archived', 0)
            ->call('setFilter', 'archived')
            ->assertSet('ads', fn($ads) => $ads->isEmpty())
            ->set('search', 'anything')
            ->assertSet('ads', fn($ads) => $ads->isEmpty())
            ->assertSeeHtml('wire:key="ads-empty"');
    }

    public function test_focus_mode_bypasses_active_filter_to_pin_archived_record()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $active = Ad::factory()->create(['active' => true, 'position' => 'Active Role']);
        $archived = Ad::factory()->create(['active' => false, 'position' => 'Archived Role']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->contains('id', $active->id) && !$ads->contains('id', $archived->id))
            ->set('open', $archived->id)
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $archived->id))
            ->assertSet('activeFilter', 'active');
    }

    public function test_focus_mode_bypasses_search_query()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $target = Ad::factory()->create(['active' => true, 'position' => 'Target Role']);
        $other = Ad::factory()->create(['active' => true, 'position' => 'Other Role']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->count() === 2)
            ->set('search', 'Target Role')
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $target->id) && !$ads->contains('id', $other->id))
            ->set('open', $other->id)
            ->assertSet('ads', fn($ads) => $ads->count() === 1 && $ads->contains('id', $other->id) && !$ads->contains('id', $target->id));
    }

    public function test_search_with_no_matching_term_returns_empty_collection()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->count(2)->create(['active' => true]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('ads', fn($ads) => $ads->count() === 2)
            ->set('search', 'ZZZNoMatchAnywhere')
            ->assertHasNoErrors()
            ->assertSet('ads', fn($ads) => $ads->isEmpty());
    }

    public function test_stats_remain_constant_across_filter_changes()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->count(2)->create(['active' => true]);
        Ad::factory()->count(3)->create(['active' => false]);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSet('stats.active', 2)
            ->assertSet('stats.archived', 3)
            ->call('setFilter', 'archived')
            ->assertSet('stats.active', 2)
            ->assertSet('stats.archived', 3)
            ->call('setFilter', 'active')
            ->assertSet('stats.active', 2)
            ->assertSet('stats.archived', 3);
    }

    public function test_mount_with_open_query_dispatches_record_focus_event_with_ad_type()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        $ad = Ad::factory()->create(['active' => true]);

        Livewire::withQueryParams(['open' => $ad->id])
            ->actingAs($user)
            ->test(Main::class)
            ->assertDispatched('record-focus', type: 'ad', id: $ad->id)
            ->assertSet('open', $ad->id);
    }

    public function test_freshness_chip_only_wraps_postings_under_48h()
    {
        $user = User::factory()->create(['status' => 'active']);
        Ad::query()->delete();
        Ad::factory()->create(['active' => true, 'position' => 'Fresh Role', 'created_at' => now()->subHours(2)]);
        Ad::factory()->create(['active' => true, 'position' => 'Stale Role', 'created_at' => now()->subDays(3)]);

        $testable = Livewire::actingAs($user)->test(Main::class);
        $testable->assertSeeInOrder(['ثبت‌شده در ۴۸ ساعت گذشته', 'Fresh Role', 'Stale Role']);
        $this->assertSame(1, substr_count($testable->html(), 'title="ثبت‌شده در ۴۸ ساعت گذشته"'),
            'the 48h freshness star must render once — for the fresh ad only');
    }
}