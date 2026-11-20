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
            'name.required' => 'نام گروه را وارد کنید.',
            'name.max' => 'نام گروه نباید بیشتر از ۱۰۰ کاراکتر باشد.',
            'slug.required' => 'کد گروه ساخته نشد. نام گروه را بررسی کنید.',
            'slug.max' => 'کد گروه بیش از ۱۲۰ کاراکتر است. نام کوتاه‌تری انتخاب کنید.',
            'slug.unique' => 'این کد گروه قبلاً استفاده شده است. نام دیگری انتخاب کنید.',
            'description.max' => 'توضیحات نباید بیشتر از ۵۰۰ کاراکتر باشد.',
            'type.required' => 'نوع گروه را انتخاب کنید.',
            'type.in' => '«عمومی» یا «خصوصی» را انتخاب کنید.',
        ];
    }
}
