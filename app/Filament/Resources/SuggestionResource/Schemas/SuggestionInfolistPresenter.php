<?php

namespace App\Filament\Resources\SuggestionResource\Schemas;

use App\Filament\Resources\SuggestionResource\Enums\SuggestionStage;
use App\Livewire\Dashboard\Suggestion\Presentation\SuggestionPresenter;
use App\Models\Department;
use App\Models\Suggestion;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Storage;

class SuggestionInfolistPresenter
{
    public static function agreeCount(): TextEntry
    {
        return TextEntry::make('agree_count')
            ->label(__('resources/suggestion/strings.fields.agree_count'))
            ->badge()
            ->color('success')
            ->icon('heroicon-o-hand-thumb-up')
            ->getStateUsing(fn($record): int => (int) ($record->agree_count ?? 0));
    }

    public static function attachment(): TextEntry
    {
        return TextEntry::make('attachment')
            ->label(__('resources/suggestion/strings.fields.attachment'))
            ->placeholder('—')
            ->icon('heroicon-o-paper-clip')
            ->color('info')
            ->url(fn($record): ?string => $record->attachment ? Storage::disk('public')->url($record->attachment) : null, shouldOpenInNewTab: true)
            ->formatStateUsing(fn($state): string => $state ? basename((string) $state) : '—');
    }

    public static function comments(): TextEntry
    {
        return TextEntry::make('comments')
            ->label(__('resources/suggestion/strings.fields.comments'))
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/suggestion/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }

    public static function deadline(): TextEntry
    {
        return TextEntry::make('deadline')
            ->label(__('resources/suggestion/strings.fields.deadline'))
            ->getStateUsing(fn($record) => (new SuggestionPresenter($record))->deadlineConfig())
            ->formatStateUsing(fn($state): string => is_array($state) ? ($state['formatted'] ?? '—') : '—')
            ->color(fn($state): string => (is_array($state) && ($state['passed'] ?? false)) ? 'danger' : 'primary')
            ->alignRight()
            ->icon(IconPosition::After)
            ->icon('heroicon-o-calendar-days')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;']);
    }

