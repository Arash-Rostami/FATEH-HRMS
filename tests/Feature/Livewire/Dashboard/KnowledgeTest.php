<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Knowledge\Main as Knowledge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        config(['app.off' => []]);
        Livewire::withoutLazyLoading();
    }

    protected function tearDown(): void
    {
        config(['app.off' => []]);
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

    private function user(): User
    {
        return User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
    }

    public function test_the_shell_starts_on_the_reports_pane_and_renders_both_pills(): void
    {
        Livewire::actingAs($this->user())->test(Knowledge::class)
            ->assertSet('activeTab', 'reports')
            ->assertSeeHtml('wire:key="knowledge-reports-pane"')
            ->assertDontSeeHtml('wire:key="knowledge-library-pane"')
            ->assertSeeHtml('data-tab-pill="reports"')
            ->assertSeeHtml('data-tab-pill="media"');
    }

    public function test_the_tab_query_param_opens_the_media_pane_and_switch_tab_moves_between_panes(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)->withQueryParams(['tab' => 'media'])->test(Knowledge::class)
            ->assertSet('activeTab', 'media')
            ->assertSeeHtml('wire:key="knowledge-library-pane"')
            ->assertDontSeeHtml('wire:key="knowledge-reports-pane"');

        Livewire::actingAs($user)->test(Knowledge::class)
            ->call('switchTab', 'media')
            ->assertSet('activeTab', 'media')
            ->assertSeeHtml('wire:key="knowledge-library-pane"')
            ->call('switchTab', 'reports')
            ->assertSet('activeTab', 'reports')
            ->assertSeeHtml('wire:key="knowledge-reports-pane"');
    }

    public function test_an_unknown_tab_value_falls_back_to_reports_and_is_ignored_by_switch_tab(): void
    {
        Livewire::actingAs($this->user())->withQueryParams(['tab' => 'nope'])->test(Knowledge::class)
            ->assertSet('activeTab', 'reports')
            ->call('switchTab', 'nope')
            ->assertSet('activeTab', 'reports');
    }

    public function test_the_media_view_off_clamps_the_shell_to_reports_and_hides_the_pill(): void
    {
        config(['app.off' => ['knowledge:media']]);

        Livewire::actingAs($this->user())->withQueryParams(['tab' => 'media'])->test(Knowledge::class)
            ->assertSet('activeTab', 'reports')
            ->assertSeeHtml('data-tab-pill="reports"')
            ->assertDontSeeHtml('data-tab-pill="media"')
            ->call('switchTab', 'media')
            ->assertSet('activeTab', 'reports')
            ->assertDontSeeHtml('wire:key="knowledge-library-pane"');
    }

    public function test_switch_tab_clears_the_stale_open_id_so_the_next_pane_never_focuses_it(): void
    {
        Livewire::actingAs($this->user())->withQueryParams(['open' => 5])->test(Knowledge::class)
            ->assertSet('open', 5)
            ->call('switchTab', 'media')
            ->assertSet('open', null)
            ->assertSet('activeTab', 'media');
    }

    public function test_the_whole_module_off_is_a_404_on_the_page_and_the_player_endpoints(): void
    {
        $user = $this->user();
        config(['app.off' => ['knowledge']]);

        $this->actingAs($user)->get(route('knowledge'))->assertNotFound();
        $this->actingAs($user)->postJson(route('media.progress'), ['items' => []])->assertNotFound();
        $this->actingAs($user)->postJson(route('media.notes'), [])->assertNotFound();
    }

    public function test_the_media_view_off_keeps_the_page_and_404s_only_the_player_endpoints(): void
    {
        $user = $this->user();
        config(['app.off' => ['knowledge:media']]);

        $this->actingAs($user)->get(route('knowledge'))->assertOk();
        $this->actingAs($user)->postJson(route('media.progress'), ['items' => []])->assertNotFound();
        $this->actingAs($user)->postJson(route('media.notes'), [])->assertNotFound();
    }

    public function test_the_old_media_url_redirects_permanently_and_keeps_the_deep_link_params(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/media')
            ->assertStatus(302)
            ->assertRedirect(route('knowledge', ['tab' => 'media']));

        $this->actingAs($user)->get('/media?open=3&t=9&junk=1')
            ->assertStatus(302)
            ->assertRedirect(route('knowledge', ['tab' => 'media', 'open' => 3, 't' => 9]));
    }

    public function test_the_media_route_name_is_gone_so_the_old_slot_is_not_reclaimed(): void
    {
        $this->assertFalse(app('router')->has('media'));
        $this->assertTrue(app('router')->has('knowledge'));
    }
}
