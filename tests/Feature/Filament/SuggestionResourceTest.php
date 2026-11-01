<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SuggestionResource\Pages\CreateSuggestion;
use App\Filament\Resources\SuggestionResource\Pages\EditSuggestion;
use App\Filament\Resources\SuggestionResource\Pages\ListSuggestions;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers\SuggestionsRelationManager;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\Review;
use App\Models\Suggestion;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuggestionResourceTest extends TestCase
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

    public function test_create_suggestion_admin_panel_creates_blank_home_dept_review_when_self_fill_false_and_not_manager()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'TST'], ['name' => 'TST', 'description' => 'TST']);
        $manager = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $manager->id, 'department_id' => 'TST', 'position' => 'manager']);
        $expert = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $expert->id, 'department_id' => 'TST', 'position' => 'expert']);

        Livewire::actingAs($admin)->test(CreateSuggestion::class)
            ->fillForm([
                'title' => 'Admin parity probe',
                'description' => 'Admin panel parity probe content for review row creation',
                'purpose' => ['cost'],
                'rule' => ['simplify'],
                'user_id' => $expert->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $suggestion = Suggestion::where('title', 'Admin parity probe')->firstOrFail();
        $reviews = Review::where('suggestion_id', $suggestion->id)->get();

        $this->assertCount(1, $reviews);
        $this->assertSame('TST', $reviews->first()->department_id);
        $this->assertSame('unknown', $reviews->first()->feedback);
        $this->assertSame('team_remarks', $suggestion->stage);
    }

    public function test_list_renders_and_shows_factory_suggestion_row()
    {
        $admin = $this->developer();
        $suggestion = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0List Render Suggestion']);

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$suggestion]);
    }

    public function test_create_suggestion_persists_title_description_and_purpose()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateSuggestion::class)
            ->fillForm([
                'title' => 'پیشنهاد بهبود فرآیند تست',
                'description' => 'محتوای پیشنهاد جهت بررسی ذخیره‌سازی در پنل مدیریت',
                'purpose' => ['cost'],
                'rule' => ['simplify'],
                'priority' => 'medium',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $suggestion = Suggestion::query()->where('user_id', $admin->id)->latest('id')->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('پیشنهاد بهبود فرآیند تست', $suggestion->title);
        $this->assertStringContainsString('محتوای پیشنهاد جهت بررسی ذخیره‌سازی', $suggestion->description);
        $this->assertSame(['cost'], $suggestion->purpose);
        $this->assertSame(['simplify'], $suggestion->rule);
        $this->assertSame('pending', $suggestion->stage);
    }

    public function test_create_suggestion_requires_title()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateSuggestion::class)
            ->fillForm([
                'title' => '',
                'description' => 'محتوای پیشنهاد بدون عنوان جهت بررسی',
                'purpose' => ['cost'],
                'rule' => ['simplify'],
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_create_suggestion_requires_description()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateSuggestion::class)
            ->fillForm([
                'title' => 'پیشنهاد بدون محتوا',
                'description' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
                'purpose' => ['cost'],
                'rule' => ['simplify'],
            ])
            ->call('create')
            ->assertHasFormErrors(['description']);
    }

    public function test_edit_suggestion_updates_title()
    {
        $admin = $this->developer();
        $suggestion = Suggestion::factory()->create([
            'user_id' => $admin->id,
            'title' => 'عنوان قدیمی',
            'purpose' => ['cost'],
            'rule' => ['simplify'],
        ]);

        Livewire::actingAs($admin)->test(EditSuggestion::class, ['record' => $suggestion->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'عنوان ویرایش‌شده پیشنهاد'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('عنوان ویرایش‌شده پیشنهاد', $suggestion->fresh()->title);
    }

    public function test_delete_suggestion_action_removes_record()
    {
        $admin = $this->developer();
        $suggestion = Suggestion::factory()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->callTableAction(DeleteAction::class, $suggestion);

        $this->assertDatabaseMissing('suggestions', ['id' => $suggestion->id]);
    }

    public function test_create_suggestion_admin_panel_silently_drops_ma_as_stakeholder_department()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        $expert = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $expert->id, 'department_id' => 'PS', 'position' => 'expert']);

        Livewire::actingAs($admin)->test(CreateSuggestion::class)
            ->fillForm([
                'title' => 'Admin MA injection probe',
                'description' => 'Admin panel MA injection probe content for review row creation',
                'purpose' => ['cost'],
                'rule' => ['simplify'],
                'user_id' => $expert->id,
                'departments' => ['MA'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $suggestion = Suggestion::where('title', 'Admin MA injection probe')->firstOrFail();
        $this->assertNotContains('MA', $suggestion->departments);
        $this->assertNull(
            Review::where('suggestion_id', $suggestion->id)->where('department_id', 'MA')->first()
        );
    }

    public function test_edit_suggestion_admin_panel_strips_ma_from_stakeholder_departments_only()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        $suggestion = Suggestion::factory()->create([
            'user_id' => $admin->id,
            'departments' => ['PS'],
            'purpose' => ['cost'],
            'rule' => ['simplify'],
        ]);

        Livewire::actingAs($admin)->test(EditSuggestion::class, ['record' => $suggestion->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['departments' => ['PS', 'MA']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['PS'], $suggestion->fresh()->departments);
    }

    public function test_edit_suggestion_admin_panel_keeps_saving_when_home_department_is_ma()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        $suggestion = Suggestion::factory()->create([
            'user_id' => $admin->id,
            'departments' => ['MA', 'PS'],
            'purpose' => ['cost'],
            'rule' => ['simplify'],
        ]);

        Livewire::actingAs($admin)->test(EditSuggestion::class, ['record' => $suggestion->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['priority' => 'high'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['MA', 'PS'], $suggestion->fresh()->departments);
        $this->assertSame('high', $suggestion->fresh()->priority);
    }

    public function test_suggestions_relation_manager_creates_suggestion_for_owner_record()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'TST'], ['name' => 'TST', 'description' => 'TST']);
        $manager = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $manager->id, 'department_id' => 'TST', 'position' => 'manager']);
        $targetUser = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $targetUser->id, 'department_id' => 'TST', 'position' => 'expert']);

        Livewire::actingAs($admin)
            ->test(SuggestionsRelationManager::class, [
                'ownerRecord' => $targetUser,
                'pageClass' => EditUser::class,
            ])
            ->mountTableAction('create')
            ->setTableActionData([
                'title' => 'Relation manager create probe',
                'description' => 'Relation manager create probe content for review row creation',
                'purpose' => ['cost'],
                'rule' => ['simplify'],
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $suggestion = Suggestion::where('title', 'Relation manager create probe')->firstOrFail();
        $this->assertSame($targetUser->id, $suggestion->user_id);
        $this->assertSame(['TST'], $suggestion->departments);

        $review = Review::where('suggestion_id', $suggestion->id)->first();
        $this->assertNotNull($review);
        $this->assertSame('TST', $review->department_id);
        $this->assertSame('unknown', $review->feedback);
        $this->assertSame('team_remarks', $suggestion->stage);
    }

    public function test_create_suggestion_admin_panel_rejects_ma_department_submitter()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        Profile::factory()->create(['user_id' => $admin->id, 'department_id' => 'MA', 'position' => 'manager']);

        Livewire::actingAs($admin)->test(CreateSuggestion::class)
            ->fillForm([
                'title' => 'MA admin restricted probe',
                'description' => 'MA admin restricted probe content for review row creation',
                'purpose' => ['cost'],
                'rule' => ['simplify'],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(0, Suggestion::where('title', 'MA admin restricted probe')->count());
    }

    public function test_create_suggestion_admin_panel_rejects_mg_department_submitter()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'MG'], ['name' => 'MG', 'description' => 'MG']);
        Profile::factory()->create(['user_id' => $admin->id, 'department_id' => 'MG', 'position' => 'manager']);

        Livewire::actingAs($admin)->test(CreateSuggestion::class)
            ->fillForm([
                'title' => 'MG admin restricted probe',
                'description' => 'MG admin restricted probe content for review row creation',
                'purpose' => ['cost'],
                'rule' => ['simplify'],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(0, Suggestion::where('title', 'MG admin restricted probe')->count());
    }

    public function test_suggestions_relation_manager_hides_create_action_for_ma_department_owner()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'MA'], ['name' => 'MA', 'description' => 'MA']);
        $targetUser = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $targetUser->id, 'department_id' => 'MA', 'position' => 'manager']);

        Livewire::actingAs($admin)
            ->test(SuggestionsRelationManager::class, [
                'ownerRecord' => $targetUser,
                'pageClass' => EditUser::class,
            ])
            ->assertTableActionHidden('create');
    }

    public function test_suggestions_relation_manager_hides_create_action_for_mg_department_owner()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'MG'], ['name' => 'MG', 'description' => 'MG']);
        $targetUser = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $targetUser->id, 'department_id' => 'MG', 'position' => 'manager']);

        Livewire::actingAs($admin)
            ->test(SuggestionsRelationManager::class, [
                'ownerRecord' => $targetUser,
                'pageClass' => EditUser::class,
            ])
            ->assertTableActionHidden('create');
    }

    public function test_suggestions_relation_manager_shows_create_action_for_non_ma_department_owner()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'TST'], ['name' => 'TST', 'description' => 'TST']);
        $targetUser = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $targetUser->id, 'department_id' => 'TST', 'position' => 'expert']);

        Livewire::actingAs($admin)
            ->test(SuggestionsRelationManager::class, [
                'ownerRecord' => $targetUser,
                'pageClass' => EditUser::class,
            ])
            ->assertTableActionVisible('create');
    }

    public function test_suggestions_relation_manager_sorts_by_review_counts_without_error()
    {
        $admin = $this->developer();
        $targetUser = User::factory()->create(['status' => 'active']);
        $suggestion = Suggestion::factory()->create(['user_id' => $targetUser->id, 'title' => 'Sortable count probe']);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'TST', 'feedback' => 'agree', 'user_id' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(SuggestionsRelationManager::class, [
                'ownerRecord' => $targetUser,
                'pageClass' => EditUser::class,
            ])
            ->sortTable('agree_count')
            ->assertSuccessful()
            ->sortTable('neutral_count')
            ->assertSuccessful()
            ->sortTable('disagree_count')
            ->assertSuccessful();
    }

    public function test_suggestion_exporter_includes_review_counts()
    {
        $admin = $this->developer();
        $suggestion = Suggestion::factory()->create(['user_id' => $admin->id]);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'TST', 'feedback' => 'agree', 'user_id' => $admin->id]);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'IT', 'feedback' => 'disagree', 'user_id' => $admin->id]);
        Review::factory()->create(['suggestion_id' => $suggestion->id, 'department_id' => 'HR', 'feedback' => 'neutral', 'user_id' => $admin->id]);

        $exported = \App\Filament\Resources\SuggestionResource\Exports\SuggestionExporter::modifyQuery(Suggestion::query())
            ->find($suggestion->id);

        $this->assertSame(1, (int) $exported->agree_count);
        $this->assertSame(1, (int) $exported->disagree_count);
        $this->assertSame(1, (int) $exported->neutral_count);
    }

    public function test_self_fill_filter_scopes_list_to_self_filled_suggestions()
    {
        $admin = $this->developer();
        $selfFilled = Suggestion::factory()->create(['user_id' => $admin->id, 'self_fill' => true, 'title' => '0Self Filled Suggestion']);
        $notSelfFilled = Suggestion::factory()->create(['user_id' => $admin->id, 'self_fill' => false, 'title' => '0Not Self Filled Suggestion']);

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->assertSuccessful()
            ->filterTable('self_fill', true)
            ->assertCanSeeTableRecords([$selfFilled])
            ->assertCanNotSeeTableRecords([$notSelfFilled]);
    }

    public function test_has_attachment_filter_scopes_list_to_suggestions_with_file()
    {
        $admin = $this->developer();
        $withFile = Suggestion::factory()->create(['user_id' => $admin->id, 'attachment' => 'suggestions/file.pdf', 'title' => '0With File Suggestion']);
        $withoutFile = Suggestion::factory()->create(['user_id' => $admin->id, 'attachment' => null, 'title' => '0No File Suggestion']);

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->assertSuccessful()
            ->filterTable('has_attachment', true)
            ->assertCanSeeTableRecords([$withFile])
            ->assertCanNotSeeTableRecords([$withoutFile]);
    }

    public function test_submitter_filter_scopes_list_to_matching_submitter()
    {
        $admin = $this->developer();
        $otherUser = User::factory()->create(['status' => 'active']);

        $adminSuggestion = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0Admin Submitted Suggestion']);
        $otherSuggestion = Suggestion::factory()->create(['user_id' => $otherUser->id, 'title' => '0Other Submitted Suggestion']);

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->assertSuccessful()
            ->filterTable('user_id', $admin->id)
            ->assertCanSeeTableRecords([$adminSuggestion])
            ->assertCanNotSeeTableRecords([$otherSuggestion]);
    }

    public function test_department_filter_scopes_list_to_matching_department()
    {
        $admin = $this->developer();
        Department::firstOrCreate(['code' => 'TST'], ['name' => 'TST', 'description' => 'TST']);

        $matching = Suggestion::factory()->create(['user_id' => $admin->id, 'departments' => ['TST'], 'title' => '0TST Department Suggestion']);
        $other = Suggestion::factory()->create(['user_id' => $admin->id, 'departments' => ['IT'], 'title' => '0IT Department Suggestion']);

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->assertSuccessful()
            ->filterTable('departments', 'TST')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0In Range Suggestion']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0Out Of Range Suggestion']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_has_referral_filter_scopes_list_to_suggestions_referred_by_ma()
    {
        $admin = $this->developer();

        $referred = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0Referred Suggestion']);
        Review::factory()->create(['suggestion_id' => $referred->id, 'department_id' => 'MA', 'referral' => ['IT'], 'user_id' => $admin->id]);

        $notReferred = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0Not Referred Suggestion']);
        Review::factory()->create(['suggestion_id' => $notReferred->id, 'department_id' => 'MA', 'referral' => [], 'user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->assertSuccessful()
            ->filterTable('has_referral')
            ->assertCanSeeTableRecords([$referred])
            ->assertCanNotSeeTableRecords([$notReferred]);
    }

    public function test_sorting_by_title_created_at_and_submitter_orders_records_correctly()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => '0-AAA-sort-user', 'status' => 'active']);
        $userZ = User::factory()->create(['name' => '0-ZZZ-sort-user', 'status' => 'active']);

        $first = Suggestion::factory()->create(['user_id' => $userA->id, 'title' => '0-AAA-sort-suggestion']);
        $first->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $second = Suggestion::factory()->create(['user_id' => $userZ->id, 'title' => '0-ZZZ-sort-suggestion']);
        $second->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('title')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_stage_orders_records_correctly()
    {
        $admin = $this->developer();
        $pending = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0-stage-sort-a', 'stage' => 'pending']);
        $closed = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0-stage-sort-b', 'stage' => 'closed']);

        $component = Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('stage')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$pending->id, $closed->id], array_values(array_intersect($ids, [$pending->id, $closed->id])));

        $component->sortTable('stage', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$closed->id, $pending->id], array_values(array_intersect($ids, [$pending->id, $closed->id])));
    }

    public function test_sorting_by_agree_neutral_and_disagree_counts_orders_records_correctly()
    {
        $admin = $this->developer();
        $low = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0-counts-sort-low']);
        $high = Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0-counts-sort-high']);
        Review::factory()->create(['suggestion_id' => $high->id, 'department_id' => 'TST', 'feedback' => 'agree', 'user_id' => $admin->id]);
        Review::factory()->create(['suggestion_id' => $high->id, 'department_id' => 'IT', 'feedback' => 'neutral', 'user_id' => $admin->id]);
        Review::factory()->create(['suggestion_id' => $high->id, 'department_id' => 'HR', 'feedback' => 'disagree', 'user_id' => $admin->id]);

        $component = Livewire::actingAs($admin)->test(ListSuggestions::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('agree_count')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('agree_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('neutral_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('neutral_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('disagree_count')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$low->id, $high->id], array_values(array_intersect($ids, [$low->id, $high->id])));

        $component->sortTable('disagree_count', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$high->id, $low->id], array_values(array_intersect($ids, [$low->id, $high->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Suggestion::factory()->create(['user_id' => $admin->id, 'title' => '0Group Render Suggestion']);

        $component = Livewire::actingAs($admin)->test(ListSuggestions::class)->assertSuccessful();

        foreach (['stage', 'user.name'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\SuggestionResource\Pages\CreateSuggestion::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\SuggestionResource\Pages\EditSuggestion::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\SuggestionResource\Pages\ListSuggestions::class => \App\Filament\Pages\ListRecords::class,
            \App\Filament\Resources\SuggestionResource\Pages\ViewSuggestion::class => \App\Filament\Pages\ViewRecord::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\SuggestionResource\Pages\CreateSuggestion::class, \App\Filament\Resources\SuggestionResource\Pages\EditSuggestion::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\SuggestionResource\RelationManagers\ReviewsRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_replaced_attachment(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\SuggestionResource\Pages\EditSuggestion());
        Storage::fake('public');
        Storage::disk('public')->put('suggestions/old.pdf', 'x');
        Storage::disk('public')->put('suggestions/new.pdf', 'x');
        $record = \App\Models\Suggestion::factory()->create(['attachment' => 'suggestions/old.pdf']);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertSame(['suggestions/old.pdf'], $before);

        $record->update(['attachment' => 'suggestions/new.pdf']);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('suggestions/old.pdf');
        Storage::disk('public')->assertExists('suggestions/new.pdf');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/SuggestionResource/Schemas/SuggestionTablePresenter.php'),
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
