<?php

namespace App\Models\Concerns\Hooks;

use App\Services\Menu\StateService;
use Illuminate\Support\Facades\Storage;

trait HasAiReportHooks
{
    protected static function bootHasAiReportHooks(): void
    {
        static::deleting(self::deleteStagedUploads(...));
        static::deleted(self::flushMenuStateWhenShared(...));
        static::updated(fn (self $report) => $report->wasChanged('active') ? self::flushMenuStateWhenShared($report) : null);
    }

    private static function deleteStagedUploads(self $report): void
    {
        Storage::disk('local')->deleteDirectory($report->tempDirectory());
    }

    private static function flushMenuStateWhenShared(self $report): void
    {
        if (filled($report->recipients)) {
            StateService::flush();
        }
    }
}
