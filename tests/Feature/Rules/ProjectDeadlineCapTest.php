<?php

namespace Tests\Feature\Rules;

use App\Models\Permission;
use App\Models\Project;
use App\Models\User;
use App\Rules\ProjectDeadlineCap;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectDeadlineCapTest extends TestCase
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

    private function fails(?int $projectId, mixed $value): bool
    {
        $failed = false;
        (new ProjectDeadlineCap($projectId))->validate('deadline_date', $value, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    private function cappedProject(string $deadline): Project
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $owner->id, 'is_super_admin' => true]);

        return Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => $deadline],
        ]);
    }

    public function test_over_cap_gregorian_date_fails(): void
    {
        $cap = now()->addDays(2)->format('Y-m-d');
        $project = $this->cappedProject($cap);

        $this->assertTrue($this->fails($project->id, now()->addDays(3)->format('Y-m-d')));
    }

    public function test_deadline_on_cap_day_passes(): void
    {
        $cap = now()->addDays(2)->format('Y-m-d');
        $project = $this->cappedProject($cap);

        $this->assertFalse($this->fails($project->id, $cap));
    }

    public function test_deadline_before_cap_passes(): void
    {
        $cap = now()->addDays(2)->format('Y-m-d');
        $project = $this->cappedProject($cap);

        $this->assertFalse($this->fails($project->id, now()->addDay()->format('Y-m-d')));
    }

    public function test_project_without_cap_setting_passes(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $this->assertFalse($this->fails($project->id, now()->addYears(2)->format('Y-m-d')));
    }

    public function test_null_project_id_passes(): void
    {
        $this->assertFalse($this->fails(null, now()->addYears(2)->format('Y-m-d')));
    }

    public function test_missing_project_passes(): void
    {
        $this->assertFalse($this->fails(999999999, now()->addYears(2)->format('Y-m-d')));
    }

    public function test_blank_or_non_date_value_passes(): void
    {
        $cap = now()->addDays(2)->format('Y-m-d');
        $project = $this->cappedProject($cap);

        $this->assertFalse($this->fails($project->id, null));
        $this->assertFalse($this->fails($project->id, ''));
        $this->assertFalse($this->fails($project->id, []));
        $this->assertFalse($this->fails($project->id, 'not-a-date'));
    }
}