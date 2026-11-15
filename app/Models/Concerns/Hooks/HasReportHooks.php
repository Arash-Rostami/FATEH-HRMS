<?php

namespace App\Models\Concerns\Hooks;

use Illuminate\Validation\ValidationException;

trait HasReportHooks
{
    protected static function bootHasReportHooks(): void
    {
        static::saving(self::guardExpiryBeforeDateOnSaving(...));
        static::deleting(self::deleteFilesOnDeleting(...));
    }

    private static function guardExpiryBeforeDateOnSaving(self $report)
    {
        if ($report->isDirty(['report_date', 'expires_at']) && $report->report_date && $report->expires_at && $report->expires_at->lt($report->report_date)) {
            throw ValidationException::withMessages(['data.expires_at' => __('resources/report/strings.validation.expiry_before_date')]);
        }
    }

    private static function deleteFilesOnDeleting(self $report)
    {
        static::deleteStoredFiles($report->cover_image);
        static::deleteStoredFiles($report->file_path);
    }
}
