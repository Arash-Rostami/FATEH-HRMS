<?php

namespace App\Livewire\Dashboard\Navbar\Actions;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class FetchWeatherAction
{
    private static array $default = ['weather' => '', 'temperature' => 'N/A', 'description' => ''];

    // Fresh window: served instantly, no API call. Stale window: still served instantly, but
    // one background refresh is deferred (Cache::flexible's atomic lock guarantees at most one
    // real HTTP call fires across all concurrent requests, protecting the API quota).
    private const FRESH_SECONDS = 14400;
    private const STALE_SECONDS = 21600;

    public function execute(): array
    {
        $city = Config::get('services.openweather.city', 'Tehran');
        $cacheKey = 'weather.' . Str::slug($city);

        return Cache::flexible($cacheKey, [self::FRESH_SECONDS, self::STALE_SECONDS], fn() => $this->fetch($city));
    }

    private function fetch(string $city): array
    {
        $keys = array_filter(explode(',', (string) Config::get('services.openweather.keys')));
        if (!$keys) return self::$default;

        $urlBase = Config::get('services.openweather.url', 'http://api.openweathermap.org/data/2.5/weather');
        $url = "{$urlBase}?q={$city}&appid={$keys[array_rand($keys)]}&units=metric";

        try {
            $response = (new Client(['timeout' => 5, 'connect_timeout' => 3]))->get($url);

            if ($response->getStatusCode() !== 200) return self::$default;

            $data = json_decode($response->getBody(), true);

            return isset($data['weather'][0]['main'], $data['main']['temp'])
                ? [
                    'weather'     => $data['weather'][0]['main'],
                    'temperature' => round($data['main']['temp']),
                    'description' => $data['weather'][0]['description'] ?? '',
                ]
                : self::$default;

        } catch (\Exception $e) {
            report($e);
            return self::$default;
        }
    }
}
