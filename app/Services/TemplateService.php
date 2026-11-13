<?php

namespace App\Services;

abstract class TemplateService
{
    abstract protected static function lines(): array;

    public static function all(): array
    {
        return static::lines();
    }

    public static function for(string $key): array
    {
        return static::lines()[$key] ?? [];
    }

    public static function pick(string $key, array $vars = []): string
    {
        $lines = static::for($key);

        if ($lines === []) {
            return '';
        }

        return strtr($lines[array_rand($lines)], array_combine(
            array_map(fn(string $name): string => '{' . $name . '}', array_keys($vars)),
            $vars,
        ));
    }
}
