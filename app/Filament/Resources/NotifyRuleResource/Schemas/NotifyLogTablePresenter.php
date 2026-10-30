<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas;

use App\Enums\NotifyEvent;
use App\Filament\Resources\NotifyRuleResource;
use App\Models\Department;
use App\Models\NotifyLog;
use App\Models\NotifyRule;
use App\Models\Profile;
use App\Models\User;
use App\Services\Notify\NotifyRegistry;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class NotifyLogTablePresenter
{
    public const STATUS_COLORS = [
        NotifyLog::STATUS_SUMMARY => 'info',
        NotifyLog::STATUS_SENT => 'success',
        NotifyLog::STATUS_FAILED => 'danger',
        NotifyLog::STATUS_SKIPPED => 'warning',
    ];

    public static function channel(): TextColumn
    {
        return TextColumn::make('channel')
            ->label(__('resources/notify_rule/strings.logs.channel'))
            ->badge()
            ->color('info')
            ->formatStateUsing(fn ($state) => self::channelLabel($state));
    }

    public static function channelFilter(): SelectFilter
    {
        return SelectFilter::make('channel')
            ->label(__('resources/notify_rule/strings.logs.channel'))
            ->options(fn () => ['all' => self::channelLabel('all')] + NotifyRegistry::channelOptions());
    }

    public static function channelLabel(mixed $channel): string
    {
        return $channel === 'all'
            ? __('resources/notify_rule/strings.log.channel_all')
            : (NotifyRegistry::channel((string) $channel)?->label() ?? (string) $channel);
    }

    public static function count(): TextColumn
    {
        return TextColumn::make('count')
            ->label(__('resources/notify_rule/strings.log.fields.count'))
            ->numeric();
    }

    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label(__('resources/notify_rule/strings.logs.created_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->sortable();
    }

    public static function dateRangeFilter(): Filter
    {
        return NotifyRuleResource::createdAtFilter()->query(fn (Builder $query, array $data) => $query
            ->when($data['from'] ?? null, fn (Builder $q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($data['until'] ?? null, fn (Builder $q, $until) => $q->where('created_at', '<=', Carbon::parse($until)->endOfDay())));
    }

    public static function departmentFilter(): SelectFilter
    {
        return SelectFilter::make('department')
            ->label(__('resources/notify_rule/strings.actions.department'))
            ->options(fn () => Department::getCachedOptions())
            ->searchable()
            ->query(fn (Builder $query, array $data) => $query->when(
                $data['value'] ?? null,
                fn (Builder $q, $department) => $q->whereIn('user_id', Profile::query()->select('user_id')->where('department_id', $department)),
            ));
    }

    public static function error(): TextColumn
    {
        return TextColumn::make('error')
            ->label(__('resources/notify_rule/strings.logs.error'))
            ->limit(60)
            ->tooltip(fn ($state) => mb_strlen((string) $state) > 60 ? $state : null)
            ->placeholder('-');
    }

    public static function event(): TextColumn
    {
        return TextColumn::make('event')
            ->label(__('resources/notify_rule/strings.logs.event'))
            ->badge()
            ->color('gray')
            ->formatStateUsing(fn ($state) => NotifyEvent::tryFrom((string) $state)?->label() ?? '-')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function eventFilter(): SelectFilter
    {
        return SelectFilter::make('event')
            ->label(__('resources/notify_rule/strings.logs.event'))
            ->options(NotifyEvent::options());
    }

    public static function emptyDescription(): HtmlString
    {
        return new HtmlString('<style>.fi-ta-empty-state-icon-bg svg{transform:scaleX(-1)}</style>'.e(__('resources/notify_rule/strings.log.empty_description')));
    }

    public static function groups(): array
    {
        return [
            Group::make('notify_rule_id')
                ->label(__('resources/notify_rule/strings.log.groups.rule'))
                ->getTitleFromRecordUsing(fn (NotifyLog $record) => $record->rule?->name ?? __('resources/notify_rule/strings.log.no_rule'))
                ->titlePrefixedWithLabel(false)
                ->collapsible(),
            Group::make('run_id')
                ->label(__('resources/notify_rule/strings.log.groups.run'))
                ->getTitleFromRecordUsing(fn (NotifyLog $record) => $record->run_id ? self::runCode($record->run_id) : __('resources/notify_rule/strings.log.no_run'))
                ->collapsible(),
            Group::make('channel')
                ->label(__('resources/notify_rule/strings.log.groups.channel'))
                ->getTitleFromRecordUsing(fn (NotifyLog $record) => self::channelLabel($record->channel))
                ->titlePrefixedWithLabel(false)
                ->collapsible(),
            Group::make('source')
                ->label(__('resources/notify_rule/strings.log.groups.source'))
                ->getTitleFromRecordUsing(fn (NotifyLog $record) => self::sourceLabel($record->source))
                ->titlePrefixedWithLabel(false)
                ->collapsible(),
            Group::make('status')
                ->label(__('resources/notify_rule/strings.log.groups.status'))
                ->getTitleFromRecordUsing(fn (NotifyLog $record) => self::statusLabel($record->status))
                ->titlePrefixedWithLabel(false)
                ->collapsible(),
        ];
    }

    public static function problemsFilter(): TernaryFilter
    {
        return TernaryFilter::make('problems')
            ->label(__('resources/notify_rule/strings.log.filters.problems'))
            ->placeholder(__('resources/notify_rule/strings.log.filters.all'))
            ->trueLabel(__('resources/notify_rule/strings.log.filters.problems_true'))
            ->falseLabel(__('resources/notify_rule/strings.log.filters.problems_false'))
            ->queries(
                true: fn (Builder $query) => $query->whereIn('status', [NotifyLog::STATUS_FAILED, NotifyLog::STATUS_SKIPPED]),
                false: fn (Builder $query) => $query->whereIn('status', [NotifyLog::STATUS_SUMMARY, NotifyLog::STATUS_SENT]),
            );
    }

    public static function recordId(): TextColumn
    {
        return TextColumn::make('record_id')
            ->label(__('resources/notify_rule/strings.logs.record_id'))
            ->placeholder('-')
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function recipient(): TextColumn
    {
        return TextColumn::make('recipient')
            ->label(__('resources/notify_rule/strings.logs.recipient'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->searchable();
    }

    public static function rule(): TextColumn
    {
        return TextColumn::make('rule.name')
            ->label(__('resources/notify_rule/strings.log.fields.rule'))
            ->limit(40)
            ->tooltip(fn ($state) => mb_strlen((string) $state) > 40 ? $state : null)
            ->placeholder('—');
    }

    public static function ruleFilter(): SelectFilter
    {
        return SelectFilter::make('notify_rule_id')
            ->label(__('resources/notify_rule/strings.log.fields.rule'))
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => NotifyRule::where('name', 'like', '%'.addcslashes($search, '%_\\').'%')->orderBy('name')->limit(50)->pluck('name', 'id')->all())
            ->getOptionLabelUsing(fn ($value) => NotifyRule::find($value)?->name)
            ->indicateUsing(fn (array $state) => filled($state['value'] ?? null) && ($name = NotifyRule::find($state['value'])?->name)
                ? [Indicator::make(__('resources/notify_rule/strings.log.fields.rule').': '.$name)]
                : []);
    }

    public static function run(): TextColumn
    {
        return TextColumn::make('run_id')
            ->label(__('resources/notify_rule/strings.log.fields.run'))
            ->formatStateUsing(fn ($state) => $state ? self::runCode($state) : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->color('gray')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function runCode(string $runId): string
    {
        return substr($runId, -6);
    }

    public static function source(): TextColumn
    {
        return TextColumn::make('source')
            ->label(__('resources/notify_rule/strings.log.fields.source'))
            ->badge()
            ->color(fn ($state) => $state === NotifyLog::SOURCE_RULE ? 'gray' : 'primary')
            ->formatStateUsing(fn ($state) => self::sourceLabel($state));
    }

    public static function sourceFilter(): SelectFilter
    {
        return SelectFilter::make('source')
            ->label(__('resources/notify_rule/strings.log.fields.source'))
            ->options(array_combine(
                [NotifyLog::SOURCE_RULE, NotifyLog::SOURCE_STATUS, NotifyLog::SOURCE_RESERVATION],
                array_map(fn (string $s) => self::sourceLabel($s), [NotifyLog::SOURCE_RULE, NotifyLog::SOURCE_STATUS, NotifyLog::SOURCE_RESERVATION]),
            ));
    }

    public static function sourceLabel(mixed $source): string
    {
        return __("resources/notify_rule/strings.badges.source_{$source}");
    }

    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('resources/notify_rule/strings.logs.status'))
            ->badge()
            ->color(fn ($state) => self::STATUS_COLORS[$state] ?? 'gray')
            ->formatStateUsing(fn ($state) => self::statusLabel($state));
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__('resources/notify_rule/strings.logs.status'))
            ->options(array_combine(
                array_keys(self::STATUS_COLORS),
                array_map(fn (string $s) => self::statusLabel($s), array_keys(self::STATUS_COLORS)),
            ))
            ->placeholder(__('resources/notify_rule/strings.log.filters.all'))
            ->default(NotifyLog::STATUS_SUMMARY);
    }

    public static function statusLabel(mixed $status): string
    {
        return __("resources/notify_rule/strings.badges.{$status}");
    }

    public static function user(): TextColumn
    {
        return TextColumn::make('user_id')
            ->label(__('resources/notify_rule/strings.logs.user'))
            ->formatStateUsing(fn ($state) => self::userName($state))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function userFilter(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label(__('resources/notify_rule/strings.log.filters.user'))
            ->options(fn () => User::getCachedAllOptions())
            ->searchable();
    }

    public static function userName(mixed $id): string
    {
        return $id ? (once(fn () => User::getCachedAllOptions())->get($id) ?? '#'.$id) : '-';
    }
}
