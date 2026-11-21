<?php

namespace App\Livewire\Dashboard\Knowledge\Actions;

use App\Models\KnowledgeProgress;
use App\Models\Media;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Menu\StateService;
use Illuminate\Support\Facades\DB;

class SaveProgressAction
{
    public static function execute(User $user, array $items): int
    {
        $parsed = self::collapse(self::parse(array_slice(array_values($items), 0, 50)));

        if ($parsed === []) {
            return 0;
        }

        $playable = Media::playableFor($user, array_column($parsed, 'id'))->keyBy('id');

        if ($playable->isEmpty()) {
            return 0;
        }

        $existing = KnowledgeProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('media_id', $playable->keys())
            ->get(['media_id', 'position', 'last_position', 'completed_at'])
            ->keyBy(fn (KnowledgeProgress $row) => 'media:'.$row->media_id);

        $stamp = now()->format('Y-m-d H:i:s');
        $mediaRows = [];
        $durations = [];

        foreach ($parsed as $item) {
            $row = $playable->get($item['id']);

            if ($row === null) {
                continue;
            }

            $sent = self::sane($item['duration']);
            $stored = $row->duration_seconds >= 1 ? $row->duration_seconds : null;
            $built = self::build($user->id, $item, $existing->get('media:'.$item['id']), $stored ?? $sent, $stamp, 'media_id');

            if ($built !== null) {
                $mediaRows[] = $built;
            }

            if ($stored === null && $sent !== null && $sent >= 10 && $sent >= (int) ($item['position'] ?? 0)) {
                $durations[$item['id']] = $sent;
            }
        }

        $firstPlay = array_filter($mediaRows, fn (array $row) => !$existing->has('media:'.$row['media_id'])) !== []
            && StateService::lit($user->id, 'media-controller');

        $write = function () use ($mediaRows, $durations, $firstPlay) {
            self::upsert('media_id', $mediaRows);

            if ($firstPlay) {
                DB::afterCommit(fn () => StateService::flush());
            }

            if ($durations !== []) {
                Media::writeSystemDurations($durations);
                DB::afterCommit(fn () => ModelCacheVersion::bump(Media::class));
            }
        };

        $durations !== [] ? DB::transaction($write) : $write();

        return count($mediaRows);
    }

    private static function parse(array $items): array
    {
        $parsed = [];

        foreach ($items as $item) {
            if (!is_array($item) || ($item['type'] ?? null) !== 'media') {
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
        $storedPosition = (int) ($stored->position ?? 0);
        $position = self::clamp($item['position'], $duration, $storedPosition);
        $last = self::clamp($item['last_position'], $duration, $storedPosition);
        $completing = $item['completed']
            || ($duration !== null && $position >= (int) floor($duration * (float) config('media.completion_ratio', 0.95)));
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

    private static function clamp(int $position, ?int $duration, int $storedPosition): int
    {
        $ceiling = $duration !== null
            ? $duration + (int) config('media.position_tolerance', 5)
            : $storedPosition + (int) config('media.unknown_duration_advance', 600);

        return min($position, $ceiling);
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
                'last_position' => DB::raw('IF('.$incoming('last_seen_at').' >= COALESCE(`last_seen_at`, \'1970-01-01 00:00:00\'), '.$incoming('last_position').', `last_position`)'),
                'completed_at' => DB::raw('COALESCE(`completed_at`, '.$incoming('completed_at').')'),
                'last_seen_at' => DB::raw('GREATEST(COALESCE(`last_seen_at`, '.$incoming('last_seen_at').'), '.$incoming('last_seen_at').')'),
                'updated_at' => DB::raw($incoming('updated_at')),
            ]
        );
    }
}
