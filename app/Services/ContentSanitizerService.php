<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class ContentSanitizerService
{
    private const MAX_BYTES = 65000;

    /** Invisible/formatting artifacts from copy-paste. Safe to strip from HTML. */
    private const ARTIFACTS = [
        "\xEF\xBB\xBF",       // UTF-8 BOM
        "\xE2\x80\x8B",       // Zero-width space
        "\xE2\x80\x8C",       // Zero-width non-joiner
        "\xE2\x80\x8D",       // Zero-width joiner
        "\xE2\x80\xAF",       // Narrow no-break space
        "\xE2\x81\x9F",       // Medium mathematical space
        "\xE3\x80\x80",       // Ideographic space
        "\xE2\x80\x8E",       // Left-to-right mark
        "\xE2\x80\x8F",       // Right-to-left mark
        "\xE2\x80\xAA",       // LTR embedding
        "\xE2\x80\xAB",       // RTL embedding
        "\xE2\x80\xAC",       // Pop directional formatting
        "\xE2\x80\xAD",       // LTR override
        "\xE2\x80\xAE",       // RTL override
        "\xEF\xBF\xBC",       // Object replacement character
    ];

    private static ?bool $dbIsUtf8mb4 = null;

    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $html = self::toUtf8($html);
        $html = self::stripArtifacts($html);
        $html = self::stripControlChars($html);
        $html = self::normalizeBlankLines($html);

        if (!self::dbIsUtf8mb4()) {
            // Replace 4-byte chars with replacement char (or strip with '' if you prefer)
            $html = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $html);
        }

        return self::truncateUtf8Bytes($html, self::MAX_BYTES);
    }

    private static function dbIsUtf8mb4(): bool
    {
        if (self::$dbIsUtf8mb4 !== null) return self::$dbIsUtf8mb4;


        $charset = Config::get(
            'database.connections.' . Config::get('database.default') . '.charset',
            'utf8mb4'
        );

        return self::$dbIsUtf8mb4 = stripos($charset, 'utf8mb4') !== false;
    }

    private static function normalizeBlankLines(string $v): string
    {
        if (preg_match('/<(pre|code)[\s>]/i', $v)) {
            return $v;
        }
        return preg_replace('/\n{3,}/', "\n\n", $v);
    }

    private static function stripArtifacts(string $v): string
    {
        $v = str_replace(self::ARTIFACTS, '', $v);
        $v = preg_replace('/[\x{00AD}\x{2060}\x{FEFF}]/u', '', $v); // soft hyphen, word joiner, BOM

        return $v;
    }

    private static function stripControlChars(string $v): string
    {
        // Keep: \x09 tab, \x0A LF, \x0D CR. Strip everything else in ASCII control range.
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $v);
    }

    private static function toUtf8(string $v): string
    {
        if (!mb_check_encoding($v, 'UTF-8')) {
            $encoding = mb_detect_encoding($v, ['ISO-8859-1', 'Windows-1252', 'UTF-16'], true);

            if ($encoding) {
                $v = mb_convert_encoding($v, 'UTF-8', $encoding);
            }
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $v);

        return $clean === false ? '' : $clean;
    }

    private static function truncateUtf8Bytes(string $v, int $limit): string
    {
        if (strlen($v) <= $limit) {
            return $v;
        }

        $truncated = substr($v, 0, $limit);
        $length = strlen($truncated);

        while ($length > 0 && (ord($truncated[$length - 1]) & 0xC0) === 0x80) {
            $length--;
        }

        $truncated = substr($truncated, 0, $length);

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $truncated);

        return $clean === false ? '' : $clean;
    }
}
