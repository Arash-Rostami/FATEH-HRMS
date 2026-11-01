<?php

namespace Tests\Feature\Rules;

use App\Models\Skill;
use App\Rules\UniqueActiveSkillName;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UniqueActiveSkillNameTest extends TestCase
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

    private function fails(UniqueActiveSkillName $rule, string $value): bool
    {
        $failed = false;
        $rule->validate('name', $value, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    public function test_blank_value_passes_without_querying(): void
    {
        $this->assertFalse($this->fails(new UniqueActiveSkillName(null, 'create'), ''));
    }

    public function test_create_mode_blocked_by_existing_active_skill_with_same_name(): void
    {
        $name = 'Active Skill ' . uniqid();
        Skill::create(['name' => $name, 'is_active' => true]);

        $this->assertTrue($this->fails(new UniqueActiveSkillName(null, 'create'), $name));
    }

    public function test_create_mode_not_blocked_by_existing_inactive_skill_with_same_name(): void
    {
        $name = 'Inactive Skill ' . uniqid();
        Skill::create(['name' => $name, 'is_active' => false]);

        $this->assertFalse($this->fails(new UniqueActiveSkillName(null, 'create'), $name));
    }

    public function test_edit_mode_blocked_by_any_collision_active_or_inactive_except_itself(): void
    {
        $activeName = 'Active Collision ' . uniqid();
        $inactiveName = 'Inactive Collision ' . uniqid();
        Skill::create(['name' => $activeName, 'is_active' => true]);
        Skill::create(['name' => $inactiveName, 'is_active' => false]);
        $record = Skill::create(['name' => 'Editable Skill ' . uniqid(), 'is_active' => true]);

        $this->assertTrue($this->fails(new UniqueActiveSkillName($record, 'edit'), $activeName));
        $this->assertTrue($this->fails(new UniqueActiveSkillName($record, 'edit'), $inactiveName));
    }

    public function test_edit_mode_excludes_the_records_own_current_name_from_counting_as_conflict(): void
    {
        $name = 'Own Name Skill ' . uniqid();
        $record = Skill::create(['name' => $name, 'is_active' => true]);

        $this->assertFalse($this->fails(new UniqueActiveSkillName($record, 'edit'), $name));
    }

    public function test_message_content(): void
    {
        $this->app->setLocale('fa');
        $name = 'Message Skill ' . uniqid();
        Skill::create(['name' => $name, 'is_active' => true]);

        $message = null;
        (new UniqueActiveSkillName(null, 'create'))
            ->validate('name', $name, function (string $m) use (&$message): void {
                $message = $m;
            });

        $this->assertNotNull($message);
        $this->assertSame(__('resources/skill/strings.errors.name_conflict'), $message);
    }
}
