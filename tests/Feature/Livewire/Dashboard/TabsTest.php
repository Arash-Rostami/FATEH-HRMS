<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tabs;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class TabsTest extends TestCase
{
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

    public function test_embed_query_param_hides_tabs_chrome_and_survives_rerender()
    {
        $user = User::factory()->create(['status' => 'active']);

        $tabs = Livewire::actingAs($user)
            ->withQueryParams(['embed' => '1'])
            ->test(Tabs::class);

        $this->assertTrue($tabs->instance()->embed);
        $tabs->assertDontSeeHtml('<aside');

        $tabs->call('navigateTab', 1)->assertDontSeeHtml('<aside');
    }

    public function test_default_tabs_view_keeps_chrome()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Tabs::class)
            ->assertSeeHtml('<aside');
    }

    public function test_peek_markup_is_wired_to_every_rail_icon(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Tabs::class)
            ->assertSeeHtml('x-data="peek"')
            ->assertSeeHtml('data-active="home"')
            ->assertSeeHtml('data-peek-card')
            ->assertSeeHtml('sandbox="allow-same-origin"')
            ->assertSeeHtml("show('post', \$el)")
            ->assertDontSeeHtml("show('reports', \$el)")
            ->assertSeeHtml("show('gallery', \$el)")
            ->assertSeeHtml("show('home', \$el)")
            ->assertSeeHtml("show('faqs', \$el)");
    }

    public function test_feed_rail_tile_keeps_the_primary_fill_and_gains_a_tiny_ai_sparkle_only_while_its_view_is_on(): void
    {
        $rail = file_get_contents(resource_path('views/components/dashboard/navbars/right.blade.php'));

        $this->assertStringContainsString("\$key === 'feed' && feedAiOn()", $rail);
        $this->assertStringContainsString('wire:key="feed-ai-rail-sparkle"', $rail);
        $this->assertStringContainsString('!text-[10px] absolute top-1.5 right-2 text-[color-mix(in_srgb,var(--ai-color)_55%,white)]', $rail);
        $this->assertStringContainsString('bg-[var(--md-sys-color-primary)]', $rail);
        $this->assertStringNotContainsString('bg-[var(--ai-bg)]', $rail);
    }

    public function test_legacy_reports_tab_url_redirects_to_knowledge_and_keeps_the_open_id()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)->withQueryParams(['tab' => 'reports'])->test(Tabs::class)
            ->assertRedirectToRoute('knowledge');

        Livewire::actingAs($user)->withQueryParams(['tab' => 'reports', 'open' => 7])->test(Tabs::class)
            ->assertRedirectToRoute('knowledge', ['open' => 7]);

        $this->assertArrayNotHasKey('reports', Livewire::actingAs($user)->withQueryParams(['tab' => 'home'])->test(Tabs::class)->instance()->tabs);
    }

    public function test_off_tab_is_absent_and_query_tab_falls_back_to_home_and_returns_when_on()
    {
        $user = User::factory()->create(['status' => 'active']);

        try {
            config(['app.off' => ['faq']]);
            $off = Livewire::actingAs($user)->withQueryParams(['tab' => 'faqs'])->test(Tabs::class);

            $this->assertArrayNotHasKey('faqs', $off->instance()->tabs);
            $off->assertSet('activeTab', 'home')->assertDontSeeHtml("setTab('faqs')");

            config(['app.off' => []]);
            $on = Livewire::actingAs($user)->withQueryParams(['tab' => 'faqs'])->test(Tabs::class);

            $this->assertArrayHasKey('faqs', $on->instance()->tabs);
            $on->assertSet('activeTab', 'faqs');
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_bottom_nav_splits_tabs_across_both_paging_pages()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Tabs::class)
            ->assertSeeHtml("setTab('home')")
            ->assertSeeHtml("setTab('faqs')");
    }
}