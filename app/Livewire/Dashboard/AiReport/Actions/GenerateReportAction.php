<?php

namespace App\Livewire\Dashboard\AiReport\Actions;

use App\Jobs\GenerateReportJob;
use App\Livewire\Dashboard\AiReport\Forms\ComposerForm;
use App\Models\AiReport;
use App\Models\User;
use App\Services\AiReport\BlockCatalog;
use App\Services\AiReport\FileExtractor;
use App\Support\AiReportAccessPolicy;
use finfo;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

class GenerateReportAction
{
    public function execute(ComposerForm $form, User $user, ?AiReport $parent = null): AiReport
    {
        $form->validated();
        if (!AiReportAccessPolicy::canGenerate($user) || ($parent && !AiReportAccessPolicy::canRefine($parent, $user))) {
            throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.generate_denied')]);
        }
        $staged = $this->stage($form);
        if (AiReport::quotaUsed($user->id) >= AiReport::quotaCap()) {
            throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.quota')]);
        }

        $names = array_column($staged, 'name');
        $token = Str::random(16);
        try {
            $report = Cache::lock('ai-report:generate:' . $user->id, (int) config('ai.report.lock.ttl'))->block((int) config('ai.report.lock.wait'), function () use ($user, $parent, $form, $names, $token) {
                if (AiReport::quotaUsed($user->id) >= AiReport::quotaCap()) {
                    throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.quota')]);
                }
                $report = DB::transaction(function () use ($user, $parent, $form, $names, $token) {
                    $this->assertIdle($user->id);
                    if (!$parent) {
                        return AiReport::query()->create([
                            'user_id' => $user->id,
                            'status' => AiReport::STATUS_GENERATING,
                            'versions' => [$this->version($form, $names, $token)],
                        ] + $this->presets($form));
                    }

                    $locked = AiReport::query()->whereKey($parent->id)->lockForUpdate()->first();
                    if (!$locked || !AiReportAccessPolicy::canRefine($locked, $user)) {
                        throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.not_refinable')]);
                    }
                    $parentSpec = $locked->spec;
                    if (empty($parentSpec) && $locked->status === AiReport::STATUS_FAILED) {
                        $parentSpec = $locked->lastSpecVersion()['spec'] ?? $locked->lastVersion()['parent_spec'] ?? null;
                    }
                    if (is_array($parentSpec)) {
                        $block = null;
                        if (is_string($form->block)) {
                            if (BlockCatalog::resolveBlock($parentSpec, $form->block) === null) {
                                throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.block_stale')]);
                            }
                            $block = $form->block;
                        }
                    }
                    $version = $this->version($form, $names, $token, is_array($parentSpec) ? $parentSpec : null, $block ?? null);
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
                try {
                    $n = AiReport::consumeQuota($user->id);
                } catch (Throwable) {
                    $report->markFailed('exception');
                    throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.busy')]);
                }
                if ($n > AiReport::quotaCap()) {
                    $report->markFailed('quota');
                    throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.quota')]);
                }

                return $report;
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.busy')]);
        }

        try {
            Storage::disk('local')->makeDirectory($report->tempDirectory());
            foreach ($staged as $item) {
                $stored = $item['file']->storeAs($report->tempDirectory(), $item['stored'], 'local');
                if ($stored === false) {
                    throw new RuntimeException('store failed');
                }
                if ($item['file'] instanceof TemporaryUploadedFile) {
                    try {
                        $item['file']->delete();
                    } catch (Throwable) {
                    }
                }
            }
        } catch (Throwable $e) {
            $report->markFailed('exception');
            throw $e;
        }

        try {
            GenerateReportJob::dispatch($report->id, $token);
        } catch (Throwable $e) {
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
            throw ValidationException::withMessages(['form.prompt' => __('resources/ai_report/strings.errors.busy')]);
        }
    }

    private function stage(ComposerForm $form): array
    {
        $staged = [];
        $total = 0;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $cap = (int) config('ai.report.upload.total_kb') * 1024;
        $inlineCap = (int) config('ai.report.upload.inline_kb') * 1024;
        $inlineTotal = 0;
        $inlineTotalCap = (int) config('ai.report.upload.inline_total_kb') * 1024;
        foreach ($form->files as $file) {
            if (!$file instanceof UploadedFile) {
                throw ValidationException::withMessages(['form.files' => __('resources/ai_report/strings.errors.file_invalid')]);
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
                throw ValidationException::withMessages(['form.files' => __('resources/ai_report/strings.errors.inline_size', ['mb' => convertToPersian(intdiv($inlineCap, 1024 * 1024))])]);
            }
            if (FileExtractor::MIMES[$mime][0] === 'inline') {
                $inlineTotal += $size;
                if ($inlineTotal > $inlineTotalCap) {
                    throw ValidationException::withMessages(['form.files' => __('resources/ai_report/strings.errors.inline_total', ['mb' => convertToPersian(intdiv($inlineTotalCap, 1024 * 1024))])]);
                }
            }
            $total += $size;
            if ($total > $cap) {
                throw ValidationException::withMessages(['form.files' => __('resources/ai_report/strings.errors.total_size', ['mb' => convertToPersian(intdiv($cap, 1024 * 1024))])]);
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

    private function version(ComposerForm $form, array $files, string $run, ?array $parentSpec = null, ?string $block = null): array
    {
        $version = [
            'prompt' => $form->prompt,
            'files' => $files,
            'run' => $run,
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
