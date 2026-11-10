<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LetterheadResource;
use App\Filament\Resources\LetterheadResource\Pages\{CreateLetterhead, EditLetterhead, ListLetterheads};
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\Permission;
use App\Models\User;
use App\Services\Correspondence\CorrespondenceFonts;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class LetterheadResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        app()->setLocale('fa');
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
        $user = User::factory()->create(['role' => 'developer', 'status' => 'active']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_letterhead_resource_lists_and_edits_a_numbering_template(): void
    {
        $admin = $this->developer();
        $head = Letterhead::forceCreate([
            'kind' => Letterhead::KIND_HEAD,
            'name' => 'Before edit',
            'pattern' => '{PREFIX}/{YYYY}/{SEQ:4}',
            'style' => Letterhead::defaultStyle(),
            'config' => ['prefix' => 'L'] + Letterhead::defaultConfig(),
            'margins' => Letterhead::defaultMargins(Letterhead::SIZE_A4),
        ]);

        $this->assertSame(Letterhead::class, LetterheadResource::getModel());

        Livewire::actingAs($admin)
            ->test(ListLetterheads::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$head])
            ->assertTableActionHasUrl('edit', LetterheadResource::getUrl('edit', ['record' => $head]), $head);

        Livewire::actingAs($admin)
            ->test(EditLetterhead::class, ['record' => $head->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'name' => 'After edit',
                'kind' => Letterhead::KIND_HEAD,
                'size' => Letterhead::SIZE_A4,
                'lang' => 'fa',
                'pattern' => '{PREFIX}/{YYYY}/{SEQ:4}',
                'config.prefix' => 'A',
                'sign_layout' => 2,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('After edit', $head->fresh()->name);
        $this->assertSame('A', $head->fresh()->config['prefix']);
        $this->assertStringContainsString('A/', $head->fresh()->preview());
    }

    private function makeHead(string $name, array $overrides = []): Letterhead
    {
        return Letterhead::forceCreate(array_replace([
            'kind' => Letterhead::KIND_HEAD,
            'name' => $name,
            'pattern' => '{PREFIX}/{YYYY}/{SEQ:4}',
            'config' => ['prefix' => 'Z'],
        ], $overrides));
    }

    private function valid(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Created by test',
            'kind' => Letterhead::KIND_HEAD,
            'size' => Letterhead::SIZE_A4,
            'lang' => 'fa',
            'pattern' => '{PREFIX}/{YYYY}/{SEQ:4}',
            'config.prefix' => 'N',
            'start_at' => 1,
            'sign_layout' => 2,
        ], $overrides);
    }

    private function create(array $data): Testable
    {
        return Livewire::actingAs($this->developer())->test(CreateLetterhead::class)->fillForm($data)->call('create');
    }

    public function test_create_page_stores_the_numbering_pattern_prefix_and_start_number(): void
    {
        $this->create($this->valid(['start_at' => 25]))->assertHasNoFormErrors();

        $created = Letterhead::query()->where('name', 'Created by test')->firstOrFail();
        $this->assertSame('{PREFIX}/{YYYY}/{SEQ:4}', $created->pattern);
        $this->assertSame('N', $created->config['prefix']);
        $this->assertSame(25, (int) $created->start_at);
        $this->assertStringStartsWith('N/', $created->preview());
    }

    public function test_the_hidden_sign_layout_still_saves_a_valid_value_when_the_form_never_fills_it(): void
    {
        $data = $this->valid(['name' => 'Hidden layout']);
        unset($data['sign_layout']);

        $this->create($data)->assertHasNoFormErrors();

        $created = Letterhead::query()->where('name', 'Hidden layout')->firstOrFail();
        $this->assertSame(2, $created->sign_layout);
    }

    public function test_pattern_must_contain_a_sequence_and_only_known_tokens(): void
    {
        $this->create($this->valid(['name' => 'No sequence', 'pattern' => '{PREFIX}/{YYYY}']))->assertHasFormErrors(['pattern']);
        $this->create($this->valid(['name' => 'Bad token', 'pattern' => '{PREFIX}/{BOGUS}/{SEQ}']))->assertHasFormErrors(['pattern']);
        $this->create($this->valid(['name' => 'Too long', 'pattern' => str_repeat('x', 121) . '{SEQ}']))->assertHasFormErrors(['pattern']);

        $this->assertFalse(Letterhead::query()->whereIn('name', ['No sequence', 'Bad token', 'Too long'])->exists());
    }

    public function test_kind_size_language_and_start_number_values_are_enforced(): void
    {
        foreach (['kind' => 'other', 'size' => 'a3', 'lang' => 'de', 'start_at' => 0] as $field => $value) {
            $this->create($this->valid(['name' => "Bad {$field}", $field => $value]))->assertHasFormErrors([$field]);
        }

        $this->assertFalse(Letterhead::query()->where('name', 'like', 'Bad %')->exists());
    }

    public function test_a_child_letterhead_shares_the_parent_pattern_and_cannot_nest_deeper(): void
    {
        $root = $this->makeHead('Root head');

        $this->create($this->valid(['name' => 'Child head', 'parent_id' => $root->id, 'pattern' => null, 'start_at' => null]))->assertHasNoFormErrors();
        $child = Letterhead::query()->where('name', 'Child head')->firstOrFail();
        $this->assertSame((int) $root->id, (int) $child->parent_id);

        $this->create($this->valid(['name' => 'Grandchild head', 'parent_id' => $child->id, 'pattern' => null, 'start_at' => null]));
        $this->assertFalse(Letterhead::query()->where('name', 'Grandchild head')->exists());
    }

    public function test_a_letterhead_cannot_borrow_from_one_that_already_borrows(): void
    {
        $source = $this->makeHead('Image source');
        $borrower = $this->makeHead('First borrower', ['config' => ['prefix' => 'B', 'header_from' => $source->id]]);

        $this->create($this->valid(['name' => 'Chain borrower', 'config.prefix' => 'C', 'config.header_from' => $borrower->id]));

        $this->assertFalse(Letterhead::query()->where('name', 'Chain borrower')->exists());
        $this->assertSame((int) $source->id, (int) $borrower->fresh()->config['header_from']);
    }

    public function test_a_borrowed_letterhead_cannot_be_deactivated_or_deleted(): void
    {
        $source = $this->makeHead('Borrowed source');
        $this->makeHead('Borrowing head', ['config' => ['prefix' => 'B', 'header_from' => $source->id]]);
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(ListLetterheads::class)->callTableAction('delete', $source);
        $this->assertNotNull(Letterhead::find($source->id));

        Livewire::actingAs($admin)->test(EditLetterhead::class, ['record' => $source->getRouteKey()])
            ->callAction('delete');
        $this->assertNotNull(Letterhead::find($source->id));

        Livewire::actingAs($admin)->test(EditLetterhead::class, ['record' => $source->getRouteKey()])
            ->fillForm($this->valid(['name' => 'Borrowed source', 'kind' => Letterhead::KIND_REGISTRY]))
            ->call('save');
        $this->assertSame(Letterhead::KIND_HEAD, $source->fresh()->kind);
    }

    public function test_delete_is_blocked_by_children_and_letters_but_allowed_when_unused(): void
    {
        $parent = $this->makeHead('Parent head');
        $this->makeHead('Child of parent', ['parent_id' => $parent->id, 'pattern' => null]);
        $used = $this->makeHead('Used head');
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        Correspondence::forceCreate([
            'user_id' => $owner->id, 'direction' => Correspondence::DIRECTION_OUT, 'type' => 'letter', 'status' => Correspondence::STATUS_DRAFT,
            'letterhead_id' => $used->id, 'subject' => 'x', 'spec' => ['blocks' => []], 'parties' => [], 'recipients' => [], 'events' => [],
        ]);
        $free = $this->makeHead('Free head');
        $page = Livewire::actingAs($this->developer())->test(ListLetterheads::class);

        $page->callTableAction('delete', $parent);
        $page->callTableAction('delete', $used);
        $page->callTableAction('delete', $free);

        $this->assertNotNull(Letterhead::find($parent->id));
        $this->assertNotNull(Letterhead::find($used->id));
        $this->assertNull(Letterhead::find($free->id));
    }

    public function test_the_font_selects_offer_exactly_the_registry_fonts_of_their_script(): void
    {
        Livewire::actingAs($this->developer())->test(CreateLetterhead::class)
            ->assertFormFieldExists('style.font', fn (Select $field): bool => $field->getOptions() === CorrespondenceFonts::options('fa'))
            ->assertFormFieldExists('style.latin_font', fn (Select $field): bool => $field->getOptions() === CorrespondenceFonts::options('latin'));

        $this->assertArrayNotHasKey('tinos', CorrespondenceFonts::options('fa'));
        $this->assertArrayNotHasKey('parastoo', CorrespondenceFonts::options('latin'));
    }

    public function test_a_font_outside_the_registry_or_of_the_other_script_is_rejected(): void
    {
        $this->create($this->valid(['name' => 'Good fonts', 'style.font' => 'parastoo', 'style.latin_font' => 'tinos']))->assertHasNoFormErrors();
        $this->assertSame('parastoo', Letterhead::query()->where('name', 'Good fonts')->firstOrFail()->style['font']);

        $this->create($this->valid(['name' => 'Evil font', 'style.font' => 'x;}</style>']))->assertHasFormErrors(['style.font']);
        $this->create($this->valid(['name' => 'Evil latin', 'style.latin_font' => 'x;}</style>']))->assertHasFormErrors(['style.latin_font']);
        $this->create($this->valid(['name' => 'Swapped script', 'style.font' => 'tinos', 'style.latin_font' => 'parastoo']))->assertHasFormErrors(['style.font', 'style.latin_font']);

        $this->assertFalse(Letterhead::query()->whereIn('name', ['Evil font', 'Evil latin', 'Swapped script'])->exists());
    }

    public function test_the_table_filters_by_kind(): void
    {
        $head = $this->makeHead('Kind head');
        $registry = $this->makeHead('Kind registry', ['kind' => Letterhead::KIND_REGISTRY, 'pattern' => '{PREFIX}/{SEQ}']);

        Livewire::actingAs($this->developer())->test(ListLetterheads::class)
            ->filterTable('kind', Letterhead::KIND_REGISTRY)
            ->assertCanSeeTableRecords([$registry])
            ->assertCanNotSeeTableRecords([$head]);
    }
}
