<?php

namespace Tests\Feature\Filament;

use App\Enums\ChannelType;
use App\Filament\Resources\ChannelResource\Pages\CreateChannel;
use App\Filament\Resources\ChannelResource\Pages\EditChannel;
use App\Filament\Resources\ChannelResource\Pages\ListChannels;
use App\Models\Channel;
use App\Models\Permission;
use App\Models\User;
use App\Filament\Resources\ChannelResource\RelationManagers\ChannelMembersRelationManager;
use Filament\Actions\AttachAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ChannelResourceTest extends TestCase
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

    public function test_list_renders_and_shows_factory_channel()
    {
        $admin = $this->developer();
        $channel = Channel::factory()->create(['name' => '0-test-list-channel']);

        Livewire::actingAs($admin)->test(ListChannels::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$channel]);
    }

    public function test_create_channel_persists_name_slug_type_and_owner()
    {
        $admin = $this->developer();
        $owner = User::factory()->create();

        Livewire::actingAs($admin)->test(CreateChannel::class)
            ->fillForm([
                'name' => '0-test-create-channel',
                'slug' => '0-test-create-channel',
                'type' => ChannelType::Open->value,
                'owner_id' => $owner->id,
                'description' => 'test description',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('channels', [
            'name' => '0-test-create-channel',
            'slug' => '0-test-create-channel',
            'type' => ChannelType::Open->value,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_create_validates_required_name()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateChannel::class)
            ->fillForm([
                'slug' => '0-test-missing-name',
                'type' => ChannelType::Open->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_validates_required_owner()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateChannel::class)
            ->fillForm([
                'name' => '0-test-missing-owner',
                'slug' => '0-test-missing-owner',
                'type' => ChannelType::Open->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['owner_id' => 'required']);
    }

    public function test_edit_channel_updates_name()
    {
        $admin = $this->developer();
        $channel = Channel::factory()->create(['name' => '0-test-edit-before']);

        Livewire::actingAs($admin)->test(EditChannel::class, ['record' => $channel->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['name' => '0-test-edit-after'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0-test-edit-after', $channel->fresh()->name);
    }

    public function test_delete_action_soft_deletes_channel()
    {
        $admin = $this->developer();
        $channel = Channel::factory()->create(['name' => '0-test-delete-channel']);

        Livewire::actingAs($admin)->test(ListChannels::class)
            ->callTableAction(DeleteAction::class, $channel);

        $this->assertSoftDeleted('channels', ['id' => $channel->id]);
    }

    public function test_members_relation_manager_attach_and_detach_follow_channel_update_permission()
    {
        $channel = Channel::factory()->create(['name' => '0-test-rm-channel']);
        $member = User::factory()->create(['status' => 'active']);
        $channel->memberUsers()->attach($member->id, ['joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $props = ['ownerRecord' => $channel, 'pageClass' => EditChannel::class];

        foreach ([['view'], ['view', 'update']] as $actions) {
            $admin = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
            Permission::create(['user_id' => $admin->id, 'is_super_admin' => false, 'abilities' => [['module' => 'channel', 'actions' => $actions]]]);
            $component = Livewire::actingAs($admin)->test(ChannelMembersRelationManager::class, $props);

            if (in_array('update', $actions)) {
                $component->assertTableActionVisible(AttachAction::class)->assertTableActionVisible(DetachAction::class, $member);
            } else {
                $component->assertTableActionHidden(AttachAction::class)->assertTableActionHidden(DetachAction::class, $member);
            }
        }
    }

    public function test_type_filter_scopes_list_to_matching_channels()
    {
        $admin = $this->developer();
        $open = Channel::factory()->ofType(ChannelType::Open)->create(['name' => '0-test-filter-open']);
        $private = Channel::factory()->ofType(ChannelType::Private)->create(['name' => '0-test-filter-private']);

        Livewire::actingAs($admin)->test(ListChannels::class)
            ->filterTable('type', ChannelType::Open->value)
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$private]);
    }

    public function test_pruning_soon_filter_scopes_to_old_trashed_channels()
    {
        $admin = $this->developer();
        $old = Channel::factory()->create([
            'name' => '0-test-prune-old',
            'deleted_at' => now()->subDays(40),
        ]);
        $recent = Channel::factory()->create([
            'name' => '0-test-prune-recent',
            'deleted_at' => now()->subDays(5),
        ]);

        Livewire::actingAs($admin)->test(ListChannels::class)
            ->filterTable('pruning_soon')
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$recent]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Channel::factory()->create(['name' => '0-test-created-in-range']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Channel::factory()->create(['name' => '0-test-created-out-of-range']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListChannels::class)
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_name_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Channel::factory()->create(['name' => '0-AAA-sort-channel']);
        $second = Channel::factory()->create(['name' => '0-ZZZ-sort-channel']);

        $component = Livewire::actingAs($admin)->test(ListChannels::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_slug_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Channel::factory()->create(['name' => '0-slug-sort-a', 'slug' => '0-aaa-slug-sort']);
        $second = Channel::factory()->create(['name' => '0-slug-sort-b', 'slug' => '0-zzz-slug-sort']);

        $component = Livewire::actingAs($admin)->test(ListChannels::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('slug')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('slug', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_id_and_created_at_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = Channel::factory()->create(['name' => '0-created-sort-a']);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = Channel::factory()->create(['name' => '0-created-sort-b']);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListChannels::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_deleted_at_orders_trashed_channels_correctly()
    {
        $admin = $this->developer();
        $olderTrashed = Channel::factory()->create(['name' => '0-deleted-sort-a']);
        $olderTrashed->forceFill(['deleted_at' => now()->subDays(60)])->saveQuietly();
        $newerTrashed = Channel::factory()->create(['name' => '0-deleted-sort-b']);
        $newerTrashed->forceFill(['deleted_at' => now()->subDays(1)])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListChannels::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('deleted_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$olderTrashed->id, $newerTrashed->id], array_values(array_intersect($ids, [$olderTrashed->id, $newerTrashed->id])));

        $component->sortTable('deleted_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newerTrashed->id, $olderTrashed->id], array_values(array_intersect($ids, [$olderTrashed->id, $newerTrashed->id])));
    }

    public function test_sorting_by_members_count_and_messages_count_orders_records_correctly()
    {
        $admin = $this->developer();
        $low = Channel::factory()->create(['name' => '0-counts-sort-a']);
        $high = Channel::factory()->create(['name' => '0-counts-sort-b']);
        $owner = User::factory()->create();
        $high->memberUsers()->attach($owner->id, ['joined_at' => now()]);
        $high->messages()->create(['sender_id' => $owner->id, 'body' => 'test message']);

        $component = Livewire::actingAs($admin)->test(ListChannels::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('members_count')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('members_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('messages_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('messages_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Channel::factory()->create(['name' => '0-test-group-render']);

        Livewire::actingAs($admin)->test(ListChannels::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'type')
            ->assertSuccessful();
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ChannelResource\Pages\CreateChannel::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ChannelResource\Pages\EditChannel::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ChannelResource\Pages\ListChannels::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ChannelResource\Pages\CreateChannel::class, \App\Filament\Resources\ChannelResource\Pages\EditChannel::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\ChannelResource\RelationManagers\ChannelMembersRelationManager::class, \App\Filament\Resources\ChannelResource\RelationManagers\ChannelMessagesRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ChannelResource/Schemas/ChannelTablePresenter.php'),
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
