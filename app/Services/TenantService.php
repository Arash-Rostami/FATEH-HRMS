<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

final class TenantService
{
    private const DEFAULT_PRIMARY = '#4e5f66';
    private const DEFAULT_HEADER = '#08191e';

    private array $memo = [];
    private array $lookups = [];
    private array $css = [];
    private array $signatures = [];

    public function off(string $key): bool
    {
        if (!config('app.off')) {
            return false;
        }

        $lookup = $this->lookup();

        return isset($lookup[$key]) || isset($lookup[strtok($key, ':')]) || isset($lookup[$this->word($key)]);
    }

    public function offKeys(): array
    {
        $keys = array_merge(array_column(config('modules'), 'id'), config('app.off'));

        return array_values(array_filter(array_unique($keys), $this->off(...)));
    }

    public function offName(string $name): bool
    {
        if (!config('app.off')) {
            return false;
        }

        foreach (['filament.admin.resources.', 'filament.admin.pages.', 'dashboard.tab.', 'dashboard.'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }

        $slot = strstr($name, '.', true) ?: $name;
        $sub = $slot === 'profile' ? (explode('.', $name)[1] ?? '') : '';

        return $this->off($this->word($slot))
            || $this->off($this->word(strstr($slot, '-', true) ?: $slot))
            || ($sub !== '' && $this->off($this->word($sub === 'tickets' ? 'ths' : $sub)));
    }

    public function lookup(): array
    {
        return $this->lookups[config('app.tenant') . '|' . implode(',', config('app.off')) . '|' . count(config('modules'))] ??= (function (): array {
            $off = array_flip(config('app.off'));
            $modules = config('modules');

            for ($pass = count($modules), $added = true; $added && $pass-- > 0;) {
                $added = false;

                foreach ($modules as $module) {
                    if (isset($off[$module['id']])) {
                        foreach ($module['with'] ?? [] as $with) {
                            if (!isset($off[$with])) {
                                $off[$with] = 0;
                                $added = true;
                            }
                        }
                    }
                }
            }

            foreach ($modules as $module) {
                if (isset($off[$module['id']])) {
                    foreach (array_filter([$module['id'], $module['nav']['key'] ?? null, $module['nav']['target'] ?? null, ...($module['aliases'] ?? [])]) as $word) {
                        $off[$word] = $off[$this->word($word)] = 0;
                    }
                }
            }

            return $off;
        })();
    }

    public function offCss(): string
    {
        if (!config('app.off')) {
            return '';
        }

        $routes = Route::getRoutes();
        $count = $routes->count();
        $signature = $this->signatures[$count] ??= md5(implode(',', array_map(fn ($r) => $r->getName() . '=' . $r->uri(), $routes->getRoutes())));
        $key = config('app.tenant') . '|' . implode(',', array_keys($this->lookup())) . '|' . $signature;

        $compute = function () use ($routes): string {
            $selectors = [];

            foreach ($routes->getRoutes() as $route) {
                $name = $route->getName();

                if (!$name || !in_array('GET', $route->methods()) || !$this->offName($name)) {
                    continue;
                }

                $uri = $route->uri();
                $prefix = strstr($uri, '{', true);

                if ($prefix === false) {
                    $selectors[] = sprintf('a[href$="/%1$s"],a[href*="/%1$s?"],a[href*="/%1$s/"]', addcslashes($uri, '"\\'));
                } elseif ($prefix !== '') {
                    $selectors[] = sprintf('a[href*="/%s"]', addcslashes($prefix, '"\\'));
                }
            }

            foreach (config('modules') as $module) {
                if (($module['nav']['type'] ?? null) === 'tab' && $this->off($module['id'])) {
                    $selectors[] = sprintf('a[href*="tab=%1$s&"],a[href$="tab=%1$s"]', addcslashes($module['nav']['target'], '"\\'));
                }
            }

            return $selectors ? implode(',', array_unique($selectors)) . '{display:none!important}' : '';
        };

        return $this->css[$key] ??= rescue(fn () => Cache::remember('tenant-off-css:' . md5($key), 3600, $compute), $compute, false);
    }

    public function themeCss(): string
    {
        $primary = (string)config('app.theme_primary');
        $header = (string)config('app.theme_header_bg');

        return $this->memo[$primary . '|' . $header] ??= $this->build($primary, $header);
    }

    private function word(string $word): string
    {
        $word = str_replace(['-', '_'], '', strtolower($word));

        return str_ends_with($word, 'ies') ? substr($word, 0, -3) . 'y' : (str_ends_with($word, 's') ? substr($word, 0, -1) : $word);
    }

    private function build(string $primary, string $header): string
    {
        $primary = $this->normalize($primary);
        $header = $this->normalize($header);

        if (!$primary || !$header || ($primary === self::DEFAULT_PRIMARY && $header === self::DEFAULT_HEADER)) {
            return '';
        }

        $light = $this->contrast($primary);
        $lightened = $this->mix($primary, 0.6, 255);
        $dark = $this->contrast($lightened);

        return ":root:not([data-theme]){--md-sys-color-primary:{$primary};--md-sys-color-on-primary:{$light};"
            . "--md-sys-color-primary-container:color-mix(in srgb,{$primary} 18%,white);"
            . "--md-sys-color-on-primary-container:color-mix(in srgb,{$primary} 80%,black);--header-bg:{$header}}"
            . ":root.dark:not([data-theme]){--md-sys-color-primary:color-mix(in srgb,{$primary} 60%,white);--md-sys-color-on-primary:{$dark};"
            . "--md-sys-color-primary-container:color-mix(in srgb,{$primary} 35%,black);"
            . "--md-sys-color-on-primary-container:color-mix(in srgb,{$primary} 20%,white);--header-bg:{$header}}";
    }

    private function normalize(string $color): ?string
    {
        if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/Di', $color)) {
            return null;
        }

        $hex = strtolower(substr($color, 1));

        return '#' . (strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex);
    }

    private function rgb(string $hex): array
    {
        return array_map('hexdec', str_split(substr($hex, 1), 2));
    }

    private function mix(string $hex, float $weight, int $with): string
    {
        return '#' . implode(array_map(
            fn(int $c) => str_pad(dechex((int)round($c * $weight + $with * (1 - $weight))), 2, '0', STR_PAD_LEFT),
            $this->rgb($hex)
        ));
    }

    private function contrast(string $hex): string
    {
        [$r, $g, $b] = array_map(function (int $c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $this->rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b > 0.179 ? '#000000' : '#ffffff';
    }
}
