<?php

namespace Tests\Feature\Support;

use App\Models\Department;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use App\Support\ProjectCharter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectCharterTest extends TestCase
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

    private function user(array $attrs = []): User
    {
        return User::factory()->create(['status' => 'active', 'role' => 'user', ...$attrs]);
    }

    private function charter(array $attrs = []): ProjectCharter
    {
        return Project::factory()->create(['member_ids' => [], 'departments' => [], ...$attrs])->charter();
    }

    public function test_roster_is_owner_members_and_department_members_active_non_guest_and_deduped(): void
    {
        $dept = Department::factory()->create();
        $owner = $this->user();
        $member = $this->user();
        $deptUser = $this->user();
        $deptInactive = $this->user(['status' => 'inactive']);
        $guest = $this->user(['type' => 'guest']);
        $outsider = $this->user();
        Profile::factory()->for($deptUser)->create(['department_id' => $dept->code]);
        Profile::factory()->for($deptInactive)->create(['department_id' => $dept->code]);
        Profile::factory()->for($member)->create(['department_id' => $dept->code]);

        $charter = $this->charter([
            'owner_id' => $owner->id,
            'member_ids' => [$member->id, $guest->id],
            'departments' => [$dept->code],
        ]);

        $ids = $charter->rosterIds();

        $this->assertEqualsCanonicalizing([$owner->id, $member->id, $deptUser->id], $ids);
        $this->assertSame(count($ids), count(array_unique($ids)));
        $this->assertTrue($charter->isMember($deptUser->id));
        $this->assertFalse($charter->isMember($outsider->id));
        $this->assertFalse($charter->isMember($guest->id));
    }

    public function test_default_deadline_adds_sla_hours_and_clamps_to_the_cap(): void
    {
        $from = Carbon::parse('2030-03-10 08:00:00');

        $this->assertNull($this->charter()->defaultDeadline($from));

        $open = $this->charter(['settings' => ['sla' => 48]]);
        $this->assertSame('2030-03-12 08:00:00', $open->defaultDeadline($from)->toDateTimeString());

        $capped = $this->charter(['settings' => ['sla' => 48, 'deadline' => '2030-03-11']]);
        $this->assertSame('2030-03-11 12:00:00', $capped->defaultDeadline($from)->toDateTimeString());
    }

    public function test_assert_deadline_rejects_beyond_cap_only_when_the_day_changed(): void
    {
        $charter = $this->charter(['settings' => ['deadline' => '2030-03-11']]);
        $beyond = Carbon::parse('2030-03-20 10:00:00');

        try {
            $charter->assertDeadline($beyond, Carbon::parse('2030-03-10'), 'deadline');
            $this->fail('changed beyond-cap deadline must be rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('deadline', $e->errors());
        }

        $charter->assertDeadline($beyond, Carbon::parse('2030-03-20 23:00:00'), 'deadline');
        $charter->assertDeadline(Carbon::parse('2030-03-11 18:00:00'), null, 'deadline');
        $charter->assertDeadline(null, null, 'deadline');
        $this->addToAssertionCount(1);
    }

    public function test_labels_are_strict_when_a_vocabulary_exists_and_free_when_empty(): void
    {
        $this->charter()->assertLabels(['anything', 'goes'], [], 'labels');

        $strict = $this->charter(['settings' => ['labels' => ['فوری', 'مالی']]]);
        $strict->assertLabels(['فوری'], [], 'labels');

        $this->expectException(ValidationException::class);
        $strict->assertLabels(['فوری', 'ناشناخته'], ['فوری'], 'labels');
    }

    public function test_label_validation_is_diff_only_so_legacy_labels_survive_an_edit(): void
    {
        $strict = $this->charter(['settings' => ['labels' => ['مالی']]]);

        $strict->assertLabels(['قدیمی', 'مالی'], ['قدیمی'], 'labels');

        $this->assertSame(['ی', 'ک'], ProjectCharter::normalizeLabels(['ي ', 'ك', '']));
    }

    public function test_tuning_defaults_and_overrides(): void
    {
        $default = $this->charter();

        $this->assertSame(45, $default->autoArchiveDays());
        $this->assertSame(3, $default->escalationDays());
        $this->assertSame(24, $default->dueSoonHours());

        $tuned = $this->charter(['settings' => ['auto_archive_days' => 10, 'escalation_days' => 0, 'due_soon_hours' => 6]]);

        $this->assertSame(10, $tuned->autoArchiveDays());
        $this->assertSame(0, $tuned->escalationDays());
        $this->assertSame(6, $tuned->dueSoonHours());
    }

    public function test_default_department_only_when_exactly_one(): void
    {
        $this->assertNull($this->charter(['departments' => []])->defaultDepartment());
        $this->assertNull($this->charter(['departments' => ['AAA', 'BBB']])->defaultDepartment());
        $this->assertSame('AAA', $this->charter(['departments' => ['AAA']])->defaultDepartment());
    }
}
