<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Permission extends Model
{
    use HasFactory;

    public const ACTIONS = ['view', 'create', 'update', 'delete', 'restore'];

    protected $fillable = [
        'user_id',
        'is_super_admin',
        'abilities',
        'excluded_modules',
    ];

    public function allowedModules(): array
    {
        if ($this->is_super_admin) {
            return array_values(array_diff(
                array_keys(self::availableModules()),
                $this->excluded_modules ?? []
            ));
        }

        return collect($this->abilities ?? [])
            ->pluck('module')
            ->filter()
            ->values()
            ->toArray();
    }

    public static function availableModules(): array
    {
        return once(fn() => Cache::remember('permission_modules', now()->addDay(), function () {
            $modules = [];

            foreach (glob(app_path('Filament/Resources/*Resource.php')) as $dir) {
                $english = basename($dir, 'Resource.php');
                $key = Str::snake($english);

                $pathSnake = lang_path("fa/resources/{$key}/strings.php");
                $pathLower = lang_path("fa/resources/" . strtolower($english) . "/strings.php");
                $pathSecondWord = lang_path("fa/resources/" . Str::afterLast($key, '_') . "/strings.php");

                $strings = [];

                if (file_exists($pathSnake)) {
                    $strings = require $pathSnake;
                } elseif (file_exists($pathLower)) {
                    $strings = require $pathLower;
                } elseif (file_exists($pathSecondWord)) {
                    $strings = require $pathSecondWord;
                }

                $farsi = $strings['plural_label'] ?? $strings['label'] ?? null;

                $modules[$key] = $farsi ? "{$farsi} ({$english})" : $english;
            }

            ksort($modules);

            return $modules;
        }));
    }

    public static function cacheKey(int $userId): string
    {
        return "user_permission:{$userId}";
    }

    public function can(string $module, string $action): bool
    {
        if ($this->is_super_admin) {
            return !$this->isModuleExcluded($module);
        }

        return collect($this->abilities ?? [])
            ->where('module', $module)
            ->flatMap(fn($row) => $row['actions'] ?? [])
            ->contains($action);
    }

    public static function forUser(int $userId): ?self
    {
        return Cache::remember(
            self::cacheKey($userId),
            now()->addDay(),
            fn() => self::where('user_id', $userId)->first()
        );
    }

    public function isModuleExcluded(string $module): bool
    {
        return in_array($module, $this->excluded_modules ?? [], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        $flush = fn(self $m) => Cache::forget(self::cacheKey($m->user_id));

        // Null the inactive side: super ignores abilities, non-super ignores excluded_modules.
        static::saving(function (self $m) {
            if ($m->is_super_admin) {
                $m->abilities = null;
            } else {
                $m->excluded_modules = null;
            }
        });

        static::saved($flush);
        static::deleted($flush);
    }

    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'abilities' => 'array',
            'excluded_modules' => 'array',
        ];
    }
}
