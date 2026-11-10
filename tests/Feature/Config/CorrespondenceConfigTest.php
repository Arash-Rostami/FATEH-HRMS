<?php

namespace Tests\Feature\Config;

use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Models\Letterhead;
use Tests\TestCase;

class CorrespondenceConfigTest extends TestCase
{
    public function test_ai_settings_read_by_correspondence_are_present_and_limits_are_sane(): void
    {
        $config = config('ai.correspondence');

        foreach (['types', 'classifications', 'urgencies', 'delivery_modes', 'max_recipients', 'max_blocks', 'max_versions', 'max_batch', 'prompt', 'upload', 'provider', 'job', 'lock'] as $key) {
            $this->assertNotEmpty($config[$key] ?? null, "ai.correspondence.{$key} must be configured");
        }

        $limits = LetterForm::limits();
        $this->assertGreaterThan(0, $limits['recipients']);
        $this->assertGreaterThan(0, $limits['blocks']);
        $this->assertGreaterThan($config['body_bytes'], $config['spec_bytes']);
        $this->assertLessThanOrEqual(72, (int) $config['sign_code_min_length']);
        $this->assertSame(['fa', 'en'], $config['languages']);
    }

    public function test_the_font_registry_is_well_formed_and_every_declared_file_of_a_required_font_exists(): void
    {
        $fonts = config('ai.correspondence.fonts');

        $this->assertNotEmpty($fonts);
        foreach ($fonts as $key => $font) {
            $this->assertMatchesRegularExpression('/^[\p{L}\p{N} ,\-]{1,100}$/u', (string) $key, "font key [{$key}] must fit the sanitizer font-family pattern");
            $this->assertContains($font['script'], ['fa', 'latin'], $key);
            $this->assertNotEmpty($font['label_fa'], $key);
            $this->assertNotEmpty($font['label_en'], $key);
            $this->assertIsArray($font['files'], $key);
            if (array_key_exists('otl', $font)) {
                $this->assertIsBool($font['otl'], $key);
            }

            foreach ($font['files'] as $face => $formats) {
                $this->assertContains($face, ['R', 'B', 'I', 'BI'], $key);
                $this->assertArrayHasKey('ttf', $formats, "{$key} {$face} needs a ttf for mPDF");
                foreach ($formats as $format => $file) {
                    $this->assertContains($format, ['woff2', 'woff', 'ttf'], $key);
                    if (($font['optional'] ?? false) !== true) {
                        $this->assertFileExists(resource_path('assets/fonts/' . $file), "{$key}: {$file}");
                    }
                }
            }

            if (($font['optional'] ?? false) === true) {
                $fallback = $fonts[$font['fallback'] ?? ''] ?? null;
                $this->assertNotNull($fallback, "optional font [{$key}] needs a registered fallback");
                $this->assertNotTrue($fallback['optional'] ?? false, $key);
                $this->assertSame($font['script'], $fallback['script'], $key);
            }
        }
    }

    public function test_the_registry_carries_the_keys_the_letterhead_defaults_and_old_letters_rely_on(): void
    {
        $fonts = config('ai.correspondence.fonts');

        foreach (['nazanin', 'iranyekan', 'parastoo', 'bnazanin', 'btitr', 'vazirmatn', 'notonaskh', 'Times New Roman', 'tinos', 'inter'] as $key) {
            $this->assertArrayHasKey($key, $fonts);
        }
        $this->assertSame('fa', $fonts[Letterhead::defaultStyle()['font']]['script']);
        $this->assertSame('latin', $fonts[Letterhead::defaultStyle()['latin_font']]['script']);
        $this->assertFalse($fonts['nazanin']['otl']);
        $this->assertFalse($fonts['notonaskh']['otl']);
        $this->assertTrue($fonts['parastoo']['otl']);
    }
}
