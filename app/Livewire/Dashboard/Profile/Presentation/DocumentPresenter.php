<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentPresenter
{

    public function parseAttachments(?array $attachments): Collection
    {
        $parsed = collect();

        foreach ($attachments ?? [] as $path) {
            if (!is_string($path)) {
                continue;
            }

            $fileName = basename($path);
            $normalizedFileName = str_replace('__', '_', $fileName);

            if (preg_match('/doc_(standard|custom)_(.+)__?(\d{10,})\.\w+/', $normalizedFileName, $matches)) {
                $category = $matches[1];
                $keyOrSlug = $matches[2];
                $timestamp = (int) $matches[3];

                $parsed->push([
                    'category' => $category,
                    'key' => $keyOrSlug,
                    'uploadedTime' => Carbon::createFromTimestamp($timestamp, 'Asia/Tehran')->format('Y/m/d H:i'),
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                    'fileName' => $fileName,
                ]);
            }
        }

        return $parsed;
    }


    public function standardTypes(): array
    {
        return [
            'shenasnameh' => ['label' => 'تمام صفحات شناسنامه', 'icon' => 'badge'],
            'national_id' => ['label' => 'پشت و روی کارت ملی', 'icon' => 'id_card'],
            'diploma' => ['label' => 'آخرین مدرک تحصیلی', 'icon' => 'school'],
            'military_service' => ['label' => 'کارت پایان خدمت یا معافیت', 'icon' => 'military_tech'],
            'insurance_record' => ['label' => 'کلیه سوابق بیمه', 'icon' => 'receipt_long'],
        ];
    }
}
