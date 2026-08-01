<?php

namespace App\Livewire\Dashboard\Energy\Actions;

use App\Models\EnergyTest as EnergyModel;
use Illuminate\Support\Facades\DB;

class SubmitTestAction
{
    public function execute(array $answers, array $questions, int $monthIndex): void
    {
        DB::transaction(function () use ($answers, $questions, $monthIndex): void {
            if (!EnergyModel::canSubmit(auth()->id(), lock: true)) {
                throw new \InvalidArgumentException('پاسخ‌نامه در ۲۵ روز گذشته ثبت شده است.');
            }

            if (collect($questions)->contains(fn($qs, $category) => !in_array(true, $answers[$category] ?? [], true))) {
                throw new \InvalidArgumentException('پاسخ به همه دسته‌ها الزامی است.');
            }

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
                'user_id' => auth()->id(),
                'answers' => $answers,
                'month_index' => $monthIndex,
                'mind_score' => $scores['mind'],
                'emotion_score' => $scores['emotion'],
                'physique_score' => $scores['physique'],
                'soul_score' => $scores['soul'],
                'overall_score' => $scores['overall'],
                'completed_at' => now(),
            ]);
        });
    }
}
