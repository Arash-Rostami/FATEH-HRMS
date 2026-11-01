<?php

namespace Tests\Feature\Services;

use App\Services\ContentSanitizerService;
use ReflectionProperty;
use Tests\TestCase;

class ContentSanitizerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetDbCharsetCache();
        parent::tearDown();
    }

    private function resetDbCharsetCache(): void
    {
        $prop = new ReflectionProperty(ContentSanitizerService::class, 'dbIsUtf8mb4');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_clean_returns_null_for_null_input(): void
    {
        $this->assertNull(ContentSanitizerService::clean(null));
    }

    public function test_clean_returns_empty_string_for_empty_input(): void
    {
        $this->assertSame('', ContentSanitizerService::clean(''));
    }

    public function test_clean_strips_invisible_artifacts_and_zero_width_chars(): void
    {
        $bom = "\xEF\xBB\xBF";
        $zwsp = "\xE2\x80\x8B";
        $softHyphen = "\xC2\xAD";
        $input = "{$bom}hello{$zwsp} world{$softHyphen}";

        $this->assertSame('hello world', ContentSanitizerService::clean($input));
    }

    public function test_clean_strips_control_chars_but_keeps_tab_newline_and_carriage_return(): void
    {
        $input = "\x00\x01\x07hello\x08\tworld\n\r\x0B";

        $this->assertSame("hello\tworld\n\r", ContentSanitizerService::clean($input));
    }

    public function test_clean_normalizes_three_or_more_blank_lines_into_two(): void
    {
        $input = "a\n\n\n\nb";

        $this->assertSame("a\n\nb", ContentSanitizerService::clean($input));
    }

    public function test_clean_skips_blank_line_normalization_inside_pre_and_code_blocks(): void
    {
        $input = "<pre>a\n\n\n\nb</pre>";

        $this->assertSame($input, ContentSanitizerService::clean($input));
    }

    public function test_clean_preserves_persian_utf8_content_intact(): void
    {
        $input = 'سلام دنیا، این یک متن فارسی است.';

        $this->assertSame($input, ContentSanitizerService::clean($input));
    }

    public function test_clean_truncates_overlong_input_under_the_byte_limit_without_splitting_multibyte_sequences(): void
    {
        $payload = str_repeat('سلام ', 20000);
        $cleaned = ContentSanitizerService::clean($payload);

        $this->assertLessThanOrEqual(65000, strlen($cleaned));
        $this->assertTrue(mb_check_encoding($cleaned, 'UTF-8'));
    }

    public function test_clean_replaces_four_byte_chars_when_database_charset_is_not_utf8mb4(): void
    {
        config(['database.default' => 'utf8only']);
        config(['database.connections.utf8only' => ['charset' => 'utf8']]);
        $this->resetDbCharsetCache();

        $emoji = '😀';
        $cleaned = ContentSanitizerService::clean("text {$emoji} end");

        $this->assertStringNotContainsString($emoji, $cleaned);
        $this->assertStringContainsString("\xEF\xBF\xBD", $cleaned);
        $this->assertStringContainsString('text', $cleaned);
        $this->assertStringContainsString('end', $cleaned);
    }
}