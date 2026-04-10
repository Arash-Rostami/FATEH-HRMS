<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class AboutForm extends Form
{
    #[Validate('nullable|string|max:2000')]
    public ?string $bio = '';

    #[Validate('nullable|string|max:255')]
    public ?string $movies = '';

    #[Validate('nullable|string|max:255')]
    public ?string $music = '';

    #[Validate('nullable|string|max:255')]
    public ?string $hobbies = '';

    #[Validate('nullable|string|max:255')]
    public ?string $food = '';

    #[Validate('nullable|string|max:255')]
    public ?string $sports = '';

    protected function messages(): array
    {
        return [
            'bio.string' => 'مقدار فیلد درباره من باید از نوع متن باشد.',
            'bio.max' => 'متن درباره من نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',

            'movies.string' => 'مقدار فیلد فیلم و سریال باید از نوع متن باشد.',
            'movies.max' => 'متن فیلم و سریال نباید بیشتر از ۲۵۵ کاراکتر باشد.',

            'music.string' => 'مقدار فیلد موسیقی و پادکست باید از نوع متن باشد.',
            'music.max' => 'متن موسیقی و پادکست نباید بیشتر از ۲۵۵ کاراکتر باشد.',

            'hobbies.string' => 'مقدار فیلد سرگرمی‌ها باید از نوع متن باشد.',
            'hobbies.max' => 'متن سرگرمی‌ها نباید بیشتر از ۲۵۵ کاراکتر باشد.',

            'food.string' => 'مقدار فیلد غذا و خوراکی باید از نوع متن باشد.',
            'food.max' => 'متن غذا و خوراکی نباید بیشتر از ۲۵۵ کاراکتر باشد.',

            'sports.string' => 'مقدار فیلد ورزش و تفریح باید از نوع متن باشد.',
            'sports.max' => 'متن ورزش و تفریح نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        ];
    }

    public function getAboutMeData(): array
    {
        return [
            'bio' => $this->bio,
            'movies' => $this->movies,
            'music' => $this->music,
            'hobbies' => $this->hobbies,
            'food' => $this->food,
            'sports' => $this->sports,
        ];
    }
}
