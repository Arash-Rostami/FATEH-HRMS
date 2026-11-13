<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\UnlockModules;
use App\Models\Channel;
use App\Models\Lock;
use App\Models\Permission;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Support\LockPolicy;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class UnlockModulesTest extends TestCase
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

    private function admin(): User
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        Permission::create(['user_id' => $admin->id, 'is_super_admin' => true]);

        return $admin;
    }

    private function keyedChannel(string $name, string $code = 'owner-key-123'): array
    {
        $channel = Channel::factory()->create(['owner_id' => User::factory()->create()->id, 'name' => $name]);
        $lock = Lock::create(['lockable_type' => 'channel', 'lockable_id' => $channel->id, 'slot' => Lock::SLOT_OWNER, 'secret_hash' => Hash::make($code)]);
        LockPolicy::flush();

        return [$channel, $lock];
    }

    public function test_the_page_is_a_404_when_nothing_is_locked_for_the_admin(): void
    {
        Livewire::actingAs($this->admin())->test(UnlockModules::class)->assertNotFound();
    }

    public function test_a_locked_channel_is_listed_by_name_and_the_redirect_flag_shows_the_toast(): void
    {
        [$channel] = $this->keyedChannel('group-needing-a-key');
        $admin = $this->admin();

        $this->actingAs($admin);
        $options = (new ReflectionMethod(UnlockModules::class, 'lockedOptions'))->invoke(null);

        $this->assertSame([__('resources/lock/strings.unlock.record_label', ['name' => 'group-needing-a-key'])], array_values($options));
        $this->assertArrayHasKey("channel:{$channel->id}", $options);

        Livewire::actingAs($admin)->withQueryParams(['record' => $channel->id])->test(UnlockModules::class)
            ->assertNotified(__('resources/lock/strings.unlock.record_locked'))
            ->assertActionMounted('unlock')
            ->assertActionDataSet(['module' => "channel:{$channel->id}"]);
    }

    public function test_a_wrong_key_is_refused_and_the_right_key_grants_only_that_channel(): void
    {
        [$channel] = $this->keyedChannel('group-one');
        [$other] = $this->keyedChannel('group-two', 'other-key-456');
        $admin = $this->admin();

        $component = Livewire::actingAs($admin)->test(UnlockModules::class);

        $component->setActionData(['module' => "channel:{$channel->id}", 'code' => 'wrong-key-000'])->callMountedAction();
        $this->assertTrue(LockPolicy::blocksRecord('channel', $channel));

        $component->setActionData(['module' => "channel:{$channel->id}", 'code' => 'owner-key-123'])->callMountedAction();
        $this->assertFalse(LockPolicy::blocksRecord('channel', $channel));
        $this->assertTrue(LockPolicy::blocksRecord('channel', $other));
    }

    public function test_a_forged_option_cannot_unlock_a_record_that_is_not_offered(): void
    {
        $channel = Channel::factory()->create(['owner_id' => User::factory()->create()->id]);
        [$keyed] = $this->keyedChannel('group-keyed');

        Livewire::actingAs($this->admin())->test(UnlockModules::class)
            ->setActionData(['module' => "channel:{$channel->id}", 'code' => 'anything-at-all'])
            ->callMountedAction()
            ->assertHasActionErrors(['module']);

        $this->assertTrue(LockPolicy::blocksRecord('channel', $keyed));
    }
}
