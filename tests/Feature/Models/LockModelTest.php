<?php

namespace Tests\Feature\Models;

use App\Models\Channel;
use App\Models\Lock;
use App\Models\Profile;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Support\LockPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDOException;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class LockModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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

    private function lock(string $module, int $recordId, string $slot): Lock
    {
        return Lock::create(['lockable_type' => $module, 'lockable_id' => $recordId, 'slot' => $slot, 'secret_hash' => Hash::make('some-secret-1')]);
    }

    public function test_slot_for_resolves_the_slot_from_who_is_asking_and_allow_lists_a_developer_request(): void
    {
        $this->assertNull(Lock::slotFor(null));
        $this->assertNull(Lock::slotFor($this->person()));
        $this->assertSame(Lock::SLOT_CEO, Lock::slotFor($this->person('user', 'ceo'), Lock::SLOT_MASTER));
        $this->assertSame(Lock::SLOT_CHAIRMAN, Lock::slotFor($this->person('user', 'chairman')));

        $developer = $this->person('developer');
        $this->assertSame(Lock::SLOT_MASTER, Lock::slotFor($developer));
        $this->assertSame(Lock::SLOT_CEO, Lock::slotFor($developer, Lock::SLOT_CEO));
        $this->assertSame(Lock::SLOT_MASTER, Lock::slotFor($developer, 'bogus'));
        $this->assertSame(Lock::SLOT_MASTER, Lock::slotFor($developer, Lock::SLOT_OWNER));
        $this->assertNotContains(Lock::SLOT_OWNER, Lock::SLOTS);
    }

    public function test_module_and_record_maps_are_separate_and_carry_fingerprints_never_hashes(): void
    {
        $module = $this->lock('contact', 0, Lock::SLOT_CEO);
        $record = $this->lock('channel', 12, Lock::SLOT_OWNER);

        $modules = Lock::moduleMap();
        $records = Lock::recordMap();

        $this->assertSame([$module->id => $module->fingerprint()], $modules['contact']);
        $this->assertArrayNotHasKey('channel', $modules);
        $this->assertSame([$record->id => $record->fingerprint()], $records['channel'][12]);
        $this->assertArrayNotHasKey('contact', $records);
        $this->assertSame(16, strlen($module->fingerprint()));
        $this->assertStringNotContainsString($module->secret_hash, json_encode([$modules, $records]));
        $this->assertArrayNotHasKey('secret_hash', $module->toArray());
    }

    public function test_saving_and_deleting_a_lock_refreshes_the_cached_maps_and_the_policy_memo(): void
    {
        $this->assertSame([], Lock::moduleMap());

        $lock = $this->lock('contact', 0, Lock::SLOT_CEO);
        $this->assertArrayHasKey('contact', Lock::moduleMap());

        $before = $lock->fingerprint();
        $lock->update(['secret_hash' => Hash::make('another-secret-2')]);
        $this->assertNotSame($before, $lock->fingerprint());
        $this->assertSame($lock->fingerprint(), Lock::moduleMap()['contact'][$lock->id]);

        $lock->delete();
        $this->assertSame([], Lock::moduleMap());
    }

    public function test_the_fingerprint_changes_with_the_hash_and_one_owner_row_exists_per_channel(): void
    {
        $first = $this->lock('channel', 5, Lock::SLOT_OWNER);
        $second = $first->replicate();
        $second->secret_hash = Hash::make('different-secret-3');

        $this->assertNotSame($first->fingerprint(), $second->fingerprint());

        $this->expectException(QueryException::class);
        $second->save();
    }

    public function test_owns_record_is_true_only_for_the_live_owner_of_that_channel(): void
    {
        $owner = $this->person();
        $other = $this->person();
        $channel = Channel::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue(Lock::ownsRecord($owner, 'channel', $channel->id));
        $this->assertFalse(Lock::ownsRecord($other, 'channel', $channel->id));
        $this->assertFalse(Lock::ownsRecord(null, 'channel', $channel->id));
        $this->assertFalse(Lock::ownsRecord($owner, 'contact', $channel->id));
        $this->assertFalse(Lock::ownsRecord($owner, 'channel', 0));

        $channel->delete();
        $this->assertFalse(Lock::ownsRecord($owner, 'channel', $channel->id));
    }

    public function test_only_a_missing_table_reads_as_no_locks_and_every_other_query_failure_propagates(): void
    {
        $method = new ReflectionMethod(Lock::class, 'mapOrEmpty');

        $failing = function (string $state): callable {
            return function () use ($state): array {
                $previous = new PDOException('boom');
                (new ReflectionProperty($previous, 'code'))->setValue($previous, $state);

                throw new QueryException('mysql', 'select 1', [], $previous);
            };
        };

        $this->assertSame([], $method->invoke(null, 'missing-table-probe', $failing(Lock::MISSING_TABLE_STATE)));

        $this->expectException(QueryException::class);
        $method->invoke(null, 'other-failure-probe', $failing('42000'));
    }
}
