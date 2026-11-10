<?php

namespace App\Filament\Resources\CorrespondenceResource\Schemas;

use App\Models\Correspondence;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

class CorrespondenceInfolistPresenter
{
    public static function awaited(): TextEntry
    {
        return TextEntry::make('awaiting_user_id')
            ->label(__('resources/correspondence/strings.admin.fields.awaited'))
            ->placeholder('—')
            ->getStateUsing(fn (Correspondence $record): ?string => $record->awaiting_user_id
                ? (string) CorrespondenceTablePresenter::names()->get($record->awaiting_user_id, __('resources/correspondence/strings.admin.fields.deleted_user'))
                : null)
            ->color('gray');
    }

    public static function classification(): TextEntry
    {
        return TextEntry::make('classification')
            ->label(__('resources/correspondence/strings.fields.classification'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.classification.{$state}"))
            ->color(fn (string $state): string => CorrespondenceTablePresenter::classificationColor($state))
            ->icon(fn (string $state): string => $state !== Correspondence::CLASSIFICATION_NORMAL
                ? 'heroicon-o-lock-closed'
                : 'heroicon-o-lock-open');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/correspondence/strings.admin.fields.created_at'))
            ->placeholder('—')
            ->formatStateUsing(fn ($state) => toJalali($state, 'Y/m/d H:i'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-calendar')
            ->color('gray');
    }

    public static function deadline(): TextEntry
    {
        return TextEntry::make('deadline_at')
            ->label(__('resources/correspondence/strings.admin.fields.deadline'))
            ->placeholder('—')
            ->formatStateUsing(fn ($state) => toJalali($state, 'Y/m/d H:i'))
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color(fn (Correspondence $record): string => $record->status === Correspondence::STATUS_SHARED && $record->deadline_at?->isPast() ? 'danger' : 'gray');
    }

    public static function deliveryTag(): TextEntry
    {
        return TextEntry::make('delivery_tag')
            ->label(__('resources/correspondence/strings.admin.fields.delivery'))
            ->badge()
            ->color('warning')
            ->icon('heroicon-o-hand-raised')
            ->getStateUsing(fn (): string => __('resources/correspondence/strings.delivery.manual'))
            ->visible(fn (Correspondence $record): bool => $record->status === Correspondence::STATUS_APPROVED
                && ! collect((array) $record->parties)->contains(fn ($party): bool => is_array($party)
                    && blank($party['user_id'] ?? null)
                    && filled($party['delivery'] ?? null)
                    && filled($party['sent_at'] ?? null)));
    }

    public static function direction(): TextEntry
    {
        return TextEntry::make('direction')
            ->label(__('resources/correspondence/strings.filters.direction'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.direction.{$state}"))
            ->icon(fn (string $state): string => $state === Correspondence::DIRECTION_IN
                ? 'heroicon-o-inbox-arrow-down'
                : 'heroicon-o-paper-airplane');
    }

    public static function events(): TextEntry
    {
        return TextEntry::make('events')
            ->label(__('resources/correspondence/strings.admin.fields.timeline'))
            ->placeholder('—')
            ->getStateUsing(fn (Correspondence $record): array => self::timelineLines($record))
            ->listWithLineBreaks()
            ->columnSpanFull()
            ->color('gray');
    }

    public static function isNormal(Correspondence $record): bool
    {
        return $record->classification === Correspondence::CLASSIFICATION_NORMAL;
    }

    public static function letterhead(): TextEntry
    {
        return TextEntry::make('letterhead.name')
            ->label(__('resources/correspondence/strings.admin.fields.letterhead'))
            ->placeholder('—')
            ->color('gray');
    }

    public static function mode(): TextEntry
    {
        return TextEntry::make('mode')
            ->label(__('resources/correspondence/strings.admin.fields.mode'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.mode.{$state}"));
    }

    public static function number(): TextEntry
    {
        return TextEntry::make('number')
            ->label(__('resources/correspondence/strings.fields.number'))
            ->placeholder('—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->color('gray');
    }

    public static function owner(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/correspondence/strings.fields.owner'))
            ->placeholder('—')
            ->icon('heroicon-o-user')
            ->weight(FontWeight::Medium);
    }

    public static function parties(): TextEntry
    {
        return TextEntry::make('parties')
            ->label(__('resources/correspondence/strings.admin.fields.parties'))
            ->placeholder('—')
            ->getStateUsing(fn (Correspondence $record): array => self::partyLines($record))
            ->listWithLineBreaks()
            ->columnSpanFull()
            ->color('gray');
    }

    public static function preview(): TextEntry
    {
        return TextEntry::make('spec')
            ->hiddenLabel()
            ->getStateUsing(fn (Correspondence $record): HtmlString => new HtmlString(
                View::make('livewire.dashboard.correspondence.paper', ['letter' => $record, 'mode' => 'link'])->render(),
            ))
            ->columnSpanFull();
    }

    public static function routeMode(): TextEntry
    {
        return TextEntry::make('route_mode')
            ->label(__('resources/correspondence/strings.admin.fields.route_mode'))
            ->badge()
            ->placeholder('—')
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.route_mode.{$state}"));
    }

    public static function senderCategory(): TextEntry
    {
        return TextEntry::make('sender_category')
            ->label(__('resources/correspondence/strings.admin.fields.sender_category'))
            ->placeholder('—')
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.sender_category.{$state}"))
            ->color('gray');
    }

    public static function signMode(): TextEntry
    {
        return TextEntry::make('sign_mode')
            ->label(__('resources/correspondence/strings.admin.fields.sign_mode'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.sign_mode.{$state}"));
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/correspondence/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.status.{$state}"))
            ->color(fn (string $state): string => Correspondence::STATUS_COLORS[$state] ?? 'info')
            ->icon(fn (string $state): string => Correspondence::STATUS_ICONS[$state] ?? 'heroicon-o-envelope');
    }

    public static function subject(): TextEntry
    {
        return TextEntry::make('subject')
            ->label(__('resources/correspondence/strings.fields.subject'))
            ->placeholder('—')
            ->formatStateUsing(fn (string $state, Correspondence $record): string => self::isNormal($record)
                ? $state
                : __('resources/correspondence/strings.admin.masked'))
            ->color(fn (Correspondence $record): string => self::isNormal($record) ? 'gray' : 'warning')
            ->columnSpan(2);
    }

    public static function template(): TextEntry
    {
        return TextEntry::make('is_template')
            ->label(__('resources/correspondence/strings.filters.template'))
            ->badge()
            ->formatStateUsing(fn (bool $state): string => $state
                ? __('resources/correspondence/strings.admin.filters.templates_only')
                : __('resources/correspondence/strings.admin.filters.letters_only'))
            ->color(fn (bool $state): string => $state ? 'info' : 'gray')
            ->icon(fn (bool $state): string => $state ? 'heroicon-o-bookmark' : 'heroicon-o-document');
    }

    public static function templateScope(): TextEntry
    {
        return TextEntry::make('meta.template_scope')
            ->label(__('resources/correspondence/strings.admin.fields.template_scope'))
            ->badge()
            ->getStateUsing(fn (Correspondence $record): string => $record->meta['template_scope'] ?? 'private')
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.template_scope.{$state}"))
            ->visible(fn (Correspondence $record): bool => (bool) $record->is_template);
    }

    public static function type(): TextEntry
    {
        return TextEntry::make('type')
            ->label(__('resources/correspondence/strings.fields.type'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.types.{$state}"));
    }

    public static function urgency(): TextEntry
    {
        return TextEntry::make('urgency')
            ->label(__('resources/correspondence/strings.fields.urgency'))
            ->badge()
            ->formatStateUsing(fn (string $state): string => __("resources/correspondence/strings.urgency.{$state}"))
            ->color(fn (string $state): string => CorrespondenceTablePresenter::urgencyColor($state));
    }

    public static function versions(): TextEntry
    {
        return TextEntry::make('versions')
            ->label(__('resources/correspondence/strings.admin.fields.versions'))
            ->placeholder('—')
            ->getStateUsing(fn (Correspondence $record): array => self::versionLines($record))
            ->listWithLineBreaks()
            ->columnSpanFull()
            ->color('gray');
    }

    private static function partyLines(Correspondence $record): array
    {
        $names = CorrespondenceTablePresenter::names();
        $roles = __('resources/correspondence/strings.roles');
        $modes = __('resources/correspondence/strings.delivery_modes');
        $lines = [];

        foreach ((array) $record->parties as $party) {
            if (! is_array($party)) {
                continue;
            }

            $internal = filled($party['user_id'] ?? null);
            $who = $internal
                ? $names->get((int) $party['user_id'], __('resources/correspondence/strings.admin.fields.deleted_user'))
                : (trim(($party['name'] ?? '') . ' ' . ($party['org'] ?? '')) ?: '—');

            $bits = [$who . ' (' . ($roles[$party['role'] ?? ''] ?? '—') . ')'];

            if (! $internal && filled($party['delivery'] ?? null)) {
                $bits[] = $modes[$party['delivery']] ?? $party['delivery'];
            }

            if (! empty($party['print_in_cc'])) {
                $bits[] = __('resources/correspondence/strings.fields.print_in_cc');
            }

            if (filled($party['sent_at'] ?? null)) {
                $bits[] = __('resources/correspondence/strings.delivery.sent') . ' ' . toJalali($party['sent_at'], 'Y/m/d H:i');
            }

            if (filled($party['opened_at'] ?? null)) {
                $bits[] = __('resources/correspondence/strings.delivery.opened') . ' ' . toJalali($party['opened_at'], 'Y/m/d H:i');
            }

            $lines[] = implode(' — ', $bits);
        }

        return $lines;
    }

    private static function timelineLines(Correspondence $record): array
    {
        $names = CorrespondenceTablePresenter::names();
        $system = __('resources/correspondence/strings.admin.fields.system');
        $masked = __('resources/correspondence/strings.admin.masked');
        $normal = self::isNormal($record);
        $lines = [];

        foreach (array_reverse((array) $record->events) as $event) {
            if (! is_array($event)) {
                continue;
            }

            $action = (string) ($event['action'] ?? '');
            $key = "resources/correspondence/strings.events.{$action}";

            $lines[] = __(Lang::has($key) ? $key : 'resources/correspondence/strings.admin.events_generic', [
                'name' => $names->get((int) ($event['user_id'] ?? 0), $system),
                'time' => filled($event['at'] ?? null) ? toJalali($event['at'], 'Y/m/d H:i') : '—',
                'target' => isset($event['target_id'])
                    ? $names->get((int) $event['target_id'], '—')
                    : (filled($event['target'] ?? null) ? (string) $event['target'] : '—'),
                'reason' => $normal ? (filled($event['reason'] ?? null) ? (string) $event['reason'] : '—') : $masked,
                'text' => $normal ? (filled($event['text'] ?? null) ? (string) $event['text'] : '—') : $masked,
                'action' => $action,
            ]);
        }

        return $lines;
    }

    private static function versionLines(Correspondence $record): array
    {
        $names = CorrespondenceTablePresenter::names();
        $system = __('resources/correspondence/strings.admin.fields.system');
        $versions = array_values(array_filter((array) $record->versions, fn ($version): bool => is_array($version)));
        $lines = [];

        foreach (array_reverse($versions, true) as $index => $version) {
            $lines[] = convertToPersian($index + 1)
                . ' — ' . $names->get((int) ($version['user_id'] ?? 0), $system)
                . ' — ' . toJalali($version['at'] ?? $record->updated_at, 'Y/m/d H:i');
        }

        return $lines;
    }
}
