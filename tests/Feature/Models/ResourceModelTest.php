<?php

namespace Tests\Feature\Models;

use App\Enums\ResourceType;
use App\Models\Profile;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    private function resource(string $type, array $metadata = [], string $name = 'منبع نمونه'): Resource
    {
        $resource = new Resource();
        $resource->forceFill([
            'type' => $type,
            'name' => $name,
            'metadata' => $metadata,
        ]);

        return $resource;
    }

    public function test_get_tabs_delegates_to_resource_type_tabs(): void
    {
        $tabs = Resource::getTabs();

        $this->assertCount(count(ResourceType::cases()), $tabs);
        $seatTab = collect($tabs)->firstWhere('id', 'seat');
        $this->assertSame(ResourceType::tryFrom('seat')->getMaterialIcon(), $seatTab['icon']);
        $this->assertSame(ResourceType::tryFrom('seat')->getLabel(), $seatTab['label']);
    }

    public function test_metadata_cast_round_trips_array(): void
    {
        $resource = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'metadata' => ['floor' => '3', 'extension' => '100'],
        ]);

        $metadata = $resource->fresh()->metadata;

        $this->assertSame(['floor' => '3', 'extension' => '100'], $metadata);
    }

    public function test_scope_matching_finds_by_name_and_metadata_including_persian_json_escapes(): void
    {
        $tag = 'zz' . uniqid();
        $byName = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => "$tag-name", 'metadata' => []]);
        $byMeta = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => 'other', 'metadata' => ['custom' => ['note' => "$tag-meta", 'zone' => 'طبقه سوم']]]);
        $other = Resource::factory()->create(['type' => 'seat', 'status' => 'active', 'name' => 'other2', 'metadata' => []]);

        $this->assertEqualsCanonicalizing([$byName->id, $byMeta->id], Resource::matching($tag)->pluck('id')->all());
        $this->assertSame([$byMeta->id], Resource::matching('طبقه سوم')->whereIn('id', [$byName->id, $byMeta->id, $other->id])->pluck('id')->all());
        $this->assertSame([], Resource::matching('%')->whereIn('id', [$byName->id, $byMeta->id, $other->id])->pluck('id')->all());
    }

    public function test_scope_available_filters_by_type_and_active_status(): void
    {
        $seat = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $inactive = Resource::factory()->create(['type' => 'seat', 'status' => 'inactive']);
        $car = Resource::factory()->create(['type' => 'car', 'status' => 'active']);

        $start = now()->addDays(1)->startOfDay();
        $end = now()->addDays(1)->endOfDay();

        $ids = Resource::available('seat', $start, $end)->pluck('id')->all();

        $this->assertContains($seat->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertNotContains($car->id, $ids);
    }

    public function test_scope_available_excludes_resources_with_overlapping_active_reservation(): void
    {
        $free = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);
        $busy = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        Reservation::factory()->create([
            'resource_id' => $busy->id,
            'status' => 'active',
            'start_time' => now()->addDays(1)->setHour(9),
            'end_time' => now()->addDays(1)->setHour(17),
            'is_full_day' => false,
        ]);

        $start = now()->addDays(1)->setHour(10);
        $end = now()->addDays(1)->setHour(15);

        $ids = Resource::available('seat', $start, $end)->pluck('id')->all();

        $this->assertContains($free->id, $ids);
        $this->assertNotContains($busy->id, $ids);
    }

    public function test_scope_available_includes_resource_with_released_reservation_when_allow_overlap_true(): void
    {
        $released = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        Reservation::factory()->create([
            'resource_id' => $released->id,
            'status' => 'released',
            'start_time' => now()->addDays(1)->setHour(9),
            'end_time' => now()->addDays(1)->setHour(17),
            'is_full_day' => false,
        ]);

        $start = now()->addDays(1)->setHour(10);
        $end = now()->addDays(1)->setHour(15);

        $ids = Resource::available('seat', $start, $end, true)->pluck('id')->all();

        $this->assertContains($released->id, $ids);
    }

    public function test_scope_available_excludes_resource_with_released_reservation_by_default(): void
    {
        $released = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        Reservation::factory()->create([
            'resource_id' => $released->id,
            'status' => 'released',
            'start_time' => now()->addDays(1)->setHour(9),
            'end_time' => now()->addDays(1)->setHour(17),
            'is_full_day' => false,
        ]);

        $start = now()->addDays(1)->setHour(10);
        $end = now()->addDays(1)->setHour(15);

        $ids = Resource::available('seat', $start, $end)->pluck('id')->all();

        $this->assertNotContains($released->id, $ids);
    }

    public function test_metadata_facets_discovers_floor_and_unit_for_seat(): void
    {
        Resource::factory()->create(['type' => 'zz-seat-facet-test', 'status' => 'active', 'metadata' => ['floor' => '1', 'unit' => '10']]);
        Resource::factory()->create(['type' => 'zz-seat-facet-test', 'status' => 'active', 'metadata' => ['floor' => '2', 'unit' => '20']]);

        $facets = Resource::metadataFacets('zz-seat-facet-test');
        $byKey = collect($facets)->keyBy('key');

        $this->assertTrue($byKey->has('floor'));
        $this->assertTrue($byKey->has('unit'));
        $this->assertSame('layers', $byKey['floor']['icon']);
        $this->assertCount(2, $byKey['floor']['options']);
    }

    public function test_metadata_facets_excludes_notes_and_operational_array_fields(): void
    {
        Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => ['notes' => 'یادداشت الف', 'available_days' => ['saturday'], 'time_slots' => ['start' => '09:00', 'end' => '10:00']]]);
        Resource::factory()->create(['type' => 'meeting', 'status' => 'active', 'metadata' => ['notes' => 'یادداشت ب', 'available_days' => ['sunday'], 'time_slots' => ['start' => '09:00', 'end' => '10:00']]]);

        $facets = Resource::metadataFacets('meeting');

        $this->assertSame([], $facets);
    }

    public function test_metadata_facets_excludes_low_presence_and_single_value_fields(): void
    {
        Resource::factory()->create(['type' => 'zz-seat-facet-test', 'status' => 'active', 'metadata' => ['floor' => '1', 'location' => 'N/A']]);
        Resource::factory()->create(['type' => 'zz-seat-facet-test', 'status' => 'active', 'metadata' => ['floor' => '1']]);
        Resource::factory()->create(['type' => 'zz-seat-facet-test', 'status' => 'active', 'metadata' => ['floor' => '1']]);

        $facets = Resource::metadataFacets('zz-seat-facet-test');
        $byKey = collect($facets)->keyBy('key');

        $this->assertFalse($byKey->has('location'), 'presence ratio 1/3 must be excluded');
        $this->assertFalse($byKey->has('floor'), 'single distinct value must be excluded');
    }

    public function test_metadata_facets_unwraps_custom_key_value_bag(): void
    {
        Resource::factory()->create(['type' => 'projector', 'status' => 'active', 'metadata' => ['custom' => ['رنگ' => 'آبی']]]);
        Resource::factory()->create(['type' => 'projector', 'status' => 'active', 'metadata' => ['custom' => ['رنگ' => 'قرمز']]]);

        $facets = Resource::metadataFacets('projector');
        $byKey = collect($facets)->keyBy('key');

        $this->assertTrue($byKey->has('رنگ'));
        $this->assertSame('custom->رنگ', $byKey['رنگ']['path']);
        $this->assertCount(2, $byKey['رنگ']['options']);
    }

    public function test_metadata_facets_returns_empty_array_for_type_with_no_resources(): void
    {
        $this->assertSame([], Resource::metadataFacets('zzz-nonexistent-type'));
    }

    public function test_metadata_facets_labels_floor_negative_and_zero_specially(): void
    {
        Resource::factory()->create(['type' => 'spot', 'status' => 'active', 'metadata' => ['floor' => '-1']]);
        Resource::factory()->create(['type' => 'spot', 'status' => 'active', 'metadata' => ['floor' => '0']]);

        $facets = collect(Resource::metadataFacets('spot'))->keyBy('key');
        $options = collect($facets['floor']['options'])->keyBy('value');

        $this->assertSame('طبقه منفی ۱', $options['-1']['label']);
        $this->assertSame('همکف', $options['0']['label']);
    }

    public function test_scope_available_excludes_resources_with_null_start_time_reservation_in_same_day(): void
    {
        $busy = Resource::factory()->create(['type' => 'seat', 'status' => 'active']);

        Reservation::factory()->create([
            'resource_id' => $busy->id,
            'status' => 'active',
            'start_time' => null,
            'end_time' => null,
            'is_full_day' => true,
        ]);

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $ids = Resource::available('seat', $start, $end)->pluck('id')->all();

        $this->assertNotContains($busy->id, $ids);
    }

    public function test_display_image_accessor_returns_image_when_set(): void
    {
        $resource = new Resource();
        $resource->type = 'seat';
        $resource->image = 'resources/img.png';

        $this->assertSame('resources/img.png', $resource->display_image);
    }

    public function test_display_image_accessor_falls_back_to_related_user_profile_image_for_meeting_type(): void
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'Alice']);
        Profile::factory()->create(['user_id' => $user->id, 'image' => 'profiles/alice.png']);

        $resource = Resource::factory()->create([
            'type' => 'meeting',
            'status' => 'active',
            'name' => 'Alice',
            'image' => null,
        ]);
        $resource->load('relatedUser.profile');

        $this->assertSame('profiles/alice.png', $resource->display_image);
    }

    public function test_display_image_accessor_returns_null_for_non_meeting_type_without_image(): void
    {
        $resource = new Resource();
        $resource->type = 'seat';
        $resource->image = null;

        $this->assertNull($resource->display_image);
    }

    public function test_display_image_url_accessor_returns_null_when_no_display_image(): void
    {
        $resource = new Resource();
        $resource->type = 'seat';
        $resource->image = null;

        $this->assertNull($resource->display_image_url);
    }

    public function test_display_image_url_accessor_resolves_absolute_url_unchanged(): void
    {
        $resource = new Resource();
        $resource->type = 'seat';
        $resource->image = 'https://example.com/img.png';

        $this->assertSame('https://example.com/img.png', $resource->display_image_url);
    }

    public function test_formatted_metadata_accessor_returns_empty_array_for_empty_metadata(): void
    {
        $resource = new Resource();
        $resource->metadata = [];

        $this->assertSame([], $resource->formatted_metadata);
    }

    public function test_formatted_metadata_accessor_returns_empty_array_for_null_metadata(): void
    {
        $resource = new Resource();

        $this->assertSame([], $resource->formatted_metadata);
    }

    public function test_formatted_metadata_accessor_returns_items_in_reverse_order_with_known_keys(): void
    {
        $resource = new Resource();
        $resource->metadata = ['floor' => '3', 'extension' => '100'];

        $items = $resource->formatted_metadata;

        $this->assertCount(2, $items);
        $this->assertSame('call', $items[0]->icon);
        $this->assertSame('۱۰۰', $items[0]->value);
        $this->assertSame('layers', $items[1]->icon);
        $this->assertSame('۳', $items[1]->value);
    }

    public function test_formatted_metadata_accessor_uses_headline_label_for_unknown_keys(): void
    {
        $resource = new Resource();
        $resource->metadata = ['capacity' => '5'];

        $items = $resource->formatted_metadata;

        $this->assertCount(1, $items);
        $this->assertNull($items[0]->icon);
        $this->assertStringContainsString('Capacity', $items[0]->label);
        $this->assertSame('۵', $items[0]->value);
    }

    public function test_formatted_metadata_accessor_excludes_notes_from_chip_list(): void
    {
        $resource = new Resource();
        $resource->metadata = ['floor' => '3', 'notes' => 'یادداشت تست'];

        $items = $resource->formatted_metadata;

        $this->assertCount(1, $items);
        $this->assertSame('layers', $items[0]->icon);
    }

    public function test_formatted_metadata_accessor_skips_empty_values(): void
    {
        $resource = new Resource();
        $resource->metadata = ['capacity' => null, 'extension' => '', 'custom' => [], 'available_days' => ['sunday']];

        $items = $resource->formatted_metadata;

        $this->assertCount(1, $items);
        $this->assertSame('event_repeat', $items[0]->icon);
    }

    public function test_formatted_metadata_accessor_flattens_custom_key_value_entries(): void
    {
        $resource = new Resource();
        $resource->metadata = ['custom' => ['رنگ' => 'آبی', 'جنس' => 'چوب']];

        $items = $resource->formatted_metadata;

        $this->assertCount(2, $items);
        $this->assertNull($items[0]->icon);
    }

    public function test_formatted_metadata_accessor_joins_available_days_with_labels(): void
    {
        $resource = new Resource();
        $resource->metadata = ['available_days' => ['saturday', 'sunday']];

        $items = $resource->formatted_metadata;

        $this->assertCount(1, $items);
        $this->assertSame('event_repeat', $items[0]->icon);
        $this->assertStringContainsString('شنبه', $items[0]->value);
    }

    public function test_formatted_metadata_accessor_formats_time_slots_as_range(): void
    {
        $resource = new Resource();
        $resource->metadata = ['time_slots' => ['start' => '09:00', 'end' => '17:00']];

        $items = $resource->formatted_metadata;

        $this->assertCount(1, $items);
        $this->assertSame('schedule', $items[0]->icon);
        $this->assertStringContainsString('-', $items[0]->value);
    }

    public function test_status_summary_seat_combines_floor_unit_and_extension(): void
    {
        $resource = new Resource();
        $resource->type = 'seat';
        $resource->metadata = ['floor' => '7', 'unit' => '39', 'extension' => '101'];

        $this->assertSame('طبقه ۷ • واحد ۳۹ • داخلی ۱۰۱', $resource->status_summary);
    }

    public function test_status_summary_spot_combines_name_floor_and_card(): void
    {
        $resource = new Resource();
        $resource->type = 'spot';
        $resource->name = '7A';
        $resource->metadata = ['floor' => '-1', 'card' => '7'];

        $this->assertSame('جای پارک 7A • طبقه -۱ • کارت ۷', $resource->status_summary);
    }

    public function test_status_summary_meeting_falls_back_to_labeled_name_when_metadata_empty(): void
    {
        $resource = new Resource();
        $resource->type = 'meeting';
        $resource->name = 'اتاق جلسه';
        $resource->metadata = [];

        $this->assertSame($resource->labeled_name, $resource->status_summary);
    }

    public function test_scope_available_narrows_by_resource_level_available_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00')); // Saturday
        $saturdayOnly = Resource::factory()->create([
            'type' => 'meeting',
            'status' => 'active',
            'metadata' => ['available_days' => ['saturday']],
        ]);
        $sundayOnly = Resource::factory()->create([
            'type' => 'meeting',
            'status' => 'active',
            'metadata' => ['available_days' => ['sunday']],
        ]);

        $start = Carbon::now()->setTime(10, 0);
        $end = Carbon::now()->setTime(11, 0);

        $ids = Resource::available('meeting', $start, $end)->pluck('id')->all();

        $this->assertContains($saturdayOnly->id, $ids);
        $this->assertNotContains($sundayOnly->id, $ids);
        Carbon::setTestNow();
    }

    public function test_scope_available_ignores_available_days_when_key_absent(): void
    {
        $resource = Resource::factory()->create([
            'type' => 'meeting',
            'status' => 'active',
            'metadata' => [],
        ]);

        $start = now()->addDay()->setTime(10, 0);
        $end = now()->addDay()->setTime(11, 0);

        $ids = Resource::available('meeting', $start, $end)->pluck('id')->all();

        $this->assertContains($resource->id, $ids);
    }

    public function test_scope_available_does_not_hide_resources_by_time_of_day(): void
    {
        $resource = Resource::factory()->create([
            'type' => 'meeting',
            'status' => 'active',
            'metadata' => ['time_slots' => ['start' => '11:00', 'end' => '16:30']],
        ]);

        $ids = Resource::available('meeting', now()->addDay()->setTime(8, 0), now()->addDay()->setTime(9, 0))->pluck('id')->all();

        $this->assertContains($resource->id, $ids);
    }

    public function test_scope_available_ignores_time_slots_for_full_day_type(): void
    {
        $resource = Resource::factory()->create([
            'type' => 'car',
            'status' => 'active',
            'metadata' => ['time_slots' => ['start' => '09:00', 'end' => '12:00']],
        ]);

        $start = now()->addDay()->startOfDay();
        $end = now()->addDay()->endOfDay();

        $ids = Resource::available('car', $start, $end)->pluck('id')->all();

        $this->assertContains($resource->id, $ids);
    }

    public function test_icon_accessor_returns_material_icon_for_known_type(): void
    {
        $resource = new Resource();
        $resource->type = 'meeting';

        $this->assertSame('person', $resource->icon);
    }

    public function test_icon_accessor_defaults_to_chair_for_unknown_type(): void
    {
        $resource = new Resource();
        $resource->type = 'unknown';

        $this->assertSame('chair', $resource->icon);
    }

    public function test_labeled_name_accessor_combines_type_label_and_name(): void
    {
        $resource = new Resource();
        $resource->type = 'meeting';
        $resource->name = 'Room A';

        $this->assertSame('ملاقات ⇄ Room A', $resource->labeled_name);
    }

    public function test_labeled_name_accessor_falls_back_to_raw_type_for_unknown_type(): void
    {
        $resource = new Resource();
        $resource->type = 'unknown';
        $resource->name = 'X';

        $this->assertSame('unknown ⇄ X', $resource->labeled_name);
    }

    public function test_labeled_name_accessor_handles_resource_type_instance_via_instanceof_branch(): void
    {
        $resource = new Resource();
        $resource->type = ResourceType::tryFrom('car');
        $resource->name = 'Room B';

        $this->assertSame('خودرو ⇄ Room B', $resource->labeled_name);
    }

    public function test_deleting_removes_image_file_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/image.jpg', 'content');
        $resource = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'image' => 'resources/image.jpg',
        ]);

        $resource->delete();

        Storage::disk('public')->assertMissing('resources/image.jpg');
    }

    public function test_deleting_with_no_image_does_not_error(): void
    {
        Storage::fake('public');
        $resource = Resource::factory()->create([
            'type' => 'seat',
            'status' => 'active',
            'image' => null,
        ]);

        $resource->delete();

        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    }

    public function test_deleting_last_resource_of_a_type_prunes_its_reservation_policies(): void
    {
        \App\Models\ReservationPolicy::insert([
            ['resource_type' => 'projector', 'key' => 'display_label', 'value' => json_encode('پروژکتور')],
            ['resource_type' => 'projector', 'key' => 'window_days', 'value' => json_encode(21)],
        ]);
        $resource = Resource::factory()->create(['type' => 'projector', 'status' => 'active']);

        $resource->delete();

        $this->assertDatabaseMissing('reservation_policies', ['resource_type' => 'projector']);
    }

    public function test_deleting_one_of_several_same_type_resources_keeps_reservation_policies(): void
    {
        \App\Models\ReservationPolicy::insert([
            ['resource_type' => 'projector', 'key' => 'display_label', 'value' => json_encode('پروژکتور')],
        ]);
        $first = Resource::factory()->create(['type' => 'projector', 'status' => 'active']);
        Resource::factory()->create(['type' => 'projector', 'status' => 'active']);

        $first->delete();

        $this->assertDatabaseHas('reservation_policies', ['resource_type' => 'projector', 'key' => 'display_label']);
    }

    public function test_formatted_metadata_renders_generic_field_config_items(): void
    {
        $items = $this->resource('seat', [
            'floor' => 3,
            'extension' => '210',
            'unit' => 12,
            'card' => 'A1',
        ])->formatted_metadata;

        $this->assertCount(4, $items);
        $byIcon = collect($items)->keyBy(fn ($i) => $i->icon);
        $this->assertSame('layers', $byIcon['layers']->icon);
        $this->assertSame('call', $byIcon['call']->icon);
        $this->assertSame('meeting_room', $byIcon['meeting_room']->icon);
        $this->assertSame('credit_card', $byIcon['credit_card']->icon);
        $this->assertSame('font-mono tracking-wider', $byIcon['call']->class);
    }

    public function test_formatted_metadata_renders_custom_keys_as_headlined_items(): void
    {
        $items = $this->resource('seat', ['custom' => ['color' => 'red']])->formatted_metadata;

        $this->assertCount(1, $items);
        $this->assertNull($items[0]->icon);
        $this->assertStringContainsString('Color', $items[0]->label);
        $this->assertSame('red', $items[0]->value);
    }

    public function test_status_summary_for_meeting_combines_capacity_days_and_slots(): void
    {
        $summary = $this->resource('meeting', [
            'capacity' => 8,
            'available_days' => ['saturday'],
            'time_slots' => ['start' => '08:00', 'end' => '18:00'],
        ])->status_summary;

        $this->assertStringContainsString('ظرفیت', $summary);
        $this->assertStringContainsString(' - ', $summary);
    }

    public function test_status_summary_for_car_behaves_like_meeting_branch(): void
    {
        $summary = $this->resource('car', ['capacity' => 4])->status_summary;

        $this->assertStringContainsString('ظرفیت', $summary);
    }

    public function test_status_summary_falls_back_to_labeled_name_for_unmatched_type(): void
    {
        $summary = $this->resource('zzz-nonexistent-type')->status_summary;

        $this->assertSame('zzz-nonexistent-type ⇄ منبع نمونه', $summary);
    }
}