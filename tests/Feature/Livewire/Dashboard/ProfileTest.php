<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Profile\About;
use App\Livewire\Dashboard\Profile\Credentials;
use App\Livewire\Dashboard\Profile\Details;
use App\Livewire\Dashboard\Profile\Documents;
use App\Livewire\Dashboard\Profile\Info;
use App\Livewire\Dashboard\Profile\Main;
use App\Livewire\Dashboard\Profile\Notifications;
use App\Livewire\Dashboard\Profile\Onboarding;
use App\Livewire\Dashboard\Profile\Settings;
use App\Livewire\Dashboard\Profile\Skills;
use App\Livewire\Dashboard\Profile\Tickets;
use App\Enums\PresenceStatus;
use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Enums\SkillRequestStatus;
use App\Models\Credential;
use App\Models\Onboarding as OnboardingModel;
use App\Models\Profile;
use App\Models\ProfileDetail;
use App\Models\ReleaseRequest;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\ProfileDetailCatalog;
use App\Livewire\Dashboard\Profile\Presentation\ProfilePresenter;
use App\Livewire\Dashboard\Profile\Presentation\SkillPresenter;
use App\Livewire\Dashboard\Profile\Presentation\SkillUserPresenter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    private function validInfoFormOverrides(): array
    {
        $uniq = uniqid();
        return [
            'gender' => 'male',
            'marital_status' => 'single',
            'number_of_children' => 0,
            'id_card_number' => 'IDC-' . $uniq,
            'id_booklet_number' => 'IDB-' . $uniq,
            'degree' => 'graduate',
            'field' => 'Computer Science',
            'cellphone' => '09120000000',
            'zip_code' => '1234567890',
            'address' => 'Tehran',
            'insurance' => 'INS-' . $uniq,
            'emergency_phone' => '09120000001',
            'emergency_relationship' => 'father',
            'work_experience' => '2-3',
        ];
    }

    public function test_about_save_persists_about_me()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(About::class)
            ->set('form.bio', 'probe bio')
            ->set('extraAnswers', ['favorite_book' => 'Dune'])
            ->call('save')
            ->assertHasNoErrors();

        $profile = Profile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('probe bio', $profile->about_me['bio']);
        $this->assertSame('Dune', $profile->about_me['favorite_book']);
    }

    public function test_about_save_preserves_field_added_concurrently_after_mount()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'about_me' => ['bio' => 'old bio']]);

        $component = Livewire::actingAs($user)->test(About::class);

        // Simulate an admin adding a new custom field via the Filament repeater
        // after this employee's About tab already loaded (stale in-memory snapshot).
        Profile::where('user_id', $user->id)->update([
            'about_me' => ['bio' => 'old bio', 'admin_added_field' => 'added while employee session was open'],
        ]);

        $component->set('form.bio', 'updated bio')->call('save')->assertHasNoErrors();

        $profile = Profile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('updated bio', $profile->about_me['bio']);
        $this->assertSame('added while employee session was open', $profile->about_me['admin_added_field']);
    }

    public function test_profile_main_renders_and_switches_tab()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertHasNoErrors()
            ->call('setTab', 'about')
            ->assertHasNoErrors();
    }

    public function test_profile_header_and_tabs_render_completion_percentage_in_persian_digits()
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'field' => null, 'id_card_number' => null, 'address' => null,
            'insurance' => null, 'emergency_phone' => null,
        ]);

        $expected = (new ProfilePresenter())->completion($user->fresh());
        $this->assertLessThan(100, $expected);

        $html = Livewire::actingAs($user)->test(Main::class)->html();

        $this->assertStringContainsString(convertToPersian($expected) . '%', $html);
    }

    public function test_profile_main_set_tab_coerces_unknown_tab_to_info()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('setTab', 'not-a-real-tab')
            ->assertSet('activeTab', 'info');
    }

    public function test_profile_off_tab_is_absent_and_query_tab_falls_back_to_info_and_returns_when_on()
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        try {
            config(['app.off' => ['skills']]);
            $this->assertArrayNotHasKey('skills', (new ProfilePresenter())->tabs());
            Livewire::actingAs($user)->withQueryParams(['tab' => 'skills'])->test(Main::class)->assertSet('activeTab', 'info');

            config(['app.off' => []]);
            $this->assertArrayHasKey('skills', (new ProfilePresenter())->tabs());
            Livewire::actingAs($user)->withQueryParams(['tab' => 'skills'])->test(Main::class)->assertSet('activeTab', 'skills');
        } finally {
            config(['app.off' => []]);
        }
    }

    public function test_profile_main_mount_coerces_unknown_query_tab_to_info()
    {
        Livewire::withoutLazyLoading();
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->withQueryParams(['tab' => 'not-a-real-tab'])
            ->test(Main::class)
            ->assertSet('activeTab', 'info');
    }

    public function test_credentials_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Credential::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Credentials::class)
            ->set('search', 'foo')
            ->assertHasNoErrors();
    }

    public function test_onboarding_renders()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        OnboardingModel::factory()->create(['is_active' => true, 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Onboarding::class)
            ->assertHasNoErrors();
    }

    public function test_onboarding_render_skips_videos_without_uploaded_file()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        OnboardingModel::factory()->create([
            'is_active' => true,
            'user_id' => $user->id,
            'videos' => [
                ['title' => 'zz-video-has-url', 'url' => 'https://example.com/intro.mp4', 'duration' => null, 'thumbnail' => null],
                ['title' => 'zz-video-no-url', 'url' => '', 'duration' => null, 'thumbnail' => null],
            ],
        ]);

        $html = Livewire::actingAs($user)->test(Onboarding::class)->html();

        $this->assertStringContainsString('zz-video-has-url', $html);
        $this->assertStringNotContainsString('zz-video-no-url', $html);
    }

    public function test_onboarding_computed_is_not_persisted_across_requests()
    {
        $attribute = (new \ReflectionMethod(Onboarding::class, 'onboarding'))
            ->getAttributes(\Livewire\Attributes\Computed::class)[0];

        $this->assertFalse($attribute->newInstance()->persist);
    }

    public function test_details_excludes_admin_only_keys_from_user_panel()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        $this->assertContains('unit', ProfileDetailCatalog::keys());
        $this->assertNotContains('unit', array_keys(ProfileDetailCatalog::userDefinitions()));
        $this->assertNotContains('section', array_keys(ProfileDetailCatalog::userDefinitions()));

        Livewire::actingAs($user)
            ->test(Details::class)
            ->assertHasNoErrors();
    }

    public function test_user_cannot_persist_admin_only_detail_keys()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.unit', 'Operations')
            ->set('form.values.section', 'Logistics')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(ProfileDetail::where('profile_id', $user->profile->id)->where('key', 'unit')->first());
        $this->assertNull(ProfileDetail::where('profile_id', $user->profile->id)->where('key', 'section')->first());
    }

    public function test_main_confirm_action_dispatches_requested_event()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Main::class)
            ->call('confirmAction', 'profile-image-delete')
            ->assertDispatched('profile-image-delete');
    }

    public function test_about_mount_seeds_core_fields_and_extracts_extra_answers_beyond_core_keys()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'about_me' => ['bio' => 'my bio', 'movies' => 'Inception', 'favorite_book' => 'Dune'],
        ]);

        $component = Livewire::actingAs($user)->test(About::class)->instance();

        $this->assertSame('my bio', $component->form->bio);
        $this->assertSame('Inception', $component->form->movies);
        $this->assertSame(['favorite_book' => 'Dune'], $component->extraAnswers);
    }

    public function test_about_mount_does_not_crash_when_core_key_holds_corrupted_array(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'about_me' => ['bio' => ['nested', 'array'], 'movies' => 'Inception'],
        ]);

        $component = Livewire::actingAs($user)->test(About::class)->instance();

        $this->assertSame('', $component->form->bio);
        $this->assertSame('Inception', $component->form->movies);
    }

    public function test_about_save_validates_bio_exceeding_max_length()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(About::class)
            ->set('form.bio', str_repeat('a', 2001))
            ->call('save')
            ->assertHasErrors(['form.bio']);
    }

    public function test_about_save_persists_all_core_keys_in_about_me_json()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(About::class)
            ->set('form.bio', 'bio text')
            ->set('form.movies', 'Matrix')
            ->set('form.music', 'Jazz')
            ->set('form.hobbies', 'Chess')
            ->set('form.food', 'Pizza')
            ->set('form.sports', 'Tennis')
            ->call('save')
            ->assertHasNoErrors();

        $aboutMe = Profile::where('user_id', $user->id)->firstOrFail()->about_me;
        $this->assertSame('Matrix', $aboutMe['movies']);
        $this->assertSame('Jazz', $aboutMe['music']);
        $this->assertSame('Chess', $aboutMe['hobbies']);
        $this->assertSame('Pizza', $aboutMe['food']);
        $this->assertSame('Tennis', $aboutMe['sports']);
    }

    public function test_about_save_rejects_extra_answer_exceeding_max_length()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(About::class)
            ->set('extraAnswers', ['favorite_book' => str_repeat('a', 2001)])
            ->call('save')
            ->assertHasErrors(['extraAnswers.favorite_book']);
    }

    public function test_details_save_aborts_when_user_has_no_profile_and_dispatches_error_toast()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.job_title', 'Engineer')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'ابتدا «اطلاعات فردی» را تکمیل و ذخیره کنید.', type: 'error');

        $this->assertNull(Profile::where('user_id', $user->id)->first());
    }

    public function test_details_save_persists_user_visible_text_detail_value()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.job_title', 'Senior Engineer')
            ->call('save')
            ->assertHasNoErrors();

        $detail = ProfileDetail::where('profile_id', $profile->id)->where('key', 'job_title')->firstOrFail();
        $this->assertSame('Senior Engineer', $detail->value);
    }

    public function test_details_save_clears_existing_detail_when_value_submitted_blank()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        ProfileDetail::create([
            'profile_id' => $profile->id,
            'section' => 'employment',
            'key' => 'job_title',
            'value' => 'Engineer',
        ]);

        $this->assertNotNull(ProfileDetail::where('profile_id', $profile->id)->where('key', 'job_title')->first());

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.job_title', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(ProfileDetail::where('profile_id', $profile->id)->where('key', 'job_title')->first());
    }

    public function test_details_date_field_assembles_jalali_parts_into_gregorian_date()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.prior_cooperation_startYear', 1400)
            ->set('form.values.prior_cooperation_startMonth', 5)
            ->set('form.values.prior_cooperation_startDay', 20)
            ->call('save')
            ->assertHasNoErrors();

        $expected = (new Jalalian(1400, 5, 20))->toCarbon()->format('Y-m-d');
        $detail = ProfileDetail::where('profile_id', $profile->id)->where('key', 'prior_cooperation_start')->firstOrFail();
        $this->assertSame($expected, $detail->value);
    }

    public function test_details_date_field_with_only_year_rejects_month_and_day_as_required_with()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.prior_cooperation_startYear', 1400)
            ->call('save')
            ->assertHasErrors([
                'form.values.prior_cooperation_startMonth',
                'form.values.prior_cooperation_startDay',
            ]);
    }

    public function test_details_save_rejects_invalid_select_value_against_catalog_options()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->set('form.values.blood_type', 'not-a-valid-blood-type')
            ->call('save')
            ->assertHasErrors(['form.values.blood_type']);
    }

    public function test_details_renders_city_field_in_legacy_string_array_mode()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Details::class)
            ->assertHasNoErrors()
            ->assertSee('objectMode: false', false)
            ->assertDontSee('objectMode: true', false);
    }

    public function test_credentials_search_filters_by_app_name()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'ProbeAlpha', 'username' => 'u1']);
        $other = Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'ProbeBeta', 'username' => 'u2']);

        $test = Livewire::actingAs($user)->test(Credentials::class)->set('search', 'ProbeAlpha');
        $creds = $test->instance()->credentials;

        $this->assertTrue($creds->contains('id', $match->id));
        $this->assertFalse($creds->contains('id', $other->id));

        $test->set('search', 'ProbeNothing')
            ->assertSeeHtml('wire:key="credentials-empty"');
    }

    public function test_credentials_search_filters_by_username()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'AlphaApp', 'username' => 'probeuser']);
        $other = Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'BetaApp', 'username' => 'otheruser']);

        $creds = Livewire::actingAs($user)->test(Credentials::class)->set('search', 'probeuser')->instance()->credentials;

        $this->assertTrue($creds->contains('id', $match->id));
        $this->assertFalse($creds->contains('id', $other->id));
    }

    public function test_credentials_search_never_returns_another_users_credentials()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $stranger = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $mine = Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'MineApp', 'username' => 'shareduser']);
        $theirs = Credential::factory()->create(['user_id' => $stranger->id, 'app_name' => 'TheirApp', 'username' => 'shareduser']);

        $creds = Livewire::actingAs($user)->test(Credentials::class)->set('search', 'shareduser')->instance()->credentials;

        // regression guard: an ungrouped orWhere(username) escaped the user_id scope and leaked other users' rows
        $this->assertTrue($creds->contains('id', $mine->id));
        $this->assertFalse($creds->contains('id', $theirs->id));
    }

    public function test_credentials_focus_mode_returns_only_the_pinned_record()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $cred1 = Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'Alpha']);
        Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'Beta']);

        $creds = Livewire::actingAs($user)->test(Credentials::class, ['open' => $cred1->id])->instance()->credentials;

        $this->assertCount(1, $creds);
        $this->assertSame($cred1->id, $creds->first()->id);
    }

    public function test_credentials_with_nonexistent_open_id_falls_back_to_unfiltered_list()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'Alpha']);
        Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'Beta']);

        $component = Livewire::actingAs($user)->test(Credentials::class, ['open' => 999999]);

        $this->assertCount(2, $component->instance()->credentials);
        $component->assertSet('open', null);
    }

    public function test_credentials_updated_search_resets_open_to_null()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $cred = Credential::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Credentials::class, ['open' => $cred->id])
            ->assertSet('open', $cred->id)
            ->set('search', 'foo')
            ->assertSet('open', null);
    }

    public function test_credentials_search_with_zero_string_filters_to_matching_credential_only()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $match = Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'App0Name', 'username' => 'u1']);
        Credential::factory()->create(['user_id' => $user->id, 'app_name' => 'Beta', 'username' => 'u2']);

        $creds = Livewire::actingAs($user)->test(Credentials::class)->set('search', '0')->instance()->credentials;

        $this->assertSame(1, $creds->count());
        $this->assertSame($match->id, $creds->first()->id);
    }

    public function test_info_save_persists_required_fields_and_assembles_jalali_birthdate()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $overrides = $this->validInfoFormOverrides();
        $profile = Profile::factory()->create(array_merge(['user_id' => $user->id], $overrides));

        Livewire::actingAs($user)
            ->test(Info::class)
            ->set('form.birthYear', 1400)
            ->set('form.birthMonth', 1)
            ->set('form.birthDay', 1)
            ->set('form.cellphone', '09120000099')
            ->call('save')
            ->assertHasNoErrors();

        $expectedBirthdate = (new Jalalian(1400, 1, 1))->toCarbon()->format('Y-m-d');
        $refreshed = Profile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($expectedBirthdate, $refreshed->birthdate->format('Y-m-d'));
        $this->assertSame('09120000099', $refreshed->cellphone);
    }

    public function test_info_save_validates_gender_required()
    {
        Livewire::actingAs(User::factory()->create(['status' => 'active', 'presence' => 'onsite']))
            ->test(Info::class)
            ->set('form.gender', '')
            ->call('save')
            ->assertHasErrors(['form.gender']);
    }

    public function test_info_save_rejects_duplicate_id_booklet_number_from_another_profile()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $other = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(array_merge(['user_id' => $user->id], $this->validInfoFormOverrides()));
        Profile::factory()->create(array_merge(['user_id' => $other->id], $this->validInfoFormOverrides(), [
            'id_booklet_number' => 'DUP-IDB-' . uniqid(),
        ]));

        $otherBooklet = Profile::where('user_id', $other->id)->first()->id_booklet_number;

        Livewire::actingAs($user)
            ->test(Info::class)
            ->set('form.id_booklet_number', $otherBooklet)
            ->call('save')
            ->assertHasErrors(['form.id_booklet_number']);
    }

    public function test_info_save_stores_uploaded_image_and_clears_form_image_property()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(array_merge(['user_id' => $user->id], $this->validInfoFormOverrides()));

        $file = UploadedFile::fake()->image('avatar.jpg');

        Livewire::actingAs($user)
            ->test(Info::class)
            ->set('form.image', $file)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.image', null);

        $this->assertNotNull(Profile::where('user_id', $user->id)->first()->image);
    }

    public function test_info_rejects_non_image_avatar_file_without_crashing()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(array_merge(['user_id' => $user->id], $this->validInfoFormOverrides()));

        $file = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test(Info::class)
            ->set('form.image', $file)
            ->assertHasErrors(['form.image'])
            ->assertSet('form.image', null);
    }

    public function test_info_save_assigns_hr_department_when_user_has_no_profile()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $overrides = $this->validInfoFormOverrides();

        $test = Livewire::actingAs($user)->test(Info::class);
        foreach ($overrides as $key => $value) {
            $test = $test->set('form.' . $key, $value);
        }
        $test->call('save')->assertHasNoErrors();

        $this->assertSame('HR', Profile::where('user_id', $user->id)->firstOrFail()->department_id);
    }

    public function test_info_save_persists_favorite_colors_array()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(array_merge(['user_id' => $user->id], $this->validInfoFormOverrides()));

        Livewire::actingAs($user)
            ->test(Info::class)
            ->set('form.favoriteColors', ['#ff0000', '#00ff00'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['#ff0000', '#00ff00'], Profile::where('user_id', $user->id)->first()->favorite_colors);
    }

    public function test_info_delete_image_removes_profile_image_via_confirm_delete_event()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'image' => 'profiles/images/to-delete.jpg']);

        Livewire::actingAs($user)
            ->test(Info::class)
            ->call('deleteImage')
            ->assertDispatched('toast', message: 'تصویر پروفایل با موفقیت حذف شد.', type: 'success');

        $this->assertNull(Profile::where('user_id', $user->id)->first()->image);
    }

    public function test_info_delete_image_dispatches_no_success_toast_when_no_image_exists()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id, 'image' => null]);

        Livewire::actingAs($user)
            ->test(Info::class)
            ->call('deleteImage')
            ->assertNotDispatched('toast', message: 'تصویر پروفایل با موفقیت حذف شد.', type: 'success');

        $this->assertNull(Profile::where('user_id', $user->id)->first()->image);
    }

    public function test_documents_show_upload_confirmation_sets_pending_state_and_dispatches_open_confirmation()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', $file)
            ->assertSet('pendingUploadKey', 'shenasnameh')
            ->assertSet('pendingFileName', 'scan.jpg')
            ->assertDispatched('open-confirmation');
    }

    public function test_documents_show_custom_upload_confirmation_validates_custom_type_required()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->call('showCustomUploadConfirmation')
            ->assertHasErrors(['form.customType']);
    }

    public function test_documents_standard_upload_shows_inline_error_on_card_when_rejected()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->create('scan.pdf', 11000, 'application/pdf');

        $component = Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', $file)
            ->assertHasErrors(['form.files.shenasnameh']);

        $this->assertStringContainsString('حجم فایل نباید بیشتر از 10 مگابایت باشد.', $component->html());
    }

    public function test_documents_standard_doc_cards_carry_status_scoped_wire_keys()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->assertSeeHtml('wire:key="profile-doc-shenasnameh-empty"')
            ->set('form.files.shenasnameh', UploadedFile::fake()->image('scan.jpg'))
            ->assertSeeHtml('wire:key="profile-doc-shenasnameh-pending"');
    }

    public function test_documents_standard_upload_persists_attachment_with_standard_category()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', $file)
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $attachments = collect($profile->refresh()->attachments);
        $standard = $attachments->where('category', 'standard')->where('key', 'shenasnameh');
        $this->assertCount(1, $standard);
        $this->assertNotNull($standard->first()['path']);
    }

    public function test_documents_custom_upload_persists_attachment_with_custom_category()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->image('custom-doc.jpg');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.customType', 'گواهی سلامت')
            ->set('form.customFile', $file)
            ->call('showCustomUploadConfirmation')
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $attachments = collect($profile->refresh()->attachments);
        $custom = $attachments->where('category', 'custom')->where('key', 'گواهی سلامت');
        $this->assertCount(1, $custom);
        $this->assertNotNull($custom->first()['path']);
        $this->assertStringContainsString('doc_custom_گواهی سلامت_', $custom->first()['path']);
    }

    public function test_documents_custom_upload_with_different_persian_titles_does_not_collide()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.customType', 'گزارش مالی')
            ->set('form.customFile', UploadedFile::fake()->image('first.jpg'))
            ->call('showCustomUploadConfirmation')
            ->call('confirmUpload')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.customType', 'برنامه کاری')
            ->set('form.customFile', UploadedFile::fake()->image('second.jpg'))
            ->call('showCustomUploadConfirmation')
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $custom = collect($profile->refresh()->attachments)->where('category', 'custom');
        $this->assertCount(2, $custom);
        $this->assertSame(['گزارش مالی', 'برنامه کاری'], $custom->pluck('key')->values()->all());
    }

    public function test_documents_custom_upload_with_same_persian_title_replaces_previous_file()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.customType', 'گزارش مالی')
            ->set('form.customFile', UploadedFile::fake()->image('first.jpg'))
            ->call('showCustomUploadConfirmation')
            ->call('confirmUpload')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.customType', 'گزارش مالی')
            ->set('form.customFile', UploadedFile::fake()->image('updated.jpg'))
            ->call('showCustomUploadConfirmation')
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $custom = collect($profile->refresh()->attachments)->where('category', 'custom');
        $this->assertCount(1, $custom);
    }

    public function test_documents_custom_reupload_replaces_legacy_slug_named_file()
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $legacyPath = 'profiles/docs/legacy/doc_custom_doc_1234567890.jpg';
        Storage::disk('public')->put($legacyPath, 'legacy');
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'attachments' => [
                ['key' => 'گواهی سلامت', 'path' => $legacyPath, 'category' => 'custom'],
            ],
        ]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.customType', 'گواهی سلامت')
            ->set('form.customFile', UploadedFile::fake()->image('new.jpg'))
            ->call('showCustomUploadConfirmation')
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $custom = collect($profile->refresh()->attachments)->where('category', 'custom');
        $this->assertCount(1, $custom);
        $this->assertNotSame($legacyPath, $custom->first()['path']);
        Storage::disk('public')->assertMissing($legacyPath);
    }

    public function test_documents_standard_upload_replaces_existing_attachment_for_same_key()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'attachments' => [
                ['key' => 'shenasnameh', 'path' => 'profiles/docs/old/doc_standard_shenasnameh_old.jpg', 'category' => 'standard'],
            ],
        ]);

        $file = UploadedFile::fake()->image('new-scan.jpg');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', $file)
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $standard = collect($profile->refresh()->attachments)
            ->where('category', 'standard')
            ->where('key', 'shenasnameh');
        $this->assertCount(1, $standard);
        $this->assertStringContainsString('doc_standard_shenasnameh_', $standard->first()['path']);
        $this->assertStringNotContainsString('doc_standard_shenasnameh_old.jpg', $standard->first()['path']);
    }

    public function test_documents_remove_file_unsets_form_files_key()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', $file)
            ->call('removeFile', 'shenasnameh')
            ->assertSet('form.files', [])
            ->assertHasNoErrors();
    }

    public function test_document_upload_path_uses_non_enumerable_token_not_raw_profile_id()
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', $file)
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $attachment = collect($profile->refresh()->attachments)->firstWhere('key', 'shenasnameh');
        $this->assertNotNull($attachment);
        $this->assertStringNotContainsString("profiles/docs/{$profile->id}/", $attachment['path']);
        $this->assertSame($profile->getDocsPathToken(), basename(dirname($attachment['path'])));
    }

    public function test_document_reupload_deletes_old_file_from_disk()
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', UploadedFile::fake()->image('scan1.jpg'))
            ->call('confirmUpload')
            ->assertHasNoErrors();

        $oldPath = collect($profile->refresh()->attachments)->firstWhere('key', 'shenasnameh')['path'];
        Storage::disk('public')->assertExists($oldPath);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', UploadedFile::fake()->image('scan2.jpg'))
            ->call('confirmUpload')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_info_save_deletes_old_avatar_image_on_replace()
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $oldImage = UploadedFile::fake()->image('old.jpg')->store('profiles/images', 'public');
        Profile::factory()->create(array_merge(
            ['user_id' => $user->id, 'image' => $oldImage],
            $this->validInfoFormOverrides()
        ));

        Storage::disk('public')->assertExists($oldImage);

        Livewire::actingAs($user)
            ->test(Info::class)
            ->set('form.image', UploadedFile::fake()->image('new.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($oldImage);
        $this->assertNotNull(Profile::where('user_id', $user->id)->first()->image);
    }

    public function test_info_save_immediately_reflects_new_avatar_via_refreshed_relation()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $overrides = $this->validInfoFormOverrides();

        $test = Livewire::actingAs($user)->test(Info::class)->set('form.image', UploadedFile::fake()->image('avatar.jpg'));
        foreach ($overrides as $key => $value) {
            $test = $test->set('form.' . $key, $value);
        }
        $test->call('save')->assertHasNoErrors();

        $this->assertNotSame($user->getInitialsAvatarUrl(), $test->instance()->existingImage);
    }

    public function test_profile_completion_excludes_admin_only_fields_and_reaches_full_percentage()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => 'HR',
            'gender' => 'male',
            'marital_status' => 'single',
            'id_card_number' => 'IDC-' . uniqid(),
            'degree' => 'graduate',
            'field' => 'Computer Science',
            'birthdate' => now(),
            'cellphone' => '09120000000',
            'address' => 'Tehran',
            'insurance' => 'INS-' . uniqid(),
            'emergency_phone' => '09120000001',
        ]);

        $completion = (new ProfilePresenter())->completion($user->fresh());

        $this->assertSame(100, $completion);
    }

    public function test_missing_field_labels_returns_empty_for_a_fully_completed_profile()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => 'HR',
            'gender' => 'male',
            'marital_status' => 'single',
            'id_card_number' => 'IDC-' . uniqid(),
            'degree' => 'graduate',
            'field' => 'Computer Science',
            'birthdate' => now(),
            'cellphone' => '09120000000',
            'address' => 'Tehran',
            'insurance' => 'INS-' . uniqid(),
            'emergency_phone' => '09120000001',
        ]);

        $this->assertSame([], (new ProfilePresenter())->missingFieldLabels($user->fresh()));
    }

    public function test_missing_field_labels_lists_only_the_unfilled_gating_fields()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => 'HR',
            'gender' => 'male',
            'marital_status' => 'single',
            'id_card_number' => null,
            'degree' => 'graduate',
            'field' => 'Computer Science',
            'birthdate' => now(),
            'cellphone' => '09120000000',
            'address' => 'Tehran',
            'insurance' => 'INS-' . uniqid(),
            'emergency_phone' => null,
        ]);

        $missing = (new ProfilePresenter())->missingFieldLabels($user->fresh());

        $this->assertSame(['شماره ملی', 'تلفن ضروری'], $missing);
    }

    public function test_profile_header_shows_a_clickable_missing_fields_popover_only_when_incomplete(): void
    {
        $incomplete = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $incomplete->id, 'cellphone' => null]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($incomplete)
            ->test(Main::class)
            ->assertSeeHtml('material-symbols-rounded text-[14px] normal-case')
            ->assertSee('فیلدهای ناقص:')
            ->assertSee('تلفن همراه');

        $complete = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create([
            'user_id' => $complete->id,
            'department_id' => 'HR',
            'gender' => 'male',
            'marital_status' => 'single',
            'id_card_number' => 'IDC-' . uniqid(),
            'degree' => 'graduate',
            'field' => 'Computer Science',
            'birthdate' => now(),
            'cellphone' => '09120000000',
            'address' => 'Tehran',
            'insurance' => 'INS-' . uniqid(),
            'emergency_phone' => '09120000001',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($complete)
            ->test(Main::class)
            ->assertDontSee('فیلدهای ناقص:');
    }

    public function test_presenter_position_prefers_custom_display_title(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $user->id, 'position' => 'ceo']);
        $profile->details()->create([
            'key' => 'display_title',
            'section' => 'employment',
            'value' => 'مشاور ارشد فناوری',
        ]);

        $this->assertSame('مشاور ارشد فناوری', (new ProfilePresenter())->position($user->fresh()));
    }

    public function test_presenter_position_falls_back_to_known_enum_label_without_custom_title(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'position' => 'ceo']);

        $this->assertSame('مدیرعامل', (new ProfilePresenter())->position($user->fresh()));
    }

    public function test_presenter_position_falls_back_to_employee_label_for_blank_or_unknown_position(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'position' => '']);

        $this->assertSame('کارمند', (new ProfilePresenter())->position($user->fresh()));

        $userWithGarbage = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $userWithGarbage->id, 'position' => 'not-a-real-position']);

        $this->assertSame('کارمند', (new ProfilePresenter())->position($userWithGarbage->fresh()));
    }

    public function test_presenter_position_falls_back_to_employee_label_when_profile_missing(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertSame('کارمند', (new ProfilePresenter())->position($user));
    }

    public function test_about_save_extra_answers_cannot_override_validated_bio()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(About::class)
            ->set('form.bio', 'validated bio')
            ->set('extraAnswers', ['bio' => 'malicious override'])
            ->call('save')
            ->assertHasNoErrors();

        $profile = Profile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('validated bio', $profile->about_me['bio']);
    }

    public function test_onboarding_computed_prefers_user_specific_record_over_global_active()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $global = OnboardingModel::factory()->create(['is_active' => true, 'user_id' => null, 'welcome' => 'global marker']);
        $specific = OnboardingModel::factory()->create(['is_active' => true, 'user_id' => $user->id, 'welcome' => 'user marker']);

        $onboarding = Livewire::actingAs($user)->test(Onboarding::class)->instance()->onboarding;

        $this->assertNotNull($onboarding);
        $this->assertSame($specific->id, $onboarding->id);
    }

    public function test_onboarding_computed_excludes_inactive_user_specific_record()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $active = OnboardingModel::factory()->create(['is_active' => true, 'user_id' => $user->id, 'welcome' => 'active marker']);
        $inactive = OnboardingModel::factory()->create(['is_active' => false, 'user_id' => $user->id, 'welcome' => 'inactive marker']);

        $onboarding = Livewire::actingAs($user)->test(Onboarding::class)->instance()->onboarding;

        $this->assertNotNull($onboarding);
        $this->assertSame($active->id, $onboarding->id);
        $this->assertNotSame($inactive->id, $onboarding->id);
    }

    public function test_documents_confirm_upload_aborts_when_user_has_no_profile(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $file = UploadedFile::fake()->image('scan.jpg');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('form.files.shenasnameh', $file)
            ->call('confirmUpload')
            ->assertDispatched('toast', message: 'ابتدا «اطلاعات فردی» را تکمیل و ذخیره کنید.', type: 'error');

        $this->assertNull(Profile::where('user_id', $user->id)->first());
    }

    public function test_profile_tabs_includes_skills_before_about()
    {
        $tabs = array_keys((new ProfilePresenter())->tabs());

        $skillsIndex = array_search('skills', $tabs);
        $aboutIndex = array_search('about', $tabs);

        $this->assertNotFalse($skillsIndex);
        $this->assertLessThan($aboutIndex, $skillsIndex);
    }

    public function test_request_skill_catalog_pick_creates_pending_skill_user_row()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Catalog Pick Skill ' . uniqid()]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.skillId', $skill->id)
            ->call('requestSkill')
            ->assertHasNoErrors();

        $row = SkillUser::where('user_id', $user->id)->where('skill_id', $skill->id)->firstOrFail();
        $this->assertSame(SkillRequestStatus::Pending, $row->status);
        $this->assertNull($row->requested_name);
    }

    public function test_request_skill_cannot_be_re_added_while_pending()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Pending Guard Skill ' . uniqid()]);
        SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.skillId', $skill->id)
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'درخواست این مهارت در حال بررسی است.', type: 'error');

        $this->assertSame(1, SkillUser::where('user_id', $user->id)->where('skill_id', $skill->id)->count());
    }

    public function test_request_skill_cannot_be_re_added_while_approved()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Approved Guard Skill ' . uniqid()]);
        SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.skillId', $skill->id)
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'این مهارت پیش‌تر برای شما تأیید شده است.', type: 'error');

        $this->assertSame(1, SkillUser::where('user_id', $user->id)->where('skill_id', $skill->id)->count());
    }

    public function test_request_skill_proposal_creates_inactive_draft_skill_with_requested_name()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $name = 'Proposed New Skill ' . uniqid();

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.proposedName', $name)
            ->call('requestSkill')
            ->assertHasNoErrors();

        $skill = Skill::where('name', $name)->firstOrFail();
        $this->assertFalse($skill->is_active);
        $this->assertFalse($skill->is_ghost);

        $row = SkillUser::where('user_id', $user->id)->where('skill_id', $skill->id)->firstOrFail();
        $this->assertSame($name, $row->requested_name);
        $this->assertSame(SkillRequestStatus::Pending, $row->status);
    }

    public function test_request_skill_proposal_with_same_name_from_different_user_reuses_draft_skill()
    {
        $first = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $second = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $name = 'Shared Proposal Skill ' . uniqid();

        Livewire::actingAs($first)->test(Skills::class)
            ->set('form.proposedName', $name)
            ->call('requestSkill')
            ->assertHasNoErrors();

        Livewire::actingAs($second)->test(Skills::class)
            ->set('form.proposedName', $name)
            ->call('requestSkill')
            ->assertHasNoErrors();

        $this->assertSame(1, Skill::where('name', $name)->count());
    }

    public function test_propose_name_matching_existing_active_skill_name_en_is_rejected_as_duplicate()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Skill::create(['name' => 'زبان برنامه‌نویسی ' . uniqid(), 'name_en' => 'ProposeDupSkillEN', 'is_active' => true, 'is_ghost' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.proposedName', 'ProposeDupSkillEN')
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'این مهارت در فهرست موجود است، لطفاً از فهرست انتخاب کنید.', type: 'error');

        $this->assertSame(1, Skill::where('name_en', 'ProposeDupSkillEN')->count());
        $this->assertSame(0, SkillUser::where('user_id', $user->id)->count());
    }

    public function test_propose_name_matching_existing_draft_skill_name_en_reuses_draft_skill()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $draft = Skill::create(['name' => 'پیش‌نویس ' . uniqid(), 'name_en' => 'ProposeReuseDraftEN', 'is_active' => false, 'is_ghost' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.proposedName', 'ProposeReuseDraftEN')
            ->call('requestSkill')
            ->assertHasNoErrors();

        $this->assertSame(1, Skill::where('name_en', 'ProposeReuseDraftEN')->count());
        $row = SkillUser::where('user_id', $user->id)->where('skill_id', $draft->id)->firstOrFail();
        $this->assertSame('ProposeReuseDraftEN', $row->requested_name);
    }

    public function test_re_request_after_rejection_reopens_same_catalog_pick_row()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Reopen Catalog Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'rejected', 'rejected_reason' => 'not needed']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('reRequest', $row->id)
            ->assertHasNoErrors();

        $fresh = $row->fresh();
        $this->assertSame($row->id, $fresh->id);
        $this->assertSame(SkillRequestStatus::Pending, $fresh->status);
        $this->assertNull($fresh->rejected_reason);
        $this->assertSame(1, SkillUser::where('user_id', $user->id)->where('skill_id', $skill->id)->count());
    }

    public function test_re_request_after_rejection_reopens_same_proposal_row_without_duplicating()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $name = 'Reopen Proposal Skill ' . uniqid();
        $draft = Skill::create(['name' => $name, 'is_active' => false, 'is_ghost' => false]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $draft->id, 'status' => 'rejected', 'requested_name' => $name, 'rejected_reason' => 'not aligned']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('reRequest', $row->id)
            ->assertHasNoErrors();

        $fresh = $row->fresh();
        $this->assertSame($row->id, $fresh->id);
        $this->assertSame(SkillRequestStatus::Pending, $fresh->status);
        $this->assertNull($fresh->rejected_reason);
        $this->assertSame($name, $fresh->requested_name);
        $this->assertSame(1, SkillUser::where('user_id', $user->id)->where('skill_id', $draft->id)->count());
    }

    public function test_skills_rows_carry_status_scoped_wire_keys()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $approvedSkill = Skill::create(['name' => 'Wire Key Approved Skill ' . uniqid()]);
        $approvedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $approvedSkill->id, 'status' => 'approved', 'approved_at' => now()]);
        $pendingSkill = Skill::create(['name' => 'Wire Key Pending Skill ' . uniqid()]);
        $pendingRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $pendingSkill->id, 'status' => 'pending']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->assertSeeHtml("wire:key=\"profile-skill-{$approvedRow->id}-approved\"")
            ->assertSeeHtml("wire:key=\"profile-skill-{$pendingRow->id}-other\"");
    }

    public function test_mark_used_stamps_last_used_at_and_context_for_approved_owner()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Mark Used Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('openMarkUsed', $row->id)
            ->set('markUsedContext', 'Used on project X')
            ->call('markUsed')
            ->assertHasNoErrors();

        $fresh = $row->fresh();
        $this->assertNotNull($fresh->last_used_at);
        $this->assertSame('Used on project X', $fresh->last_used_context);
    }

    public function test_mark_used_on_pending_skill_is_rejected_with_error_toast()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Mark Used Pending Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('openMarkUsed', $row->id)
            ->call('markUsed')
            ->assertDispatched('toast', message: 'فقط مهارت‌های تأییدشده را می‌توان به‌روزرسانی کرد.', type: 'error');

        $this->assertNull($row->fresh()->last_used_at);
    }

    public function test_mark_used_rejects_context_over_255_chars_with_error_toast()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Mark Used Overlong Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('openMarkUsed', $row->id)
            ->set('markUsedContext', str_repeat('a', 256))
            ->call('markUsed')
            ->assertHasErrors(['markUsedContext' => 'max']);

        $fresh = $row->fresh();
        $this->assertNull($fresh->last_used_at);
        $this->assertNull($fresh->last_used_context);
    }

    public function test_mark_used_accepts_context_at_exactly_255_chars()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Mark Used Boundary Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);
        $context = str_repeat('a', 255);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('openMarkUsed', $row->id)
            ->set('markUsedContext', $context)
            ->call('markUsed')
            ->assertHasNoErrors();

        $fresh = $row->fresh();
        $this->assertNotNull($fresh->last_used_at);
        $this->assertSame($context, $fresh->last_used_context);
    }

    public function test_toggle_privacy_flips_is_private_for_approved_row()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Toggle Privacy Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('togglePrivacy', $row->id);

        $this->assertTrue($row->fresh()->is_private);
    }

    public function test_toggle_privacy_on_pending_row_throws_and_leaves_value_unchanged()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Toggle Privacy Pending Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending', 'is_private' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('togglePrivacy', $row->id)
            ->assertDispatched('toast', message: 'فقط مهارت‌های تأییدشده امکان تغییر حریم خصوصی دارند.', type: 'error');

        $this->assertFalse($row->fresh()->is_private);
    }

    public function test_toggle_privacy_on_deleted_row_reports_model_not_found_but_not_a_rejected_toggle_on_pending_row()
    {
        Exceptions::fake();

        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Toggle Privacy Missing Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);
        $missingId = $row->id;
        $row->delete();

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('togglePrivacy', $missingId)
            ->assertDispatched('toast', message: 'خطایی غیرمنتظره رخ داد. لطفاً دوباره تلاش کنید.', type: 'error');

        Exceptions::assertReported(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Exceptions::fake();

        $pendingSkill = Skill::create(['name' => 'Toggle Privacy Pending Report Skill ' . uniqid()]);
        $pendingRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $pendingSkill->id, 'status' => 'pending']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('togglePrivacy', $pendingRow->id)
            ->assertDispatched('toast', type: 'error');

        Exceptions::assertNotReported(\RuntimeException::class);
    }

    public function test_toggle_mentoring_flips_is_mentoring_for_approved_row()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Toggle Mentoring Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_mentoring' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('toggleMentoring', $row->id);

        $this->assertTrue($row->fresh()->is_mentoring);
    }

    public function test_toggle_mentoring_on_pending_row_throws_and_leaves_value_unchanged()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Toggle Mentoring Pending Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $user->id, 'skill_id' => $skill->id, 'status' => 'pending', 'is_mentoring' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('toggleMentoring', $row->id)
            ->assertDispatched('toast', message: 'فقط مهارت‌های تأییدشده امکان راهنمایی دارند.', type: 'error');

        $this->assertFalse($row->fresh()->is_mentoring);
    }

    public function test_endorse_by_a_colleague_increments_endorsement_and_self_endorse_is_blocked()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Endorse Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now()]);

        Livewire::actingAs($owner)
            ->test(Skills::class)
            ->call('endorse', $row->id);

        $this->assertSame(0, $row->fresh()->endorsements_count);

        Livewire::actingAs($colleague)
            ->test(Skills::class)
            ->call('endorse', $row->id)
            ->assertDispatched('toast', message: 'مهارت با موفقیت تأیید شد.', type: 'success');

        $fresh = $row->fresh();
        $this->assertSame(1, $fresh->endorsements_count);
        $this->assertTrue($fresh->hasEndorser($colleague->id));
    }

    public function test_endorse_by_same_colleague_twice_is_blocked_from_double_counting()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Double Endorse Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsers' => [$colleague->id], 'endorsements_count' => 1]);

        Livewire::actingAs($colleague)
            ->test(Skills::class)
            ->call('endorse', $row->id)
            ->assertDispatched('toast', message: 'شما قبلاً این مهارت را تأیید کرده‌اید.', type: 'error');

        $this->assertSame(1, $row->fresh()->endorsements_count);
    }

    public function test_revoke_endorsement_then_re_endorse_is_allowed()
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Revoke Reendorse Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsers' => [$colleague->id], 'endorsements_count' => 1]);

        Livewire::actingAs($colleague)
            ->test(Skills::class)
            ->call('revokeEndorsement', $row->id);

        $this->assertSame(0, $row->fresh()->endorsements_count);

        Livewire::actingAs($colleague)
            ->test(Skills::class)
            ->call('endorse', $row->id)
            ->assertDispatched('toast', message: 'مهارت با موفقیت تأیید شد.', type: 'success');

        $fresh = $row->fresh();
        $this->assertSame(1, $fresh->endorsements_count);
        $this->assertTrue($fresh->hasEndorser($colleague->id));
    }

    public function test_own_skills_computed_sorts_endorsed_before_active_before_unused()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $endorser = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $endorsedSkill = Skill::create(['name' => 'Tier Endorsed Skill ' . uniqid()]);
        $activeSkill = Skill::create(['name' => 'Tier Active Skill ' . uniqid()]);
        $unusedSkill = Skill::create(['name' => 'Tier Unused Skill ' . uniqid()]);

        $endorsedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $endorsedSkill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsers' => [$endorser->id], 'endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP]);
        $activeRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $activeSkill->id, 'status' => 'approved', 'approved_at' => now(), 'last_used_at' => now()->subDay()]);
        $unusedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $unusedSkill->id, 'status' => 'approved', 'approved_at' => now()]);

        $ordered = Livewire::actingAs($user)->test(Skills::class)->instance()->ownSkills;

        $this->assertSame([$endorsedRow->id, $activeRow->id, $unusedRow->id], $ordered->pluck('id')->all());
    }

    public function test_own_skills_computed_breaks_active_tier_ties_by_endorsement_count_then_recency()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $moreEndorsedSkill = Skill::create(['name' => 'Tie Break More Endorsed Skill ' . uniqid()]);
        $lessEndorsedSkill = Skill::create(['name' => 'Tie Break Less Endorsed Skill ' . uniqid()]);
        $unendorsedSkill = Skill::create(['name' => 'Tie Break Unendorsed Skill ' . uniqid()]);

        $moreEndorsedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $moreEndorsedSkill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsements_count' => 2, 'last_used_at' => now()->subDay()]);
        $lessEndorsedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $lessEndorsedSkill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsements_count' => 1, 'last_used_at' => now()->subDay()]);
        $unendorsedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $unendorsedSkill->id, 'status' => 'approved', 'approved_at' => now(), 'endorsements_count' => 0, 'last_used_at' => now()->subDay()]);

        $ordered = Livewire::actingAs($user)->test(Skills::class)->instance()->ownSkills;

        $this->assertSame([$moreEndorsedRow->id, $lessEndorsedRow->id, $unendorsedRow->id], $ordered->pluck('id')->all());
    }

    public function test_own_skills_computed_sorts_approved_rows_before_pending_and_rejected()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $approvedSkill = Skill::create(['name' => 'Mixed Approved Skill ' . uniqid()]);
        $pendingSkill = Skill::create(['name' => 'Mixed Pending Skill ' . uniqid()]);
        $rejectedSkill = Skill::create(['name' => 'Mixed Rejected Skill ' . uniqid()]);

        $pendingRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $pendingSkill->id, 'status' => 'pending']);
        $rejectedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $rejectedSkill->id, 'status' => 'rejected']);
        $approvedRow = SkillUser::create(['user_id' => $user->id, 'skill_id' => $approvedSkill->id, 'status' => 'approved', 'approved_at' => now(), 'last_used_at' => now()->subDay()]);

        $ordered = Livewire::actingAs($user)->test(Skills::class)->instance()->ownSkills;

        $this->assertSame($approvedRow->id, $ordered->first()->id);
        $this->assertLessThan(
            $ordered->search(fn ($row) => $row->id === $pendingRow->id),
            $ordered->search(fn ($row) => $row->id === $approvedRow->id),
        );
        $this->assertLessThan(
            $ordered->search(fn ($row) => $row->id === $rejectedRow->id),
            $ordered->search(fn ($row) => $row->id === $approvedRow->id),
        );
    }

    public function test_skills_catalog_picker_renders_object_mode_grouped_options()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Skill::create([
            'name' => 'Probe Catalog Skill ' . uniqid(),
            'name_en' => 'Probe EN',
            'category' => 'فنی',
            'is_active' => true,
            'is_ghost' => false,
        ]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->assertHasNoErrors()
            ->assertSee('objectMode: true', false);
    }

    public function test_new_badge_visible_before_cutoff_and_hidden_after()
    {
        Carbon::setTestNow(SkillUser::newBadgeUntil()->subDay());
        $this->assertTrue((new ProfilePresenter())->newBadgeVisible());

        Carbon::setTestNow(SkillUser::newBadgeUntil()->addDay());
        $this->assertFalse((new ProfilePresenter())->newBadgeVisible());

        Carbon::setTestNow();
    }

    public function test_tabs_skills_entry_is_new_flag_matches_new_badge_visible(): void
    {
        Carbon::setTestNow(SkillUser::newBadgeUntil()->subDay());
        $this->assertTrue((new ProfilePresenter())->tabs()['skills']['isNew']);

        Carbon::setTestNow(SkillUser::newBadgeUntil()->addDay());
        $this->assertFalse((new ProfilePresenter())->tabs()['skills']['isNew']);

        Carbon::setTestNow();
    }

    public function test_mount_drains_queued_notices_dispatches_toast_and_clears_cache(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $dedupeKey = 'test.notice.' . uniqid();
        Cache::put("skill_notices:{$user->id}", [
            $dedupeKey => ['body' => 'یک اعلان آزمایشی', 'type' => 'success', 'created_at' => now()->timestamp],
        ], now()->addDays(7));

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->assertDispatched('toast', message: 'یک اعلان آزمایشی', type: 'success');

        $this->assertSame([], Cache::get("skill_notices:{$user->id}", []));
    }

    public function test_re_request_aborts_with_403_for_non_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $intruder = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'ReRequest Guard Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'rejected', 'rejected_reason' => 'no']);

        Livewire::actingAs($intruder)
            ->test(Skills::class)
            ->call('reRequest', $row->id)
            ->assertStatus(403);
    }

    public function test_request_skill_with_blank_skill_id_and_proposed_name_shows_selection_error(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'یک مهارت را از فهرست انتخاب کنید یا مهارت جدید پیشنهاد دهید.', type: 'error');
    }

    public function test_request_skill_with_inactive_catalog_skill_id_shows_not_available_error(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $draft = Skill::create(['name' => 'Inactive Pick Skill ' . uniqid(), 'is_active' => false, 'is_ghost' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.skillId', $draft->id)
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'مهارت انتخاب‌شده در دسترس نیست.', type: 'error');
    }

    public function test_request_skill_with_nonexistent_skill_id_shows_not_available_error(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.skillId', 999999999)
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'مهارت انتخاب‌شده در دسترس نیست.', type: 'error');
    }

    public function test_request_skill_proposal_matching_existing_active_catalog_name_shows_already_listed_error(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $name = 'Already Listed Skill ' . uniqid();
        Skill::create(['name' => $name, 'is_active' => true, 'is_ghost' => false]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.proposedName', $name)
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'این مهارت در فهرست موجود است، لطفاً از فهرست انتخاب کنید.', type: 'error');
    }

    public function test_request_skill_proposal_matching_own_pending_draft_shows_in_review_error(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $name = 'Pending Draft Reproposal Skill ' . uniqid();
        $draft = Skill::create(['name' => $name, 'is_active' => false, 'is_ghost' => false]);
        SkillUser::create(['user_id' => $user->id, 'skill_id' => $draft->id, 'status' => 'pending', 'requested_name' => $name]);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.proposedName', $name)
            ->call('requestSkill')
            ->assertDispatched('toast', message: 'درخواست این مهارت در حال بررسی است.', type: 'error');
    }

    public function test_request_skill_proposal_exceeding_max_length_is_rejected_without_creating_a_row(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $overlong = str_repeat('a', 256);

        Livewire::actingAs($user)
            ->test(Skills::class)
            ->set('form.proposedName', $overlong)
            ->call('requestSkill')
            ->assertDispatched('toast', function (string $event, array $params): bool {
                return $params['type'] === 'error';
            });

        $this->assertNull(Skill::where('name', $overlong)->first());
    }

    public function test_endorse_pending_skill_shows_not_yet_approved_error(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Endorse Pending Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        Livewire::actingAs($colleague)
            ->test(Skills::class)
            ->call('endorse', $row->id)
            ->assertDispatched('toast', message: 'این مهارت هنوز تأیید نشده است.', type: 'error');

        $this->assertSame(0, $row->fresh()->endorsements_count);
    }

    public function test_endorse_private_skill_shows_private_error(): void
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $colleague = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $skill = Skill::create(['name' => 'Endorse Private Skill ' . uniqid()]);
        $row = SkillUser::create(['user_id' => $owner->id, 'skill_id' => $skill->id, 'status' => 'approved', 'approved_at' => now(), 'is_private' => true]);

        Livewire::actingAs($colleague)
            ->test(Skills::class)
            ->call('endorse', $row->id)
            ->assertDispatched('toast', message: 'این مهارت خصوصی است و قابل تأیید نیست.', type: 'error');

        $this->assertSame(0, $row->fresh()->endorsements_count);
    }

    public function test_catalog_computed_formats_label_with_name_en_in_parens_and_falls_back_category(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $withEn = Skill::create(['name' => 'Catalog Label Skill ' . uniqid(), 'name_en' => 'Catalog Label EN', 'category' => 'Engineering', 'is_active' => true, 'is_ghost' => false]);
        $noCategory = Skill::create(['name' => 'Catalog No Category Skill ' . uniqid(), 'is_active' => true, 'is_ghost' => false]);

        $catalog = Livewire::actingAs($user)->test(Skills::class)->instance()->catalog;

        $withEnEntry = $catalog->firstWhere('value', $withEn->id);
        $this->assertSame('(Catalog Label EN) ' . $withEn->name, $withEnEntry['label']);
        $this->assertSame('Engineering', $withEnEntry['category']);

        $noCategoryEntry = $catalog->firstWhere('value', $noCategory->id);
        $this->assertSame('دسته‌بندی‌نشده', $noCategoryEntry['category']);
    }

    public function test_skill_user_presenter_last_used_label_returns_no_history_when_null(): void
    {
        $skillUser = new SkillUser();

        $this->assertSame('هنوز فرصت به‌کارگیری آن پیش نیامده', (new SkillUserPresenter())->lastUsedLabel($skillUser));
    }

    public function test_skill_user_presenter_mentor_and_privacy_labels_reflect_flags(): void
    {
        $presenter = new SkillUserPresenter();

        $mentor = new SkillUser(['is_mentoring' => true, 'is_private' => false]);
        $this->assertSame('آماده راهنمایی', $presenter->mentorLabel($mentor));
        $this->assertSame('عمومی', $presenter->privacyLabel($mentor));

        $nonMentorPrivate = new SkillUser(['is_mentoring' => false, 'is_private' => true]);
        $this->assertSame('', $presenter->mentorLabel($nonMentorPrivate));
        $this->assertSame('خصوصی', $presenter->privacyLabel($nonMentorPrivate));
    }

    public function test_skill_user_presenter_dormant_and_sole_endorsement_delegate_to_model(): void
    {
        $presenter = new SkillUserPresenter();

        $dormantEndorsed = new SkillUser(['endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP, 'last_used_at' => now()->subDays(SkillUser::ACTIVE_WINDOW_DAYS + 1)]);
        $this->assertTrue($presenter->isDormant($dormantEndorsed));

        $freshEndorsed = new SkillUser(['endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP, 'last_used_at' => now()->subDay()]);
        $this->assertFalse($presenter->isDormant($freshEndorsed));

        $this->assertTrue($presenter->isSoleEndorsement(new SkillUser(['endorsements_count' => 1])));
        $this->assertFalse($presenter->isSoleEndorsement(new SkillUser(['endorsements_count' => 2])));
    }

    public function test_skill_user_presenter_endorsement_progress_label_and_metal_classes(): void
    {
        $presenter = new SkillUserPresenter();

        $sole = new SkillUser(['endorsements_count' => 1]);
        $this->assertSame('تأیید یک همکار', $presenter->endorsementProgressLabel($sole));
        $this->assertStringContainsString('slate', $presenter->endorsementMetalClasses($sole));

        $saturated = new SkillUser(['endorsements_count' => SkillUser::ENDORSEMENT_SATURATION_CAP]);
        $this->assertSame('تأیید 4 همکار', $presenter->endorsementProgressLabel($saturated));
        $this->assertStringContainsString('amber', $presenter->endorsementMetalClasses($saturated));

        $none = new SkillUser(['endorsements_count' => 0]);
        $this->assertSame('', $presenter->endorsementMetalClasses($none));
    }

    public function test_skill_user_presenter_preload_and_avatar_stack_resolve_endorser_users(): void
    {
        $endorser1 = User::factory()->create(['status' => 'active']);
        $endorser2 = User::factory()->create(['status' => 'active']);
        $skillUser = new SkillUser(['endorsers' => [$endorser1->id, $endorser2->id]]);

        $presenter = new SkillUserPresenter();
        $presenter->preloadEndorsers([$skillUser]);

        $stack = $presenter->endorsersAvatarStack($skillUser);

        $this->assertSame([$endorser1->id, $endorser2->id], $stack->pluck('id')->all());
    }

    public function test_skill_presenter_category_label_falls_back_when_blank(): void
    {
        $presenter = new SkillPresenter();
        $withCategory = new Skill(['category' => 'Engineering']);
        $blankCategory = new Skill(['category' => null]);

        $this->assertSame('Engineering', $presenter->categoryLabel($withCategory));
        $this->assertSame('دسته‌بندی‌نشده', $presenter->categoryLabel($blankCategory));
    }

    public function test_skill_presenter_icon_falls_back_to_default_when_blank(): void
    {
        $presenter = new SkillPresenter();
        $skill = new Skill(['icon' => null]);

        $this->assertSame(\App\Enums\SkillIcon::default()->value, $presenter->icon($skill));
    }

    public function test_skill_presenter_display_label_prefers_english_name_only_in_english_locale(): void
    {
        $presenter = new SkillPresenter();
        $skill = new Skill(['name' => 'فارسی نام', 'name_en' => 'English Name']);

        app()->setLocale('fa');
        $this->assertSame('فارسی نام', $presenter->displayLabel($skill));

        app()->setLocale('en');
        $this->assertSame('English Name', $presenter->displayLabel($skill));

        app()->setLocale('fa');
    }

    public function test_profile_master_notification_legend_renders_all_subgroup_pills(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('فهرست')
            ->assertSee('سررسید')
            ->assertSee('تأیید')
            ->assertSee('پروژه')
            ->assertSee('تیکت')
            ->assertSee('پیام مستقیم')
            ->assertSee('گروه‌ها')
            ->assertSee('تقویم')
            ->assertSee('اخبار و گالری')
            ->assertSee('پیگیری')
            ->assertSee('گزارش');
    }

    public function test_profile_header_completion_link_uses_tab_query_key_not_active_tab(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)
            ->test(Main::class)
            ->assertSee('?tab=info', false)
            ->assertDontSee('?activeTab=info', false);
    }

    public function test_doc_status_derives_approved_pending_and_empty_from_parsed_attachments_and_form_state(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Profile::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)->test(Documents::class);
        $presenter = new \App\Livewire\Dashboard\Profile\Presentation\DocumentPresenter();
        $resetAction = app(\App\Livewire\Dashboard\Profile\Actions\ResetDocumentStateAction::class);
        $form = $component->instance()->form;

        $parsed = $presenter->parseAttachments([
            ['category' => 'standard', 'key' => 'shenasnameh', 'path' => 'docs/shenasnameh_1700000000.jpg', 'uploaded_at' => '2026-01-01 10:00:00'],
        ]);

        $approved = $presenter->docStatus($parsed, $resetAction, $form, 'shenasnameh');
        $this->assertSame('approved', $approved['status']);
        $this->assertNotNull($approved['uploadedDoc']);
        $this->assertSame('shenasnameh', $approved['uploadedDoc']['key']);

        $empty = $presenter->docStatus($parsed, $resetAction, $form, 'national_id');
        $this->assertSame('empty', $empty['status'], 'no parsed doc and no pending file in the form means empty');
        $this->assertNull($empty['uploadedDoc']);

        $component->set('form.files.national_id', UploadedFile::fake()->image('nid.jpg'));
        $pending = $presenter->docStatus($parsed, $resetAction, $component->instance()->form, 'national_id');
        $this->assertSame('pending', $pending['status'], 'a pending file in the form without a parsed doc means pending');
        $this->assertNull($pending['uploadedDoc']);
    }

    public function test_custom_docs_filters_parsed_attachments_to_the_custom_category_only(): void
    {
        $presenter = new \App\Livewire\Dashboard\Profile\Presentation\DocumentPresenter();

        $customDocs = $presenter->customDocs($presenter->parseAttachments([
            ['category' => 'standard', 'key' => 'shenasnameh', 'path' => 'docs/shenasnameh_1700000000.jpg'],
            ['category' => 'custom', 'key' => 'گواهی دوره', 'path' => 'docs/custom_1700000001.pdf'],
            ['category' => 'custom', 'key' => 'بیم‌نامه', 'path' => 'docs/custom_1700000002.pdf'],
        ]));

        $this->assertCount(2, $customDocs);
        $this->assertSame(['گواهی دوره', 'بیم‌نامه'], $customDocs->pluck('key')->all());
    }

    private function seedNotification(User $user, bool $read = false): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => DatabaseNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Probe Notice', 'body' => 'probe body']),
            'read_at' => $read ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_tickets_computed_scopes_to_the_requesting_users_own_tickets(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $mine = Ticket::factory()->create(['requester_id' => $user->id]);
        $others = Ticket::factory()->create(['requester_id' => $other->id]);

        $tickets = Livewire::actingAs($user)->test(Tickets::class)->instance()->tickets;

        $this->assertTrue($tickets->contains('id', $mine->id));
        $this->assertFalse($tickets->contains('id', $others->id));
    }

    public function test_tickets_load_more_increases_page_size_and_reveals_more_rows(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Ticket::factory()->count(11)->create(['requester_id' => $user->id]);

        $test = Livewire::actingAs($user)->test(Tickets::class);
        $this->assertSame(10, $test->instance()->tickets->count());

        $test->call('loadMore')->assertSet('perPage', 20);
        $this->assertSame(11, $test->instance()->tickets->count());
    }

    public function test_tickets_render_shows_assignee_name_and_reply_count_when_present(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $assignee = User::factory()->create(['status' => 'active', 'name' => 'Probe Assignee']);
        $ticket = Ticket::factory()->create(['requester_id' => $user->id, 'assigned_to' => $assignee->id]);
        $ticket->addReply($user, 'first reply');
        $ticket->addReply($assignee, 'second reply');

        Livewire::actingAs($user)
            ->test(Tickets::class)
            ->assertSee('Probe Assignee')
            ->assertSee(convertToPersian(2));
    }

    public function test_switch_tab_flips_active_section_to_support(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Tickets::class)
            ->call('switchTab', 'support')
            ->assertSet('activeSection', 'support')
            ->assertSee('نوع')
            ->assertDontSee('شناسه');
    }

    public function test_switch_tab_falls_back_to_ths_on_invalid_section(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Tickets::class)
            ->call('switchTab', 'bogus-value')
            ->assertSet('activeSection', 'ths');
    }

    public function test_support_requests_computed_scopes_to_the_requesting_users_own_release_requests(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $mine = ReleaseRequest::factory()->create(['user_id' => $user->id]);
        $others = ReleaseRequest::factory()->create(['user_id' => $other->id]);

        $requests = Livewire::actingAs($user)->test(Tickets::class)->instance()->supportRequests;

        $this->assertTrue($requests->contains('id', $mine->id));
        $this->assertFalse($requests->contains('id', $others->id));
    }

    public function test_support_load_more_increases_page_size_and_reveals_more_rows(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        ReleaseRequest::factory()->count(11)->create(['user_id' => $user->id]);

        $test = Livewire::actingAs($user)->test(Tickets::class)->call('switchTab', 'support');
        $this->assertSame(10, $test->instance()->supportRequests->count());

        $test->call('loadMoreSupport')->assertSet('supportPerPage', 20);
        $this->assertSame(11, $test->instance()->supportRequests->count());
    }

    public function test_support_section_renders_type_and_status_chip_labels(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        ReleaseRequest::factory()->create([
            'user_id' => $user->id,
            'type' => ReleaseRequestType::Bug->value,
            'status' => ReleaseRequestStatus::InReview->value,
        ]);

        Livewire::actingAs($user)
            ->test(Tickets::class)
            ->call('switchTab', 'support')
            ->assertSee('گزارش باگ')
            ->assertSee('در حال بررسی');
    }

    public function test_support_section_renders_empty_state_when_user_has_no_release_requests(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Tickets::class)
            ->call('switchTab', 'support')
            ->assertSee('درخواستی ثبت نشده');
    }

    public function test_notifications_computed_scopes_to_the_authenticated_users_own_notifications(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $mineId = $this->seedNotification($user);
        $othersId = $this->seedNotification($other);

        $notifications = Livewire::actingAs($user)->test(Notifications::class)->instance()->notifications;

        $this->assertTrue($notifications->contains('id', $mineId));
        $this->assertFalse($notifications->contains('id', $othersId));
    }

    public function test_mark_read_flips_read_at_on_the_target_notification(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $id = $this->seedNotification($user);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('markRead', $id)
            ->assertHasNoErrors();

        $this->assertNotNull(DB::table('notifications')->where('id', $id)->value('read_at'));
    }

    public function test_mark_read_on_another_users_notification_id_throws_model_not_found(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $intruder = User::factory()->create(['status' => 'active']);
        $id = $this->seedNotification($owner);

        $threw = false;
        try {
            Livewire::actingAs($intruder)->test(Notifications::class)->call('markRead', $id);
        } catch (ModelNotFoundException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'markRead must not allow a user to touch another users notification');
        $this->assertNull(DB::table('notifications')->where('id', $id)->value('read_at'));
    }

    public function test_mark_all_read_marks_only_the_authenticated_users_unread_notifications(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $mineUnread = $this->seedNotification($user);
        $othersUnread = $this->seedNotification($other);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('markAllRead')
            ->assertHasNoErrors();

        $this->assertNotNull(DB::table('notifications')->where('id', $mineUnread)->value('read_at'));
        $this->assertNull(DB::table('notifications')->where('id', $othersUnread)->value('read_at'));
    }

    public function test_mark_read_bumps_the_database_notification_model_cache_version(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $id = $this->seedNotification($user);
        $before = ModelCacheVersion::version(DatabaseNotification::class);

        Livewire::actingAs($user)->test(Notifications::class)->call('markRead', $id);

        $this->assertNotSame($before, ModelCacheVersion::version(DatabaseNotification::class));
    }

    public function test_mark_all_read_bumps_the_database_notification_model_cache_version(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->seedNotification($user);
        $before = ModelCacheVersion::version(DatabaseNotification::class);

        Livewire::actingAs($user)->test(Notifications::class)->call('markAllRead');

        $this->assertNotSame($before, ModelCacheVersion::version(DatabaseNotification::class));
    }

    public function test_password_save_updates_password_when_current_password_correct_and_new_password_valid(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Credentials::class)
            ->set('passwordForm.current_password', 'password')
            ->set('passwordForm.password', 'newSecurePass123')
            ->set('passwordForm.password_confirmation', 'newSecurePass123')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('newSecurePass123', $user->fresh()->password));
    }

    public function test_password_save_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Credentials::class)
            ->set('passwordForm.current_password', 'not-the-real-password')
            ->set('passwordForm.password', 'newSecurePass123')
            ->set('passwordForm.password_confirmation', 'newSecurePass123')
            ->call('savePassword')
            ->assertHasErrors(['passwordForm.current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_save_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Credentials::class)
            ->set('passwordForm.current_password', 'password')
            ->set('passwordForm.password', 'newSecurePass123')
            ->set('passwordForm.password_confirmation', 'different-confirmation')
            ->call('savePassword')
            ->assertHasErrors(['passwordForm.password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_save_rejects_password_shorter_than_the_minimum_length(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Credentials::class)
            ->set('passwordForm.current_password', 'password')
            ->set('passwordForm.password', 'short1')
            ->set('passwordForm.password_confirmation', 'short1')
            ->call('savePassword')
            ->assertHasErrors(['passwordForm.password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reminders_section_renders_the_embedded_reminder_widget_not_a_trigger_button(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('switchTab', 'reminders')
            ->assertSeeHtml('wire:key="reminder-embedded"')
            ->assertDontSeeHtml('wire:click="open"');
    }

    public function test_notifications_switch_tab_flips_active_section_to_reminders(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('switchTab', 'reminders')
            ->assertSet('activeSection', 'reminders');
    }

    public function test_notifications_switch_tab_falls_back_to_notifications_on_invalid_section(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Notifications::class)
            ->call('switchTab', 'bogus-value')
            ->assertSet('activeSection', 'notifications');
    }

    public function test_profile_tabs_includes_tickets_and_notifications_with_correct_components_and_no_separate_password_or_reminders_tabs(): void
    {
        $tabs = (new ProfilePresenter())->tabs();

        $this->assertSame('dashboard.profile.tickets', $tabs['tickets']['component']);
        $this->assertSame('dashboard.profile.notifications', $tabs['notifications']['component']);
        $this->assertArrayNotHasKey('password', $tabs);
        $this->assertArrayNotHasKey('reminders', $tabs);
    }

    public function test_profile_tabs_includes_settings_component(): void
    {
        $tabs = (new ProfilePresenter())->tabs();

        $this->assertSame('dashboard.profile.settings', $tabs['settings']['component']);
    }

    public function test_activate_focus_mode_sets_busy_presence_and_focus_until_from_minutes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $frozen = Carbon::now();
        Carbon::setTestNow($frozen);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('activateFocusMode', 30)
            ->assertHasNoErrors();

        $fresh = $user->refresh();
        $this->assertSame('busy', $fresh->presence->value);
        $this->assertSame(
            $frozen->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            $fresh->focus_until->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }

    public function test_activate_focus_mode_without_minutes_is_indefinite_and_still_counts_as_focusing(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('activateFocusMode', null)
            ->assertHasNoErrors();

        $fresh = $user->refresh();
        $this->assertSame('busy', $fresh->presence->value);
        $this->assertTrue($fresh->focus_until->gt(now()->addDays(30)));
        $this->assertTrue($fresh->isFocusing());
        $this->assertSame('حالت تمرکز فعال', $fresh->focusLabel());
    }

    public function test_focus_label_is_null_without_busy_or_future_end_and_shows_the_end_time_when_timed(): void
    {
        $this->assertNull((new User(['presence' => PresenceStatus::Busy, 'focus_until' => null]))->focusLabel());
        $this->assertNull((new User(['presence' => PresenceStatus::Angry, 'focus_until' => now()->addMinutes(30)]))->focusLabel());
        $this->assertStringStartsWith('متمرکز تا', (new User(['presence' => PresenceStatus::Busy, 'focus_until' => now()->addMinutes(30)]))->focusLabel());
    }

    public function test_activate_focus_mode_clamps_out_of_range_minutes_to_the_one_to_two_forty_bounds(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $frozen = Carbon::now();
        Carbon::setTestNow($frozen);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('activateFocusMode', 9999)
            ->assertHasNoErrors();

        $this->assertSame(
            $frozen->copy()->addMinutes(240)->format('Y-m-d H:i:s'),
            $user->refresh()->focus_until->format('Y-m-d H:i:s')
        );

        $user2 = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Livewire::actingAs($user2)
            ->test(Settings::class)
            ->call('activateFocusMode', -5)
            ->assertHasNoErrors();

        $this->assertSame(
            $frozen->copy()->addMinutes(1)->format('Y-m-d H:i:s'),
            $user2->refresh()->focus_until->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }

    public function test_deactivate_focus_mode_restores_given_presence_and_clears_focus_until(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(30)]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('deactivateFocusMode', 'remote')
            ->assertHasNoErrors();

        $fresh = $user->refresh();
        $this->assertSame('remote', $fresh->presence->value);
        $this->assertNull($fresh->focus_until);
    }

    public function test_deactivate_focus_mode_falls_back_to_onsite_for_invalid_or_null_restore_presence(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(30)]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('deactivateFocusMode', 'not-a-real-status')
            ->assertHasNoErrors();

        $this->assertSame(PresenceStatus::Onsite->value, $user->refresh()->presence->value);

        $user2 = User::factory()->create(['status' => 'active', 'presence' => 'busy', 'focus_until' => now()->addMinutes(30)]);
        Livewire::actingAs($user2)
            ->test(Settings::class)
            ->call('deactivateFocusMode', null)
            ->assertHasNoErrors();

        $this->assertSame(PresenceStatus::Onsite->value, $user2->refresh()->presence->value);
    }

    public function test_focus_mode_actions_only_ever_affect_the_authenticated_user(): void
    {
        $actor = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $bystander = User::factory()->create(['status' => 'active', 'presence' => 'remote']);

        Livewire::actingAs($actor)
            ->test(Settings::class)
            ->call('activateFocusMode', 30)
            ->assertHasNoErrors();

        $freshBystander = $bystander->refresh();
        $this->assertSame('remote', $freshBystander->presence->value);
        $this->assertNull($freshBystander->focus_until);
        $this->assertSame('busy', $actor->refresh()->presence->value);
    }

    public function test_settings_switch_tab_only_accepts_whitelisted_sections_and_ignores_others(): void
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        $component = Livewire::actingAs($user)
            ->test(Settings::class)
            ->assertSet('activeSection', 'appearance')
            ->call('switchTab', 'focus')
            ->assertSet('activeSection', 'focus');

        $component->call('switchTab', 'bogus-section')
            ->assertSet('activeSection', 'focus');
    }
}