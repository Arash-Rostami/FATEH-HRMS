<?php

namespace Tests\Feature\Rules;

use App\Rules\ExtraRequiresInternalUrl;
use Tests\TestCase;

class ExtraRequiresInternalUrlTest extends TestCase
{
    private function fails(ExtraRequiresInternalUrl $rule): bool
    {
        $failed = false;
        $rule->validate('extra', null, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    public function test_both_blank_passes(): void
    {
        $this->assertFalse($this->fails(new ExtraRequiresInternalUrl([], null)));
        $this->assertFalse($this->fails(new ExtraRequiresInternalUrl(null, '')));
    }

    public function test_both_filled_passes(): void
    {
        $this->assertFalse($this->fails(new ExtraRequiresInternalUrl(['10.0.0.1'], 'https://intranet.fateh.local')));
    }

    public function test_extra_filled_with_internal_url_blank_fails(): void
    {
        $this->assertTrue($this->fails(new ExtraRequiresInternalUrl(['10.0.0.1'], null)));
    }

    public function test_internal_url_filled_with_extra_blank_fails(): void
    {
        $this->assertTrue($this->fails(new ExtraRequiresInternalUrl([], 'https://intranet.fateh.local')));
    }

    public function test_extra_containing_only_whitespace_after_trim_counts_as_blank(): void
    {
        $this->assertTrue($this->fails(new ExtraRequiresInternalUrl(['  ', ''], 'https://intranet.fateh.local')));
        $this->assertFalse($this->fails(new ExtraRequiresInternalUrl(['  ', ''], null)));
    }

    public function test_rule_fires_regardless_of_which_field_it_is_attached_to(): void
    {
        $rule = new ExtraRequiresInternalUrl(['10.0.0.1'], null);

        foreach (['extra', 'internal_url'] as $attribute) {
            $failed = false;
            $rule->validate($attribute, null, function () use (&$failed): void {
                $failed = true;
            });

            $this->assertTrue($failed, "rule must fire when attached to the {$attribute} field");
        }
    }

    public function test_message_renders_without_leftover_key_or_placeholders(): void
    {
        $this->app->setLocale('fa');

        $message = null;
        (new ExtraRequiresInternalUrl(['10.0.0.1'], null))
            ->validate('extra', null, function (string $m) use (&$message): void {
                $message = $m;
            });

        $this->assertNotNull($message);
        $this->assertStringNotContainsString('resources/link/strings', $message);
        $this->assertStringNotContainsString(':', $message);
    }
}
