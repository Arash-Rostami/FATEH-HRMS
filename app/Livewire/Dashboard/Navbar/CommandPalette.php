<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Services\NavigationSearchService;
use Exception;
use Livewire\Component;

class CommandPalette extends Component
{
    public string $query = '';
    public array $results = [];

    public function render()
    {
        return view('livewire.dashboard.navbar.command-palette');
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            return;
        }

        $this->results = app(NavigationSearchService::class)->search($this->query);
    }

    public function selectResult(string $action): mixed
    {
        if (!empty($this->results)) {
            $selectedItem = collect($this->results)->firstWhere('action', $action);

            if ($selectedItem) {
                $this->dispatch('add-recent-search', item: $selectedItem);
            }
        }

        $parts = explode(':', $action, 2);

        if (count($parts) < 2) {
            return null;
        }

        [$type, $target] = $parts;

        return match ($type) {
            'tab' => $this->handleTab($target),
            'route' => $this->handleRoute($target),
            'url' => redirect()->to($target),
            'event' => $this->handleEvent($target),
            default => null,
        };
    }

    private function handleTab(string $target): void
    {
        $this->dispatch('switch-tab', tab: $target);
        $this->resetPalette();
    }

    private function handleRoute(string $target): mixed
    {
        try {
            return redirect()->route($target);
        } catch (Exception) {
            return null;
        }
    }

    private function handleEvent(string $target): void
    {
        $this->dispatch($target);
        $this->resetPalette();
    }

    private function resetPalette(): void
    {
        $this->reset(['query', 'results']);
        $this->dispatch('close-command-palette');
    }
}
