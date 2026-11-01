<?php

namespace Tests\Feature\Models;

use App\Enums\PresenceStatus;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        User::resetTodaysDeskCache();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        User::resetTodaysDeskCache();
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

    private function dept(string $code): Department
    {
        return Department::firstOrCreate(['code' => $code], ['name' => $code, 'description' => $code]);
    }

    private function userWithProfile(string $deptCode, string $position, array $overrides = []): User
    {
        $dept = $this->dept($deptCode);
        $user = User::factory()->create(array_merge([
            'status' => 'active',
            'type' => 'employee',
            'role' => 'user',
        ], $overrides));

        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $dept->code,
            'position' => $position,
        ]);

        return $user->fresh('profile.department');
    }

    public function test_ranks_constant_maps_position_strings_to_numeric_rank(): void
    {
        $this->assertSame(1, User::RANKS['chairman']);
        $this->assertSame(1, User::RANKS['ceo']);
        $this->assertSame(7, User::RANKS['employee']);
    }

    public function test_scope_active_filters_to_active_users_only(): void
    {
        $active = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive']);

        $ids = User::active()->pluck('id')->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_scope_visible_on_board_excludes_guests_and_inactive(): void
    {
        $employee = User::factory()->create(['status' => 'active', 'type' => 'employee']);
        $guest = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        $inactiveEmployee = User::factory()->create(['status' => 'inactive', 'type' => 'employee']);

        $ids = User::visibleOnBoard()->pluck('id')->all();

        $this->assertContains($employee->id, $ids);
        $this->assertNotContains($guest->id, $ids);
        $this->assertNotContains($inactiveEmployee->id, $ids);
    }

    public function test_scope_of_type_filters_by_type_column(): void
    {
        $employee = User::factory()->create(['type' => 'employee']);
        $manager = User::factory()->create(['type' => 'manager']);

        $this->assertContains($employee->id, User::ofType('employee')->pluck('id')->all());
        $this->assertNotContains($manager->id, User::ofType('employee')->pluck('id')->all());
    }

    public function test_scope_with_role_filters_by_role_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->assertContains($admin->id, User::withRole('admin')->pluck('id')->all());
        $this->assertNotContains($user->id, User::withRole('admin')->pluck('id')->all());
    }

    public function test_scope_online_filters_by_last_seen_within_window(): void
    {
        $online = User::factory()->create(['last_seen' => now()->subMinutes(2)]);
        $offline = User::factory()->create(['last_seen' => now()->subHours(2)]);

        $this->assertContains($online->id, User::online()->pluck('id')->all());
        $this->assertNotContains($offline->id, User::online()->pluck('id')->all());
    }

    public function test_is_active_returns_true_only_for_active_status(): void
    {
        $this->assertTrue((new User(['status' => 'active']))->isActive());
        $this->assertFalse((new User(['status' => 'inactive']))->isActive());
    }

    public function test_is_admin_is_developer_and_has_elevated_role_branch_correctly(): void
    {
        $this->assertTrue((new User(['role' => 'admin']))->isAdmin());
        $this->assertTrue((new User(['role' => 'developer']))->isDeveloper());
        $this->assertTrue((new User(['role' => 'admin']))->hasElevatedRole());
        $this->assertTrue((new User(['role' => 'developer']))->hasElevatedRole());
        $this->assertFalse((new User(['role' => 'user']))->hasElevatedRole());
    }

    public function test_is_online_returns_true_when_last_seen_within_default_window(): void
    {
        $this->assertTrue((new User(['last_seen' => now()->subMinutes(3)]))->isOnline());
        $this->assertFalse((new User(['last_seen' => now()->subHours(2)]))->isOnline());
        $this->assertFalse((new User(['last_seen' => null]))->isOnline());
    }

    public function test_one_online_window_drives_is_online_scope_and_mark_offline(): void
    {
        $inside = User::factory()->create(['last_seen' => now()->subMinutes(User::ONLINE_MINUTES - 1)]);
        $outside = User::factory()->create(['last_seen' => now()->subMinutes(User::ONLINE_MINUTES + 1)]);

        $this->assertTrue($inside->isOnline());
        $this->assertFalse($outside->isOnline());
        $this->assertContains($inside->id, User::online()->pluck('id')->all());
        $this->assertNotContains($outside->id, User::online()->pluck('id')->all());

        $inside->markOffline();
        $this->assertFalse($inside->fresh()->isOnline());
        $this->assertNotContains($inside->id, User::online()->pluck('id')->all());
    }

    public function test_last_seen_label_is_online_relative_or_never(): void
    {
        $this->assertSame('آنلاین', (new User(['last_seen' => now()->subMinute()]))->lastSeenLabel());
        $this->assertSame('هرگز', (new User(['last_seen' => null]))->lastSeenLabel());
        $this->assertSame(toJalaliRelative(now()->subHours(3)), (new User(['last_seen' => now()->subHours(3)]))->lastSeenLabel());
    }

    public function test_new_user_defaults_to_the_enum_default_presence(): void
    {
        $this->assertSame(PresenceStatus::default(), (new User())->presence);
    }

    public function test_is_focusing_requires_both_busy_presence_and_future_focus_until(): void
    {
        $this->assertTrue((new User(['presence' => PresenceStatus::Busy, 'focus_until' => now()->addMinutes(30)]))->isFocusing());
        $this->assertFalse((new User(['presence' => PresenceStatus::Busy, 'focus_until' => now()->subMinutes(5)]))->isFocusing());
        $this->assertFalse((new User(['presence' => PresenceStatus::Angry, 'focus_until' => now()->addMinutes(30)]))->isFocusing());
        $this->assertFalse((new User(['presence' => PresenceStatus::Busy, 'focus_until' => null]))->isFocusing());
    }

    public function test_extra_get_returns_empty_array_for_null_column(): void
    {
        $user = new User();

        $this->assertSame([], $user->extra);
    }

    public function test_extra_set_with_preferences_merges_into_preferences_bucket(): void
    {
        $user = new User();
        $user->extra = ['preferences' => ['theme' => 'dark']];

        $this->assertSame(['preferences' => ['theme' => 'dark']], $user->extra);
    }

    public function test_extra_set_without_preferences_routes_non_reserved_keys_into_admin_bucket(): void
    {
        $user = new User();
        $user->extra = ['foo' => 'bar'];

        $this->assertSame(['admin' => ['foo' => 'bar']], $user->extra);
    }

    public function test_extra_set_with_preferences_merges_with_existing_preferences(): void
    {
        $user = new User();
        $user->extra = ['preferences' => ['theme' => 'light']];
        $user->extra = ['preferences' => ['theme' => 'dark', 'lang' => 'fa']];

        $this->assertSame(['preferences' => ['theme' => 'dark', 'lang' => 'fa']], $user->extra);
    }

    public function test_extra_set_with_preferences_and_extra_keys_buckets_them_into_admin(): void
    {
        $user = new User();
        $user->extra = ['preferences' => ['theme' => 'dark'], 'foo' => 'bar'];

        $this->assertSame(['preferences' => ['theme' => 'dark'], 'admin' => ['foo' => 'bar']], $user->extra);
    }

    public function test_extra_set_with_null_value_routes_into_empty_admin_bucket(): void
    {
        $user = new User();
        $user->extra = null;

        $this->assertSame(['admin' => []], $user->extra);
    }

    public function test_booking_get_normalizes_key_value_pair_array_to_flat_map(): void
    {
        $user = new User();
        $user->booking = '[{"key":"all","value":false},{"key":"seat","value":true}]';

        $this->assertSame(['all' => false, 'seat' => true], $user->booking);
    }

    public function test_booking_max_reads_the_per_type_override_and_ignores_missing_or_blank_values(): void
    {
        $user = new User();
        $user->booking = '[{"key":"seat","value":true,"max":"3"},{"key":"spot","value":true,"max":null},{"key":"car","value":true},{"key":"meeting","value":true,"max":0}]';

        $this->assertSame(3, $user->bookingMax('seat'));
        $this->assertNull($user->bookingMax('spot'));
        $this->assertNull($user->bookingMax('car'));
        $this->assertNull($user->bookingMax('absent'));
        $this->assertSame(1, $user->bookingMax('meeting'));
        $this->assertSame(['seat' => true, 'spot' => true, 'car' => true, 'meeting' => true], $user->booking);

        $this->assertNull((new User())->bookingMax('seat'));
    }

    public function test_booking_max_falls_back_to_the_all_row_when_type_has_no_own_value(): void
    {
        $user = new User();
        $user->booking = '[{"key":"all","value":false,"max":10},{"key":"seat","value":true,"max":12},{"key":"spot","value":true}]';

        $this->assertSame(12, $user->bookingMax('seat'));
        $this->assertSame(10, $user->bookingMax('spot'));
        $this->assertSame(10, $user->bookingMax('absent'));
    }

    public function test_booking_get_returns_empty_array_for_null_or_invalid_json(): void
    {
        $user = new User();

        $this->assertSame([], $user->booking);

        $user->booking = 'invalid-json';
        $this->assertSame([], $user->booking);
    }

    public function test_booking_get_preserves_flat_key_value_array(): void
    {
        $user = new User();
        $user->booking = ['all' => true, 'car' => false];

        $this->assertSame(['all' => true, 'car' => false], $user->booking);
    }

    public function test_get_extra_value_reads_from_extra_array_with_dot_notation(): void
    {
        $user = new User();
        $user->extra = ['preferences' => ['theme' => 'dark']];

        $this->assertSame('dark', $user->getExtraValue('preferences.theme'));
        $this->assertSame('default', $user->getExtraValue('preferences.nonexistent', 'default'));
    }

    public function test_get_preference_reads_from_preferences_sub_key(): void
    {
        $user = new User();
        $user->extra = ['preferences' => ['theme' => 'dark']];

        $this->assertSame('dark', $user->getPreference('theme'));
        $this->assertNull($user->getPreference('nonexistent'));
    }

    public function test_set_extra_value_writes_into_extra_array_with_dot_notation(): void
    {
        $user = new User();
        $user->setExtraValue('preferences.theme', 'dark');

        $this->assertSame('dark', $user->getExtraValue('preferences.theme'));
    }

    public function test_is_in_department_returns_true_when_profile_department_code_matches(): void
    {
        $user = $this->userWithProfile('MA', 'ceo');

        $this->assertTrue($user->isInDepartment('MA'));
        $this->assertFalse($user->isInDepartment('HR'));
    }

    public function test_is_in_department_returns_false_when_profile_is_null(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertFalse($user->isInDepartment('MA'));
    }

    public function test_rank_returns_numeric_rank_for_known_position(): void
    {
        $user = $this->userWithProfile('HR', 'manager');

        $this->assertSame(3, $user->rank());
    }

    public function test_rank_returns_php_int_max_for_null_or_unknown_position(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertSame(PHP_INT_MAX, $user->rank());
    }

    public function test_is_ceo_returns_true_for_ma_department_only(): void
    {
        $maUser = $this->userWithProfile('MA', 'employee');
        $hrUser = $this->userWithProfile('HR', 'ceo');

        $this->assertTrue($maUser->isCeo());
        $this->assertFalse($hrUser->isCeo());
    }

    public function test_is_top_executive_returns_true_for_chairman_or_ceo_position(): void
    {
        $chairman = $this->userWithProfile('MA', 'chairman');
        $ceo = $this->userWithProfile('MA', 'ceo');
        $manager = $this->userWithProfile('HR', 'manager');

        $this->assertTrue($chairman->isTopExecutive());
        $this->assertTrue($ceo->isTopExecutive());
        $this->assertFalse($manager->isTopExecutive());
    }

    public function test_is_senior_decision_maker_returns_true_for_top_executive(): void
    {
        $ceo = $this->userWithProfile('MA', 'ceo');

        $this->assertTrue($ceo->isSeniorDecisionMaker());
    }

    public function test_is_senior_decision_maker_returns_false_for_ma_user_when_top_executive_exists(): void
    {
        $this->userWithProfile('MA', 'ceo');
        $maEmployee = $this->userWithProfile('MA', 'employee');

        $this->assertFalse($maEmployee->isSeniorDecisionMaker());
    }

    public function test_is_senior_decision_maker_returns_true_for_ma_user_when_no_top_executive_exists(): void
    {
        $preExistingTopExec = User::active()
            ->whereHas('profile', fn($q) => $q->whereIn('position', ['chairman', 'ceo']))
            ->exists();

        if ($preExistingTopExec) {
            $this->markTestSkipped('dev DB has a pre-existing chairman/ceo; the no-top-executive fallback branch cannot be isolated');
        }

        $maEmployee = $this->userWithProfile('MA', 'manager');

        $this->assertTrue($maEmployee->isSeniorDecisionMaker());
    }

    public function test_is_senior_decision_maker_returns_false_for_non_ma_user(): void
    {
        $hrManager = $this->userWithProfile('HR', 'manager');

        $this->assertFalse($hrManager->isSeniorDecisionMaker());
    }

    public function test_is_senior_decision_maker_returns_true_for_mg_user_when_no_top_executive_and_no_ma_user_exists(): void
    {
        $preExistingTopExecOrMa = User::active()
            ->whereHas('profile', fn($q) => $q->whereIn('position', ['chairman', 'ceo'])->orWhere('department_id', 'MA'))
            ->exists();

        if ($preExistingTopExecOrMa) {
            $this->markTestSkipped('dev DB has a pre-existing chairman/ceo/MA user; the MG-only fallback branch cannot be isolated');
        }

        $mgEmployee = $this->userWithProfile('MG', 'manager');

        $this->assertTrue($mgEmployee->isSeniorDecisionMaker());
    }

    public function test_is_senior_decision_maker_returns_false_for_mg_user_when_ma_user_exists(): void
    {
        $this->userWithProfile('MA', 'employee');
        $mgEmployee = $this->userWithProfile('MG', 'manager');

        $this->assertFalse($mgEmployee->isSeniorDecisionMaker());
    }

    public function test_is_dept_head_returns_true_when_no_superior_exists_in_department(): void
    {
        $manager = $this->userWithProfile('ZZ1', 'manager');

        $this->assertTrue($manager->isDeptHead());
    }

    public function test_is_dept_head_returns_false_when_a_superior_exists_in_same_department(): void
    {
        $this->userWithProfile('HR', 'ceo');
        $manager = $this->userWithProfile('HR', 'manager');

        $this->assertFalse($manager->isDeptHead());
    }

    public function test_is_dept_head_returns_false_when_user_has_no_department(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertFalse($user->isDeptHead());
    }

    public function test_is_dept_head_returns_true_for_lowest_rank_when_no_higher_positions_exist_in_dept(): void
    {
        $employee = $this->userWithProfile('ZZ2', 'employee');

        $this->assertTrue($employee->isDeptHead());
    }

    public function test_highest_ranking_in_department_returns_the_lowest_rank_number_user(): void
    {
        $this->userWithProfile('ZZ3', 'employee');
        $manager = $this->userWithProfile('ZZ3', 'manager');
        $this->userWithProfile('ZZ3', 'supervisor');

        $highest = User::highestRankingInDepartment('ZZ3');

        $this->assertNotNull($highest);
        $this->assertSame($manager->id, $highest->id);
    }

    public function test_highest_ranking_in_department_returns_null_when_no_active_users_in_dept(): void
    {
        $this->assertNull(User::highestRankingInDepartment('XX'));
    }

    public function test_highest_ranking_in_department_ignores_inactive_users(): void
    {
        $this->dept('ZZ4');
        $inactive = User::factory()->create(['status' => 'inactive']);
        Profile::factory()->create([
            'user_id' => $inactive->id,
            'department_id' => 'ZZ4',
            'position' => 'ceo',
        ]);

        $this->assertNull(User::highestRankingInDepartment('ZZ4'));
    }

    public function test_reset_todays_desk_cache_clears_static_memo(): void
    {
        $prop = new \ReflectionProperty(User::class, 'todaysDeskCache');
        $prop->setAccessible(true);

        User::primeTodaysDeskCache();
        $this->assertNotNull($prop->getValue());

        User::resetTodaysDeskCache();
        $this->assertNull($prop->getValue());
    }

    public function test_get_todays_desk_extension_returns_null_when_cache_not_primed(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertNull($user->getTodaysDeskExtension());
    }

    public function test_prime_todays_desk_cache_populates_extension_from_today_reservations(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create([
            'type' => 'seat',
            'metadata' => ['extension' => '1234'],
        ]);
        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->setHour(9),
            'end_time' => now()->setHour(17),
            'is_full_day' => false,
            'status' => 'active',
        ]);

        User::primeTodaysDeskCache();

        $this->assertSame('1234', $user->fresh()->getTodaysDeskExtension());
    }

    public function test_get_todays_status_summary_returns_null_when_cache_not_primed(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertNull($user->getTodaysStatusSummary());
    }

    public function test_prime_todays_desk_cache_populates_status_summary_from_today_reservation(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create([
            'type' => 'seat',
            'metadata' => ['floor' => '7', 'unit' => '39', 'extension' => '1234'],
        ]);
        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->setHour(9),
            'end_time' => now()->setHour(17),
            'is_full_day' => false,
            'status' => 'active',
        ]);

        User::primeTodaysDeskCache();

        $this->assertSame($resource->status_summary, $user->fresh()->getTodaysStatusSummary());
    }

    public function test_prime_todays_desk_cache_now_includes_meeting_type_reservations(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $resource = Resource::factory()->create([
            'type' => 'meeting',
            'metadata' => ['extension' => '5678'],
        ]);
        Reservation::factory()->create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'start_time' => now()->setHour(9),
            'end_time' => now()->setHour(10),
            'is_full_day' => false,
            'status' => 'active',
        ]);

        User::primeTodaysDeskCache();

        $this->assertSame('5678', $user->fresh()->getTodaysDeskExtension());
    }

    public function test_reset_todays_desk_cache_also_clears_status_summary_cache(): void
    {
        $prop = new \ReflectionProperty(User::class, 'todaysStatusSummaryCache');
        $prop->setAccessible(true);

        User::primeTodaysDeskCache();
        $this->assertNotNull($prop->getValue());

        User::resetTodaysDeskCache();
        $this->assertNull($prop->getValue());
    }

    public function test_can_access_panel_returns_true_for_developer(): void
    {
        $developer = User::factory()->create(['role' => 'developer', 'status' => 'active']);

        $this->assertTrue($developer->canAccessPanel(app(\Filament\Panel::class)));
    }

    public function test_can_access_panel_returns_false_for_plain_user(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->assertFalse($user->canAccessPanel(app(\Filament\Panel::class)));
    }

    public function test_can_access_panel_returns_true_for_admin_with_super_admin_permission(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Permission::factory()->create(['user_id' => $admin->id, 'is_super_admin' => true]);

        $this->assertTrue($admin->canAccessPanel(app(\Filament\Panel::class)));
    }

    public function test_can_access_panel_returns_false_for_admin_without_abilities_or_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Permission::factory()->create(['user_id' => $admin->id, 'is_super_admin' => false, 'abilities' => []]);

        $this->assertFalse($admin->canAccessPanel(app(\Filament\Panel::class)));
    }

    public function test_permits_returns_true_for_developer_regardless_of_module(): void
    {
        $developer = User::factory()->create(['role' => 'developer', 'status' => 'active']);

        $this->assertTrue($developer->permits('dms', 'view'));
    }

    public function test_permits_returns_true_when_admin_has_matching_ability(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Permission::factory()->create([
            'user_id' => $admin->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'dms', 'actions' => ['view', 'create']]],
        ]);

        $this->assertTrue($admin->permits('dms', 'view'));
        $this->assertFalse($admin->permits('dms', 'delete'));
    }

    public function test_permits_returns_false_for_plain_user_without_permission_row(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->assertFalse($user->permits('dms', 'view'));
    }

    public function test_permits_memoizes_the_row_per_instance_while_a_fresh_instance_sees_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Permission::factory()->create([
            'user_id' => $admin->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'dms', 'actions' => ['view']]],
        ]);

        $this->assertTrue($admin->permits('dms', 'view'));
        $this->assertFalse($admin->permits('dms', 'create'));

        Permission::where('user_id', $admin->id)->first()->update(['abilities' => [['module' => 'dms', 'actions' => ['view', 'create']]]]);

        $this->assertFalse($admin->permits('dms', 'create'), 'the same instance keeps its request-start resolution');
        $this->assertTrue(User::find($admin->id)->permits('dms', 'create'), 'a fresh instance reads through the rotated version key and sees the grant');
    }

    public function test_permits_picks_up_a_row_created_after_a_null_resolution(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->assertFalse($admin->permits('dms', 'view'));

        Permission::factory()->create([
            'user_id' => $admin->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'dms', 'actions' => ['view']]],
        ]);

        $this->assertTrue($admin->permits('dms', 'view'), 'a null resolution is not memoized, so a later grant is seen within the same instance');
    }

    public function test_creating_user_invalidates_cached_all_option_list(): void
    {
        User::getCachedAllOptions();
        $keyBefore = ModelCacheVersion::key(User::class, 'user_all_options');

        $newUser = User::factory()->create([
            'status' => 'active',
            'name' => 'CacheInvalidationProbe',
        ]);

        $this->assertNotSame($keyBefore, ModelCacheVersion::key(User::class, 'user_all_options'), 'user_all_options cache key did not rotate on create');

        $refreshed = User::getCachedAllOptions();
        $this->assertTrue($refreshed->has($newUser->id));
        $this->assertSame('CacheInvalidationProbe', $refreshed->get($newUser->id));
    }

    public function test_changing_type_alone_invalidates_cached_active_options(): void
    {
        $user = User::factory()->create(['status' => 'active', 'type' => 'guest']);
        User::getCachedActiveOptions();
        $keyBefore = ModelCacheVersion::key(User::class, 'user_active_options');

        $user->type = 'employee';
        $user->save();

        $this->assertNotSame($keyBefore, ModelCacheVersion::key(User::class, 'user_active_options'), 'visibleOnBoard() filters on type, so a type-only change must still bust user_active_options');
    }

    public function test_get_cached_department_options_scopes_by_department_without_key_collision(): void
    {
        $userA = $this->userWithProfile('ZZ5', 'employee', ['name' => 'Dept ZZ5 Probe']);
        $userB = $this->userWithProfile('ZZ6', 'employee', ['name' => 'Dept ZZ6 Probe']);

        $optionsA = User::getCachedDepartmentOptions('ZZ5');
        $optionsB = User::getCachedDepartmentOptions('ZZ6');

        $this->assertTrue($optionsA->has($userA->id));
        $this->assertFalse($optionsA->has($userB->id));
        $this->assertTrue($optionsB->has($userB->id));
        $this->assertFalse($optionsB->has($userA->id));
    }

    public function test_moving_a_profile_between_departments_invalidates_cached_department_options(): void
    {
        $this->dept('ZZ8');
        $user = $this->userWithProfile('ZZ7', 'employee');
        $this->assertTrue(User::getCachedDepartmentOptions('ZZ7')->has($user->id));

        $user->profile->update(['department_id' => 'ZZ8']);

        $this->assertFalse(User::getCachedDepartmentOptions('ZZ7')->has($user->id), 'the old department options must not still list the moved user');
        $this->assertTrue(User::getCachedDepartmentOptions('ZZ8')->has($user->id), 'the new department options must include the moved user');
    }

    public function test_saving_a_profile_without_department_change_does_not_bump_the_user_cache_version(): void
    {
        $user = $this->userWithProfile('ZZ9', 'employee');
        $keyBefore = ModelCacheVersion::key(User::class, 'user_dept_options:ZZ9');

        $user->profile->update(['address' => 'CacheProbe']);

        $this->assertSame($keyBefore, ModelCacheVersion::key(User::class, 'user_dept_options:ZZ9'),
            'a non-department profile save must not rotate the User cache version — only department_id/position changes bump it');
    }

    public function test_changing_a_profile_position_bumps_the_user_cache_version(): void
    {
        $user = $this->userWithProfile('ZZ9', 'employee');
        $keyBefore = ModelCacheVersion::key(User::class, 'user_dept_options:ZZ9');

        $user->profile->update(['position' => 'manager']);

        $this->assertNotSame($keyBefore, ModelCacheVersion::key(User::class, 'user_dept_options:ZZ9'));
    }

    public function test_touch_last_seen_restores_timestamps_flag_for_subsequent_saves(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $originalUpdated = $user->fresh()->updated_at;

        $user->touchLastSeen();

        $this->assertTrue($user->timestamps);

        $this->travelTo(now()->addMinute());
        $user->name = 'TimestampProbe';
        $user->save();

        $this->assertNotEquals($originalUpdated, $user->fresh()->updated_at);
    }

    public function test_head_change_reconcile_also_covers_open_tickets_routed_to_the_default_department(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $head = $this->userWithProfile('PS', 'manager');
        $legacy = \App\Models\Ticket::factory()->create(['status' => 'open']);
        DB::table('tickets')->where('id', $legacy->id)->update(['extra' => json_encode(['department' => 'ZZ1'])]);

        $head->profile->update(['position' => 'employee']);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ReconcileNudge::class, fn($job) => $job->subjectId === $legacy->id);
    }
}
