<?php

namespace App\Livewire\Dashboard\Profile\Actions;

use App\Livewire\Dashboard\Profile\Forms\DetailsForm;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class SaveDetailsAction
{
    public function execute(DetailsForm $form): Profile
    {
        $form->normalize();
        $form->validate();

        $profile = Auth::user()->profile;

        $formattedValues = $this->getFormattedValues($form);

        $profile->syncDetails($formattedValues);

        return $profile;
    }

    public function getFormattedValues(DetailsForm $form): array
    {
        $values = $form->values;
        foreach (\App\Services\ProfileDetailCatalog::definitions() as $key => $def) {
            if ($def['type'] === 'date') {
                $year = $values[$key . 'Year'] ?? null;
                $month = $values[$key . 'Month'] ?? null;
                $day = $values[$key . 'Day'] ?? null;

                if ($year && $month && $day) {
                    $values[$key] = str_pad($year, 4, '0', STR_PAD_LEFT) . '/' .
                        str_pad($month, 2, '0', STR_PAD_LEFT) . '/' .
                        str_pad($day, 2, '0', STR_PAD_LEFT);
                } else {
                    $values[$key] = null;
                }

                unset($values[$key . 'Year']);
                unset($values[$key . 'Month']);
                unset($values[$key . 'Day']);
            }
        }

        return $values;
    }
}
