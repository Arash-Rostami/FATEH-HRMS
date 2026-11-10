<?php

namespace App\Services\Correspondence;

use App\Models\Letterhead;

class CorrespondenceFonts
{
    private const FACES = [
        'R' => ['normal', 400],
        'B' => ['normal', 700],
        'I' => ['italic', 400],
        'BI' => ['italic', 700],
    ];

    private const FORMATS = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype'];

    public static function all(): array
    {
        $fonts = [];

        foreach (self::registry() as $key => $font) {
            if (($font['optional'] ?? false) === true) {
                $font['files'] = array_filter((array) $font['files'], fn (array $formats): bool => is_file(resource_path('assets/fonts/' . ($formats['ttf'] ?? '-'))));
                if (!isset($font['files']['R'])) {
                    continue;
                }
            }
            $fonts[$key] = $font;
        }

        return $fonts;
    }

    public static function keys(?string $script = null): array
    {
        return array_keys(self::of($script));
    }

    public static function known(string $script): array
    {
        return array_keys(array_filter(self::registry(), fn (array $font): bool => ($font['script'] ?? null) === $script));
    }

    public static function options(?string $script = null): array
    {
        return array_map(fn (array $font): string => (string) ($font['script'] === 'latin' ? $font['label_en'] : $font['label_fa']), self::of($script));
    }

    public static function pick(mixed $value, string $script): string
    {
        $keys = self::keys($script);

        if (!is_string($value)) {
            return self::fallback($script);
        }

        if (in_array($value, $keys, true)) {
            return $value;
        }

        $substitute = self::registry()[$value]['fallback'] ?? null;

        return is_string($substitute) && in_array($substitute, $keys, true) ? $substitute : self::fallback($script);
    }

    public static function used(array $style, array $blocks): array
    {
        $keys = [self::pick($style['font'] ?? null, 'fa'), self::pick($style['latin_font'] ?? null, 'latin')];
        $json = (string) json_encode($blocks);

        if (str_contains($json, 'font-family')) {
            foreach (self::keys() as $key) {
                if (preg_match('/font-family:\s*[^;"\\\\]*\b' . preg_quote($key, '/') . '\b/iu', $json) === 1) {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    public static function fontFace(array $keys): string
    {
        $css = '';
        $all = self::all();

        foreach ($keys as $key) {
            foreach ((array) ($all[$key]['files'] ?? []) as $face => $formats) {
                if (!isset(self::FACES[$face])) {
                    continue;
                }
                [$style, $weight] = self::FACES[$face];
                $sources = [];
                foreach (self::FORMATS as $format => $label) {
                    if (isset($formats[$format])) {
                        $sources[] = 'url("' . asset('build/assets/fonts/' . $formats[$format]) . '") format("' . $label . '")';
                    }
                }
                if ($sources !== []) {
                    $css .= '@font-face { font-family: "' . $key . '"; font-style: ' . $style . '; font-weight: ' . $weight . '; font-display: swap; src: ' . implode(', ', $sources) . "; }\n";
                }
            }
        }

        return $css;
    }

    public static function pdfData(): array
    {
        $data = [];

        foreach (self::all() as $key => $font) {
            $entry = [];
            foreach (array_keys(self::FACES) as $face) {
                if (isset($font['files'][$face]['ttf'])) {
                    $entry[$face] = $font['files'][$face]['ttf'];
                }
            }
            if ($entry === []) {
                continue;
            }
            if (($font['otl'] ?? null) === true) {
                $entry += ['useOTL' => 0xFF, 'useKashida' => 75];
            } elseif (($font['otl'] ?? null) === false) {
                $entry += ['useOTL' => 0];
            }
            $data[strtolower(str_replace([' ', '-'], '', (string) $key))] = $entry;
        }

        return $data;
    }

    private static function registry(): array
    {
        return array_filter((array) config('ai.correspondence.fonts'), 'is_array');
    }

    private static function fallback(string $script): string
    {
        $default = (string) Letterhead::defaultStyle()[$script === 'fa' ? 'font' : 'latin_font'];

        if (in_array($default, self::keys($script), true)) {
            return $default;
        }

        $substitute = self::registry()[$default]['fallback'] ?? null;

        return is_string($substitute) ? $substitute : $default;
    }

    private static function of(?string $script): array
    {
        return $script === null
            ? self::all()
            : array_filter(self::all(), fn (array $font): bool => ($font['script'] ?? null) === $script);
    }
}
