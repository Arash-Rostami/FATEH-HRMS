<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Tab\Faqs;
use App\Livewire\Dashboard\Tab\Presentation\FaqPresenter;
use App\Models\Department;
use App\Models\FAQ;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FaqsTest extends TestCase
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

    public function test_faqs_render_and_filter()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        FAQ::factory()->create([
            'department_id' => 'IT',
            'user_id' => $user->id,
            'category' => 'general',
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->assertHasNoErrors()
            ->call('filterByDepartment', 'IT')
            ->assertHasNoErrors()
            ->call('filterByCategory', 'general')
            ->assertHasNoErrors()
            ->call('loadMore')
            ->assertHasNoErrors()
            ->call('resetFilters')
            ->assertHasNoErrors();
    }

    public function test_total_faqs_computed_reflects_newly_created_rows()
    {
        $user = User::factory()->create(['status' => 'active']);
        $before = FAQ::count();
        FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);
        FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->assertSet('totalFaqs', $before + 2);
    }

    public function test_faqs_list_paginates_with_default_per_page_of_ten()
    {
        $user = User::factory()->create(['status' => 'active']);
        FAQ::factory()->count(12)->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        $list = Livewire::actingAs($user)->test(Faqs::class)->get('faqs');

        $this->assertSame(10, $list->count());
        $this->assertTrue($list->hasMorePages());
    }

    public function test_load_more_increases_per_page_by_ten()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->assertSet('perPage', 10)
            ->call('loadMore')
            ->assertSet('perPage', 20)
            ->call('loadMore')
            ->assertSet('perPage', 30);
    }

    public function test_filter_by_category_sets_selected_category_and_resets_page()
    {
        $user = User::factory()->create(['status' => 'active']);
        FAQ::factory()->count(12)->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->call('filterByCategory', 'general')
            ->assertSet('selectedCategory', 'general')
            ->assertSet('paginators.page', 1);
    }

    public function test_filter_by_category_clears_open_focus()
    {
        $user = User::factory()->create(['status' => 'active']);
        $faq = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->set('open', $faq->id)
            ->assertSet('open', $faq->id)
            ->call('filterByCategory', 'general')
            ->assertSet('open', null);
    }

    public function test_filter_by_category_narrows_list_to_matching_faqs()
    {
        $user = User::factory()->create(['status' => 'active']);
        $keep = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'keep-uniq']);
        $drop = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'drop-uniq']);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Faqs::class)
            ->call('filterByCategory', 'keep-uniq')
            ->get('faqs')
            ->pluck('id')
            ->all();

        $this->assertContains($keep->id, $ids);
        $this->assertNotContains($drop->id, $ids);
    }

    public function test_filter_by_department_sets_selected_department_and_resets_page()
    {
        $user = User::factory()->create(['status' => 'active']);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        FAQ::factory()->count(12)->create(['user_id' => $user->id, 'department_id' => 'IT', 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->call('filterByDepartment', 'IT')
            ->assertSet('selectedDepartment', 'IT')
            ->assertSet('paginators.page', 1);
    }

    public function test_filter_by_department_clears_open_focus()
    {
        $user = User::factory()->create(['status' => 'active']);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        $faq = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => 'IT', 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->set('open', $faq->id)
            ->assertSet('open', $faq->id)
            ->call('filterByDepartment', 'IT')
            ->assertSet('open', null);
    }

    public function test_filter_by_department_narrows_list_to_matching_department()
    {
        $user = User::factory()->create(['status' => 'active']);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        Department::firstOrCreate(['code' => 'HR'], ['name' => 'HR']);
        $keep = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => 'IT', 'category' => 'general']);
        $drop = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => 'HR', 'category' => 'general']);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Faqs::class)
            ->call('filterByDepartment', 'IT')
            ->get('faqs')
            ->pluck('id')
            ->all();

        $this->assertContains($keep->id, $ids);
        $this->assertNotContains($drop->id, $ids);
    }

    public function test_reset_filters_clears_search_category_department_and_resets_page()
    {
        $user = User::factory()->create(['status' => 'active']);
        Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);
        FAQ::factory()->count(12)->create(['user_id' => $user->id, 'department_id' => 'IT', 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->set('search', 'term')
            ->set('selectedCategory', 'general')
            ->set('selectedDepartment', 'IT')
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('selectedCategory', null)
            ->assertSet('selectedDepartment', null)
            ->assertSet('paginators.page', 1);
    }

    public function test_reset_filters_clears_open_focus()
    {
        $user = User::factory()->create(['status' => 'active']);
        $faq = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->set('open', $faq->id)
            ->assertSet('open', $faq->id)
            ->call('resetFilters')
            ->assertSet('open', null);
    }

    public function test_updated_search_resets_page_and_clears_open()
    {
        $user = User::factory()->create(['status' => 'active']);
        $faq = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);
        FAQ::factory()->count(11)->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->set('open', $faq->id)
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->assertSet('open', $faq->id)
            ->set('search', 'term')
            ->assertSet('paginators.page', 1)
            ->assertSet('open', null);
    }

    public function test_search_filters_by_question_text()
    {
        $user = User::factory()->create(['status' => 'active']);
        $keep = FAQ::factory()->create([
            'user_id' => $user->id,
            'department_id' => null,
            'category' => 'general',
            'question' => 'unique-question-xyz-token',
            'answer' => 'irrelevant answer text',
        ]);
        $drop = FAQ::factory()->create([
            'user_id' => $user->id,
            'department_id' => null,
            'category' => 'general',
            'question' => 'totally different subject',
            'answer' => 'no token here at all',
        ]);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Faqs::class)
            ->set('search', 'unique-question-xyz-token')
            ->get('faqs')
            ->pluck('id')
            ->all();

        $this->assertContains($keep->id, $ids);
        $this->assertNotContains($drop->id, $ids);
    }

    public function test_search_filters_by_answer_text()
    {
        $user = User::factory()->create(['status' => 'active']);
        $keep = FAQ::factory()->create([
            'user_id' => $user->id,
            'department_id' => null,
            'category' => 'general',
            'question' => 'some unrelated question',
            'answer' => 'unique-answer-token-abc',
        ]);
        $drop = FAQ::factory()->create([
            'user_id' => $user->id,
            'department_id' => null,
            'category' => 'general',
            'question' => 'another unrelated question',
            'answer' => 'nothing matching here',
        ]);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Faqs::class)
            ->set('search', 'unique-answer-token-abc')
            ->get('faqs')
            ->pluck('id')
            ->all();

        $this->assertContains($keep->id, $ids);
        $this->assertNotContains($drop->id, $ids);
    }

    public function test_search_with_falsy_zero_string_filters_to_matching_faqs_only()
    {
        $user = User::factory()->create(['status' => 'active']);
        $match = FAQ::factory()->create([
            'user_id' => $user->id,
            'department_id' => null,
            'category' => 'general',
            'question' => 'سوال 0 نمونه',
            'answer' => 'پاسخ',
        ]);
        $other = FAQ::factory()->create([
            'user_id' => $user->id,
            'department_id' => null,
            'category' => 'general',
            'question' => 'سوال بدون رقم صفر در متن',
            'answer' => 'پاسخ نمونه بدون هیچ رقم',
        ]);

        Livewire::withoutLazyLoading();
        $ids = Livewire::actingAs($user)->test(Faqs::class)
            ->set('search', '0')
            ->get('faqs')
            ->pluck('id')
            ->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_focus_mode_pins_listing_to_single_open_record()
    {
        $user = User::factory()->create(['status' => 'active']);
        $target = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);
        $other = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        $list = Livewire::actingAs($user)->test(Faqs::class)
            ->set('open', $target->id)
            ->get('faqs');

        $this->assertSame(1, $list->count());
        $this->assertSame($target->id, $list->first()->id);
        $this->assertNotSame($other->id, $list->first()->id);
    }

    public function test_mount_with_open_query_param_increases_per_page_to_at_least_fifty()
    {
        $user = User::factory()->create(['status' => 'active']);
        $faq = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withQueryParams(['open' => $faq->id])
            ->actingAs($user)
            ->test(Faqs::class)
            ->assertSet('open', $faq->id)
            ->assertSet('perPage', 50);
    }

    public function test_clear_focus_resets_open_and_resets_page()
    {
        $user = User::factory()->create(['status' => 'active']);
        $faq = FAQ::factory()->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);
        FAQ::factory()->count(11)->create(['user_id' => $user->id, 'department_id' => null, 'category' => 'general']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->set('open', $faq->id)
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->call('clearFocus')
            ->assertSet('open', null)
            ->assertSet('paginators.page', 1);
    }

    public function test_search_with_no_match_returns_empty_list()
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        $list = Livewire::actingAs($user)->test(Faqs::class)
            ->set('search', 'zzz-no-such-faq-token-xyz')
            ->get('faqs');

        $this->assertTrue($list->isEmpty());
    }

    public function test_answer_snippet_decodes_editor_entities(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $faq = FAQ::factory()->create([
            'user_id' => $user->id,
            'department_id' => null,
            'category' => 'general',
            'answer' => '<p>او &quot;سلام&quot; گفت &amp; رفت</p>',
        ]);

        $this->assertSame('او "سلام" گفت & رفت', (new FaqPresenter())->answerSnippet($faq));
    }

    public function test_toggle_view_sets_and_persists_view_choice(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)->assertSet('view', 'list')->call('toggleView', 'card')->assertSet('view', 'card');
        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)->assertSet('view', 'card');
    }

    public function test_toggle_view_ignores_unsupported_value(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)
            ->call('toggleView', 'list')->assertSet('view', 'list')
            ->call('toggleView', 'grid')->assertSet('view', 'list');
    }

    public function test_list_row_expands_in_place_instead_of_switching_to_card_view(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        FAQ::factory()->create(['user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)->call('toggleView', 'list')
            ->assertSeeHtml('toggle(')
            ->assertDontSeeHtml("view = 'card'; active =");
    }

    public function test_card_view_answer_is_wired_to_the_same_toggle_accordion(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        FAQ::factory()->create(['user_id' => $user->id]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($user)->test(Faqs::class)->call('toggleView', 'card')
            ->assertSeeHtml('toggle(')
            ->assertSeeHtml('active === ');
    }
}
