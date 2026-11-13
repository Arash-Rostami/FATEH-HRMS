<?php

namespace App\Jobs;

use App\Models\AiReport;
use App\Services\Ai\AiClient;
use App\Services\AiReport\BlockCatalog;
use App\Services\AiReport\FileExtractor;
use App\Services\AiReport\ReportAiService;
use DomainException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use JsonException;
use Throwable;

class GenerateReportJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public int $uniqueFor;

    public ?string $run = null;

    public function __construct(public int $reportId, ?string $run = null)
    {
        $this->run = $run;
        $padding = (int) config('ai.report.job.padding');
        $this->timeout = 2 * ((int) AiClient::setting('report', 'timeout') + (int) AiClient::setting('report', 'connect_timeout')) + $padding;
        $ceiling = (int) config('queue.connections.' . config('queue.default') . '.retry_after') - (int) config('ai.report.job.retry_margin');
        if ($ceiling >= 1 && $this->timeout > $ceiling) {
            $this->timeout = $ceiling;
        }
        $this->uniqueFor = $this->timeout + $padding;
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->reportId . ($this->run !== null ? ':' . $this->run : '');
    }

    public function handle(ReportAiService $ai, FileExtractor $files): void
    {
        $report = AiReport::query()->find($this->reportId);
        if (!$report || $report->status !== AiReport::STATUS_GENERATING) {
            Storage::disk('local')->deleteDirectory("ai-report/tmp/{$this->reportId}");

            return;
        }

        if ($this->superseded($report)) {
            return;
        }

        $report->touch();

        try {
            $extracted = $files->extract('local', $report->tempDirectory());
            $versions = $report->versions ?? [];
            $last = array_key_last($versions);
            $notes = $extracted['notes'];
            if ($extracted['skipped'] !== [] && $extracted['text'] === '' && $extracted['inline'] === []) {
                $notes[] = __('resources/ai_report/strings.notes.all_skipped');
            }
            if ($last !== null && ($extracted['skipped'] !== [] || $notes !== [])) {
                if ($extracted['skipped'] !== []) {
                    $versions[$last]['skipped'] = $extracted['skipped'];
                }
                if ($notes !== []) {
                    $versions[$last]['notes'] = $notes;
                }
                $report->versions = $versions;
                $report->save();
            }

            $parentSpec = is_array($versions[$last]['parent_spec'] ?? null) ? $versions[$last]['parent_spec'] : null;
            $blockRef = $parentSpec !== null && is_string($versions[$last]['block'] ?? null) ? $versions[$last]['block'] : null;
            $options = array_intersect_key(is_array($versions[$last] ?? null) ? $versions[$last] : [], ['type' => 1, 'length' => 1, 'audience' => 1, 'block' => 1]);
            $result = $ai->generate((string) ($versions[$last]['prompt'] ?? ''), $extracted['text'], $extracted['inline'], $parentSpec, $notes, $options);
            if ($blockRef !== null) {
                $result['spec'] = $this->mergeBlock($parentSpec, $result['spec'], $blockRef);
            }

            DB::transaction(function () use ($result, $blockRef) {
                $fresh = AiReport::query()->whereKey($this->reportId)->lockForUpdate()->first();
                if (!$fresh || $fresh->status !== AiReport::STATUS_GENERATING || $this->superseded($fresh)) {
                    return;
                }
                $versions = $fresh->versions ?? [];
                $last = array_key_last($versions);
                if ($last !== null) {
                    $versions[$last]['spec'] = $result['spec'];
                    $versions[$last]['model'] = $result['model'];
                    $versions[$last]['tokens'] = $result['tokens'];
                    unset($versions[$last]['parent_spec']);
                }
                $fresh->fill([
                    'spec' => $result['spec'],
                    'title' => $result['spec']['title'] ?? null,
                    'status' => AiReport::STATUS_READY,
                    'model' => $result['model'],
                    'tokens_total' => (int) $fresh->tokens_total + $result['tokens'],
                    'versions' => $versions,
                ] + ($blockRef !== null ? [] : [
                    'preset_type' => is_string($versions[$last]['type'] ?? null) ? $versions[$last]['type'] : null,
                    'preset_length' => is_string($versions[$last]['length'] ?? null) ? $versions[$last]['length'] : null,
                    'preset_audience' => is_string($versions[$last]['audience'] ?? null) ? $versions[$last]['audience'] : null,
                ]))->save();
            }, 3);
        } catch (Throwable $e) {
            if (!$this->superseded(AiReport::query()->find($this->reportId))) {
                $this->failWith($report, $e, $ai->spent);
            }
        } finally {
            if (!$this->superseded(AiReport::query()->find($this->reportId))) {
                Storage::disk('local')->deleteDirectory($report->tempDirectory());
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        $report = AiReport::query()->find($this->reportId);
        if (!$report || $this->superseded($report)) {
            return;
        }
        $report->markFailed($e instanceof TimeoutExceededException ? 'timeout' : 'exception');
        Storage::disk('local')->deleteDirectory($report->tempDirectory());
    }

    private function superseded(?AiReport $report): bool
    {
        return $this->run !== null && $report !== null && ($report->lastVersion()['run'] ?? null) !== $this->run;
    }

    private function mergeBlock(array $parent, array $result, string $ref): array
    {
        $target = BlockCatalog::resolveBlock($parent, $ref);
        if ($target === null) {
            throw new InvalidArgumentException('block refine: target missing');
        }
        $lang = ($parent['lang'] ?? 'fa') === 'en' ? 'en' : 'fa';
        $original = $target['block'];
        $wanted = $original['type'] ?? null;
        $originalAssumptions = $wanted === 'section' && is_string($original['heading'] ?? null) && BlockCatalog::isAssumptionsHeading($original['heading'], $lang);
        $revised = null;
        foreach (($result['pages'][0]['blocks'] ?? []) as $block) {
            if (!is_array($block) || ($block['type'] ?? null) !== $wanted) {
                continue;
            }
            if (!$originalAssumptions && ($block['type'] ?? null) === 'section' && is_string($block['heading'] ?? null) && BlockCatalog::isAssumptionsHeading($block['heading'], $lang)) {
                continue;
            }
            $revised = $block;
            break;
        }
        if ($revised === null) {
            throw new InvalidArgumentException('block refine: type mismatch');
        }
        if ($revised['type'] === 'section' && is_string($original['heading'] ?? null) && BlockCatalog::isAssumptionsHeading($original['heading'], $lang)) {
            $revised['heading'] = $original['heading'];
        }
        $parent['pages'][$target['p']]['blocks'][$target['b']] = $revised;
        if ($revised['type'] === 'title') {
            $parent['title'] = $revised['heading'];
        }

        return BlockCatalog::validate($parent);
    }

    private function failWith(AiReport $report, Throwable $e, int $spent = 0): void
    {
        if ($spent > 0) {
            AiReport::query()->whereKey($report->id)->where('status', AiReport::STATUS_GENERATING)->increment('tokens_total', $spent);
        }

        if ($e instanceof RequestException) {
            $status = $e->response?->status() ?? 0;
            $decoded = json_decode((string) $e->response?->body(), true);
            $error = is_array($decoded) ? ($decoded['error'] ?? null) : null;
            Log::warning('ai-report.http', [
                'report_id' => $report->id,
                'status' => $status,
                'retry_after' => $status === 429 ? $e->response?->header('Retry-After') : null,
                'type' => is_array($error) && is_scalar($error['type'] ?? null) ? (string) $error['type'] : null,
                'code' => is_array($error) && is_scalar($error['code'] ?? null) ? (string) $error['code'] : null,
            ]);
            $code = match (true) {
                $status === 429 => 'http_429',
                $status >= 500 && $status <= 599 => 'http_5xx',
                $status >= 400 && $status <= 499 => 'http_4xx',
                default => 'exception',
            };
            if ($code === 'exception') {
                report($e);
            }
            $report->markFailed($code);

            return;
        }

        $code = match (true) {
            $e instanceof JsonException => 'invalid_json',
            $e instanceof InvalidArgumentException => 'invalid_spec',
            $e instanceof DomainException => match ($e->getMessage()) {
                'too_large', 'blocked', 'empty' => $e->getMessage(),
                'unreachable' => 'timeout',
                default => 'exception',
            },
            default => 'exception',
        };
        if ($code === 'exception') {
            report($e);
        }
        $report->markFailed($code);
    }
}
