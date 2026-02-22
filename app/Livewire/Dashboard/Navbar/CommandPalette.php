<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Services\NavigationSearchService;
use Livewire\Component;

class CommandPalette extends Component
{
    public string $query = '';
    public array $results = [];

    public function render()
    {
        return view('livewire.dashboard.navbar.command-palette');
    }

    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            return;
        }

        $searchService = app(NavigationSearchService::class);
        $this->results = $searchService->search($this->query);
    }

    public function selectResult($action)
    {
        // Add to recent searches via browser event
        // The component will send back the full item details needed for history display
        if (!empty($this->results)) {
            // Find the item matching the action
            $selectedItem = collect($this->results)->firstWhere('action', $action);
            if ($selectedItem) {
                $this->dispatch('add-recent-search', item: $selectedItem);
            }
        }

        // Format: 'type:target'
        $parts = explode(':', $action, 2);

        if (count($parts) < 2) {
            return;
        }

        $type = $parts[0];
        $target = $parts[1];

        switch ($type) {
            case 'tab':
                $this->dispatch('switch-tab', tab: $target);
                $this->reset(['query', 'results']);
                $this->dispatch('close-command-palette');
                break;

            case 'route':
                try {
                     return redirect()->route($target);
                } catch (\Exception $e) {
                    // Fallback
                }
                break;

            case 'url':
                return redirect()->to($target);

            case 'event':
                $this->dispatch($target);
                $this->reset(['query', 'results']);
                $this->dispatch('close-command-palette');
                break;
        }
    }
}
