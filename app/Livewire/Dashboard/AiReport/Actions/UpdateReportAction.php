<?php

namespace App\Livewire\Dashboard\AiReport\Actions;

use App\Models\AiReport;
use App\Models\User;
use App\Services\AiReport\BlockCatalog;
use App\Support\AiReportAccessPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateReportAction
{
    public const STALE_EDITOR = 409;

    public function execute(AiReport $report, User $user, array $changes): void
    {
        $this->authorize($report, $user, $changes);

        DB::transaction(function () use ($report, $user, $changes) {
            $fresh = AiReport::query()->whereKey($report->id)->lockForUpdate()->first();
            if (!$fresh) {
                $this->deny();
            }
            $this->authorize($fresh, $user, $changes);
            $this->apply($fresh, $changes);
            $report->setRawAttributes($fresh->getAttributes(), true);
        });
    }

    private function authorize(AiReport $report, User $user, array $changes): void
    {
        if (array_key_exists('save', $changes) && !AiReportAccessPolicy::canSave($report, $user)) {
            $this->deny();
        }
        if (array_key_exists('revert_to', $changes) && !AiReportAccessPolicy::canRevert($report, $user)) {
            $this->deny();
        }
        if (array_key_exists('title', $changes) && !AiReportAccessPolicy::canRename($report, $user)) {
            $this->deny();
        }
        if (array_key_exists('block', $changes) && !AiReportAccessPolicy::canRevert($report, $user)) {
            $this->deny();
        }
        if (array_key_exists('active', $changes) && !AiReportAccessPolicy::canToggleActive($report, $user)) {
            $this->deny();
        }
    }

    private function apply(AiReport $report, array $changes): void
    {
        if (($changes['save'] ?? null) === true) {
            $report->saved_at ??= now();
        }
        if (array_key_exists('revert_to', $changes)) {
            $version = $report->versions[(int) $changes['revert_to']] ?? [];
            $spec = $version['spec'] ?? null;
            if (!is_array($spec)) {
                $this->deny();
            }
            foreach (['type', 'length', 'audience'] as $key) {
                $report->{"preset_{$key}"} = is_string($version[$key] ?? null) ? $version[$key] : null;
            }
            $report->spec = $spec;
            $report->title = $spec['title'] ?? $report->title;
        }
        if (array_key_exists('title', $changes)) {
            $t = BlockCatalog::clean((string) $changes['title'], (int) config('ai_report.spec.short'));
            if ($t === '') {
                throw ValidationException::withMessages(['titleDraft' => 'عنوان نمی‌تواند خالی باشد.']);
            }
            $spec = $report->spec ?? [];
            $spec['title'] = $t;
            foreach ($spec['pages'][0]['blocks'] ?? [] as $i => $block) {
                if (($block['type'] ?? null) === 'title') {
                    $spec['pages'][0]['blocks'][$i]['heading'] = $t;
                    break;
                }
            }
            $report->spec = $spec;
            $report->title = $t;
        }
        if (array_key_exists('block', $changes)) {
            $ref = (string) $changes['block'];
            $target = BlockCatalog::resolveBlock($report->spec ?? [], $ref);
            if ($target === null) {
                $this->deny();
            }
            ['p' => $p, 'b' => $b, 'block' => $original] = $target;
            if (is_string($changes['editor_hash'] ?? null) && $changes['editor_hash'] !== md5(json_encode($original))) {
                throw new \RuntimeException('گزارش در جای دیگری تغییر کرده؛ پنجره را ببندید و دوباره باز کنید.', self::STALE_EDITOR);
            }
            $data = is_array($changes['data'] ?? null) ? $changes['data'] : [];
            $data['type'] = $original['type'];
            $lang = ($report->spec['lang'] ?? 'fa') === 'en' ? 'en' : 'fa';
            $normalized = BlockCatalog::normalizeBlock(BlockCatalog::retotal($data, $original), $lang);
            if ($normalized === null) {
                throw ValidationException::withMessages(['blockDraft' => 'محتوای بخش ناقص است؛ عنوان یا متن لازم را خالی نگذارید.']);
            }
            foreach (['items', 'rows', 'bullets', 'paragraphs', 'options', 'labels', 'series'] as $list) {
                if (is_array($original[$list] ?? null) && count($original[$list]) > count($normalized[$list] ?? [])) {
                    throw ValidationException::withMessages(['blockDraft' => 'محتوای بخش ناقص است؛ عنوان یا متن لازم را خالی نگذارید.']);
                }
            }
            if ($original['type'] === 'section' && is_string($original['heading'] ?? null) && BlockCatalog::isAssumptionsHeading($original['heading'], $lang)) {
                $normalized['heading'] = $original['heading'];
            }
            $spec = $report->spec;
            $spec['pages'][$p]['blocks'][$b] = $normalized;
            if ($original['type'] === 'title') {
                $spec['title'] = $normalized['heading'];
            }
            try {
                $report->spec = BlockCatalog::validate($spec);
            } catch (\InvalidArgumentException) {
                throw ValidationException::withMessages(['blockDraft' => 'این تغییر گزارش را از سقف مجاز بیرون می‌برد؛ متن را کوتاه‌تر کنید.']);
            }
            if ($original['type'] === 'title') {
                $report->title = $spec['title'];
            }
            $report->pushVersion(['prompt' => 'ویرایش دستی', 'files' => [], 'manual' => true, 'spec' => $report->spec, 'at' => now()->toIso8601String(), 'quota_day' => now()->format('Y-m-d')]);
        }
        if (array_key_exists('active', $changes)) {
            $report->active = (bool) $changes['active'];
        }
        $report->save();
    }

    private function deny(): never
    {
        throw new \RuntimeException('این عملیات مجاز نیست.');
    }
}