    public static function departments(): TextEntry
    {
        return TextEntry::make('departments')
            ->label(__('resources/suggestion/strings.fields.departments'))
            ->badge()
            ->color('info')
            ->getStateUsing(function ($record): array {
                $map = Department::getCachedOptions()->toArray();

                return collect($record->departments ?? [])
                    ->map(fn($code): string => $map[$code] ?? (string) $code)
                    ->values()
                    ->all();
            })
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function description(): TextEntry
    {
        return TextEntry::make('description')
            ->label(__('resources/suggestion/strings.fields.description'))
            ->prose()
            ->placeholder('—')
            ->columnSpanFull();
    }

    public static function disagreeCount(): TextEntry
    {
        return TextEntry::make('disagree_count')
            ->label(__('resources/suggestion/strings.fields.disagree_count'))
            ->badge()
            ->color('danger')
            ->icon('heroicon-o-hand-thumb-down')
            ->getStateUsing(fn($record): int => (int) ($record->disagree_count ?? 0));
    }

    public static function neutralCount(): TextEntry
    {
        return TextEntry::make('neutral_count')
            ->label(__('resources/suggestion/strings.fields.neutral_count'))
            ->badge()
            ->color('gray')
            ->icon('heroicon-o-minus-circle')
            ->getStateUsing(fn($record): int => (int) ($record->neutral_count ?? 0));
    }

    public static function purpose(): TextEntry
    {
        return TextEntry::make('purpose')
            ->label(__('resources/suggestion/strings.fields.purpose'))
            ->badge()
            ->color('warning')
            ->getStateUsing(fn($record): array => collect($record->purpose ?? [])
                ->map(fn($key): string => Suggestion::PURPOSES[$key] ?? (string) $key)
                ->values()
                ->all())
            ->placeholder('—');
    }

    public static function referralActions(): TextEntry
    {
        return TextEntry::make('referral_actions')
            ->label(__('resources/suggestion/strings.fields.referral_actions'))
            ->placeholder('—')
            ->getStateUsing(fn($record): ?string => $record->reviews?->firstWhere('department_id', 'MA')?->actions)
            ->columnSpanFull();
    }

    public static function referralDepts(): TextEntry
    {
        return TextEntry::make('referral_depts')
            ->label(__('resources/suggestion/strings.fields.referral_depts'))
            ->badge()
            ->color('primary')
            ->icon('heroicon-o-arrow-uturn-right')
            ->getStateUsing(function ($record): array {
                $ceoReview = $record->reviews?->firstWhere('department_id', 'MA');

                if (! $ceoReview) {
                    return [];
                }

                $referrals = $ceoReview->referral ?? [];
                $map = Department::getCachedOptions()->toArray();

                return collect($referrals)
                    ->map(fn($code): string => $map[$code] ?? (string) $code)
                    ->values()
                    ->all();
            })
            ->placeholder(__('resources/suggestion/strings.infolist.no_referral'))
            ->columnSpanFull();
    }

    public static function reviews(): ViewEntry
    {
        return ViewEntry::make('reviews_timeline')
            ->label(__('resources/suggestion/strings.infolist.section_reviews'))
            ->view('filament.resources.suggestion.reviews')
            ->state(fn($record): array => [
                'items' => (new SuggestionPresenter($record))->reviewItems(),
                'empty' => __('resources/suggestion/strings.infolist.no_reviews'),
            ])
            ->columnSpanFull();
    }

    public static function rule(): TextEntry
    {
        return TextEntry::make('rule')
            ->label(__('resources/suggestion/strings.fields.rule'))
            ->badge()
            ->color('info')
            ->getStateUsing(fn($record): array => collect($record->rule ?? [])
                ->map(fn($key): string => Suggestion::RULES[$key] ?? (string) $key)
                ->values()
                ->all())
            ->placeholder('—');
    }

    public static function selfFill(): IconEntry
    {
        return IconEntry::make('self_fill')
            ->label(__('resources/suggestion/strings.fields.self_fill'))
            ->boolean();
    }

    public static function sentToCeo(): IconEntry
    {
        return IconEntry::make('sent_to_ceo')
            ->label(__('resources/suggestion/strings.fields.sent_to_ceo'))
            ->boolean()
            ->getStateUsing(fn($record): bool => (new SuggestionPresenter($record))->sentToCeo());
    }

    public static function serial(): TextEntry
    {
        return TextEntry::make('serial')
            ->label(__('resources/suggestion/strings.fields.serial'))
            ->badge()
            ->color('gray')
            ->copyable()
            ->alignRight()
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->getStateUsing(fn($record): string => sprintf(
                'SN-%s-%06d',
                $record->created_at?->format('Ymd') ?? '00000000',
                $record->id,
            ));
    }

    public static function stage(): TextEntry
    {
        return TextEntry::make('stage')
            ->label(__('resources/suggestion/strings.fields.stage'))
            ->getStateUsing(fn($record) => SuggestionStage::tryFrom($record->stage))
            ->badge();
    }

    public static function submitter(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/suggestion/strings.fields.submitter'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function submitterDept(): TextEntry
    {
        return TextEntry::make('user.profile.department.description')
            ->label(__('resources/suggestion/strings.fields.submitter_dept'))
            ->placeholder('—')
            ->icon('heroicon-o-building-office-2');
    }

    public static function title(): TextEntry
    {
        return TextEntry::make('title')
            ->label(__('resources/suggestion/strings.fields.title'))
            ->size(TextSize::Large)
            ->weight('bold')
            ->columnSpanFull();
    }

    public static function updatedAt(): TextEntry
    {
        return TextEntry::make('updated_at')
            ->label(__('resources/suggestion/strings.fields.updated_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->color('gray')
            ->icon('heroicon-o-arrow-path');
    }

    public static function workflow(): ViewEntry
    {
        return ViewEntry::make('workflow_timeline')
            ->label(__('resources/suggestion/strings.infolist.section_workflow'))
            ->view('filament.resources.suggestion.workflow')
            ->state(fn($record): array => [
                'items' => (new SuggestionPresenter($record))->workflowItems(),
                'current_step' => (new SuggestionPresenter($record))->currentStep(),
            ])
            ->columnSpanFull();
    }
}
