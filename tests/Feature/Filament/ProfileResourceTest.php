<?php

namespace Tests\Feature\Filament;

use App\Enums\SkillRequestStatus;
use App\Enums\SkillTier;
use App\Filament\Resources\ProfileResource\Pages\CreateProfile;
use App\Filament\Resources\ProfileResource\Pages\EditProfile;
use App\Filament\Resources\ProfileResource\Pages\ListProfiles;
use App\Filament\Resources\ProfileResource\Schemas\ProfileInfolistPresenter;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\ProfileDetail;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileResourceTest extends TestCase
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

    private function validCreateData(): array
    {
        $owner = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        $department = Department::first()?->code ?? 'HR';

        return [
            'user_id' => $owner->id,
            'department_id' => $department,
            'gender' => 'male',
            'employment_type' => 'fulltime',
            'marital_status' => 'single',
            'employment_status' => 'working',
            'degree' => 'graduate',
            'position' => 'employee',
            'birthdate_year' => '1370',
            'birthdate_month' => '1',
            'birthdate_day' => '1',
        ];
    }

    public function test_list_renders_and_shows_factory_profile_row()
    {
        $admin = $this->developer();

        $profile = Profile::factory()->create([
            'position' => 'employee',
            'image' => null,
        ]);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$profile]);
    }

    public function test_list_table_position_column_shows_display_title_over_raw_position()
    {
        $admin = $this->developer();

        $profile = Profile::factory()->create(['position' => 'employee', 'image' => null]);
        ProfileDetail::create(['profile_id' => $profile->id, 'section' => 'employment', 'key' => 'display_title', 'value' => 'Custom Title']);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->assertSuccessful()
            ->assertSee('Custom Title');
    }

    public function test_create_profile_persists_required_fields()
    {
        $admin = $this->developer();
        $data = $this->validCreateData();

        Livewire::actingAs($admin)->test(CreateProfile::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('profiles', [
            'user_id' => $data['user_id'],
            'gender' => 'male',
            'employment_type' => 'fulltime',
            'marital_status' => 'single',
            'employment_status' => 'working',
            'degree' => 'graduate',
            'position' => 'employee',
        ]);
    }

    public function test_create_profile_persists_catalog_favorite_colors()
    {
        $admin = $this->developer();
        $data = $this->validCreateData();
        $data['favorite_colors'] = [
            ['color' => \App\Enums\FavoriteColor::DarkRed->value],
            ['color' => \App\Enums\FavoriteColor::TailwindBlue->value],
        ];

        Livewire::actingAs($admin)->test(CreateProfile::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $profile = Profile::where('user_id', $data['user_id'])->first();
        $this->assertSame(
            [\App\Enums\FavoriteColor::DarkRed->value, \App\Enums\FavoriteColor::TailwindBlue->value],
            $profile->favorite_colors
        );
    }

    public function test_create_profile_rejects_off_catalog_favorite_color()
    {
        $admin = $this->developer();
        $data = $this->validCreateData();
        $data['favorite_colors'] = [
            ['color' => '#123456'],
        ];

        Livewire::actingAs($admin)->test(CreateProfile::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['favorite_colors.0.color']);
    }

    public function test_create_profile_requires_gender()
    {
        $admin = $this->developer();
        $data = $this->validCreateData();
        unset($data['gender']);

        Livewire::actingAs($admin)->test(CreateProfile::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['gender' => 'required']);
    }

    public function test_edit_profile_saves_changed_field()
    {
        $admin = $this->developer();

        $profile = Profile::factory()->create([
            'position' => 'employee',
            'image' => null,
            'field' => 'Old Field',
        ]);

        Livewire::actingAs($admin)->test(EditProfile::class, ['record' => $profile->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['field' => 'Computer Engineering'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Computer Engineering', $profile->fresh()->field);
    }

    public function test_delete_profile_removes_row()
    {
        $admin = $this->developer();

        $profile = Profile::factory()->create([
            'position' => 'employee',
            'image' => null,
        ]);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $profile);

        $this->assertDatabaseMissing('profiles', ['id' => $profile->id]);
    }

    public function test_employment_status_filter_scopes_list_to_matching_profiles()
    {
        $admin = $this->developer();

        $working = Profile::factory()->create([
            'position' => 'employee',
            'image' => null,
            'employment_status' => 'working',
        ]);
        $terminated = Profile::factory()->create([
            'position' => 'employee',
            'image' => null,
            'employment_status' => 'terminated',
        ]);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->filterTable('employment_status', 'working')
            ->assertCanSeeTableRecords([$working])
            ->assertCanNotSeeTableRecords([$terminated]);
    }

    public function test_employment_type_filter_scopes_list_to_matching_profiles()
    {
        $admin = $this->developer();

        $fulltime = Profile::factory()->create(['position' => 'employee', 'image' => null, 'employment_type' => 'fulltime']);
        $parttime = Profile::factory()->create(['position' => 'employee', 'image' => null, 'employment_type' => 'parttime']);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->filterTable('employment_type', 'fulltime')
            ->assertCanSeeTableRecords([$fulltime])
            ->assertCanNotSeeTableRecords([$parttime]);
    }

    public function test_gender_filter_scopes_list_to_matching_profiles()
    {
        $admin = $this->developer();

        $female = Profile::factory()->create(['position' => 'employee', 'image' => null, 'gender' => 'female']);
        $male = Profile::factory()->create(['position' => 'employee', 'image' => null, 'gender' => 'male']);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->filterTable('gender', 'female')
            ->assertCanSeeTableRecords([$female])
            ->assertCanNotSeeTableRecords([$male]);
    }

    public function test_degree_filter_scopes_list_to_matching_profiles()
    {
        $admin = $this->developer();

        $graduate = Profile::factory()->create(['position' => 'employee', 'image' => null, 'degree' => 'graduate']);
        $doctorate = Profile::factory()->create(['position' => 'employee', 'image' => null, 'degree' => 'doctorate']);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->filterTable('degree', 'graduate')
            ->assertCanSeeTableRecords([$graduate])
            ->assertCanNotSeeTableRecords([$doctorate]);
    }

    public function test_department_filter_scopes_list_to_matching_profiles()
    {
        $admin = $this->developer();
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $inA = Profile::factory()->create(['position' => 'employee', 'image' => null, 'department_id' => $departmentA->code]);
        $inB = Profile::factory()->create(['position' => 'employee', 'image' => null, 'department_id' => $departmentB->code]);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->filterTable('department_id', $departmentA->code)
            ->assertCanSeeTableRecords([$inA])
            ->assertCanNotSeeTableRecords([$inB]);
    }

    public function test_has_details_filter_scopes_to_profiles_with_details()
    {
        $admin = $this->developer();

        $withDetails = Profile::factory()->create([
            'position' => 'employee',
            'image' => null,
        ]);
        $withDetails->details()->create([
            'key' => 'custom_test',
            'section' => 'other',
            'value' => 'sample',
        ]);

        $withoutDetails = Profile::factory()->create([
            'position' => 'employee',
            'image' => null,
        ]);

        Livewire::actingAs($admin)->test(ListProfiles::class)
            ->filterTable('has_details', true)
            ->assertCanSeeTableRecords([$withDetails])
            ->assertCanNotSeeTableRecords([$withoutDetails]);
    }

    public function test_profile_department_hint_documents_ceo_decision_authority()
    {
        app()->setLocale('fa');
        $admin = $this->developer();

        $html = Livewire::actingAs($admin)->test(CreateProfile::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString(
            __('resources/profile/strings.hints.department_id'),
            $html,
            'the department_id field must explain that department MA grants sole suggestion-decision authority',
        );
    }

    public function test_profile_position_hint_documents_department_head_ranking()
    {
        app()->setLocale('fa');
        $admin = $this->developer();

        $html = Livewire::actingAs($admin)->test(CreateProfile::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString(
            __('resources/profile/strings.hints.position'),
            $html,
            'the position field must explain that rank determines department-head status for suggestion approvals/ticket routing',
        );
    }

    public function test_profile_employment_status_hint_documents_terminated_effects()
    {
        app()->setLocale('fa');
        $admin = $this->developer();

        $html = Livewire::actingAs($admin)->test(CreateProfile::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString(
            __('resources/profile/strings.hints.employment_status'),
            $html,
            'the employment_status field must explain that "terminated" suppresses birthday/anniversary nudges and active-employee counts',
        );
    }

    public function test_profile_infolist_attachments_returns_two_repeatable_entries_for_spreading()
    {
        $entries = ProfileInfolistPresenter::attachments();

        $this->assertIsArray($entries);
        $this->assertCount(2, $entries);
        $this->assertInstanceOf(RepeatableEntry::class, $entries[0]);
        $this->assertInstanceOf(RepeatableEntry::class, $entries[1]);
    }

    public function test_profile_infolist_schemas_compose_without_type_errors()
    {
        $resourceSchema = \App\Filament\Resources\ProfileResource::infolist(\Filament\Schemas\Schema::make());
        $this->assertInstanceOf(\Filament\Schemas\Schema::class, $resourceSchema);

        $relationSchema = (new \App\Filament\Resources\UserResource\RelationManagers\ProfileRelationManager())
            ->infolist(\Filament\Schemas\Schema::make());
        $this->assertInstanceOf(\Filament\Schemas\Schema::class, $relationSchema);
    }

    public function test_profile_infolist_attachments_state_splits_standard_from_custom()
    {
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create([
            'user_id' => $target->id,
            'attachments' => [
                ['key' => 'shenasnameh', 'path' => 'profiles/docs/a/doc_standard_shenasnameh_1.jpg', 'category' => 'standard'],
                ['key' => 'گواهی سلامت', 'path' => 'profiles/docs/a/doc_custom_گواهی سلامت_2.jpg', 'category' => 'custom'],
                ['key' => 'legacy', 'path' => 'profiles/docs/a/legacy_row.jpg'],
            ],
        ]);

        $standard = ProfileInfolistPresenter::standardAttachmentsState($profile->fresh());
        $custom = ProfileInfolistPresenter::customAttachmentsState($profile->fresh());

        $this->assertSame(['shenasnameh'], array_column($standard, 'key'));
        $this->assertSame(['گواهی سلامت', 'legacy'], array_column($custom, 'key'));
        $this->assertSame([], ProfileInfolistPresenter::standardAttachmentsState(new Profile()));
        $this->assertSame([], ProfileInfolistPresenter::customAttachmentsState(new Profile()));
    }

    public function test_profile_infolist_skills_entry_state_reflects_users_approved_skill()
    {
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id]);
        $skill = Skill::create(['name' => '0Infolist Skill', 'is_active' => true, 'is_ghost' => false]);
        SkillUser::create([
            'user_id' => $target->id,
            'skill_id' => $skill->id,
            'status' => SkillRequestStatus::Approved,
            'approved_at' => now(),
        ]);

        $state = ProfileInfolistPresenter::skillsState($profile->fresh());

        $this->assertCount(1, $state);
        $this->assertSame('0Infolist Skill', $state[0]['skill_name']);
        $this->assertSame(SkillRequestStatus::Approved, $state[0]['status']);
        $this->assertSame(SkillTier::Unused, $state[0]['tier']);
        $this->assertSame('بدون تأیید', $state[0]['endorsements_count']);
    }

    public function test_profile_infolist_skills_entry_state_omits_tier_for_non_approved_rows()
    {
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id]);
        $skill = Skill::create(['name' => 'Infolist Pending Skill ' . uniqid()]);
        SkillUser::create(['user_id' => $target->id, 'skill_id' => $skill->id, 'status' => 'pending']);

        $state = ProfileInfolistPresenter::skillsState($profile->fresh());

        $this->assertNull($state[0]['tier']);
    }

    public function test_profile_infolist_skills_entry_state_is_empty_without_skills()
    {
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id]);

        $state = ProfileInfolistPresenter::skillsState($profile->fresh());

        $this->assertSame([], $state);
    }

    public function test_admin_can_add_skill_to_profile_via_repeater()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id, 'position' => 'employee', 'image' => null, 'cellphone' => '09120000000', 'landline' => '02112340000']);
        $skill = Skill::create(['name' => '0New Skill', 'is_active' => true, 'is_ghost' => false]);

        Livewire::actingAs($admin)->test(EditProfile::class, ['record' => $profile->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'skills' => [
                    ['skill_id' => $skill->id, 'status' => 'approved', 'is_mentoring' => true, 'is_private' => false],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('skill_user', [
            'user_id' => $target->id,
            'skill_id' => $skill->id,
            'status' => 'approved',
            'is_mentoring' => 1,
            'is_private' => 0,
        ]);
    }

    public function test_admin_can_edit_existing_skill_row_and_persists_toggles()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id, 'position' => 'employee', 'image' => null, 'cellphone' => '09120000000', 'landline' => '02112340000']);
        $skill = Skill::create(['name' => '0Edit Skill', 'is_active' => true, 'is_ghost' => false]);
        $skillUser = SkillUser::create([
            'user_id' => $target->id,
            'skill_id' => $skill->id,
            'status' => SkillRequestStatus::Approved,
            'is_mentoring' => false,
            'is_private' => false,
            'approved_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(EditProfile::class, ['record' => $profile->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'skills' => [
                    ['id' => $skillUser->id, 'skill_id' => $skill->id, 'status' => 'approved', 'is_mentoring' => true, 'is_private' => true],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('skill_user', [
            'id' => $skillUser->id,
            'is_mentoring' => 1,
            'is_private' => 1,
        ]);
    }

    public function test_admin_skill_last_used_at_persists_via_year_month_day_selects()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id, 'position' => 'employee', 'image' => null, 'cellphone' => '09120000000', 'landline' => '02112340000']);
        $skill = Skill::create(['name' => '0LastUsed Skill', 'is_active' => true, 'is_ghost' => false]);

        Livewire::actingAs($admin)->test(EditProfile::class, ['record' => $profile->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'skills' => [
                    ['skill_id' => $skill->id, 'status' => 'approved', 'is_mentoring' => false, 'is_private' => false],
                ],
            ])
            ->set('data.skills.0.last_used_at_year', '1402')
            ->set('data.skills.0.last_used_at_month', '3')
            ->set('data.skills.0.last_used_at_day', '15')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('skill_user', [
            'user_id' => $target->id,
            'skill_id' => $skill->id,
            'last_used_at' => Jalalian::fromFormat('Y/m/d', '1402/03/15')->toCarbon()->format('Y-m-d'),
        ]);
    }

    public function test_admin_cannot_submit_duplicate_skill_rows_via_repeater()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id, 'position' => 'employee', 'image' => null, 'cellphone' => '09120000000', 'landline' => '02112340000']);
        $skill = Skill::create(['name' => '0Duplicate Skill', 'is_active' => true, 'is_ghost' => false]);

        Livewire::actingAs($admin)->test(EditProfile::class, ['record' => $profile->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'skills' => [
                    ['skill_id' => $skill->id, 'status' => 'approved', 'is_mentoring' => false, 'is_private' => false],
                    ['skill_id' => $skill->id, 'status' => 'approved', 'is_mentoring' => false, 'is_private' => false],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['skills.0.skill_id', 'skills.1.skill_id']);

        $this->assertSame(0, SkillUser::where('user_id', $target->id)->where('skill_id', $skill->id)->count());
    }

    public function test_admin_can_delete_skill_row_via_repeater()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active']);
        $profile = Profile::factory()->create(['user_id' => $target->id, 'position' => 'employee', 'image' => null, 'cellphone' => '09120000000', 'landline' => '02112340000']);
        $skill = Skill::create(['name' => '0Delete Skill', 'is_active' => true, 'is_ghost' => false]);
        $skillUser = SkillUser::create([
            'user_id' => $target->id,
            'skill_id' => $skill->id,
            'status' => SkillRequestStatus::Approved,
            'approved_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(EditProfile::class, ['record' => $profile->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['skills' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('skill_user', ['id' => $skillUser->id]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Profile::factory()->create(['position' => 'employee', 'image' => null]);

        $component = Livewire::actingAs($admin)->test(ListProfiles::class)->assertSuccessful();

        foreach (['department.name', 'position', 'employment_status', 'employment_type', 'gender'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sorting_by_id_and_created_at_produces_correct_order()
    {
        $admin = $this->developer();
        $first = Profile::factory()->create(['position' => 'employee', 'image' => null]);
        $second = Profile::factory()->create(['position' => 'employee', 'image' => null]);

        $component = Livewire::actingAs($admin)->test(ListProfiles::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $old = $first;
        $old->forceFill(['created_at' => now()->subYears(5)])->saveQuietly();
        $new = $second;
        $new->forceFill(['created_at' => now()])->saveQuietly();

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$old->id, $new->id], array_values(array_intersect($ids, [$old->id, $new->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$new->id, $old->id], array_values(array_intersect($ids, [$old->id, $new->id])));
    }

    public function test_sorting_by_details_count_produces_correct_order()
    {
        $admin = $this->developer();
        $withoutDetails = Profile::factory()->create(['position' => 'employee', 'image' => null]);
        $withDetails = Profile::factory()->create(['position' => 'employee', 'image' => null]);
        $withDetails->details()->create(['key' => 'sort_test', 'section' => 'other', 'value' => 'x']);

        $component = Livewire::actingAs($admin)->test(ListProfiles::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('details_count')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$withoutDetails->id, $withDetails->id], array_values(array_intersect($ids, [$withoutDetails->id, $withDetails->id])));

        $component->sortTable('details_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$withDetails->id, $withoutDetails->id], array_values(array_intersect($ids, [$withoutDetails->id, $withDetails->id])));
    }

    public function test_sorting_by_department_description_produces_correct_order()
    {
        $admin = $this->developer();
        $deptA = Department::factory()->create(['code' => strtoupper(\Illuminate\Support\Str::random(8)), 'description' => 'AAA-sort-dept-desc']);
        $deptZ = Department::factory()->create(['code' => strtoupper(\Illuminate\Support\Str::random(8)), 'description' => 'ZZZ-sort-dept-desc']);
        $first = Profile::factory()->create(['position' => 'employee', 'image' => null, 'department_id' => $deptA->code]);
        $second = Profile::factory()->create(['position' => 'employee', 'image' => null, 'department_id' => $deptZ->code]);

        $component = Livewire::actingAs($admin)->test(ListProfiles::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('department.description')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('department.description', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_employment_status_employment_type_and_position_produces_correct_order()
    {
        $admin = $this->developer();

        $first = Profile::factory()->create(['position' => 'aaa-sort-position', 'image' => null, 'employment_status' => 'probational', 'employment_type' => 'contract']);
        $second = Profile::factory()->create(['position' => 'zzz-sort-position', 'image' => null, 'employment_status' => 'working', 'employment_type' => 'parttime']);

        $component = Livewire::actingAs($admin)->test(ListProfiles::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('position')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('employment_status')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('employment_type')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_start_date_and_user_name_produces_correct_order()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => 'AAA-sort-user']);
        $userZ = User::factory()->create(['name' => 'ZZZ-sort-user']);
        $first = Profile::factory()->create(['position' => 'employee', 'image' => null, 'user_id' => $userA->id, 'start_date' => '2000-01-01']);
        $second = Profile::factory()->create(['position' => 'employee', 'image' => null, 'user_id' => $userZ->id, 'start_date' => '2020-01-01']);

        $component = Livewire::actingAs($admin)->test(ListProfiles::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('start_date')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ProfileResource\Pages\CreateProfile::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ProfileResource\Pages\EditProfile::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ProfileResource\Pages\ListProfiles::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ProfileResource\Pages\CreateProfile::class, \App\Filament\Resources\ProfileResource\Pages\EditProfile::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\ProfileResource\RelationManagers\UserRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_dropped_image_and_documents(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\ProfileResource\Pages\EditProfile());
        Storage::fake('public');
        foreach (['profiles/images/old.jpg', 'profiles/images/new.jpg', 'profiles/docs/t/old.pdf', 'profiles/docs/t/kept.pdf'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = \App\Models\Profile::factory()->create([
            'image' => 'profiles/images/old.jpg',
            'attachments' => [['path' => 'profiles/docs/t/old.pdf'], ['path' => 'profiles/docs/t/kept.pdf']],
        ]);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['profiles/images/old.jpg', 'profiles/docs/t/old.pdf', 'profiles/docs/t/kept.pdf'], $before);

        $record->update(['image' => 'profiles/images/new.jpg', 'attachments' => [['path' => 'profiles/docs/t/kept.pdf']]]);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('profiles/images/old.jpg');
        Storage::disk('public')->assertMissing('profiles/docs/t/old.pdf');
        Storage::disk('public')->assertExists('profiles/docs/t/kept.pdf');
        Storage::disk('public')->assertExists('profiles/images/new.jpg');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ProfileResource/Schemas/ProfileTablePresenter.php'),
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
