<?php

namespace Tests\Feature\Services;

use App\Services\QuoteService;
use Tests\TestCase;

class QuoteServiceTest extends TestCase
{
    public function test_all_returns_array_of_quotes(): void
    {
        $quotes = QuoteService::all();

        $this->assertIsArray($quotes);
    }

    public function test_all_returns_non_empty_array(): void
    {
        $this->assertNotEmpty(QuoteService::all());
    }

    public function test_all_returns_string_values_only(): void
    {
        foreach (QuoteService::all() as $quote) {
            $this->assertIsString($quote);
        }
    }

    public function test_all_quotes_are_non_empty_strings(): void
    {
        foreach (QuoteService::all() as $quote) {
            $this->assertNotSame('', $quote);
        }
    }

    public function test_all_returns_sequential_zero_indexed_list(): void
    {
        $quotes = QuoteService::all();

        $this->assertSame($quotes === [] ? [] : range(0, count($quotes) - 1), array_keys($quotes));
    }

    public function test_all_is_deterministic_across_calls(): void
    {
        $first = QuoteService::all();
        $second = QuoteService::all();

        $this->assertSame($first, $second);
    }

    public function test_all_count_matches_constant(): void
    {
        $reflection = new \ReflectionClass(QuoteService::class);
        $constant = $reflection->getConstant('QUOTES');

        $this->assertCount(count($constant), QuoteService::all());
    }

    public function test_all_returns_exact_constant_contents(): void
    {
        $reflection = new \ReflectionClass(QuoteService::class);
        $constant = $reflection->getConstant('QUOTES');

        $this->assertSame($constant, QuoteService::all());
    }

    public function test_all_contains_known_first_quote(): void
    {
        $quotes = QuoteService::all();

        $this->assertContains('مقیاس‌پذیریِ یک کسب‌وکار، پیش از زیرساخت‌های فنی، به وسعتِ ظرفیتِ روانیِ رهبرانش گره خورده است.', $quotes);
    }

    public function test_all_contains_known_last_quote(): void
    {
        $quotes = QuoteService::all();

        $this->assertContains('پایان هر جستجو، شناختن همان نقطه‌ای است که از آن آغاز کرده‌ایم.', $quotes);
    }

    public function test_all_first_element_is_first_constant_entry(): void
    {
        $reflection = new \ReflectionClass(QuoteService::class);
        $constant = $reflection->getConstant('QUOTES');

        $this->assertSame($constant[0], QuoteService::all()[0]);
    }

    public function test_all_last_element_is_last_constant_entry(): void
    {
        $reflection = new \ReflectionClass(QuoteService::class);
        $constant = $reflection->getConstant('QUOTES');
        $lastIndex = count($constant) - 1;

        $this->assertSame($constant[$lastIndex], QuoteService::all()[$lastIndex]);
    }

    public function test_all_quotes_are_unique(): void
    {
        $quotes = QuoteService::all();

        $this->assertSame(count($quotes), count(array_unique($quotes)));
    }

    public function test_all_preserves_persian_text_intact(): void
    {
        $quotes = QuoteService::all();

        foreach ($quotes as $quote) {
            $this->assertNotEmpty(trim($quote));
            $this->assertTrue(mb_strlen($quote) > 5);
        }
    }

    public function test_all_quotes_contain_only_persian_and_punctuation_chars(): void
    {
        foreach (QuoteService::all() as $quote) {
            $this->assertMatchesRegularExpression('/^[\p{Arabic}\p{P}\p{M}\s\.\,\«\»\:\؛\،\-\؟\x{200C}\x{200D}]+$/u', $quote);
        }
    }

    public function test_quote_count_is_reasonably_large(): void
    {
        $this->assertGreaterThan(100, count(QuoteService::all()));
    }
}