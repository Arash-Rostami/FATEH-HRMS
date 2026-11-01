<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Links;
use App\Livewire\Dashboard\Tab\Presentation\LinkPresenter;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class LinksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
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

    public function test_links_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'external', 'sequence' => 1, 'url_description' => 'ext', 'image_description' => 'ext', 'url_title' => 'ext']);
        Link::factory()->create(['link' => 'internal', 'sequence' => 1, 'url_description' => 'int', 'image_description' => 'int', 'url_title' => 'int']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertHasNoErrors();
    }

    public function test_external_links_source_returns_only_external_links_ordered_by_sequence()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $mine = Link::factory()->create(['link' => 'external', 'sequence' => 15]);
        Link::factory()->create(['link' => 'internal', 'sequence' => 5]);

        Livewire::withoutLazyLoading();
        $source = Livewire::actingAs($user)->test(Links::class)->instance()->externalLinksSource;

        $this->assertTrue($source->every(fn($l) => $l->link === 'external'));
        $sequences = $source->pluck('sequence')->all();
        $this->assertSame($sequences, collect($sequences)->sort()->values()->all());
        $this->assertContains($mine->id, $source->pluck('id')->all());
    }

    public function test_internal_links_source_returns_only_internal_links_ordered_by_sequence()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $mine = Link::factory()->create(['link' => 'internal', 'sequence' => 15]);
        Link::factory()->create(['link' => 'external', 'sequence' => 5]);

        Livewire::withoutLazyLoading();
        $source = Livewire::actingAs($user)->test(Links::class)->instance()->internalLinksSource;

        $this->assertTrue($source->every(fn($l) => $l->link === 'internal'));
        $sequences = $source->pluck('sequence')->all();
        $this->assertSame($sequences, collect($sequences)->sort()->values()->all());
        $this->assertContains($mine->id, $source->pluck('id')->all());
    }

    public function test_external_links_returns_full_source_list_when_open_is_null()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'external', 'sequence' => 99999]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)->instance();

        $this->assertNull($component->open);
        $this->assertSame(
            $component->externalLinksSource->pluck('id')->all(),
            $component->externalLinks->pluck('id')->all()
        );
    }

    public function test_internal_links_returns_full_source_list_when_open_is_null()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'internal', 'sequence' => 99999]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)->instance();

        $this->assertNull($component->open);
        $this->assertSame(
            $component->internalLinksSource->pluck('id')->all(),
            $component->internalLinks->pluck('id')->all()
        );
    }

    public function test_total_links_equals_external_plus_internal_source_counts()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'external', 'sequence' => 1]);
        Link::factory()->create(['link' => 'internal', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)->instance();

        $this->assertSame(
            $component->externalLinksSource->count() + $component->internalLinksSource->count(),
            $component->totalLinks
        );
    }

    public function test_external_links_filters_to_focused_record_when_open_targets_an_external_id()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $keep = Link::factory()->create(['link' => 'external', 'sequence' => 1]);
        Link::factory()->create(['link' => 'external', 'sequence' => 2]);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Links::class)
            ->set('open', $keep->id)
            ->instance()->externalLinks->pluck('id')->all();

        $this->assertSame([$keep->id], $ids);
    }

    public function test_internal_links_filters_to_focused_record_when_open_targets_an_internal_id()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $keep = Link::factory()->create(['link' => 'internal', 'sequence' => 1]);
        Link::factory()->create(['link' => 'internal', 'sequence' => 2]);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Links::class)
            ->set('open', $keep->id)
            ->instance()->internalLinks->pluck('id')->all();

        $this->assertSame([$keep->id], $ids);
    }

    public function test_external_links_is_empty_when_open_targets_an_internal_link_id()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'external', 'sequence' => 1]);
        $internal = Link::factory()->create(['link' => 'internal', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        $this->assertCount(0, Livewire::actingAs($user)->test(Links::class)
            ->set('open', $internal->id)
            ->instance()->externalLinks);
    }

    public function test_internal_links_is_empty_when_open_targets_an_external_link_id()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $external = Link::factory()->create(['link' => 'external', 'sequence' => 1]);
        Link::factory()->create(['link' => 'internal', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        $this->assertCount(0, Livewire::actingAs($user)->test(Links::class)
            ->set('open', $external->id)
            ->instance()->internalLinks);
    }

    public function test_is_focusing_returns_false_when_open_is_null()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $this->assertFalse(Livewire::actingAs($user)->test(Links::class)->instance()->isFocusing());
    }

    public function test_is_focusing_returns_true_when_open_is_set()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $link = Link::factory()->create(['link' => 'external', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        $this->assertTrue(Livewire::actingAs($user)->test(Links::class)
            ->set('open', $link->id)
            ->instance()->isFocusing());
    }

    public function test_clear_focus_resets_open_to_null_and_restores_full_list()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $a = Link::factory()->create(['link' => 'external', 'sequence' => 1]);
        Link::factory()->create(['link' => 'external', 'sequence' => 2]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)
            ->set('open', $a->id)
            ->call('clearFocus')
            ->assertSet('open', null)
            ->instance();

        $this->assertSame(
            $component->externalLinksSource->count(),
            $component->externalLinks->count()
        );
    }

    public function test_mount_with_open_query_param_dispatches_record_focus_event()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $link = Link::factory()->create(['link' => 'external', 'sequence' => 1]);

        Livewire::withQueryParams(['open' => $link->id])
            ->actingAs($user)
            ->test(Links::class)
            ->assertDispatched('record-focus', type: 'links', id: $link->id)
            ->assertSet('open', $link->id);
    }

    public function test_mount_without_open_does_not_dispatch_record_focus_event()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertNotDispatched('record-focus')
            ->assertSet('open', null);
    }

    public function test_url_description_renders_as_title_tooltip_on_card()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $description = 'Tooltip probe ' . uniqid();
        Link::factory()->create(['link' => 'external', 'sequence' => 1, 'url_description' => $description]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertSee('title="' . $description . '"', false);
    }

    public function test_card_falls_back_to_icon_image_when_no_banner_image()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $link = Link::factory()->create([
            'link' => 'external',
            'sequence' => 1,
            'image' => null,
            'icon' => 'link/icon/probe-' . uniqid() . '.png',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertSee($link->icon_url, false);
    }

    public function test_card_falls_back_to_generic_glyph_when_no_image_or_icon()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create([
            'link' => 'external',
            'sequence' => 1,
            'image' => null,
            'icon' => null,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertSee('>open_in_new<', false);
    }

    public function test_copy_button_renders_on_card()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'external', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertSee('کپی در کلیپ‌بورد');
    }

    public function test_search_filters_links_by_title()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $title = 'Findable Title ' . uniqid();
        $match = Link::factory()->create(['link' => 'external', 'sequence' => 1, 'url_title' => $title]);
        $other = Link::factory()->create(['link' => 'external', 'sequence' => 2, 'url_title' => 'Something else']);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Links::class)
            ->set('search', $title)
            ->instance()->externalLinks->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_search_filters_links_by_description()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $description = 'Findable Description ' . uniqid();
        $match = Link::factory()->create(['link' => 'internal', 'sequence' => 1, 'url_description' => $description]);
        Link::factory()->create(['link' => 'internal', 'sequence' => 2, 'url_description' => 'Something else']);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Links::class)
            ->set('search', $description)
            ->instance()->internalLinks->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
    }

    public function test_set_filter_internal_hides_external_section()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'external', 'sequence' => 1]);
        Link::factory()->create(['link' => 'internal', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)
            ->call('setFilter', 'internal')
            ->assertSet('activeFilter', 'internal');

        $this->assertCount(0, $component->instance()->externalLinks);
        $this->assertGreaterThan(0, $component->instance()->internalLinks->count());
    }

    public function test_set_filter_external_hides_internal_section()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create(['link' => 'external', 'sequence' => 1]);
        Link::factory()->create(['link' => 'internal', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)
            ->call('setFilter', 'external')
            ->assertSet('activeFilter', 'external');

        $this->assertCount(0, $component->instance()->internalLinks);
        $this->assertGreaterThan(0, $component->instance()->externalLinks->count());
    }

    public function test_set_filter_rejects_unknown_value()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Links::class)
            ->call('setFilter', 'bogus')
            ->assertSet('activeFilter', 'all');
    }

    public function test_reset_filters_clears_search_and_active_filter()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Links::class)
            ->set('search', 'something')
            ->call('setFilter', 'internal')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('activeFilter', 'all');
    }

    public function test_updated_search_clears_open_focus()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $link = Link::factory()->create(['link' => 'external', 'sequence' => 1]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Links::class)
            ->set('open', $link->id)
            ->set('search', 'anything')
            ->assertSet('open', null);
    }

    public function test_card_renders_picked_icon_as_third_tier_fallback()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create([
            'link' => 'external',
            'sequence' => 1,
            'image' => null,
            'icon' => null,
            'icon_description' => \App\Enums\LinkIcon::Hub->value,
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertSee('>' . \App\Enums\LinkIcon::Hub->value . '<', false)
            ->assertSee('title="' . \App\Enums\LinkIcon::Hub->getLabel() . '"', false);
    }

    public function test_card_falls_back_to_generic_glyph_when_icon_description_is_not_a_valid_picked_icon()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Link::factory()->create([
            'link' => 'external',
            'sequence' => 1,
            'image' => null,
            'icon' => null,
            'icon_description' => 'some legacy free-typed sentence',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Links::class)
            ->assertSee('>open_in_new<', false);
    }

    public function test_toggle_view_to_launch_sets_view_and_persists_session()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)
            ->call('toggleView', 'launch');

        $this->assertSame('launch', $component->instance()->view);
        $this->assertSame('launch', session('links_view_mode'));
    }

    public function test_toggle_view_ignores_invalid_value()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class)
            ->call('toggleView', 'bogus');

        $this->assertSame('rail', $component->instance()->view);
        $this->assertNull(session('links_view_mode'));
    }

    public function test_mount_restores_launch_view_from_session()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        session(['links_view_mode' => 'launch']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class);

        $this->assertSame('launch', $component->instance()->view);
    }

    public function test_mount_defaults_to_rail_when_session_unset()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Links::class);

        $this->assertSame('rail', $component->instance()->view);
    }

    public function test_launch_link_data_assigns_incrementing_hotkey_with_nine_cap()
    {
        $p = new LinkPresenter();
        $link = Link::factory()->make(['internal_url' => '', 'url' => 'https://ext.com', 'extra' => []]);

        $hk = 0;
        $d = $p->launchLinkData($link, 'external', '', $hk);
        $this->assertSame(1, $d['hotkey']);
        $this->assertSame(1, $hk);

        $hk = 8;
        $d = $p->launchLinkData($link, 'external', '', $hk);
        $this->assertSame(9, $d['hotkey']);

        $hk = 9;
        $d = $p->launchLinkData($link, 'external', '', $hk);
        $this->assertNull($d['hotkey']);

        $hk = 20;
        $d = $p->launchLinkData($link, 'external', '', $hk);
        $this->assertNull($d['hotkey']);
    }

    public function test_launch_link_data_sets_target_and_rel_per_internal_external_kind()
    {
        $p = new LinkPresenter();

        $ext = Link::factory()->make(['internal_url' => '', 'url' => 'https://ext.com', 'extra' => []]);
        $hk = 0;
        $dExt = $p->launchLinkData($ext, 'external', '', $hk);
        $this->assertSame('_blank', $dExt['target']);
        $this->assertSame('noopener noreferrer', $dExt['rel']);
        $this->assertSame('https://ext.com', $dExt['resolved']);
        $this->assertFalse($dExt['internal']);

        $int = Link::factory()->make(['internal_url' => '/inside/path', 'extra' => []]);
        $hk = 0;
        $dInt = $p->launchLinkData($int, 'internal', '', $hk);
        $this->assertSame('_self', $dInt['target']);
        $this->assertSame('', $dInt['rel']);
        $this->assertSame('/inside/path', $dInt['resolved']);
        $this->assertTrue($dInt['internal']);
    }
}
