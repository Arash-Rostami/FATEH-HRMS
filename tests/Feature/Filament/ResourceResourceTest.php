<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\ResourceResource\Pages\CreateResource;
use App\Filament\Resources\ResourceResource\Pages\EditResource;
use App\Filament\Resources\ResourceResource\Pages\ListResources;
use App\Filament\Resources\ResourceResource\Schemas\Helper\TypeProvisioner;
use App\Filament\Resources\ResourceResource\Schemas\ResourceFormPresenter;
use App\Enums\ResourceType;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\Resource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class ResourceResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        Cache::forget(ResourceType::CACHE_KEY);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget(ResourceType::CACHE_KEY);
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

    public function test_list_renders_and_shows_factory_resource_row()
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create([
            'name' => '0-test-seat-list',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);

        Livewire::actingAs($admin)->test(ListResources::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$resource]);
    }

    public function test_create_resource_persists_name_type_and_status()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => '0-test-create-resource',
                'type' => 'seat',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('resources', [
            'name' => '0-test-create-resource',
            'type' => 'seat',
            'status' => 'active',
        ]);
    }

    public function test_create_resource_requires_name()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => null,
                'type' => 'seat',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_edit_resource_updates_name()
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create([
            'name' => '0-test-edit-before',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);

        Livewire::actingAs($admin)->test(EditResource::class, ['record' => $resource->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['name' => '0-test-edit-after'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0-test-edit-after', $resource->fresh()->name);
    }

    public function test_delete_resource_removes_row_from_database()
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create([
            'name' => '0-test-delete-resource',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);

        Livewire::actingAs($admin)->test(ListResources::class)
            ->callTableAction(DeleteAction::class, $resource);

        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    }

    public function test_type_filter_scopes_list_to_matching_resources()
    {
        $admin = $this->developer();
        $seat = Resource::factory()->create([
            'name' => '0-test-filter-seat',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);
        $car = Resource::factory()->create([
            'name' => '0-test-filter-car',
            'type' => 'car',
            'status' => 'active',
            'metadata' => [],
        ]);

        Livewire::actingAs($admin)->test(ListResources::class)
            ->assertSuccessful()
            ->filterTable('type', 'seat')
            ->assertCanSeeTableRecords([$seat])
            ->assertCanNotSeeTableRecords([$car]);
    }

    public function test_status_filter_scopes_list_to_matching_resources()
    {
        $admin = $this->developer();
        $active = Resource::factory()->create([
            'name' => '0-test-filter-active',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);
        $inactive = Resource::factory()->create([
            'name' => '0-test-filter-inactive',
            'type' => 'seat',
            'status' => 'inactive',
            'metadata' => [],
        ]);

        Livewire::actingAs($admin)->test(ListResources::class)
            ->assertSuccessful()
            ->filterTable('status', 'active')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_created_at_filter_scopes_by_from_until()
    {
        $admin = $this->developer();
        $old = Resource::factory()->create([
            'name' => '0-test-created-old',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
            'created_at' => '2026-01-15',
        ]);
        $recent = Resource::factory()->create([
            'name' => '0-test-created-recent',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
            'created_at' => '2026-03-20',
        ]);

        Livewire::actingAs($admin)->test(ListResources::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => '2026-02-01', 'until' => '2026-04-01'])
            ->assertCanSeeTableRecords([$recent])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_create_resource_with_image_uploads_file_to_public_disk()
    {
        Storage::fake('public');
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => '0-test-create-with-image',
                'type' => 'seat',
                'status' => 'active',
                'image' => UploadedFile::fake()->image('resource.png'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Resource::query()->where('name', '0-test-create-with-image')->latest('id')->first();
        $this->assertNotNull($created);
        $this->assertStringStartsWith('resources/', $created->image);
        Storage::disk('public')->assertExists($created->image);
    }

    public function test_has_reservations_filter_scopes_list_to_resources_without_reservations()
    {
        $admin = $this->developer();
        $without = Resource::factory()->create([
            'name' => '0-test-no-reservations',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);
        $with = Resource::factory()->create([
            'name' => '0-test-with-reservations',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);
        \App\Models\Reservation::factory()->create(['resource_id' => $with->id]);

        Livewire::actingAs($admin)->test(ListResources::class)
            ->assertSuccessful()
            ->filterTable('has_reservations', '0')
            ->assertCanSeeTableRecords([$without])
            ->assertCanNotSeeTableRecords([$with]);
    }

    public function test_create_seat_with_metadata_fields_has_no_form_errors()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => '0-test-seat-metadata',
                'type' => 'seat',
                'status' => 'active',
                'metadata.floor' => '3',
                'metadata.unit' => '12',
                'metadata.extension' => '199',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('resources', ['name' => '0-test-seat-metadata', 'type' => 'seat']);
    }

    public function test_create_spot_with_card_field_has_no_form_errors()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => '0-test-spot-metadata',
                'type' => 'spot',
                'status' => 'active',
                'metadata.floor' => '-1',
                'metadata.card' => '77',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('resources', ['name' => '0-test-spot-metadata', 'type' => 'spot']);
    }

    public function test_create_meeting_with_available_days_and_time_slots_has_no_form_errors()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => '0-test-meeting-metadata',
                'type' => 'meeting',
                'status' => 'active',
                'metadata.available_days' => ['saturday', 'sunday'],
                'metadata.time_slots.start' => '09:00',
                'metadata.time_slots.end' => '17:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('resources', ['name' => '0-test-meeting-metadata', 'type' => 'meeting']);
    }

    public function test_full_day_type_show_time_toggle_saves_time_slots()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => '0-test-seat-show-time',
                'type' => 'seat',
                'status' => 'active',
                'show_time' => true,
                'metadata.time_slots.start' => '09:00',
                'metadata.time_slots.end' => '17:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Resource::query()->where('name', '0-test-seat-show-time')->latest('id')->first();
        $this->assertSame('09:00', $created->metadata['time_slots']['start']);
        $this->assertSame('17:00', $created->metadata['time_slots']['end']);
    }

    public function test_create_resource_persists_custom_metadata_key_value_pairs()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateResource::class)
            ->fillForm([
                'name' => '0-test-custom-metadata',
                'type' => 'seat',
                'status' => 'active',
                'metadata.custom' => ['رنگ' => 'آبی'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Resource::query()->where('name', '0-test-custom-metadata')->latest('id')->first();
        $this->assertSame(['رنگ' => 'آبی'], $created->metadata['custom']);
    }

    public function test_normalize_metadata_strips_blank_time_slots_to_fully_absent()
    {
        $data = ['metadata' => ['time_slots' => ['start' => null, 'end' => null]]];

        $result = ResourceFormPresenter::normalizeMetadata($data);

        $this->assertArrayNotHasKey('time_slots', $result['metadata']);
    }

    public function test_normalize_metadata_keeps_time_slots_when_both_set()
    {
        $data = ['metadata' => ['time_slots' => ['start' => '09:00', 'end' => '17:00']]];

        $result = ResourceFormPresenter::normalizeMetadata($data);

        $this->assertSame(['start' => '09:00', 'end' => '17:00'], $result['metadata']['time_slots']);
    }

    public function test_normalize_metadata_strips_time_slots_when_only_one_side_set()
    {
        $data = ['metadata' => ['time_slots' => ['start' => '09:00', 'end' => null]]];

        $result = ResourceFormPresenter::normalizeMetadata($data);

        $this->assertArrayNotHasKey('time_slots', $result['metadata']);
    }

    public function test_normalize_metadata_strips_empty_available_days_to_fully_absent()
    {
        $data = ['metadata' => ['available_days' => []]];

        $result = ResourceFormPresenter::normalizeMetadata($data);

        $this->assertArrayNotHasKey('available_days', $result['metadata']);
    }

    public function test_normalize_metadata_keeps_available_days_when_non_empty()
    {
        $data = ['metadata' => ['available_days' => ['saturday']]];

        $result = ResourceFormPresenter::normalizeMetadata($data);

        $this->assertSame(['saturday'], $result['metadata']['available_days']);
    }

    public function test_edit_form_hydrates_unit_and_extension_for_existing_seat()
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create([
            'name' => '0-test-hydrate-seat',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => ['floor' => '7', 'unit' => '39', 'extension' => '101'],
        ]);

        Livewire::actingAs($admin)->test(EditResource::class, ['record' => $resource->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'metadata.floor' => '7',
                'metadata.unit' => '39',
                'metadata.extension' => '101',
            ]);
    }

    public function test_edit_form_hydrates_card_for_existing_spot()
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create([
            'name' => '0-test-hydrate-spot',
            'type' => 'spot',
            'status' => 'active',
            'metadata' => ['card' => '7', 'floor' => '-1'],
        ]);

        Livewire::actingAs($admin)->test(EditResource::class, ['record' => $resource->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['metadata.card' => '7']);
    }

    public function test_provision_type_creates_all_display_and_behavior_policy_rows()
    {
        $value = TypeProvisioner::provision([
            'display_label' => 'پروژکتور تست',
            'display_icon' => 'heroicon-o-video-camera',
            'display_color' => 'gray',
            'is_full_day' => false,
        ]);

        $this->assertSame('prozhktor_tst', $value);
        $rows = ReservationPolicy::where('resource_type', $value)->pluck('value', 'key');
        $this->assertSame('پروژکتور تست', $rows['display_label']);
        $this->assertSame('videocam', $rows['display_material_icon']);
        $this->assertSame(21, $rows['window_days']);
        $this->assertCount(21, $rows);
        $this->assertTrue($rows->has('last_minute_hours'));
        $this->assertNull($rows['last_minute_hours']);

        $type = ResourceType::tryFrom($value);
        $this->assertNotNull($type);
        $this->assertFalse($type->isFullDay());
    }

    public function test_provision_type_reuses_existing_slug_for_identical_label()
    {
        $first = TypeProvisioner::provision(['display_label' => 'انبار', 'display_icon' => 'heroicon-o-archive-box', 'display_color' => 'gray', 'is_full_day' => true]);
        $countAfterFirst = ReservationPolicy::where('resource_type', $first)->count();

        $second = TypeProvisioner::provision(['display_label' => 'انبار', 'display_icon' => 'heroicon-o-archive-box', 'display_color' => 'gray', 'is_full_day' => true]);

        $this->assertSame($first, $second);
        $this->assertSame($countAfterFirst, ReservationPolicy::where('resource_type', $first)->count());
    }

    public function test_provision_type_suffixes_slug_on_collision_with_a_different_label()
    {
        $first = TypeProvisioner::provision(['display_label' => 'کمد ۱', 'display_icon' => 'heroicon-o-key', 'display_color' => 'gray', 'is_full_day' => true]);
        $second = TypeProvisioner::provision(['display_label' => 'کمد ۲', 'display_icon' => 'heroicon-o-key', 'display_color' => 'gray', 'is_full_day' => true]);

        $this->assertNotSame($first, $second, 'two distinct Persian labels that slugify identically (numerals dropped) must not collapse into one type');
        $label = ReservationPolicy::where('resource_type', $second)->where('key', 'display_label')->first()?->value;
        $this->assertSame('کمد ۲', $label);
    }

    public function test_resource_type_icon_options_render_svg_and_persian_label_for_every_case()
    {
        $method = new ReflectionMethod(ResourceFormPresenter::class, 'curatedIconOptions');
        $method->setAccessible(true);
        $options = $method->invoke(null);

        $this->assertCount(14, $options);
        $this->assertArrayHasKey('heroicon-o-cube', $options);
        $this->assertStringContainsString('<svg', $options['heroicon-o-cube']);
        $this->assertStringContainsString('جعبه', $options['heroicon-o-cube']);
    }

    public function test_resource_type_icon_option_label_renders_persian_for_a_curated_value_and_falls_back_in_english_for_off_catalog()
    {
        $method = new ReflectionMethod(ResourceFormPresenter::class, 'curatedIconOptionLabel');
        $method->setAccessible(true);

        $known = $method->invoke(null, 'heroicon-o-key');
        $this->assertStringContainsString('<svg', $known);
        $this->assertStringContainsString('کلید', $known);

        $offCatalog = $method->invoke(null, 'heroicon-o-academic-cap');
        $this->assertStringContainsString('<svg', $offCatalog);
        $this->assertStringContainsString('Academic Cap', $offCatalog);
    }

    public function test_reservations_relation_manager_edit_navigates_to_the_full_reservation_edit_page(): void
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create();
        $reservation = Reservation::factory()->create(['resource_id' => $resource->id]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ResourceResource\RelationManagers\ReservationsRelationManager::class, [
                'ownerRecord' => $resource,
                'pageClass' => EditResource::class,
            ])
            ->assertSuccessful()
            ->assertTableActionHasUrl('edit', ReservationResource::getUrl('edit', ['record' => $reservation], shouldGuessMissingParameters: true), $reservation);
    }

    public function test_reservations_relation_manager_strips_the_inherited_bulk_actions(): void
    {
        $admin = $this->developer();
        $resource = Resource::factory()->create();

        $page = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ResourceResource\RelationManagers\ReservationsRelationManager::class, [
                'ownerRecord' => $resource,
                'pageClass' => EditResource::class,
            ])
            ->assertSuccessful();

        $this->assertSame([], $page->instance()->getTable()->getToolbarActions(),
            'the رزروها tab must stay a plain list/edit tab — no bulk delete/exporter inherited from ReservationResource');
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Resource::factory()->create([
            'name' => '0-test-group-render',
            'type' => 'seat',
            'status' => 'active',
            'metadata' => [],
        ]);

        Livewire::actingAs($admin)->test(ListResources::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'type')
            ->assertSuccessful();
    }

    public function test_sort_by_id_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $first = Resource::factory()->create(['name' => '0-sort-id-a', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $second = Resource::factory()->create(['name' => '0-sort-id-b', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);

        $component = Livewire::actingAs($admin)->test(ListResources::class)
            ->searchTable('0-sort-id-')
            ->sortTable('id')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_name_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $first = Resource::factory()->create(['name' => '0-AAA-sort-name', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $second = Resource::factory()->create(['name' => '0-ZZZ-sort-name', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);

        $component = Livewire::actingAs($admin)->test(ListResources::class)
            ->searchTable('sort-name')
            ->sortTable('name')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sort_by_created_at_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $older = Resource::factory()->create(['name' => '0-sort-created-old', 'type' => 'seat', 'status' => 'active', 'metadata' => [], 'created_at' => '2020-01-15']);
        $newer = Resource::factory()->create(['name' => '0-sort-created-new', 'type' => 'seat', 'status' => 'active', 'metadata' => [], 'created_at' => '2030-01-15']);

        $component = Livewire::actingAs($admin)->test(ListResources::class)
            ->searchTable('0-sort-created-')
            ->sortTable('created_at')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sort_by_status_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $active = Resource::factory()->create(['name' => '0-sort-status-active', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $inactive = Resource::factory()->create(['name' => '0-sort-status-inactive', 'type' => 'seat', 'status' => 'inactive', 'metadata' => []]);

        $component = Livewire::actingAs($admin)->test(ListResources::class)
            ->searchTable('0-sort-status-')
            ->sortTable('status')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$active->id, $inactive->id], array_values(array_intersect($ids, [$active->id, $inactive->id])));

        $component->sortTable('status', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$inactive->id, $active->id], array_values(array_intersect($ids, [$active->id, $inactive->id])));
    }

    public function test_sort_by_type_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $car = Resource::factory()->create(['name' => '0-sort-type-car', 'type' => 'car', 'status' => 'active', 'metadata' => []]);
        $seat = Resource::factory()->create(['name' => '0-sort-type-seat', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);

        $component = Livewire::actingAs($admin)->test(ListResources::class)
            ->searchTable('0-sort-type-')
            ->sortTable('type')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$car->id, $seat->id], array_values(array_intersect($ids, [$car->id, $seat->id])));

        $component->sortTable('type', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$seat->id, $car->id], array_values(array_intersect($ids, [$car->id, $seat->id])));
    }

    public function test_sort_by_reservations_count_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $few = Resource::factory()->create(['name' => '0-sort-reservations-few', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);
        $many = Resource::factory()->create(['name' => '0-sort-reservations-many', 'type' => 'seat', 'status' => 'active', 'metadata' => []]);
        Reservation::factory()->count(3)->create(['resource_id' => $many->id]);

        $component = Livewire::actingAs($admin)->test(ListResources::class)
            ->searchTable('0-sort-reservations-')
            ->sortTable('reservations_count')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$few->id, $many->id], array_values(array_intersect($ids, [$few->id, $many->id])));

        $component->sortTable('reservations_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$many->id, $few->id], array_values(array_intersect($ids, [$few->id, $many->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ResourceResource\Pages\CreateResource::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ResourceResource\Pages\EditResource::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ResourceResource\Pages\ListResources::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ResourceResource\Pages\CreateResource::class, \App\Filament\Resources\ResourceResource\Pages\EditResource::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\ResourceResource\RelationManagers\ReservationsRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_replaced_files(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\ResourceResource\Pages\EditResource());
        Storage::fake('public');
        Storage::disk('public')->put('resources/old.jpg', 'x');
        Storage::disk('public')->put('resources/new.jpg', 'x');
        $record = \App\Models\Resource::factory()->create(['image' => 'resources/old.jpg']);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertSame(['resources/old.jpg'], $before);

        $record->update(['image' => 'resources/new.jpg']);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('resources/old.jpg');
        Storage::disk('public')->assertExists('resources/new.jpg');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ResourceResource/Schemas/ResourceTablePresenter.php'),
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
