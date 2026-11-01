<?php

namespace Tests\Feature\Enums;

use App\Enums\NotifyEvent;
use Tests\TestCase;

class NotifyEventEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Created' => 'created',
        'Updated' => 'updated',
        'Deleted' => 'deleted',
    ];

    private const LABELS = [
        'Created' => 'ایجاد',
        'Updated' => 'ویرایش',
        'Deleted' => 'حذف',
    ];

    public function test_cases_returns_all_three_cases_in_declared_order(): void
    {
        $cases = NotifyEvent::cases();

        $this->assertCount(3, $cases);
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn ($c) => $c->name, $cases));
    }

    public function test_every_case_value_is_a_unique_backed_string_matching_contract(): void
    {
        $values = array_map(fn (NotifyEvent $c) => $c->value, NotifyEvent::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_label_returns_persian_label_for_every_case(): void
    {
        foreach (NotifyEvent::cases() as $case) {
            $this->assertSame(self::LABELS[$case->name], $case->label(), "Label mismatch for {$case->name}.");
        }
    }

    public function test_options_maps_every_value_to_its_persian_label(): void
    {
        $this->assertSame([
            'created' => 'ایجاد',
            'updated' => 'ویرایش',
            'deleted' => 'حذف',
        ], NotifyEvent::options());
    }

    public function test_try_from_round_trips_every_case_value_and_returns_null_for_unknown(): void
    {
        foreach (NotifyEvent::cases() as $case) {
            $this->assertSame($case, NotifyEvent::tryFrom($case->value));
        }

        $this->assertNull(NotifyEvent::tryFrom('nonexistent_event'));
        $this->assertNull(NotifyEvent::tryFrom(''));
    }
}