<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Actions\SaveDetailsAction;
use App\Livewire\Dashboard\Profile\Forms\DetailsForm;
use App\Livewire\Dashboard\Profile\Presentation\DetailsPresenter;
use App\Services\ProfileDetailCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Details extends Component
{
    public DetailsForm $form;

    public bool $hasProfile = false;

    public function mount(): void
    {
        $profile = Auth::user()->profile;
        $this->hasProfile = (bool)$profile;

        $existing = $profile ? $profile->detailsMap()->toArray() : [];

        $values = collect(ProfileDetailCatalog::keys())
            ->mapWithKeys(fn($key) => [$key => $existing[$key] ?? ''])
            ->all();

        foreach ($values as $key => $value) {
            $def = ProfileDetailCatalog::definition($key);
            if ($def && $def['type'] === 'date' && !empty($value)) {
                try {
                    $jalali = Jalalian::fromCarbon(Carbon::parse($value));
                    $values[$key . 'Year'] = $jalali->getYear();
                    $values[$key . 'Month'] = $jalali->getMonth();
                    $values[$key . 'Day'] = $jalali->getDay();
                } catch (\Throwable $e) {
                    Log::warning("Failed to parse date detail for profile key '{$key}': " . $e->getMessage());
                }
            }
        }

        $this->form->values = $values;
    }

    public function render(DetailsPresenter $presenter)
    {
        return view('livewire.dashboard.profile.details', [
            'groups' => $presenter->groups(),
        ]);
    }

    public function save(SaveDetailsAction $action): void
    {
        if (!Auth::user()->profile) {
            $this->dispatch('toast', message: 'ابتدا «اطلاعات فردی» را تکمیل و ذخیره کنید.', type: 'error');
            return;
        }

        $action->execute($this->form);

        $this->dispatch('toast', message: 'اطلاعات تکمیلی با موفقیت ذخیره شد.', type: 'success');
    }
}
