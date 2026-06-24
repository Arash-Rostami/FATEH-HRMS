<?php

namespace App\Services\Search;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NavigationService
{
    /**
     * Search for items based on a query string.
     */
    public function search(string $query): array
    {
        $query = $this->normalize($query);

        if (strlen($query) < 2) return [];

        // Break query into words (e.g., "ticket support" -> ['ticket', 'support'])
        $tokens = array_values(array_filter(explode(' ', $query)));

        return $this->getSearchableItems()
            ->map(function ($item) use ($query, $tokens) {
                $score = 0;

                $title = $this->normalize($item['title']);
                $subtitle = $this->normalize($item['subtitle']);
                $keywords = collect($item['keywords'])->map(fn($k) => $this->normalize($k));

                // 1. Exact Match Priority (Highest Score)
                if ($title === $query || $keywords->contains($query)) $score += 100;

                // 2. Token Matching (Smart Multi-word search)
                foreach ($tokens as $token) {
                    if (Str::contains($title, $token)) $score += 40;
                    if (Str::contains($subtitle, $token)) $score += 20;
                    if ($keywords->contains(fn($k) => Str::contains($k, $token))) $score += 30;
                }

                $item['score'] = $score;
                return $item;
            })
            ->filter(fn($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->toArray();
    }

    /**
     * Normalizes text to handle Persian/Arabic inconsistencies and casing.
     */
    protected function normalize(string $text): string
    {
        $text = Str::lower(trim($text));
        $map = [
            'ي' => 'ی',
            'ك' => 'ک',
        ];

        $text = strtr($text, $map);

        return preg_replace('/\s+/u', ' ', $text);
    }

    /**
     * Get all searchable items.
     */

    protected function getSearchableItems(): Collection
    {
        return collect(config('search'));
    }
}
