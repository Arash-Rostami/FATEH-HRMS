<?php

namespace App\Filament\Resources\AiReportResource\Schemas;

use App\Livewire\Dashboard\AiReport\Presentation\AiReportPresenter;
use App\Models\AiReport;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class AiReportInfolistPresenter
{
    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/ai_report/strings.fields.created_at'))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-calendar')
            ->color('gray');
    }

    public static function failReason(): TextEntry
    {
        return TextEntry::make('fail_reason')
            ->label(__('resources/ai_report/strings.fields.fail_reason'))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => filled($state) ? (new AiReportPresenter)->failReason($state) : '—')
            ->color(fn($state) => filled($state) ? 'danger' : 'gray');
    }

    public static function lastPrompt(): TextEntry
    {
        return TextEntry::make('last_prompt')
            ->label(__('resources/ai_report/strings.fields.last_prompt'))
            ->placeholder('—')
            ->getStateUsing(fn (AiReport $r) => $r->lastVersion()['prompt'] ?? null)
            ->columnSpanFull()
            ->color('gray');
    }

    public static function model(): TextEntry
    {
        return TextEntry::make('model')
            ->label(__('resources/ai_report/strings.fields.model'))
            ->placeholder('—')
            ->color('gray');
    }

    public static function owner(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/ai_report/strings.fields.owner'))
            ->placeholder('—')
            ->icon('heroicon-o-user')
            ->weight(FontWeight::Medium);
    }

    public static function preview(): TextEntry
    {
        return TextEntry::make('spec')
            ->label(__('resources/ai_report/strings.fields.preview'))
            ->getStateUsing(fn(AiReport $r) => $r->spec
                ? new HtmlString(Blade::render('<x-dashboard.ai-report :spec="$spec" :dir="$dir" :author="$author" :live="false"/>', ['spec' => $r->spec, 'dir' => (new AiReportPresenter)->dir($r->spec), 'author' => $r->user?->name]))
                : '—')
            ->columnSpanFull();
    }

    public static function recipients(): TextEntry
    {
        return TextEntry::make('recipients')
            ->label(__('resources/ai_report/strings.fields.recipients'))
            ->placeholder('—')
            ->getStateUsing(function (AiReport $record): string {
                $names = User::getCachedAllOptions()->all();

                return $record->recipients
                    ? implode('، ', array_map(fn($id) => $names[$id] ?? 'کاربر حذف‌شده', $record->recipients))
                    : '—';
            })
            ->icon('heroicon-o-users');
    }

    public static function savedAt(): TextEntry
    {
        return TextEntry::make('saved_at')
            ->label(__('resources/ai_report/strings.fields.saved_at'))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->alignRight()
            ->iconPosition(IconPosition::After)
            ->icon('heroicon-o-clock')
            ->color('gray');
    }

    public static function skippedFiles(): TextEntry
    {
        return TextEntry::make('skipped_files')
            ->label(__('resources/ai_report/strings.fields.skipped_files'))
            ->placeholder('—')
            ->getStateUsing(fn (AiReport $r): array => array_map(
                fn (array $f): string => $f['name'] . ' — ' . $f['label'],
                (new AiReportPresenter)->skippedFiles($r),
            ))
            ->listWithLineBreaks()
            ->columnSpanFull()
            ->color('warning');
    }

    public static function status(): TextEntry
    {
        return TextEntry::make('status')
            ->label(__('resources/ai_report/strings.fields.status'))
            ->badge()
            ->formatStateUsing(fn(string $state): string => __("resources/ai_report/strings.statuses.{$state}"))
            ->color(fn(string $state): string => AiReport::STATUS_COLORS[$state] ?? 'info')
            ->icon(fn(string $state): string => AiReport::STATUS_ICONS[$state] ?? 'heroicon-o-arrow-path');
    }

    public static function tokensTotal(): TextEntry
    {
        return TextEntry::make('tokens_total')
            ->label(__('resources/ai_report/strings.fields.tokens_total'))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state) : '—')
            ->color('gray');
    }

    public static function versionsCount(): TextEntry
    {
        return TextEntry::make('versions')
            ->label(__('resources/ai_report/strings.fields.versions_count'))
            ->placeholder('—')
            ->getStateUsing(fn(AiReport $record) => convertToPersian(count($record->versions ?? [])))
            ->color('gray');
    }
}
