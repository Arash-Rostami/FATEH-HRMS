<?php

namespace Tests\Feature\Models;

use App\Models\Onboarding;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class OnboardingModelTest extends TestCase
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

    public function test_videos_cast_round_trips_collection_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $onboarding = Onboarding::factory()->create([
            'user_id' => $user->id,
            'videos' => ['https://example.com/a', 'https://example.com/b'],
        ]);

        $loaded = Onboarding::find($onboarding->id);

        $this->assertInstanceOf(Collection::class, $loaded->videos);
        $this->assertSame(['https://example.com/a', 'https://example.com/b'], $loaded->videos->all());
    }

    public function test_guides_cast_round_trips_collection_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $onboarding = Onboarding::factory()->create([
            'user_id' => $user->id,
            'guides' => ['guide-one', 'guide-two'],
        ]);

        $loaded = Onboarding::find($onboarding->id);

        $this->assertInstanceOf(Collection::class, $loaded->guides);
        $this->assertSame(['guide-one', 'guide-two'], $loaded->guides->all());
    }

    public function test_extras_cast_round_trips_array_object_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $onboarding = Onboarding::factory()->create([
            'user_id' => $user->id,
            'extras' => ['parking' => ['label' => 'پارکینگ']],
        ]);

        $loaded = Onboarding::find($onboarding->id);

        $this->assertInstanceOf(ArrayObject::class, $loaded->extras);
        $this->assertSame(['parking' => ['label' => 'پارکینگ']], $loaded->extras->toArray());
    }

    public function test_is_active_cast_round_trips_boolean_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $active = Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        $inactive = Onboarding::factory()->create(['user_id' => $user->id, 'is_active' => false]);

        $this->assertTrue(Onboarding::find($active->id)->is_active);
        $this->assertFalse(Onboarding::find($inactive->id)->is_active);
    }

    public function test_videos_cast_serializes_to_json_in_attributes(): void
    {
        $onboarding = new Onboarding();
        $onboarding->videos = ['a', 'b'];

        $this->assertSame('["a","b"]', $onboarding->getAttributes()['videos']);
        $this->assertInstanceOf(Collection::class, $onboarding->videos);
        $this->assertSame(['a', 'b'], $onboarding->videos->all());
    }

    public function test_guides_cast_serializes_to_json_in_attributes(): void
    {
        $onboarding = new Onboarding();
        $onboarding->guides = ['x'];

        $this->assertSame('["x"]', $onboarding->getAttributes()['guides']);
        $this->assertInstanceOf(Collection::class, $onboarding->guides);
        $this->assertSame(['x'], $onboarding->guides->all());
    }

    public function test_extras_cast_serializes_to_json_in_attributes(): void
    {
        $onboarding = new Onboarding();
        $onboarding->extras = ['k' => 'v'];

        $this->assertSame('{"k":"v"}', $onboarding->getAttributes()['extras']);
        $this->assertInstanceOf(ArrayObject::class, $onboarding->extras);
        $this->assertSame('v', $onboarding->extras['k']);
    }

    public function test_videos_cast_returns_null_when_unset_in_memory(): void
    {
        $onboarding = new Onboarding();

        $this->assertNull($onboarding->videos);
    }

    public function test_guides_cast_returns_null_when_unset_in_memory(): void
    {
        $onboarding = new Onboarding();

        $this->assertNull($onboarding->guides);
    }

    public function test_extras_cast_returns_null_when_unset_in_memory(): void
    {
        $onboarding = new Onboarding();

        $this->assertNull($onboarding->extras);
    }

    public function test_admin_options_groups_all_categories_with_items(): void
    {
        $options = Onboarding::adminOptions();

        $this->assertSame(
            ['logistics', 'workplace', 'technology', 'time_policy', 'people', 'benefits', 'support', 'growth', 'feedback'],
            array_keys($options)
        );

        foreach ($options as $category => $block) {
            $this->assertArrayHasKey('title', $block);
            $this->assertArrayHasKey('items', $block);
            $this->assertNotEmpty($block['items']);
            foreach ($block['items'] as $item) {
                $this->assertArrayHasKey('key', $item);
                $this->assertArrayHasKey('icon', $item);
                $this->assertArrayHasKey('label', $item);
                $this->assertArrayHasKey('description', $item);
            }
        }

        $this->assertSame('parking', $options['logistics']['items'][0]['key']);
        $this->assertSame('local_parking', $options['logistics']['items'][0]['icon']);
    }

    public function test_auto_title_returns_label_for_known_key_and_fallback_for_unknown(): void
    {
        $this->assertSame(Onboarding::extraLabel('parking'), Onboarding::autoTitle('parking'));
        $this->assertSame('آیتم بدون عنوان (does_not_exist)', Onboarding::autoTitle('does_not_exist'));
    }

    public function test_category_title_returns_persian_title_for_known_categories(): void
    {
        $this->assertSame('رفت و آمد و تردد', Onboarding::categoryTitle('logistics'));
        $this->assertSame('محیط کار', Onboarding::categoryTitle('workplace'));
        $this->assertSame('فناوری و ابزار', Onboarding::categoryTitle('technology'));
        $this->assertSame('زمان و مقررات', Onboarding::categoryTitle('time_policy'));
        $this->assertSame('همکاران و فرهنگ', Onboarding::categoryTitle('people'));
        $this->assertSame('مزایا و رفاه', Onboarding::categoryTitle('benefits'));
        $this->assertSame('پشتیبانی و تماس', Onboarding::categoryTitle('support'));
        $this->assertSame('رشد و یادگیری', Onboarding::categoryTitle('growth'));
        $this->assertSame('بازخورد', Onboarding::categoryTitle('feedback'));
    }

    public function test_category_title_returns_fallback_for_unknown_category(): void
    {
        $this->assertSame('آیتم بدون عنوان (unknown_cat)', Onboarding::categoryTitle('unknown_cat'));
    }

    public function test_extra_description_returns_string_for_known_key_and_null_for_unknown(): void
    {
        $this->assertNotNull(Onboarding::extraDescription('parking'));
        $this->assertNull(Onboarding::extraDescription('does_not_exist'));
    }

    public function test_extra_icon_returns_icon_for_known_key_and_default_for_unknown(): void
    {
        $this->assertSame('local_parking', Onboarding::extraIcon('parking'));
        $this->assertSame('info', Onboarding::extraIcon('does_not_exist'));
    }

    public function test_extra_icons_returns_full_icons_array_with_default(): void
    {
        $icons = Onboarding::extraIcons();

        $this->assertArrayHasKey('parking', $icons);
        $this->assertSame('local_parking', $icons['parking']);
        $this->assertArrayHasKey('default', $icons);
        $this->assertSame('info', $icons['default']);
    }

    public function test_extra_label_returns_label_for_known_key_and_fallback_for_unknown(): void
    {
        $this->assertSame('🚗 پارکینگ و خودرو', Onboarding::extraLabel('parking'));
        $this->assertSame('آیتم بدون عنوان (missing)', Onboarding::extraLabel('missing'));
    }

    public function test_fallback_label_formats_key_via_reflection(): void
    {
        $method = new ReflectionMethod(Onboarding::class, 'fallbackLabel');
        $method->setAccessible(true);

        $this->assertSame('آیتم بدون عنوان (xyz)', $method->invoke(null, 'xyz'));
    }

    public function test_deleting_removes_video_and_guide_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('onboarding/videos/a.mp4', 'content');
        Storage::disk('public')->put('onboarding/videos/a-thumb.jpg', 'content');
        Storage::disk('public')->put('onboarding/guides/g.pdf', 'content');
        $user = User::factory()->create(['status' => 'active']);
        $onboarding = Onboarding::factory()->create([
            'user_id' => $user->id,
            'videos' => [['title' => 'Intro', 'url' => 'onboarding/videos/a.mp4', 'thumbnail' => 'onboarding/videos/a-thumb.jpg', 'duration' => 60]],
            'guides' => [['title' => 'Guide', 'url' => 'onboarding/guides/g.pdf', 'ext' => 'pdf', 'size' => 100]],
        ]);

        $onboarding->delete();

        Storage::disk('public')->assertMissing('onboarding/videos/a.mp4');
        Storage::disk('public')->assertMissing('onboarding/videos/a-thumb.jpg');
        Storage::disk('public')->assertMissing('onboarding/guides/g.pdf');
    }

    public function test_deleting_with_no_videos_or_guides_does_not_error(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active']);
        $onboarding = Onboarding::factory()->create([
            'user_id' => $user->id,
            'videos' => null,
            'guides' => null,
        ]);

        $onboarding->delete();

        $this->assertDatabaseMissing('onboardings', ['id' => $onboarding->id]);
    }
}