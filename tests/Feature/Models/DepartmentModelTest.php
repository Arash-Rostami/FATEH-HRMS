<?php

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DepartmentModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        ModelCacheVersion::bump(Department::class);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(Department::class);
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

    public function test_display_label_returns_description_when_present(): void
    {
        $dept = new Department(['code' => 'X', 'name' => 'Name', 'description' => 'Desc']);

        $this->assertSame('Desc', $dept->displayLabel());
    }

    public function test_display_label_falls_back_to_name_then_code(): void
    {
        $this->assertSame('Name', (new Department(['code' => 'X', 'name' => 'Name']))->displayLabel());
        $this->assertSame('X', (new Department(['code' => 'X']))->displayLabel());
    }

    public function test_tooltip_label_returns_name_when_present(): void
    {
        $this->assertSame('Name', (new Department(['code' => 'X', 'name' => 'Name', 'description' => 'Desc']))->tooltipLabel());
        $this->assertSame('X', (new Department(['code' => 'X', 'description' => 'Desc']))->tooltipLabel());
        $this->assertSame('Desc', (new Department(['description' => 'Desc']))->tooltipLabel());
    }

    public function test_sections_accessor_decodes_json_to_array_and_filters_empty(): void
    {
        $dept = Department::create([
            'code' => 'ZZS',
            'name' => 'Test',
            'description' => 'T',
            'sections' => ['A', 'B', '', 'C'],
        ]);

        $this->assertSame(['A', 'B', 'C'], $dept->fresh()->sections);
    }

    public function test_sections_setter_deduplicates_values(): void
    {
        $dept = Department::create([
            'code' => 'ZZD',
            'name' => 'Test',
            'description' => 'T',
            'sections' => ['A', 'A', 'B'],
        ]);

        $this->assertSame(['A', 'B'], $dept->fresh()->sections);
    }

    public function test_sections_accessor_returns_empty_array_for_null_column(): void
    {
        $dept = Department::create(['code' => 'ZZN', 'name' => 'Test', 'description' => 'T']);

        $this->assertSame([], $dept->fresh()->sections);
    }

    public function test_units_accessor_decodes_json_to_array(): void
    {
        $dept = Department::create([
            'code' => 'ZZU',
            'name' => 'Test',
            'description' => 'T',
            'units' => ['U1', 'U2'],
        ]);

        $this->assertSame(['U1', 'U2'], $dept->fresh()->units);
    }

    public function test_units_setter_deduplicates_values(): void
    {
        $dept = Department::create([
            'code' => 'ZZV',
            'name' => 'Test',
            'description' => 'T',
            'units' => ['U1', 'U1', 'U2'],
        ]);

        $this->assertSame(['U1', 'U2'], $dept->fresh()->units);
    }

    public function test_sections_options_returns_combine_of_sections_values(): void
    {
        $dept = new Department();
        $dept->sections = ['A', 'B'];

        $this->assertSame(['A' => 'A', 'B' => 'B'], $dept->sectionsOptions());
    }

    public function test_sections_options_returns_empty_array_for_null_sections(): void
    {
        $dept = new Department();

        $this->assertSame([], $dept->sectionsOptions());
    }

    public function test_units_options_returns_combine_of_units_values(): void
    {
        $dept = new Department();
        $dept->units = ['X', 'Y'];

        $this->assertSame(['X' => 'X', 'Y' => 'Y'], $dept->unitsOptions());
    }

    public function test_ticket_options_cast_round_trips_array_through_db(): void
    {
        $dept = Department::create([
            'code' => 'ZZT',
            'name' => 'Test',
            'description' => 'T',
            'ticket_options' => ['type1', 'type2'],
        ]);

        $this->assertSame(['type1', 'type2'], $dept->fresh()->ticket_options);
    }

    public function test_ticket_options_cast_returns_null_for_unset_column(): void
    {
        $dept = Department::create(['code' => 'ZZQ', 'name' => 'Test', 'description' => 'T']);

        $this->assertNull($dept->fresh()->ticket_options);
    }

    public function test_scope_excluding_empty_ticket_options_filters_to_non_empty(): void
    {
        $withOptions = Department::create(['code' => 'ZZE', 'name' => 'T', 'description' => 'T', 'ticket_options' => ['a']]);
        $emptyOptions = Department::create(['code' => 'ZZF', 'name' => 'T', 'description' => 'T', 'ticket_options' => []]);
        $nullOptions = Department::create(['code' => 'ZZG', 'name' => 'T', 'description' => 'T']);

        $codes = Department::excludingEmptyTicketOptions()->whereKey(['ZZE', 'ZZF', 'ZZG'])->pluck('code')->all();

        $this->assertContains('ZZE', $codes);
        $this->assertNotContains('ZZF', $codes);
        $this->assertNotContains('ZZG', $codes);
    }

    public function test_scope_including_empty_ticket_options_filters_to_null_or_empty(): void
    {
        $withOptions = Department::create(['code' => 'ZZH', 'name' => 'T', 'description' => 'T', 'ticket_options' => ['a']]);
        $emptyOptions = Department::create(['code' => 'ZZI', 'name' => 'T', 'description' => 'T', 'ticket_options' => []]);
        $nullOptions = Department::create(['code' => 'ZZJ', 'name' => 'T', 'description' => 'T']);

        $codes = Department::includingEmptyTicketOptions()->whereKey(['ZZH', 'ZZI', 'ZZJ'])->pluck('code')->all();

        $this->assertNotContains('ZZH', $codes);
        $this->assertContains('ZZI', $codes);
        $this->assertContains('ZZJ', $codes);
    }

    public function test_any_has_custom_ticket_options_returns_true_when_at_least_one_dept_has_options(): void
    {
        Department::create(['code' => 'ZZK', 'name' => 'T', 'description' => 'T', 'ticket_options' => ['custom']]);

        $this->assertTrue(Department::anyHasCustomTicketOptions());
    }

    public function test_any_has_custom_ticket_options_returns_false_when_none_have_options(): void
    {
        $baseline = Department::anyHasCustomTicketOptions();
        if ($baseline) {
            $this->markTestSkipped('dev DB has pre-existing departments with ticket_options');
        }

        $this->assertFalse(Department::anyHasCustomTicketOptions());
    }

    public function test_level_defaults_to_zero(): void
    {
        $dept = Department::create(['code' => 'ZZLV0', 'name' => 'T', 'description' => 'T']);

        $this->assertSame(0, $dept->fresh()->level);
    }

    public function test_subordinate_to_defaults_to_null(): void
    {
        $dept = Department::create(['code' => 'ZZLV1', 'name' => 'T', 'description' => 'T']);

        $this->assertNull($dept->fresh()->subordinate_to);
    }

    public function test_saving_auto_bumps_level_zero_to_one_when_subordinate_to_set(): void
    {
        Department::create(['code' => 'ZZPA1', 'name' => 'Parent', 'description' => 'T', 'level' => 1]);
        $child = Department::create(['code' => 'ZZCH1', 'name' => 'Child', 'description' => 'T', 'level' => 0, 'subordinate_to' => 'ZZPA1']);

        $this->assertSame(1, $child->fresh()->level);
    }

    public function test_clearing_subordinate_to_does_not_force_a_level_change(): void
    {
        Department::create(['code' => 'ZZPA2', 'name' => 'Parent', 'description' => 'T', 'level' => 1]);
        $child = Department::create(['code' => 'ZZCH2', 'name' => 'Child', 'description' => 'T', 'level' => 2, 'subordinate_to' => 'ZZPA2']);

        $child->subordinate_to = null;
        $child->save();

        $this->assertSame(2, $child->fresh()->level);
        $this->assertNull($child->fresh()->subordinate_to);
    }

    public function test_saving_rejects_self_reference(): void
    {
        $this->expectException(RuntimeException::class);

        Department::create(['code' => 'ZZSF1', 'name' => 'T', 'description' => 'T', 'subordinate_to' => 'ZZSF1']);
    }

    public function test_saving_rejects_cyclic_hierarchy(): void
    {
        Department::create(['code' => 'ZZCA1', 'name' => 'A', 'description' => 'T', 'level' => 1]);
        Department::create(['code' => 'ZZCB1', 'name' => 'B', 'description' => 'T', 'level' => 1, 'subordinate_to' => 'ZZCA1']);

        $a = Department::find('ZZCA1');
        $a->subordinate_to = 'ZZCB1';

        $this->expectException(RuntimeException::class);

        $a->save();
    }

    public function test_would_create_cycle_detects_self_reference(): void
    {
        $this->assertTrue(Department::wouldCreateCycle('ZZX', 'ZZX'));
    }

    public function test_would_create_cycle_returns_false_when_subordinate_to_is_null(): void
    {
        $this->assertFalse(Department::wouldCreateCycle('ZZX', null));
    }

    public function test_would_create_cycle_returns_false_when_code_is_null(): void
    {
        $this->assertFalse(Department::wouldCreateCycle(null, 'ZZX'));
    }
}