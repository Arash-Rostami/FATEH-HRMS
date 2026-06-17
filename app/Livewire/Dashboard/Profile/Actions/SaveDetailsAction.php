<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DetailsForm;
use App\Models\Profile;
use App\Services\ProfileDetailCatalog;
use Illuminate\Support\Facades\Auth;

class SaveDetailsAction
{
    public function execute(DetailsForm $form): Profile
    {
        $form->normalize();
        $form->validate();

        $profile = Auth::user()?->profile;

        $formattedValues = $this->getFormattedValues($form);

        $profile->syncDetails($formattedValues);

        return $profile;
    }

    public function getFormattedValues(DetailsForm $form): array
    {
        $values = $form->values;
        $formatted = [];

        foreach (ProfileDetailCatalog::definitions() as $key => $def) {
            if ($def['type'] === 'date') {
                if (
                    array_key_exists($key . 'Year', $values) || array_key_exists($key . 'Month', $values) || array_key_exists($key . 'Day', $values)
                ) {
                    $year = $values[$key . 'Year'] ?? null;
                    $month = $values[$key . 'Month'] ?? null;
                    $day = $values[$key . 'Day'] ?? null;

                    $formatted[$key] = ($year && $month && $day)
                        ? sprintf('%04d/%02d/%02d', $year, $month, $day)
                        : null;
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
}
