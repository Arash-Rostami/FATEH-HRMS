<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas;

use App\Enums\NotifyEvent;
use App\Models\NotifyLog;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotifyLogInfolistPresenter
{
    private const PROBLEMS_LIMIT = 50;

    public static function channel(): TextEntry
    {
        return TextEntry::make('channel')
            ->label(__('resources/notify_rule/strings.logs.channel'))
            ->badge()
            ->color('info')
            ->formatStateUsing(fn ($state) => NotifyLogTablePresenter::channelLabel($state))
            ->visible(fn (?Model $record) => ! self::isSummary($record));
    }

    public static function count(): TextEntry
    {
        return TextEntry::make('count')
            ->label(__('resources/notify_rule/strings.log.fields.count'))
            ->numeric()
            ->visible(fn (?Model $record) => ! self::isSummary($record));
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/notify_rule/strings.logs.created_at'))
            ->formatStateUsing(fn ($state) => $state ? toJalali($state, 'Y/m/d H:i') : '-')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->icon('heroicon-o-clock')
            ->color('gray');
    }

    public static function error(): TextEntry
    {
        return TextEntry::make('error')
            ->label(__('resources/notify_rule/strings.logs.error'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder('-')
            ->visible(fn (?Model $record) => ! self::isSummary($record));
    }

    public static function event(): TextEntry
    {
        return TextEntry::make('event')
            ->label(__('resources/notify_rule/strings.logs.event'))
            ->badge()
            ->color('gray')
            ->formatStateUsing(fn ($state) => NotifyEvent::tryFrom((string) $state)?->label() ?? '-')
            ->visible(fn (?Model $record) => $record?->source === NotifyLog::SOURCE_RULE);
    }

    public static function failed(): TextEntry
    {
        return self::total('failed', 'danger');
    }

    public static function problems(): TextEntry
    {
        return TextEntry::make('problems')
            ->label(__('resources/notify_rule/strings.log.fields.problems'))
            ->hint(__('resources/notify_rule/strings.log.fields.problems_hint', ['count' => self::PROBLEMS_LIMIT]))
            ->state(fn (Model $record) => self::stats($record)->get('problems'))
            ->listWithLineBreaks()
            ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-wrap; unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder(__('resources/notify_rule/strings.log.fields.problems_none'))
            ->visible(fn (?Model $record) => self::isSummary($record));
    }

    public static function recipient(): TextEntry
    {
        return TextEntry::make('recipient')
            ->label(__('resources/notify_rule/strings.logs.recipient'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->visible(fn (?Model $record) => ! self::isSummary($record));
    }

    public static function recordId(): TextEntry
    {
        return TextEntry::make('record_id')
            ->label(__('resources/notify_rule/strings.logs.record_id'))
            ->placeholder('-')
            ->visible(fn (?Model $record) => $record?->source === NotifyLog::SOURCE_RULE);
    }

    public static function rule(): TextEntry
    {
        return TextEntry::make('rule.name')
            ->label(__('resources/notify_rule/strings.log.fields.rule'))
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->placeholder('—')
            ->visible(fn (?Model $record) => $record?->source === NotifyLog::SOURCE_RULE);
    }

    public static function runError(): TextEntry
    {
        return TextEntry::make('run_error')
            ->label(__('resources/notify_rule/strings.log.fields.run_error'))
            ->state(fn (Model $record) => $record->error)
            ->extraAttributes(['dir' => 'auto', 'style' => 'unicode-bidi: isolate;'])
            ->columnSpanFull()
            ->placeholder(__('resources/notify_rule/strings.log.fields.run_error_none'))
            ->visible(fn (?Model $record) => self::isSummary($record));
    }

    public static function sender(): TextEntry
    {
        return TextEntry::make('sender_id')
            ->label(__('resources/notify_rule/strings.log.fields.sender'))
            ->formatStateUsing(fn ($state) => NotifyLogTablePresenter::userName($state))
            ->visible(fn (?Model $record) => $record?->source !== NotifyLog::SOURCE_RULE);
    }

    public static function sent(): TextEntry
    {
        return self::total('sent', 'success');
    }

    public static function skipped(): TextEntry
    {
        return self::total('skipped', 'warning');
    }

    public static function source(): TextEntry
    {
        return TextEntry::make('source')
            ->label(__('resources/notify_rule/strings.log.fields.source'))
            ->badge()
            ->color(fn ($state) => $state === NotifyLog::SOURCE_RULE ? 'gray' : 'primary')
            ->formatStateUsing(fn ($state) => NotifyLogTablePresenter::sourceLabel($state));
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/notify_rule/strings.logs.status'))
            ->badge()
            ->color(fn ($state) => NotifyLogTablePresenter::STATUS_COLORS[$state] ?? 'gray')
            ->formatStateUsing(fn ($state) => NotifyLogTablePresenter::statusLabel($state));
    }

    public static function targeted(): TextEntry
    {
        return TextEntry::make('targeted')
            ->label(__('resources/notify_rule/strings.log.fields.targeted'))
            ->state(fn (Model $record) => $record->count)
            ->numeric()
            ->visible(fn (?Model $record) => self::isSummary($record));
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user_id')
            ->label(__('resources/notify_rule/strings.logs.user'))
            ->formatStateUsing(fn ($state) => NotifyLogTablePresenter::userName($state))
            ->visible(fn (?Model $record) => ! self::isSummary($record));
    }

    private static function isSummary(?Model $record): bool
    {
        return $record?->status === NotifyLog::STATUS_SUMMARY;
    }

    private static function stats(Model $record): Collection
    {
        if ($record->relationLoaded('runStats')) {
            return $record->getRelation('runStats');
        }

        $totals = $record->run_id
            ? NotifyLog::query()
                ->where('run_id', $record->run_id)
                ->where('status', '!=', NotifyLog::STATUS_SUMMARY)
                ->selectRaw('status, SUM(count) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        $problems = $record->run_id
            ? NotifyLog::query()
                ->where('run_id', $record->run_id)
                ->whereIn('status', [NotifyLog::STATUS_FAILED, NotifyLog::STATUS_SKIPPED])
                ->orderBy('id')
                ->limit(self::PROBLEMS_LIMIT)
                ->get(['user_id', 'recipient', 'status', 'error'])
                ->map(fn (NotifyLog $row) => ($row->user_id ? NotifyLogTablePresenter::userName($row->user_id) : $row->recipient)
                    .' ('.NotifyLogTablePresenter::statusLabel($row->status).')'
                    .($row->error ? ': '.$row->error : ''))
                ->all()
            : [];

        $stats = collect([
            NotifyLog::STATUS_SENT => (int) $totals->get(NotifyLog::STATUS_SENT, 0),
            NotifyLog::STATUS_FAILED => (int) $totals->get(NotifyLog::STATUS_FAILED, 0),
            NotifyLog::STATUS_SKIPPED => (int) $totals->get(NotifyLog::STATUS_SKIPPED, 0),
            'problems' => $problems,
        ]);

        $record->setRelation('runStats', $stats);

        return $stats;
    }

    private static function total(string $status, string $color): TextEntry
    {
        return TextEntry::make("run_{$status}")
            ->label(__("resources/notify_rule/strings.log.fields.{$status}"))
            ->state(fn (Model $record) => self::stats($record)->get($status))
            ->numeric()
            ->badge()
            ->color($color)
            ->visible(fn (?Model $record) => self::isSummary($record));
    }
}
