<?php

namespace App\Livewire\Dashboard\Navbar;

use App\Livewire\Dashboard\Tabs;
use App\Services\Search\ContentService;
use App\Services\Search\NavigationService;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CommandPalette extends Component
{
    public string $query = '';
    public array $results = [];

    /** 'navigate' = jump to a module (static list) | 'content' = search real records. */
    public string $mode = 'navigate';

    public function render()
    {
        return view('livewire.dashboard.navbar.top.command-palette');
    }

    public function selectResult(string $action): void
    {
        if (!empty($this->results)) {
            $selectedItem = collect($this->results)->firstWhere('action', $action);

            if ($selectedItem) {
                $this->dispatch('add-recent-search', item: $selectedItem);
            }
        }

        $parts = explode(':', $action, 2);

        if (count($parts) < 2) return;

        [$type, $target] = $parts;

        match ($type) {
            'tab' => $this->handleTab($target),
            'route' => $this->handleRoute($target),
            'url' => $this->handleUrl($target),
            'event' => $this->handleEvent($target),
            default => null,
        };
    }

    /**
     * Flip the "smart button" between navigation and full content search,
     * then re-run the current query under the new engine.
     */
    public function toggleMode(): void
    {
        $this->mode = $this->mode === 'navigate' ? 'content' : 'navigate';
        $this->results = [];
        $this->updatedQuery();
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            return;
        }

        $this->results = $this->mode === 'content'
            ? app(ContentService::class)->search($this->query)
            : app(NavigationService::class)->search($this->query);
    }

    private function handleEvent(string $target): void
    {
        $this->dispatch($target);
        $this->resetPalette();
    }

    private function handleRoute(string $target): void
    {
        try {
            $this->redirectRoute($target, navigate: true);
        } catch (Exception $e) {
            Log::error("CommandPalette route redirection failed: {$target}", ['exception' => $e]);
        }
    }

    private function handleTab(string $target): void
    {
        $dashboardPath = parse_url(route('dashboard'), PHP_URL_PATH);
        $previousPath = parse_url(url()->previous(), PHP_URL_PATH);

        if ($previousPath === $dashboardPath) {
            $this->dispatch('switch-tab', tab: $target)->to(Tabs::class);
            $this->resetPalette();
            return;
        }

        $this->redirectRoute('dashboard', ['tab' => $target], navigate: true);
    }

    private function handleUrl(string $target): void
    {
        // navigate:true keeps the SPA feel; the target module reads `?open={id}`
        // via the WithRecordFocus trait and focuses the record in place.
        $this->redirect($target, navigate: true);
    }

    private function resetPalette(): void
    {
        $this->reset(['query', 'results']);
        $this->dispatch('close-command-palette');
    }
}
