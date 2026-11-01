<?php

namespace Tests\Feature\Services;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CacheComputedGuardTest extends TestCase
{
    private const SCAN_DIRS = [
        'app/Livewire',
        'app/Filament/Widgets',
    ];

    private const MODIFIERS = [
        'public', 'protected', 'private', 'static', 'final', 'readonly', 'abstract',
    ];

    #[Test]
    public function no_literal_computed_key_and_no_cache_true_on_parameterized_methods(): void
    {
        $violations = [];

        foreach ($this->phpFiles() as $file) {
            $src = file_get_contents($file->getPathname());
            $offset = 0;

            while (($pos = strpos($src, '#[Computed', $offset)) !== false) {
                $offset = $pos + 1;
                $open = $this->skipWs($src, $pos + strlen('#[Computed'));

                if ($open === null || $src[$open] !== '(') {
                    continue;
                }

                $close = $this->balancedParen($src, $open);
                if ($close === null) {
                    continue;
                }

                $args = substr($src, $open + 1, $close - $open - 1);
                $argsNoStr = $this->stripStrings($args);
                $rel = $this->rel($file);

                if (preg_match('/\bkey\s*:/', $argsNoStr)) {
                    $violations[] = "{$rel}: #[Computed({$args})] uses a literal key: — the versioned-cache staleness signature; use ModelCacheVersion::key()/remember()/rememberGlobal() instead.";
                }

                if (preg_match('/\bcache\s*:\s*true\b/i', $argsNoStr)) {
                    $params = $this->followingFunctionParams($src, $close + 2);
                    if ($params !== null && str_contains($params, '$')) {
                        $violations[] = "{$rel}: #[Computed({$args})] with cache:true on a parameterized method ({$params}) — the contamination signature; Livewire's computed key is arg-unaware. Use ModelCacheVersion::rememberGlobal() with the arg baked into the key.";
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Banned #[Computed] cache patterns found:\n" . implode("\n", $violations));
    }

    private function followingFunctionParams(string $src, int $start): ?string
    {
        $len = strlen($src);
        $i = $start;

        while ($i < $len) {
            $i = $this->skipWs($src, $i) ?? $len;

            if ($i + 1 < $len && $src[$i] === '#' && $src[$i + 1] === '[') {
                $br = $this->balancedBracket($src, $i + 1);
                if ($br === null) {
                    return null;
                }
                $i = $br + 1;
                continue;
            }

            if (preg_match('/^([a-z_]\w*)(\s+)/', substr($src, $i), $m)) {
                $word = $m[1];
                if (in_array($word, self::MODIFIERS, true)) {
                    $i += strlen($m[0]);
                    continue;
                }
                if ($word === 'function') {
                    $i += strlen('function');
                    $i = $this->skipWs($src, $i) ?? $len;
                    if (preg_match('/^([a-z_]\w*)/', substr($src, $i), $mn)) {
                        $i += strlen($mn[0]);
                    }
                    $i = $this->skipWs($src, $i) ?? $len;
                    if ($i < $len && $src[$i] === '(') {
                        $pclose = $this->balancedParen($src, $i);
                        if ($pclose === null) {
                            return null;
                        }
                        return substr($src, $i + 1, $pclose - $i - 1);
                    }
                    return null;
                }
                return null;
            }

            return null;
        }

        return null;
    }

    private function balancedParen(string $src, int $open): ?int
    {
        $len = strlen($src);
        $depth = 0;
        $i = $open;

        while ($i < $len) {
            $c = $src[$i];

            if ($c === "'" || $c === '"') {
                $i = $this->skipString($src, $i);
                continue;
            }

            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }

            $i++;
        }

        return null;
    }

    private function balancedBracket(string $src, int $open): ?int
    {
        $len = strlen($src);
        $depth = 0;
        $i = $open;

        while ($i < $len) {
            $c = $src[$i];

            if ($c === "'" || $c === '"') {
                $i = $this->skipString($src, $i);
                continue;
            }

            if ($c === '(') {
                $inner = $this->balancedParen($src, $i);
                $i = $inner === null ? $len : $inner + 1;
                continue;
            }

            if ($c === '[') {
                $depth++;
            } elseif ($c === ']') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }

            $i++;
        }

        return null;
    }

    private function skipString(string $src, int $start): int
    {
        $len = strlen($src);
        $quote = $src[$start];
        $i = $start + 1;

        while ($i < $len) {
            if ($src[$i] === '\\') {
                $i += 2;
                continue;
            }
            if ($src[$i] === $quote) {
                return $i + 1;
            }
            $i++;
        }

        return $len;
    }

    private function skipWs(string $src, int $start): ?int
    {
        $len = strlen($src);
        $i = $start;
        while ($i < $len && ctype_space($src[$i])) {
            $i++;
        }
        return $i < $len ? $i : null;
    }

    private function stripStrings(string $s): string
    {
        $len = strlen($s);
        $out = '';
        $i = 0;

        while ($i < $len) {
            $c = $s[$i];
            if ($c === "'" || $c === '"') {
                $i = $this->skipString($s, $i);
                $out .= '""';
                continue;
            }
            $out .= $c;
            $i++;
        }

        return $out;
    }

    private function phpFiles(): array
    {
        $files = [];
        $base = dirname(__DIR__, 3);

        foreach (self::SCAN_DIRS as $dir) {
            $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (!is_dir($path)) {
                continue;
            }
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function rel(SplFileInfo $file): string
    {
        return str_replace(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR, '', $file->getPathname());
    }
}