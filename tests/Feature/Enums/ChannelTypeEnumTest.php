<?php

namespace Tests\Feature\Enums;

use App\Enums\ChannelType;
use Tests\TestCase;
use ValueError;

class ChannelTypeEnumTest extends TestCase
{
    public function test_cases_returns_both_open_and_private_cases(): void
    {
        $cases = ChannelType::cases();

        $this->assertSame([ChannelType::Open, ChannelType::Private], $cases);
        $this->assertCount(2, $cases);
    }

    public function test_backed_string_values_map_to_their_lowercase_slug(): void
    {
        $this->assertSame('open', ChannelType::Open->value);
        $this->assertSame('private', ChannelType::Private->value);
    }

    public function test_from_resolves_each_backed_value_to_its_case(): void
    {
        $this->assertSame(ChannelType::Open, ChannelType::from('open'));
        $this->assertSame(ChannelType::Private, ChannelType::from('private'));
    }

    public function test_tryFrom_resolves_known_values_and_returns_null_for_unknown(): void
    {
        $this->assertSame(ChannelType::Open, ChannelType::tryFrom('open'));
        $this->assertSame(ChannelType::Private, ChannelType::tryFrom('private'));
        $this->assertNull(ChannelType::tryFrom('public'));
        $this->assertNull(ChannelType::tryFrom(''));
    }

    public function test_from_throws_value_error_for_an_unknown_backed_value(): void
    {
        $threw = false;
        try {
            ChannelType::from('closed');
        } catch (ValueError $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected ChannelType::from to throw ValueError for an unknown backed value.');
    }

    public function test_get_label_returns_persian_label_for_each_case(): void
    {
        $this->assertSame('عمومی', ChannelType::Open->getLabel());
        $this->assertSame('خصوصی', ChannelType::Private->getLabel());
    }

    public function test_get_color_returns_filament_color_token_for_each_case(): void
    {
        $this->assertSame('success', ChannelType::Open->getColor());
        $this->assertSame('warning', ChannelType::Private->getColor());
    }

    public function test_get_icon_returns_heroicon_outline_icon_for_each_case(): void
    {
        $this->assertSame('heroicon-o-lock-open', ChannelType::Open->getIcon());
        $this->assertSame('heroicon-o-lock-closed', ChannelType::Private->getIcon());
    }

    public function test_get_material_icon_returns_material_symbol_for_each_case(): void
    {
        $this->assertSame('campaign', ChannelType::Open->getMaterialIcon());
        $this->assertSame('lock', ChannelType::Private->getMaterialIcon());
    }

    public function test_get_material_color_returns_css_custom_property_for_each_case(): void
    {
        $this->assertSame('var(--md-sys-color-primary)', ChannelType::Open->getMaterialColor());
        $this->assertSame('var(--md-sys-color-tertiary)', ChannelType::Private->getMaterialColor());
    }

    public function test_implements_filament_label_color_and_icon_contracts(): void
    {
        $this->assertInstanceOf(\Filament\Support\Contracts\HasLabel::class, ChannelType::Open);
        $this->assertInstanceOf(\Filament\Support\Contracts\HasColor::class, ChannelType::Open);
        $this->assertInstanceOf(\Filament\Support\Contracts\HasIcon::class, ChannelType::Open);
    }
}