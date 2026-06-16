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
                $year = $values[$key . 'Year'] ?? null;
                $month = $values[$key . 'Month'] ?? null;
                $day = $values[$key . 'Day'] ?? null;

                if ($year && $month && $day) {
                    $formatted[$key] = str_pad($year, 4, '0', STR_PAD_LEFT) . '/' .
                        str_pad($month, 2, '0', STR_PAD_LEFT) . '/' .
                        str_pad($day, 2, '0', STR_PAD_LEFT);
                } elseif (array_key_exists($key . 'Year', $values) || array_key_exists($key . 'Month', $values) || array_key_exists($key . 'Day', $values)) {
                    // if part of date is submitted but not all, treat it as empty rather than preserving old invalid value
                    $formatted[$key] = null;
                } elseif (array_key_exists($key, $values)) {
                    $formatted[$key] = $values[$key];
                }
            } else {
                if (array_key_exists($key, $values)) {
                    $formatted[$key] = $values[$key];
                }
            }
        }
        return $formatted;
    }
}
