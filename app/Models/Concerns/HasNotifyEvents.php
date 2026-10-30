<?php

namespace App\Models\Concerns;

use App\Enums\NotifyEvent;
use App\Services\Notify\NotifyRegistry;
use App\Services\Notify\NotifyService;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasNotifyEvents
{
    protected static function bootHasNotifyEvents(): void
    {
        static::created(fn ($m) => NotifyService::handle($m, NotifyEvent::Created));
        static::updated(fn ($m) => NotifyService::handle($m, NotifyEvent::Updated));
        static::deleted(function ($m) {
            $secondDeleteOfSoftDeleted = method_exists($m, 'isForceDeleting') && $m->isForceDeleting() && $m->getOriginal('deleted_at');

            if (! $secondDeleteOfSoftDeleted) {
                NotifyService::handle($m, NotifyEvent::Deleted);
            }
        });
    }

    public static function notifyLabel(): string
    {
        return NotifyRegistry::trackables()[static::class] ?? class_basename(static::class);
    }

    public function notifyValues(): array
    {
        $values = [];

        foreach (array_keys(Arr::except($this->getAttributes(), [...static::notifyExcept(), ...$this->getHidden()])) as $key) {
            $value = $this->getAttribute($key);
            $value = $value instanceof CarbonInterface ? toJalali($value) : $value;
            $value = $value instanceof \BackedEnum ? $value->value : $value;

            if (is_scalar($value)) {
                $values[$key] = is_string($value) ? Str::limit($value, 500) : $value;
            }
        }

        return $values;
    }

    public static function notifyExcept(): array
    {
        return [];
    }

    public static function notifyFields(): array
    {
        return [];
    }

    public static function notifyPlaceholders(): array
    {
        return array_values(array_diff((new static)->getFillable(), static::notifyExcept()));
    }
}
