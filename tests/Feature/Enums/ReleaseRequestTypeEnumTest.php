<?php

namespace Tests\Feature\Enums;

use App\Enums\ReleaseRequestType;
use Tests\TestCase;

class ReleaseRequestTypeEnumTest extends TestCase
{
    public function test_cases_returns_all_three_cases_in_declaration_order(): void
    {
        $cases = ReleaseRequestType::cases();

        $this->assertSame(
            [ReleaseRequestType::Support, ReleaseRequestType::Recommendation, ReleaseRequestType::Bug],
            $cases,
        );
    }

    public function test_backed_values_are_the_lowercase_slugs(): void
    {
        $this->assertSame('support', ReleaseRequestType::Support->value);
        $this->assertSame('recommendation', ReleaseRequestType::Recommendation->value);
        $this->assertSame('bug', ReleaseRequestType::Bug->value);
    }

    public function test_tryfrom_round_trips_every_value_and_rejects_unknown(): void
    {
        foreach (ReleaseRequestType::cases() as $case) {
            $this->assertSame($case, ReleaseRequestType::tryFrom($case->value));
        }

        $this->assertNull(ReleaseRequestType::tryFrom('unknown'));
        $this->assertNull(ReleaseRequestType::tryFrom(''));
        $this->assertNull(ReleaseRequestType::tryFrom('Support'));
    }

    public function test_from_throws_value_error_for_unknown_value(): void
    {
        $this->expectException(\ValueError::class);
        ReleaseRequestType::from('nope');
    }

    public function test_get_label_returns_persian_label_per_case(): void
    {
        $this->assertSame('پشتیبانی نرم افزار', ReleaseRequestType::Support->getLabel());
        $this->assertSame('پیشنهاد ماژول', ReleaseRequestType::Recommendation->getLabel());
        $this->assertSame('گزارش باگ', ReleaseRequestType::Bug->getLabel());
    }

    public function test_get_color_returns_filament_color_per_case(): void
    {
        $this->assertSame('info', ReleaseRequestType::Support->getColor());
        $this->assertSame('success', ReleaseRequestType::Recommendation->getColor());
        $this->assertSame('danger', ReleaseRequestType::Bug->getColor());
    }

    public function test_get_icon_returns_heroicon_per_case(): void
    {
        $this->assertSame('heroicon-o-lifebuoy', ReleaseRequestType::Support->getIcon());
        $this->assertSame('heroicon-o-light-bulb', ReleaseRequestType::Recommendation->getIcon());
        $this->assertSame('heroicon-o-shield-exclamation', ReleaseRequestType::Bug->getIcon());
    }

    public function test_get_material_icon_returns_material_symbol_per_case(): void
    {
        $this->assertSame('support_agent', ReleaseRequestType::Support->getMaterialIcon());
        $this->assertSame('lightbulb', ReleaseRequestType::Recommendation->getMaterialIcon());
        $this->assertSame('bug_report', ReleaseRequestType::Bug->getMaterialIcon());
    }

    public function test_get_material_color_returns_css_variable_per_case(): void
    {
        $this->assertSame('var(--md-sys-color-primary)', ReleaseRequestType::Support->getMaterialColor());
        $this->assertSame('var(--md-sys-color-secondary)', ReleaseRequestType::Recommendation->getMaterialColor());
        $this->assertSame('var(--md-sys-color-error)', ReleaseRequestType::Bug->getMaterialColor());
    }

    public function test_options_maps_values_to_labels_for_all_cases(): void
    {
        $expected = [
            'support' => 'پشتیبانی نرم افزار',
            'recommendation' => 'پیشنهاد ماژول',
            'bug' => 'گزارش باگ',
        ];

        $this->assertSame($expected, ReleaseRequestType::options());
    }

    public function test_options_key_count_matches_cases_count(): void
    {
        $this->assertCount(count(ReleaseRequestType::cases()), ReleaseRequestType::options());
    }

    public function test_each_case_implements_filament_label_color_icon_contracts(): void
    {
        foreach (ReleaseRequestType::cases() as $case) {
            $this->assertNotEmpty($case->getLabel());
            $this->assertNotEmpty($case->getColor());
            $this->assertNotEmpty($case->getIcon());
            $this->assertNotEmpty($case->getMaterialIcon());
            $this->assertNotEmpty($case->getMaterialColor());
        }
    }
}