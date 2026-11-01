<?php

namespace Tests\Feature\Enums;

use App\Enums\PresenceStatus;
use Tests\TestCase;

class PresenceStatusEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Onsite' => 'onsite',
        'Remote' => 'remote',
        'Mission' => 'mission',
        'Busy' => 'busy',
        'Grumpy' => 'grumpy',
        'Angry' => 'angry',
        'Leave' => 'leave',
    ];

    private const LABELS = [
        'Onsite' => 'در دفتر',
        'Remote' => 'دورکار',
        'Mission' => 'مأموریت',
        'Busy' => 'مشغول',
        'Grumpy' => 'بی‌حوصله',
        'Angry' => 'عصبانی',
        'Leave' => 'مرخصی',
    ];

    private const SUBLABELS = [
        'Onsite' => 'حضور فیزیکی',
        'Remote' => 'کار از منزل',
        'Mission' => 'خارج از شرکت',
        'Busy' => 'عدم مزاحمت',
        'Grumpy' => 'بی انرژی',
        'Angry' => ' نزدیک نشوید',
        'Leave' => 'در مرخصی',
    ];

    private const COLORS = [
        'Onsite' => 'emerald',
        'Remote' => 'blue',
        'Mission' => 'amber',
        'Busy' => 'rose',
        'Grumpy' => 'violet',
        'Angry' => 'red',
        'Leave' => 'gray',
    ];

    private const HEROICONS = [
        'Onsite' => 'heroicon-m-building-office',
        'Remote' => 'heroicon-m-computer-desktop',
        'Mission' => 'heroicon-m-paper-airplane',
        'Busy' => 'heroicon-m-minus-circle',
        'Grumpy' => 'heroicon-m-face-frown',
        'Angry' => 'heroicon-m-fire',
        'Leave' => 'heroicon-m-sun',
    ];

    private const ICONS = [
        'Onsite' => 'apartment',
        'Remote' => 'laptop_chromebook',
        'Mission' => 'flight_takeoff',
        'Busy' => 'do_not_disturb_on',
        'Grumpy' => 'sentiment_dissatisfied',
        'Angry' => 'local_fire_department',
        'Leave' => 'sunny',
    ];

    private const HEX = [
        'Onsite' => '#00A44A',
        'Remote' => '#002060',
        'Mission' => '#FE9900',
        'Busy' => '#B00000',
        'Grumpy' => '#7C3AED',
        'Angry' => '#FF3B30',
        'Leave' => '#6B7280',
    ];

    private const RING_CLASSES = [
        'Onsite' => 'border-emerald-500',
        'Remote' => 'border-blue-500',
        'Mission' => 'border-amber-500',
        'Busy' => 'border-rose-500',
        'Grumpy' => 'border-violet-500',
        'Angry' => 'border-red-600',
        'Leave' => 'border-gray-500',
    ];

    private const ICON_BG_CLASSES = [
        'Onsite' => 'bg-emerald-500/20 text-emerald-400',
        'Remote' => 'bg-blue-500/20 text-blue-400',
        'Mission' => 'bg-amber-500/20 text-amber-400',
        'Busy' => 'bg-rose-500/20 text-rose-400',
        'Grumpy' => 'bg-violet-500/20 text-violet-400',
        'Angry' => 'bg-red-600/30 text-red-500',
        'Leave' => 'bg-gray-500/20 text-gray-400',
    ];

    private const INACTIVE_CLASSES = [
        'Onsite' => 'text-emerald-400 hover:bg-emerald-500/10 hover:text-emerald-500 transition-colors',
        'Remote' => 'text-blue-400 hover:bg-blue-500/10 hover:text-blue-500 transition-colors',
        'Mission' => 'text-amber-400 hover:bg-amber-500/10 hover:text-amber-500 transition-colors',
        'Busy' => 'text-rose-400 hover:bg-rose-500/10 hover:text-rose-500 transition-colors',
        'Grumpy' => 'text-violet-400 hover:bg-violet-500/10 hover:text-violet-500 transition-colors',
        'Angry' => 'text-red-500 hover:bg-red-600/20 hover:text-red-400 transition-colors',
        'Leave' => 'text-gray-400 hover:bg-gray-500/10 hover:text-gray-500 transition-colors',
    ];

    public function test_cases_returns_all_seven_cases_in_declared_order(): void
    {
        $cases = PresenceStatus::cases();

        $this->assertCount(7, $cases);
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn ($c) => $c->name, $cases));
    }

    public function test_every_case_value_is_unique_and_backed_string_matching_contract(): void
    {
        $values = array_map(fn (PresenceStatus $c) => $c->value, PresenceStatus::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_label_returns_persian_label_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::LABELS[$case->name], $case->label(), "Label mismatch for {$case->name}.");
        }
    }

    public function test_sublabel_returns_persian_sublabel_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::SUBLABELS[$case->name], $case->sublabel(), "Sublabel mismatch for {$case->name}.");
        }
    }

    public function test_color_returns_named_color_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::COLORS[$case->name], $case->color(), "Color mismatch for {$case->name}.");
        }
    }

    public function test_heroicon_returns_heroicon_identifier_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::HEROICONS[$case->name], $case->heroicon(), "Heroicon mismatch for {$case->name}.");
        }
    }

    public function test_icon_returns_material_symbol_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::ICONS[$case->name], $case->icon(), "Icon mismatch for {$case->name}.");
        }
    }

    public function test_hex_returns_uppercase_hex_for_every_case_and_every_hex_is_distinct(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::HEX[$case->name], $case->hex(), "Hex mismatch for {$case->name}.");
        }

        $hexes = array_map(fn (PresenceStatus $c) => $c->hex(), PresenceStatus::cases());
        $this->assertCount(count($hexes), array_unique($hexes));
    }

    public function test_default_is_onsite_and_unreachable_values_are_exactly_leave_and_mission(): void
    {
        $this->assertSame(PresenceStatus::Onsite, PresenceStatus::default());

        foreach (PresenceStatus::cases() as $case) {
            $expected = !in_array($case, [PresenceStatus::Leave, PresenceStatus::Mission], true);
            $this->assertSame($expected, $case->isReachable(), "isReachable mismatch for {$case->name}.");
        }

        $this->assertEqualsCanonicalizing(['leave', 'mission'], PresenceStatus::unreachableValues());
        $this->assertEqualsCanonicalizing(['busy', 'grumpy', 'angry'], PresenceStatus::moodValues());
    }

    public function test_badge_bg_class_is_a_literal_pair_with_dark_variant_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $class = $case->badgeBgClass();
            $this->assertMatchesRegularExpression('/^bg-' . $case->color() . '-\d+ text-white dark:bg-' . $case->color() . '-\d+ dark:text-' . $case->color() . '-\d+$/', $class, "Badge bg class malformed for {$case->name}.");
        }
    }

    public function test_tip_joins_label_and_trimmed_sublabel(): void
    {
        $this->assertSame('عصبانی — نزدیک نشوید', PresenceStatus::Angry->tip());
        $this->assertSame('مرخصی — در مرخصی', PresenceStatus::Leave->tip());
    }

    public function test_ring_class_returns_border_class_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::RING_CLASSES[$case->name], $case->ringClass(), "Ring class mismatch for {$case->name}.");
        }
    }

    public function test_icon_bg_class_returns_pair_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::ICON_BG_CLASSES[$case->name], $case->iconBgClass(), "Icon bg class mismatch for {$case->name}.");
        }
    }

    public function test_inactive_class_returns_pair_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame(self::INACTIVE_CLASSES[$case->name], $case->inactiveClass(), "Inactive class mismatch for {$case->name}.");
        }
    }

    public function test_active_class_is_non_empty_and_carries_the_color_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $class = $case->activeClass();
            $this->assertNotSame('', $class, "Active class empty for {$case->name}.");
            $this->assertStringContainsString('text-white', $class);
            $this->assertStringContainsString('ring-1', $class);
            $this->assertStringContainsString($case->color(), $class);
        }
    }

    public function test_active_class_carries_dark_contrast_variants_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $class = $case->activeClass();
            $this->assertStringContainsString('dark:bg-', $class, "Active class missing dark bg for {$case->name}.");
            $this->assertStringContainsString('dark:text-', $class, "Active class missing dark text for {$case->name}.");
        }
    }

    public function test_css_var_returns_presence_token_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame('var(--presence-' . $case->value . ')', $case->cssVar(), "cssVar mismatch for {$case->name}.");
        }
    }

    public function test_card_class_is_non_empty_and_carries_the_color_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $class = $case->cardClasses();
            $this->assertNotSame('', $class, "Card class empty for {$case->name}.");
            $this->assertStringContainsString('border-2', $class);
            $this->assertStringContainsString($case->color(), $class);
        }
    }

    public function test_badge_class_returns_animate_bounce_only_for_angry_and_empty_otherwise(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $expected = $case === PresenceStatus::Angry ? 'animate-bounce' : '';
            $this->assertSame($expected, $case->badgeClasses(), "Badge class mismatch for {$case->name}.");
        }
    }

    public function test_lift_class_returns_strong_lift_only_for_angry_and_default_otherwise(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $expected = $case === PresenceStatus::Angry ? 'hover:-translate-y-1' : 'hover:-translate-y-0.5';
            $this->assertSame($expected, $case->liftClass(), "Lift class mismatch for {$case->name}.");
        }
    }

    public function test_image_class_returns_specific_tailwind_for_busy_grumpy_angry_and_default_otherwise(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $class = $case->imageClasses();
            if ($case === PresenceStatus::Busy) {
                $this->assertStringContainsString('cursor-wait', $class);
            } elseif ($case === PresenceStatus::Grumpy) {
                $this->assertStringContainsString('cursor-not-allowed', $class);
            } elseif ($case === PresenceStatus::Angry) {
                $this->assertStringContainsString('cursor-no-drop', $class);
            } else {
                $this->assertSame('transition-transform duration-300', $class, "Image class mismatch for {$case->name}.");
            }
        }
    }

    public function test_is_obscured_returns_true_only_for_grumpy(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $expected = $case === PresenceStatus::Grumpy;
            $this->assertSame($expected, $case->isObscured(), "isObscured mismatch for {$case->name}.");
        }
    }

    public function test_effect_type_returns_the_backed_value_for_every_case(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame($case->value, $case->effectType(), "Effect type mismatch for {$case->name}.");
        }
    }

    public function test_try_from_round_trips_every_case_value_and_returns_null_for_unknown(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame($case, PresenceStatus::tryFrom($case->value));
        }

        $this->assertNull(PresenceStatus::tryFrom('nonexistent_status'));
        $this->assertNull(PresenceStatus::tryFrom(''));
        $this->assertNull(PresenceStatus::tryFrom('Onsite'));
    }

    public function test_from_round_trips_every_case_value_and_throws_for_unknown(): void
    {
        foreach (PresenceStatus::cases() as $case) {
            $this->assertSame($case, PresenceStatus::from($case->value));
        }

        $threw = false;
        try {
            PresenceStatus::from('nonexistent_status');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an unknown value.');
    }
}