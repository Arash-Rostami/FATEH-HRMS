<?php

namespace App\Livewire\Dashboard\Profile;

use App\Models\Channel;
use App\Models\Project;
use App\Models\User;
use App\Traits\HasFocusMode;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class Settings extends Component
{
    use HasFocusMode;

    public string $activeSection = 'appearance';

    private const SECTIONS = ['appearance', 'notifications', 'tables', 'focus'];

    private const MAX_RESOLVE_IDS = 50;

    public function switchTab(string $section): void
    {
        if (in_array($section, self::SECTIONS, true)) {
            $this->activeSection = $section;
        }
    }

    #[Renderless]
    public function resolveMuted(string $scope, array $ids): array
    {
        $ids = array_slice(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)), 0, self::MAX_RESOLVE_IDS);

        if (empty($ids)) {
            return [];
        }

        return match ($scope) {
            'channel' => Channel::whereHas('memberUsers', fn ($q) => $q->where('users.id', Auth::id()))
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            'contact' => User::whereIn('id', $ids)
                ->where('id', '!=', Auth::id())
                ->pluck('name', 'id')
                ->all(),
            'project' => Project::visibleTo(Auth::user())
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all(),
            default => [],
        };
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.dashboard.profile.settings', [
            'presence' => $user->presence,
            'focusUntil' => $user->focus_until,
        ]);
    }
}
