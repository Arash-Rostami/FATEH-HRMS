<?php

namespace App\Livewire\Dashboard\Profile\Forms;

use App\Services\ProfileDetailCatalog;
use Livewire\Form;
use Livewire\Attributes\Validate;

class DetailsForm extends Form
{
    public array $values = [];

    protected function rules(): array
    {
        $rules = ProfileDetailCatalog::rules('values');

        // Add dynamic rules for the date parts
        foreach (ProfileDetailCatalog::definitions() as $key => $def) {
            if ($def['type'] === 'date') {
                $rules["values.{$key}Year"] = 'nullable|integer|min:1300|max:1500';
                $rules["values.{$key}Month"] = 'nullable|integer|min:1|max:12';
                $rules["values.{$key}Day"] = 'nullable|integer|min:1|max:31';
            }
        }

        return $rules;
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

    protected function messages(): array
    {
        return [
            'values.*.integer' => ':attribute باید یک عدد صحیح باشد.',
            'values.*.min'     => ':attribute نمی‌تواند کمتر از مقدار مجاز باشد.',
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

    public function getFormattedValues(): array
    {
        $formattedValues = [];

        // Loop through all catalog definitions instead of $this->values
        // to ensure we process every field, including virtual date parts
        foreach (ProfileDetailCatalog::definitions() as $key => $def) {
            if ($def['type'] === 'date') {
                $year = $this->values[$key . 'Year'] ?? null;
                $month = $this->values[$key . 'Month'] ?? null;
                $day = $this->values[$key . 'Day'] ?? null;

                if ($year && $month && $day) {
                    $formattedValues[$key] = str_pad($year, 4, '0', STR_PAD_LEFT) . '/' .
                                             str_pad($month, 2, '0', STR_PAD_LEFT) . '/' .
                                             str_pad($day, 2, '0', STR_PAD_LEFT);
                } else {
                    // Fallback to existing value if parts are missing
                    $formattedValues[$key] = $this->values[$key] ?? null;
                }
            } else {
                $formattedValues[$key] = $this->values[$key] ?? null;
            }
        }

        return $formattedValues;
    }
}