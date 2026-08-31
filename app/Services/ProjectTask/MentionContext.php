<?php

namespace App\Services\ProjectTask;

use Illuminate\Support\Collection;

class MentionContext
{
    public function __construct(
        private Collection $byName,
        private string $detectPattern,
        private string $highlightPattern,
    ) {}

    public function mentionedUsers(string $body): Collection
    {
        if (trim($body) === '' || $this->byName->isEmpty()) {
            return collect();
        }

        preg_match_all($this->detectPattern, $body, $matches);

        return collect($matches[1] ?? [])
            ->unique()
            ->map(fn($name) => $this->byName->get($name))
            ->filter()
            ->values();
    }

    public function render(string $body, ?Collection $mentioned = null): string
    {
        $escaped = e($body);

        if ($mentioned !== null && $mentioned->isEmpty()) {
            return $escaped;
        }

        return preg_replace(
            $this->highlightPattern,
            '<span class="font-bold text-[var(--md-sys-color-primary)]">@$1</span>',
            $escaped,
        ) ?? $escaped;
    }

    public function highlight(string $body): string
    {
        return $this->render($body, $this->mentionedUsers($body));
    }
}