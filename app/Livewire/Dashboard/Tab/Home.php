<?php

namespace App\Livewire\Dashboard\Tab;

use App\Livewire\Dashboard\Tab\Presentation\TabPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Home extends Component
{

    public ?array $tools = [];
    public string $teamSearch = '';
    public int $teamSkip = 5;
    public int $teamShowCount = 10;

    #[Computed]
    public function modules(): array
    {
        return Config::get('modules', []);
    }

    #[Computed]
    public function remainingTeam(): Collection
    {
        $pool = (new TabPresenter())->teamPulse()->skip($this->teamSkip)->values();

        if ($this->teamSearch !== '') {
            $needle = mb_strtolower($this->teamSearch);
            $pool = $pool->filter(fn ($u) => str_contains(mb_strtolower($u->casual_name), $needle))->values();
        }

        return $pool;
    }

    public function openTeamSearch(int $skip): void
    {
        $this->teamSkip = $skip;
        $this->teamSearch = '';
        $this->teamShowCount = 10;
        $this->dispatch('open-team-search');
    }

    public function loadMoreTeam(): void
    {
        $this->teamShowCount += 10;
    }

    public function updatedTeamSearch(): void
    {
        $this->teamShowCount = 10;
    }

    public function render()
    {
        $presenter = new TabPresenter();

        return view('livewire.dashboard.tab.home', [
            'tools' => $presenter->tools(),
            'stats' => $presenter->stats(),
            'shortcuts' => $presenter->shortcuts(),
            'teamPulse' => $presenter->teamPulse(),
            'gadgetCatalog' => $presenter->heroGadgetCatalog(),
        ]);
    }

    public function placeholder(): \Illuminate\View\View
    {
        return view('livewire.dashboard.tab.home.placeholder');
    }
}
