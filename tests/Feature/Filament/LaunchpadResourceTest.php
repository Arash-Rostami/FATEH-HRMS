<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LaunchpadResource;
use App\Filament\Resources\LaunchpadResource\Pages\ListLaunchpads;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Services\Menu\Indicators\UnseenLaunchpads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LaunchpadResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        app()->setLocale('fa');
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

    private function developer(): User
    {
        $user = User::factory()->create(['role' => 'developer', 'status' => 'active']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    private function dept(string $code): string
    {
        Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => $code]);

        return $code;
    }

    private function deptUser(string $dept): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => $dept]);

        return $user;
    }

    private function row(User $owner, string $dept, array $overrides = []): Launchpad
    {
        $row = Launchpad::create(array_replace([
            'user_id' => $owner->id,
            'department_id' => $dept,
            'kind' => 'static',
            'active' => true,
            'title' => 'LP-' . Str::random(8),
            'folder' => (string) Str::ulid(),
        ], $overrides));
        Storage::disk('public')->put('launchpad/files/' . $row->folder . '/' . Launchpad::storedName('index.html'), '<html>ok</html>');

        return $row;
    }

    public function test_admin_sees_every_department_and_the_resource_is_monitoring_only(): void
    {
        Storage::fake('public');
        $admin = $this->developer();
        $deptA = $this->dept('LPRA');
        $deptB = $this->dept('LPRB');
        $ownerA = $this->deptUser($deptA);
        $ownerB = $this->deptUser($deptB);
        $this->row($ownerA, $deptA, ['title' => 'LP-admin-a']);
        $this->row($ownerB, $deptB, ['title' => 'LP-admin-b']);
        $this->row($ownerB, $deptB, ['title' => 'LP-admin-inactive', 'active' => false]);

        Livewire::actingAs($admin)->test(ListLaunchpads::class)
            ->assertSee('LP-admin-a')->assertSee('LP-admin-b')->assertSee('LP-admin-inactive');

        $this->assertFalse(LaunchpadResource::canCreate());
    }

    public function test_link_rows_appear_with_their_kind_and_monitoring_stays_read_only(): void
    {
        Storage::fake('public');
        $admin = $this->developer();
        $dept = $this->dept('LPRK');
        $oldOwner = $this->deptUser($dept);
        $newOwner = $this->deptUser($dept);
        $row = Launchpad::create([
            'user_id' => $oldOwner->id,
            'department_id' => $dept,
            'kind' => 'link',
            'active' => true,
            'title' => 'LP-admin-link',
            'folder' => null,
            'audience' => [],
            'settings' => ['site' => 'lps', 'path' => 'reports'],
        ]);

        Livewire::actingAs($admin)->test(ListLaunchpads::class)
            ->assertSee('LP-admin-link')
            ->assertSee('پیوند به سامانه');

        $this->assertFalse(LaunchpadResource::canCreate());

        Livewire::actingAs($admin)->test(ListLaunchpads::class)
            ->callTableAction('reassignOwner', $row, ['user_id' => (string) $newOwner->id]);

        $this->assertSame($newOwner->id, $row->fresh()->user_id);
        $this->assertSame('link', $row->fresh()->kind);
    }

    public function test_reassign_owner_moves_ownership_and_drops_the_new_owner_from_the_audience(): void
    {
        Storage::fake('public');
        $admin = $this->developer();
        $dept = $this->dept('LPRR');
        $oldOwner = $this->deptUser($dept);
        $newOwner = $this->deptUser($dept);
        $other = $this->deptUser($dept);
        $row = $this->row($oldOwner, $dept, ['title' => 'LP-reassign', 'audience' => [$newOwner->id, $other->id]]);

        Livewire::actingAs($admin)->test(ListLaunchpads::class)
            ->callTableAction('reassignOwner', $row, ['user_id' => (string) $newOwner->id]);

        $fresh = $row->fresh();
        $this->assertSame($newOwner->id, $fresh->user_id);
        $this->assertSame([$other->id], array_map('intval', $fresh->audience));
    }

    public function test_bulk_delete_soft_deletes_rows_and_cleans_their_folders(): void
    {
        Storage::fake('public');
        $admin = $this->developer();
        $dept = $this->dept('LPRD');
        $owner = $this->deptUser($dept);
        $a = $this->row($owner, $dept, ['title' => 'LP-bulk-a']);
        $b = $this->row($owner, $dept, ['title' => 'LP-bulk-b']);

        Livewire::actingAs($admin)->test(ListLaunchpads::class)
            ->callTableBulkAction('delete', [$a, $b]);

        $this->assertNull(Launchpad::query()->find($a->id));
        $this->assertNull(Launchpad::query()->find($b->id));
        $this->assertNotNull(Launchpad::withTrashed()->find($a->id));
        Storage::disk('public')->assertMissing('launchpad/files/' . $a->folder . '/' . Launchpad::storedName('index.html'));
        Storage::disk('public')->assertMissing('launchpad/files/' . $b->folder . '/' . Launchpad::storedName('index.html'));
    }

    public function test_bulk_visibility_actions_toggle_rows_and_the_audience_badge(): void
    {
        Storage::fake('public');
        $admin = $this->developer();
        $dept = $this->dept('LPRV');
        $owner = $this->deptUser($dept);
        $viewer = $this->deptUser($dept);
        $badge = app(UnseenLaunchpads::class);
        $a = $this->row($owner, $dept, ['title' => 'LP-visibility-a', 'audience' => [$viewer->id]]);
        $b = $this->row($owner, $dept, ['title' => 'LP-visibility-b', 'audience' => [$viewer->id]]);

        $this->actingAs($viewer);
        $this->assertTrue($badge->isActive());

        Livewire::actingAs($admin)->test(ListLaunchpads::class)
            ->callTableBulkAction('bulk_deactivate', [$a, $b]);

        $this->assertFalse((bool) $a->fresh()->active);
        $this->assertFalse((bool) $b->fresh()->active);
        $this->actingAs($viewer);
        $this->assertFalse($badge->isActive());

        Livewire::actingAs($admin)->test(ListLaunchpads::class)
            ->callTableBulkAction('bulk_activate', [$a, $b]);

        $this->assertTrue((bool) $a->fresh()->active);
        $this->assertTrue((bool) $b->fresh()->active);
        $this->actingAs($viewer);
        $this->assertTrue($badge->isActive());
    }
}
