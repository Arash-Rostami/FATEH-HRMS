<?php

namespace Tests\Feature\Models;

use App\Models\Link;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LinkModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    public function test_extra_cast_round_trips_array_through_db(): void
    {
        $link = Link::factory()->create(['extra' => ['1.1.1.1', '2.2.2.2']]);

        $this->assertSame(['1.1.1.1', '2.2.2.2'], $link->fresh()->extra);
    }

    public function test_is_extra_empty_in_database_returns_true_for_null_extra(): void
    {
        $link = Link::factory()->create(['extra' => null]);

        $this->assertTrue($link->isExtraEmptyInDatabase());
    }

    public function test_is_extra_empty_in_database_returns_true_for_empty_array_extra(): void
    {
        $link = Link::factory()->create(['extra' => []]);

        $this->assertTrue($link->isExtraEmptyInDatabase());
    }

    public function test_is_extra_empty_in_database_returns_false_for_populated_extra(): void
    {
        $link = Link::factory()->create(['extra' => ['1.1.1.1']]);

        $this->assertFalse($link->isExtraEmptyInDatabase());
    }

    public function test_scope_external_filters_by_link_external(): void
    {
        $external = Link::factory()->create(['link' => 'external']);
        $internal = Link::factory()->create(['link' => 'internal']);

        $ids = Link::external()->pluck('id')->all();
        $this->assertContains($external->id, $ids);
        $this->assertNotContains($internal->id, $ids);
    }

    public function test_scope_internal_filters_by_link_internal(): void
    {
        $external = Link::factory()->create(['link' => 'external']);
        $internal = Link::factory()->create(['link' => 'internal']);

        $ids = Link::internal()->pluck('id')->all();
        $this->assertContains($internal->id, $ids);
        $this->assertNotContains($external->id, $ids);
    }

    public function test_scope_sorted_orders_by_sequence_ascending(): void
    {
        $c = Link::factory()->create(['sequence' => 30, 'url' => 'https://c.test']);
        $a = Link::factory()->create(['sequence' => 10, 'url' => 'https://a.test']);
        $b = Link::factory()->create(['sequence' => 20, 'url' => 'https://b.test']);
        $ids = [$c->id, $a->id, $b->id];

        $ordered = Link::sorted()->whereIn('id', $ids)->pluck('url')->all();

        $this->assertSame(['https://a.test', 'https://b.test', 'https://c.test'], $ordered);
    }

    public function test_resolved_is_internal_returns_false_when_internal_url_empty(): void
    {
        $link = new Link(['internal_url' => null, 'extra' => []]);

        $this->assertFalse($link->resolvedIsInternal());
    }

    public function test_resolved_is_internal_returns_true_when_internal_url_set_and_extra_empty(): void
    {
        $link = new Link(['internal_url' => 'https://intranet.test', 'extra' => []]);

        $this->assertTrue($link->resolvedIsInternal());
    }

    public function test_resolved_is_internal_returns_true_when_internal_url_set_and_extra_null(): void
    {
        $link = new Link(['internal_url' => 'https://intranet.test', 'extra' => null]);

        $this->assertTrue($link->resolvedIsInternal());
    }

    public function test_resolved_is_internal_returns_false_when_ip_list_set_and_ip_null(): void
    {
        $link = new Link(['internal_url' => 'https://intranet.test', 'extra' => ['1.1.1.1']]);

        $this->assertFalse($link->resolvedIsInternal());
    }

    public function test_resolved_is_internal_returns_true_when_ip_matches_list(): void
    {
        $link = new Link(['internal_url' => 'https://intranet.test', 'extra' => ['1.1.1.1', '2.2.2.2']]);

        $this->assertTrue($link->resolvedIsInternal('2.2.2.2'));
    }

    public function test_resolved_is_internal_returns_false_when_ip_not_in_list(): void
    {
        $link = new Link(['internal_url' => 'https://intranet.test', 'extra' => ['1.1.1.1']]);

        $this->assertFalse($link->resolvedIsInternal('9.9.9.9'));
    }

    public function test_resolved_is_internal_trims_ips_and_drops_empty_entries(): void
    {
        $link = new Link(['internal_url' => 'https://intranet.test', 'extra' => ['  1.1.1.1  ', '']]);

        $this->assertTrue($link->resolvedIsInternal('1.1.1.1'));
        $this->assertFalse($link->resolvedIsInternal('9.9.9.9'));
    }

    public function test_resolved_is_internal_returns_true_when_all_extra_entries_are_blank(): void
    {
        $link = new Link(['internal_url' => 'https://intranet.test', 'extra' => ['  ', '']]);

        $this->assertTrue($link->resolvedIsInternal());
    }

    public function test_resolved_url_returns_internal_url_when_resolved_internal(): void
    {
        $link = new Link([
            'url' => 'https://external.test',
            'internal_url' => 'https://intranet.test',
            'extra' => [],
        ]);

        $this->assertSame('https://intranet.test', $link->resolvedUrl());
    }

    public function test_resolved_url_returns_url_when_not_internal_and_ip_not_allowed(): void
    {
        $link = new Link([
            'url' => 'https://external.test',
            'internal_url' => 'https://intranet.test',
            'extra' => ['1.1.1.1'],
        ]);

        $this->assertSame('https://external.test', $link->resolvedUrl('9.9.9.9'));
    }

    public function test_resolved_url_returns_empty_string_when_url_null_and_not_internal(): void
    {
        $link = new Link(['url' => null, 'internal_url' => null, 'extra' => []]);

        $this->assertSame('', $link->resolvedUrl());
    }

    public function test_image_url_accessor_returns_empty_string_for_null_image(): void
    {
        $link = new Link(['image' => null]);

        $this->assertSame('', $link->image_url);
    }

    public function test_image_url_accessor_returns_https_absolute_url_unchanged(): void
    {
        $link = new Link(['image' => 'https://cdn.test/img/x.png']);

        $this->assertSame('https://cdn.test/img/x.png', $link->image_url);
    }

    public function test_image_url_accessor_returns_http_absolute_url_unchanged(): void
    {
        $link = new Link(['image' => 'http://cdn.test/img/x.png']);

        $this->assertSame('http://cdn.test/img/x.png', $link->image_url);
    }

    public function test_image_url_accessor_returns_protocol_relative_url_unchanged(): void
    {
        $link = new Link(['image' => '//cdn.test/img/x.png']);

        $this->assertSame('//cdn.test/img/x.png', $link->image_url);
    }

    public function test_image_url_accessor_falls_back_to_asset_helper_for_nonexistent_path(): void
    {
        $link = new Link(['image' => 'nonexistent/link-image-' . uniqid() . '.png']);

        $this->assertStringContainsString('nonexistent/link-image-', $link->image_url);
    }

    public function test_saving_normalizes_all_blank_extra_to_empty_array(): void
    {
        $link = Link::factory()->create(['extra' => ['  ', '']]);

        $this->assertSame([], $link->fresh()->extra);
    }

    public function test_saving_trims_extra_entries_and_drops_empties_on_persist(): void
    {
        $link = Link::factory()->create(['extra' => ['  1.1.1.1  ', '', '2.2.2.2']]);

        $this->assertSame(['1.1.1.1', '2.2.2.2'], $link->fresh()->extra);
    }

    public function test_deleting_removes_image_and_icon_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('links/image.jpg', 'content');
        Storage::disk('public')->put('links/icon.png', 'content');
        $link = Link::factory()->create([
            'image' => 'links/image.jpg',
            'icon' => 'links/icon.png',
        ]);

        $link->delete();

        Storage::disk('public')->assertMissing('links/image.jpg');
        Storage::disk('public')->assertMissing('links/icon.png');
    }

    public function test_deleting_with_no_image_or_icon_does_not_error(): void
    {
        Storage::fake('public');
        $link = Link::factory()->create([
            'image' => null,
            'icon' => null,
        ]);

        $link->delete();

        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_cached_external_and_internal_are_scoped_and_auto_invalidate_on_delete(): void
    {
        $external = Link::factory()->create(['link' => 'external']);
        $internal = Link::factory()->create(['link' => 'internal']);

        $this->assertTrue(Link::cachedExternal()->pluck('id')->contains($external->id));
        $this->assertFalse(Link::cachedExternal()->pluck('id')->contains($internal->id));
        $this->assertTrue(Link::cachedInternal()->pluck('id')->contains($internal->id));

        $external->delete();
        $this->assertFalse(Link::cachedExternal()->pluck('id')->contains($external->id), 'deleting an external link must bump the version so cachedExternal recomputes without a manual forget');
    }
}