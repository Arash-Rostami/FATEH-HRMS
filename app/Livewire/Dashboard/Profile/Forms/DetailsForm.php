<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use App\Services\ProfileDetailCatalog;
use Closure;
use Livewire\Form;
use Morilog\Jalali\CalendarUtils;

class DetailsForm extends Form
{
    public array $values = [];

    public function normalize(): void
    {
        $this->values = array_map(fn($v) => $v === '' ? null : $v, $this->values);
    }

    protected function messages(): array
    {
        return [
            'values.*.integer' => ':attribute باید یک عدد صحیح باشد.',
            'values.*.numeric' => ':attribute باید یک عدد باشد.',
            'values.*.min' => ':attribute نمی‌تواند کمتر از مقدار مجاز باشد.',
            'values.*.max' => 'مقدار واردشده برای :attribute بیش از حد مجاز است.',
            'values.*.in' => 'مقدار انتخاب‌شده برای :attribute نامعتبر است.',
            'values.*.string' => ':attribute باید به صورت متن باشد.',
            'values.*.required_with' => ':attribute الزامی است.',
        ];
    }

    protected function rules(): array
    {
        $rules = ProfileDetailCatalog::rules('values');

        foreach (ProfileDetailCatalog::definitions() as $key => $def) {
            if ($def['type'] === 'date') {
                $rules["values.{$key}Year"] = "nullable|integer|min:1300|max:1500|required_with:values.{$key}Month,values.{$key}Day";
                $rules["values.{$key}Month"] = "nullable|integer|min:1|max:12|required_with:values.{$key}Year,values.{$key}Day";
                $rules["values.{$key}Day"] = ['nullable', 'integer', 'min:1', 'max:31', "required_with:values.{$key}Year,values.{$key}Month", $this->validJalaliDate($key)];
            }
        }

        return $rules;
    }

    private function validJalaliDate(string $key): Closure
    {
        return function ($attribute, $value, $fail) use ($key) {
            $year = $this->values["{$key}Year"] ?? null;
            $month = $this->values["{$key}Month"] ?? null;
            $day = $this->values["{$key}Day"] ?? null;

            if ($year && $month && $day && !CalendarUtils::checkDate((int) $year, (int) $month, (int) $day, true)) {
                $fail('تاریخ واردشده معتبر نیست.');
            }
        };
    }

    protected function validationAttributes(): array
    {
        $attrs = ProfileDetailCatalog::attributes('values');

        foreach (ProfileDetailCatalog::definitions() as $key => $def) {
            if ($def['type'] === 'date') {
                $attrs["values.{$key}Year"] = 'سال ' . $def['label'];
                $attrs["values.{$key}Month"] = 'ماه ' . $def['label'];
                $attrs["values.{$key}Day"] = 'روز ' . $def['label'];
            }
        }

        return $attrs;
    }
}
