<?php

namespace Tests\Feature\Models;

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuggestionModelTest extends TestCase
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

    public function test_purposes_constant_maps_keys_to_persian_labels(): void
    {
        $this->assertSame('کاهش هزینه', Suggestion::PURPOSES['cost']);
        $this->assertArrayHasKey('sales', Suggestion::PURPOSES);
    }

    public function test_stages_constant_has_all_eight_stages(): void
    {
        $this->assertCount(8, Suggestion::STAGES);
        $this->assertSame('ارسال شده', Suggestion::STAGES['pending']);
        $this->assertSame('پایان‌یافته', Suggestion::STAGES['closed']);
    }

    public function test_departments_cast_round_trips_array_through_db(): void
    {
        $suggestion = Suggestion::factory()->create([
            'departments' => ['HR', 'IT'],
        ]);

        $this->assertSame(['HR', 'IT'], $suggestion->fresh()->departments);
    }

    public function test_purpose_cast_round_trips_array_through_db(): void
    {
        $suggestion = Suggestion::factory()->create([
            'purpose' => ['cost', 'time'],
        ]);

        $this->assertSame(['cost', 'time'], $suggestion->fresh()->purpose);
    }

    public function test_rule_cast_round_trips_array_through_db(): void
    {
        $suggestion = Suggestion::factory()->create([
            'rule' => ['simplify', 'boost'],
        ]);

        $this->assertSame(['simplify', 'boost'], $suggestion->fresh()->rule);
    }

    public function test_self_fill_cast_round_trips_boolean(): void
    {
        $suggestion = Suggestion::factory()->create(['self_fill' => true]);

        $this->assertTrue($suggestion->fresh()->self_fill);
    }

    public function test_abort_cast_round_trips_boolean(): void
    {
        $suggestion = Suggestion::factory()->create(['abort' => true]);

        $this->assertTrue($suggestion->fresh()->abort);
    }

    public function test_scope_for_department_filters_by_json_contains(): void
    {
        $withDept = Suggestion::factory()->create(['departments' => ['HR', 'IT']]);
        $withoutDept = Suggestion::factory()->create(['departments' => ['MA']]);

        $this->assertContains($withDept->id, Suggestion::forDepartment('HR')->pluck('id')->all());
        $this->assertNotContains($withoutDept->id, Suggestion::forDepartment('HR')->pluck('id')->all());
    }

    public function test_count_pending_excludes_terminal_stages(): void
    {
        $baseline = Suggestion::countPending();

        Suggestion::factory()->create(['stage' => 'pending']);
        Suggestion::factory()->create(['stage' => 'accepted']);
        Suggestion::factory()->create(['stage' => 'rejected']);
        Suggestion::factory()->create(['stage' => 'closed']);

        $this->assertSame($baseline + 1, Suggestion::countPending());
    }

    public function test_count_closed_returns_count_of_closed_stage(): void
    {
        $baseline = Suggestion::countClosed();

        Suggestion::factory()->create(['stage' => 'closed']);
        Suggestion::factory()->create(['stage' => 'pending']);

        $this->assertSame($baseline + 1, Suggestion::countClosed());
    }

    public function test_count_aborted_returns_count_of_aborted_flag(): void
    {
        $baseline = Suggestion::countAborted();

        Suggestion::factory()->create(['abort' => true]);
        Suggestion::factory()->create(['abort' => false]);

        $this->assertSame($baseline + 1, Suggestion::countAborted());
    }

    public function test_count_self_filled_returns_count_of_self_fill_flag(): void
    {
        $baseline = Suggestion::countSelfFilled();

        Suggestion::factory()->create(['self_fill' => true]);
        Suggestion::factory()->create(['self_fill' => false]);

        $this->assertSame($baseline + 1, Suggestion::countSelfFilled());
    }

    public function test_count_attachment_returns_count_of_non_null_attachment(): void
    {
        $baseline = Suggestion::countAttachment();

        Suggestion::factory()->create(['attachment' => 'file.pdf']);
        Suggestion::factory()->create(['attachment' => null]);

        $this->assertSame($baseline + 1, Suggestion::countAttachment());
    }

    public function test_count_stage_returns_count_for_specific_stage(): void
    {
        $baseline = Suggestion::countStage('pending');

        Suggestion::factory()->create(['stage' => 'pending']);
        Suggestion::factory()->create(['stage' => 'accepted']);

        $this->assertSame($baseline + 1, Suggestion::countStage('pending'));
    }

    public function test_description_set_mutator_strips_control_characters(): void
    {
        $suggestion = Suggestion::factory()->create([
            'description' => "hello\x00world",
        ]);

        $this->assertSame('helloworld', $suggestion->fresh()->description);
    }

    public function test_description_set_mutator_passes_null_through_unchanged(): void
    {
        $suggestion = new Suggestion();
        $suggestion->description = null;

        $this->assertNull($suggestion->getAttributes()['description']);
    }

    public function test_deleting_removes_attachment_file_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('suggestions/file.pdf', 'content');
        $suggestion = Suggestion::factory()->create(['attachment' => 'suggestions/file.pdf']);

        $suggestion->delete();

        Storage::disk('public')->assertMissing('suggestions/file.pdf');
    }

    public function test_deleting_with_no_attachment_does_not_error(): void
    {
        Storage::fake('public');
        $suggestion = Suggestion::factory()->create(['attachment' => null]);

        $suggestion->delete();

        $this->assertDatabaseMissing('suggestions', ['id' => $suggestion->id]);
    }
}