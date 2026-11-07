<?php

namespace App\Livewire\Dashboard\AiReport\Presentation;

use App\Models\AiReport;
use App\Services\AiReport\BlockCatalog;
use App\Support\AiReportAccessPolicy;
use App\Support\AiReportUploadLimits;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

final class AiReportPresenter
{
    public function canSave(AiReport $r): bool
    {
        return AiReportAccessPolicy::canSave($r, auth()->user());
    }

    public function canRefine(AiReport $r): bool
    {
        return AiReportAccessPolicy::canRefine($r, auth()->user());
    }

    public function canRename(AiReport $r): bool
    {
        return AiReportAccessPolicy::canRename($r, auth()->user());
    }

    public function canShare(AiReport $r): bool
    {
        return AiReportAccessPolicy::canShare($r, auth()->user());
    }

    public function canRevert(AiReport $r): bool
    {
        return AiReportAccessPolicy::canRevert($r, auth()->user());
    }

    public function dir(?array $spec): string
    {
        return (($spec['lang'] ?? 'fa') === 'en') ? 'ltr' : 'rtl';
    }

    public function letter(?array $spec): ?array
    {
        $lang = ($spec['lang'] ?? 'fa') === 'en' ? 'en' : 'fa';
        $blocks = collect($spec['pages'] ?? [])->pluck('blocks')->flatten(1)->filter(fn ($block) => is_array($block));
        $labels = [
            'figure' => $lang === 'en' ? 'Key figure' : 'رقم کلیدی',
            'decision' => $lang === 'en' ? 'Decision' : 'تصمیم',
            'assumption' => (string)config("ai_report.assumptions.heading.{$lang}"),
        ];

        $title = $blocks->firstWhere('type', 'title')['heading'] ?? $spec['title'] ?? null;
        $item = ($blocks->firstWhere('type', 'kpi_row') ?? $blocks->firstWhere('type', 'facts'))['items'][0] ?? null;
        $figure = filled($item['label'] ?? null) && filled($item['value'] ?? null) ? Arr::only($item, ['label', 'value']) : null;

        $callout = $blocks->first(fn(array $b): bool => ($b['type'] ?? null) === 'callout' && ($b['variant'] ?? null) === 'decision');
        $decision = filled($callout['paragraphs'][0] ?? null)
            ? ['heading' => $callout['heading'] ?? $labels['decision'], 'text' => $callout['paragraphs'][0]]
            : null;

        $section = $blocks->first(fn(array $b): bool => ($b['type'] ?? null) === 'section'
            && is_string($b['heading'] ?? null)
            && BlockCatalog::isAssumptionsHeading($b['heading'], $lang));
        $assumption = $section['paragraphs'][0] ?? $section['bullets'][0] ?? null;

        if (!filled($title) || ($figure === null && $decision === null && !filled($assumption))) {
            return null;
        }

        $lines = array_filter([
            $title,
            $figure ? "{$figure['label']}: {$figure['value']}" : null,
            $decision ? "{$decision['heading']}: {$decision['text']}" : null,
            filled($assumption) ? "{$labels['assumption']}: {$assumption}" : null,
        ]);

        return [
            'title' => $title,
            'figure' => $figure,
            'decision' => $decision,
            'assumption' => filled($assumption) ? $assumption : null,
            'labels' => $labels,
            'text' => implode("\n", $lines),
            'lang' => $lang,
            'dir' => $this->dir($spec),
        ];
    }

    public function blockLabel(AiReport $r, ?string $ref): ?string
    {
        $target = $ref !== null ? BlockCatalog::resolveBlock($r->spec ?? [], $ref) : null;
        if ($target === null) {
            return null;
        }
        $block = $target['block'];
        $names = ['title' => 'عنوان', 'kpi_row' => 'شاخص‌ها', 'summary' => 'خلاصه', 'table' => 'جدول', 'section' => 'بخش', 'callout' => 'یادداشت', 'facts' => 'اطلاعات پایه', 'action_items' => 'اقدام‌ها', 'progress' => 'پیشرفت', 'chart' => 'نمودار'];

        return is_string($block['heading'] ?? null)
            ? $block['heading']
            : (($names[$block['type'] ?? ''] ?? 'بخش') . ' ' . convertToPersian($target['b'] + 1));
    }

    public function failReason(?string $code): string
    {
        return $this->failText('fail_reasons', $code);
    }

    public function failToast(?string $code): string
    {
        return $this->failText('fail_toasts', $code);
    }

    public function failHint(?string $code): string
    {
        return $this->failText('fail_hints', $code);
    }

    private function failText(string $group, ?string $code): string
    {
        $base = "resources/ai_report/strings.{$group}.";

        return __($base . ($code !== null && Lang::has($base . $code, 'fa') ? $code : 'default'), [], 'fa');
    }

