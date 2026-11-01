<?php

namespace Tests\Feature\Enums;

use App\Enums\LinkIcon;
use Tests\TestCase;

class LinkIconEnumTest extends TestCase
{
    private const CASE_VALUES = [
        'Link' => 'link',
        'OpenInNew' => 'open_in_new',
        'Public' => 'public',
        'Apartment' => 'apartment',
        'Language' => 'language',
        'Hub' => 'hub',
        'DatasetLinked' => 'dataset_linked',
        'Share' => 'share',
        'Launch' => 'launch',
        'AltRoute' => 'alt_route',
        'Cloud' => 'cloud',
        'Storage' => 'storage',
        'Dashboard' => 'dashboard',
        'AccountTree' => 'account_tree',
    ];

    private const LABELS = [
        'Link' => 'لینک عمومی',
        'OpenInNew' => 'باز شدن در تب جدید',
        'Public' => 'وب / اینترنت',
        'Apartment' => 'سازمانی / داخلی',
        'Language' => 'زبان / وب‌سایت',
        'Hub' => 'هاب / مرکز',
        'DatasetLinked' => 'سامانهٔ متصل',
        'Share' => 'اشتراک‌گذاری',
        'Launch' => 'اجرا / راه‌اندازی',
        'AltRoute' => 'مسیر جایگزین',
        'Cloud' => 'ابری',
        'Storage' => 'ذخیره‌سازی',
        'Dashboard' => 'داشبورد',
        'AccountTree' => 'ساختار / نمودار',
    ];

    public function test_cases_returns_all_fourteen_cases_in_declared_order(): void
    {
        $cases = LinkIcon::cases();

        $this->assertCount(14, $cases);
        $this->assertSame(array_keys(self::CASE_VALUES), array_map(fn ($c) => $c->name, $cases));
    }

    public function test_every_case_value_is_unique_and_backed_string_matching_contract(): void
    {
        $values = array_map(fn (LinkIcon $c) => $c->value, LinkIcon::cases());

        $this->assertSame(array_values(self::CASE_VALUES), $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }

    public function test_get_label_returns_persian_label_for_every_case(): void
    {
        foreach (LinkIcon::cases() as $case) {
            $this->assertSame(self::LABELS[$case->name], $case->getLabel(), "Label mismatch for {$case->name}.");
        }
    }

    public function test_default_returns_link(): void
    {
        $this->assertSame(LinkIcon::Link, LinkIcon::default());
        $this->assertSame('link', LinkIcon::default()->value);
    }

    public function test_try_from_returns_case_for_valid_value_and_null_for_invalid_value(): void
    {
        foreach (self::CASE_VALUES as $value) {
            $this->assertSame($value, LinkIcon::tryFrom($value)->value);
        }

        $this->assertNull(LinkIcon::tryFrom('nonexistent_icon'));
        $this->assertNull(LinkIcon::tryFrom(''));
        $this->assertNull(LinkIcon::tryFrom('Launch'));
    }

    public function test_from_returns_case_for_valid_value_and_throws_for_invalid_value(): void
    {
        foreach (self::CASE_VALUES as $value) {
            $this->assertSame($value, LinkIcon::from($value)->value);
        }

        $threw = false;
        try {
            LinkIcon::from('nonexistent_icon');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected from() to throw ValueError for an invalid value.');
    }
}