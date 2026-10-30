<?php

namespace App\Models\Concerns\Hooks;

trait HasReportHooks
{
    protected static function bootHasReportHooks(): void
    {
        static::deleting(self::deleteFilesOnDeleting(...));
    }

    private static function deleteFilesOnDeleting(self $report)
    {
        static::deleteStoredFiles($report->cover_image);
        static::deleteStoredFiles($report->file_path);
    }
}
