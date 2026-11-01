<?php

namespace Tests\Feature\Models;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventShareModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::forget('menu_state:version');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget('menu_state:version');
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

    public function test_menu_state_events_constant_is_created_and_deleted_only(): void
    {
        $this->assertSame(['created', 'deleted'], EventShare::MENU_STATE_EVENTS);
    }

    public function test_has_imminent_for_returns_true_when_shared_event_within_one_day(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $sharer = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $sharer->id,
            'date' => now()->addHours(12),
            'private' => false,
        ]);
        EventShare::create([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'shared_by' => $sharer->id,
        ]);

        $this->assertTrue(EventShare::hasImminentFor($recipient));
    }

    public function test_has_imminent_for_returns_false_when_shared_event_outside_window(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $sharer = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $sharer->id,
            'date' => now()->addDays(5),
            'private' => false,
        ]);
        EventShare::create([
            'event_id' => $event->id,
            'user_id' => $recipient->id,
            'shared_by' => $sharer->id,
        ]);

        $this->assertFalse(EventShare::hasImminentFor($recipient));
    }

    public function test_has_imminent_for_returns_false_when_no_share_exists(): void
    {
        $recipient = User::factory()->create(['status' => 'active']);
        $sharer = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $sharer->id,
            'date' => now()->addHours(12),
            'private' => false,
        ]);

        $this->assertFalse(EventShare::hasImminentFor($recipient));
    }

    public function test_has_imminent_for_scopes_to_recipient_user_only(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $sharer = User::factory()->create(['status' => 'active']);
        $event = Event::factory()->create([
            'user_id' => $sharer->id,
            'date' => now()->addHours(12),
            'private' => false,
        ]);
        EventShare::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'shared_by' => $sharer->id,
        ]);

        $this->assertTrue(EventShare::hasImminentFor($owner));
        $this->assertFalse(EventShare::hasImminentFor($other));
    }
}