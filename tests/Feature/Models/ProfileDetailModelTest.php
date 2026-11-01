<?php

namespace Tests\Feature\Models;

use App\Models\ProfileDetail;
use Tests\TestCase;

class ProfileDetailModelTest extends TestCase
{
    public function test_get_display_value_attribute_returns_option_label_for_known_select_value(): void
    {
        $detail = new ProfileDetail(['key' => 'religion', 'value' => 'اسلام']);

        $this->assertSame('اسلام', $detail->display_value);
    }

    public function test_get_display_value_attribute_returns_raw_value_when_select_value_not_in_options(): void
    {
        $detail = new ProfileDetail(['key' => 'religion', 'value' => 'UnknownOption']);

        $this->assertSame('UnknownOption', $detail->display_value);
    }

    public function test_get_display_value_attribute_returns_jalali_for_filled_date_value(): void
    {
        $detail = new ProfileDetail(['key' => 'marriage_date', 'value' => '2025-01-15']);

        $this->assertNotSame('2025-01-15', $detail->display_value);
        $this->assertStringContainsString('/', $detail->display_value);
    }

    public function test_get_display_value_attribute_returns_raw_value_when_date_value_is_blank(): void
    {
        $this->assertNull((new ProfileDetail(['key' => 'marriage_date', 'value' => null]))->display_value);
        $this->assertSame('', (new ProfileDetail(['key' => 'marriage_date', 'value' => '']))->display_value);
    }

    public function test_get_display_value_attribute_returns_raw_value_for_text_type(): void
    {
        $detail = new ProfileDetail(['key' => 'company_name', 'value' => 'Acme Co']);

        $this->assertSame('Acme Co', $detail->display_value);
    }

    public function test_get_display_value_attribute_returns_raw_value_for_unknown_key(): void
    {
        $detail = new ProfileDetail(['key' => 'nonexistent_key', 'value' => 'raw']);

        $this->assertSame('raw', $detail->display_value);
    }

    public function test_get_label_attribute_returns_label_for_known_key(): void
    {
        $this->assertSame('نام شرکت', (new ProfileDetail(['key' => 'company_name']))->label);
    }

    public function test_get_label_attribute_returns_key_for_unknown_key(): void
    {
        $this->assertSame('nonexistent_key', (new ProfileDetail(['key' => 'nonexistent_key']))->label);
    }
}