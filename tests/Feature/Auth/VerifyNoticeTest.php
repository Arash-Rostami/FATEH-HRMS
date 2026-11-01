<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\VerifyNotice;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class VerifyNoticeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(VerifyNotice::class)
            ->assertOk()
            ->assertHasNoErrors();
    }

    public function test_logout_logs_out_and_redirects_to_login(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(VerifyNotice::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_logout_backdates_last_seen_out_of_online_window_and_leaves_presence_untouched(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'last_seen' => now()]);
        Cache::put('last_seen:' . $user->id, true, now()->addMinutes(5));

        Livewire::actingAs($user)
            ->test(VerifyNotice::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $fresh = $user->fresh();
        $this->assertTrue($fresh->last_seen->lt(User::onlineSince()));
        $this->assertFalse($fresh->isOnline());
        $this->assertFalse(Cache::has('last_seen:' . $user->id));
        $this->assertSame('onsite', $fresh->presence->value);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $browser = Livewire::actingAs($user)->test(VerifyNotice::class);

        session()->put('verify-notice-marker', 'sentinel');
        $this->assertTrue(session()->has('verify-notice-marker'));

        $browser->call('logout')->assertRedirect(route('login'));

        $this->assertFalse(session()->has('verify-notice-marker'));
    }
}