<?php

namespace App\Livewire\Dashboard\Navbar\Actions;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;

class FetchWeatherAction
{
    private static array $default = ['weather' => '', 'temperature' => 'N/A', 'description' => ''];

    public function execute(): array
    {
        $config = [
            'keys'     => explode(',', Config::get('services.openweather.keys')),
            'url_base' => Config::get('services.openweather.url', 'http://api.openweathermap.org/data/2.5/weather'),
            'city'     => Config::get('services.openweather.city', 'Tehran'),
        ];

        if (empty($config['keys']) || !$config['keys'][0]) return self::$default;

        $url = "{$config['url_base']}?q={$config['city']}&appid={$config['keys'][array_rand($config['keys'])]}&units=metric";

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

        } catch (\Exception) {
            return self::$default;
        }
    }
}
