<?php

namespace App\Livewire\Dashboard\Energy\Actions;

use App\Models\EnergyTest as EnergyModel;

class SubmitTestAction
{
    public function execute(array $answers, array $questions, int $monthIndex): void
    {
        $scores = collect($questions)
            ->mapWithKeys(function ($qs, $category) use ($answers) {
                $count = 0;
                for ($i = 0; $i < count($qs) - 1; $i++) {
                    if ($answers[$category][$i] ?? false) $count++;
                }
                return [$category => $count];
            })
            ->toArray();

        $scores['overall'] = array_sum($scores);

        EnergyModel::create([
            'user_id'        => auth()->id(),
            'answers'        => $answers,
            'month_index'    => $monthIndex,
            'mind_score'     => $scores['mind'],
            'emotion_score'  => $scores['emotion'],
            'physique_score' => $scores['physique'],
            'soul_score'     => $scores['soul'],
            'overall_score'  => $scores['overall'],
            'completed_at'   => now(),
        ]);
    }
}
