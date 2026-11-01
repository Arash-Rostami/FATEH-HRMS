<?php

namespace Tests\Feature\Enums;

use App\Enums\ProfileDetailGroup;
use Tests\TestCase;
use ValueError;

class ProfileDetailGroupEnumTest extends TestCase
{
    private const EXPECTED_LABELS = [
        'identity' => 'اطلاعات هویتی تکمیلی',
        'employment' => 'اطلاعات شغلی تکمیلی',
        'experience' => 'سوابق کاری و بیمه',
        'education' => 'تحصیلات',
        'skills' => 'مهارت‌ها و دستاوردها',
        'family' => 'خانواده و افراد تحت تکفل',
        'health' => 'سلامت و وضعیت جسمانی',
        'military' => 'نظام وظیفه',
        'financial' => 'مالی و رفاهی',
        'other' => 'سایر اطلاعات',
    ];

    private const EXPECTED_COLORS = [
        'identity' => 'info',
        'employment' => 'warning',
        'experience' => 'amber',
        'education' => 'success',
        'skills' => 'violet',
        'family' => 'pink',
        'health' => 'rose',
        'military' => 'slate',
        'financial' => 'emerald',
        'other' => 'gray',
    ];

    private const EXPECTED_ICONS = [
        'identity' => 'heroicon-o-identification',
        'employment' => 'heroicon-o-briefcase',
        'experience' => 'heroicon-o-clock',
        'education' => 'heroicon-o-academic-cap',
        'skills' => 'heroicon-o-sparkles',
        'family' => 'heroicon-o-users',
        'health' => 'heroicon-o-heart',
        'military' => 'heroicon-o-shield-check',
        'financial' => 'heroicon-o-banknotes',
        'other' => 'heroicon-o-ellipsis-horizontal',
    ];

    private const EXPECTED_MATERIAL_ICONS = [
        'identity' => 'badge',
        'employment' => 'work',
        'experience' => 'history',
        'education' => 'school',
        'skills' => 'auto_awesome',
        'family' => 'family_restroom',
        'health' => 'health_and_safety',
        'military' => 'military_tech',
        'financial' => 'payments',
        'other' => 'more_horiz',
    ];

    public function test_cases_count_matches_expected_set(): void
    {
        $this->assertSame(count(self::EXPECTED_LABELS), count(ProfileDetailGroup::cases()));
    }

    public function test_every_case_value_is_unique_and_backed_string(): void
    {
        $values = array_map(fn (ProfileDetailGroup $case) => $case->value, ProfileDetailGroup::cases());

        $this->assertSame(count($values), count(array_unique($values)));
        $this->assertSame(array_keys(self::EXPECTED_LABELS), $values);
    }

    public function test_get_label_returns_persian_label_for_every_case(): void
    {
        foreach (ProfileDetailGroup::cases() as $case) {
            $this->assertSame(
                self::EXPECTED_LABELS[$case->value],
                $case->getLabel(),
                "Label mismatch for case {$case->name}."
            );
        }
    }

    public function test_get_color_returns_filament_color_token_for_every_case(): void
    {
        foreach (ProfileDetailGroup::cases() as $case) {
            $this->assertSame(
                self::EXPECTED_COLORS[$case->value],
                $case->getColor(),
                "Color mismatch for case {$case->name}."
            );
        }
    }

    public function test_get_icon_returns_heroicon_outline_icon_for_every_case(): void
    {
        foreach (ProfileDetailGroup::cases() as $case) {
            $this->assertSame(
                self::EXPECTED_ICONS[$case->value],
                $case->getIcon(),
                "Icon mismatch for case {$case->name}."
            );
        }
    }

    public function test_material_icon_returns_material_symbol_for_every_case(): void
    {
        foreach (ProfileDetailGroup::cases() as $case) {
            $this->assertSame(
                self::EXPECTED_MATERIAL_ICONS[$case->value],
                $case->materialIcon(),
                "Material icon mismatch for case {$case->name}."
            );
        }
    }

    public function test_ordered_returns_cases_in_declared_order(): void
    {
        $this->assertSame(
            [
                ProfileDetailGroup::Identity,
                ProfileDetailGroup::Employment,
                ProfileDetailGroup::Experience,
                ProfileDetailGroup::Education,
                ProfileDetailGroup::Skills,
                ProfileDetailGroup::Family,
                ProfileDetailGroup::Health,
                ProfileDetailGroup::Military,
                ProfileDetailGroup::Financial,
                ProfileDetailGroup::Other,
            ],
            ProfileDetailGroup::ordered()
        );
    }

    public function test_try_from_returns_case_for_valid_value_and_null_for_invalid_value(): void
    {
        foreach (array_keys(self::EXPECTED_LABELS) as $value) {
            $this->assertSame($value, ProfileDetailGroup::tryFrom($value)->value);
        }

        $this->assertNull(ProfileDetailGroup::tryFrom('nonexistent_group'));
        $this->assertNull(ProfileDetailGroup::tryFrom(''));
    }

    public function test_from_returns_case_for_valid_value_and_throws_value_error_for_invalid_value(): void
    {
        $first = array_key_first(self::EXPECTED_LABELS);
        $last = array_key_last(self::EXPECTED_LABELS);

        $this->assertSame($first, ProfileDetailGroup::from($first)->value);
        $this->assertSame($last, ProfileDetailGroup::from($last)->value);

        $threw = false;
        try {
            ProfileDetailGroup::from('nonexistent_group');
        } catch (ValueError $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected ProfileDetailGroup::from to throw ValueError for an unknown backed value.');
    }

    public function test_implements_filament_label_color_and_icon_contracts(): void
    {
        $this->assertContains('Filament\Support\Contracts\HasLabel', class_implements(ProfileDetailGroup::class));
        $this->assertContains('Filament\Support\Contracts\HasColor', class_implements(ProfileDetailGroup::class));
        $this->assertContains('Filament\Support\Contracts\HasIcon', class_implements(ProfileDetailGroup::class));
    }
}