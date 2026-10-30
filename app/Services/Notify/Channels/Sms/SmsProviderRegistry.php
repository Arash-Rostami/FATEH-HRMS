<?php

namespace App\Services\Notify\Channels\Sms;

use App\Services\Notify\Channels\Sms\Providers\KavenegarProvider;
use App\Services\Notify\Channels\Sms\Providers\LogProvider;

final class SmsProviderRegistry
{
    public const PROVIDERS = [KavenegarProvider::class, LogProvider::class];

    public static function active(): SmsProvider
    {
        $name = (string) config('services.sms.provider', 'kavenegar');

        foreach (self::PROVIDERS as $class) {
            $provider = new $class;

            if ($provider->key() === $name && $provider->isConfigured()) {
                return $provider;
            }
        }

        return new LogProvider;
    }
}
