<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use App\Services\ProfileDetailCatalog;
use Livewire\Form;

class DetailsForm extends Form
{
    public array $values = [];

    protected function rules(): array
    {
        return ProfileDetailCatalog::rules('values');
    }

    protected function validationAttributes(): array
    {
        return ProfileDetailCatalog::attributes('values');
    }

    protected function messages(): array
    {
        return [
            'values.*.integer' => ':attribute باید یک عدد صحیح باشد.',
            'values.*.min'     => ':attribute نمی‌تواند منفی باشد.',
            'values.*.max'     => 'مقدار واردشده برای :attribute بیش از حد مجاز است.',
            'values.*.in'      => 'مقدار انتخاب‌شده برای :attribute نامعتبر است.',
            'values.*.string'  => ':attribute باید به صورت متن باشد.',
        ];
    }

    public function normalize(): void
    {
        $this->values = array_map(
            fn($v) => $v === '' ? null : $v,
            $this->values,
        );
    }
}
