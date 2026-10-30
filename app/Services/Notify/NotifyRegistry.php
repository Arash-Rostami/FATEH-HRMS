<?php

namespace App\Services\Notify;

use App\Models\Concerns\HasNotifyEvents;
use App\Services\Notify\Channels\MailChannel;
use App\Services\Notify\Channels\SmsChannel;
use App\Services\Notify\Contracts\NotifyChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class NotifyRegistry
{
    public const CHANNELS = [MailChannel::class, SmsChannel::class];

    public const TEMPLATES = ['plain' => 'mail.notify', 'styled' => 'mail.notify-styled'];

    private static ?array $channels = null;

    private static array $fieldLabels = [];

    public static function channels(): array
    {
        if (self::$channels === null) {
            self::$channels = [];

            foreach (self::CHANNELS as $class) {
                $channel = new $class;
                self::$channels[$channel->key()] = $channel;
            }
        }

        return array_filter(self::$channels, fn (NotifyChannel $c) => ! tenantOff('notify:'.$c->key()));
    }

    public static function channel(string $key): ?NotifyChannel
    {
        return self::channels()[$key] ?? null;
    }

    public static function channelOptions(): array
    {
        return array_map(fn (NotifyChannel $c) => $c->label(), self::channels());
    }

    public static function templateOptions(): array
    {
        return array_map(fn (string $key) => __("resources/notify_rule/strings.templates.{$key}"), array_combine(array_keys(self::TEMPLATES), array_keys(self::TEMPLATES)));
    }

    public static function templateView(string $key): string
    {
        return self::TEMPLATES[$key] ?? self::TEMPLATES['plain'];
    }

    public static function fieldLabels(string $class): array
    {
        return self::$fieldLabels[$class] ??= Cache::remember('notify:field-labels:'.md5($class), now()->addDay(), fn () => self::loadFieldLabels($class));
    }

    private static function loadFieldLabels(string $class): array
    {
        foreach (glob(app_path('Filament/Resources/*Resource.php')) as $file) {
            $english = basename($file, 'Resource.php');
            $resource = "App\\Filament\\Resources\\{$english}Resource";

            if (! class_exists($resource) || $resource::getModel() !== $class) {
                continue;
            }

            $key = Str::snake($english);

            foreach ([$key, strtolower($english), Str::afterLast($key, '_'), Str::before($key, '_')] as $dir) {
                $path = lang_path("fa/resources/{$dir}/strings.php");

                if (file_exists($path)) {
                    return array_filter((require $path)['fields'] ?? [], 'is_string');
                }
            }
        }

        return [];
    }

    private static array $permitted = [];

    public static function permitted(): array
    {
        $who = auth()->id().'|'.md5(json_encode(config('app.off')));

        if (isset(self::$permitted[$who])) {
            return self::$permitted[$who];
        }

        $resources = [];

        foreach (glob(app_path('Filament/Resources/*Resource.php')) as $file) {
            $resource = 'App\\Filament\\Resources\\'.basename($file, '.php');

            if (class_exists($resource)) {
                $resources[$resource::getModel()] ??= $resource;
            }
        }

        return self::$permitted[$who] = array_filter(
            self::trackables(),
            fn (string $label, string $class) => isset($resources[$class]) && $resources[$class]::canViewAny(),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public static function trackables(): array
    {
        return once(fn () => Cache::remember('notify:trackables', now()->addDay(), function () {
            $labels = [];
            $off = [];

            foreach (glob(app_path('Filament/Resources/*Resource.php')) as $file) {
                $resource = 'App\\Filament\\Resources\\'.basename($file, '.php');

                if (class_exists($resource)) {
                    $labels[$resource::getModel()] ??= $resource::getPluralModelLabel();

                    if (tenantOffName('filament.admin.resources.'.$resource::getSlug().'.index')) {
                        $off[$resource::getModel()] = true;
                    }
                }
            }

            $models = [];

            foreach (glob(app_path('Models/*.php')) as $file) {
                $class = 'App\\Models\\'.basename($file, '.php');

                if (class_exists($class) && ! isset($off[$class]) && in_array(HasNotifyEvents::class, class_uses_recursive($class), true)) {
                    $models[$class] = $labels[$class] ?? class_basename($class);
                }
            }

            return $models;
        }));
    }
}
