<?php

namespace App\Livewire\Dashboard\Suggestion\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class SuggestionForm extends Form
{
    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('required|string|min:10')]
    public string $descriptionSelf = '';

    #[Validate('array')]
    public array $departments = [];

    #[Validate('required|array|min:1')]
    public array $purpose = [];

    #[Validate('required|array|min:1')]
    public array $rule = [];

    #[Validate('nullable|file|mimes:pdf,png,jpg|max:10240')]
    public $attachment = null;

    public bool $selfFill = false;
    public string $feedbackTeam = '';
    public string $descriptionTeam = '';
    public array $feedback = [];
    public array $descriptionDepts = [];

    protected function messages(): array
    {
        return [
            'title.required' => 'وارد کردن عنوان الزامی است.',
            'title.string' => 'عنوان باید متن باشد.',
            'title.min' => 'عنوان باید حداقل ۳ کاراکتر باشد.',
            'title.max' => 'عنوان نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'descriptionSelf.required' => 'وارد کردن توضیحات الزامی است.',
            'descriptionSelf.string' => 'توضیحات باید متن باشد.',
            'descriptionSelf.min' => 'توضیحات باید حداقل ۱۰ کاراکتر باشد.',
            'departments.array' => 'واحدها باید به صورت آرایه باشند.',
            'purpose.required' => 'انتخاب هدف الزامی است.',
            'purpose.array' => 'هدف باید به صورت آرایه باشد.',
            'purpose.min' => 'حداقل یک هدف باید انتخاب شود.',
            'rule.required' => 'انتخاب قوانین الزامی است.',
            'rule.array' => 'قوانین باید به صورت آرایه باشند.',
            'rule.min' => 'حداقل یک قانون باید انتخاب شود.',
            'attachment.file' => 'فایل انتخاب شده معتبر نیست.',
            'attachment.mimes' => 'فرمت فایل باید pdf، png یا jpg باشد.',
            'attachment.max' => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.',
        ];
    }

    protected function attributes(): array
    {
        return [
            'title' => 'عنوان',
            'descriptionSelf' => 'توضیحات',
            'departments' => 'واحدها',
            'purpose' => 'هدف',
            'rule' => 'قوانین',
            'attachment' => 'پیوست',
        ];
    }

    public function validated(): array
    {
        return $this->validate();
    }
}
