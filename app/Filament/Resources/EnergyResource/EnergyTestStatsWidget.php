<?php

namespace App\Filament\Resources\EnergyTestResource\Widgets;

use App\Models\EnergyTest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EnergyTestStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totals = EnergyTest::selectRaw('
            COUNT(*) as total,
            COALESCE(ROUND(AVG(overall_score), 1), 0) as avg_overall,
            COALESCE(ROUND(AVG(mind_score), 1), 0) as avg_mind,
            COALESCE(ROUND(AVG(emotion_score), 1), 0) as avg_emotion,
            COALESCE(ROUND(AVG(physique_score), 1), 0) as avg_physique,
            COALESCE(ROUND(AVG(soul_score), 1), 0) as avg_soul,
            SUM(CASE WHEN overall_score < 45 THEN 1 ELSE 0 END) as low_count,
            SUM(CASE WHEN completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as last_month_count
        ')->first();

        return [
            Stat::make(__('resources/energy_test/strings.stats.total'), $totals->total)
                ->icon('heroicon-o-bolt')
                ->color('primary'),

            Stat::make(__('resources/energy_test/strings.stats.avg_overall'), $totals->avg_overall)
                ->icon('heroicon-o-chart-bar')
                ->color($totals->avg_overall >= 70 ? 'success' : ($totals->avg_overall >= 45 ? 'warning' : 'danger'))
                ->description(__('resources/energy_test/strings.stats.out_of_100')),

            Stat::make(__('resources/energy_test/strings.stats.dimension_averages'),
                "🧠 {$totals->avg_mind} | ❤️ {$totals->avg_emotion} | 💪 {$totals->avg_physique} | ✨ {$totals->avg_soul}"
            )
                ->icon('heroicon-o-squares-2x2')
                ->color('info'),

            Stat::make(__('resources/energy_test/strings.stats.low_scores'), $totals->low_count)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($totals->low_count > 0 ? 'danger' : 'success')
                ->description(__('resources/energy_test/strings.stats.below_45')),

            Stat::make(__('resources/energy_test/strings.stats.last_month'), $totals->last_month_count)
                ->icon('heroicon-o-calendar')
                ->color('gray'),
        ];
    }
}
