<?php

namespace App\Support;

final class AiReportUploadLimits
{
    public static function perFileKb(): int
    {
        return self::global('report') ?: (int) config('file_upload_limits.' . config('ai_report.upload.tier', 'document'));
    }

    public static function inlineKb(): int
    {
        return self::global('inline') ?: (int) config('ai_report.upload.inline_kb');
    }

    public static function inlineTotalKb(): int
    {
        return self::global('inline_total') ?: (int) config('ai_report.upload.inline_total_kb');
    }

    public static function totalKb(): int
    {
        return self::global('total') ?: (int) config('ai_report.upload.total_kb');
    }

    private static function global(string $key): int
    {
        return max(0, (int) config("file_upload_limits.ai.{$key}"));
    }
}
