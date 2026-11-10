<?php

namespace Tests\Feature\Models;

use Closure;
use App\Models\Correspondence;
use App\Models\Letterhead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class LetterheadModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Storage::fake('public');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Carbon::setTestNow();
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

    private function makeHead(array $overrides = []): Letterhead
    {
        return Letterhead::forceCreate(array_replace([
            'kind' => Letterhead::KIND_HEAD,
            'name' => 'سربرگ آزمایشی',
            'pattern' => '{PREFIX}/{YYYY}/{DEPT}/{TYPE}/{SEQ:4}',
            'config' => ['prefix' => 'L'],
        ], $overrides));
    }

    private function letter(array $overrides = []): Correspondence
    {
        return Correspondence::forceCreate(array_replace([
            'user_id' => User::factory()->create(['role' => 'user', 'status' => 'active'])->id,
            'direction' => Correspondence::DIRECTION_OUT,
            'type' => 'letter',
            'status' => Correspondence::STATUS_DRAFT,
            'subject' => 'آزمون',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'متن']]],
        ], $overrides));
    }

    private function expectValidation(callable $callback, ?string $field = null): ValidationException
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            if ($field !== null) {
                $this->assertArrayHasKey($field, $exception->errors());
            }

            return $exception;
        }

        $this->fail('Expected a ValidationException.');
    }

    public function test_kind_size_language_layout_and_start_number_values_are_validated(): void
    {
        foreach ([Letterhead::KIND_HEAD, Letterhead::KIND_REGISTRY] as $kind) {
            foreach ([Letterhead::SIZE_A4, Letterhead::SIZE_A5, Letterhead::SIZE_FIT] as $size) {
                foreach (['fa', 'en'] as $lang) {
                    $this->makeHead(['kind' => $kind, 'size' => $size, 'lang' => $lang]);
                }
            }
        }

        foreach ([
            ['kind' => 'unknown', 'field' => 'kind'],
            ['size' => 'letter', 'field' => 'size'],
            ['lang' => 'fr', 'field' => 'lang'],
            ['sign_layout' => 4, 'field' => 'sign_layout'],
            ['start_at' => 0, 'field' => 'start_at'],
        ] as $case) {
            $field = $case['field'];
            unset($case['field']);
            $this->expectValidation(fn () => $this->makeHead($case), $field);
        }
    }

    public function test_the_default_style_uses_the_real_nazanin_and_the_tinos_latin_font(): void
    {
        $style = Letterhead::defaultStyle();

        $this->assertSame('bnazanin', $style['font']);
        $this->assertSame('tinos', $style['latin_font']);
    }

    public function test_style_fonts_are_a_strict_allow_list_per_script_and_null_is_allowed(): void
    {
        foreach ([null, 'parastoo', 'iranyekan', 'nazanin'] as $font) {
            $this->makeHead(['style' => ['font' => $font, 'latin_font' => 'tinos']]);
        }
        $this->makeHead(['style' => ['font' => 'vazirmatn', 'latin_font' => null]]);
        $this->makeHead(['style' => ['size' => 13]]);

        foreach (['font' => 'x;}</style>', 'latin_font' => 'x;}</style>'] as $key => $evil) {
            $this->expectValidation(fn () => $this->makeHead(['style' => [$key => $evil]]), 'style');
        }

        $this->expectValidation(fn () => $this->makeHead(['style' => ['font' => 'tinos']]), 'style');
        $this->expectValidation(fn () => $this->makeHead(['style' => ['latin_font' => 'parastoo']]), 'style');
        $this->expectValidation(fn () => $this->makeHead(['style' => ['font' => 'Comic Sans']]), 'style');
        $this->expectValidation(fn () => $this->makeHead(['style' => ['font' => ['parastoo']]]), 'style');
    }

    public function test_an_existing_letterhead_cannot_be_updated_to_an_unknown_font(): void
    {
        $head = $this->makeHead(['style' => ['font' => 'parastoo', 'latin_font' => 'tinos']]);

        $head->style = ['font' => 'parastoo', 'latin_font' => 'inter'];
        $head->save();
        $this->assertSame('inter', $head->fresh()->style['latin_font']);

        $head->style = ['font' => 'x;}</style>', 'latin_font' => 'inter'];
        $this->expectValidation(fn () => $head->save(), 'style');
        $this->assertSame('parastoo', $head->fresh()->style['font']);
    }

    public function test_parent_must_exist_match_kind_and_have_depth_one(): void
    {
        $root = $this->makeHead();
        $child = $this->makeHead(['parent_id' => $root->id, 'pattern' => null]);
        $this->assertSame($root->id, $child->fresh()->parent_id);

        $registry = $this->makeHead(['kind' => Letterhead::KIND_REGISTRY, 'pattern' => '{SEQ}']);
        $this->expectValidation(fn () => $this->makeHead(['parent_id' => $registry->id]), 'parent_id');
        $this->expectValidation(fn () => $this->makeHead(['parent_id' => 999999]), 'parent_id');

        $self = Letterhead::findOrFail($root->id);
        $self->forceFill(['parent_id' => $root->id]);
        $this->expectValidation(fn () => $self->save(), 'parent_id');

        $grandchild = fn () => $this->makeHead(['parent_id' => $child->id, 'pattern' => null]);
        $this->expectValidation($grandchild, 'parent_id');

        $root = Letterhead::findOrFail($root->id);
        $root->forceFill(['parent_id' => '']);
        $root->save();
        $this->assertNull($root->fresh()->parent_id);

        $child = Letterhead::findOrFail($child->id);
        $child->forceFill(['parent_id' => $registry->id]);
        $this->expectValidation(fn () => $child->save(), 'parent_id');
    }

    public function test_parent_with_children_cannot_be_reparented(): void
    {
        $first = $this->makeHead();
        $second = $this->makeHead();
        $this->makeHead(['parent_id' => $first->id, 'pattern' => null]);

        $first = Letterhead::findOrFail($first->id);
        $first->forceFill(['parent_id' => $second->id]);
        $this->expectValidation(fn () => $first->save(), 'parent_id');
    }

    public function test_borrowing_requires_an_active_unborrowed_head_and_registry_clears_borrow_keys(): void
    {
        $source = $this->makeHead();
        $consumer = $this->makeHead(['config' => ['prefix' => 'C', 'header_from' => $source->id, 'footer_from' => null]]);
        $this->assertSame($source->id, $consumer->fresh()->config['header_from']);

        $inactive = $this->makeHead(['active' => false]);
        $alreadyBorrowing = $this->makeHead(['config' => ['header_from' => $source->id]]);
        $registrySource = $this->makeHead(['kind' => Letterhead::KIND_REGISTRY, 'pattern' => '{SEQ}']);

        foreach ([
            ['config' => ['header_from' => 'not-an-id']],
            ['config' => ['header_from' => 999999]],
            ['config' => ['header_from' => $inactive->id]],
            ['config' => ['header_from' => $alreadyBorrowing->id]],
            ['config' => ['header_from' => $registrySource->id]],
        ] as $case) {
            $this->expectValidation(fn () => $this->makeHead($case), 'config');
        }

        $self = Letterhead::findOrFail($source->id);
        $self->forceFill(['config' => ['header_from' => $source->id]]);
        $this->expectValidation(fn () => $self->save(), 'config');

        $registry = $this->makeHead([
            'kind' => Letterhead::KIND_REGISTRY,
            'pattern' => '{SEQ}',
            'config' => ['header_from' => $source->id, 'footer_from' => $source->id, 'prefix' => 'R'],
        ]);
        $this->assertNull($registry->fresh()->config['header_from']);
        $this->assertNull($registry->fresh()->config['footer_from']);
        $this->assertSame('R', $registry->fresh()->config['prefix']);
    }

    public function test_borrowed_source_cannot_change_kind_or_active_state_or_be_deleted(): void
    {
        $source = $this->makeHead();
        $this->makeHead(['config' => ['header_from' => $source->id]]);

        $source = Letterhead::findOrFail($source->id);
        $source->forceFill(['active' => false]);
        $this->expectValidation(fn () => $source->save(), 'active');

        $source = Letterhead::findOrFail($source->id);
        $source->forceFill(['kind' => Letterhead::KIND_REGISTRY, 'pattern' => '{SEQ}']);
        $this->expectValidation(fn () => $source->save(), 'kind');

        $source = Letterhead::findOrFail($source->id);
        $this->expectValidation(fn () => $source->delete(), 'id');
    }

    public function test_pattern_accepts_supported_forms_and_rejects_bad_tokens_or_missing_root_sequence(): void
    {
        $this->makeHead(['pattern' => '{PREFIX}/{YYYY}/{DEPT}/{SEQ:4}']);
        $this->makeHead(['pattern' => 'PL/{YYYY}/{SEQ:5}']);
        $this->makeHead(['pattern' => '{SEQ}']);

        foreach (['{SEQ:0}', 'PL/{YYYY}/{BAD}/{SEQ}', 'PL/{YYYY}', 'PL/{SEQ'] as $pattern) {
            $this->expectValidation(fn () => $this->makeHead(['pattern' => $pattern]), 'pattern');
        }
    }

    public function test_image_paths_are_confined_and_replaced_images_wait_for_commit(): void
    {
        $disk = Storage::disk('public');
        $disk->put('letterheads/old.png', 'old');
        $disk->put('letterheads/new.png', 'new');
        $head = $this->makeHead(['header_image' => 'letterheads/old.png']);

        $invalid = Letterhead::findOrFail($head->id);
        $invalid->forceFill(['footer_image' => '../private.png']);
        $this->expectValidation(fn () => $invalid->save(), 'footer_image');

        $database = DB::getFacadeRoot();
        $callbacks = [];

        try {
            DB::shouldReceive('afterCommit')->twice()->andReturnUsing(function (Closure $callback) use (&$callbacks): void {
                $callbacks[] = $callback;
            });

            $head = Letterhead::findOrFail($head->id);
            $head->forceFill(['header_image' => 'letterheads/new.png'])->save();
            $this->assertTrue($disk->exists('letterheads/old.png'));
            $this->assertTrue($disk->exists('letterheads/new.png'));
            $this->assertSame('letterheads/new.png', $head->fresh()->header_image);

            $callbacks[0]();
            $disk->assertMissing('letterheads/old.png');
            $this->assertTrue($disk->exists('letterheads/new.png'));

            $head->delete();
            $this->assertTrue($disk->exists('letterheads/new.png'));
            $callbacks[1]();
            $disk->assertMissing('letterheads/new.png');
        } finally {
            DB::swap($database);
        }
    }

    public function test_preview_is_pure_and_last_number_reads_the_root_counter(): void
    {
        $head = $this->makeHead(['pattern' => '{PREFIX}/{YYYY}/{DEPT}/{TYPE}/{SEQ:4}', 'start_at' => 1]);
        $context = ['date' => '1405/01/01', 'dept' => 'hr', 'type' => 'L'];
        $values = Letterhead::numberValues($context, 'L');
        $key = Letterhead::counterKey($head->pattern, $values);
        $head->forceFill(['counters' => [$key => 7]])->save();

        $head = Letterhead::findOrFail($head->id);
        $before = $head->counters;
        $this->assertSame('L/1405/hr/L/0008', $head->preview($context));
        $this->assertSame($before, Letterhead::findOrFail($head->id)->counters);
        $this->assertSame(7, $head->lastNumber());
    }

    public function test_next_number_requires_a_stored_letterhead_inside_a_transaction(): void
    {
        $head = new Letterhead(['name' => 'Transient', 'pattern' => '{SEQ}']);
        $this->expectException(LogicException::class);
        $head->nextNumber([]);
    }

    public function test_stored_letterhead_cannot_allocate_a_number_outside_the_callers_transaction(): void
    {
        $head = $this->makeHead(['pattern' => '{SEQ}']);
        DB::rollBack();

        $blocked = false;

        try {
            $head->nextNumber([]);
        } catch (LogicException) {
            $blocked = true;
        } finally {
            DB::beginTransaction();
        }

        $this->assertTrue($blocked, 'Expected nextNumber to require the caller transaction.');
    }

    public function test_counter_key_tracks_token_values_but_ignores_literal_text_and_sequence_width(): void
    {
        $values = Letterhead::numberValues(['date' => '1405/02/03', 'dept' => 'hr', 'type' => 'L'], 'PL');

        $this->assertSame(
            Letterhead::counterKey('A/{YYYY}/{SEQ:4}', $values),
            Letterhead::counterKey('B/{YYYY}/{SEQ:5}', $values),
        );
        $this->assertNotSame(
            Letterhead::counterKey('{YYYY}/{SEQ}', $values),
            Letterhead::counterKey('{YYYY}/{DEPT}/{SEQ}', $values),
        );
        $this->assertNotSame(
            Letterhead::counterKey('{YYYY}/{DEPT}/{SEQ}', $values),
            Letterhead::counterKey('{YYYY}/{SEQ}', $values),
        );
    }

    public function test_sequence_width_start_at_floor_and_root_counter_are_applied(): void
    {
        $head = $this->makeHead(['pattern' => 'PL/{SEQ}', 'start_at' => 5]);
        $plain = $head->nextNumber(['type' => 'L']);
        $this->assertSame('PL/5', $plain['number']);
        $this->assertSame(5, $plain['seq']);

        $padded = $this->makeHead(['pattern' => 'PL/{SEQ:5}']);
        $this->assertSame('PL/00001', $padded->preview());

        $root = $this->makeHead(['pattern' => 'ROOT/{SEQ}', 'start_at' => 1]);
        $child = $this->makeHead(['parent_id' => $root->id, 'pattern' => null]);
        $this->assertSame('ROOT/1', $child->nextNumber(['type' => 'L'])['number']);
        $this->assertSame(1, $root->fresh()->counters['-']);
        $this->assertNull($child->fresh()->counters);
    }

    public function test_next_number_uses_profile_context_type_code_and_never_emits_more_than_64_characters(): void
    {
        $head = $this->makeHead(['pattern' => '{PREFIX}/{YYYY}/{DEPT}/{TYPE}/{SEQ}']);
        $number = $head->nextNumber(['date' => '1405/01/01', 'dept' => 'hr-x', 'type' => 'C', 'prefix' => 'PL']);
        $this->assertSame('PL/1405/hr-x/C/1', $number['number']);

        $tooLong = $this->makeHead(['pattern' => str_repeat('X', 65) . '{SEQ}']);
        $this->expectValidation(fn () => $tooLong->nextNumber(['type' => 'L']), 'letterhead_id');
    }

    public function test_number_collision_skips_existing_soft_deleted_correspondence_numbers(): void
    {
        $head = $this->makeHead(['pattern' => '{PREFIX}{SEQ}']);
        $prefix = 'T' . $head->id;
        $old = $this->letter(['number' => $prefix . '1', 'status' => Correspondence::STATUS_VOID]);
        $old->delete();

        $next = $head->nextNumber(['type' => 'L', 'prefix' => $prefix]);

        $this->assertSame($prefix . '2', $next['number']);
        $this->assertSame(2, $next['seq']);
    }

    public function test_counter_updates_roll_back_with_the_callers_transaction(): void
    {
        $head = $this->makeHead(['pattern' => '{SEQ}']);
        $initial = $head->fresh()->counters;

        DB::beginTransaction();
        $this->assertSame('1', $head->nextNumber(['type' => 'L'])['number']);
        DB::rollBack();

        $this->assertSame($initial, Letterhead::findOrFail($head->id)->counters);
    }

    public function test_nowruz_counter_scope_changes_at_tehran_midnight(): void
    {
        $head = $this->makeHead(['pattern' => '{YYYY}/{MM}/{SEQ}']);
        Carbon::setTestNow(Carbon::parse('2025-03-20 20:29:59', 'UTC'));
        $this->assertSame('1403/12/1', $head->preview());

        Carbon::setTestNow(Carbon::parse('2025-03-20 20:30:00', 'UTC'));
        $this->assertSame('1404/01/1', $head->preview());
    }

    public function test_registry_letterheads_can_allocate_their_own_sequence(): void
    {
        $registry = $this->makeHead(['kind' => Letterhead::KIND_REGISTRY, 'pattern' => 'IN/{YYYY}/{SEQ:3}']);

        $number = $registry->nextNumber(['date' => '1405/01/01', 'type' => 'IN']);

        $this->assertSame('IN/1405/001', $number['number']);
        $this->assertSame(1, $number['seq']);
    }

    public function test_delete_is_blocked_by_children_and_letters_including_trashed_letters(): void
    {
        $parent = $this->makeHead();
        $this->makeHead(['parent_id' => $parent->id, 'pattern' => null]);
        $parent = Letterhead::findOrFail($parent->id);
        $this->expectValidation(fn () => $parent->delete(), 'id');

        $head = $this->makeHead();
        $letter = Correspondence::forceCreate([
            'user_id' => User::factory()->create(['role' => 'user'])->id,
            'letterhead_id' => $head->id,
            'type' => 'letter',
            'subject' => 'letter uses this head',
            'spec' => ['blocks' => [['type' => 'text', 'text' => 'body']]],
        ]);
        $letter->delete();

        $head = Letterhead::findOrFail($head->id);
        $this->expectValidation(fn () => $head->delete(), 'id');
    }
}
