<?php

namespace App\Filament\Resources\EnergyResource\Schemas;

use App\Models\EnergyTest;
use App\Services\EnergyQuestionService;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;

class EnergyTestInfolistPresenter
{
    public static function answers(): ViewEntry
    {
        return ViewEntry::make('answers')
            ->label(__('resources/energy/strings.fields.user_averages'))
            ->view('filament.resources.energy.answers')
            ->state(fn($record): array => EnergyTest::getAverageScoresForUser($record->user_id))
            ->columnSpanFull();
    }

    public static function completedAt(): TextEntry
    {
        return TextEntry::make('completed_at')
            ->label(__('resources/energy/strings.fields.completed_at'))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
            ->icon('heroicon-o-clock')
            ->iconPosition(IconPosition::After)
            ->alignEnd()
            ->tooltip(fn($state) => $state?->diffForHumans())
            ->color('gray');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/energy/strings.fields.created_at'))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d') : '—')
            ->icon('heroicon-o-calendar')
            ->tooltip(fn($state) => $state?->diffForHumans())
            ->color('gray');
    }

    public static function emotionScore(): TextEntry
    {
        return self::scoreEntry('emotion_score', '❤️');
    }

    public static function mindScore(): TextEntry
    {
        return self::scoreEntry('mind_score', '🧠');
    }

    public static function overallScore(): TextEntry
    {
        return TextEntry::make('overall_score')
            ->label(__('resources/energy/strings.fields.overall_score'))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) . ' / 100' : '—')
            ->weight(FontWeight::Bold)
            ->size(TextSize::Large)
            ->badge()
            ->columnSpanFull();
    }

    public static function physiqueScore(): TextEntry
    {
        return self::scoreEntry('physique_score', '🏋️‍♂️');
    }

    public static function questionsDetail(): ViewEntry
    {
        return ViewEntry::make('questions_detail')
            ->view('filament.resources.energy.test')
            ->state(function ($record): array {
                $data = app(EnergyQuestionService::class)
                    ->getQuestionsForMonth($record->month_index ?? 0);

                return [
                    'questions' => $data['questions'],
                    'sections' => $data['sections'],
                    'prompts' => $data['prompts'],
                    'answers' => $record->answers ?? [],
                ];
            })
            ->columnSpanFull();
    }

    public static function soulScore(): TextEntry
    {
        return self::scoreEntry('soul_score', '✨');
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/energy/strings.fields.user'))
            ->placeholder('—')
            ->icon('heroicon-o-user')
            ->weight(FontWeight::Medium);
    }


    private static function scoreEntry(string $field, string $emoji = ''): TextEntry
    {
        return TextEntry::make($field)
            ->label(__("resources/energy/strings.fields.{$field}"))
            ->placeholder('—')
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => match (true) {
                $state === null => 'gray',
                $state >= 70 => 'danger',
                $state >= 45 => 'warning',
                default => 'success',
            })
            ->badge()
            ->size(TextSize::Medium);
    }
}
