<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Pulse;
use App\Models\Reminder;
use App\Models\User;
use App\Services\Menu\PulseService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PulseTest extends TestCase
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

    private function activeUser(): User
    {
        return User::factory()->create(['status' => 'active']);
    }

    public function test_mount_renders_the_alpine_leader_hook_with_the_starting_fingerprint_and_no_wire_poll(): void
    {
        $user = $this->activeUser();

        Livewire::actingAs($user)->test(Pulse::class)
            ->assertSeeHtml('x-data="pulse(')
            ->assertDontSeeHtml('wire:poll')
            ->assertSet('fingerprint', PulseService::fingerprint($user));
    }

    public function test_a_tick_with_nothing_changed_returns_no_events_and_the_same_fingerprint(): void
    {
        $user = $this->activeUser();
        $known = PulseService::fingerprint($user);

        Livewire::actingAs($user)->test(Pulse::class)
            ->call('pulse', $known)
            ->assertReturned(['events' => [], 'fingerprint' => $known]);
    }

    public function test_a_tick_returns_only_the_event_of_the_part_that_changed_and_then_settles(): void
    {
        $user = $this->activeUser();
        $known = PulseService::fingerprint($user);
        $component = Livewire::actingAs($user)->test(Pulse::class);

        Reminder::create(['user_id' => $user->id, 'title' => 'pulse', 'due_at' => now(), 'recurs' => 'none', 'channels' => ['inapp:badge']]);
        $fresh = PulseService::fingerprint($user);

        $component->call('pulse', $known)->assertReturned(['events' => ['reminder-changed', 'home-stats-changed'], 'fingerprint' => $fresh]);
        $component->call('pulse', $fresh)->assertReturned(['events' => [], 'fingerprint' => $fresh]);
    }

    public function test_a_tampered_known_fingerprint_only_causes_extra_refresh_events_never_an_error(): void
    {
        $user = $this->activeUser();

        Livewire::actingAs($user)->test(Pulse::class)
            ->call('pulse', ['reminders' => ['x'], 'edges' => null])
            ->assertHasNoErrors()
            ->assertReturned(fn (array $r) => in_array('reminder-changed', $r['events'], true));
    }

    public function test_a_tick_without_a_session_user_returns_nothing(): void
    {
        Livewire::test(Pulse::class)
            ->call('pulse', [])
            ->assertReturned(['events' => [], 'fingerprint' => []]);
    }
}
