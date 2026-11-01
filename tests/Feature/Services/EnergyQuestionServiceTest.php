<?php

namespace Tests\Feature\Services;

use App\Services\EnergyQuestionService;
use Tests\TestCase;

class EnergyQuestionServiceTest extends TestCase
{
    private EnergyQuestionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EnergyQuestionService::class);
    }

    public function test_get_questions_returns_current_month_structure(): void
    {
        $result = $this->service->getQuestions();

        $this->assertSame(jNow('month') - 1, $result['monthIndex']);
        $this->assertArrayHasKey('questions', $result);
        $this->assertArrayHasKey('prompts', $result);
        $this->assertArrayHasKey('sections', $result);
    }

    public function test_get_questions_delegates_to_current_month_index(): void
    {
        $current = jNow('month') - 1;

        $this->assertSame(
            $this->service->getQuestionsForMonth($current),
            $this->service->getQuestions()
        );
    }

    public function test_get_questions_for_month_clamps_below_zero_to_zero(): void
    {
        $result = $this->service->getQuestionsForMonth(-5);

        $this->assertSame(0, $result['monthIndex']);
        $this->assertSame(
            $this->service->getQuestionsForMonth(0),
            $result
        );
    }

    public function test_get_questions_for_month_clamps_above_eleven_to_eleven(): void
    {
        $result = $this->service->getQuestionsForMonth(99);

        $this->assertSame(11, $result['monthIndex']);
        $this->assertSame(
            $this->service->getQuestionsForMonth(11),
            $result
        );
    }

    public function test_get_questions_for_month_returns_expected_top_level_keys(): void
    {
        $result = $this->service->getQuestionsForMonth(3);

        $this->assertSame(['monthIndex', 'questions', 'prompts', 'sections'], array_keys($result));
    }

    public function test_get_questions_for_month_returns_four_sections_each_with_five_options_for_every_month(): void
    {
        foreach (range(0, 11) as $month) {
            $result = $this->service->getQuestionsForMonth($month);

            foreach (['physique', 'emotion', 'mind', 'soul'] as $section) {
                $this->assertArrayHasKey($section, $result['questions']);
                $this->assertCount(5, $result['questions'][$section], "month {$month} / section {$section}");
                foreach ($result['questions'][$section] as $option) {
                    $this->assertIsString($option, "month {$month} / section {$section}");
                    $this->assertNotSame('', $option, "month {$month} / section {$section}");
                }
            }
        }
    }

    public function test_get_questions_for_month_returns_prompts_with_four_section_keys(): void
    {
        $result = $this->service->getQuestionsForMonth(0);

        $this->assertSame(['physique', 'emotion', 'mind', 'soul'], array_keys($result['prompts']));
        foreach ($result['prompts'] as $prompt) {
            $this->assertIsString($prompt);
            $this->assertNotSame('', $prompt);
        }
    }

    public function test_get_questions_for_month_returns_section_labels_with_four_keys(): void
    {
        $result = $this->service->getQuestionsForMonth(0);

        $this->assertSame(['physique', 'emotion', 'mind', 'soul'], array_keys($result['sections']));
        foreach ($result['sections'] as $label) {
            $this->assertIsString($label);
            $this->assertNotSame('', $label);
        }
    }

    public function test_each_month_returns_a_distinct_question_set_per_section(): void
    {
        foreach (['physique', 'emotion', 'mind', 'soul'] as $section) {
            $sets = [];
            foreach (range(0, 11) as $month) {
                $sets[$month] = $this->service->getQuestionsForMonth($month)['questions'][$section];
            }
            $unique = array_unique(array_map('serialize', $sets));
            $this->assertCount(12, $unique, "section {$section} should have 12 distinct monthly question sets");
        }
    }

    public function test_get_questions_for_month_returns_int_month_index(): void
    {
        $this->assertIsInt($this->service->getQuestionsForMonth(5)['monthIndex']);
    }
}