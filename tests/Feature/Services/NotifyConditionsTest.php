<?php

namespace Tests\Feature\Services;

use App\Casts\IntegerList;
use App\Enums\NotifyEvent;
use App\Jobs\SendNotifyRule;
use App\Models\Authority;
use App\Models\Department;
use App\Models\EnergyTest;
use App\Models\Feed;
use App\Models\Link;
use App\Models\NotifyRule;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ReleaseRequest;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Notify\NotifyConditions;
use App\Services\Notify\NotifyRegistry;
use App\Services\Notify\NotifyService;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NotifyConditionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        app()->setLocale('fa');
        Cache::forget(NotifyService::CACHE_KEY);
        Cache::forget('notify:trackables');
        $this->forgetLabels();
        Bus::fake();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::forget(NotifyService::CACHE_KEY);
        Cache::forget('notify:trackables');
        $this->forgetLabels();
        parent::tearDown();
    }

    private function forgetLabels(): void
    {
        foreach ([Ticket::class, Task::class, Project::class, Authority::class, EnergyTest::class, Profile::class, Feed::class, Post::class, Photo::class] as $class) {
            Cache::forget('notify:field-labels:'.md5($class));
        }
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

    private function record(array $attributes = []): Model
    {
        $model = new class extends Model {
            use \App\Models\Concerns\HasNotifyEvents;

            protected $fillable = ['title', 'amount', 'score', 'ratio', 'due', 'born', 'active', 'tags', 'ids', 'kind', 'secret', 'pass', 'meta'];

            protected $hidden = ['pass'];

            protected $casts = [
                'score' => 'integer',
                'ratio' => 'decimal:2',
                'due' => 'datetime',
                'born' => 'date',
                'active' => 'boolean',
                'tags' => 'array',
                'ids' => IntegerList::class,
                'kind' => NotifyEvent::class,
                'meta' => 'array',
            ];

            public static function notifyExcept(): array
            {
                return ['secret'];
            }

            public static function notifyFields(): array
            {
                return [
                    'amount' => ['low' => 'Low', 'high' => 'High'],
                    'meta.city' => 'text',
                    'meta.when' => 'date',
                    'secret.key' => 'text',
                ];
            }
        };

        return $model->forceFill($attributes);
    }

    private function r(string $field, string $op, mixed $value = null, ?array $values = null): array
    {
        return array_filter(['field' => $field, 'op' => $op, 'value' => $value, 'values' => $values], fn ($v) => $v !== null);
    }

    private function check(Model $model, array $rules, string $match = 'all'): bool
    {
        return NotifyConditions::matches($model, ['match' => $match, 'rules' => $rules]);
    }

    private function assertInvalid(array $rules, string $match = 'all'): void
    {
        try {
            NotifyConditions::validate($this->record()::class, ['match' => $match, 'rules' => $rules]);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('data.conditions.rules', $e->errors());
        }
    }

    public function test_the_catalog_is_fillable_minus_excepted_and_hidden_columns_with_dotted_keys_gated_by_their_root(): void
    {
        $class = $this->record()::class;

        DB::connection()->enableQueryLog();
        $fields = NotifyConditions::fields($class);
        NotifyConditions::fields(EnergyTest::class);
        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();

        $this->assertSame([], $queries);
        $this->assertArrayNotHasKey('secret', $fields);
        $this->assertArrayNotHasKey('pass', $fields);
        $this->assertArrayNotHasKey('secret.key', $fields);
        $this->assertSame('text', $fields['meta.city']);
        $this->assertSame(['low' => 'Low', 'high' => 'High'], $fields['amount']);
        $this->assertArrayNotHasKey('mind_score', NotifyConditions::fields(EnergyTest::class));
        $this->assertNull(NotifyConditions::kind($class, 'secret'));
    }

    public function test_kinds_are_inferred_from_casts(): void
    {
        $class = $this->record()::class;

        $expected = [
            'title' => 'text', 'score' => 'number', 'ratio' => 'number', 'due' => 'date', 'born' => 'date',
            'active' => 'bool', 'tags' => 'list', 'ids' => 'list', 'kind' => NotifyEvent::class,
        ];

        $this->assertNull(NotifyConditions::kind($class, 'meta'));

        foreach ($expected as $field => $kind) {
            $this->assertSame($kind, NotifyConditions::kind($class, $field), $field);
        }

        $this->assertSame(NotifyConditions::OPS['option'], NotifyConditions::operators(NotifyEvent::class));
        $this->assertSame(NotifyConditions::OPS['option'], NotifyConditions::operators(['a' => 'A']));
        $this->assertSame(NotifyConditions::OPS['date'], NotifyConditions::operators('date'));
        $this->assertTrue(NotifyConditions::hasOptions('bool'));
        $this->assertFalse(NotifyConditions::hasOptions('text'));
        $this->assertSame(['1', '0'], array_map('strval', array_keys(NotifyConditions::options($class, 'active'))));
        $this->assertSame(['created', 'updated', 'deleted'], array_map('strval', array_keys(NotifyConditions::options($class, 'kind'))));
        $this->assertSame([], NotifyConditions::options($class, 'title'));
    }

    public function test_match_all_versus_any_and_a_stale_field_never_matches_everything(): void
    {
        $m = $this->record(['title' => 'Alpha', 'score' => 5]);
        $yes = $this->r('title', 'eq', 'Alpha');
        $no = $this->r('score', 'gt', '9');

        $this->assertTrue($this->check($m, [$yes, $yes]));
        $this->assertFalse($this->check($m, [$yes, $no]));
        $this->assertTrue($this->check($m, [$yes, $no], 'any'));
        $this->assertFalse($this->check($m, [$no, $no], 'any'));

        $stale = $this->r('ghost', 'eq', 'x');
        $this->assertFalse($this->check($m, [$yes, $stale]));
        $this->assertFalse($this->check($m, [$yes, $stale], 'any'));
        $this->assertFalse($this->check($m, [$this->r('secret', 'filled')], 'any'));
    }

    public function test_null_facts_follow_the_truth_table(): void
    {
        $m = $this->record(['kind' => null, 'title' => null]);

        $this->assertFalse($this->check($m, [$this->r('kind', 'in', null, ['created'])]));
        $this->assertTrue($this->check($m, [$this->r('kind', 'not_in', null, ['created'])]));
        $this->assertTrue($this->check($m, [$this->r('kind', 'empty')]));
        $this->assertFalse($this->check($m, [$this->r('kind', 'filled')]));
        $this->assertFalse($this->check($m, [$this->r('title', 'eq', 'x')]));
        $this->assertTrue($this->check($m, [$this->r('title', 'neq', 'x')]));
        $this->assertFalse($this->check($m, [$this->r('title', 'contains', 'x')]));
        $this->assertFalse($this->check($m, [$this->r('meta.city', 'eq', 'x')]));
    }

    public function test_dates_compare_as_jalali_strings_for_carbon_and_string_facts(): void
    {
        $nowruz = Carbon::create(2025, 3, 21, 12, 0, 0, config('app.timezone'));
        $m = $this->record(['due' => $nowruz]);

        $this->assertTrue($this->check($m, [$this->r('due', 'eq', '1404/01/01')]));
        $this->assertTrue($this->check($m, [$this->r('due', 'eq', '۱۴۰۴/۰۱/۰۱')]));
        $this->assertTrue($this->check($m, [$this->r('due', 'before', '1404/02/01')]));
        $this->assertFalse($this->check($m, [$this->r('due', 'before', '1404/01/01')]));
        $this->assertTrue($this->check($m, [$this->r('due', 'after', '1403/12/29')]));
        $this->assertFalse($this->check($m, [$this->r('due', 'after', '1404/01/01')]));

        $this->assertTrue($this->check($this->record(['meta' => ['when' => '1404/01/01']]), [$this->r('meta.when', 'eq', '1404/01/01')]));
        $this->assertTrue($this->check($this->record(['meta' => ['when' => '2025-03-21 12:00:00']]), [$this->r('meta.when', 'eq', '1404/01/01')]));

        $bad = $this->record(['meta' => ['when' => 'not a date']]);
        $this->assertFalse($this->check($bad, [$this->r('meta.when', 'eq', '1404/01/01')]));
        $this->assertFalse($this->check($bad, [$this->r('meta.when', 'before', '1500/01/01')]));
    }

    public function test_numbers_compare_as_floats_with_persian_digits(): void
    {
        $m = $this->record(['score' => 7, 'ratio' => '7.50']);

        $this->assertTrue($this->check($m, [$this->r('score', 'eq', '7')]));
        $this->assertTrue($this->check($m, [$this->r('score', 'eq', '۷')]));
        $this->assertTrue($this->check($m, [$this->r('score', 'gt', '6')]));
        $this->assertFalse($this->check($m, [$this->r('score', 'lt', '6')]));
        $this->assertTrue($this->check($m, [$this->r('ratio', 'eq', '7.5')]));
        $this->assertTrue($this->check($m, [$this->r('ratio', 'gt', '7.4')]));
        $this->assertFalse($this->check($m, [$this->r('score', 'gt', 'abc')]));
        $this->assertTrue($this->check($this->record(['score' => 0]), [$this->r('score', 'filled')]));
    }

    public function test_text_contains_is_case_insensitive_and_eq_normalises_whitespace_and_digits(): void
    {
        $m = $this->record(['title' => ' Hello ۱۲ World ']);

        $this->assertTrue($this->check($m, [$this->r('title', 'contains', 'hello 12')]));
        $this->assertTrue($this->check($m, [$this->r('title', 'eq', 'Hello 12 World')]));
        $this->assertFalse($this->check($m, [$this->r('title', 'neq', 'Hello 12 World')]));
    }

    public function test_enum_bool_and_inline_option_facts_normalise_to_their_plain_value(): void
    {
        $this->assertTrue($this->check($this->record(['kind' => 'created']), [$this->r('kind', 'in', null, ['created', 'deleted'])]));
        $this->assertTrue($this->check($this->record(['kind' => NotifyEvent::Updated]), [$this->r('kind', 'not_in', null, ['created'])]));
        $this->assertFalse($this->check($this->record(['kind' => NotifyEvent::Updated]), [$this->r('kind', 'in', null, ['created'])]));

        $this->assertTrue($this->check($this->record(['active' => true]), [$this->r('active', 'in', null, ['1'])]));
        $this->assertTrue($this->check($this->record(['active' => '1']), [$this->r('active', 'in', null, ['1'])]));
        $this->assertTrue($this->check($this->record(['active' => false]), [$this->r('active', 'in', null, ['0'])]));
        $this->assertTrue($this->check($this->record(['active' => false]), [$this->r('active', 'not_in', null, ['1'])]));

        $this->assertTrue($this->check($this->record(['amount' => 'high']), [$this->r('amount', 'in', null, ['high'])]));
        $this->assertFalse($this->check($this->record(['amount' => 'low']), [$this->r('amount', 'in', null, ['high'])]));
    }

    public function test_list_facts_support_contains_not_contains_and_empty(): void
    {
        $m = $this->record(['tags' => ['a', 'b'], 'ids' => [3, 4]]);

        $this->assertTrue($this->check($m, [$this->r('tags', 'contains', 'a')]));
        $this->assertFalse($this->check($m, [$this->r('tags', 'contains', 'c')]));
        $this->assertTrue($this->check($m, [$this->r('tags', 'not_contains', 'c')]));
        $this->assertTrue($this->check($m, [$this->r('ids', 'contains', '3')]));
        $this->assertTrue($this->check($m, [$this->r('tags', 'filled')]));

        foreach ([[], null] as $empty) {
            $e = $this->record(['tags' => $empty]);
            $this->assertTrue($this->check($e, [$this->r('tags', 'empty')]));
            $this->assertFalse($this->check($e, [$this->r('tags', 'contains', 'a')]));
            $this->assertTrue($this->check($e, [$this->r('tags', 'not_contains', 'a')]));
        }
    }

    public function test_validate_prunes_trims_normalises_digits_and_drops_unused_keys(): void
    {
        $class = $this->record()::class;

        $this->assertNull(NotifyConditions::validate($class, null));
        $this->assertNull(NotifyConditions::validate($class, ['match' => 'all', 'rules' => [['field' => '  ', 'op' => 'eq', 'value' => 'x'], ['field' => '']]]));

        $clean = NotifyConditions::validate($class, ['match' => 'any', 'rules' => [
            ['field' => '', 'op' => 'eq', 'value' => 'x'],
            ['field' => ' score ', 'op' => ' gt ', 'value' => ' ۱۲ ', 'values' => ['x']],
            ['field' => 'kind', 'op' => 'in', 'value' => 'zzz', 'values' => ['created', ' deleted ']],
            ['field' => 'title', 'op' => 'filled', 'value' => 'zzz', 'values' => ['x']],
            ['field' => 'meta.when', 'op' => 'before', 'value' => '۱۴۰۴/۰۸/۰۶'],
        ]]);

        $this->assertSame([
            'match' => 'any',
            'rules' => [
                ['field' => 'score', 'op' => 'gt', 'value' => '12'],
                ['field' => 'kind', 'op' => 'in', 'values' => ['created', 'deleted']],
                ['field' => 'title', 'op' => 'filled'],
                ['field' => 'meta.when', 'op' => 'before', 'value' => '1404/08/06'],
            ],
        ], $clean);
    }

    public function test_validate_rejects_bad_fields_ops_values_and_oversized_sets(): void
    {
        $this->assertInvalid([$this->r('ghost', 'eq', 'x')]);
        $this->assertInvalid([$this->r('secret', 'filled')]);
        $this->assertInvalid([$this->r('title', 'gt', '1')]);
        $this->assertInvalid([$this->r('kind', 'eq', 'created')]);
        $this->assertInvalid([$this->r('kind', 'in', null, ['nope'])]);
        $this->assertInvalid([$this->r('kind', 'in', null, [])]);
        $this->assertInvalid([$this->r('score', 'eq', 'abc')]);
        $this->assertInvalid([$this->r('title', 'eq', '')]);
        $this->assertInvalid([$this->r('meta.when', 'eq', '1404-01-01')]);
        $this->assertInvalid([$this->r('title', 'eq', 'x')], 'xor');
        $this->assertInvalid(array_fill(0, NotifyConditions::MAX_RULES + 1, $this->r('title', 'eq', 'x')));

        $this->assertNotNull(NotifyConditions::validate($this->record()::class, ['match' => 'all', 'rules' => array_fill(0, NotifyConditions::MAX_RULES, $this->r('title', 'eq', 'x'))]));
    }

    public function test_summary_lists_one_persian_line_per_rule_and_flags_unknown_fields(): void
    {
        $class = $this->record()::class;

        $lines = NotifyConditions::summary($class, ['match' => 'all', 'rules' => [
            $this->r('score', 'gt', '5'),
            $this->r('kind', 'in', null, ['created']),
            $this->r('title', 'filled'),
            $this->r('ghost', 'eq', 'x'),
        ]]);

        $this->assertSame('score '.__('resources/notify_rule/strings.conditions.ops.gt').' 5', $lines[0]);
        $this->assertStringContainsString(NotifyEvent::Created->label(), $lines[1]);
        $this->assertSame('title '.__('resources/notify_rule/strings.conditions.ops.filled'), $lines[2]);
        $this->assertSame(__('resources/notify_rule/strings.conditions.invalid_field', ['field' => 'ghost']), $lines[3]);
        $this->assertSame([], NotifyConditions::summary($class, null));
    }

    public function test_declared_catalogs_are_consistent_and_the_hardening_rules_hold(): void
    {
        foreach (array_keys(NotifyRegistry::trackables()) as $class) {
            $fillable = (new $class)->getFillable();
            $fields = NotifyConditions::fields($class);

            foreach ($class::notifyFields() as $key => $kind) {
                $this->assertContains(Str::before($key, '.'), $fillable, "{$class} {$key}");
                $this->assertArrayHasKey($key, $fields, "{$class} {$key}");

                if (is_array($kind)) {
                    $this->assertNotEmpty($kind, "{$class} {$key}");

                    foreach ($kind as $value => $label) {
                        $this->assertIsString($label, "{$class} {$key}");
                        $this->assertNotSame('', $label, "{$class} {$key}");
                    }
                } elseif (! in_array($kind, ['text', 'number', 'date', 'list', 'bool', 'user', 'department'], true)) {
                    $this->assertTrue(class_exists($kind) && is_subclass_of($kind, BackedEnum::class), "{$class} {$key}");
                }
            }

            foreach (NotifyConditions::fieldOptions($class) as $field => $label) {
                $this->assertNotSame($field, $label, "{$class} {$field}");
            }

            if (in_array($class, [Ticket::class, Project::class, Authority::class], true)) {
                foreach (['extra', 'settings', 'details'] as $root) {
                    $this->assertArrayNotHasKey($root, $fields, "{$class} {$root}");
                }
            }
        }

        $class = $this->record()::class;

        $this->assertFalse($this->check($this->record(['title' => 'x']), [$this->r('title', 'gt', '1')]));
        $this->assertFalse($this->check($this->record(['title' => 'x']), [$this->r('title', 'gt', '1'), $this->r('title', 'eq', 'x')], 'any'));

        $this->assertSame(['in', 'not_in'], NotifyConditions::operators('bool'));
        $this->assertSame(NotifyConditions::OPS['option'], NotifyConditions::operators('user'));
        $this->assertTrue($this->check($this->record(['active' => null]), [$this->r('active', 'in', null, ['0'])]));
        $this->assertFalse($this->check($this->record(['active' => null]), [$this->r('active', 'in', null, ['1'])]));
        $this->assertTrue($this->check($this->record(['active' => null]), [$this->r('active', 'not_in', null, ['1'])]));
        $this->assertInvalid([$this->r('active', 'filled')]);
        $this->assertInvalid([$this->r('active', 'empty')]);
        $this->assertNotNull(NotifyConditions::validate($class, ['match' => 'all', 'rules' => [$this->r('active', 'in', null, ['1'])]]));

        $this->assertTrue($this->check($this->record(['meta' => ['when' => '1404/01/01 10:30:00']]), [$this->r('meta.when', 'eq', '1404/01/01')]));
        $this->assertTrue($this->check($this->record(['meta' => ['when' => '1404-01-01']]), [$this->r('meta.when', 'eq', '1404/01/01')]));
        $this->assertTrue($this->check($this->record(['meta' => ['when' => '1500/01/01']]), [$this->r('meta.when', 'after', '1499/12/29')]));
        $this->assertFalse($this->check($this->record(['meta' => ['when' => '1600/01/01']]), [$this->r('meta.when', 'eq', '1404/01/01')]));
        $this->assertInvalid([$this->r('meta.when', 'eq', '1600/01/01')]);
        $this->assertInvalid([$this->r('meta.when', 'eq', '1250/01/01')]);
        $this->assertNotNull(NotifyConditions::validate($class, ['match' => 'all', 'rules' => [$this->r('meta.when', 'eq', '1500/01/01')]]));

        $this->assertInvalid([$this->r('title', 'eq', str_repeat('a', 256))]);
        $this->assertNotNull(NotifyConditions::validate($class, ['match' => 'all', 'rules' => [$this->r('title', 'eq', str_repeat('a', 255))]]));
        $this->assertInvalid([$this->r('kind', 'in', null, array_fill(0, 101, 'created'))]);

        $this->assertSame([], NotifyConditions::fields('App\\Models\\GoneModel'));
        $this->assertSame([], NotifyConditions::fieldOptions('App\\Models\\GoneModel'));
        $this->assertSame([], NotifyConditions::fieldOptions(''));
        $this->assertSame(
            [__('resources/notify_rule/strings.conditions.invalid_field', ['field' => 'x'])],
            NotifyConditions::summary('App\\Models\\GoneModel', ['rules' => [$this->r('x', 'eq', '1')]]),
        );

        $this->assertSame(NotifyConditions::label(Ticket::class, 'requester'), NotifyConditions::label(Ticket::class, 'requester_id'));
        $this->assertNotSame('requester_id', NotifyConditions::label(Ticket::class, 'requester_id'));
        $this->assertSame(NotifyConditions::label(Project::class, 'owner'), NotifyConditions::label(Project::class, 'owner_id'));
        $this->assertSame(__('resources/task/strings.fields.creator'), NotifyConditions::label(Task::class, 'user_id'));
        $this->assertSame(__('resources/task/strings.fields.assignee'), NotifyConditions::label(Task::class, 'assigned_to'));
        $this->assertSame(__('resources/ths/strings.fields.assignee'), NotifyConditions::label(Ticket::class, 'assigned_to'));
        $this->assertSame(__('resources/ths/strings.fields.requester'), NotifyConditions::label(Ticket::class, 'requester_id'));
        $this->assertSame(__('resources/project/strings.fields.owner'), NotifyConditions::label(Project::class, 'owner_id'));
        $this->assertSame(__('resources/authority/strings.fields.user'), NotifyConditions::label(Authority::class, 'user_id'));
        $this->assertSame(__('resources/authority/strings.fields.department'), NotifyConditions::label(Authority::class, 'department_id'));

        foreach ([Ticket::class, Task::class] as $labelled) {
            $this->assertArrayNotHasKey('assigned_to', NotifyRegistry::fieldLabels($labelled));
            $this->assertArrayNotHasKey('user_id', NotifyRegistry::fieldLabels($labelled));
        }
    }

    public function test_picker_offers_aliased_labels_and_never_a_raw_column_name(): void
    {
        $options = NotifyConditions::fieldOptions(Ticket::class);

        $this->assertSame(__('resources/ths/strings.fields.subject'), $options['request_subject']);
        $this->assertSame(__('resources/ths/strings.fields.deadline'), $options['completion_deadline']);
        $this->assertSame(__('resources/ths/strings.fields.satisfaction'), $options['satisfaction_score']);
        $this->assertArrayNotHasKey('attachment', NotifyConditions::fields(Ticket::class));
    }

    public function test_audience_models_offer_the_visibility_labels_for_departments_and_active(): void
    {
        foreach ([Feed::class, Post::class] as $class) {
            $options = NotifyConditions::fieldOptions($class);

            $this->assertSame(__('resources/general/strings.visibility.departments'), $options['departments'], $class);
            $this->assertSame(__('resources/general/strings.visibility.active'), $options['active'], $class);
            $this->assertSame('list', NotifyConditions::kind($class, 'departments'), $class);
            $this->assertSame('bool', NotifyConditions::kind($class, 'active'), $class);
        }
    }

    public function test_energy_test_offers_its_user_and_dates_with_energy_labels_and_hides_scores(): void
    {
        $options = NotifyConditions::fieldOptions(EnergyTest::class);

        $this->assertSame('user', NotifyConditions::kind(EnergyTest::class, 'user_id'));
        $this->assertSame(__('resources/energy/strings.fields.user'), $options['user_id']);
        $this->assertSame(__('resources/energy/strings.fields.month_index'), $options['month_index']);
        $this->assertSame(__('resources/energy/strings.fields.completed_at'), $options['completed_at']);
        $this->assertArrayNotHasKey('answers', $options);
        $this->assertArrayNotHasKey('mind_score', $options);
        $this->assertArrayNotHasKey('overall_score', $options);
    }

    public function test_profile_offers_only_the_curated_safe_fields_and_no_pii_placeholders(): void
    {
        $options = NotifyConditions::fieldOptions(Profile::class);

        foreach (['department_id', 'position', 'employment_type', 'employment_status', 'degree', 'field', 'start_date', 'end_date'] as $field) {
            $this->assertArrayHasKey($field, $options, $field);
        }

        $this->assertSame(__('resources/profile/strings.fields.department'), $options['department_id']);
        $this->assertSame('department', NotifyConditions::kind(Profile::class, 'department_id'));

        foreach (['gender', 'personnel_id', 'interests', 'work_experience', 'user_id', ...Profile::notifyExcept()] as $private) {
            $this->assertArrayNotHasKey($private, $options, $private);
        }

        $labelled = array_keys(array_intersect_key(NotifyRegistry::fieldLabels(Profile::class), array_flip(Profile::notifyPlaceholders())));

        $this->assertSame([], array_values(array_intersect($labelled, Profile::notifyExcept())));
        $this->assertSame([], array_values(array_intersect($labelled, ['gender', 'personnel_id', 'interests', 'work_experience', 'birthdate', 'cellphone', 'address'])));
        $this->assertSame([], array_values(array_intersect(Profile::notifyPlaceholders(), Profile::notifyExcept())));
    }

    public function test_a_catalogued_field_without_a_label_is_hidden_from_the_picker_but_still_works(): void
    {
        $this->assertArrayHasKey('rank', NotifyConditions::fields(Task::class));
        $this->assertArrayNotHasKey('rank', NotifyConditions::fieldOptions(Task::class));

        $task = (new Task)->forceFill(['rank' => 3]);
        $rules = [$this->r('rank', 'eq', '3')];

        $this->assertTrue($this->check($task, $rules));
        $this->assertSame('rank', NotifyConditions::label(Task::class, 'rank'));
        $this->assertNotNull(NotifyConditions::validate(Task::class, ['match' => 'all', 'rules' => $rules]));
        $this->assertCount(1, NotifyConditions::summary(Task::class, ['rules' => $rules]));
        $this->assertStringStartsWith('rank', NotifyConditions::summary(Task::class, ['rules' => $rules])[0]);
    }

    public function test_empty_or_blank_date_facts_never_match_and_illegal_ops_are_flagged_in_the_summary(): void
    {
        foreach (['', '   '] as $blank) {
            $m = $this->record(['meta' => ['when' => $blank]]);

            foreach (['eq', 'before', 'after'] as $op) {
                $this->assertFalse($this->check($m, [$this->r('meta.when', $op, '1404/01/01')]), "'{$blank}' {$op}");
            }
        }

        $class = $this->record()::class;

        $this->assertSame(
            [__('resources/notify_rule/strings.conditions.invalid_field', ['field' => NotifyConditions::label($class, 'title')])],
            NotifyConditions::summary($class, ['rules' => [$this->r('title', 'gt', '1')]]),
        );
    }

    public function test_private_columns_stay_out_of_the_catalog_and_uncast_array_accessors_are_lists(): void
    {
        $this->assertArrayNotHasKey('answers', NotifyConditions::fields(EnergyTest::class));
        $this->assertArrayNotHasKey('favorite_colors', NotifyConditions::fields(Profile::class));
        $this->assertArrayNotHasKey('attachments', NotifyConditions::fields(ReleaseRequest::class));
        $this->assertArrayNotHasKey('requester_files', NotifyConditions::fields(Ticket::class));

        $fields = NotifyConditions::fields(Department::class);
        $this->assertSame('list', $fields['units']);
        $this->assertSame('list', $fields['sections']);

        $department = new Department;
        $department->units = ['A', 'B'];
        $this->assertTrue($this->check($department, [$this->r('units', 'contains', 'A')]));
        $this->assertFalse($this->check($department, [$this->r('units', 'contains', 'C')]));
    }

    public function test_a_model_that_lost_the_trait_has_no_fields_and_summaries_degrade(): void
    {
        $this->assertSame([], NotifyConditions::fields(User::class));
        $this->assertSame([], NotifyConditions::fieldOptions(User::class));
        $this->assertSame(
            [__('resources/notify_rule/strings.conditions.invalid_field', ['field' => 'email'])],
            NotifyConditions::summary(User::class, ['rules' => [$this->r('email', 'eq', 'x')]]),
        );
    }

    public function test_arabic_yeh_and_kaf_equal_their_persian_forms_for_text_and_lists(): void
    {
        $m = $this->record(['title' => 'علي كريمي', 'tags' => ['كتاب']]);

        $this->assertTrue($this->check($m, [$this->r('title', 'eq', 'علی کریمی')]));
        $this->assertTrue($this->check($m, [$this->r('title', 'contains', 'کریم')]));
        $this->assertTrue($this->check($this->record(['title' => 'علی کریمی']), [$this->r('title', 'eq', 'علي كريمي')]));
        $this->assertTrue($this->check($m, [$this->r('tags', 'contains', 'کتاب')]));
        $this->assertTrue($this->check($this->record(['tags' => ['کتاب']]), [$this->r('tags', 'contains', 'كتاب')]));
    }

    private function linkRule(int $id, array $rule, array $overrides = []): NotifyRule
    {
        $model = new NotifyRule(array_merge([
            'name' => 'rule '.$id,
            'model' => Link::class,
            'events' => ['created'],
            'channels' => ['mail'],
            'subject' => 'subject',
            'lines' => ['line'],
            'is_active' => true,
            'conditions' => ['match' => 'all', 'rules' => [$rule]],
        ], $overrides));
        $model->id = $id;

        return $model;
    }

    private function prime(NotifyRule ...$rules): void
    {
        Cache::put(NotifyService::CACHE_KEY, collect($rules)->groupBy('model'), now()->addHour());
    }

    private function dispatchedRuleIds(): array
    {
        return Bus::dispatched(SendNotifyRule::class)->map(fn (SendNotifyRule $job) => $job->ruleId)->sort()->values()->all();
    }

    public function test_a_non_matching_rule_dispatches_nothing_with_zero_queries_while_a_matching_one_dispatches(): void
    {
        $this->prime(
            $this->linkRule(971, $this->r('url_title', 'eq', 'Other')),
            $this->linkRule(972, $this->r('url_title', 'eq', 'Mine')),
            $this->linkRule(973, $this->r('ghost', 'eq', 'Mine')),
        );

        $link = Link::factory()->make(['url_title' => 'Mine']);

        DB::connection()->enableQueryLog();
        NotifyService::handle($link, NotifyEvent::Created);
        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();

        $this->assertSame([], $queries);
        $this->assertSame([972], $this->dispatchedRuleIds());
    }

    public function test_deleted_events_match_the_in_memory_snapshot(): void
    {
        $this->prime($this->linkRule(974, $this->r('url_title', 'eq', 'Gone'), ['events' => ['deleted']]));

        NotifyService::handle(Link::factory()->make(['url_title' => 'Other']), NotifyEvent::Deleted);
        Bus::assertNotDispatched(SendNotifyRule::class);

        NotifyService::handle(Link::factory()->make(['url_title' => 'Gone']), NotifyEvent::Deleted);
        $this->assertSame([974], $this->dispatchedRuleIds());
    }

    public function test_updated_with_watch_and_condition_evaluates_the_new_state(): void
    {
        $watch = ['events' => ['updated'], 'extra' => ['watch' => ['url_title']]];
        $this->prime(
            $this->linkRule(975, $this->r('url_title', 'eq', 'new'), $watch),
            $this->linkRule(976, $this->r('url_title', 'eq', 'old'), $watch),
        );

        $link = Link::factory()->make(['url_title' => 'old']);
        $link->syncOriginal();
        $link->forceFill(['url_title' => 'new'])->syncChanges();

        NotifyService::handle($link, NotifyEvent::Updated);

        $this->assertSame([975], $this->dispatchedRuleIds());
    }

    public function test_rules_without_conditions_still_fire(): void
    {
        $this->prime($this->linkRule(977, [], ['conditions' => null]), $this->linkRule(978, [], ['conditions' => ['match' => 'all', 'rules' => []]]));

        NotifyService::handle(Link::factory()->make(), NotifyEvent::Created);

        $this->assertSame([977, 978], $this->dispatchedRuleIds());
    }
}
