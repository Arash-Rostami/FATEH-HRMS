<?php

namespace Tests\Feature\Traits;

use App\Enums\UserType;
use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use App\Traits\DepartmentAxis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepartmentAxisTest extends TestCase
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

    private function host(): object
    {
        return new class {
            use DepartmentAxis {
                topDepartments as public;
            }
        };
    }

    private function biggestDepartment(): int
    {
        return (int) (DB::table('profiles')
            ->whereNotNull('department_id')
            ->select(DB::raw('COUNT(*) as cnt'))
            ->groupBy('department_id')
            ->orderByDesc('cnt')
            ->value('cnt') ?? 0);
    }

    private function seedDepartment(string $code, string $name, ?string $description, int $profiles, ?string $userType = null): void
    {
        Department::create(['code' => $code, 'name' => $name, 'description' => $description]);

        for ($i = 0; $i < $profiles; $i++) {
            $user = User::factory()->create($userType ? ['type' => $userType] : []);
            Profile::factory()->create([
                'department_id' => $code,
                'user_id' => $user->id,
                'personnel_id' => 'PC-' . $code . '-' . $i . '-' . uniqid(),
            ]);
        }
    }

    public function test_departments_are_ordered_by_headcount_descending(): void
    {
        $max = $this->biggestDepartment();
        $this->seedDepartment('ZX-SMALL', 'Small', 'Small Desc', $max + 1);
        $this->seedDepartment('ZX-BIG', 'Big', 'Big Desc', $max + 2);

        [$codes] = $this->host()->topDepartments(2);

        $this->assertSame(['ZX-BIG', 'ZX-SMALL'], $codes);
    }

    public function test_equal_headcounts_are_tie_broken_by_department_code(): void
    {
        $max = $this->biggestDepartment();
        $this->seedDepartment('ZX-TIE-B', 'Tie B', 'Tie B Desc', $max + 1);
        $this->seedDepartment('ZX-TIE-A', 'Tie A', 'Tie A Desc', $max + 1);

        [$codes] = $this->host()->topDepartments(2);

        $this->assertSame(['ZX-TIE-A', 'ZX-TIE-B'], $codes);
    }

    public function test_limit_caps_the_axis_and_the_index_maps_code_to_position(): void
    {
        $max = $this->biggestDepartment();
        $this->seedDepartment('ZX-TOP', 'Top', 'Top Desc', $max + 1);

        [$codes, $labels, $index] = $this->host()->topDepartments(1);

        $this->assertSame(['ZX-TOP'], $codes);
        $this->assertSame(['Top Desc'], $labels);
        $this->assertSame(['ZX-TOP' => 0], $index);
    }

    public function test_guest_users_do_not_count_toward_headcount(): void
    {
        $max = $this->biggestDepartment();
        $this->seedDepartment('ZX-GUESTS', 'Guests', 'Guests Desc', $max + 1, UserType::Guest->value);

        [$codes] = $this->host()->topDepartments(10);

        $this->assertNotContains('ZX-GUESTS', $codes);
    }

    public function test_label_prefers_description_then_name_then_code(): void
    {
        $max = $this->biggestDepartment();
        $this->seedDepartment('ZX-DESC', 'Has Name', 'Has Description', $max + 3);
        $this->seedDepartment('ZX-NAME', 'Only Name', '', $max + 2);
        $this->seedDepartment('ZX-CODE', '', '', $max + 1);

        [$codes, $labels] = $this->host()->topDepartments(3);

        $this->assertSame(['ZX-DESC', 'ZX-NAME', 'ZX-CODE'], $codes);
        $this->assertSame(['Has Description', 'Only Name', 'ZX-CODE'], $labels);
    }

    public function test_result_is_cached_per_limit_within_an_instance(): void
    {
        $max = $this->biggestDepartment();
        $host = $this->host();
        $this->seedDepartment('ZX-FIRST', 'First', 'First Desc', $max + 1);
        $first = $host->topDepartments(1);

        $this->seedDepartment('ZX-LATER', 'Later', 'Later Desc', $max + 5);

        $this->assertSame($first, $host->topDepartments(1));
        $this->assertSame(['ZX-LATER'], $this->host()->topDepartments(1)[0]);
    }
}
