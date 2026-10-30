<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas;

use App\Enums\NotifyEvent;
use App\Models\Department;
use App\Models\User;
use App\Services\Notify\NotifyConditions;
use App\Services\Notify\NotifyRegistry;
use App\Services\PersianDateFieldService;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class NotifyRuleFormPresenter
{
    private const BUILTIN_PLACEHOLDERS = ['model', 'event', 'id', 'actor', 'date'];

    public static function activeFromDate(): FusedGroup
    {
        return self::dateField('active_from_date', __('resources/notify_rule/strings.fields.active_from_date'));
    }

    public static function activeFromTime(): TextInput
    {
        return self::timeField('active_from_time', __('resources/notify_rule/strings.fields.active_from_time'));
    }

    public static function activeUntilDate(): FusedGroup
    {
        return self::dateField('active_until_date', __('resources/notify_rule/strings.fields.active_until_date'));
    }

    public static function activeUntilTime(): TextInput
    {
        return self::timeField('active_until_time', __('resources/notify_rule/strings.fields.active_until_time'));
    }

    public static function assertWindow(array $data): array
    {
        if (filled($data['active_from'] ?? null) && filled($data['active_until'] ?? null)
            && Carbon::parse($data['active_until'])->lt(Carbon::parse($data['active_from']))) {
            throw ValidationException::withMessages([
                'data.active_until_date_day' => __('resources/notify_rule/strings.validation.window'),
            ]);
        }

        return $data;
    }

    public static function assertConditions(array $data): array
    {
        $data['conditions'] = NotifyConditions::validate($data['model'], $data['conditions'] ?? null);

        return $data;
    }

    public static function conditionsMatch(): Radio
    {
        return Radio::make('conditions.match')
            ->label(__('resources/notify_rule/strings.fields.condition_match'))
            ->options([
                'all' => __('resources/notify_rule/strings.conditions.match_all'),
                'any' => __('resources/notify_rule/strings.conditions.match_any'),
            ])
            ->default('all')
            ->formatStateUsing(fn ($state) => $state ?? 'all')
            ->inline()
            ->visible(fn ($livewire) => filled($livewire->data['model'] ?? null))
            ->columnSpanFull();
    }

    public static function conditions(): Repeater
    {
        return Repeater::make('conditions.rules')
            ->label(__('resources/notify_rule/strings.fields.conditions'))
            ->schema([
                Select::make('field')
                    ->label(__('resources/notify_rule/strings.fields.condition_field'))
                    ->options(function (Get $get, $livewire): array {
                        $class = (string) ($livewire->data['model'] ?? '');
                        $options = NotifyConditions::fieldOptions($class);
                        $current = (string) $get('field');

                        if ($current !== '' && ! isset($options[$current]) && NotifyConditions::kind($class, $current) !== null) {
                            $options[$current] = NotifyConditions::label($class, $current);
                        }

                        return $options;
                    })
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('op', null);
                        $set('value', null);
                        $set('values', []);
                    }),
                Select::make('op')
                    ->label(__('resources/notify_rule/strings.fields.condition_op'))
                    ->options(function (Get $get, $livewire): array {
                        $kind = NotifyConditions::kind((string) ($livewire->data['model'] ?? ''), (string) $get('field'));

                        return $kind === null ? [] : collect(NotifyConditions::operators($kind))
                            ->mapWithKeys(fn ($op) => [$op => __("resources/notify_rule/strings.conditions.ops.{$op}")])
                            ->all();
                    })
                    ->native(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('value', null);
                        $set('values', []);
                    }),
                TextInput::make('value')
                    ->label(__('resources/notify_rule/strings.fields.condition_value'))
                    ->inputMode(fn (Get $get, $livewire) => NotifyConditions::kind((string) ($livewire->data['model'] ?? ''), (string) $get('field')) === 'number' ? 'decimal' : null)
                    ->placeholder(fn (Get $get, $livewire) => NotifyConditions::kind((string) ($livewire->data['model'] ?? ''), (string) $get('field')) === 'date'
                        ? __('resources/notify_rule/strings.conditions.date_placeholder')
                        : null)
                    ->maxLength(191)
                    ->visible(fn (Get $get) => filled($get('field')) && filled($get('op')) && ! in_array($get('op'), ['in', 'not_in', 'filled', 'empty'], true)),
                Select::make('values')
                    ->label(__('resources/notify_rule/strings.fields.condition_value'))
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->options(fn (Get $get, $livewire): array => NotifyConditions::kind((string) ($livewire->data['model'] ?? ''), (string) $get('field')) === 'user'
                        ? []
                        : NotifyConditions::options((string) ($livewire->data['model'] ?? ''), (string) $get('field')))
                    ->getSearchResultsUsing(fn (string $search, Get $get, $livewire): array => collect(NotifyConditions::options((string) ($livewire->data['model'] ?? ''), (string) $get('field')))
                        ->filter(fn ($label) => mb_stripos((string) $label, $search) !== false)
                        ->take(50)
                        ->all())
                    ->getOptionLabelsUsing(fn (array $values, Get $get, $livewire): array => collect(NotifyConditions::options((string) ($livewire->data['model'] ?? ''), (string) $get('field')))
                        ->only($values)
                        ->all())
                    ->visible(fn (Get $get) => in_array($get('op'), ['in', 'not_in'], true)),
            ])
            ->columns(3)
            ->default([])
            ->maxItems(NotifyConditions::MAX_RULES)
            ->reorderable(false)
            ->addActionLabel(__('resources/notify_rule/strings.conditions.add'))
            ->helperText(__('resources/notify_rule/strings.hints.conditions'))
            ->visible(fn ($livewire) => filled($livewire->data['model'] ?? null))
            ->columnSpanFull();
    }

    public static function mergeExtra(array $data, ?array $stored): array
    {
        $extra = array_merge($stored ?? [], $data['extra'] ?? []);

        if (! in_array(NotifyEvent::Updated->value, $data['events'] ?? [], true)) {
            unset($extra['watch']);
        }

        if (($extra['audience'] ?? 'users') !== 'departments') {
            unset($extra['departments']);
        }

        $data['extra'] = $extra;

        return $data;
    }

    public static function recipientFields(): array
    {
        return [self::audience(), self::departments(), self::userIds(), self::extraContacts()];
    }

    public static function messageFields(bool $oneOff = false): array
    {
        return [self::template(), self::subject($oneOff), self::lines($oneOff)];
    }

    public static function audience(): Radio
    {
        return Radio::make('extra.audience')
            ->label(__('resources/notify_rule/strings.audience.label'))
            ->options([
                'users' => __('resources/notify_rule/strings.audience.users'),
                'all' => __('resources/notify_rule/strings.audience.all'),
                'departments' => __('resources/notify_rule/strings.audience.departments'),
            ])
            ->default('users')
            ->inline()
            ->live()
            ->helperText(__('resources/notify_rule/strings.audience.hint', ['ceiling' => number_format((int) config('notify.ceiling'))]))
            ->columnSpanFull();
    }

    public static function departments(): Select
    {
        return Select::make('extra.departments')
            ->label(__('resources/notify_rule/strings.audience.departments_label'))
            ->multiple()
            ->searchable()
            ->native(false)
            ->options(fn () => Department::getCachedOptions()->toArray())
            ->required()
            ->helperText(__('resources/notify_rule/strings.audience.departments_hint'))
            ->visible(fn (Get $get) => $get('extra.audience') === 'departments')
            ->columnSpanFull();
    }

    public static function channels(): CheckboxList
    {
        return CheckboxList::make('channels')
            ->label(__('resources/notify_rule/strings.fields.channels'))
            ->options(fn () => NotifyRegistry::channelOptions())
            ->required()
            ->columns(2);
    }

    public static function events(): CheckboxList
    {
        return CheckboxList::make('events')
            ->label(__('resources/notify_rule/strings.fields.events'))
            ->options(NotifyEvent::options())
            ->helperText(__('resources/notify_rule/strings.hints.events'))
            ->live()
            ->required()
            ->columns(3);
    }

    public static function extraContacts(): TagsInput
    {
        return TagsInput::make('extra.contacts')
            ->label(__('resources/notify_rule/strings.fields.extra_contacts'))
            ->placeholder(__('resources/notify_rule/strings.placeholders.contacts'))
            ->helperText(__('resources/notify_rule/strings.hints.contacts'))
            ->required(fn (Get $get) => self::needsRecipient($get, 'user_ids'))
            ->rules(['array', 'max:20'])
            ->nestedRecursiveRules([
                fn () => function (string $attribute, mixed $value, Closure $fail): void {
                    $accepted = collect(NotifyRegistry::channels())->contains(fn ($channel) => $channel->accepts((string) $value));

                    if (! $accepted || mb_strlen((string) $value) > 191) {
                        $fail(__('resources/notify_rule/strings.validation.contact', ['contact' => $value]));
                    }
                },
            ])
            ->columnSpanFull();
    }

    public static function isActive(): Toggle
    {
        return Toggle::make('is_active')
            ->label(__('resources/notify_rule/strings.fields.is_active'))
            ->default(true);
    }

    public static function lines(bool $oneOff = false): Repeater
    {
        return Repeater::make('lines')
            ->label(__('resources/notify_rule/strings.fields.lines'))
            ->simple(
                TextInput::make('text')
                    ->label(__('resources/notify_rule/strings.fields.line'))
                    ->required()
                    ->maxLength(500)
            )
            ->defaultItems(1)
            ->minItems(1)
            ->maxItems(10)
            ->required()
            ->helperText(__($oneOff ? 'resources/notify_rule/strings.hints.manual_lines' : 'resources/notify_rule/strings.hints.lines'))
            ->columnSpanFull();
    }

    public static function model(): Select
    {
        return Select::make('model')
            ->label(__('resources/notify_rule/strings.fields.model'))
            ->options(fn (?\Illuminate\Database\Eloquent\Model $record) => NotifyRegistry::permitted() + array_intersect_key(NotifyRegistry::trackables(), array_flip(array_filter([$record?->model]))))
            ->native(false)
            ->live()
            ->afterStateUpdated(function (Set $set): void {
                $set('extra.watch', []);
                $set('conditions', ['match' => 'all', 'rules' => []]);
            })
            ->required();
    }

    public static function watch(): Select
    {
        return Select::make('extra.watch')
            ->label(__('resources/notify_rule/strings.fields.watch'))
            ->multiple()
            ->searchable()
            ->native(false)
            ->options(function (Get $get): array {
                $class = $get('model');

                return is_string($class) && array_key_exists($class, NotifyRegistry::trackables())
                    ? collect($class::notifyPlaceholders())->mapWithKeys(fn (string $field) => [$field => NotifyConditions::label($class, $field)])->all()
                    : [];
            })
            ->helperText(__('resources/notify_rule/strings.hints.watch'))
            ->visible(fn (Get $get) => in_array(NotifyEvent::Updated->value, $get('events') ?? [], true) && filled($get('model')))
            ->columnSpanFull();
    }

    public static function name(): TextInput
    {
        return TextInput::make('name')
            ->label(__('resources/notify_rule/strings.fields.name'))
            ->placeholder(__('resources/notify_rule/strings.placeholders.name'))
            ->required()
            ->maxLength(191);
    }

    public static function subject(bool $oneOff = false): TextInput
    {
        return TextInput::make('subject')
            ->label(__('resources/notify_rule/strings.fields.subject'))
            ->placeholder(__('resources/notify_rule/strings.placeholders.subject'))
            ->required()
            ->maxLength(191)
            ->helperText(function (Get $get) use ($oneOff): Htmlable {
                $items = collect($oneOff ? ['actor', 'date'] : self::BUILTIN_PLACEHOLDERS)
                    ->mapWithKeys(fn ($n) => [$n => __("resources/notify_rule/strings.builtin_labels.{$n}")]);
                $class = $get('model');

                if (is_string($class) && array_key_exists($class, NotifyRegistry::trackables())) {
                    $labels = NotifyRegistry::fieldLabels($class);
                    $items = $items->union(
                        collect($class::notifyPlaceholders())->filter(fn ($n) => isset($labels[$n]))->mapWithKeys(fn ($n) => [$n => $labels[$n]])
                    );
                }

                return new HtmlString(__($oneOff ? 'resources/notify_rule/strings.hints.manual_subject' : 'resources/notify_rule/strings.hints.subject', [
                    'placeholders' => $items->map(fn ($label, $n) => '<bdi>{'.e($label).'}</bdi>')->implode(' ، '),
                ]));
            })
            ->columnSpanFull();
    }

    public static function template(): Select
    {
        return Select::make('extra.template')
            ->label(__('resources/notify_rule/strings.fields.template'))
            ->options(fn () => NotifyRegistry::templateOptions())
            ->default('plain')
            ->native(false)
            ->selectablePlaceholder(false)
            ->helperText(__('resources/notify_rule/strings.hints.template'));
    }

    public static function userIds(): Select
    {
        return Select::make('user_ids')
            ->label(__('resources/notify_rule/strings.fields.user_ids'))
            ->multiple()
            ->searchable()
            ->native(false)
            ->options(fn () => User::getCachedAllOptions()->toArray())
            ->required(fn (Get $get) => self::needsRecipient($get, 'extra.contacts'))
            ->maxItems(100)
            ->helperText(__('resources/notify_rule/strings.hints.user_ids'))
            ->suffixAction(
                Action::make('addDepartment')
                    ->label(__('resources/notify_rule/strings.actions.add_department'))
                    ->icon('heroicon-o-user-group')
                    ->modalHeading(__('resources/notify_rule/strings.actions.add_department_heading'))
                    ->modalWidth('md')
                    ->schema([
                        Select::make('department')
                            ->label(__('resources/notify_rule/strings.actions.department'))
                            ->options(fn () => Department::getCachedOptions()->toArray())
                            ->native(false)
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, Get $get, Set $set): void {
                        $ids = collect($get('user_ids') ?? [])
                            ->merge(User::getCachedDepartmentOptions((string) $data['department'])->keys())
                            ->map(fn ($id) => (int) $id)
                            ->unique()
                            ->take(100)
                            ->values()
                            ->all();

                        $set('user_ids', $ids);
                    })
            )
            ->columnSpanFull();
    }

    private static function needsRecipient(Get $get, string $other): bool
    {
        return ($get('extra.audience') ?? 'users') === 'users' && blank($get($other));
    }

    private static function dateField(string $prefix, string $label): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: $prefix,
            label: $label,
            required: false,
            yearFrom: 1400,
            yearTo: Jalalian::now()->getYear() + 5,
            fullWidth: false,
        )->columnSpan(3);
    }

    private static function timeField(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->columnSpan(2)
            ->type('time');
    }
}
