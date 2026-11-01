<?php

namespace Tests\Feature\Models;

use App\Models\Authority;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthorityModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
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

    private function dept(string $code, array $overrides = []): Department
    {
        return Department::firstOrCreate(
            ['code' => $code],
            array_merge(['name' => $code, 'description' => $code], $overrides)
        );
    }

    public function test_sub_duty_cast_normalizes_scalar_to_boolean(): void
    {
        $authority = new Authority();

        $authority->sub_duty = 1;
        $this->assertTrue($authority->sub_duty);

        $authority->sub_duty = 0;
        $this->assertFalse($authority->sub_duty);

        $authority->sub_duty = true;
        $this->assertTrue($authority->sub_duty);
    }

    public function test_details_cast_decodes_json_array_to_php_array(): void
    {
        $authority = new Authority();
        $authority->details = ['duty' => 'manage operations', 'impact_score' => 'high'];

        $this->assertSame(['duty' => 'manage operations', 'impact_score' => 'high'], $authority->details);
    }

    public function test_details_cast_returns_null_for_unset_column(): void
    {
        $authority = new Authority();

        $this->assertNull($authority->details);
    }

    public function test_scope_search_with_null_search_applies_no_filter(): void
    {
        $baseline = Authority::count();
        Authority::create(['sub_duty' => false, 'details' => ['duty' => 'alpha']]);
        Authority::create(['sub_duty' => true, 'details' => ['duty' => 'beta']]);

        $this->assertSame($baseline + 2, Authority::search(null)->count());
    }

    public function test_scope_search_with_empty_string_applies_no_filter(): void
    {
        $baseline = Authority::count();
        Authority::create(['sub_duty' => false, 'details' => ['duty' => 'alpha']]);

        $this->assertSame($baseline + 1, Authority::search('')->count());
    }

    public function test_scope_search_matches_json_details_key_value(): void
    {
        $matched = Authority::create(['sub_duty' => false, 'details' => ['duty' => 'manage operations']]);
        $other = Authority::create(['sub_duty' => false, 'details' => ['duty' => 'clerical work']]);

        $ids = Authority::search('operations')->pluck('id')->all();

        $this->assertContains($matched->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_scope_search_matches_any_of_the_seven_details_keys(): void
    {
        $matched = Authority::create([
            'sub_duty' => false,
            'details' => ['repeat_frequency' => 'quarterly review'],
        ]);
        $other = Authority::create([
            'sub_duty' => false,
            'details' => ['duty' => 'unrelated'],
        ]);

        $ids = Authority::search('quarterly')->pluck('id')->all();

        $this->assertContains($matched->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_scope_search_matches_department_relation_fields(): void
    {
        $dept = $this->dept('TST1', ['name' => 'Logistics Hub', 'description' => 'supply chain']);
        $matched = Authority::create([
            'department_id' => $dept->code,
            'sub_duty' => false,
            'details' => ['duty' => 'unrelated duty text'],
        ]);
        $other = Authority::create([
            'sub_duty' => false,
            'details' => ['duty' => 'unrelated duty text'],
        ]);

        $ids = Authority::search('Logistics')->pluck('id')->all();

        $this->assertContains($matched->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_scope_search_matches_department_description_field(): void
    {
        $dept = $this->dept('TST2', ['name' => 'Generic', 'description' => 'special procurement pipeline']);
        $matched = Authority::create([
            'department_id' => $dept->code,
            'sub_duty' => false,
            'details' => ['duty' => 'plain duty'],
        ]);

        $ids = Authority::search('procurement')->pluck('id')->all();

        $this->assertContains($matched->id, $ids);
    }

    public function test_scope_search_excludes_rows_with_no_match_anywhere(): void
    {
        $unmatched = Authority::create([
            'sub_duty' => false,
            'details' => ['duty' => 'ordinary task', 'impact_score' => 'low'],
        ]);

        $ids = Authority::search('zzz-no-such-term-123')->pluck('id')->all();

        $this->assertNotContains($unmatched->id, $ids);
    }

    public function test_scope_search_returns_empty_when_table_has_no_rows(): void
    {
        Authority::whereNotNull('id')->delete();

        $this->assertSame(0, Authority::search('anything')->count());
    }

    public function test_fillable_allows_mass_assignment_of_core_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $dept = $this->dept('TST3');

        $authority = Authority::create([
            'department_id' => $dept->code,
            'user_id' => $user->id,
            'sub_duty' => true,
            'details' => ['duty' => 'approve'],
        ]);

        $this->assertSame($dept->code, $authority->fresh()->department_id);
        $this->assertSame($user->id, $authority->fresh()->user_id);
        $this->assertTrue($authority->fresh()->sub_duty);
        $this->assertSame(['duty' => 'approve'], $authority->fresh()->details);
    }

    public function test_cached_count_auto_invalidates_on_save(): void
    {
        $before = Authority::cachedCount();

        Authority::factory()->create();

        $this->assertSame($before + 1, Authority::cachedCount(), 'creating an authority must bump the version so cachedCount recomputes without a manual forget');
    }

    public function test_cached_departments_auto_invalidates_on_save(): void
    {
        $dept = $this->dept('ZZ-CD-' . uniqid());

        $this->assertFalse(Authority::cachedDepartments()->pluck('code')->contains($dept->code));

        Authority::factory()->create(['department_id' => $dept->code]);
        $this->assertTrue(Authority::cachedDepartments()->pluck('code')->contains($dept->code), 'creating an authority in a new dept must bump the version so cachedDepartments recomputes');
    }
}