<?php

namespace App\Livewire\Dashboard\Dms\Presentation;

use App\Models\DMS;

class DmsPresenter
{
    public function columns(): array
    {
        return [
            'title'  => 'عنوان سند',
            'code'   => 'نسخه',
            'dept'   => 'واحد(های) ذی نفع',
            'status' => 'وضعیت',
            'details'=> 'جزییات',
            'desc'   => 'توضیحات',
            'action' => 'مشاهده و تایید',
        ];
    }

    public function sortIsDefault(string $sort, string $sortDir): bool
    {
        return $sort === 'updated' && $sortDir === 'desc';
    }

    public function rowState(DMS $doc, array $confirmedDocs, array $readDocs): array
    {
        $isConfirmed = in_array($doc->id, $confirmedDocs);
        $isRead = in_array($doc->id, $readDocs);
        $cat = optional($doc->extra)['category'] ?? optional($doc->extra)['Category'];
        $extraDetails = collect($doc->extra ?? [])->except(['category', 'Category', 'type', 'Type', 'users']);
        $cleanTitle = superClean($doc->title ?? 'بدون عنوان');
        $statusColor = !$isConfirmed
            ? 'var(--md-sys-color-error)'
            : (($isConfirmed && !$isRead)
                ? 'var(--md-sys-color-tertiary)'
                : 'var(--md-sys-color-primary)');

        return [
            'isConfirmed' => $isConfirmed,
            'isRead' => $isRead,
            'cat' => $cat,
            'extraDetails' => $extraDetails,
            'cleanTitle' => $cleanTitle,
            'statusColor' => $statusColor,
            'deptLabels' => $doc->getDepartmentTooltipLabels(),
        ];
    }

    public function versionPopover(DMS $doc): bool
    {
        return !empty($doc->created_at) || !empty($doc->updated_at);
    }

    public function extensionIcon(?string $ext): array
    {
        return match (strtolower($ext ?? '')) {
            'pdf'                => ['icon' => 'picture_as_pdf', 'label' => 'سند PDF', 'bg' => 'bg-[var(--md-sys-color-error-container)]', 'text' => 'text-[var(--md-sys-color-on-error-container)]'],
            'xlsx', 'xls', 'csv' => ['icon' => 'table_chart', 'label' => 'فایل اکسل', 'bg' => 'bg-[var(--md-sys-color-tertiary-container)]', 'text' => 'text-[var(--md-sys-color-on-tertiary-container)]'],
            'docx', 'doc'        => ['icon' => 'description', 'label' => 'سند Word', 'bg' => 'bg-[var(--md-sys-color-primary-container)]', 'text' => 'text-[var(--md-sys-color-on-primary-container)]'],
            default              => ['icon' => 'insert_drive_file', 'label' => 'فایل ضمیمه', 'bg' => 'bg-[var(--md-sys-color-surface-variant)]', 'text' => 'text-[var(--md-sys-color-on-surface-variant)]'],
        };
    }

    public function renditionData(array $rendition, string $docFile): array
    {
        $icon = $this->extensionIcon($rendition['ext'] ?? null);

        return [
            'icon' => $icon['icon'],
            'label' => $icon['label'],
            'bg' => $icon['bg'],
            'text' => $icon['text'],
            'route' => $rendition['path'] === $docFile
                ? route('secure-file', $rendition['path'])
                : route('secure-extra-file', $rendition['path']),
        ];
    }
}