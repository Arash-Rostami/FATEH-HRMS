<?php

namespace App\Livewire\Dashboard\Channel\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class CreateChannelForm extends Form
{
    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public string $type = 'open';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('channels', 'slug')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:open,private'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'نام گروه الزامی است.',
            'name.max' => 'نام گروه نباید بیشتر از ۱۰۰ کاراکتر باشد.',
            'slug.required' => 'اسلاگ گروه الزامی است.',
            'slug.max' => 'اسلاگ نباید بیشتر از ۱۲۰ کاراکتر باشد.',
            'slug.unique' => 'این اسلاگ قبلا استفاده شده است.',
            'description.max' => 'توضیحات نباید بیشتر از ۵۰۰ کاراکتر باشد.',
            'type.required' => 'نوع گروه الزامی است.',
            'type.in' => 'نوع گروه نامعتبر است.',
        ];
    }
}