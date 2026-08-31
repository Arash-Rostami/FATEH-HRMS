<?php

namespace App\Services\ProjectTask;

use Illuminate\Support\Collection;

class MentionResolver
{
    public function context(Collection $participants): MentionContext
    {
        $byName = $participants->keyBy('name');
        $highlightNames = $participants->pluck('name')->map(fn($name) => e($name));

        return new MentionContext(
            $byName,
            $this->buildPattern($byName->keys()) ?? '/(?!)/',
            $this->buildPattern($highlightNames) ?? '/(?!)/',
        );
    }

    public function mentionedUsers(string $body, Collection $participants): Collection
    {
        return $this->context($participants)->mentionedUsers($body);
    }

    public function highlight(string $body, Collection $participants): string
    {
        return $this->context($participants)->highlight($body);
    }

    public function render(string $body, Collection $mentioned): string
    {
        return $this->context($mentioned)->render($body, $mentioned);
    }

    private function buildPattern(Collection $names): ?string
    {
        $quoted = $names
            ->filter(fn($name) => trim((string) $name) !== '')
            ->sortByDesc(fn($name) => mb_strlen($name))
            ->map(fn($name) => preg_quote($name, '/'));

        if ($quoted->isEmpty()) {
            return null;
        }

        return '/(?<![\p{L}\p{N}_])@(' . $quoted->implode('|') . ')(?![\p{L}\p{N}_])/u';
    }
}