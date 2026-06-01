<?php

namespace App\Services\Search\Contracts;

use Illuminate\Support\Str;

final class SearchContext
{
    public function __construct(
        public readonly string $query,
        public readonly array $tokens,
    ) {}

    /**
     * Normalize + tokenize a raw query.
     * Returns null when the query is too short / empty to search.
     */
    public static function for(string $query): ?self
    {
        $query = self::normalize($query);

        if (Str::length($query) < 2) {
            return null;
        }

        $tokens = array_values(array_filter(
            explode(' ', $query),
            fn (string $token) => $token !== ''
        ));

        if ($tokens === []) {
            return null;
        }

        return new self($query, $tokens);
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
