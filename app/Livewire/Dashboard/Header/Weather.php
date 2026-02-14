<?php

namespace App\Livewire\Dashboard\Header;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Livewire\Component;

class Weather extends Component
{
    public array $weatherData = [];

    public function mount()
    {
        $this->weatherData = $this->getWeather();
    }

    public function getWeather()
    {
        return Cache::remember('weather.tehran', now()->addMinutes(240), function () {
            $keys = explode(',', Config::get('services.openweather.keys'));
            $urlBase = Config::get('services.openweather.url', 'http://api.openweathermap.org/data/2.5/weather');
            $city = Config::get('services.openweather.city', 'Tehran');

            $defaultWeather = ['weather' => '', 'temperature' => 'N/A', 'description' => ''];

            if (empty($keys) || !$keys[0]) {
                return $defaultWeather;
            }

            $apiKey = trim($keys[array_rand($keys)]);

            $url = "{$urlBase}?q={$city}&appid={$apiKey}&units=metric";

            try {
                $client = new Client(['timeout' => 5, 'connect_timeout' => 3]);
                $response = $client->get($url);

                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getBody(), true);

                    if (isset($data['weather'][0]['main']) && isset($data['main']['temp'])) {
                        return [
                            'weather' => $data['weather'][0]['main'],
                            'temperature' => round($data['main']['temp']),
                            'description' => $data['weather'][0]['description'] ?? ''
                        ];
                    }
                }
            } catch (\Exception $e) {
                return $defaultWeather;
            }

            return $defaultWeather;
        });
    }

    public function render()
    {
        return view('livewire.dashboard.header.weather');
    }
}
