<?php

namespace Tests\Feature\Models;

use App\Models\Edge;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EdgeModelTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        $this->user = User::factory()->create(['status' => 'active']);
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

    private function createEdge(array $attributes = []): Edge
    {
        return Edge::create($attributes + [
            'user_id' => $this->user->id,
            'edge_key' => 'edge-model-test:edge',
            'subject_type' => 'App\\Models\\Task',
            'subject_id' => '71',
            'icon' => 'info',
            'title' => 'title',
            'body' => 'body',
            'url' => '/somewhere',
        ]);
    }

    public function test_timestamp_casts_round_trip_datetime_through_db(): void
    {
        $dismissed = now()->subHour()->startOfSecond();
        $snoozed = now()->addWeek()->startOfSecond();

        $this->createEdge(['dismissed_at' => $dismissed, 'snoozed_until' => $snoozed]);

        $fresh = Edge::query()
            ->where('user_id', $this->user->id)
            ->where('edge_key', 'edge-model-test:edge')
            ->first();

        $this->assertInstanceOf(CarbonInterface::class, $fresh->dismissed_at);
        $this->assertInstanceOf(CarbonInterface::class, $fresh->snoozed_until);
        $this->assertTrue($fresh->dismissed_at->equalTo($dismissed));
        $this->assertTrue($fresh->snoozed_until->equalTo($snoozed));
    }

    public function test_subject_id_round_trips_as_string(): void
    {
        $this->createEdge(['subject_id' => 71]);

        $fresh = Edge::find(Edge::query()->where('edge_key', 'edge-model-test:edge')->first()->id);

        $this->assertSame('71', $fresh->subject_id);
    }

    public function test_clear_dismissal_nulls_both_timestamp_columns(): void
    {
        $edge = $this->createEdge(['dismissed_at' => now(), 'snoozed_until' => now()->addDay()]);

        Edge::find($edge->id)->clearDismissal();

        $fresh = Edge::find($edge->id);
        $this->assertNull($fresh->dismissed_at);
        $this->assertNull($fresh->snoozed_until);
    }

    public function test_durations_constant_maps_options_to_carbon_methods(): void
    {
        $this->assertSame([
            '1day' => 'addDay',
            '1week' => 'addWeek',
            '1month' => 'addMonth',
        ], Edge::DURATIONS);
    }

    public function test_snooze_forever_sets_dismissed_at_and_leaves_snoozed_until_null(): void
    {
        $edge = $this->createEdge(['snoozed_until' => now()->addDay()]);

        Edge::find($edge->id)->snooze('forever');

        $fresh = Edge::find($edge->id);
        $this->assertInstanceOf(CarbonInterface::class, $fresh->dismissed_at);
        $this->assertNull($fresh->snoozed_until);
    }

    public function test_snooze_duration_options_clear_dismissal_and_set_snoozed_until(): void
    {
        foreach (Edge::DURATIONS as $optionKey => $method) {
            $edge = $this->createEdge(['dismissed_at' => now(), 'subject_id' => $optionKey]);

            Edge::find($edge->id)->snooze($optionKey);

            $fresh = Edge::find($edge->id);
            $this->assertNull($fresh->dismissed_at, "dismissed_at not cleared for {$optionKey}.");
            $this->assertInstanceOf(CarbonInterface::class, $fresh->snoozed_until);
            $expected = now()->{$method}();
            $this->assertTrue(
                $fresh->snoozed_until->between($expected->copy()->subSeconds(5), $expected->copy()->addSeconds(5)),
                "snoozed_until out of range for {$optionKey}."
            );
        }
    }

    public function test_snooze_with_unknown_option_changes_nothing(): void
    {
        $edge = $this->createEdge(['dismissed_at' => now()->subHour()]);

        Edge::find($edge->id)->snooze('1year');

        $fresh = Edge::find($edge->id);
        $this->assertNotNull($fresh->dismissed_at);
        $this->assertNull($fresh->snoozed_until);
    }

    public function test_scope_for_matches_user_edge_key_and_subject_id_only(): void
    {
        $otherUser = User::factory()->create(['status' => 'active']);
        $edge = $this->createEdge();
        $this->createEdge(['user_id' => $otherUser->id]);
        $this->createEdge(['edge_key' => 'edge-model-test:other', 'subject_id' => '71']);
        $this->createEdge(['subject_id' => '72']);

        $found = Edge::query()->for($this->user->id, 'edge-model-test:edge', '71')->get();

        $this->assertSame(1, $found->count());
        $this->assertSame($edge->id, $found->first()->id);
    }

    public function test_scope_for_accepts_int_subject_id(): void
    {
        $this->createEdge();

        $this->assertSame(1, Edge::query()->for($this->user->id, 'edge-model-test:edge', 71)->count());
    }

    public function test_scope_matching_subject_ignores_user_and_other_keys(): void
    {
        $otherUser = User::factory()->create(['status' => 'active']);
        $edge = $this->createEdge();
        $this->createEdge(['subject_id' => '72']);
        $this->createEdge(['edge_key' => 'edge-model-test:other']);
        $this->createEdge(['user_id' => $otherUser->id, 'subject_type' => 'App\\Models\\ChannelMessage']);

        $found = Edge::query()->matchingSubject('edge-model-test:edge', 'App\\Models\\Task', '71')->get();

        $this->assertSame(1, $found->count());
        $this->assertSame($edge->id, $found->first()->id);
    }

    public function test_scope_visible_hides_dismissed_and_future_snoozed_keeps_expired_snooze(): void
    {
        $live = $this->createEdge(['subject_id' => 'live']);
        $dismissed = $this->createEdge(['subject_id' => 'dismissed', 'dismissed_at' => now()->subMinute()]);
        $snoozed = $this->createEdge(['subject_id' => 'snoozed', 'snoozed_until' => now()->addMinute()]);
        $expired = $this->createEdge(['subject_id' => 'expired', 'snoozed_until' => now()->subMinute()]);
        $this->createEdge(['subject_id' => 'both', 'dismissed_at' => now()->subMinute(), 'snoozed_until' => now()->addMinute()]);

        $visible = Edge::query()->visible()->where('user_id', $this->user->id)->get()->keyBy('subject_id');

        $this->assertTrue($visible->has($live->subject_id));
        $this->assertTrue($visible->has($expired->subject_id));
        $this->assertFalse($visible->has($dismissed->subject_id));
        $this->assertFalse($visible->has($snoozed->subject_id));
    }

    public function test_scope_visible_hides_dismissed_row_even_when_snooze_is_expired(): void
    {
        $this->createEdge(['dismissed_at' => now()->subDay(), 'snoozed_until' => now()->subDay()]);

        $this->assertSame(0, Edge::query()->visible()->where('user_id', $this->user->id)->count());
    }
}