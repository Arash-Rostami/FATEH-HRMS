<?php

namespace Tests\Feature\Enums;

use App\Enums\SkillRequestStatus;
use Tests\TestCase;

class SkillRequestStatusEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Pending' => 'pending',
        'Approved' => 'approved',
        'Rejected' => 'rejected',
    ];

    private const LABELS = [
        'Pending' => 'در حال بررسی',
        'Approved' => 'تایید شده',
        'Rejected' => 'رد شده',
    ];

    private const COLORS = [
        'Pending' => 'amber',
        'Approved' => 'emerald',
        'Rejected' => 'rose',
    ];

    private const HEROICONS = [
        'Pending' => 'heroicon-m-clock',
        'Approved' => 'heroicon-m-check-circle',
        'Rejected' => 'heroicon-m-x-circle',
    ];

    private const BADGE_CLASSES = [
        'Pending' => 'animate-pulse',
        'Approved' => '',
        'Rejected' => '',
    ];

    public function test_cases_returns_three_cases_in_declared_order(): void
    {
        $cases = SkillRequestStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn ($c) => $c->name, $cases));
    }

    public function test_every_case_value_is_unique_and_backed_string_matching_contract(): void
    {
        $values = array_map(fn (SkillRequestStatus $c) => $c->value, SkillRequestStatus::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_label_returns_persian_label_for_every_case(): void
    {
        foreach (SkillRequestStatus::cases() as $case) {
            $this->assertSame(self::LABELS[$case->name], $case->label(), "Label mismatch for {$case->name}.");
        }
    }

    public function test_color_returns_named_color_for_every_case(): void
    {
        foreach (SkillRequestStatus::cases() as $case) {
            $this->assertSame(self::COLORS[$case->name], $case->color(), "Color mismatch for {$case->name}.");
        }
    }

    public function test_heroicon_returns_heroicon_identifier_for_every_case(): void
    {
        foreach (SkillRequestStatus::cases() as $case) {
            $this->assertSame(self::HEROICONS[$case->name], $case->heroicon(), "Heroicon mismatch for {$case->name}.");
        }
    }

    public function test_badge_classes_returns_animate_pulse_only_for_pending_and_empty_otherwise(): void
    {
        foreach (SkillRequestStatus::cases() as $case) {
            $expected = $case === SkillRequestStatus::Pending ? 'animate-pulse' : '';
            $this->assertSame($expected, $case->badgeClasses(), "Badge class mismatch for {$case->name}.");
        }
    }

    public function test_try_from_round_trips_every_case_value_and_returns_null_for_unknown(): void
    {
        foreach (SkillRequestStatus::cases() as $case) {
            $this->assertSame($case, SkillRequestStatus::tryFrom($case->value));
        }

        $this->assertNull(SkillRequestStatus::tryFrom('nonexistent_status'));
        $this->assertNull(SkillRequestStatus::tryFrom(''));
        $this->assertNull(SkillRequestStatus::tryFrom('Pending'));
    }

    public function test_from_round_trips_every_case_value_and_throws_for_unknown(): void
    {
        foreach (SkillRequestStatus::cases() as $case) {
            $this->assertSame($case, SkillRequestStatus::from($case->value));
        }

        $threw = false;
        try {
            SkillRequestStatus::from('nonexistent_status');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an unknown value.');
    }
}