<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Gallery;
use App\Livewire\Dashboard\Tab\Presentation\GalleryPresenter;
use App\Models\Department;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
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

    public function test_gallery_renders_and_loads_more()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Photo::factory()->count(6)->create(['department_id' => 'IT']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Gallery::class)
            ->assertHasNoErrors()
            ->call('loadMore')
            ->assertHasNoErrors();
    }

    public function test_gallery_renders_mixed_image_and_video_paths()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create([
            'department_id' => 'IT',
            'path' => [
                'gallery/img-' . uniqid() . '.jpg',
                'gallery/vid-' . uniqid() . '.mp4',
            ],
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Gallery::class)
            ->assertHasNoErrors()
            ->assertSee('<video', false)
            ->assertSee('data-type="html5video"', false)
            ->assertSee('galleryPreviewPlay', false);
    }

    public function test_photos_are_scoped_to_users_department_id()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->count(3)->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);
        Photo::factory()->count(2)->create(['department_id' => 'HR', 'departments' => null, 'event_date' => '2026-01-01']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);

        $this->assertSame(3, $component->instance()->totalPhotos);
        $this->assertCount(3, $component->instance()->photos);
    }

    public function test_total_photos_cache_is_per_department_and_follows_photo_writes()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $itUser = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $itUser->id, 'department_id' => 'IT']);
        $hrUser = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $hrUser->id, 'department_id' => 'HR']);

        Photo::factory()->count(2)->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);

        Livewire::withoutLazyLoading();
        $this->assertSame(2, Livewire::actingAs($itUser)->test(Gallery::class)->instance()->totalPhotos);

        Livewire::withoutLazyLoading();
        $this->assertSame(0, Livewire::actingAs($hrUser)->test(Gallery::class)->instance()->totalPhotos, 'the IT count must never be served to an HR viewer');

        Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);

        Livewire::withoutLazyLoading();
        $this->assertSame(3, Livewire::actingAs($itUser)->test(Gallery::class)->instance()->totalPhotos, 'a Photo write rotates the version so the cached count is recomputed');
    }

    public function test_photos_visible_via_departments_json_column_for_users_department()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create([
            'department_id' => null,
            'departments' => ['IT'],
            'event_date' => '2026-01-01',
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);

        $this->assertSame(1, $component->instance()->totalPhotos);
        $this->assertCount(1, $component->instance()->photos);
    }

    public function test_public_photos_with_null_department_and_empty_departments_are_visible()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create([
            'department_id' => null,
            'departments' => null,
            'event_date' => '2026-01-01',
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);

        $this->assertSame(1, $component->instance()->totalPhotos);
        $this->assertCount(1, $component->instance()->photos);
    }

    public function test_photo_with_other_department_id_is_hidden_from_user()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create([
            'department_id' => 'HR',
            'departments' => null,
            'event_date' => '2026-01-01',
        ]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);

        $this->assertSame(0, $component->instance()->totalPhotos);
        $this->assertCount(0, $component->instance()->photos);
    }

    private function makeVisibleAndInactivePhotos(): array
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        $visible = Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);
        $inactive = Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-02-01', 'active' => false]);

        return [$user, $visible, $inactive];
    }

    public function test_inactive_photos_are_hidden_from_list_and_totals()
    {
        [$user, $visible] = $this->makeVisibleAndInactivePhotos();

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);
        $this->assertSame(1, $component->instance()->totalPhotos);
        $this->assertSame([$visible->id], $component->get('photoIds'));
    }

    public function test_open_deep_link_on_inactive_photo_shows_nothing()
    {
        [$user, $visible, $inactive] = $this->makeVisibleAndInactivePhotos();

        Livewire::withoutLazyLoading();
        $deepLink = Livewire::actingAs($user)->withQueryParams(['open' => $inactive->id])->test(Gallery::class);
        $this->assertNull($deepLink->get('open'));
        $this->assertSame([$visible->id], $deepLink->get('photoIds'));
    }

    public function test_total_photos_computed_counts_only_visible_photos()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->count(4)->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);
        Photo::factory()->count(3)->create(['department_id' => null, 'departments' => null, 'event_date' => '2026-01-01']);
        Photo::factory()->count(2)->create(['department_id' => 'HR', 'departments' => null, 'event_date' => '2026-01-01']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Gallery::class)
            ->assertSet('totalPhotos', 7);
    }

    public function test_each_image_is_in_exactly_one_lightbox_slot_per_view_group()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $photo = Photo::factory()->create([
            'department_id' => 'IT',
            'departments' => null,
            'path' => array_map(fn($n) => "gallery/{$n}-" . uniqid() . '.jpg', ['a', 'b', 'c', 'd']),
        ]);

        $html = Livewire::actingAs($user)->test(Gallery::class)->html();

        $this->assertSame(4, substr_count($html, 'data-fancybox="gallery-' . $photo->id . '"'));
        $this->assertSame(4, substr_count($html, 'data-fancybox="gallery-wall-' . $photo->id . '"'));
    }

    public function test_photos_sharing_an_event_date_page_in_a_stable_order_without_repeats()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Photo::factory()->count(12)->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-05-01']);

        $component = Livewire::actingAs($user)->test(Gallery::class);
        $component->call('loadMore')->call('loadMore');
        $ids = $component->get('photoIds');

        $this->assertCount(12, $ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertSame(Photo::orderByDesc('id')->pluck('id')->all(), $ids);
    }

    public function test_load_more_appends_next_page_ids_until_exhausted()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Photo::factory()->count(12)->create(['department_id' => 'IT', 'departments' => null]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);
        $this->assertCount(5, $component->get('photoIds'));
        $this->assertTrue($component->get('hasMorePages'));

        $component->call('loadMore');
        $this->assertCount(10, $component->get('photoIds'));
        $this->assertTrue($component->get('hasMorePages'));

        $component->call('loadMore');
        $this->assertCount(12, $component->get('photoIds'));

        $component->call('loadMore');
        $this->assertFalse($component->get('hasMorePages'));
        $this->assertCount(12, $component->get('photoIds'));
    }

    public function test_load_more_is_noop_when_has_more_pages_already_false()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Photo::factory()->count(3)->create(['department_id' => 'IT', 'departments' => null]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);
        $this->assertCount(3, $component->get('photoIds'));
        $this->assertFalse($component->get('hasMorePages'));

        $component->call('loadMore');
        $this->assertCount(3, $component->get('photoIds'));
        $this->assertFalse($component->get('hasMorePages'));
    }

    public function test_initial_load_sets_has_more_pages_false_at_exact_per_page_boundary()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        Photo::factory()->count(5)->create(['department_id' => 'IT', 'departments' => null]);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);
        $this->assertCount(5, $component->get('photoIds'));
        $this->assertFalse($component->get('hasMorePages'));

        $component->call('loadMore');
        $this->assertFalse($component->get('hasMorePages'));
        $this->assertCount(5, $component->get('photoIds'));
    }

    public function test_load_initial_photos_auto_selects_first_photo_id()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        $latest = Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-03-01']);
        Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-02-01']);
        Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Gallery::class)
            ->assertSet('selectedPhotoId', $latest->id);
    }

    public function test_focus_mode_open_param_loads_single_visible_photo_only()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->count(4)->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);
        $focus = Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-02-01']);

        Livewire::withQueryParams(['open' => $focus->id])
            ->actingAs($user)
            ->test(Gallery::class)
            ->assertSet('open', $focus->id)
            ->assertSet('selectedPhotoId', $focus->id)
            ->assertSet('hasMorePages', false)
            ->assertSet('photoIds', [$focus->id]);
    }

    public function test_focus_mode_open_param_falls_back_to_full_list_when_photo_not_visible()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->count(5)->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);
        $hidden = Photo::factory()->create(['department_id' => 'HR', 'departments' => null, 'event_date' => '2026-02-01']);

        $component = Livewire::withQueryParams(['open' => $hidden->id])
            ->actingAs($user)
            ->test(Gallery::class);

        $this->assertNull($component->get('open'));
        $this->assertCount(5, $component->get('photoIds'));
        $this->assertFalse($component->get('hasMorePages'));
    }

    public function test_focus_mode_open_param_falls_back_to_full_list_when_photo_id_does_not_exist()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->count(5)->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-01-01']);

        $component = Livewire::withQueryParams(['open' => 999999])
            ->actingAs($user)
            ->test(Gallery::class);

        $this->assertNull($component->get('open'));
        $this->assertCount(5, $component->get('photoIds'));
        $this->assertFalse($component->get('hasMorePages'));
    }

    public function test_clear_focus_resets_open_and_reloads_initial_photos()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->count(6)->create(['department_id' => 'IT', 'departments' => null]);
        $focus = Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-02-01']);

        $component = Livewire::withQueryParams(['open' => $focus->id])
            ->actingAs($user)
            ->test(Gallery::class);
        $this->assertSame($focus->id, $component->get('open'));
        $this->assertSame([$focus->id], $component->get('photoIds'));

        $component->call('clearFocus');
        $this->assertNull($component->get('open'));
        $this->assertCount(5, $component->get('photoIds'));
        $this->assertTrue($component->get('hasMorePages'));
    }

    public function test_empty_gallery_renders_empty_state_message()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Gallery::class)
            ->assertSee('گالری هنوز خالی است', false);
    }

    public function test_month_filter_renders_when_photos_have_event_dates()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-03-15']);
        Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-05-10']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Gallery::class)
            ->assertSee('فیلتر تاریخ', false);
    }

    public function test_presenter_months_returns_unique_keys_sorted_desc_by_event_date()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);

        $photos = collect([
            Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-03-15']),
            Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-03-20']),
            Photo::factory()->create(['department_id' => 'IT', 'departments' => null, 'event_date' => '2026-05-10']),
        ]);

        $months = (new GalleryPresenter())->months($photos, 'event_date');

        $this->assertCount(2, $months);
        $this->assertSame(2, $months->pluck('key')->unique()->count());
        $this->assertSame('2026-05-10', $months->first()['sort']->toDateString());
    }

    public function test_scope_meta_icon_is_groups_for_multi_department_photo()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create([
            'department_id' => 'IT',
            'departments' => ['HR'],
            'event_date' => '2026-01-01',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Gallery::class)
            ->assertSee('>groups<', false);
    }

    public function test_scope_meta_icon_is_lock_for_single_department_photo()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create([
            'department_id' => 'IT',
            'departments' => null,
            'event_date' => '2026-01-01',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Gallery::class)
            ->assertSee('>lock<', false);
    }

    public function test_scope_meta_icon_is_public_for_photo_with_no_departments()
    {
        Photo::query()->delete();
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Photo::factory()->create([
            'department_id' => null,
            'departments' => null,
            'event_date' => '2026-01-01',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Gallery::class)
            ->assertSee('>public<', false);
    }

    public function test_toggle_view_to_wall_sets_view_and_persists_session()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class)
            ->call('toggleView', 'wall');

        $this->assertSame('wall', $component->instance()->view);
        $this->assertSame('wall', session('gallery_view_mode'));
    }

    public function test_toggle_view_ignores_invalid_value()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class)
            ->call('toggleView', 'bogus');

        $this->assertSame('filmstrip', $component->instance()->view);
        $this->assertNull(session('gallery_view_mode'));
    }

    public function test_mount_restores_wall_view_from_session()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        session(['gallery_view_mode' => 'wall']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)->test(Gallery::class);

        $this->assertSame('wall', $component->instance()->view);
    }

    public function test_mount_forces_filmstrip_when_open_set()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT', 'description' => 'IT']);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'department_id' => 'IT']);
        $photo = Photo::factory()->create(['department_id' => 'IT']);
        session(['gallery_view_mode' => 'wall']);

        Livewire::withoutLazyLoading();
        $component = Livewire::actingAs($user)
            ->withQueryParams(['open' => $photo->id])
            ->test(Gallery::class);

        $this->assertSame('filmstrip', $component->instance()->view);
    }

    public function test_is_video_detects_video_extensions_case_insensitively_and_strips_query_string()
    {
        $this->assertTrue(isVideo('http://x/y.mp4'));
        $this->assertTrue(isVideo('http://x/y.MP4'));
        $this->assertTrue(isVideo('http://x/y.webm?token=1'));
        $this->assertTrue(isVideo('http://x/y.mov'));
        $this->assertTrue(isVideo('http://x/y.ogg'));
        $this->assertFalse(isVideo('http://x/y.png'));
        $this->assertFalse(isVideo(''));
        $this->assertFalse(isVideo(null));
        $this->assertFalse(isVideo('https://cdn.example.com'));
    }

    public function test_collage_cell_data_returns_indexed_transform_and_video_flag_with_fallback()
    {
        $p = new GalleryPresenter();
        $cell = $p->collageCellData(0, 'http://x/y.png');
        $this->assertSame('z-20', $cell['transform']['z']);
        $this->assertSame('rotate-6', $cell['transform']['rotate']);
        $this->assertFalse($cell['isVideo']);
        $videoCell = $p->collageCellData(5, 'http://x/y.mp4');
        $this->assertSame('z-0', $videoCell['transform']['z']);
        $this->assertSame('', $videoCell['transform']['rotate']);
        $this->assertSame('', $videoCell['transform']['hover']);
        $this->assertTrue($videoCell['isVideo']);
    }

    public function test_media_type_label_returns_image_when_no_video_present()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $photo = Photo::factory()->create([
            'department_id' => 'IT',
            'path' => ['gallery/img-' . uniqid() . '.jpg'],
        ]);
        $this->assertSame(__('resources/gallery/strings.fields.type_image'), (new GalleryPresenter())->mediaTypeLabel($photo));
    }

    public function test_media_type_label_returns_video_when_all_attachments_are_video()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $photo = Photo::factory()->create([
            'department_id' => 'IT',
            'path' => ['gallery/vid-' . uniqid() . '.mp4'],
        ]);
        $this->assertSame(__('resources/gallery/strings.fields.type_video'), (new GalleryPresenter())->mediaTypeLabel($photo));
    }

    public function test_media_type_label_returns_mixed_when_image_and_video_both_present()
    {
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $photo = Photo::factory()->create([
            'department_id' => 'IT',
            'path' => [
                'gallery/img-' . uniqid() . '.jpg',
                'gallery/vid-' . uniqid() . '.mp4',
            ],
        ]);
        $this->assertSame(__('resources/gallery/strings.fields.type_mixed'), (new GalleryPresenter())->mediaTypeLabel($photo));
    }
}
