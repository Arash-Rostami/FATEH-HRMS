<?php

namespace Tests\Feature\Enums;

use App\Enums\ResourceTypeIcon;
use Tests\TestCase;

class ResourceTypeIconEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Desk' => 'heroicon-o-computer-desktop',
        'Vehicle' => 'heroicon-o-truck',
        'Parking' => 'heroicon-o-map-pin',
        'Meeting' => 'heroicon-o-users',
        'Projector' => 'heroicon-o-video-camera',
        'Printer' => 'heroicon-o-printer',
        'Tools' => 'heroicon-o-wrench-screwdriver',
        'Key' => 'heroicon-o-key',
        'Box' => 'heroicon-o-cube',
        'Building' => 'heroicon-o-building-office',
        'Device' => 'heroicon-o-device-phone-mobile',
        'Storage' => 'heroicon-o-archive-box',
        'Briefcase' => 'heroicon-o-briefcase',
        'Ticket' => 'heroicon-o-ticket',
    ];

    private const MATERIAL_ICONS = [
        'Desk' => 'desk',
        'Vehicle' => 'directions_car',
        'Parking' => 'local_parking',
        'Meeting' => 'group',
        'Projector' => 'videocam',
        'Printer' => 'print',
        'Tools' => 'build',
        'Key' => 'key',
        'Box' => 'inventory_2',
        'Building' => 'apartment',
        'Device' => 'smartphone',
        'Storage' => 'archive',
        'Briefcase' => 'work',
        'Ticket' => 'confirmation_number',
    ];

    private const LABELS = [
        'Desk' => 'میز',
        'Vehicle' => 'وسیله نقلیه',
        'Parking' => 'پارکینگ',
        'Meeting' => 'جلسه',
        'Projector' => 'پروژکتور',
        'Printer' => 'چاپگر',
        'Tools' => 'ابزار',
        'Key' => 'کلید',
        'Box' => 'جعبه',
        'Building' => 'ساختمان',
        'Device' => 'دستگاه',
        'Storage' => 'انبار',
        'Briefcase' => 'کیف',
        'Ticket' => 'بلیت',
    ];

    public function test_cases_count_matches_expected_set(): void
    {
        $this->assertSame(count(self::CASE_VALUES), count(ResourceTypeIcon::cases()));
    }

    public function test_cases_are_returned_in_declared_order_with_expected_names(): void
    {
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn($c) => $c->name, ResourceTypeIcon::cases()));
    }

    public function test_every_case_value_is_a_unique_heroicon_string(): void
    {
        $values = array_map(fn(ResourceTypeIcon $c) => $c->value, ResourceTypeIcon::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
        foreach ($values as $value) {
            $this->assertStringStartsWith('heroicon-', $value);
        }
    }

    public function test_get_label_returns_a_non_empty_persian_string_for_every_case(): void
    {
        $labels = [];
        foreach (ResourceTypeIcon::cases() as $case) {
            $label = $case->getLabel();
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $label, "{$case->name} label must be Persian text");
            $labels[] = $label;
        }

        $this->assertSame($labels, array_unique($labels), 'ResourceTypeIcon labels must be unique so options are distinguishable in the select.');
    }

    public function test_get_label_matches_expected_map_for_every_case(): void
    {
        foreach (ResourceTypeIcon::cases() as $case) {
            $this->assertSame(self::LABELS[$case->name], $case->getLabel(), "Label mismatch for {$case->name}.");
        }
    }

    public function test_get_material_icon_matches_expected_map_for_every_case(): void
    {
        foreach (ResourceTypeIcon::cases() as $case) {
            $this->assertSame(self::MATERIAL_ICONS[$case->name], $case->getMaterialIcon());
        }
    }

    public function test_default_returns_box(): void
    {
        $this->assertSame(ResourceTypeIcon::Box, ResourceTypeIcon::default());
        $this->assertSame('heroicon-o-cube', ResourceTypeIcon::default()->value);
    }

    public function test_try_from_returns_case_for_valid_value_and_null_for_invalid_value(): void
    {
        foreach (self::CASE_VALUES as $value) {
            $this->assertSame($value, ResourceTypeIcon::tryFrom($value)->value);
        }

        $this->assertNull(ResourceTypeIcon::tryFrom('heroicon-o-does-not-exist'));
        $this->assertNull(ResourceTypeIcon::tryFrom(''));
    }
}
