<?php

namespace App\Livewire\Dashboard\Media\Presentation;

use App\Enums\MediaIcon;
use App\Models\Department;
use App\Models\KnowledgeProgress;
use App\Models\Media;
use App\Models\MediaNote;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MediaPresenter
{
    private const COLUMNS = [
        'id', 'parent_id', 'kind', 'title', 'topic', 'description', 'cover_image',
        'file_path', 'transcript_path', 'chapters_path', 'icon', 'duration_seconds',
        'position', 'active', 'pinned', 'department_id', 'departments', 'report_date', 'report_ids', 'created_at',
    ];

    private const EPISODE_COLUMNS = [
        'id', 'parent_id', 'kind', 'title', 'topic', 'icon', 'duration_seconds',
        'file_path', 'transcript_path', 'chapters_path', 'report_date', 'position',
    ];

    public function library(?User $user = null): array
    {
        $payload = ['serverNow' => now()->timestamp, 'items' => [], 'progress' => (object) [], 'notes' => (object) []];
        $user ??= auth()->user();

        if (! $user || tenantOff('reports:media')) {
            return $payload;
        }

        $user->loadMissing('profile');
        $departments = Department::getCachedModels();
        $payload['items'] = array_map(
            fn (array $item) => $this->labelled($item, $departments),
            Media::cachedCatalog($user->profile?->department_id, fn () => $this->catalog($user))
        );
        $episodeIds = collect($payload['items'])->pluck('episodes.*.id')->flatten()->all();

        if ($episodeIds === []) {
            return $payload;
        }

        $payload['progress'] = (object) KnowledgeProgress::where('user_id', $user->id)->whereIn('media_id', $episodeIds)
            ->get(['media_id', 'position', 'last_position', 'completed_at'])
            ->mapWithKeys(fn (KnowledgeProgress $row) => [$row->media_id => [
                'position' => (int) $row->position,
                'last_position' => (int) $row->last_position,
                'completed' => $row->completed_at !== null,
            ]])->all();
        $payload['notes'] = (object) MediaNote::where('user_id', $user->id)->whereIn('media_id', $episodeIds)
            ->orderBy('position')->orderBy('id')->get(['id', 'media_id', 'position', 'body'])
            ->groupBy('media_id')->map(fn (Collection $notes) => $notes->map(fn (MediaNote $note) => [
                'id' => $note->id, 'position' => (int) $note->position, 'body' => $note->body,
            ])->values()->all())->all();

        return $payload;
    }

    private function labelled(array $item, Collection $departments): array
    {
        ['department_code' => $code, 'audience' => $audience] = $item;
        unset($item['department_code'], $item['audience']);
        $item['department'] = $departments->get((string) $code)?->name
            ?? ($departments->only($audience)->pluck('name')->join('، ') ?: 'همهٔ بخش‌ها');

        return $item;
    }

    private function catalog(User $user): array
    {
        $library = Media::library($user)
            ->select(self::COLUMNS)
            ->with(['episodes' => fn ($query) => $query->select(self::EPISODE_COLUMNS)->where('active', true)->notExpired()])
            ->orderByDesc('pinned')->latest()->orderByDesc('id')->get();

        $reportIds = $library->flatMap(fn (Media $item) => $item->report_ids ?? [])->unique()->values();
        $reports = $reportIds->isEmpty() ? collect() : Report::forViewer($user)->notExpired()
            ->whereIn('id', $reportIds)->get(['id', 'title', 'file_path'])
            ->mapWithKeys(fn (Report $report) => [$report->id => $this->material($report)]);

        return $library->map(function (Media $item) use ($reports): array {
            $episodes = $item->kind === 'series' ? $item->episodes : collect([$item]);
            $kind = $item->kind === 'series'
                ? ($episodes->first(fn (Media $episode) => $episode->kind === 'video') ? 'video' : 'audio')
                : $item->kind;

            return [
                'id' => $item->id,
                'kind' => $kind,
                'type' => $item->kind === 'series' ? 'series' : 'single',
                'title' => $item->title,
                'description_html' => $item->description ?? '',
                'cover_url' => $item->cover_url ?: null,
                'icon' => $this->icon($item, $kind),
                'accent' => ['p', 't', 'ok', 'w', 'sec'][$item->id % 5],
                'unit' => 'دانش سازمانی',
                'department_code' => $item->department_id,
                'audience' => $item->audienceCodes(),
                'date_label' => convertToPersian(toJalali($item->report_date ?? $item->created_at, 'j F Y')),
                'pinned' => (bool) $item->pinned,
                'total_duration' => (int) $episodes->sum('duration_seconds'),
                'episodes' => $episodes->map(fn (Media $episode) => $this->episode($episode))->values()->all(),
                'materials' => $this->materials($item, $reports),
            ];
        })->all();
    }

    private function episode(Media $episode): array
    {
        $locked = (bool) $episode->is_locked;
        $url = $locked ? null : ($episode->file_url ?: null);
        $extension = strtolower(pathinfo((string) $episode->file_path, PATHINFO_EXTENSION));

        return [
            'id' => $episode->id,
            'title' => $episode->title,
            'topic' => $episode->topic ?? '',
            'icon' => $this->icon($episode, $episode->kind),
            'duration' => $episode->duration_seconds === null ? null : (int) $episode->duration_seconds,
            'file_url' => $url,
            'download_url' => $url,
            'mime' => match ($extension) {
                'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav',
                'ogg' => 'audio/ogg', 'mp4' => 'video/mp4', 'webm' => 'video/webm', default => null,
            },
            'unlock_at' => $locked ? $episode->report_date->startOfDay()->timestamp : null,
            'unlock_label' => $locked ? convertToPersian(toJalali($episode->report_date, 'j F Y')) : null,
            'transcript_url' => ! $locked && $episode->transcript_path ? Media::resolvePublicAssetUrl($episode->transcript_path) : null,
            'chapters_url' => ! $locked && $episode->chapters_path ? Media::resolvePublicAssetUrl($episode->chapters_path) : null,
        ];
    }

    private function icon(Media $item, string $kind): string
    {
        $icon = $item->icon instanceof MediaIcon ? $item->icon : MediaIcon::tryFrom((string) $item->icon);

        return $icon?->getMaterialIcon() ?? ($kind === 'video' ? 'smart_display' : 'headphones');
    }

    private function materials(Media $item, Collection $reports): array
    {
        return collect($item->report_ids ?? [])->unique()->map(fn ($id) => $reports->get($id))
            ->filter()->values()->all();
    }

    private function material(Report $report): ?array
    {
        if (! filled($report->file_path)) {
            return null;
        }

        $path = $report->file_path;
        $disk = Storage::disk('public');
        $size = ! preg_match('~^(?:https?:)?//~i', $path) && $disk->exists($path) ? $disk->size($path) : null;

        return [
            'id' => $report->id,
            'title' => $report->title,
            'type' => $report->file_type,
            'url' => Report::resolvePublicAssetUrl($path),
            'size_label' => $size === null ? '' : convertToPersian((string) round($size / 1024)) . ' کیلوبایت',
        ];
    }
}
