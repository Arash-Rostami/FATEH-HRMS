<?php

namespace App\Filament\Resources\EnergyTestResource\Schemas;

use Filament\Infolists\Components\TextEntry;

class EnergyTestInfolistPresenter
{
    private static function scoreColor(?float $score): string
    {
        return match (true) {
            $score === null => 'gray',
            $score >= 70    => 'success',
            $score >= 45    => 'warning',
            default         => 'danger',
        };
    }

    private static function scoreEntry(string $field, string $labelKey): TextEntry
    {
        return TextEntry::make($field)
            ->label(__("resources/energy_test/strings.fields.{$labelKey}"))
            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 1) : '—')
            ->color(fn($state) => self::scoreColor($state))
            ->badge()
            ->size(TextEntry\TextEntrySize::Large);
    }

    public static function user(): TextEntry
    {
        return TextEntry::make('user.name')
            ->label(__('resources/energy_test/strings.fields.user'))
            ->placeholder('—')
            ->icon('heroicon-o-user');
    }

    public static function monthIndex(): TextEntry
    {
        return TextEntry::make('month_index')
            ->label(__('resources/energy_test/strings.fields.month_index'))
            ->badge()
            ->color('gray');
    }

    public static function completedAt(): TextEntry
    {
        return TextEntry::make('completed_at')
            ->label(__('resources/energy_test/strings.fields.completed_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->icon('heroicon-o-clock');
    }

    public static function overallScore(): TextEntry
    {
        return self::scoreEntry('overall_score', 'overall_score');
    }

    public static function mindScore(): TextEntry
    {
        return self::scoreEntry('mind_score', 'mind_score');
    }

    public static function emotionScore(): TextEntry
    {
        return self::scoreEntry('emotion_score', 'emotion_score');
    }

    public static function physiqueScore(): TextEntry
    {
        return self::scoreEntry('physique_score', 'physique_score');
    }

    public static function soulScore(): TextEntry
    {
        return self::scoreEntry('soul_score', 'soul_score');
    }

    public static function answers(): TextEntry
    {
        return TextEntry::make('answers')
            ->label(__('resources/energy_test/strings.fields.answers'))
            ->getStateUsing(function ($record): string {
                $answers = $record->answers ?? [];
                if (empty($answers)) return '—';
                return collect($answers)
                    ->map(fn($categoryAnswers, $category) =>
                        $category . ': ' . collect($categoryAnswers)
                            ->filter()
                            ->count() . '/' . count($categoryAnswers)
                    )
                    ->implode(' | ');
            })
            ->columnSpanFull()
            ->color('gray');
    }

    public static function createdAt(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label(__('resources/energy_test/strings.fields.created_at'))
            ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
            ->color('gray')
            ->icon('heroicon-o-clock');
    }
}
