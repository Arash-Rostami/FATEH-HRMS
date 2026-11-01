<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\FAQResource\Pages\CreateFAQ;
use App\Filament\Resources\FAQResource\Pages\EditFAQ;
use App\Filament\Resources\FAQResource\Pages\ListFAQs;
use App\Models\FAQ;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FAQResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::forget('faq_categories_filter_options');
        Cache::forget('user_filter_options');
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

    public function test_list_renders_and_shows_factory_faq_row()
    {
        $admin = $this->developer();
        $faq = FAQ::factory()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListFAQs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$faq]);
    }

    public function test_create_faq_persists_question_answer_and_category()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateFAQ::class)
            ->fillForm([
                'category' => 'General',
                'question' => '<p>What is the leave policy?</p>',
                'answer' => '<p>Employees may request leave via the HR portal.</p>',
                'user_id' => $admin->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('faqs', [
            'category' => 'General',
            'user_id' => $admin->id,
        ]);

        $created = FAQ::query()->where('user_id', $admin->id)->latest('id')->first();
        $this->assertNotNull($created);
        $this->assertStringContainsString('What is the leave policy?', $created->question);
        $this->assertStringContainsString('Employees may request leave via the HR portal.', $created->answer);
    }

    public function test_create_faq_requires_question()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateFAQ::class)
            ->fillForm([
                'category' => 'General',
                'answer' => '<p>An answer without a question.</p>',
                'user_id' => $admin->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['question']);
    }

    public function test_edit_faq_updates_answer()
    {
        $admin = $this->developer();
        $faq = FAQ::factory()->create(['user_id' => $admin->id, 'answer' => '<p>Old answer.</p>']);

        Livewire::actingAs($admin)->test(EditFAQ::class, ['record' => $faq->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['answer' => '<p>Updated answer text.</p>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('Updated answer text', $faq->fresh()->answer);
    }

    public function test_edit_heading_strips_html_tags_from_rich_question()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();
        $faq = FAQ::factory()->create([
            'user_id' => $admin->id,
            'question' => '<p><strong>☕️ سوال html</strong></p>',
        ]);

        $heading = (string) Livewire::actingAs($admin)
            ->test(EditFAQ::class, ['record' => $faq->getRouteKey()])
            ->assertSuccessful()
            ->instance()->getHeading();

        $this->assertStringContainsString('☕️ سوال html', $heading);
        $this->assertStringNotContainsString('<p>', $heading);
        $this->assertStringNotContainsString('<strong>', $heading);
    }

    public function test_view_modal_heading_strips_html_tags_from_rich_question()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();
        $faq = FAQ::factory()->create([
            'user_id' => $admin->id,
            'question' => '<p><strong>☕️ سوال html</strong></p>',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(ListFAQs::class)
            ->assertSuccessful()
            ->mountTableAction('view', $faq);

        $action = $component->instance()->getMountedAction();
        $this->assertNotNull($action, 'view table action should mount');

        $heading = (string) $action->getModalHeading();

        $this->assertStringContainsString('نمایش', $heading);
        $this->assertStringContainsString('☕️ سوال html', $heading);
        $this->assertStringNotContainsString('<p>', $heading);
        $this->assertStringNotContainsString('<strong>', $heading);
    }

    public function test_delete_faq_removes_row_from_database()
    {
        $admin = $this->developer();
        $faq = FAQ::factory()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListFAQs::class)
            ->callTableAction('delete', $faq);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_category_filter_scopes_list_to_matching_faqs()
    {
        $admin = $this->developer();
        $matching = FAQ::factory()->create(['user_id' => $admin->id, 'category' => 'Payroll']);
        $other = FAQ::factory()->create(['user_id' => $admin->id, 'category' => 'Leave']);

        Livewire::actingAs($admin)->test(ListFAQs::class)
            ->assertSuccessful()
            ->filterTable('category', 'Payroll')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_department_filter_scopes_list_to_matching_department()
    {
        $admin = $this->developer();
        $deptA = \App\Models\Department::factory()->create();
        $deptB = \App\Models\Department::factory()->create();

        $matching = FAQ::factory()->create(['user_id' => $admin->id, 'department_id' => $deptA->code]);
        $other = FAQ::factory()->create(['user_id' => $admin->id, 'department_id' => $deptB->code]);

        Livewire::actingAs($admin)->test(ListFAQs::class)
            ->assertSuccessful()
            ->filterTable('department_id', $deptA->code)
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_user_filter_scopes_list_to_matching_user()
    {
        $admin = $this->developer();
        $otherUser = User::factory()->create();

        $adminFaq = FAQ::factory()->create(['user_id' => $admin->id]);
        $otherFaq = FAQ::factory()->create(['user_id' => $otherUser->id]);

        Livewire::actingAs($admin)->test(ListFAQs::class)
            ->assertSuccessful()
            ->filterTable('user_id', $admin->id)
            ->assertCanSeeTableRecords([$adminFaq])
            ->assertCanNotSeeTableRecords([$otherFaq]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = FAQ::factory()->create(['user_id' => $admin->id]);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = FAQ::factory()->create(['user_id' => $admin->id]);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListFAQs::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_category_id_and_created_at_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = FAQ::factory()->create(['user_id' => $admin->id, 'category' => '0-AAA-sort-category']);
        $first->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $second = FAQ::factory()->create(['user_id' => $admin->id, 'category' => '0-ZZZ-sort-category']);
        $second->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListFAQs::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('category')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('category', 'desc')->assertSuccessful();
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
    }

    public function test_sorting_by_department_and_user_orders_records_correctly()
    {
        $admin = $this->developer();
        $deptA = \App\Models\Department::factory()->create(['name' => '0-AAA-sort-department']);
        $deptZ = \App\Models\Department::factory()->create(['name' => '0-ZZZ-sort-department']);
        $userA = User::factory()->create(['name' => '0-AAA-sort-user']);
        $userZ = User::factory()->create(['name' => '0-ZZZ-sort-user']);

        $first = FAQ::factory()->create(['user_id' => $userA->id, 'department_id' => $deptA->code]);
        $second = FAQ::factory()->create(['user_id' => $userZ->id, 'department_id' => $deptZ->code]);

        $component = Livewire::actingAs($admin)->test(ListFAQs::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('department.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('department.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        FAQ::factory()->create(['user_id' => $admin->id]);

        $component = Livewire::actingAs($admin)->test(ListFAQs::class)->assertSuccessful();

        foreach (['category', 'department.description', 'user.name'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\FAQResource\Pages\CreateFAQ::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\FAQResource\Pages\EditFAQ::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\FAQResource\Pages\ListFAQs::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\FAQResource\Pages\CreateFAQ::class, \App\Filament\Resources\FAQResource\Pages\EditFAQ::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/FAQResource/Schemas/FAQTablePresenter.php'),
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
