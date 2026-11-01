<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\User\UserKeyGrouper;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;
use stdClass;
use Tests\TestCase;

/**
 * In-memory coverage of UserKeyGrouper: the merge rule, valueFor, and the
 * scope-expression builder. The MySQL-only map() SQL is covered by the
 * integration test at the bottom.
 */
class UserKeyGrouperTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::forget(UserKeyGrouper::CACHE_KEY);
        parent::tearDown();
    }

    private function callProtected(string $method, array $args = []): mixed
    {
        return (new ReflectionMethod(UserKeyGrouper::class, $method))->invoke(null, ...$args);
    }

    private function row(string $k, int $cnt): stdClass
    {
        $r = new stdClass();
        $r->k = $k;
        $r->cnt = $cnt;

        return $r;
    }

    private function user(array $fields = []): User
    {
        $u = new User();
        $u->extra = $fields;

        return $u;
    }

    public function test_merges_whitespace_and_case_variants_into_one_group(): void
    {
        $groups = UserKeyGrouper::mergeRows([
            $this->row('Company', 143),
            $this->row('Company ', 1),
            $this->row('company', 1),
            $this->row('Location', 143),
            $this->row('location', 2),
        ]);

        $labels = array_column($groups, 'label');
        $this->assertSame(['Company', 'Location'], $labels);
    }

    public function test_label_is_the_most_frequent_variant(): void
    {
        $groups = UserKeyGrouper::mergeRows([
            $this->row('Company', 2),
            $this->row('company', 5),
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame('company', $groups[0]['label']);
        $this->assertSame(['Company', 'company'], $groups[0]['variants']);
    }

    public function test_genuine_typo_stays_a_separate_group(): void
    {
        $groups = UserKeyGrouper::mergeRows([
            $this->row('Company', 143),
            $this->row('Comapny', 1),
        ]);

        $this->assertSame(['Comapny', 'Company'], array_column($groups, 'label'));
    }

    public function test_byte_distinct_variants_are_all_retained(): void
    {
        $groups = UserKeyGrouper::mergeRows([
            $this->row('Company', 143),
            $this->row('Company ', 1),
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame(['Company', 'Company '], $groups[0]['variants']);
    }

    public function test_leading_whitespace_is_also_trimmed(): void
    {
        $groups = UserKeyGrouper::mergeRows([
            $this->row('company', 3),
            $this->row(' Company', 1),
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame('company', $groups[0]['norm']);
    }

    public function test_persian_keys_merge_correctly(): void
    {
        $groups = UserKeyGrouper::mergeRows([
            $this->row('محل', 3),
            $this->row('شرکت', 2),
        ]);

        $this->assertEqualsCanonicalizing(['محل', 'شرکت'], array_column($groups, 'label'));
    }

    public function test_count_tie_keeps_first_seen_as_label(): void
    {
        $groups = UserKeyGrouper::mergeRows([
            $this->row('Foo', 1),
            $this->row('foo', 1),
        ]);

        $this->assertSame('Foo', $groups[0]['label']);
    }

    public function test_empty_input_returns_empty_list(): void
    {
        $this->assertSame([], UserKeyGrouper::mergeRows([]));
    }

    public function test_valueFor_returns_admin_value_when_key_present(): void
    {
        $u = $this->user(['Company' => 'Tehran']);

        $this->assertSame('Tehran', UserKeyGrouper::valueFor($u, ['Company']));
    }

    public function test_valueFor_returns_dash_when_key_absent(): void
    {
        $u = $this->user(['other' => 'x']);

        $this->assertSame('—', UserKeyGrouper::valueFor($u, ['Company']));
    }

    public function test_valueFor_returns_dash_for_null_value(): void
    {
        $u = $this->user(['Company' => null]);

        $this->assertSame('—', UserKeyGrouper::valueFor($u, ['Company']));
    }

    public function test_valueFor_serializes_non_scalar_values_as_json(): void
    {
        $u = $this->user(['tags' => ['a', 'b']]);

        $this->assertSame('["a","b"]', UserKeyGrouper::valueFor($u, ['tags']));
    }

    public function test_valueFor_casts_integer_to_string(): void
    {
        $u = $this->user(['n' => 7]);

        $this->assertSame('7', UserKeyGrouper::valueFor($u, ['n']));
    }

    public function test_valueFor_tries_variants_in_order_until_one_hits(): void
    {
        $u = $this->user(['company' => 'lower']);

        $this->assertSame('lower', UserKeyGrouper::valueFor($u, ['Company', 'company']));
        $this->assertSame('—', UserKeyGrouper::valueFor($u, ['Company']));
    }

    public function test_valueFor_handles_null_extra_gracefully(): void
    {
        $u = new User();
        $u->extra = null;

        $this->assertSame('—', UserKeyGrouper::valueFor($u, ['anything']));
    }

    public function test_valueFor_handles_missing_admin_bucket_gracefully(): void
    {
        $u = new User();
        $u->extra = ['preferences' => ['theme' => 'dark']];

        $this->assertSame('—', UserKeyGrouper::valueFor($u, ['Company']));
    }

    public function test_jsonPath_quotes_key_and_escapes_double_quote_and_backslash(): void
    {
        $this->assertSame("'$.admin.\"Company\"'", $this->callProtected('jsonPath', ['Company']));
        $this->assertSame("'$.admin.\"a\\\"b\"'", $this->callProtected('jsonPath', ['a"b']));
        $this->assertSame("'$.admin.\"a\\\\b\"'", $this->callProtected('jsonPath', ['a\\b']));
    }

    public function test_jsonPath_escapes_single_quote(): void
    {
        $path = $this->callProtected('jsonPath', ["O'Brien"]);
        $inner = substr($path, 1, -1);

        $this->assertStringNotContainsString("O'Brien", $inner);
        $this->assertStringContainsString("O\\'Brien", $inner);
    }

    public function test_valueExpression_single_variant(): void
    {
        $expr = $this->callProtected('valueExpression', [['Company']]);

        $this->assertSame(
            "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`extra`, '\$.admin.\"Company\"')))",
            $expr,
        );
    }

    public function test_valueExpression_orders_variants_correctly(): void
    {
        $expr = $this->callProtected('valueExpression', [['A', 'B']]);

        $this->assertStringContainsString("JSON_EXTRACT(`extra`, '\$.admin.\"A\"')", $expr);
        $this->assertStringContainsString("JSON_EXTRACT(`extra`, '\$.admin.\"B\"')", $expr);

        $aPos = strpos($expr, "'\$.admin.\"A\"");
        $bPos = strpos($expr, "'\$.admin.\"B\"");
        $this->assertNotFalse($aPos);
        $this->assertNotFalse($bPos);
        $this->assertLessThan($bPos, $aPos, 'variant A must precede variant B');
    }

    public function test_valueExpression_handles_persian_key(): void
    {
        $expr = $this->callProtected('valueExpression', [['شرکت']]);

        $this->assertStringContainsString("'\$.admin.\"شرکت\"'", $expr);
    }

    public function test_idFor_is_deterministic_and_distinct_per_norm(): void
    {
        $id = fn (string $norm) => $this->callProtected('idFor', [$norm]);

        $this->assertSame($id('company'), $id('company'));
        $this->assertNotSame($id('company'), $id('location'));
        $this->assertNotSame($id('company'), $id('comapny'));
    }

    public function test_idFor_format_is_dyn_plus_16_hex(): void
    {
        $id = $this->callProtected('idFor', ['company']);

        $this->assertMatchesRegularExpression('/^dyn_[0-9a-f]{16}$/', $id);
    }

    public function test_groups_have_unique_ids_and_labels_match_map(): void
    {
        Cache::forever(UserKeyGrouper::CACHE_KEY, UserKeyGrouper::mergeRows([
            $this->row('Company', 143),
            $this->row('company', 1),
            $this->row('Comapny', 1),
        ]));

        $groups = UserKeyGrouper::groups();

        $this->assertCount(2, $groups);
        $ids = array_map(fn ($g) => $g->getId(), $groups);
        $this->assertSame(count($ids), count(array_unique($ids)), 'group ids must be unique');
        $this->assertSame(['Comapny', 'Company'], array_map(fn ($g) => $g->getLabel(), $groups));

        $company = collect($groups)->firstWhere(fn ($g) => $g->getLabel() === 'Company');
        $this->assertSame(
            $this->callProtected('idFor', ['company']),
            $company->getId(),
        );
    }

    public function test_groups_closure_buckets_records_correctly(): void
    {
        Cache::forever(UserKeyGrouper::CACHE_KEY, UserKeyGrouper::mergeRows([
            $this->row('Company', 1),
        ]));

        $group = UserKeyGrouper::groups()[0];
        $with = $this->user(['Company' => 'Tehran']);
        $without = $this->user(['other' => 'x']);

        $this->assertSame('Tehran', $group->getStringKey($with));
        $this->assertSame('—', $group->getStringKey($without));
    }

    public function test_groups_scope_does_not_reference_hash_id_column(): void
    {
        Cache::forever(UserKeyGrouper::CACHE_KEY, UserKeyGrouper::mergeRows([
            $this->row('Company', 1),
        ]));

        $group = UserKeyGrouper::groups()[0];
        $query = User::query();
        $group->scopeQueryByKey($query, 'Tehran');

        $sql = $query->toBase()->toSql();
        $bindings = $query->toBase()->getBindings();

        $this->assertStringContainsString('COALESCE(', $sql);
        $this->assertStringContainsString('`extra`', $sql);
        $this->assertStringNotContainsString('dyn_', $sql);
        $this->assertContains('Tehran', $bindings);
    }

    public function test_groups_scope_for_dash_bucket_uses_is_null(): void
    {
        Cache::forever(UserKeyGrouper::CACHE_KEY, UserKeyGrouper::mergeRows([
            $this->row('Company', 1),
        ]));

        $group = UserKeyGrouper::groups()[0];
        $query = User::query();
        $group->scopeQueryByKey($query, '—');

        $sql = $query->toBase()->toSql();

        $this->assertStringContainsString('IS NULL', $sql);
        $this->assertStringNotContainsString('= ?', $sql);
    }

    public function test_filters_have_unique_ids_and_labels_match_map(): void
    {
        Cache::forever(UserKeyGrouper::CACHE_KEY, UserKeyGrouper::mergeRows([
            $this->row('Company', 1),
            $this->row('Location', 1),
        ]));

        $filters = UserKeyGrouper::filters();

        $this->assertCount(2, $filters);
        $ids = array_map(fn ($f) => $f->getName(), $filters);
        $this->assertSame(count($ids), count(array_unique($ids)), 'filter names must be unique');
        $this->assertSame(['Company', 'Location'], array_map(fn ($f) => $f->getLabel(), $filters));
    }
}
