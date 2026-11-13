<?php

namespace Tests\Feature\Support;

use App\Models\Channel;
use App\Models\Lock;
use App\Models\Profile;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Support\LockPolicy;
use Collator;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LockPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['locks.disabled' => false]);
        $this->useMysql();
        DB::beginTransaction();
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

    private function lock(string $slot = Lock::SLOT_CEO, string $code = 'secret-code', string $module = 'contact'): Lock
    {
        return $this->lockFor(0, $slot, $code, $module);
    }

    private function recordLock(int $recordId, string $code = 'record-secret', string $module = 'channel'): Lock
    {
        return $this->lockFor($recordId, Lock::SLOT_OWNER, $code, $module);
    }

    private function lockFor(int $recordId, string $slot, string $code, string $module): Lock
    {
        return Lock::create([
            'lockable_type' => $module,
            'lockable_id' => $recordId,
            'slot' => $slot,
            'secret_hash' => Hash::make($code),
        ]);
    }

    public function test_a_module_without_any_key_stays_open(): void
    {
        $this->actingAs($this->person());

        $this->assertFalse(LockPolicy::blocks('contact'));
    }

    public function test_any_single_key_slot_locks_the_module_for_an_ordinary_admin(): void
    {
        $this->actingAs($this->person());

        foreach ([Lock::SLOT_MASTER, Lock::SLOT_CEO, Lock::SLOT_CHAIRMAN] as $slot) {
            Lock::query()->delete();
            $this->lock($slot);
            LockPolicy::flush();

            $this->assertTrue(LockPolicy::blocks('contact'), $slot);
            $this->assertFalse(LockPolicy::blocks('channel'), $slot);
        }
    }

    public function test_developer_chairman_and_ceo_bypass_every_lock(): void
    {
        $this->lock();

        foreach ([$this->person('developer'), $this->person('user', 'ceo'), $this->person('user', 'chairman')] as $holder) {
            LockPolicy::flush();
            $this->actingAs($holder);

            $this->assertFalse(LockPolicy::blocks('contact'));
            $this->assertTrue(LockPolicy::isHolder($holder));
        }
    }

    public function test_a_session_unlock_ends_when_that_slot_code_changes_but_not_when_another_slot_changes(): void
    {
        $ceo = $this->lock(Lock::SLOT_CEO);
        $chairman = $this->lock(Lock::SLOT_CHAIRMAN, 'other-secret');
        $this->actingAs($this->person());

        LockPolicy::remember('contact', $ceo);
        $this->assertFalse(LockPolicy::blocks('contact'));

        $chairman->update(['secret_hash' => Hash::make('rotated-secret')]);
        $this->assertFalse(LockPolicy::blocks('contact'));

        $ceo->update(['secret_hash' => Hash::make('rotated-ceo-secret')]);
        $this->assertTrue(LockPolicy::blocks('contact'));
    }

    public function test_an_unlock_grant_never_transfers_to_another_account_in_the_same_session(): void
    {
        $lock = $this->lock();
        $first = $this->person();
        $second = $this->person();

        $this->actingAs($first);
        LockPolicy::remember('contact', $lock);
        $this->assertFalse(LockPolicy::blocks('contact'));

        $this->actingAs($second);
        LockPolicy::flush();
        $this->assertTrue(LockPolicy::blocks('contact'));
    }

    public function test_manages_lets_a_developer_touch_any_slot_and_a_top_executive_only_their_own(): void
    {
        $developer = $this->person('developer');
        $ceo = $this->person('user', 'ceo');
        $chairman = $this->person('user', 'chairman');
        $admin = $this->person();

        foreach (Lock::SLOTS as $slot) {
            $this->assertTrue(LockPolicy::manages($developer, $slot));
            $this->assertFalse(LockPolicy::manages($admin, $slot));
            $this->assertFalse(LockPolicy::manages(null, $slot));
        }

        $this->assertTrue(LockPolicy::manages($ceo, Lock::SLOT_CEO));
        $this->assertFalse(LockPolicy::manages($ceo, Lock::SLOT_MASTER));
        $this->assertFalse(LockPolicy::manages($ceo, Lock::SLOT_CHAIRMAN));
        $this->assertTrue(LockPolicy::manages($chairman, Lock::SLOT_CHAIRMAN));
        $this->assertFalse(LockPolicy::manages($chairman, Lock::SLOT_CEO));
    }

    public function test_a_developer_cannot_write_a_slot_outside_the_allow_list(): void
    {
        $this->assertSame(Lock::SLOT_MASTER, Lock::slotFor($this->person('developer'), 'bogus'));
        $this->assertSame(Lock::SLOT_CEO, Lock::slotFor($this->person('developer'), Lock::SLOT_CEO));
        $this->assertNull(Lock::slotFor($this->person()));
    }

    public function test_kill_switch_opens_everything(): void
    {
        $this->lock();
        $this->actingAs($this->person());
        config(['locks.disabled' => true]);

        $this->assertFalse(LockPolicy::blocks('contact'));
    }

    public function test_the_bcrypt_hash_never_reaches_the_cache_or_array_output(): void
    {
        $lock = $this->lock();

        $this->assertArrayNotHasKey('secret_hash', $lock->toArray());

        $map = Lock::moduleMap();
        $fingerprint = $map['contact'][$lock->id];

        $this->assertSame(16, strlen($fingerprint));
        $this->assertStringNotContainsString($lock->secret_hash, json_encode($map));
    }

    public function test_lockable_modules_exclude_the_permission_screen(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $modules = LockPolicy::lockableModules();

        $this->assertArrayHasKey('contact', $modules);
        $this->assertArrayNotHasKey('permission', $modules);

        $labels = array_values($modules);
        $sorted = $labels;
        (new Collator('fa_IR'))->sort($sorted);

        $this->assertSame($sorted, $labels);
    }

    public function test_a_locked_channel_record_blocks_only_non_owners(): void
    {
        $owner = $this->person();
        $ceo = $this->person('user', 'ceo');
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);
        $this->recordLock($channel->id);
        $this->actingAs($this->person());

        $this->assertTrue(LockPolicy::blocksRecord('channel', $channel));
        $this->assertSame([$channel->id], LockPolicy::lockedRecordIds('channel'));

        $this->actingAs($owner);
        $this->assertFalse(LockPolicy::blocksRecord('channel', $channel));
        $this->assertSame([$channel->id], LockPolicy::lockedRecordIds('channel'));

        $this->actingAs($ceo);
        $this->assertFalse(LockPolicy::blocksRecord('channel', $channel));
        $this->assertSame([], LockPolicy::lockedRecordIds('channel'));
    }
}
