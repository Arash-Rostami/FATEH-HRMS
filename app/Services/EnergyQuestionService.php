<?php

namespace App\Services;

class EnergyQuestionService
{

    public function getQuestions(): array
    {
        return $this->getQuestionsForMonth($this->getMonthIndex());
    }

    public function getQuestionsForMonth(int $monthIndex): array
    {
        $monthIndex = max(0, min(11, $monthIndex));
        $bank = $this->questionBank();

        return [
            'monthIndex' => $monthIndex,
            'questions' => [
                'physique' => $bank['physique'][$monthIndex],
                'emotion' => $bank['emotion'][$monthIndex],
                'mind' => $bank['mind'][$monthIndex],
                'soul' => $bank['soul'][$monthIndex],
            ],
            'prompts' => [
                'physique' => '🏋️‍♂️ چطورید؟ درباره‌ی انرژی جسمی‌تون بگید، کدوم گزینه بیشتر توصیف‌کننده‌ی وضعیت‌تونه؟',
                'emotion' => '❤️ حال‌وهوای احساسی‌تون این روزها چطوره؟ گزینه‌ای که با احساستون هم‌خوانی داره رو انتخاب کنید.',
                'mind' => '🧠 وضعیت تمرکز و ذهن‌تون چطوره؟ کدوم مورد بیشتر صحت داره؟',
                'soul' => '✨ چقدر حس می‌کنید کارهاتون با ارزش‌ها و هدف‌هاتون هم‌خوانی داره؟ گزینه‌ی مناسب رو انتخاب کنید:',
            ],
            'sections' => [
                'physique' => '🏋️‍♂️ جسم',
                'emotion' => '❤️ احساس',
                'mind' => '🧠 ذهن',
                'soul' => '✨ روح',
            ],
        ];
    }

    private function getMonthIndex(): int
    {
        return (int)now()->format('n') - 1; // 0..11
    }

    private function questionBank(): array
    {
        return config('energy_questions', []);
    }
}
