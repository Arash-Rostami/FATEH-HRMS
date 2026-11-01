<?php

namespace Tests\Feature\Enums;

use App\Enums\TaskActivityType;
use Tests\TestCase;

class TaskActivityTypeEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Comment' => 'comment',
        'StatusChange' => 'status_change',
        'Assignment' => 'assignment',
        'Archive' => 'archive',
        'Approval' => 'approval',
        'Attachment' => 'attachment',
        'ResponsibleChange' => 'responsible_change',
        'DepartmentChange' => 'department_change',
        'StateChange' => 'state_change',
        'DeadlineChange' => 'deadline_change',
        'PriorityChange' => 'priority_change',
        'LabelChange' => 'label_change',
        'ProjectChange' => 'project_change',
        'MetaChange' => 'meta_change',
        'SettingsChange' => 'settings_change',
        'WorkflowStep' => 'workflow_step',
    ];

    public function test_cases_returns_all_sixteen_cases_in_declared_order(): void
    {
        $cases = TaskActivityType::cases();

        $this->assertCount(16, $cases);
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn ($c) => $c->name, $cases));
    }

    public function test_every_case_value_is_unique_and_backed_string_matching_contract(): void
    {
        $values = array_map(fn (TaskActivityType $c) => $c->value, TaskActivityType::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_try_from_returns_case_for_valid_value_and_null_for_invalid_value(): void
    {
        foreach (self::CASE_VALUES as $value) {
            $this->assertSame($value, TaskActivityType::tryFrom($value)->value);
        }

        $this->assertNull(TaskActivityType::tryFrom('nonexistent_activity'));
        $this->assertNull(TaskActivityType::tryFrom(''));
        $this->assertNull(TaskActivityType::tryFrom('Comment'));
    }

    public function test_from_returns_case_for_valid_value_and_throws_for_invalid_value(): void
    {
        foreach (self::CASE_VALUES as $value) {
            $this->assertSame($value, TaskActivityType::from($value)->value);
        }

        $threw = false;
        try {
            TaskActivityType::from('nonexistent_activity');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an invalid value.');
    }
}