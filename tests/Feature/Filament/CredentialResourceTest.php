<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\CredentialResource\Pages\CreateCredential;
use App\Filament\Resources\CredentialResource\Pages\EditCredential;
use App\Filament\Resources\CredentialResource\Pages\ListCredentials;
use App\Models\Credential;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CredentialResourceTest extends TestCase
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_list_renders_and_shows_factory_credential_row()
    {
        $admin = $this->developer();
        $credential = Credential::factory()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListCredentials::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$credential]);
    }

    public function test_create_credential_persists_app_name_username_and_encrypted_password()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateCredential::class)
            ->fillForm([
                'user_id' => $admin->id,
                'app_name' => 'GitHub',
                'username' => 'octocat',
                'password' => 's3cret-pass',
                'link' => 'https://github.com',
                'note' => 'Personal account',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('credentials', [
            'user_id' => $admin->id,
            'app_name' => 'GitHub',
            'username' => 'octocat',
        ]);

        $created = Credential::query()->where('user_id', $admin->id)->latest('id')->first();
        $this->assertNotNull($created);
        $this->assertSame('s3cret-pass', $created->password);
        $this->assertNotSame('s3cret-pass', $created->getRawOriginal('password'));
    }

    public function test_create_credential_requires_app_name()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateCredential::class)
            ->fillForm([
                'user_id' => $admin->id,
                'username' => 'octocat',
                'password' => 's3cret-pass',
            ])
            ->call('create')
            ->assertHasFormErrors(['app_name' => 'required']);
    }

    public function test_edit_credential_updates_username_and_reencrypts_password()
    {
        $admin = $this->developer();
        $credential = Credential::factory()->create([
            'user_id' => $admin->id,
            'username' => 'old-name',
            'password' => 'old-pass',
        ]);

        Livewire::actingAs($admin)->test(EditCredential::class, ['record' => $credential->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'username' => 'new-name',
                'password' => 'new-pass',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $credential->fresh();
        $this->assertSame('new-name', $fresh->username);
        $this->assertSame('new-pass', $fresh->password);
    }

    public function test_delete_credential_removes_row_from_database()
    {
        $admin = $this->developer();
        $credential = Credential::factory()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListCredentials::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $credential);

        $this->assertDatabaseMissing('credentials', ['id' => $credential->id]);
    }

    public function test_has_link_filter_scopes_list_to_credentials_with_link()
    {
        $admin = $this->developer();
        $withLink = Credential::factory()->create(['user_id' => $admin->id, 'link' => 'https://example.com']);
        $withoutLink = Credential::factory()->create(['user_id' => $admin->id, 'link' => null]);

        Livewire::actingAs($admin)->test(ListCredentials::class)
            ->assertSuccessful()
            ->filterTable('has_link', '1')
            ->assertCanSeeTableRecords([$withLink])
            ->assertCanNotSeeTableRecords([$withoutLink]);
    }

    public function test_user_filter_scopes_list_to_selected_owner()
    {
        $admin = $this->developer();
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        $owned = Credential::factory()->create(['user_id' => $admin->id]);
        $foreign = Credential::factory()->create(['user_id' => $other->id]);

        Livewire::actingAs($admin)->test(ListCredentials::class)
            ->assertSuccessful()
            ->filterTable('user_id', $admin->id)
            ->assertCanSeeTableRecords([$owned])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Credential::factory()->create(['user_id' => $admin->id]);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Credential::factory()->create(['user_id' => $admin->id]);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListCredentials::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Credential::factory()->create(['user_id' => $admin->id]);

        $component = Livewire::actingAs($admin)->test(ListCredentials::class)->assertSuccessful();

        foreach (['app_name', 'user.name'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sorting_by_app_name_username_and_id_produces_correct_order()
    {
        $admin = $this->developer();

        foreach ([
            'app_name' => ['AAA-sort-app', 'ZZZ-sort-app'],
            'username' => ['AAA-sort-user', 'ZZZ-sort-user'],
        ] as $column => [$low, $high]) {
            $first = Credential::factory()->create(['user_id' => $admin->id, $column => $low]);
            $second = Credential::factory()->create(['user_id' => $admin->id, $column => $high]);

            $component = Livewire::actingAs($admin)->test(ListCredentials::class)
                ->set('tableRecordsPerPage', 'all')
                ->sortTable($column)
                ->assertSuccessful();

            $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
            $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

            $component->sortTable($column, 'desc')->assertSuccessful();
            $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
            $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
        }

        $first = Credential::factory()->create(['user_id' => $admin->id]);
        $second = Credential::factory()->create(['user_id' => $admin->id]);

        $component = Livewire::actingAs($admin)->test(ListCredentials::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_created_at_produces_ascending_and_descending_order()
    {
        $admin = $this->developer();
        $old = Credential::factory()->create(['user_id' => $admin->id]);
        $old->forceFill(['created_at' => now()->subYears(5)])->saveQuietly();
        $new = Credential::factory()->create(['user_id' => $admin->id]);
        $new->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListCredentials::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$old->id, $new->id], array_values(array_intersect($ids, [$old->id, $new->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$new->id, $old->id], array_values(array_intersect($ids, [$old->id, $new->id])));
    }

    public function test_sorting_by_owner_name_produces_correct_order()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => 'AAA-sort-owner']);
        $userZ = User::factory()->create(['name' => 'ZZZ-sort-owner']);
        $first = Credential::factory()->create(['user_id' => $userA->id]);
        $second = Credential::factory()->create(['user_id' => $userZ->id]);

        $component = Livewire::actingAs($admin)->test(ListCredentials::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('user.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\CredentialResource\Pages\CreateCredential::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\CredentialResource\Pages\EditCredential::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\CredentialResource\Pages\ListCredentials::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\CredentialResource\Pages\CreateCredential::class, \App\Filament\Resources\CredentialResource\Pages\EditCredential::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/CredentialResource/Schemas/CredentialTablePresenter.php'),
        ];

        $recordMethods = ['getTitleFromRecordUsing', 'getKeyFromRecordUsing', 'getDescriptionFromRecordUsing'];
        $queryMethods = ['modifyQueryUsing', 'groupQueryUsing', 'orderQueryUsing', 'scopeQueryByKeyUsing', 'scopeQueryUsing', 'query'];

        $offenders = [];

        foreach ([[$recordMethods, 'record'], [$queryMethods, 'query']] as [$methods, $expectedName]) {
            $pattern = '/\\b(' . implode('|', $methods) . ')\\s*\\(\\s*(?:static\\s+)?(?:function|fn)\\s*\\(([^)]*)\\)/s';

            foreach ($files as $path) {
                $contents = file_get_contents($path);

                if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $method = $match[1][0];
                    $paramList = $match[2][0];
                    $offset = $match[0][1];

                    $firstParam = trim(explode(',', $paramList)[0] ?? '');

                    if ($firstParam === '') {
                        continue;
                    }

                    if (!preg_match('/^(?<type>[^$]*)\\$(?<name>[A-Za-z_][A-Za-z0-9_]*)/', $firstParam, $m)) {
                        continue;
                    }

                    $hasType = trim($m['type']) !== '';
                    $name = $m['name'];

                    if ($name === $expectedName || $hasType) {
                        continue;
                    }

                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = basename($path) . ":{$line} {$method}(fn(\${$name}) => ...)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closure param must be named \$record/\$query (or carry an explicit type hint) so Filament's by-name evaluate() injection binds correctly:\n" . implode("\n", $offenders),
        );
    }
}
