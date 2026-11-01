<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\GalleryResource\Pages\CreateGallery;
use App\Filament\Resources\GalleryResource\Pages\EditGallery;
use App\Filament\Resources\GalleryResource\Pages\ListGallery;
use App\Models\Permission;
use App\Models\Photo;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Storage::fake('public');
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

    public function test_list_renders_and_shows_factory_gallery_row()
    {
        $admin = $this->developer();
        $photo = Photo::factory()->create(['title' => 'List Render Probe', 'event_date' => '2025-06-15']);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$photo]);
    }

    public function test_active_toggle_and_bulk_visibility_actions_set_active_and_inactive_rows_stay_listed()
    {
        $admin = $this->developer();
        $a = Photo::factory()->create(['title' => 'Vis Probe A', 'event_date' => '2025-06-15', 'active' => true]);
        $b = Photo::factory()->create(['title' => 'Vis Probe B', 'event_date' => '2025-06-15', 'active' => true]);

        $component = Livewire::actingAs($admin)->test(ListGallery::class)
            ->call('updateTableColumnState', 'active', (string) $a->getKey(), false);

        $this->assertFalse((bool) $a->fresh()->active);
        $component->assertCanSeeTableRecords([$a->fresh(), $b]);

        $component->callTableBulkAction('bulk_deactivate', [$a, $b]);
        $this->assertFalse((bool) $a->fresh()->active);
        $this->assertFalse((bool) $b->fresh()->active);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->callTableBulkAction('bulk_activate', [$a, $b])
            ->filterTable('active', false)
            ->assertCanNotSeeTableRecords([$a, $b]);
        $this->assertTrue((bool) $a->fresh()->active);
        $this->assertTrue((bool) $b->fresh()->active);
    }

    public function test_create_gallery_persists_title_and_path()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateGallery::class)
            ->fillForm([
                'title' => 'گالری جدید برای تست',
                'event_date' => '2024-06-15',
                'path' => [UploadedFile::fake()->image('photo1.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $photo = Photo::query()->latest('id')->first();
        $this->assertNotNull($photo);
        $this->assertSame('گالری جدید برای تست', $photo->title);
        $this->assertCount(1, $photo->path);
        Storage::disk('public')->assertExists($photo->path[0]);
    }

    public function test_create_gallery_shows_friendly_notification_when_upload_never_finished()
    {
        $admin = $this->developer();

        $test = Livewire::actingAs($admin)->test(CreateGallery::class)
            ->fillForm([
                'title' => 'آپلود ناقص',
                'event_date' => '2024-06-15',
                'path' => [UploadedFile::fake()->image('photo1.jpg')],
            ]);

        Storage::disk(FileUploadConfiguration::disk())->deleteDirectory(FileUploadConfiguration::directory());

        $test->call('create')
            ->assertNotified(__('errors/strings.notifications.file_upload_incomplete_title'));

        $this->assertDatabaseMissing('photos', ['title' => 'آپلود ناقص']);
    }

    public function test_create_gallery_requires_title()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateGallery::class)
            ->fillForm([
                'title' => null,
                'path' => [UploadedFile::fake()->image('photo1.jpg')],
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_create_gallery_requires_path()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateGallery::class)
            ->fillForm([
                'title' => 'گالری بدون تصویر',
                'path' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['path' => 'required']);
    }

    public function test_edit_gallery_updates_title()
    {
        $admin = $this->developer();
        $photo = Photo::factory()->create(['title' => 'عنوان اصلی گالری', 'event_date' => '2024-06-15']);

        Livewire::actingAs($admin)->test(EditGallery::class, ['record' => $photo->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'title' => 'عنوان ویرایش‌شده گالری',
                'event_date' => '2024-06-15',
                'path' => [UploadedFile::fake()->image('updated.jpg')],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('عنوان ویرایش‌شده گالری', $photo->fresh()->title);
    }

    public function test_edit_gallery_deletes_only_the_files_dropped_from_path()
    {
        $admin = $this->developer();
        Storage::disk('public')->put('gallery/old.jpg', 'x');
        Storage::disk('public')->put('gallery/kept.jpg', 'x');
        $photo = Photo::factory()->create(['title' => 'Drop Probe', 'event_date' => '2024-06-15', 'path' => ['gallery/old.jpg', 'gallery/kept.jpg']]);

        Livewire::actingAs($admin)->test(EditGallery::class, ['record' => $photo->getRouteKey()])
            ->set('data.path', ['gallery/kept.jpg'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['gallery/kept.jpg'], array_values($photo->fresh()->path));
        Storage::disk('public')->assertMissing('gallery/old.jpg');
        Storage::disk('public')->assertExists('gallery/kept.jpg');
    }

    public function test_delete_table_action_removes_gallery_row()
    {
        $admin = $this->developer();
        $photo = Photo::factory()->create(['title' => 'Delete Probe']);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->callTableAction(DeleteAction::class, $photo);

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    public function test_department_filter_scopes_list_to_matching_gallery_rows()
    {
        $admin = $this->developer();
        $matched = Photo::factory()->create(['title' => 'AC Gallery', 'department_id' => 'AC', 'departments' => null]);
        $other = Photo::factory()->create(['title' => 'AS Gallery', 'department_id' => 'AS', 'departments' => null]);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->filterTable('department_id', 'AC')
            ->assertCanSeeTableRecords([$matched])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_visibility_filter_public_includes_empty_array_departments_not_just_null()
    {
        $admin = $this->developer();
        $publicNull = Photo::factory()->create(['title' => 'Public Null', 'department_id' => null, 'departments' => null]);
        $publicEmptyArray = Photo::factory()->create(['title' => 'Public Empty Array', 'department_id' => null, 'departments' => []]);
        $private = Photo::factory()->create(['title' => 'Private', 'department_id' => 'AC', 'departments' => null]);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->filterTable('visibility', false)
            ->assertCanSeeTableRecords([$publicNull, $publicEmptyArray])
            ->assertCanNotSeeTableRecords([$private]);
    }

    public function test_visibility_filter_private_matches_single_or_multi_department_rows()
    {
        $admin = $this->developer();
        $public = Photo::factory()->create(['title' => 'Public', 'department_id' => null, 'departments' => null]);
        $singleDept = Photo::factory()->create(['title' => 'Single Dept', 'department_id' => 'AC', 'departments' => null]);
        $multiDept = Photo::factory()->create(['title' => 'Multi Dept', 'department_id' => null, 'departments' => ['AC', 'AS']]);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->filterTable('visibility', true)
            ->assertCanSeeTableRecords([$singleDept, $multiDept])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_shared_filter_true_scopes_list_to_multi_department_rows()
    {
        $admin = $this->developer();
        $multiDept = Photo::factory()->create(['title' => 'Shared Multi', 'department_id' => null, 'departments' => ['AC', 'AS']]);
        $singleDept = Photo::factory()->create(['title' => 'Shared Single', 'department_id' => 'AC', 'departments' => null]);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->filterTable('shared_type', true)
            ->assertCanSeeTableRecords([$multiDept])
            ->assertCanNotSeeTableRecords([$singleDept]);
    }

    public function test_shared_filter_false_scopes_list_to_single_department_rows()
    {
        $admin = $this->developer();
        $singleDept = Photo::factory()->create(['title' => 'Shared Single', 'department_id' => 'AC', 'departments' => null]);
        $multiDept = Photo::factory()->create(['title' => 'Shared Multi', 'department_id' => null, 'departments' => ['AC', 'AS']]);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->filterTable('shared_type', false)
            ->assertCanSeeTableRecords([$singleDept])
            ->assertCanNotSeeTableRecords([$multiDept]);
    }

    public function test_event_date_range_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();
        $inRange = Photo::factory()->create(['title' => 'In Range', 'event_date' => now()->subDays(5)->toDateString()]);
        $outOfRange = Photo::factory()->create(['title' => 'Out Of Range', 'event_date' => now()->subDays(60)->toDateString()]);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->filterTable('event_date_range', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_title_id_created_at_and_event_date_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Photo::factory()->create(['title' => '0-AAA-sort-photo', 'event_date' => now()->subDays(60)->toDateString()]);
        $first->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $second = Photo::factory()->create(['title' => '0-ZZZ-sort-photo', 'event_date' => now()->addDays(60)->toDateString()]);
        $second->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListGallery::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('title')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('event_date')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('event_date', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Photo::factory()->create(['title' => 'Group Render Probe', 'event_date' => '2025-06-15']);

        Livewire::actingAs($admin)->test(ListGallery::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'department.description')
            ->assertSuccessful();
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\GalleryResource\Pages\CreateGallery::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\GalleryResource\Pages\EditGallery::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\GalleryResource\Pages\ListGallery::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\GalleryResource\Pages\CreateGallery::class, \App\Filament\Resources\GalleryResource\Pages\EditGallery::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/GalleryResource/Schemas/GalleryTablePresenter.php'),
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
