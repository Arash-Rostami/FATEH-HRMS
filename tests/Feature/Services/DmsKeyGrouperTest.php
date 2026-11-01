<?php

namespace Tests\Feature\Services;

use App\Models\DMS;
use App\Services\Dms\DmsKeyGrouper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use stdClass;
use Tests\TestCase;

/**
 * In-memory coverage of DmsKeyGrouper: the merge rule, byte-exact valueFor, and the
 * scope-expression builder (the "select all in group" crash fix). The MySQL-only keys()
 * SQL is covered by the skipped integration test at the bottom.
 */
class DmsKeyGrouperTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::forget(DmsKeyGrouper::CACHE_KEY);
        parent::tearDown();
    }

    private function callProtected(string $method, array $args = []): mixed
    {
        return (new ReflectionMethod(DmsKeyGrouper::class, $method))->invoke(null, ...$args);
    }

    private function row(string $k, int $cnt): stdClass
    {
        $r = new stdClass();
        $r->k = $k;
        $r->cnt = $cnt;

        return $r;
    }

    private function dms(array $extra = [], array $tags = []): DMS
    {
        $d = new DMS();
        $d->extra = $extra;
        $d->tags = $tags;

        return $d;
    }

    public function test_merges_whitespace_and_case_variants_into_one_group(): void
    {
        $groups = DmsKeyGrouper::mergeRows([
            $this->row('Category', 143),
            $this->row('Category ', 1),   // trailing space
            $this->row('category', 1),     // lowercase
            $this->row('Type', 143),
            $this->row('type', 2),
        ]);

        $labels = array_column($groups, 'label');
        $this->assertSame(['Category', 'Type'], $labels);
    }

    public function test_label_is_the_most_frequent_variant(): void
    {
        $groups = DmsKeyGrouper::mergeRows([
            $this->row('Category', 2),
            $this->row('category', 5),     // lowercase wins on count
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame('category', $groups[0]['label']); // most frequent, not prettiest
        $this->assertSame('category', $groups[0]['norm']);
        $this->assertSame(['Category', 'category'], $groups[0]['variants']);
    }

    public function test_genuine_typo_stays_a_separate_group(): void
    {
        $groups = DmsKeyGrouper::mergeRows([
            $this->row('Category', 143),
            $this->row('Catergory', 1),   // different spelling — NOT merged
        ]);

        // strcmp byte-order: 'Category' < 'Catergory' (differs at 'o' vs 'r').
        $this->assertSame(['Category', 'Catergory'], array_column($groups, 'label'));
    }

    public function test_byte_distinct_variants_are_all_retained_not_collapsed_by_merge(): void
    {
        $groups = DmsKeyGrouper::mergeRows([
            $this->row('Category', 143),
            $this->row('Category ', 1),
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame(['Category', 'Category '], $groups[0]['variants']);
    }

    public function test_leading_whitespace_is_also_trimmed_for_normalize(): void
    {
        $groups = DmsKeyGrouper::mergeRows([
            $this->row('category', 3),
            $this->row(' Category', 1),   // leading space → same norm
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame('category', $groups[0]['norm']);
    }

    public function test_persian_keys_merge_correctly(): void
    {
        $groups = DmsKeyGrouper::mergeRows([
            $this->row('روش', 3),
            $this->row('تاریخ', 2),
        ]);

        // Both present (sorted by byte order; order isn't load-bearing here).
        $this->assertEqualsCanonicalizing(['روش', 'تاریخ'], array_column($groups, 'label'));
    }

    public function test_count_tie_keeps_first_seen_as_label(): void
    {
        $groups = DmsKeyGrouper::mergeRows([
            $this->row('Foo', 1),
            $this->row('foo', 1),     // tie — first seen wins, strict >
        ]);

        $this->assertSame('Foo', $groups[0]['label']);
    }

    public function test_empty_input_returns_empty_list(): void
    {
        $this->assertSame([], DmsKeyGrouper::mergeRows([]));
    }

    public function test_valueFor_returns_extra_value_when_key_present(): void
    {
        $d = $this->dms(extra: ['Category' => 'کالا']);

        $this->assertSame('کالا', DmsKeyGrouper::valueFor($d, ['Category']));
    }

    public function test_valueFor_falls_back_to_tags_when_missing_from_extra(): void
    {
        $d = $this->dms(extra: [], tags: ['type' => 'test1']);

        $this->assertSame('test1', DmsKeyGrouper::valueFor($d, ['type']));
    }

    public function test_valueFor_extra_wins_over_tags(): void
    {
        $d = $this->dms(extra: ['type' => 'fromExtra'], tags: ['type' => 'fromTags']);

        $this->assertSame('fromExtra', DmsKeyGrouper::valueFor($d, ['type']));
    }

    public function test_valueFor_returns_dash_when_key_absent_everywhere(): void
    {
        $d = $this->dms(extra: ['other' => 'x'], tags: []);

        $this->assertSame('—', DmsKeyGrouper::valueFor($d, ['Category']));
    }

    public function test_valueFor_returns_dash_for_null_value(): void
    {
        $d = $this->dms(extra: ['Parent Document' => null]);

        $this->assertSame('—', DmsKeyGrouper::valueFor($d, ['Parent Document']));
    }

    public function test_valueFor_serializes_non_scalar_values_as_json_unicode_safe(): void
    {
        $d = $this->dms(extra: ['list' => ['a', 'روش']]);

        $this->assertSame('["a","روش"]', DmsKeyGrouper::valueFor($d, ['list']));
    }

    public function test_valueFor_casts_integer_to_string(): void
    {
        $d = $this->dms(extra: ['n' => 7]);

        $this->assertSame('7', DmsKeyGrouper::valueFor($d, ['n']));
    }

    public function test_valueFor_is_byte_exact_trailing_space_is_a_different_key(): void
    {
        $d = $this->dms(extra: ['Category ' => 'trailing']); // key HAS trailing space

        $this->assertSame('—', DmsKeyGrouper::valueFor($d, ['Category']));   // exact key absent
        $this->assertSame('trailing', DmsKeyGrouper::valueFor($d, ['Category ']));
    }

    public function test_valueFor_tries_variants_in_order_until_one_hits(): void
    {
        // record carries only the 2nd variant
        $d = $this->dms(extra: ['category' => 'lower']);

        $this->assertSame('lower', DmsKeyGrouper::valueFor($d, ['Category', 'category']));
        $this->assertSame('—', DmsKeyGrouper::valueFor($d, ['Category']));
    }

    public function test_valueFor_handles_null_cast_array_gracefully(): void
    {
        $d = $this->dms();
        $d->extra = null; // nullable column can be null
        $d->tags = null;

        $this->assertSame('—', DmsKeyGrouper::valueFor($d, ['anything']));
    }

    public function test_jsonPath_quotes_key_and_escapes_double_quote_and_backslash(): void
    {
        $this->assertSame("'$.\"Category\"'", $this->callProtected('jsonPath', ['Category']));
        $this->assertSame("'$.\"a\\\"b\"'", $this->callProtected('jsonPath', ['a"b']));
        $this->assertSame("'$.\"a\\\\b\"'", $this->callProtected('jsonPath', ['a\\b']));
    }

    public function test_jsonPath_escapes_single_quote_so_it_cannot_break_the_sql_literal(): void
    {
        // The path is interpolated into a raw WHERE (see scopeQueryByKeyUsing). A key with a
        // single quote must be backslash-escaped, else it closes the wrapping SQL string literal
        // and allows a crash / SQL injection (the key is user-controlled via the KeyValue field).
        $path = $this->callProtected('jsonPath', ["O'Brien"]);
        $inner = substr($path, 1, -1); // strip the wrapping single quotes

        $this->assertStringNotContainsString("O'Brien", $inner, 'unescaped apostrophe would close the SQL string literal');
        $this->assertStringContainsString("O\\'Brien", $inner, 'apostrophe must be backslash-escaped');
    }

    public function test_valueExpression_single_variant_checks_extra_before_tags(): void
    {
        $expr = $this->callProtected('valueExpression', [['Category']]);

        $this->assertSame(
            "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`extra`, '\$.\"Category\"')), JSON_UNQUOTE(JSON_EXTRACT(`tags`, '\$.\"Category\"')))",
            $expr,
        );
    }

    public function test_valueExpression_orders_parts_as_variant_then_column(): void
    {
        $expr = $this->callProtected('valueExpression', [['A', 'B']]);
        // A-extra, A-tags, B-extra, B-tags
        $this->assertStringContainsString("JSON_EXTRACT(`extra`, '\$.\"A\"')", $expr);
        $this->assertStringContainsString("JSON_EXTRACT(`tags`, '\$.\"A\"')", $expr);
        $this->assertStringContainsString("JSON_EXTRACT(`extra`, '\$.\"B\"')", $expr);
        $this->assertStringContainsString("JSON_EXTRACT(`tags`, '\$.\"B\"')", $expr);

        $aPos = strpos($expr, "'\$.\"A\"");
        $bPos = strpos($expr, "'\$.\"B\"");
        $this->assertNotFalse($aPos);
        $this->assertNotFalse($bPos);
        $this->assertLessThan($bPos, $aPos, 'variant A must precede variant B');
    }

    public function test_valueExpression_handles_persian_key(): void
    {
        $expr = $this->callProtected('valueExpression', [['تاریخ']]);

        $this->assertStringContainsString("'\$.\"تاریخ\"'", $expr);
    }

    public function test_idFor_is_deterministic_and_distinct_per_norm(): void
    {
        // idFor hashes the norm it is given (the merge step already normalized it); it does
        // NOT re-normalize. Same norm ⇒ same id; different norms ⇒ different ids.
        $id = fn (string $norm) => $this->callProtected('idFor', [$norm]);

        $this->assertSame($id('category'), $id('category'), 'deterministic for identical norm');
        $this->assertNotSame($id('category'), $id('type'), 'distinct norms yield distinct ids');
        $this->assertNotSame($id('category'), $id('catergory'), 'typo norm differs from canonical');
    }

    public function test_idFor_format_is_dyn_plus_16_hex(): void
    {
        $id = $this->callProtected('idFor', ['category']);

        $this->assertMatchesRegularExpression('/^dyn_[0-9a-f]{16}$/', $id);
    }

    public function test_groups_have_unique_ids_and_labels_match_labels_of_map(): void
    {
        // Override the cache so groups() builds from synthetic data without touching MySQL.
        Cache::forever(DmsKeyGrouper::CACHE_KEY, DmsKeyGrouper::mergeRows([
            $this->row('Category', 143),
            $this->row('category', 1),
            $this->row('Catergory', 1),
        ]));

        $groups = DmsKeyGrouper::groups();

        $this->assertCount(2, $groups);
        $ids = array_map(fn ($g) => $g->getId(), $groups);
        $this->assertSame(count($ids), count(array_unique($ids)), 'group ids must be unique');
        $this->assertSame(['Category', 'Catergory'], array_map(fn ($g) => $g->getLabel(), $groups));

        // The merged Category group must use ONE id regardless of case variants.
        $category = collect($groups)->firstWhere(fn ($g) => $g->getLabel() === 'Category');
        $this->assertSame(
            $this->callProtected('idFor', ['category']),
            $category->getId(),
        );
    }

    public function test_groups_closure_buckets_records_correctly(): void
    {
        Cache::forever(DmsKeyGrouper::CACHE_KEY, DmsKeyGrouper::mergeRows([
            $this->row('Category', 1),
        ]));

        $group = DmsKeyGrouper::groups()[0];
        $with = $this->dms(extra: ['Category' => 'کالا']);
        $without = $this->dms(extra: ['other' => 'x']);

        $this->assertSame('کالا', $group->getStringKey($with));
        $this->assertSame('—', $group->getStringKey($without));
    }

    public function test_groups_scope_query_does_not_reference_the_hash_id_column(): void
    {
        // The crash was `WHERE dyn_xxx = 'value'`. scopeQueryByKeyUsing must instead build
        // a raw COALESCE on the real JSON columns — never the opaque id.
        Cache::forever(DmsKeyGrouper::CACHE_KEY, DmsKeyGrouper::mergeRows([
            $this->row('Category', 1),
        ]));

        $group = DmsKeyGrouper::groups()[0];
        $query = DMS::query();
        $group->scopeQueryByKey($query, 'کالا');

        $sql = $query->toBase()->toSql();
        $bindings = $query->toBase()->getBindings();

        $this->assertStringContainsString('COALESCE(', $sql);
        $this->assertStringContainsString('`extra`', $sql);
        $this->assertStringContainsString('`tags`', $sql);
        $this->assertStringNotContainsString('dyn_', $sql, 'scope must not reference the hash-id column');
        $this->assertContains('کالا', $bindings);
    }

    public function test_groups_scope_for_dash_bucket_uses_is_null(): void
    {
        Cache::forever(DmsKeyGrouper::CACHE_KEY, DmsKeyGrouper::mergeRows([
            $this->row('Category', 1),
        ]));

        $group = DmsKeyGrouper::groups()[0];
        $query = DMS::query();
        $group->scopeQueryByKey($query, '—');

        $sql = $query->toBase()->toSql();

        $this->assertStringContainsString('IS NULL', $sql);
        $this->assertStringNotContainsString('= ?', $sql);
    }

    public function test_integration_mysql_extraction_returns_merged_keys(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('DmsKeyGrouper SQL extraction is MySQL-only; run with a MySQL connection.');
        }

        Cache::forget(DmsKeyGrouper::CACHE_KEY);
        $keys = DmsKeyGrouper::keys();

        $this->assertNotEmpty($keys, 'expected at least some keys in the dev DMS data');
        $this->assertSame(count($keys), count(array_unique($keys)), 'merged keys must be unique');

        foreach (DmsKeyGrouper::map() as $g) {
            $this->assertNotSame('—', $g['label'], 'a real key should never be the dash sentinel');
        }
    }
}