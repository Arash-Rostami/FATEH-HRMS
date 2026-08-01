<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DetailsForm;
use App\Models\Profile;
use App\Services\ProfileDetailCatalog;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class SaveDetailsAction
{
    public function execute(DetailsForm $form): Profile
    {
        $form->normalize();
        $form->validate();

        $profile = Auth::user()?->profile;
        $profile->syncDetails($this->getFormattedValues($form));

        return $profile;
    }

    public function getFormattedValues(DetailsForm $form): array
    {
        $values = $form->values;
        $formatted = [];

        foreach (ProfileDetailCatalog::userDefinitions() as $key => $def) {
            if ($def['type'] === 'date') {
                if (
                    array_key_exists($key . 'Year', $values) || array_key_exists($key . 'Month', $values) || array_key_exists($key . 'Day', $values)
                ) {
                    $formatted[$key] = $this->assembleDate(
                        $values[$key . 'Year'] ?? null,
                        $values[$key . 'Month'] ?? null,
                        $values[$key . 'Day'] ?? null
                    );
                } elseif (array_key_exists($key, $values)) {
                    $formatted[$key] = $values[$key];
                }

                continue;
            }

            if (array_key_exists($key, $values)) {
                $formatted[$key] = $values[$key];
            }
        }

        return $formatted;
    }

    private function assembleDate(mixed $year, mixed $month, mixed $day): ?string
    {
        if (!$year || !$month || !$day) {
            return null;
        }

        try {
            return Jalalian::fromFormat('Y/m/d', sprintf('%04d/%02d/%02d', $year, $month, $day))
                ->toCarbon()
                ->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
