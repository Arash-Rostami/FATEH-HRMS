<?php

namespace Tests\Feature\Models;

use App\Enums\ProfileDetailGroup;
use App\Jobs\ReconcileNudge;
use App\Models\Department;
use App\Models\Feed;
use App\Models\Post;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProfileModelTest extends TestCase
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

    public function test_creating_a_second_profile_for_the_same_user_throws(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $this->expectException(RuntimeException::class);

        Profile::factory()->create(['user_id' => $user->id]);
    }

    public function test_get_position_label_attribute_returns_enum_label_for_known_position(): void
    {
        $profile = new Profile();
        $profile->position = 'ceo';

        $this->assertSame('مدیرعامل', $profile->getPositionLabelAttribute());
    }

    public function test_get_position_label_attribute_returns_custom_position_when_not_in_enum(): void
    {
        $profile = new Profile();
        $profile->position = 'custom-role';

        $this->assertSame('custom-role', $profile->getPositionLabelAttribute());
    }

    public function test_get_position_label_attribute_returns_default_for_null_position(): void
    {
        $profile = new Profile();
        $profile->position = null;

        $this->assertSame('کارشناس', $profile->getPositionLabelAttribute());
    }

    public function test_get_display_position_attribute_falls_back_to_position_label_when_no_custom_title(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'position' => 'ceo']);

        $this->assertSame('مدیرعامل', $profile->getDisplayPositionAttribute());
    }

    public function test_get_display_position_attribute_prefers_custom_title_when_set(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'position' => 'ceo']);
        $profile->details()->create([
            'key' => 'display_title',
            'section' => ProfileDetailGroup::Employment->value,
            'value' => 'رئیس واحد فناوری اطلاعات',
        ]);
        $profile->load('details');

        $this->assertSame('رئیس واحد فناوری اطلاعات', $profile->getDisplayPositionAttribute());
    }

    public function test_get_has_display_position_attribute_is_false_with_no_position_and_no_custom_title(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'position' => '']);

        $this->assertFalse($profile->getHasDisplayPositionAttribute());
    }

    public function test_get_has_display_position_attribute_is_true_with_custom_title_only(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'position' => '']);
        $profile->details()->create([
            'key' => 'display_title',
            'section' => ProfileDetailGroup::Employment->value,
            'value' => 'مشاور ارشد',
        ]);
        $profile->load('details');

        $this->assertTrue($profile->getHasDisplayPositionAttribute());
        $this->assertSame('مشاور ارشد', $profile->getDisplayPositionAttribute());
    }

    public function test_is_working_returns_true_for_working_employment_status(): void
    {
        $this->assertTrue((new Profile(['employment_status' => 'working']))->isWorking());
        $this->assertFalse((new Profile(['employment_status' => 'terminated']))->isWorking());
    }

    public function test_is_probational_returns_true_for_probational_employment_status(): void
    {
        $this->assertTrue((new Profile(['employment_status' => 'probational']))->isProbational());
        $this->assertFalse((new Profile(['employment_status' => 'working']))->isProbational());
    }

    public function test_is_terminated_returns_true_for_terminated_employment_status(): void
    {
        $this->assertTrue((new Profile(['employment_status' => 'terminated']))->isTerminated());
        $this->assertFalse((new Profile(['employment_status' => 'working']))->isTerminated());
    }

    public function test_age_returns_years_from_birthdate(): void
    {
        $profile = new Profile();
        $profile->birthdate = now()->subYears(30);

        $this->assertSame(30, $profile->age());
    }

    public function test_age_returns_null_when_birthdate_is_null(): void
    {
        $this->assertNull((new Profile())->age());
    }

    public function test_about_me_cast_round_trips_array_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'about_me' => ['bio' => 'test bio'],
        ]);

        $this->assertSame(['bio' => 'test bio'], $profile->fresh()->about_me);
    }

    public function test_attachments_cast_round_trips_array_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'attachments' => [['path' => 'docs/cv.pdf', 'name' => 'cv.pdf']],
        ]);

        $this->assertEquals([['path' => 'docs/cv.pdf', 'name' => 'cv.pdf']], $profile->fresh()->attachments);
    }

    public function test_favorite_colors_cast_round_trips_array_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'favorite_colors' => ['blue', 'red'],
        ]);

        $this->assertSame(['blue', 'red'], $profile->fresh()->favorite_colors);
    }

    public function test_number_of_children_cast_round_trips_integer(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'number_of_children' => '3',
        ]);

        $this->assertSame(3, $profile->fresh()->number_of_children);
    }

    public function test_birthdate_is_cast_to_date(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'birthdate' => '1990-05-15',
        ]);

        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $profile->fresh()->birthdate);
    }

    public function test_get_docs_path_token_is_deterministic_for_same_id(): void
    {
        $profile1 = new Profile();
        $profile1->id = 42;

        $profile2 = new Profile();
        $profile2->id = 42;

        $this->assertSame($profile1->getDocsPathToken(), $profile2->getDocsPathToken());
    }

    public function test_get_docs_path_token_differs_for_different_ids(): void
    {
        $profile1 = new Profile();
        $profile1->id = 1;

        $profile2 = new Profile();
        $profile2->id = 2;

        $this->assertNotSame($profile1->getDocsPathToken(), $profile2->getDocsPathToken());
    }

    public function test_get_initials_avatar_url_delegates_to_user_name(): void
    {
        $user = User::factory()->create(['status' => 'active', 'name' => 'Test User']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $url = $profile->getInitialsAvatarUrl();

        $this->assertNotEmpty($url);
    }

    public function test_sync_details_deletes_blank_values_and_upserts_filled_values(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $profile->details()->create([
            'key' => 'old_key',
            'section' => ProfileDetailGroup::Other->value,
            'value' => 'old_value',
        ]);

        $profile->syncDetails(['old_key' => '', 'new_key' => 'new_value']);

        $this->assertNull($profile->details()->where('key', 'old_key')->first());
        $this->assertSame('new_value', $profile->details()->where('key', 'new_key')->value('value'));
    }

    public function test_details_map_plucks_value_keyed_by_key(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $profile->details()->create(['key' => 'duty', 'section' => 'other', 'value' => 'manager']);
        $profile->load('details');

        $this->assertSame('manager', $profile->detailsMap()->get('duty'));
    }

    public function test_deleting_removes_image_and_attachment_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profiles/avatar.jpg', 'content');
        Storage::disk('public')->put('docs/cv.pdf', 'content');
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'image' => 'profiles/avatar.jpg',
            'attachments' => [['path' => 'docs/cv.pdf', 'name' => 'cv.pdf']],
        ]);

        $profile->delete();

        Storage::disk('public')->assertMissing('profiles/avatar.jpg');
        Storage::disk('public')->assertMissing('docs/cv.pdf');
    }

    public function test_deleting_with_no_image_or_attachments_does_not_error(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'image' => null,
            'attachments' => null,
        ]);

        $profile->delete();

        $this->assertDatabaseMissing('profiles', ['id' => $profile->id]);
    }

    public function test_is_birthday_today_matches_month_and_day_regardless_of_year(): void
    {
        $profile = new Profile(['birthdate' => now()->subYears(30), 'employment_status' => 'working']);

        $this->assertTrue($profile->isBirthdayToday());
    }

    public function test_is_birthday_today_false_when_month_or_day_differs(): void
    {
        $profile = new Profile(['birthdate' => now()->subYears(30)->addDay(), 'employment_status' => 'working']);

        $this->assertFalse($profile->isBirthdayToday());
    }

    public function test_is_birthday_today_false_when_birthdate_is_null(): void
    {
        $profile = new Profile(['birthdate' => null, 'employment_status' => 'working']);

        $this->assertFalse($profile->isBirthdayToday());
    }

    public function test_is_birthday_today_false_when_terminated_even_if_date_matches(): void
    {
        $profile = new Profile(['birthdate' => now()->subYears(30), 'employment_status' => 'terminated']);

        $this->assertFalse($profile->isBirthdayToday());
    }

    public function test_is_anniversary_today_matches_month_and_day_regardless_of_year(): void
    {
        $profile = new Profile(['start_date' => now()->subYears(5), 'employment_status' => 'working']);

        $this->assertTrue($profile->isAnniversaryToday());
    }

    public function test_is_anniversary_today_false_when_terminated_even_if_date_matches(): void
    {
        $profile = new Profile(['start_date' => now()->subYears(5), 'employment_status' => 'terminated']);

        $this->assertFalse($profile->isAnniversaryToday());
    }

    public function test_todays_occasion_type_returns_null_when_neither_matches(): void
    {
        $profile = new Profile([
            'birthdate' => now()->subYears(30)->addDay(),
            'start_date' => now()->subYears(5)->addDay(),
            'employment_status' => 'working',
        ]);

        $this->assertNull($profile->todaysOccasionType());
    }

    public function test_todays_occasion_type_returns_anniversary_when_only_anniversary_matches(): void
    {
        $profile = new Profile([
            'birthdate' => now()->subYears(30)->addDay(),
            'start_date' => now()->subYears(5),
            'employment_status' => 'working',
        ]);

        $this->assertSame('anniversary', $profile->todaysOccasionType());
    }

    public function test_todays_occasion_type_prefers_birthday_when_both_match(): void
    {
        $profile = new Profile([
            'birthdate' => now()->subYears(30),
            'start_date' => now()->subYears(5),
            'employment_status' => 'working',
        ]);

        $this->assertSame('birthday', $profile->todaysOccasionType());
    }

    public function test_occasion_tone_returns_the_expected_icon_and_label_per_type(): void
    {
        $this->assertSame('cake', Profile::occasionTone('birthday')['icon']);
        $this->assertSame('تولد', Profile::occasionTone('birthday')['label']);
        $this->assertSame('workspace_premium', Profile::occasionTone('anniversary')['icon']);
        $this->assertSame('سالگرد کاری', Profile::occasionTone('anniversary')['label']);
    }

    public function test_department_move_reconciles_nudges_for_old_dept_audiences_only(): void
    {
        $old = Department::firstOrCreate(['code' => 'PMA'], ['name' => 'PMA', 'description' => 'PMA']);
        $new = Department::firstOrCreate(['code' => 'PMB'], ['name' => 'PMB', 'description' => 'PMB']);
        $other = Department::firstOrCreate(['code' => 'PMC'], ['name' => 'PMC', 'description' => 'PMC']);
        $profile = Profile::factory()->create(['user_id' => User::factory()->create()->id, 'department_id' => $old->code]);

        $post = Post::factory()->create();
        $post->forceFill(['departments' => [$old->code]])->saveQuietly();
        $feed = Feed::factory()->create();
        $feed->forceFill(['departments' => [$old->code]])->saveQuietly();
        $unrelated = Post::factory()->create();
        $unrelated->forceFill(['departments' => [$other->code]])->saveQuietly();

        Queue::fake();

        $profile->update(['department_id' => $new->code]);

        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $j) => $j->subjectClass === Post::class && $j->subjectId === $post->id && $j->ruleKey === Post::NUDGE_KEY);
        Queue::assertPushed(ReconcileNudge::class, fn(ReconcileNudge $j) => $j->subjectClass === Feed::class && $j->subjectId === $feed->id && $j->ruleKey === Feed::NUDGE_KEY);
        Queue::assertNotPushed(ReconcileNudge::class, fn(ReconcileNudge $j) => $j->subjectClass === Post::class && $j->subjectId === $unrelated->id);
    }
}