    public function listRow(AiReport $r, int $viewerId): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title ?? 'بدون عنوان',
            'chip' => $this->statusChip($r->status),
            'stamp' => $r->updated_at ? toJalali($r->updated_at, 'j F Y') : '—',
            'is_mine' => $r->user_id === $viewerId,
            'is_new' => $r->user_id !== $viewerId && !in_array($viewerId, array_map('intval', $r->seen_by ?? []), true),
            'shared_count' => count($r->recipients ?? []),
            'is_draft' => $r->saved_at === null,
            'is_fork' => $r->forked_from !== null,
            'active' => (bool)$r->active,
            'state' => !$r->active ? 'inactive' : ($r->saved_at === null ? 'draft' : 'saved'),
            'owner' => $r->relationLoaded('user') ? ($r->user?->name ?? '—') : null,
            'presets' => $this->presets($r),
        ];
    }

    public function presets(AiReport $r): array
    {
        return [
            'type' => config('ai_report.types.' . (string)($r->getAttribute('preset_type') ?? '') . '.label', '—'),
            'length' => config('ai_report.lengths.' . (string)($r->getAttribute('preset_length') ?? '') . '.label', '—'),
            'audience' => config('ai_report.audiences.' . (string)($r->getAttribute('preset_audience') ?? '') . '.label', '—'),
        ];
    }

    private ?array $limits = null;

    public function limits(): array
    {
        return $this->limits ??= $this->buildLimits();
    }

    private function buildLimits(): array
    {
        [$etaMin, $etaMax] = config('ai_report.eta_seconds');

        return [
            'files' => convertToPersian(config('ai_report.upload.max_files')),
            'per_file_mb' => convertToPersian((int)(AiReportUploadLimits::perFileKb() / 1024)),
            'recipients' => convertToPersian(AiReport::maxRecipients()),
            'inline_mb' => convertToPersian((int)(AiReportUploadLimits::inlineKb() / 1024)),
            'inline_total_mb' => convertToPersian((int)(AiReportUploadLimits::inlineTotalKb() / 1024)),
            'total_mb' => convertToPersian((int)(AiReportUploadLimits::totalKb() / 1024)),
            'prompt_min' => convertToPersian(config('ai_report.prompt.min')),
            'prompt_max' => convertToPersian(config('ai_report.prompt.max')),
            'versions' => convertToPersian(config('ai_report.max_versions')),
            'link_days' => convertToPersian(config('ai_report.share_link_days')),
            'stale_minutes' => convertToPersian(config('ai_report.stale_minutes')),
            'stale_draft_days' => convertToPersian(config('ai_report.stale_draft_days')),
            'eta' => convertToPersian($etaMin) . ' تا ' . convertToPersian($etaMax),
        ];
    }

    public function skippedFiles(AiReport $r): array
    {
        $labels = [
            'corrupt' => 'فایل خراب یا ناخوانا',
            'encrypted' => 'فایل رمزگذاری‌شده',
            'too_large' => 'حجم بیش از حد',
            'empty' => 'بدون متن قابل استخراج',
            'unsupported' => 'قالب پشتیبانی‌نشده',
        ];

        return collect($r->lastVersion()['skipped'] ?? [])
            ->map(fn(array $f): array => [
                'name' => $f['name'] ?? '',
                'label' => $labels[$f['reason'] ?? ''] ?? $labels['corrupt'],
            ])
            ->values()
            ->all();
    }

    public function notes(AiReport $r): array
    {
        return array_values($r->lastVersion()['notes'] ?? []);
    }

    public function statusChip(string $status): array
    {
        return match ($status) {
            AiReport::STATUS_GENERATING => ['label' => 'در حال تولید', 'icon' => 'progress_activity'],
            AiReport::STATUS_READY => ['label' => 'آماده', 'icon' => 'check_circle'],
            AiReport::STATUS_FAILED => ['label' => 'ناموفق', 'icon' => 'error'],
            default => ['label' => 'نامشخص', 'icon' => 'info'],
        };
    }

    private function untitled(array $spec): array
    {
        $spec = Arr::except($spec, 'title');

        foreach ($spec['pages'][0]['blocks'] ?? [] as $i => $block) {
            if (($block['type'] ?? null) === 'title') {
                unset($spec['pages'][0]['blocks'][$i]['heading']);
                break;
            }
        }

        return $spec;
    }

    public function versions(AiReport $r): array
    {
        $versions = array_values($r->versions ?? []);
        $current = null;
        $now = $this->untitled($r->spec ?? []);

        for ($i = count($versions) - 1; $i >= 0; $i--) {
            if (isset($versions[$i]['spec']) && $this->untitled($versions[$i]['spec']) === $now) {
                $current = $i;
                break;
            }
        }

        return collect($versions)
            ->map(fn(array $v, int $i): array => [
                'index' => $i,
                'label' => 'نسخهٔ ' . convertToPersian($i + 1) . (($v['manual'] ?? false) ? ' · دستی' : ''),
                'stamp' => ($v['at'] ?? null) ? toJalali($v['at'], 'j F Y') : '—',
                'is_current' => $i === $current,
                'has_spec' => isset($v['spec']),
                'type' => config('ai_report.types.' . (string)($v['type'] ?? '') . '.label'),
            ])
            ->all();
    }

    public function originalPrompt(AiReport $r): ?array
    {
        $versions = array_values($r->versions ?? []);
        $prompt = $versions[0]['prompt'] ?? null;

        if (!filled($prompt)) {
            return null;
        }

        return [
            'text' => Str::limit($prompt, 240),
            'trimmed' => (bool)($versions[0]['trimmed'] ?? false),
        ];
    }
}
