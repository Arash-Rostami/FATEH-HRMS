<?php

namespace Tests\Feature\Enums;

use App\Enums\ResourceType;
use App\Models\ReservationPolicy;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResourceTypeEnumTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        ResourceType::forgetCache();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ResourceType::forgetCache();
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

    private function wipeDisplayRows(string $type): void
    {
        ReservationPolicy::where('resource_type', $type)->whereIn('key', ResourceType::DISPLAY_KEYS)->delete();
    }

    public function test_tryfrom_falls_back_to_legacy_values_when_db_has_no_display_rows(): void
    {
        $this->wipeDisplayRows('seat');
        ResourceType::forgetCache();

        $seat = ResourceType::tryFrom('seat');

        $this->assertNotNull($seat);
        $this->assertSame('میز کار', $seat->getLabel());
        $this->assertSame('primary', $seat->getColor());
        $this->assertSame('heroicon-o-computer-desktop', $seat->getIcon());
        $this->assertSame('desk', $seat->getMaterialIcon());
        $this->assertTrue($seat->isFullDay());
        $this->assertSame('🖥️', $seat->getEmoji());
    }

    public function test_db_row_overrides_legacy_fallback_for_same_type(): void
    {
        $this->wipeDisplayRows('seat');
        ReservationPolicy::insert([
            ['resource_type' => 'seat', 'key' => 'display_label', 'value' => json_encode('میز اختصاصی')],
            ['resource_type' => 'seat', 'key' => 'display_color', 'value' => json_encode('danger')],
            ['resource_type' => 'seat', 'key' => 'display_icon', 'value' => json_encode('heroicon-o-key')],
            ['resource_type' => 'seat', 'key' => 'display_material_icon', 'value' => json_encode('key')],
            ['resource_type' => 'seat', 'key' => 'is_full_day', 'value' => json_encode(false)],
        ]);
        ResourceType::forgetCache();

        $seat = ResourceType::tryFrom('seat');

        $this->assertSame('میز اختصاصی', $seat->getLabel());
        $this->assertSame('danger', $seat->getColor());
        $this->assertFalse($seat->isFullDay());
        $this->assertSame('🖥️', $seat->getEmoji(), 'emoji must still fall back to the legacy value even when other display keys are DB-backed');
    }

    public function test_tryfrom_returns_null_for_unknown_type(): void
    {
        $this->assertNull(ResourceType::tryFrom('does-not-exist'));
        $this->assertNull(ResourceType::tryFrom(null));
    }

    public function test_cases_includes_all_four_legacy_types_plus_any_custom_db_type(): void
    {
        ResourceType::forgetCache();
        $baselineCount = count(ResourceType::cases());

        ReservationPolicy::insert([
            ['resource_type' => 'projector', 'key' => 'display_label', 'value' => json_encode('پروژکتور')],
            ['resource_type' => 'projector', 'key' => 'display_color', 'value' => json_encode('gray')],
            ['resource_type' => 'projector', 'key' => 'display_icon', 'value' => json_encode('heroicon-o-video-camera')],
            ['resource_type' => 'projector', 'key' => 'display_material_icon', 'value' => json_encode('videocam')],
            ['resource_type' => 'projector', 'key' => 'is_full_day', 'value' => json_encode(false)],
        ]);
        ResourceType::forgetCache();

        $values = array_map(fn(ResourceType $t) => $t->value, ResourceType::cases());

        $this->assertContains('seat', $values);
        $this->assertContains('spot', $values);
        $this->assertContains('car', $values);
        $this->assertContains('meeting', $values);
        $this->assertContains('projector', $values);
        $this->assertCount($baselineCount + 1, $values, 'inserting one new custom type must grow the case list by exactly one, regardless of how many other custom types already exist in the db');
    }

    public function test_cases_excludes_resource_types_with_incomplete_display_rows(): void
    {
        ReservationPolicy::insert([
            ['resource_type' => 'orphan', 'key' => 'display_color', 'value' => json_encode('gray')],
        ]);
        ResourceType::forgetCache();

        $values = array_map(fn(ResourceType $t) => $t->value, ResourceType::cases());

        $this->assertNotContains('orphan', $values, 'a resource_type with no display_label row is not a real type yet');
    }

    public function test_pluck_maps_value_to_label(): void
    {
        $options = ResourceType::pluck();

        $this->assertSame('میز کار', $options['seat']);
        $this->assertSame('ملاقات', $options['meeting']);
    }

    public function test_tabs_returns_id_icon_and_label_for_every_case(): void
    {
        $tabs = ResourceType::tabs();
        $ids = array_column($tabs, 'id');

        $this->assertContains('seat', $ids);
        $this->assertContains('meeting', $ids);
        $seatTab = collect($tabs)->firstWhere('id', 'seat');
        $this->assertSame('desk', $seatTab['icon']);
        $this->assertSame('میز کار', $seatTab['label']);
    }

    public function test_forgetcache_busts_the_registry_so_subsequent_reads_see_new_rows(): void
    {
        ResourceType::tryFrom('seat'); // warms the cache
        ReservationPolicy::insert([
            ['resource_type' => 'locker', 'key' => 'display_label', 'value' => json_encode('کمد')],
            ['resource_type' => 'locker', 'key' => 'display_color', 'value' => json_encode('gray')],
            ['resource_type' => 'locker', 'key' => 'display_icon', 'value' => json_encode('heroicon-o-archive-box')],
            ['resource_type' => 'locker', 'key' => 'display_material_icon', 'value' => json_encode('archive')],
            ['resource_type' => 'locker', 'key' => 'is_full_day', 'value' => json_encode(true)],
        ]);

        $this->assertNull(ResourceType::tryFrom('locker'), 'stale cache from the warm-up call should still hide the new row');

        ResourceType::forgetCache();

        $this->assertNotNull(ResourceType::tryFrom('locker'));
    }

    public function test_is_full_day_is_true_for_legacy_types_except_meeting(): void
    {
        $this->assertTrue(ResourceType::tryFrom('seat')->isFullDay());
        $this->assertTrue(ResourceType::tryFrom('spot')->isFullDay());
        $this->assertTrue(ResourceType::tryFrom('car')->isFullDay());
        $this->assertFalse(ResourceType::tryFrom('meeting')->isFullDay());
    }
}
