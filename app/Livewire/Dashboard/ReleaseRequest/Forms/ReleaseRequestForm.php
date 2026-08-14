<?php

namespace App\Livewire\Dashboard\ReleaseRequest\Forms;

use App\Enums\ReleaseRequestType;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ReleaseRequestForm extends Form
{
    public string $type = 'recommendation';
    public string $title = '';
    public string $body = '';
    public array $attachments = [];

    public function rules(): array
    {
        return [
            'type'  => ['required', Rule::enum(ReleaseRequestType::class)],
            'title' => ['required', 'string', 'max:191', $this->strippedMinLength(3)],
            'body'  => ['required', 'string', 'max:5000', $this->strippedMinLength(5)],
        ];
    }

    private function strippedMinLength(int $min): Closure
    {
        return function ($attribute, $value, $fail) use ($min) {
            if (mb_strlen(trim(strip_tags((string) $value))) < $min) {
                $fail("حداقل {$min} کاراکتر معتبر الزامی است.");
            }
        };
    }

    public function validated(): array
    {
        return $this->validate();
    }

    protected function validationAttributes(): array
    {
        return [
            'type'  => 'نوع درخواست',
            'title' => 'عنوان',
            'body'  => 'متن درخواست',
        ];
    }
}