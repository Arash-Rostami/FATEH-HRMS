<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Enums\PresenceStatus;
use App\Enums\UserType;
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

    public function test_a_presence_change_or_an_online_flip_fires_presence_changed_but_a_repeat_touch_does_not(): void
    {
        $user = User::factory()->create(['status' => 'active', 'last_seen' => null, 'presence' => PresenceStatus::Onsite->value]);
        $component = Livewire::actingAs($user)->test(Pulse::class);
        $known = PulseService::fingerprint($user);

        $user->touchLastSeen();
        $component->call('pulse', $known)->assertReturned(fn (array $r) => in_array('presence-changed', $r['events'], true));

        $known = PulseService::fingerprint($user);
        $user->touchLastSeen();
        $component->call('pulse', $known)->assertReturned(fn (array $r) => !in_array('presence-changed', $r['events'], true));

        $user->update(['presence' => PresenceStatus::Remote]);
        $component->call('pulse', $known)->assertReturned(fn (array $r) => in_array('presence-changed', $r['events'], true)
            && ($r['presence']['p'][$user->id] ?? null) === 'remote'
            && in_array($user->id, $r['presence']['on'], true));
    }

    public function test_guests_and_inactive_users_never_move_the_presence_part_or_enter_the_presence_map(): void
    {
        $viewer = $this->activeUser();
        $guest = User::factory()->create(['status' => 'active', 'type' => UserType::Guest->value, 'last_seen' => null]);
        $inactive = User::factory()->create(['status' => 'inactive', 'last_seen' => null]);
        $known = PulseService::fingerprint($viewer);

        $guest->touchLastSeen();
        $inactive->touchLastSeen();
        $guest->update(['presence' => PresenceStatus::Remote]);
        $stamp = PulseService::fingerprint($viewer)['presence'];
        $map = PulseService::presenceMap($stamp . '|' . uniqid('zz'));

        $this->assertSame(strstr($known['presence'], '|', true), strstr($stamp, '|', true));
        $this->assertNotContains($guest->id, $map['on']);
        $this->assertNotContains($inactive->id, $map['on']);
        $this->assertArrayNotHasKey($guest->id, $map['p']);
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
