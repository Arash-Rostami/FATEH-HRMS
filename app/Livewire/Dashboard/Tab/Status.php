<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Status extends Component
{
    public string $activeFilter = 'onsite';
    public string $search = '';

    public function sendSms(string $userId, SmsService $smsService)
    {
        $user = User::findOrFail($userId);

        if ($user->sms_number) {
            // Placeholder message - ideally passed from frontend modal
            $message = "Your status has been updated.";
            $smsService->send($user->sms_number, $message);

            $this->dispatch('toast-message', message: 'پیامک با موفقیت ارسال شد', type: 'success');
        } else {
            $this->dispatch('toast-message', message: 'شماره تلفن همراه یافت نشد', type: 'error');
        }
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['profile.department'])
            ->where('status', 'active')
            ->when($this->activeFilter !== 'all', function (Builder $query) {
                $query->where('presence', $this->activeFilter);
            })
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('profile', function (Builder $profileQuery) {
                            $profileQuery->where('position', 'like', '%' . $this->search . '%')
                                ->orWhereHas('department', function (Builder $deptQuery) {
                                    $deptQuery->where('name', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        $counts = User::query()
            ->where('status', 'active')
            ->selectRaw('presence, count(*) as count')
            ->groupBy('presence')
            ->pluck('count', 'presence')
            ->toArray();

        return [
            'onsite' => $counts['onsite'] ?? 0,
            'remote' => $counts['remote'] ?? 0,
            'busy' => $counts['busy'] ?? 0,
            'mission' => $counts['mission'] ?? 0,
        ];
    }

    public function setFilter(string $filter)
    {
        $this->activeFilter = $filter;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.status.index');
    }
}
