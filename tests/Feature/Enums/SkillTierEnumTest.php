<?php

namespace Tests\Feature\Enums;

use App\Enums\SkillTier;
use Tests\TestCase;

class SkillTierEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Endorsed' => 'endorsed',
        'Active' => 'active',
        'Unused' => 'unused',
    ];

    private const LABELS = [
        'Endorsed' => 'تأییدشده',
        'Active' => 'فعال',
        'Unused' => 'آماده مشارکت',
    ];

    private const ICONS = [
        'Endorsed' => 'verified',
        'Active' => 'bolt',
        'Unused' => 'hourglass_empty',
    ];

    private const BADGE_CLASSES = [
        'Endorsed' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        'Active' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        'Unused' => 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]',
    ];

    private const COLORS = [
        'Endorsed' => 'amber',
        'Active' => 'emerald',
        'Unused' => 'gray',
    ];

    private const HEROICONS = [
        'Endorsed' => 'heroicon-m-check-badge',
        'Active' => 'heroicon-m-bolt',
        'Unused' => 'heroicon-m-clock',
    ];

    public function test_cases_returns_three_cases_in_declared_order(): void
    {
        $cases = SkillTier::cases();

        $this->assertCount(3, $cases);
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn ($c) => $c->name, $cases));
    }

    public function test_every_case_value_is_unique_and_backed_string_matching_contract(): void
    {
        $values = array_map(fn (SkillTier $c) => $c->value, SkillTier::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_label_returns_persian_label_for_every_case(): void
    {
        foreach (SkillTier::cases() as $case) {
            $this->assertSame(self::LABELS[$case->name], $case->label(), "Label mismatch for {$case->name}.");
        }
    }

    public function test_icon_returns_material_symbol_for_every_case(): void
    {
        foreach (SkillTier::cases() as $case) {
            $this->assertSame(self::ICONS[$case->name], $case->icon(), "Icon mismatch for {$case->name}.");
        }
    }

    public function test_badge_classes_returns_tailwind_pair_for_every_case(): void
    {
        foreach (SkillTier::cases() as $case) {
            $this->assertSame(self::BADGE_CLASSES[$case->name], $case->badgeClasses(), "Badge class mismatch for {$case->name}.");
        }
    }

    public function test_color_returns_named_color_for_every_case(): void
    {
        foreach (SkillTier::cases() as $case) {
            $this->assertSame(self::COLORS[$case->name], $case->color(), "Color mismatch for {$case->name}.");
        }
    }

    public function test_heroicon_returns_heroicon_identifier_for_every_case(): void
    {
        foreach (SkillTier::cases() as $case) {
            $this->assertSame(self::HEROICONS[$case->name], $case->heroicon(), "Heroicon mismatch for {$case->name}.");
        }
    }

    public function test_try_from_round_trips_every_case_value_and_returns_null_for_unknown(): void
    {
        foreach (SkillTier::cases() as $case) {
            $this->assertSame($case, SkillTier::tryFrom($case->value));
        }

        $this->assertNull(SkillTier::tryFrom('nonexistent_tier'));
        $this->assertNull(SkillTier::tryFrom(''));
        $this->assertNull(SkillTier::tryFrom('Endorsed'));
    }

    public function test_from_round_trips_every_case_value_and_throws_for_unknown(): void
    {
        foreach (SkillTier::cases() as $case) {
            $this->assertSame($case, SkillTier::from($case->value));
        }

        $threw = false;
        try {
            SkillTier::from('nonexistent_tier');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an unknown value.');
    }
}