<?php

namespace App\Services\Notify;

use App\Casts\IntegerList;
use App\Models\Concerns\HasAudience;
use App\Models\Department;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class NotifyConditions
{
    public const OPS = [
        'text' => ['eq', 'neq', 'contains', 'filled', 'empty'],
        'number' => ['eq', 'neq', 'gt', 'lt', 'filled', 'empty'],
        'date' => ['eq', 'before', 'after', 'filled', 'empty'],
        'list' => ['contains', 'not_contains', 'filled', 'empty'],
        'option' => ['in', 'not_in', 'filled', 'empty'],
        'bool' => ['in', 'not_in'],
    ];

    private const MAX_VALUE_LENGTH = 255;

    private const MAX_VALUES = 100;

    private const JALALI_DATE = '/^1[3-5]\d{2}\/\d{2}\/\d{2}$/';

    public const MAX_RULES = 10;

    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    private const DATE_CASTS = ['datetime', 'date', 'immutable_datetime', 'immutable_date'];

    private const NUMBER_CASTS = ['int', 'integer', 'float', 'double', 'decimal', 'real'];

    private const LIST_CASTS = ['array', 'json', 'collection', AsCollection::class, AsArrayObject::class, IntegerList::class];

    private static array $fields = [];

    private static array $options = [];

    private static ?array $aliases = null;

    public static function fields(string $class): array
    {
        if (! class_exists($class) || ! method_exists($class, 'notifyFields')) {
            return [];
        }

        return self::$fields[$class] ??= self::buildFields($class);
    }

    private static function buildFields(string $class): array
    {
        $model = new $class;
        $casts = $model->getCasts();
        $fields = [];

        foreach (array_diff($model->getFillable(), $class::notifyExcept(), $model->getHidden()) as $column) {
            $fields[$column] = self::inferKind($casts[$column] ?? null);
        }

        $roots = $fields;

        $dottedRoots = [];

        foreach ($class::notifyFields() as $key => $kind) {
            if (! str_contains($key, '.')) {
                $fields[$key] = $kind;
            } elseif (isset($roots[$root = Str::before($key, '.')])) {
                $fields[$key] = $kind;
                $dottedRoots[$root] = true;
            }
        }

        return array_diff_key($fields, $dottedRoots);
    }

    private static function inferKind(?string $cast): string
    {
        if ($cast === null) {
            return 'text';
        }

        $base = Str::before($cast, ':');

        return match (true) {
            in_array($base, self::DATE_CASTS, true) => 'date',
            in_array($base, ['bool', 'boolean'], true) => 'bool',
            in_array($base, self::NUMBER_CASTS, true) => 'number',
            in_array($base, self::LIST_CASTS, true) => 'list',
            is_subclass_of($base, BackedEnum::class) => $base,
            default => 'text',
        };
    }

    public static function kind(string $class, string $field): string|array|null
    {
        return self::fields($class)[$field] ?? null;
    }

    public static function operators(string|array $kind): array
    {
        return self::OPS[self::group($kind)];
    }

    public static function hasOptions(string|array $kind): bool
    {
        return in_array(self::group($kind), ['option', 'bool'], true);
    }

    private static function group(string|array $kind): string
    {
        return is_string($kind) && in_array($kind, ['text', 'number', 'date', 'list', 'bool'], true) ? $kind : 'option';
    }

    public static function options(string $class, string $field): array
    {
        $kind = self::kind($class, $field);

        return match (true) {
            $kind === 'department' => Department::getCachedOptions()->all(),
            $kind === 'user' => User::getCachedAllOptions()->all(),
            $kind === 'bool' => ['1' => __('resources/notify_rule/strings.conditions.yes'), '0' => __('resources/notify_rule/strings.conditions.no')],
            is_array($kind) => $kind,
            is_string($kind) && is_subclass_of($kind, BackedEnum::class) => self::enumOptions($kind),
            default => [],
        };
    }

    private static function enumOptions(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $options[$case->value] = (string) match (true) {
                $case instanceof HasLabel => $case->getLabel(),
                method_exists($case, 'label') => $case->label(),
                default => $case->name,
            };
        }

        return $options;
    }

    public static function label(string $class, string $field): string
    {
        return self::resolved($class, $field) ?? $field;
    }

    private static function resolved(string $class, string $field): ?string
    {
        $labels = NotifyRegistry::fieldLabels($class);

        $name = Str::afterLast($field, '.');

        $alias = self::aliases()[$name] ?? null;

        return $labels[$field]
            ?? $labels[$name]
            ?? ($alias !== null ? ($labels[$alias] ?? null) : null)
            ?? $labels[Str::beforeLast($name, '_id')]
            ?? (in_array($name, ['active', 'departments'], true) && in_array(HasAudience::class, class_uses_recursive($class), true) ? __("resources/general/strings.visibility.{$name}") : null);
    }

    private static function aliases(): array
    {
        if (self::$aliases === null) {
            $aliases = __('resources/notify_rule/strings.conditions.label_aliases');

            if (! is_array($aliases)) {
                return [];
            }

            self::$aliases = $aliases;
        }

        return self::$aliases;
    }

    public static function fieldOptions(string $class): array
    {
        if (isset(self::$options[$class])) {
            return self::$options[$class];
        }

        $fields = self::fields($class);

        if ($fields === []) {
            return self::$options[$class] = [];
        }

        $options = [];
        $declared = $class::notifyFields();

        foreach (array_keys($fields) as $field) {
            if (($label = self::resolved($class, $field)) !== null || array_key_exists($field, $declared)) {
                $options[$field] = $label ?? $field;
            }
        }

        return self::$options[$class] = $options;
    }

    public static function matches(Model $model, array $conditions): bool
    {
        $fields = self::fields($model::class);
        $rules = $conditions['rules'] ?? [];

        foreach ($rules as $rule) {
            $field = $rule['field'] ?? '';

            if (! array_key_exists($field, $fields) || ! in_array($rule['op'] ?? '', self::operators($fields[$field]), true)) {
                return false;
            }
        }

        $any = ($conditions['match'] ?? 'all') === 'any';

        foreach ($rules as $rule) {
            $hit = self::test($model, $fields[$rule['field']], $rule);

            if ($hit === $any) {
                return $hit;
            }
        }

        return ! $any;
    }

    private static function test(Model $model, string|array $kind, array $rule): bool
    {
        $op = $rule['op'] ?? '';
        $raw = self::fact($model, $rule['field']);

        if ($kind === 'bool' && $raw === null) {
            $raw = '0';
        }

        if ($op === 'filled') {
            return filled($raw);
        }

        if ($op === 'empty') {
            return blank($raw);
        }

        if ($kind === 'list') {
            $items = is_array($raw) ? $raw : ($raw instanceof Arrayable ? $raw->toArray() : null);

            if ($items === null) {
                return $op === 'not_contains';
            }

            $has = in_array(self::norm($rule['value'] ?? null, 'list'), array_map(fn ($i) => self::norm($i, 'list'), $items), true);

            return $op === 'contains' ? $has : ! $has;
        }

        $fact = self::norm($raw, $kind);

        if ($fact === null) {
            return in_array($op, ['neq', 'not_in', 'not_contains'], true);
        }

        $value = self::norm($rule['value'] ?? null, $kind);

        return match ($op) {
            'eq' => $value !== null && $fact === $value,
            'neq' => $fact !== $value,
            'contains' => $value !== null && mb_stripos((string) $fact, (string) $value) !== false,
            'gt' => $value !== null && $fact > $value,
            'lt' => $value !== null && $fact < $value,
            'before' => $value !== null && strcmp($fact, $value) < 0,
            'after' => $value !== null && strcmp($fact, $value) > 0,
            'in' => in_array($fact, self::normAll($rule['values'] ?? [], $kind), true),
            'not_in' => ! in_array($fact, self::normAll($rule['values'] ?? [], $kind), true),
            default => false,
        };
    }

    private static function normAll(mixed $values, string|array $kind): array
    {
        return array_map(fn ($v) => self::norm($v, $kind), (array) $values);
    }

    private static function fact(Model $model, string $field): mixed
    {
        if (! str_contains($field, '.')) {
            return $model->getAttribute($field);
        }

        [$root, $rest] = explode('.', $field, 2);

        return data_get($model->getAttribute($root), $rest);
    }

    private static function norm(mixed $value, string|array $kind): string|float|null
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        if ($kind === 'date') {
            return self::normDate($value);
        }

        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return match (true) {
            $kind === 'number' => is_numeric($number = self::digits($text)) ? (float) $number : null,
            $kind === 'text', $kind === 'list' => self::letters($text),
            default => $text,
        };
    }

    private static function normDate(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return self::jalali($value);
        }

        if (! is_scalar($value)) {
            return null;
        }

        $token = preg_split('/\s+/', trim((string) $value))[0];

        if ($token === '') {
            return null;
        }

        $text = str_replace('-', '/', self::digits($token));

        if (preg_match(self::JALALI_DATE, $text)) {
            return $text;
        }

        $date = rescue(fn () => Carbon::parse($token), null, false);

        return $date ? self::jalali($date) : null;
    }

    private static function jalali(CarbonInterface $date): ?string
    {
        return self::digits(toJalali($date, 'Y/m/d')) ?: null;
    }

    private static function digits(string $text): string
    {
        return strtr($text, self::DIGITS);
    }

    private static function letters(string $text): string
    {
        return strtr(self::digits($text), ['ي' => 'ی', 'ك' => 'ک']);
    }

    public static function validate(string $class, ?array $conditions): ?array
    {
        $rows = array_filter($conditions['rules'] ?? [], fn ($r) => is_array($r) && trim((string) ($r['field'] ?? '')) !== '');

        if ($rows === []) {
            return null;
        }

        $match = $conditions['match'] ?? 'all';

        if (! in_array($match, ['all', 'any'], true)) {
            self::fail('condition_op');
        }

        if (count($rows) > self::MAX_RULES) {
            self::fail('condition_max');
        }

        $rules = [];

        foreach ($rows as $row) {
            $rules[] = self::cleanRow($class, $row);
        }

        return ['match' => $match, 'rules' => $rules];
    }

    private static function cleanRow(string $class, array $row): array
    {
        $field = trim((string) $row['field']);
        $kind = self::kind($class, $field);

        if ($kind === null) {
            self::fail('condition_field');
        }

        $op = trim((string) ($row['op'] ?? ''));

        if (! in_array($op, self::operators($kind), true)) {
            self::fail('condition_op');
        }

        $clean = ['field' => $field, 'op' => $op];

        if (in_array($op, ['filled', 'empty'], true)) {
            return $clean;
        }

        if (in_array($op, ['in', 'not_in'], true)) {
            if (count((array) ($row['values'] ?? [])) > self::MAX_VALUES) {
                self::fail('condition_value');
            }

            $values = array_values(array_unique(array_filter(
                array_map(fn ($v) => trim((string) $v), array_filter((array) ($row['values'] ?? []), 'is_scalar')),
                fn ($v) => $v !== '',
            )));

            if ($values === [] || array_diff($values, array_map('strval', array_keys(self::options($class, $field)))) !== []) {
                self::fail('condition_value');
            }

            return $clean + ['values' => $values];
        }

        $value = is_scalar($row['value'] ?? null) ? self::digits(trim((string) $row['value'])) : '';

        $valid = match (true) {
            $value === '' || mb_strlen($value) > self::MAX_VALUE_LENGTH => false,
            $kind === 'number' => is_numeric($value),
            $kind === 'date' => (bool) preg_match(self::JALALI_DATE, $value),
            default => true,
        };

        if (! $valid) {
            self::fail('condition_value');
        }

        return $clean + ['value' => $value];
    }

    private static function fail(string $key): never
    {
        throw ValidationException::withMessages(['data.conditions.rules' => __("resources/notify_rule/strings.validation.{$key}")]);
    }

    public static function summary(string $class, ?array $conditions): array
    {
        $lines = [];

        foreach ($conditions['rules'] ?? [] as $rule) {
            $field = (string) ($rule['field'] ?? '');
            $kind = self::kind($class, $field);

            $op = $rule['op'] ?? '';

            if ($kind === null || ! in_array($op, self::operators($kind), true)) {
                $lines[] = __('resources/notify_rule/strings.conditions.invalid_field', ['field' => $kind === null ? $field : self::label($class, $field)]);

                continue;
            }

            $value = '';

            if (! in_array($op, ['filled', 'empty'], true)) {
                if (self::hasOptions($kind)) {
                    $options = self::options($class, $field);
                    $value = implode('، ', array_map(fn ($v) => $options[$v] ?? $v, (array) ($rule['values'] ?? [])));
                } else {
                    $value = (string) ($rule['value'] ?? '');
                }
            }

            $lines[] = trim(self::label($class, $field).' '.__("resources/notify_rule/strings.conditions.ops.{$op}").' '.$value);
        }

        return $lines;
    }
}
