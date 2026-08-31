<?php

namespace App\Models;

use App\Services\Cache\ModelCacheVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

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

        return array_values(array_filter(array_column($this->abilities ?? [], 'module')));
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
        return ModelCacheVersion::key(self::class, "user_permission:{$userId}");
    }

    public function can(string $module, string $action): bool
    {
        if ($this->is_super_admin) {
            return !$this->isModuleExcluded($module);
        }

        foreach ($this->abilities ?? [] as $row) {
            if (($row['module'] ?? null) === $module && in_array($action, $row['actions'] ?? [], true)) {
                return true;
            }
        }

        return false;
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
        static::saving(function (self $m) {
            if ($m->is_super_admin) {
                $m->abilities = null;
            } else {
                $m->excluded_modules = null;
            }
        });

        static::creating(function (self $permission) {
            if (static::where('user_id', $permission->user_id)->exists()) {
                throw new RuntimeException(__('resources/permission/strings.validation.duplicate'));
            }
        });
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
