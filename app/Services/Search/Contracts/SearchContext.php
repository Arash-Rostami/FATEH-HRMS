<?php

namespace App\Services\Search\Contracts;

use Illuminate\Support\Str;

final class SearchContext
{
    private const QUERY_MAX = 100;
    private const TOKENS_MAX = 5;

    public function __construct(
        public readonly string $query,
        public readonly array $tokens,
        public readonly bool $allHistory = false,
    ) {}

    /**
     * Normalize + tokenize a raw query.
     * Returns null when the query is too short / empty to search.
     */
    public static function for(string $query, bool $allHistory = false): ?self
    {
        $query = self::normalize(mb_substr($query, 0, self::QUERY_MAX));

        if (Str::length($query) < 2) {
            return null;
        }

        $tokens = array_slice(array_values(array_unique(array_filter(
            explode(' ', $query),
            fn (string $token) => $token !== ''
        ))), 0, self::TOKENS_MAX);

        if ($tokens === []) {
            return null;
        }

        return new self($query, $tokens, $allHistory);
    }

    /** Lowercase, fold Persian ي/ك, and collapse whitespace. */
    private static function normalize(string $text): string
    {
        $text = Str::lower(trim($text));
        $text = strtr($text, [
            'ي' => 'ی', 'ك' => 'ک',
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ی',
            "\u{200C}" => '',
        ]);

        return preg_replace('/\s+/u', ' ', $text);
    }
}
