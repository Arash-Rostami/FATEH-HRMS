<?php

namespace App\Livewire\Dashboard\Media\Actions;

use App\Models\KnowledgeProgress;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveProgressAction
{
    public static function execute(User $user, array $items): int
    {
        $parsed = self::collapse(self::parse(array_slice(array_values($items), 0, 50)));

        if ($parsed === []) {
            return 0;
        }

        $mediaItems = array_values(array_filter($parsed, fn (array $item) => $item['type'] === 'media'));
        $reportItems = config('media.track_reports')
            ? array_values(array_filter($parsed, fn (array $item) => $item['type'] === 'report'))
            : [];

        $playable = Media::playableFor($user, array_column($mediaItems, 'id'))->keyBy('id');
        $reportIds = $reportItems === []
            ? collect()
            : Report::forViewer($user)->notExpired()->whereIn('id', array_column($reportItems, 'id'))->pluck('id')->flip();

        if ($playable->isEmpty() && $reportIds->isEmpty()) {
            return 0;
        }

        $existing = KnowledgeProgress::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($playable, $reportIds) {
                if ($playable->isNotEmpty()) {
                    $query->whereIn('media_id', $playable->keys());
                }

                if ($reportIds->isNotEmpty()) {
                    $method = $playable->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('report_id', $reportIds->keys());
                }
            })
            ->get(['media_id', 'report_id', 'position', 'last_position', 'completed_at'])
            ->keyBy(fn (KnowledgeProgress $row) => $row->media_id ? 'media:'.$row->media_id : 'report:'.$row->report_id);

        $stamp = now()->format('Y-m-d H:i:s');
        $mediaRows = [];
        $reportRows = [];
        $durations = [];

        foreach ($mediaItems as $item) {
            $row = $playable->get($item['id']);

            if ($row === null) {
                continue;
            }

            $sent = self::sane($item['duration']);
            $duration = $row->duration_seconds ?: $sent;
            $built = self::build($user->id, $item, $existing->get('media:'.$item['id']), $duration, $stamp, 'media_id');

            if ($built !== null) {
                $built['report_id'] = null;
                $mediaRows[] = $built;
            }

            if ($row->duration_seconds === null && $sent !== null) {
                $durations[$item['id']] = $sent;
            }
        }

        foreach ($reportItems as $item) {
            if (!$reportIds->has($item['id'])) {
                continue;
            }

            $built = self::build($user->id, $item, $existing->get('report:'.$item['id']), self::sane($item['duration']), $stamp, 'report_id');

            if ($built !== null) {
                $built['media_id'] = null;
                $reportRows[] = $built;
            }
        }

        self::upsert('media_id', $mediaRows);
        self::upsert('report_id', $reportRows);
        Media::writeSystemDurations($durations);

        return count($mediaRows) + count($reportRows);
    }

    private static function parse(array $items): array
    {
        $parsed = [];

        foreach ($items as $item) {
            if (!is_array($item) || !in_array($item['type'] ?? null, ['media', 'report'], true)) {
                continue;
            }

            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $position = filter_var($item['position'] ?? null, FILTER_VALIDATE_INT);
            $last = filter_var($item['last_position'] ?? null, FILTER_VALIDATE_INT);

            if ($id === false || $id < 1 || $position === false || $last === false) {
                continue;
            }

            $parsed[] = [
                'type' => $item['type'],
                'id' => $id,
                'position' => max(0, $position),
                'last_position' => max(0, $last),
                'completed' => filter_var($item['completed'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'duration' => $item['duration'] ?? null,
            ];
        }

        return $parsed;
    }

    private static function collapse(array $items): array
    {
        $byKey = [];

        foreach ($items as $item) {
            $key = $item['type'].':'.$item['id'];

            if (!isset($byKey[$key])) {
                $byKey[$key] = $item;
                continue;
            }

            $byKey[$key]['position'] = max($byKey[$key]['position'], $item['position']);
            $byKey[$key]['last_position'] = $item['last_position'];
            $byKey[$key]['completed'] = $byKey[$key]['completed'] || $item['completed'];
            $byKey[$key]['duration'] ??= $item['duration'];
        }

        return array_values($byKey);
    }

    private static function build(int $userId, array $item, ?KnowledgeProgress $stored, ?int $duration, string $stamp, string $idColumn): ?array
    {
        $position = self::clamp($item['position'], $duration);
        $last = self::clamp($item['last_position'], $duration);
        $completing = $item['completed']
            || ($duration !== null && $position >= (int) floor($duration * (float) config('media.completion_ratio', 0.95)));
        $storedPosition = (int) ($stored->position ?? 0);
        $storedLast = (int) ($stored->last_position ?? -1);
        $alreadyDone = $stored?->completed_at !== null;

        if ($stored !== null && $position <= $storedPosition && $last === $storedLast && !($completing && !$alreadyDone)) {
            return null;
        }

        return [
            'user_id' => $userId,
            $idColumn => $item['id'],
            'position' => max($position, $storedPosition),
            'last_position' => $last,
            'completed_at' => $completing && !$alreadyDone ? $stamp : null,
            'last_seen_at' => $stamp,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ];
    }

    private static function clamp(int $position, ?int $duration): int
    {
        $tolerance = (int) config('media.position_tolerance', 5);

        return min($position, ($duration ?? Media::MAX_SECONDS) + $tolerance);
    }

    private static function sane(mixed $value): ?int
    {
        $seconds = filter_var($value, FILTER_VALIDATE_INT);

        return $seconds !== false && $seconds >= 1 && $seconds <= Media::MAX_SECONDS ? $seconds : null;
    }

    private static function upsert(string $uniqueColumn, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $connection = config('database.default');
        $alias = (bool) config("database.connections.{$connection}.use_upsert_alias");
        $incoming = fn (string $column) => $alias ? 'laravel_upsert_alias.`'.$column.'`' : 'VALUES(`'.$column.'`)';

        KnowledgeProgress::upsert(
            $rows,
            ['user_id', $uniqueColumn],
            [
                'position' => DB::raw('GREATEST(COALESCE(`position`, 0), COALESCE('.$incoming('position').', 0))'),
                'last_position' => DB::raw($incoming('last_position')),
                'completed_at' => DB::raw('COALESCE(`completed_at`, '.$incoming('completed_at').')'),
                'last_seen_at' => DB::raw($incoming('last_seen_at')),
                'updated_at' => DB::raw($incoming('updated_at')),
            ]
        );
    }
}
