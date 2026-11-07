<?php

namespace App\Livewire\Dashboard\AiReport\Actions;

use App\Jobs\GenerateReportJob;
use App\Livewire\Dashboard\AiReport\Forms\ComposerForm;
use App\Models\AiReport;
use App\Models\User;
use App\Services\AiReport\BlockCatalog;
use App\Services\AiReport\FileExtractor;
use App\Support\AiReportAccessPolicy;
use App\Support\AiReportUploadLimits;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GenerateReportAction
{
    private const BUSY = 'یک گزارش در حال تولید است';

    private const QUOTA = "\u{0633}\u{0647}\u{0645}\u{06CC}\u{0647}\u{0654} \u{0627}\u{0645}\u{0631}\u{0648}\u{0632} \u{0634}\u{0645}\u{0627} \u{0628}\u{0647} \u{067E}\u{0627}\u{06CC}\u{0627}\u{0646} \u{0631}\u{0633}\u{06CC}\u{062F}\u{0647} \u{0627}\u{0633}\u{062A}.";

    public function execute(ComposerForm $form, User $user, ?AiReport $parent = null): AiReport
    {
        $form->validated();
        if (!AiReportAccessPolicy::canGenerate($user) || ($parent && !AiReportAccessPolicy::canRefine($parent, $user))) {
            throw ValidationException::withMessages(['form.prompt' => 'تولید گزارش برای شما مجاز نیست.']);
        }
        $staged = $this->stage($form);
        if (AiReport::quotaUsed($user->id) >= AiReport::quotaCap()) {
            throw ValidationException::withMessages(['form.prompt' => self::QUOTA]);
        }

        $names = array_column($staged, 'name');
        try {
            $report = Cache::lock('ai-report:generate:' . $user->id, (int) config('ai_report.lock.ttl'))->block((int) config('ai_report.lock.wait'), function () use ($user, $parent, $form, $names) {
                if (AiReport::quotaUsed($user->id) >= AiReport::quotaCap()) {
                    throw ValidationException::withMessages(['form.prompt' => self::QUOTA]);
                }
                $report = DB::transaction(function () use ($user, $parent, $form, $names) {
                    $this->assertIdle($user->id);
                    if (!$parent) {
                        return AiReport::query()->create([
                            'user_id' => $user->id,
                            'status' => AiReport::STATUS_GENERATING,
                            'versions' => [$this->version($form, $names)],
                        ] + $this->presets($form));
                    }

                    $locked = AiReport::query()->whereKey($parent->id)->lockForUpdate()->first();
                    if (!$locked || !AiReportAccessPolicy::canRefine($locked, $user)) {
                        throw ValidationException::withMessages(['form.prompt' => 'این گزارش قابل بازبینی نیست.']);
                    }
                    $parentSpec = $locked->spec;
                    if (empty($parentSpec) && $locked->status === AiReport::STATUS_FAILED) {
                        $parentSpec = $locked->lastSpecVersion()['spec'] ?? $locked->lastVersion()['parent_spec'] ?? null;
                    }
                    if (is_array($parentSpec)) {
                        $block = null;
                        if (is_string($form->block) && BlockCatalog::resolveBlock($parentSpec, $form->block) !== null) {
                            $block = $form->block;
                        }
                    }
                    $version = $this->version($form, $names, is_array($parentSpec) ? $parentSpec : null, $block ?? null);
                    if (filled($locked->recipients)) {
                        $version['forked_from'] = $locked->id;

                        return AiReport::query()->create([
                            'user_id' => $locked->user_id,
                            'status' => AiReport::STATUS_GENERATING,
                            'forked_from' => $locked->id,
                            'versions' => [$version],
                        ] + $this->presets($form));
                    }

                    $locked->status = AiReport::STATUS_GENERATING;
                    $locked->fail_reason = null;
                    $locked->pushVersion($version);
                    $locked->save();

                    return $locked;
                });
                $n = AiReport::consumeQuota($user->id);
                if ($n > AiReport::quotaCap()) {
                    $report->markFailed('quota');
                    throw ValidationException::withMessages(['form.prompt' => self::QUOTA]);
                }

                return $report;
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['form.prompt' => self::BUSY]);
        }

        try {
            Storage::disk('local')->makeDirectory($report->tempDirectory());
            foreach ($staged as $item) {
                $stored = $item['file']->storeAs($report->tempDirectory(), $item['stored'], 'local');
                if ($stored === false) {
                    throw new \RuntimeException('store failed');
                }
                if ($item['file'] instanceof TemporaryUploadedFile) {
                    try {
                        $item['file']->delete();
                    } catch (\Throwable) {
                    }
                }
            }
        } catch (\Throwable $e) {
            $report->markFailed('exception');
            throw $e;
        }

        try {
            GenerateReportJob::dispatch($report->id);
        } catch (\Throwable $e) {
            $report->markFailed('exception');
            throw $e;
        }

        return $report;
    }

    private function assertIdle(int $userId): void
    {
        $busy = AiReport::query()->freshGenerating($userId)
            ->lockForUpdate()
            ->exists();
        if ($busy) {
            throw ValidationException::withMessages(['form.prompt' => self::BUSY]);
        }
    }

    private function stage(ComposerForm $form): array
    {
        $staged = [];
        $total = 0;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $cap = AiReportUploadLimits::totalKb() * 1024;
        $inlineCap = AiReportUploadLimits::inlineKb() * 1024;
        $inlineTotal = 0;
        $inlineTotalCap = AiReportUploadLimits::inlineTotalKb() * 1024;
        foreach ($form->files as $file) {
            if (!$file instanceof UploadedFile) {
                throw ValidationException::withMessages(['form.files' => 'فایل پیوست معتبر نیست.']);
            }
            $client = basename(str_replace('\\', '/', (string) $file->getClientOriginalName()));
            $path = $file->getRealPath() ?: $file->getPathname();
            $mime = $finfo->file($path) ?: '';
            if (ComposerForm::isEncryptedOffice($mime, $client)) {
                throw ValidationException::withMessages(['form.files' => ComposerForm::encryptedFileMessage()]);
            }
            if ($client === '' || !isset(FileExtractor::MIMES[$mime])) {
                throw ValidationException::withMessages(['form.files' => ComposerForm::unsupportedFileMessage()]);
            }
            $size = $file->getSize();
            $size = is_int($size) ? $size : (int) (filesize($path) ?: 0);
            if (FileExtractor::MIMES[$mime][0] === 'inline' && $size > $inlineCap) {
                throw ValidationException::withMessages(['form.files' => "\u{062D}\u{062C}\u{0645} \u{0647}\u{0631} \u{0641}\u{0627}\u{06CC}\u{0644} PDF \u{06CC}\u{0627} \u{062A}\u{0635}\u{0648}\u{06CC}\u{0631} \u{0646}\u{0628}\u{0627}\u{06CC}\u{062F} \u{0628}\u{06CC}\u{0634} \u{0627}\u{0632} " . convertToPersian(intdiv($inlineCap, 1024 * 1024)) . " \u{0645}\u{06AF}\u{0627}\u{0628}\u{0627}\u{06CC}\u{062A} \u{0628}\u{0627}\u{0634}\u{062F}."]);
            }
            if (FileExtractor::MIMES[$mime][0] === 'inline') {
                $inlineTotal += $size;
                if ($inlineTotal > $inlineTotalCap) {
                    throw ValidationException::withMessages(['form.files' => 'حجم مجموع فایل‌های PDF و تصویر نباید بیش از ' . convertToPersian(intdiv($inlineTotalCap, 1024 * 1024)) . ' مگابایت باشد.']);
                }
            }
            $total += $size;
            if ($total > $cap) {
                throw ValidationException::withMessages(['form.files' => 'حجم مجموع فایل‌ها نباید بیش از ' . convertToPersian(intdiv($cap, 1024 * 1024)) . ' مگابایت باشد.']);
            }
            $staged[] = [
                'file' => $file,
                'name' => $client,
                'stored' => $this->storedName(count($staged) + 1, $client, FileExtractor::MIMES[$mime][1]),
            ];
        }

        return $staged;
    }

    private function presets(ComposerForm $form): array
    {
        return [
            'preset_type' => $form->type,
            'preset_length' => $form->length,
            'preset_audience' => $form->audience,
        ];
    }

    private function version(ComposerForm $form, array $files, ?array $parentSpec = null, ?string $block = null): array
    {
        $version = [
            'prompt' => $form->prompt,
            'files' => $files,
            'type' => $form->type,
            'length' => $form->length,
            'audience' => $form->audience,
            'at' => now()->toIso8601String(),
            'quota_day' => now()->format('Y-m-d'),
        ];
        if ($parentSpec !== null) {
            $version['parent_spec'] = $parentSpec;
        }
        if ($block !== null) {
            $version['block'] = $block;
        }

        return $version;
    }

    private function storedName(int $index, string $client, string $ext): string
    {
        $stem = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', pathinfo($client, PATHINFO_FILENAME)) ?? '';
        $stem = mb_substr($stem, 0, 100);

        return $index . '-' . ($stem !== '' ? $stem : 'file') . '.' . $ext;
    }
}
