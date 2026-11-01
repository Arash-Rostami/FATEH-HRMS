<?php

namespace Tests\Feature\Database;

use App\Enums\SkillIcon;
use App\Models\Skill;
use Database\Seeders\SkillCatalogSeeder;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class SkillCatalogSeederTest extends TestCase
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

    private function catalog(): array
    {
        $method = new ReflectionMethod(SkillCatalogSeeder::class, 'catalog');
        $method->setAccessible(true);

        return $method->invoke(new SkillCatalogSeeder());
    }

    public function test_catalog_has_at_least_two_hundred_skills_across_multiple_categories()
    {
        $catalog = $this->catalog();
        $total = array_sum(array_map('count', $catalog));

        $this->assertGreaterThanOrEqual(200, $total);
        $this->assertGreaterThanOrEqual(10, count($catalog));
    }

    public function test_catalog_has_no_duplicate_skill_names()
    {
        $names = collect($this->catalog())->flatten(1)->map(fn (array $row) => $row[0]);

        $this->assertSame($names->count(), $names->unique()->count(), 'every catalog skill name must be unique');
    }

    public function test_catalog_every_icon_is_a_valid_skill_icon_enum_case()
    {
        $invalid = collect($this->catalog())
            ->flatten(1)
            ->reject(fn (array $row) => SkillIcon::tryFrom($row[2]) !== null)
            ->map(fn (array $row) => $row[0] . ' => ' . $row[2]);

        $this->assertTrue($invalid->isEmpty(), 'invalid icons: ' . $invalid->implode(', '));
    }

    public function test_catalog_every_row_has_non_empty_name_name_en_and_category()
    {
        foreach ($this->catalog() as $category => $skills) {
            $this->assertNotSame('', trim($category));

            foreach ($skills as [$name, $nameEn, $icon]) {
                $this->assertNotSame('', trim($name));
                $this->assertNotSame('', trim($nameEn));
                $this->assertNotSame('', trim($icon));
            }
        }
    }

    public function test_run_persists_every_catalog_row_via_real_skill_model_and_is_idempotent_on_rerun()
    {
        $catalog = $this->catalog();
        $catalogNames = collect($catalog)->flatten(1)->pluck(0);
        $total = array_sum(array_map('count', $catalog));
        [$firstCategory, $firstSkills] = [array_key_first($catalog), reset($catalog)];
        [$firstName, $firstNameEn, $firstIcon] = $firstSkills[0];

        // A ghost row and an inactive draft row, unrelated to the catalog, exercised
        // alongside the catalog rows to prove the seeder only ever touches its own
        // catalog names and never mutates other Skill row types.
        $ghost = Skill::create(['name' => 'Ghost Sentinel Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => true, 'search_count' => 3]);
        $draft = Skill::create(['name' => 'Draft Sentinel Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);

        (new SkillCatalogSeeder())->run();

        // Scoped to catalog names, not Skill::count(), so this stays correct against a
        // shared dev DB that may already hold unrelated rows (e.g. ghost/proposal skills).
        $countAfterFirstRun = Skill::whereIn('name', $catalogNames)->count();
        $this->assertSame($total, $countAfterFirstRun);

        $skill = Skill::where('name', $firstName)->firstOrFail();
        $this->assertSame($firstNameEn, $skill->name_en);
        $this->assertSame($firstCategory, $skill->category);
        $this->assertSame($firstIcon, $skill->icon);
        $this->assertTrue($skill->is_active);
        $this->assertFalse($skill->is_ghost);

        $freshGhost = $ghost->fresh();
        $this->assertTrue($freshGhost->is_ghost);
        $this->assertFalse($freshGhost->is_active);
        $this->assertSame(3, $freshGhost->search_count);

        $freshDraft = $draft->fresh();
        $this->assertFalse($freshDraft->is_ghost);
        $this->assertFalse($freshDraft->is_active);

        (new SkillCatalogSeeder())->run();

        $this->assertSame($countAfterFirstRun, Skill::whereIn('name', $catalogNames)->count());
        $this->assertTrue($ghost->fresh()->is_ghost);
        $this->assertFalse($draft->fresh()->is_active);
    }
}
