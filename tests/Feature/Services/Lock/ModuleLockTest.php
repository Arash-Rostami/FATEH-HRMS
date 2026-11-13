<?php

namespace Tests\Feature\Services\Lock;

use App\Models\Channel;
use App\Models\Lock;
use App\Models\Profile;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Lock\ClearModuleLock;
use App\Services\Lock\SetModuleLock;
use App\Services\Lock\UnlockModule;
use App\Support\LockPolicy;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ModuleLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['locks.disabled' => false]);
        $this->useMysql();
        DB::beginTransaction();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Lock::query()->delete();
        ModelCacheVersion::bump(Lock::class);
        LockPolicy::flush();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(Lock::class);
        LockPolicy::flush();
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

    private function person(string $role = 'admin', ?string $position = null): User
    {
        $user = User::factory()->create(['status' => 'active', 'role' => $role]);

        if ($position !== null) {
            Profile::factory()->create(['user_id' => $user->id, 'position' => $position]);
        }

        return User::find($user->id);
    }

    private function statusOf(callable $call): ?int
    {
        try {
            $call();
        } catch (HttpException $e) {
            return $e->getStatusCode();
        }

        return null;
    }

    public function test_a_holder_stores_only_a_hash_in_their_own_slot(): void
    {
        $ceo = $this->person('user', 'ceo');

        $lock = app(SetModuleLock::class)->handle($ceo, 'contact', 'plain-secret');

        $this->assertSame(Lock::SLOT_CEO, $lock->slot);
        $this->assertNotSame('plain-secret', $lock->secret_hash);
        $this->assertTrue(Hash::check('plain-secret', $lock->secret_hash));
    }

    public function test_a_developer_may_set_any_slot_but_a_ceo_cannot_reach_the_master_slot(): void
    {
        $developer = $this->person('developer');
        $ceo = $this->person('user', 'ceo');

        app(SetModuleLock::class)->handle($developer, 'contact', 'master-secret', Lock::SLOT_MASTER);
        app(SetModuleLock::class)->handle($ceo, 'contact', 'ceo-secret-1', Lock::SLOT_MASTER);

        $this->assertDatabaseHas('locks', ['lockable_type' => 'contact', 'slot' => Lock::SLOT_MASTER]);
        $this->assertDatabaseHas('locks', ['lockable_type' => 'contact', 'slot' => Lock::SLOT_CEO]);
        $this->assertSame(2, Lock::where('lockable_type', 'contact')->count());
    }

    public function test_a_non_holder_gets_a_404_and_nothing_is_written(): void
    {
        $admin = $this->person();

        $status = $this->statusOf(fn () => app(SetModuleLock::class)->handle($admin, 'contact', 'plain-secret'));

        $this->assertSame(404, $status);
        $this->assertSame(0, Lock::count());
    }

    public function test_clear_removes_only_the_actors_own_slot(): void
    {
        $developer = $this->person('developer');
        $ceo = $this->person('user', 'ceo');
        app(SetModuleLock::class)->handle($developer, 'contact', 'master-secret', Lock::SLOT_MASTER);
        app(SetModuleLock::class)->handle($ceo, 'contact', 'ceo-secret-1');

        app(ClearModuleLock::class)->handle($ceo, 'contact', Lock::SLOT_MASTER);

        $this->assertDatabaseMissing('locks', ['lockable_type' => 'contact', 'slot' => Lock::SLOT_CEO]);
        $this->assertDatabaseHas('locks', ['lockable_type' => 'contact', 'slot' => Lock::SLOT_MASTER]);
    }

    public function test_any_slot_code_unlocks_and_a_wrong_code_does_not(): void
    {
        app(SetModuleLock::class)->handle($this->person('user', 'ceo'), 'contact', 'ceo-secret-1');
        app(SetModuleLock::class)->handle($this->person('user', 'chairman'), 'contact', 'chair-secret-1');
        $admin = $this->person();
        $this->actingAs($admin);

        $this->assertFalse(app(UnlockModule::class)->handle($admin, 'contact', 'nope-nope-1'));
        $this->assertTrue(LockPolicy::blocks('contact'));

        $this->assertTrue(app(UnlockModule::class)->handle($admin, 'contact', 'chair-secret-1'));
        $this->assertFalse(LockPolicy::blocks('contact'));
    }

    public function test_the_sixth_attempt_is_throttled_and_logs_never_contain_the_code(): void
    {
        app(SetModuleLock::class)->handle($this->person('user', 'ceo'), 'contact', 'ceo-secret-1');
        $admin = $this->person();
        $this->actingAs($admin);
        Log::spy();

        foreach (range(1, 5) as $_) {
            $this->assertFalse(app(UnlockModule::class)->handle($admin, 'contact', 'wrong-code-9'));
        }

        $this->assertSame(429, $this->statusOf(fn () => app(UnlockModule::class)->handle($admin, 'contact', 'ceo-secret-1')));
        Log::shouldHaveReceived('warning')->with('module unlock failed', Mockery::on(fn (array $context): bool => !str_contains(json_encode($context), 'wrong-code-9')))->times(5);
    }

    public function test_failures_share_one_budget_across_modules_and_successes_cost_nothing(): void
    {
        $ceo = $this->person('user', 'ceo');
        app(SetModuleLock::class)->handle($ceo, 'contact', 'ceo-secret-1');
        app(SetModuleLock::class)->handle($ceo, 'channel', 'ceo-secret-1');
        $admin = $this->person();
        $this->actingAs($admin);

        foreach (range(1, 4) as $_) {
            $this->assertFalse(app(UnlockModule::class)->handle($admin, 'contact', 'wrong-code-9'));
        }

        foreach (range(1, 3) as $_) {
            $this->assertTrue(app(UnlockModule::class)->handle($admin, 'channel', 'ceo-secret-1'));
        }

        $this->assertFalse(app(UnlockModule::class)->handle($admin, 'contact', 'wrong-code-9'));
        $this->assertSame(429, $this->statusOf(fn () => app(UnlockModule::class)->handle($admin, 'channel', 'ceo-secret-1')));
    }

    public function test_unlocking_a_module_without_any_key_succeeds_and_never_burns_the_budget(): void
    {
        $admin = $this->person();
        $this->actingAs($admin);

        foreach (range(1, 8) as $_) {
            $this->assertTrue(app(UnlockModule::class)->handle($admin, 'contact', 'anything-at-all'));
        }

        $this->assertSame(0, Lock::count());
    }

    public function test_one_key_is_set_on_many_modules_at_once_with_a_single_hash(): void
    {
        $ceo = $this->person('user', 'ceo');

        $locks = app(SetModuleLock::class)->handleMany($ceo, ['contact', 'channel', 'ths', 'contact'], 'one-key-1234');

        $this->assertCount(3, $locks);
        $this->assertSame(3, Lock::where('slot', Lock::SLOT_CEO)->count());
        $this->assertCount(1, $locks->pluck('secret_hash')->unique());

        $admin = $this->person();
        $this->actingAs($admin);

        foreach (['contact', 'channel', 'ths'] as $module) {
            $this->assertTrue(LockPolicy::blocks($module));
            $this->assertTrue(app(UnlockModule::class)->handle($admin, $module, 'one-key-1234'));
            $this->assertFalse(LockPolicy::blocks($module));
        }
    }

    public function test_a_batch_containing_an_unknown_module_writes_nothing(): void
    {
        $ceo = $this->person('user', 'ceo');

        $status = $this->statusOf(fn () => app(SetModuleLock::class)->handleMany($ceo, ['contact', 'not_a_module'], 'one-key-1234'));

        $this->assertSame(404, $status);
        $this->assertSame(0, Lock::count());
    }

    public function test_a_code_longer_than_the_bcrypt_limit_is_rejected_before_hashing(): void
    {
        $ceo = $this->person('user', 'ceo');

        try {
            app(SetModuleLock::class)->handle($ceo, 'contact', str_repeat('a', Lock::MAX_CODE_BYTES + 1));
            $this->fail('an oversized code must be rejected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }

        $this->assertSame(0, Lock::count());
    }

    public function test_a_tenant_off_or_unknown_module_cannot_be_locked_or_unlocked(): void
    {
        $developer = $this->person('developer');

        $this->assertSame(404, $this->statusOf(fn () => app(SetModuleLock::class)->handle($developer, 'not_a_module', 'plain-secret', Lock::SLOT_MASTER)));
        $this->assertSame(404, $this->statusOf(fn () => app(UnlockModule::class)->handle($developer, 'not_a_module', 'plain-secret')));
    }

    public function test_an_owner_locks_their_own_channel_and_a_non_owner_cannot(): void
    {
        $owner = $this->person();
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);

        $lock = app(SetModuleLock::class)->handleRecord($owner, 'channel', $channel->id, 'record-secret');

        $this->assertSame(Lock::SLOT_OWNER, $lock->slot);
        $this->assertSame($channel->id, $lock->lockable_id);
        $this->assertTrue(Hash::check('record-secret', $lock->secret_hash));

        $this->assertSame(404, $this->statusOf(fn () => app(SetModuleLock::class)->handleRecord($this->person(), 'channel', $channel->id, 'record-secret')));

        $this->assertSame(1, Lock::count());
    }

    public function test_a_record_code_unlocks_only_that_record(): void
    {
        $owner = $this->person();
        $locked = Channel::factory()->create(['owner_id' => $owner->id]);
        $open = Channel::factory()->create(['owner_id' => $owner->id]);
        app(SetModuleLock::class)->handleRecord($owner, 'channel', $locked->id, 'record-secret');

        $admin = $this->person();
        $this->actingAs($admin);

        $this->assertTrue(LockPolicy::blocksRecord('channel', $locked->fresh()));
        $this->assertFalse(LockPolicy::blocksRecord('channel', $open->fresh()));
        $this->assertFalse(LockPolicy::blocks('channel'));

        $this->assertTrue(app(UnlockModule::class)->handle($admin, 'channel', 'record-secret', $locked->id));
        $this->assertFalse(LockPolicy::blocksRecord('channel', $locked->fresh()));
    }

    public function test_a_record_unlock_is_refused_for_a_module_without_record_locks(): void
    {
        $owner = $this->person();
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $this->actingAs($this->person());

        $this->assertSame(404, $this->statusOf(fn () => app(UnlockModule::class)->handle($this->person(), 'contact', 'record-secret', $channel->id)));
    }
}
