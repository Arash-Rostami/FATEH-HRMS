<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\DmsResource\Pages\CreateDms;
use App\Filament\Resources\DmsResource\Pages\EditDms;
use App\Filament\Resources\DmsResource\Pages\ListDms;
use App\Models\DMS;
use App\Models\Department;
use App\Models\Permission;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Filament\Actions\DeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DmsResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Storage::fake('public');
        ModelCacheVersion::bump(Department::class);
        Cache::forget('dms_document_counts');
        Cache::forget('dms_dynamic_group_keys');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(Department::class);
        Cache::forget('dms_document_counts');
        Cache::forget('dms_dynamic_group_keys');
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

    public function test_globally_searchable_attributes_include_revision()
    {
        $this->assertSame(
            ['title', 'code', 'version', 'revision'],
            \App\Filament\Resources\DmsResource::getGloballySearchableAttributes()
        );
    }

    public function test_list_renders_and_shows_factory_dms_row()
    {
        $admin = $this->developer();

        $doc = DMS::factory()->create(['title' => 'List Render Probe']);

        Livewire::actingAs($admin)->test(ListDms::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$doc]);
    }

    public function test_create_dms_persists_title_code_and_version()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateDms::class)
            ->fillForm([
                'title' => 'Created DMS Title',
                'code' => 'DOC-CREATE-001',
                'version' => '1.0',
                'status' => 'live',
                'type' => true,
                'owners' => ['ALL'],
                'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('dms', [
            'title' => 'Created DMS Title',
            'code' => 'DOC-CREATE-001',
            'version' => '1.0',
            'status' => 'live',
        ]);
    }

    public function test_create_dms_requires_a_title()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateDms::class)
            ->fillForm([
                'title' => null,
                'code' => 'DOC-VALIDATE-001',
                'version' => '1.0',
                'status' => 'live',
                'type' => true,
                'owners' => ['ALL'],
                'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_edit_dms_updates_title_and_persists()
    {
        $admin = $this->developer();

        Storage::disk('public')->put('dms/orig.pdf', 'x');
        $doc = DMS::factory()->create([
            'title' => 'Original DMS Title',
            'file' => 'dms/orig.pdf',
            'status' => 'under_review',
        ]);

        Livewire::actingAs($admin)->test(EditDms::class, ['record' => $doc->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'Updated DMS Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated DMS Title', $doc->fresh()->title);
    }

    public function test_delete_table_action_removes_dms_row()
    {
        $admin = $this->developer();

        $doc = DMS::factory()->create(['title' => 'Delete Probe']);

        Livewire::actingAs($admin)->test(ListDms::class)
            ->callTableAction(DeleteAction::class, $doc);

        $this->assertDatabaseMissing('dms', ['id' => $doc->id]);
    }

    public function test_type_filter_scopes_list_to_systematic_documents_only()
    {
        $admin = $this->developer();

        $systematic = DMS::factory()->create(['title' => 'Systematic Probe', 'type' => true]);
        $nonSystematic = DMS::factory()->create(['title' => 'Non Systematic Probe', 'type' => false]);

        Livewire::actingAs($admin)->test(ListDms::class)
            ->assertSuccessful()
            ->filterTable('type', true)
            ->assertCanSeeTableRecords([$systematic])
            ->assertCanNotSeeTableRecords([$nonSystematic]);
    }

    public function test_owners_group_renders_and_select_all_in_group_scopes_correctly()
    {
        $admin = $this->developer();

        $docA1 = DMS::factory()->create(['owners' => ['MA', 'IT'], 'status' => 'live']);
        $docA2 = DMS::factory()->create(['owners' => ['MA', 'IT'], 'status' => 'live']);
        $docB = DMS::factory()->create(['owners' => ['MA'], 'status' => 'live']);

        $component = Livewire::actingAs($admin)->test(ListDms::class)
            ->set('tableGrouping', 'owners')
            ->assertSuccessful();

        $selected = $component->instance()->getGroupedSelectableTableRecordKeys('MA,IT');

        $this->assertContains((string) $docA1->id, $selected);
        $this->assertContains((string) $docA2->id, $selected);
        $this->assertNotContains((string) $docB->id, $selected);
    }

    public function test_owners_filter_includes_all_owned_documents()
    {
        $admin = $this->developer();

        $maDoc = DMS::factory()->create(['owners' => ['MA'], 'status' => 'live']);
        $allDoc = DMS::factory()->create(['owners' => ['ALL'], 'status' => 'live']);
        $itDoc = DMS::factory()->create(['owners' => ['IT'], 'status' => 'live']);

        Livewire::actingAs($admin)->test(ListDms::class)
            ->assertSuccessful()
            ->filterTable('owners', ['departments' => ['MA']])
            ->assertCanSeeTableRecords([$maDoc, $allDoc])
            ->assertCanNotSeeTableRecords([$itDoc]);
    }

    public function test_created_at_filter_scopes_list_to_documents_in_date_range()
    {
        $admin = $this->developer();

        $inRange = DMS::factory()->create(['title' => '0-created-in-range']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = DMS::factory()->create(['title' => '0-created-out-of-range']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListDms::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        DMS::factory()->create(['status' => 'live']);

        Livewire::actingAs($admin)->test(ListDms::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'status')
            ->assertSuccessful();
    }

    public function test_sorting_by_code_and_title_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = DMS::factory()->create(['code' => 'AAA-sort-code', 'title' => 'AAA-sort-title']);
        $second = DMS::factory()->create(['code' => 'ZZZ-sort-code', 'title' => 'ZZZ-sort-title']);

        $component = Livewire::actingAs($admin)->test(ListDms::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('code')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('code', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_created_at_and_id_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = DMS::factory()->create(['title' => '0-sort-created-a']);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = DMS::factory()->create(['title' => '0-sort-created-b']);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListDms::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_combined_read_count_orders_records_correctly()
    {
        $admin = $this->developer();
        $low = DMS::factory()->create(['combined_read_count' => 0]);
        $high = DMS::factory()->create(['combined_read_count' => 500]);

        $component = Livewire::actingAs($admin)->test(ListDms::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('combined_read_count')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('combined_read_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));
    }

    public function test_sorting_by_status_orders_records_correctly()
    {
        $admin = $this->developer();
        $live = DMS::factory()->create(['status' => 'live']);
        $underReview = DMS::factory()->create(['status' => 'under_review']);

        $component = Livewire::actingAs($admin)->test(ListDms::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('status')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$live->id, $underReview->id], array_values(array_intersect($ids, [$live->id, $underReview->id])));

        $component->sortTable('status', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$underReview->id, $live->id], array_values(array_intersect($ids, [$live->id, $underReview->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\DmsResource\Pages\CreateDms::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\DmsResource\Pages\EditDms::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\DmsResource\Pages\ListDms::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\DmsResource\Pages\CreateDms::class, \App\Filament\Resources\DmsResource\Pages\EditDms::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\DmsResource\RelationManagers\ReadsRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_dropped_files(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\DmsResource\Pages\EditDms());
        Storage::fake('public');
        foreach (['dms/main.pdf', 'dms/old-extra.pdf', 'dms/kept-extra.pdf'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = \App\Models\DMS::factory()->create(['file' => 'dms/main.pdf', 'extra_files' => ['dms/old-extra.pdf', 'dms/kept-extra.pdf']]);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['dms/main.pdf', 'dms/old-extra.pdf', 'dms/kept-extra.pdf'], $before);

        $record->update(['extra_files' => ['dms/kept-extra.pdf']]);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('dms/old-extra.pdf');
        Storage::disk('public')->assertExists('dms/main.pdf');
        Storage::disk('public')->assertExists('dms/kept-extra.pdf');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/DmsResource/Schemas/DmsTablePresenter.php'),
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
