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
            $apiKey = Config::get('services.openweather.key');

            $defaultWeather = ['weather' => '', 'temperature' => 'N/A', 'description' => ''];

            if (!$apiKey) {
                return $defaultWeather;
            }

            $url = "http://api.openweathermap.org/data/2.5/weather?q=Tehran&appid=$apiKey&units=metric";

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
