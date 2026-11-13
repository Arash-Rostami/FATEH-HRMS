<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\ManageLocks;
use App\Models\Channel;
use App\Models\Lock;
use App\Models\Profile;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Support\LockPolicy;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManageLocksTest extends TestCase
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

    private function person(string $role, ?string $position = null): User
    {
        $user = User::factory()->create(['status' => 'active', 'role' => $role]);

        if ($position !== null) {
            Profile::factory()->create(['user_id' => $user->id, 'position' => $position]);
        }

        return User::find($user->id);
    }

    private function lock(string $module, string $slot): Lock
    {
        return Lock::create(['lockable_type' => $module, 'lockable_id' => 0, 'slot' => $slot, 'secret_hash' => Hash::make('some-secret-1')]);
    }

    public function test_a_holder_sees_only_their_own_slot_and_a_developer_sees_all(): void
    {
        $ceo = $this->person('user', 'ceo');
        $developer = $this->person('developer');
        $chairman = $this->person('user', 'chairman');
        $ceoRow = $this->lock('contact', Lock::SLOT_CEO);
        $chairmanRow = $this->lock('channel', Lock::SLOT_CHAIRMAN);
        $masterRow = $this->lock('ths', Lock::SLOT_MASTER);

        Livewire::actingAs($ceo)->test(ManageLocks::class)
            ->assertCanSeeTableRecords([$ceoRow])
            ->assertCanNotSeeTableRecords([$chairmanRow, $masterRow]);

        Livewire::actingAs($chairman)->test(ManageLocks::class)
            ->assertCanSeeTableRecords([$chairmanRow])
            ->assertCanNotSeeTableRecords([$ceoRow, $masterRow]);

        Livewire::actingAs($developer)->test(ManageLocks::class)
            ->assertCanSeeTableRecords([$masterRow])
            ->assertCanNotSeeTableRecords([$ceoRow, $chairmanRow])
            ->filterTable('slot', Lock::SLOT_CEO)
            ->assertCanSeeTableRecords([$ceoRow])
            ->assertCanNotSeeTableRecords([$masterRow, $chairmanRow])
            ->removeTableFilter('slot')
            ->assertCanSeeTableRecords([$ceoRow, $chairmanRow, $masterRow]);
    }

    public function test_bulk_change_skips_a_stale_module_row_instead_of_failing_the_whole_batch(): void
    {
        $developer = $this->person('developer');
        $good = $this->lock('contact', Lock::SLOT_MASTER);
        $stale = $this->lock('not_a_module', Lock::SLOT_MASTER);

        Livewire::actingAs($developer)->test(ManageLocks::class)
            ->callTableBulkAction('changeMany', [$good, $stale], ['code' => 'rotated-key-1', 'code_confirmation' => 'rotated-key-1'])
            ->assertHasNoTableBulkActionErrors();

        $this->assertTrue(Hash::check('rotated-key-1', $good->fresh()->secret_hash));
        $this->assertTrue(Hash::check('some-secret-1', $stale->fresh()->secret_hash));
    }

    public function test_a_holder_changes_or_clears_many_of_their_own_keys_at_once_and_never_anyone_elses(): void
    {
        $ceo = $this->person('user', 'ceo');
        $mineA = $this->lock('contact', Lock::SLOT_CEO);
        $mineB = $this->lock('channel', Lock::SLOT_CEO);
        $theirs = $this->lock('ths', Lock::SLOT_CHAIRMAN);

        Livewire::actingAs($ceo)->test(ManageLocks::class)
            ->callTableBulkAction('changeMany', [$mineA, $mineB], ['code' => 'rotated-key-1', 'code_confirmation' => 'rotated-key-1']);

        $this->assertTrue(Hash::check('rotated-key-1', $mineA->fresh()->secret_hash));
        $this->assertTrue(Hash::check('rotated-key-1', $mineB->fresh()->secret_hash));
        $this->assertTrue(Hash::check('some-secret-1', $theirs->fresh()->secret_hash));

        Livewire::actingAs($ceo)->test(ManageLocks::class)->callTableBulkAction('clearMany', [$mineA, $mineB]);

        $this->assertSame(0, Lock::where('slot', Lock::SLOT_CEO)->count());
        $this->assertSame(1, Lock::where('slot', Lock::SLOT_CHAIRMAN)->count());
    }

    public function test_the_table_search_matches_the_persian_module_label_and_the_module_key(): void
    {
        $developer = $this->person('developer');
        $contactRow = $this->lock('contact', Lock::SLOT_MASTER);
        $channelRow = $this->lock('channel', Lock::SLOT_MASTER);
        $label = LockPolicy::lockableModules()['contact'];

        Livewire::actingAs($developer)->test(ManageLocks::class)
            ->searchTable($label)
            ->assertCanSeeTableRecords([$contactRow])
            ->assertCanNotSeeTableRecords([$channelRow])
            ->searchTable('channel')
            ->assertCanSeeTableRecords([$channelRow])
            ->assertCanNotSeeTableRecords([$contactRow]);
    }

    public function test_the_set_form_never_offers_a_module_the_holder_already_locked(): void
    {
        $ceo = $this->person('user', 'ceo');
        $this->lock('contact', Lock::SLOT_CEO);

        $component = Livewire::actingAs($ceo)->test(ManageLocks::class);

        $component->callAction('set', ['modules' => ['contact'], 'code' => 'fresh-key-123', 'code_confirmation' => 'fresh-key-123'])
            ->assertHasActionErrors(['modules.0']);

        Livewire::actingAs($ceo)->test(ManageLocks::class)->callAction('set', ['modules' => ['channel'], 'code' => 'fresh-key-123', 'code_confirmation' => 'fresh-key-123'])
            ->assertHasNoActionErrors();

        $this->assertSame(1, Lock::where('lockable_type', 'channel')->where('slot', Lock::SLOT_CEO)->count());
        $this->assertSame(1, Lock::where('lockable_type', 'contact')->where('slot', Lock::SLOT_CEO)->count());
    }

    public function test_a_key_set_by_another_holder_never_narrows_the_set_form_of_the_others(): void
    {
        $ceo = $this->person('user', 'ceo');
        $chairman = $this->person('user', 'chairman');
        $this->lock('channel', Lock::SLOT_MASTER);

        foreach ([[$ceo, Lock::SLOT_CEO], [$chairman, Lock::SLOT_CHAIRMAN]] as [$holder, $slot]) {
            Livewire::actingAs($holder)->test(ManageLocks::class)
                ->callAction('set', ['modules' => ['channel'], 'code' => 'own-key-1234', 'code_confirmation' => 'own-key-1234'])
                ->assertHasNoActionErrors();

            $this->assertSame(1, Lock::where('lockable_type', 'channel')->where('slot', $slot)->count());
        }

        $this->assertSame(3, Lock::where('lockable_type', 'channel')->count());
    }

    public function test_a_developer_sees_channel_keys_with_group_and_owner_names_filters_by_owner_and_clears_one_while_holders_never_see_them(): void
    {
        $developer = $this->person('developer');
        $ceo = $this->person('user', 'ceo');
        $ownerA = $this->person('user');
        $ownerB = $this->person('user');
        $channelA = Channel::factory()->create(['owner_id' => $ownerA->id, 'name' => 'group-a-keyed']);
        $channelB = Channel::factory()->create(['owner_id' => $ownerB->id, 'name' => 'group-b-keyed']);
        $rowA = Lock::create(['lockable_type' => 'channel', 'lockable_id' => $channelA->id, 'slot' => Lock::SLOT_OWNER, 'secret_hash' => Hash::make('owner-key-111')]);
        $rowB = Lock::create(['lockable_type' => 'channel', 'lockable_id' => $channelB->id, 'slot' => Lock::SLOT_OWNER, 'secret_hash' => Hash::make('owner-key-222')]);
        $masterRow = $this->lock('contact', Lock::SLOT_MASTER);

        Livewire::actingAs($developer)->test(ManageLocks::class)
            ->assertCanSeeTableRecords([$masterRow])
            ->assertCanNotSeeTableRecords([$rowA, $rowB])
            ->filterTable('slot', Lock::SLOT_OWNER)
            ->assertCanSeeTableRecords([$rowA, $rowB])
            ->assertTableColumnStateSet('channel.name', 'group-a-keyed', $rowA)
            ->assertTableColumnStateSet('channel.owner.name', $ownerA->name, $rowA)
            ->filterTable('channel_owner', $ownerA->id)
            ->assertCanSeeTableRecords([$rowA])
            ->assertCanNotSeeTableRecords([$rowB, $masterRow])
            ->callTableAction('clear', $rowA);

        $this->assertNull(Lock::find($rowA->id));
        $this->assertNotNull(Lock::find($rowB->id));

        Livewire::actingAs($ceo)->test(ManageLocks::class)
            ->assertCanNotSeeTableRecords([$rowB]);
    }

    public function test_a_non_holder_gets_a_404_and_a_demoted_holder_loses_the_open_page(): void
    {
        $admin = $this->person('admin');
        $ceo = $this->person('user', 'ceo');

        Livewire::actingAs($admin)->test(ManageLocks::class)->assertNotFound();

        $component = Livewire::actingAs($ceo)->test(ManageLocks::class)->assertSuccessful();

        DB::table('profiles')->where('user_id', $ceo->id)->update(['position' => 'employee']);
        $this->actingAs(User::find($ceo->id));
        LockPolicy::flush();

        $component->call('$refresh')->assertNotFound();
    }
}
