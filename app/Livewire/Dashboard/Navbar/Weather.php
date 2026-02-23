<?php

namespace App\Livewire\Dashboard\Navbar;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Weather extends Component
{
    public function render()
    {
        return view('livewire.dashboard.navbar.weather');
    }

    #[Computed(seconds: 14400, cache: true, key: 'weather.tehran')]
    public function weatherData()
    {
        $config = $this->getWeatherConfig();

        if (empty($config['keys']) || !$config['keys'][0]) return $this->defaultWeather();

        $apiKey = trim($config['keys'][array_rand($config['keys'])]);
        $url = "{$config['url_base']}?q={$config['city']}&appid={$apiKey}&units=metric";

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
            return $this->defaultWeather();
        }

        return $this->defaultWeather();
    }

    private function defaultWeather(): array
    {
        return ['weather' => '', 'temperature' => 'N/A', 'description' => ''];
    }

    private function getWeatherConfig(): array
    {
        return [
            'keys' => explode(',', Config::get('services.openweather.keys')),
            'url_base' => Config::get('services.openweather.url', 'http://api.openweathermap.org/data/2.5/weather'),
            'city' => Config::get('services.openweather.city', 'Tehran'),
        ];
    }
}
