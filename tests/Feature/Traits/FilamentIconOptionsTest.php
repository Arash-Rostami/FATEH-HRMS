<?php

namespace Tests\Feature\Traits;

use App\Enums\ResourceTypeIcon;
use App\Traits\FilamentIconOptions;
use Tests\TestCase;

class FilamentIconOptionsTest extends TestCase
{
    private function host(): object
    {
        return new class {
            use FilamentIconOptions;

            public function options(): array
            {
                return self::curatedIconOptions();
            }

            public function labelFor(string $value): string
            {
                return self::curatedIconOptionLabel($value);
            }
        };
    }

    public function test_curated_icon_options_are_keyed_by_every_enum_value(): void
    {
        $options = $this->host()->options();

        $this->assertSame(
            array_map(fn (ResourceTypeIcon $icon) => $icon->value, ResourceTypeIcon::cases()),
            array_keys($options)
        );
    }

    public function test_curated_icon_options_render_label_span_for_every_case(): void
    {
        $options = $this->host()->options();

        foreach (ResourceTypeIcon::cases() as $icon) {
            $this->assertStringContainsString('<span>' . e($icon->getLabel()) . '</span>', $options[$icon->value]);
        }
    }

    public function test_curated_icon_option_label_for_known_value_renders_enum_label(): void
    {
        $html = $this->host()->labelFor(ResourceTypeIcon::Desk->value);

        $this->assertStringContainsString('<span>میز</span>', $html);
    }

    public function test_curated_icon_option_label_for_unknown_value_derives_human_label(): void
    {
        $html = $this->host()->labelFor('heroicon-o-something-new');

        $this->assertStringContainsString('<span>Something New</span>', $html);
    }

    public function test_rendered_svg_is_sized_when_asset_exists(): void
    {
        $file = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/o-computer-desktop.svg');

        if (! is_file($file)) {
            $this->markTestSkipped('blade-heroicons o-computer-desktop.svg asset not present.');
        }

        $html = $this->host()->labelFor(ResourceTypeIcon::Desk->value);

        $this->assertStringContainsString('<svg class="w-4 h-4"', $html);
    }

    public function test_format_icon_svg_strips_width_height_and_adds_sizing_class(): void
    {
        $method = new \ReflectionMethod($this->host()::class, 'formatIconSvg');
        $method->setAccessible(true);
        $output = $method->invoke(null, '<svg width="24" height="24" viewBox="0 0 24 24"><path d="x"/></svg>');

        $this->assertStringContainsString('<svg class="w-4 h-4"', $output);
        $this->assertStringNotContainsString('width="24"', $output);
        $this->assertStringNotContainsString('height="24"', $output);
    }
}