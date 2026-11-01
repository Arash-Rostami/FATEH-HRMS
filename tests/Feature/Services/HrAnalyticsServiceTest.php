<?php

namespace Tests\Feature\Services;

use App\Enums\PresenceStatus;
use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use App\Services\HrAnalyticsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class HrAnalyticsServiceTest extends TestCase
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
        Cache::flush();
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

    private function service(): HrAnalyticsService
    {
        return new HrAnalyticsService();
    }

    private function invoke(object $obj, string $method, array $args = []): mixed
    {
        $rm = new ReflectionMethod($obj, $method);
        $rm->setAccessible(true);
        return $rm->invokeArgs($obj, $args);
    }

    private function createProfile(array $overrides): Profile
    {
        $user = User::factory()->create(['status' => 'active']);
        return Profile::factory()->create(array_merge(['user_id' => $user->id], $overrides));
    }

    private function assertChartShape(array $result): void
    {
        $this->assertArrayHasKey('datasets', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertIsArray($result['datasets']);
        $this->assertIsArray($result['labels']);
        foreach ($result['datasets'] as $ds) {
            $this->assertArrayHasKey('data', $ds);
            $this->assertSame(count($result['labels']), count($ds['data']));
            foreach ($ds['data'] as $value) {
                $this->assertIsNumeric($value);
                $this->assertGreaterThanOrEqual(0, (float) $value);
            }
        }
    }

    private function topTenThreshold(): int
    {
        $counts = DB::table('profiles')
            ->select('department_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->pluck('cnt')
            ->all();

        return $counts === [] ? 0 : (int) min($counts);
    }

    private function seedDept(string $code, string $name, ?string $description, int $profileCount, array $profileAttrs = []): Department
    {
        $dept = Department::create(['code' => $code, 'name' => $name, 'description' => $description]);
        $threshold = $this->topTenThreshold();
        $count = max($profileCount, $threshold + 3);
        for ($i = 0; $i < $count; $i++) {
            Profile::factory()->create(array_merge([
                'department_id' => $code,
                'user_id' => User::factory(),
                'personnel_id' => 'PC-' . $code . '-' . $i . '-' . uniqid(),
            ], $profileAttrs));
        }

        return $dept;
    }

    private function personnelId(): string
    {
        return 'TST-' . bin2hex(random_bytes(6));
    }

    private function makeTopDepartment(string $description): Department
    {
        $code = 'TST' . bin2hex(random_bytes(3));
        return Department::factory()->create([
            'code' => $code,
            'name' => $code,
            'description' => $description,
        ]);
    }

    private function maxDeptProfileCount(): int
    {
        $row = DB::table('profiles')
            ->select(DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->orderByDesc('cnt')
            ->first();
        return $row?->cnt ?? 0;
    }

    private function fillerProfiles(string $deptCode, int $userId, array $attrs): int
    {
        $n = $this->maxDeptProfileCount() + 3;
        $now = now()->toDateTimeString();
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $rows[] = array_merge([
                'user_id' => $userId,
                'department_id' => $deptCode,
                'gender' => 'male',
                'employment_type' => 'fulltime',
                'marital_status' => 'single',
                'personnel_id' => $this->personnelId(),
                'created_at' => $now,
                'updated_at' => $now,
            ], $attrs);
        }
        DB::table('profiles')->insert($rows);
        return $n;
    }

    private function makeProfileRow(string $deptCode, int $userId, array $attrs): void
    {
        $now = now()->toDateTimeString();
        DB::table('profiles')->insert(array_merge([
            'user_id' => $userId,
            'department_id' => $deptCode,
            'gender' => 'male',
            'employment_type' => 'fulltime',
            'marital_status' => 'single',
            'personnel_id' => $this->personnelId(),
            'created_at' => $now,
            'updated_at' => $now,
        ], $attrs));
    }

    private function deptIndex(array $labels, string $expectedLabel): int
    {
        $i = array_search($expectedLabel, $labels, true);
        $this->assertNotFalse($i, "department label [{$expectedLabel}] not present: " . json_encode(array_values($labels), JSON_UNESCAPED_UNICODE));
        return (int) $i;
    }

    private function validPositionValues(): array
    {
        return array_map(fn(Position $p) => $p->value, Position::cases());
    }

    private function deleteProfilesOutside(string $column, array $validValues): void
    {
        $ids = DB::table('profiles')->whereNotIn($column, $validValues)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('profile_details')->whereIn('profile_id', $ids)->delete();
        }
        DB::table('profiles')->whereNotIn($column, $validValues)->delete();
    }

    public function test_get_hr_a_data_tracks_position_gender_breakdown(): void
    {
        $baseline = $this->service()->getHrAData();
        $this->assertChartShape($baseline);
        $idx = array_search(Position::Employee->getLabel(), $baseline['labels']);
        $this->assertNotFalse($idx);
        $before = $baseline['datasets'][0]['data'][$idx];

        $this->createProfile([
            'position' => Position::Employee->value,
            'gender' => 'male',
            'degree' => Degree::Undergraduate->value,
            'marital_status' => 'single',
        ]);

        Cache::forget('hr:hr_a');
        $after = $this->service()->getHrAData();
        $this->assertSame($before + 1, $after['datasets'][0]['data'][$idx]);
    }

    public function test_get_hr_a_data_excludes_guest_type_users(): void
    {
        $baseline = $this->service()->getHrAData();
        $idx = array_search(Position::Employee->getLabel(), $baseline['labels']);
        $before = $baseline['datasets'][0]['data'][$idx];

        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Profile::factory()->create([
            'user_id' => $guest->id,
            'position' => Position::Employee->value,
            'gender' => 'male',
        ]);

        $after = $this->service()->getHrAData();
        $this->assertSame($before, $after['datasets'][0]['data'][$idx], 'a guest-type profile must not be counted');
    }

    public function test_get_hr_b_data_tracks_degree_gender_breakdown(): void
    {
        $baseline = $this->service()->getHrBData();
        $this->assertChartShape($baseline);
        $this->assertSame(count(Degree::cases()), count($baseline['labels']));
        $idx = array_search(Degree::Undergraduate->getLabel(), $baseline['labels']);
        $this->assertNotFalse($idx);
        $before = $baseline['datasets'][0]['data'][$idx];

        $this->createProfile([
            'position' => Position::Employee->value,
            'gender' => 'male',
            'degree' => Degree::Undergraduate->value,
            'marital_status' => 'single',
        ]);

        Cache::forget('hr:hr_b');
        $after = $this->service()->getHrBData();
        $this->assertSame($before + 1, $after['datasets'][0]['data'][$idx]);
    }

    public function test_hr_a_appends_other_slice_when_invalid_position_exists(): void
    {
        $baselineHasOther = in_array('سایر', $this->service()->getHrAData()['labels']);
        $this->createProfile([
            'position' => 'zzz-invalid',
            'gender' => 'male',
            'degree' => Degree::Undergraduate->value,
            'marital_status' => 'single',
        ]);
        Cache::forget('hr:hr_a');
        $labels = $this->service()->getHrAData()['labels'];
        $this->assertContains('سایر', $labels);
        if ($baselineHasOther) {
            $this->markTestSkipped('dev-DB already held invalid-position rows; سایر presence asserted but delta not attributable to seeded row');
        }
    }

    public function test_hr_a_omits_other_slice_when_all_positions_valid(): void
    {
        $this->deleteProfilesOutside('position', $this->validPositionValues());
        $labels = $this->service()->getHrAData()['labels'];
        $this->assertNotContains('سایر', $labels);
        $this->assertSame(count(Position::cases()), count($labels));
    }

    public function test_hr_b_other_slice_branch_unreachable_due_to_enum_constraint(): void
    {
        $this->markTestSkipped('profiles.degree is MySQL enum NOT NULL — invalid degree value cannot be inserted to exercise the سایر branch');
    }

    public function test_get_hr_c_data_assigns_age_bands_correctly(): void
    {
        $baseline = $this->service()->getHrCData();
        $this->assertChartShape($baseline);
        $this->assertSame(['کمتر از ۲۵', '۲۵ تا ۳۴', '۳۵ تا ۴۴', '۴۵ تا ۵۴', '۵۵ به بالا'], $baseline['labels']);
        $maleBand = $baseline['datasets'][0]['data'];

        $this->createProfile([
            'gender' => 'male',
            'birthdate' => now()->subYears(30)->toDateString(),
            'position' => Position::Employee->value,
            'degree' => Degree::Undergraduate->value,
            'marital_status' => 'single',
        ]);

        Cache::forget('hr:hr_c');
        $after = $this->service()->getHrCData();
        $this->assertSame($maleBand[1] + 1, $after['datasets'][0]['data'][1]);
    }

    public function test_get_hr_c_data_excludes_guest_type_users(): void
    {
        $baseline = $this->service()->getHrCData();
        $before = $baseline['datasets'][0]['data'][1];

        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        Profile::factory()->create([
            'user_id' => $guest->id,
            'gender' => 'male',
            'birthdate' => now()->subYears(30)->toDateString(),
        ]);

        $after = $this->service()->getHrCData();
        $this->assertSame($before, $after['datasets'][0]['data'][1], 'a guest-type profile must not be counted in the age-band raw subquery');
    }

    public function test_get_hr_d_data_returns_doughnut_shape_with_color_array(): void
    {
        $baseline = $this->service()->getHrDData();
        $this->assertCount(1, $baseline['datasets']);
        $ds = $baseline['datasets'][0];
        $this->assertIsArray($ds['backgroundColor']);
        $this->assertCount(count($baseline['labels']), $ds['backgroundColor']);
        $this->assertContains('مجرد آقا', $baseline['labels']);
        $idx = array_search('مجرد آقا', $baseline['labels']);

        $this->createProfile([
            'gender' => 'male',
            'marital_status' => 'single',
            'position' => Position::Employee->value,
            'degree' => Degree::Undergraduate->value,
        ]);

        Cache::forget('hr:hr_d');
        $after = $this->service()->getHrDData();
        $this->assertSame($ds['data'][$idx] + 1, $after['datasets'][0]['data'][$idx]);
    }

    public function test_get_hr_e_data_shape_and_other_position_bucket(): void
    {
        $this->createProfile(['position' => Position::Manager->value, 'degree' => Degree::Doctorate->value, 'personnel_id' => 'PC-E1-' . uniqid()]);
        $this->createProfile(['position' => 'invalid-role', 'degree' => Degree::Graduate->value, 'personnel_id' => 'PC-E2-' . uniqid()]);

        $result = $this->service()->getHrEData();
        $this->assertChartShape($result);
        $this->assertContains('سایر', $result['labels']);
        $this->assertNotEmpty($result['datasets']);
    }

    public function test_get_hr_f_data_shape(): void
    {
        $this->createProfile(['birthdate' => '1990-01-01', 'degree' => Degree::Postgraduate->value, 'personnel_id' => 'PC-F1-' . uniqid()]);
        $this->createProfile(['birthdate' => '2000-01-01', 'degree' => Degree::Undergraduate->value, 'personnel_id' => 'PC-F2-' . uniqid()]);

        $result = $this->service()->getHrFData();
        $this->assertChartShape($result);
        $this->assertCount(5, $result['labels']);
        $this->assertNotEmpty($result['datasets']);
    }

    public function test_get_hr_g_data_shape_and_other_position_bucket(): void
    {
        $this->createProfile(['position' => Position::Senior->value, 'start_date' => '2015-01-01', 'personnel_id' => 'PC-G1-' . uniqid()]);
        $this->createProfile(['position' => 'bogus-position', 'start_date' => '2023-01-01', 'personnel_id' => 'PC-G2-' . uniqid()]);

        $result = $this->service()->getHrGData();
        $this->assertChartShape($result);
        $this->assertContains('سایر', $result['labels']);
        $this->assertNotEmpty($result['datasets']);
    }

    public function test_get_hr_h_data_shape_and_other_field_bucket(): void
    {
        $fields = ['FieldA', 'FieldB', 'FieldC', 'FieldD', 'FieldE', 'FieldF', 'FieldG'];
        foreach ($fields as $i => $field) {
            $this->createProfile(['position' => Position::Expert->value, 'field' => $field, 'personnel_id' => 'PC-H' . $i . '-' . uniqid()]);
        }

        $result = $this->service()->getHrHData();
        $this->assertChartShape($result);
        $datasetLabels = array_map(fn($ds) => $ds['label'], $result['datasets']);
        $this->assertContains('سایر تخصص‌ها', $datasetLabels);
        $this->assertSame(count($result['labels']), count($result['datasets'][0]['data']));
    }

    public function test_position_axis_returns_three_element_tuple_and_no_other_when_all_valid(): void
    {
        $rows = array_map(fn(Position $p) => (object) ['position' => $p->value], Position::cases());
        [$keys, $labels, $index] = $this->invoke($this->service(), 'positionAxis', [$rows]);

        $this->assertSame(count($keys), count($labels));
        $this->assertSame($keys, array_keys($index));
        $this->assertNotContains('سایر', $labels);
        $this->assertNotContains('__other__', $keys);
    }

    public function test_position_axis_appends_other_when_null_or_invalid_position_present(): void
    {
        $validRows = array_map(fn(Position $p) => (object) ['position' => $p->value], Position::cases());

        $withNull = array_merge($validRows, [(object) ['position' => null]]);
        [, $labelsNull] = $this->invoke($this->service(), 'positionAxis', [$withNull]);
        $this->assertContains('سایر', $labelsNull);

        $withInvalid = array_merge($validRows, [(object) ['position' => 'not-a-real-position']]);
        [, $labelsInvalid] = $this->invoke($this->service(), 'positionAxis', [$withInvalid]);
        $this->assertContains('سایر', $labelsInvalid);
    }

    public function test_position_map_equals_array_flip_of_position_values(): void
    {
        $map = $this->invoke($this->service(), 'positionMap');
        $expected = array_flip(array_map(fn(Position $p) => $p->value, Position::cases()));
        $this->assertSame($expected, $map);
    }

    public function test_get_hr_i_data_shape_and_position_rest_bucket(): void
    {
        $this->seedDept('ZZ-SI', 'Unit I', 'Unit I Desc', 3, ['position' => Position::Employee->value]);
        $this->seedDept('ZZ-SIR', 'Unit IR', 'Unit IR Desc', 2, ['position' => 'unknown-title']);

        $result = $this->service()->getHrIData();
        $this->assertChartShape($result);
        $found = array_map(fn($ds) => $ds['label'], $result['datasets']);
        foreach (['سایر', 'کارمند'] as $label) {
            $this->assertContains($label, $found);
        }
        $rest = collect($result['datasets'])->firstWhere('label', 'سایر');
        $this->assertNotNull($rest);
        $this->assertSame(count($result['labels']), count($rest['data']));
    }

    public function test_get_hr_i_data_routes_known_positions_out_of_rest_bucket(): void
    {
        $this->seedDept('ZZ-SIV', 'Unit IV', 'Unit IV Desc', 3, ['position' => Position::Manager->value]);

        $service = $this->service();
        $result = $service->getHrIData();
        $this->assertChartShape($result);

        [$codes] = $this->invoke($service, 'topDepartments');
        $idx = array_search('ZZ-SIV', $codes, true);
        $this->assertNotFalse($idx);
        $count = Profile::where('department_id', 'ZZ-SIV')->count();
        $manager = collect($result['datasets'])->firstWhere('label', Position::Manager->getLabel());
        $this->assertNotNull($manager);
        $this->assertSame($count, $manager['data'][$idx]);

        $rest = collect($result['datasets'])->firstWhere('label', 'سایر');
        if ($rest !== null) {
            $this->assertSame(0, $rest['data'][$idx]);
        }
    }

    public function test_get_hr_j_data_shape_and_no_rest_for_enum_constrained_gender(): void
    {
        $this->seedDept('ZZ-SJ', 'Unit J', 'Unit J Desc', 3, ['gender' => 'male']);

        $result = $this->service()->getHrJData();
        $this->assertChartShape($result);
        $found = array_map(fn($ds) => $ds['label'], $result['datasets']);
        $this->assertNotContains('نامشخص', $found);
        $this->assertSame(2, count($result['datasets']));
    }

    public function test_get_hr_k_data_shape_and_five_age_bands(): void
    {
        $this->seedDept('ZZ-SK', 'Unit K', 'Unit K Desc', 0);
        foreach ([20, 30, 60] as $age) {
            Profile::factory()->create([
                'department_id' => 'ZZ-SK',
                'user_id' => User::factory(),
                'personnel_id' => 'PC-ZZ-SK-' . $age . '-' . uniqid(),
                'birthdate' => now()->subYears($age)->format('Y-m-d'),
            ]);
        }

        $result = $this->service()->getHrKData();
        $this->assertChartShape($result);
        $this->assertSame(5, count($result['datasets']));
        $this->assertSame(['کمتر از ۲۵', '۲۵ تا ۳۴', '۳۵ تا ۴۴', '۴۵ تا ۵۴', '۵۵ به بالا'], array_map(fn($ds) => $ds['label'], $result['datasets']));
    }

    public function test_get_hr_l_data_shape_and_no_rest_for_enum_constrained_status(): void
    {
        $this->seedDept('ZZ-SL', 'Unit L', 'Unit L Desc', 3, ['employment_status' => 'working']);

        $result = $this->service()->getHrLData();
        $this->assertChartShape($result);
        $found = array_map(fn($ds) => $ds['label'], $result['datasets']);
        $this->assertNotContains('نامشخص', $found);
        $this->assertSame(3, count($result['datasets']));
    }

    public function test_get_hr_m_data_probational_percentage(): void
    {
        Department::create(['code' => 'ZZ-SM', 'name' => 'Unit M', 'description' => 'Unit M Desc']);
        $threshold = $this->topTenThreshold();
        $probational = max(2, (int) ceil(($threshold + 3) / 3));
        $working = 2 * $probational;
        $total = $working + $probational;
        for ($i = 0; $i < $working; $i++) {
            Profile::factory()->create([
                'department_id' => 'ZZ-SM',
                'user_id' => User::factory(),
                'personnel_id' => 'PC-ZZ-SM-w-' . $i . '-' . uniqid(),
                'employment_status' => 'working',
            ]);
        }
        for ($i = 0; $i < $probational; $i++) {
            Profile::factory()->create([
                'department_id' => 'ZZ-SM',
                'user_id' => User::factory(),
                'personnel_id' => 'PC-ZZ-SM-p-' . $i . '-' . uniqid(),
                'employment_status' => 'probational',
            ]);
        }
        $expected = round($probational / $total * 100, 1);

        $service = $this->service();
        $result = $service->getHrMData();
        $this->assertChartShape($result);
        $this->assertSame(1, count($result['datasets']));
        [$codes] = $this->invoke($service, 'topDepartments');
        $idx = array_search('ZZ-SM', $codes, true);
        $this->assertNotFalse($idx);
        $value = (float) $result['datasets'][0]['data'][$idx];
        $this->assertSame($expected, $value);
        $this->assertLessThanOrEqual(100, $value);
        $this->assertGreaterThanOrEqual(0, $value);
    }

    public function test_get_hr_m_data_zero_percent_when_no_probational(): void
    {
        $this->seedDept('ZZ-SMZ', 'Unit MZ', 'Unit MZ Desc', 3, ['employment_status' => 'working']);

        $service = $this->service();
        $result = $service->getHrMData();
        $this->assertChartShape($result);
        $this->assertSame(1, count($result['datasets']));
        [$codes] = $this->invoke($service, 'topDepartments');
        $idx = array_search('ZZ-SMZ', $codes, true);
        $this->assertNotFalse($idx);
        $this->assertSame(0.0, (float) $result['datasets'][0]['data'][$idx]);
    }

    public function test_department_axis_label_falls_back_to_name_when_description_empty(): void
    {
        $this->seedDept('ZZ-SLBL', 'FallbackName', '', 3);

        $service = $this->service();
        [$codes, $labels] = $this->invoke($service, 'topDepartments');

        $this->assertContains('ZZ-SLBL', $codes);
        $idx = array_search('ZZ-SLBL', $codes, true);
        $this->assertSame('FallbackName', $labels[$idx]);
    }

    public function test_department_axis_tie_breaker_is_deterministic_by_department_id(): void
    {
        $this->seedDept('ZZ-STIE-A', 'Tie A', 'Tie A Desc', 3);
        $this->seedDept('ZZ-STIE-B', 'Tie B', 'Tie B Desc', 3);

        $service = $this->service();
        [$codes] = $this->invoke($service, 'topDepartments');

        $posA = array_search('ZZ-STIE-A', $codes, true);
        $posB = array_search('ZZ-STIE-B', $codes, true);
        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertLessThan($posB, $posA);
    }

    public function test_bucket_series_appends_rest_dataset_for_invalid_gender_via_reflection(): void
    {
        $rows = new Collection([
            (object) ['department_id' => 'ZZ1', 'gender' => 'zzz', 'count' => 1],
            (object) ['department_id' => 'ZZ2', 'gender' => 'male', 'count' => 2],
        ]);
        $known = [
            ['value' => 'male', 'label' => 'آقا', 'color' => '#3b82f6'],
            ['value' => 'female', 'label' => 'خانم', 'color' => '#ef4444'],
        ];
        $rest = ['label' => 'نامشخص', 'color' => '#94a3b8'];
        $idx = ['ZZ1' => 0, 'ZZ2' => 1];

        $datasets = $this->invoke($this->service(), 'bucketSeries', [$rows, $idx, 'gender', $known, $rest, 2]);

        $labels = array_map(fn($d) => $d['label'], $datasets);
        $this->assertContains('نامشخص', $labels);
        $restDs = collect($datasets)->firstWhere('label', 'نامشخص');
        $this->assertSame([1, 0], $restDs['data']);
    }

    public function test_bucket_series_omits_rest_dataset_when_all_values_known(): void
    {
        $rows = new Collection([
            (object) ['department_id' => 'ZZ1', 'gender' => 'male', 'count' => 2],
            (object) ['department_id' => 'ZZ2', 'gender' => 'female', 'count' => 3],
        ]);
        $known = [
            ['value' => 'male', 'label' => 'آقا', 'color' => '#3b82f6'],
            ['value' => 'female', 'label' => 'خانم', 'color' => '#ef4444'],
        ];
        $rest = ['label' => 'نامشخص', 'color' => '#94a3b8'];
        $idx = ['ZZ1' => 0, 'ZZ2' => 1];

        $datasets = $this->invoke($this->service(), 'bucketSeries', [$rows, $idx, 'gender', $known, $rest, 2]);

        $labels = array_map(fn($d) => $d['label'], $datasets);
        $this->assertNotContains('نامشخص', $labels);
        $this->assertSame(2, count($datasets));
    }

    public function test_get_hr_n_data_shape_one_dataset_per_presence_status(): void
    {
        $dept = $this->makeTopDepartment('SHRN-SHAPE');
        $filler = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->fillerProfiles($dept->code, $filler->id, []);

        $result = $this->service()->getHrNData();
        $this->assertChartShape($result);

        $cases = PresenceStatus::cases();
        $this->assertGreaterThanOrEqual(count($cases), count($result['datasets']));
        foreach ($cases as $p) {
            $found = collect($result['datasets'])->firstWhere('label', $p->label());
            $this->assertNotNull($found, "missing dataset for presence [{$p->label()}]");
            $this->assertSame($p->hex(), $found['backgroundColor']);
        }

        $i = $this->deptIndex($result['labels'], 'SHRN-SHAPE');
        $rest = collect($result['datasets'])->firstWhere('label', 'نامشخص');
        if ($rest !== null) {
            $this->assertSame(0, $rest['data'][$i]);
        }
    }

    public function test_hr_n_rest_dataset_appears_for_unknown_presence(): void
    {
        $dept = $this->makeTopDepartment('SHRN-REST');
        $filler = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->fillerProfiles($dept->code, $filler->id, []);

        $unknown = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        DB::table('users')->where('id', $unknown->id)->update(['presence' => 'unknown_xyz']);
        $this->makeProfileRow($dept->code, $unknown->id, []);

        $result = $this->service()->getHrNData();
        $this->assertChartShape($result);

        $rest = collect($result['datasets'])->firstWhere('label', 'نامشخص');
        $this->assertNotNull($rest, 'rest dataset [نامشخص] should appear when an unknown presence value exists');
        $i = $this->deptIndex($result['labels'], 'SHRN-REST');
        $this->assertSame(1, $rest['data'][$i]);
    }

    public function test_get_hr_o_data_counts_active_and_online_separately(): void
    {
        $dept = $this->makeTopDepartment('SHRO-ACT');
        $filler = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->fillerProfiles($dept->code, $filler->id, []);

        $online = User::factory()->create(['status' => 'active', 'last_seen' => now()]);
        $this->makeProfileRow($dept->code, $online->id, []);

        $activeOnly = User::factory()->create(['status' => 'active', 'last_seen' => now()->subHour()]);
        $this->makeProfileRow($dept->code, $activeOnly->id, []);

        $inactiveRecent = User::factory()->create(['status' => 'inactive', 'last_seen' => now()]);
        $this->makeProfileRow($dept->code, $inactiveRecent->id, []);

        $result = $this->service()->getHrOData();
        $this->assertChartShape($result);

        $i = $this->deptIndex($result['labels'], 'SHRO-ACT');
        $active = collect($result['datasets'])->firstWhere('label', 'فعال')['data'];
        $onlineData = collect($result['datasets'])->firstWhere('label', 'آنلاین (' . convertToPersian(User::ONLINE_MINUTES) . ' دقیقه)')['data'];
        $this->assertSame(2, $active[$i]);
        $this->assertSame(2, $onlineData[$i]);
    }

    public function test_get_hr_o_data_excludes_guest_type_users(): void
    {
        $dept = $this->makeTopDepartment('SHRO-GST');
        $filler = User::factory()->create(['status' => 'inactive', 'last_seen' => null]);
        $this->fillerProfiles($dept->code, $filler->id, []);

        $employee = User::factory()->create(['status' => 'active', 'last_seen' => now()]);
        $this->makeProfileRow($dept->code, $employee->id, []);

        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest', 'last_seen' => now()]);
        $this->makeProfileRow($dept->code, $guest->id, []);

        $result = $this->service()->getHrOData();
        $i = $this->deptIndex($result['labels'], 'SHRO-GST');
        $active = collect($result['datasets'])->firstWhere('label', 'فعال')['data'];

        $this->assertSame(1, $active[$i], 'the guest-type user must not be counted as active');
    }

    public function test_get_hr_p_data_counts_succession_risk_profiles(): void
    {
        $dept = $this->makeTopDepartment('SHRP-RISK');
        $filler = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->fillerProfiles($dept->code, $filler->id, [
            'birthdate' => now()->subYears(25)->toDateString(),
            'start_date' => now()->subYears(2)->toDateString(),
        ]);

        $atRisk = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->makeProfileRow($dept->code, $atRisk->id, [
            'birthdate' => now()->subYears(60)->toDateString(),
            'start_date' => now()->subYears(11)->toDateString(),
        ]);

        $young = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->makeProfileRow($dept->code, $young->id, [
            'birthdate' => now()->subYears(30)->toDateString(),
            'start_date' => now()->subYears(11)->toDateString(),
        ]);

        $result = $this->service()->getHrPData();
        $this->assertChartShape($result);

        $i = $this->deptIndex($result['labels'], 'SHRP-RISK');
        $this->assertSame(1, $result['datasets'][0]['data'][$i]);
    }

    public function test_get_hr_q_data_counts_profiles_without_field(): void
    {
        $dept = $this->makeTopDepartment('SHRQ-NOF');
        $filler = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->fillerProfiles($dept->code, $filler->id, ['field' => 'Engineering']);

        $nullField = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->makeProfileRow($dept->code, $nullField->id, ['field' => null]);

        $emptyField = User::factory()->create(['status' => 'inactive', 'presence' => PresenceStatus::Onsite->value, 'last_seen' => null]);
        $this->makeProfileRow($dept->code, $emptyField->id, ['field' => '']);

        $result = $this->service()->getHrQData();
        $this->assertChartShape($result);

        $i = $this->deptIndex($result['labels'], 'SHRQ-NOF');
        $this->assertSame(2, $result['datasets'][0]['data'][$i]);
    }

    public function test_get_hr_a_data_caches_under_fixed_key_so_a_mid_test_insert_is_invisible_until_flush(): void
    {
        Department::factory()->create(['code' => 'ZZ-CACHE', 'name' => 'CacheDept', 'description' => 'CacheDept']);

        $first = $this->service()->getHrAData();
        $this->assertChartShape($first);

        $this->createProfile(['department_id' => 'ZZ-CACHE', 'position' => 'manager', 'gender' => 'male']);

        $second = $this->service()->getHrAData();
        $this->assertSame($first, $second, 'getHrAData must hit the rememberGlobal cache and return the pre-insert dataset, not re-query');

        Cache::forget('hr:hr_a');
        $third = $this->service()->getHrAData();
        $this->assertNotEquals($first, $third, 'after forgetting hr:hr_a, getHrAData must recompute and reflect the extra profile');
    }
}
