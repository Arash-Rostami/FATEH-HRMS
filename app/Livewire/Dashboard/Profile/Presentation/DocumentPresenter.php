<?php

namespace App\Livewire\Dashboard\Profile\Presentation;

use App\Models\Traits\HasPublicAssetUrl;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DocumentPresenter
{
    use HasPublicAssetUrl;

    public function parseAttachments(?array $attachments): Collection
    {
        return collect($attachments ?? [])
            ->filter(fn ($item) => is_array($item) && isset($item['path'], $item['category'], $item['key']))
            ->map(fn ($item) => [
                'category'     => $item['category'],
                'key'          => $item['key'],
                'path'         => $item['path'],
                'url'          => self::resolvePublicAssetUrl($item['path']),
                'fileName'     => basename($item['path']),
                'uploadedTime' => isset($item['uploaded_at'])
                    ? Carbon::parse($item['uploaded_at'])->timezone('Asia/Tehran')->format('Y/m/d H:i')
                    : Carbon::createFromTimestamp(
                        (int) preg_replace('/.*_(\d{10,})\.\w+$/', '$1', basename($item['path'])),
                        'Asia/Tehran'
                    )->format('Y/m/d H:i'),
            ])
            ->values();
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
