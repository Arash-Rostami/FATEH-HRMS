<?php

namespace App\Livewire\Dashboard\Knowledge;

use App\Livewire\Dashboard\Knowledge\Presentation\MediaPresenter;
use App\Traits\Livewire\FocusOnRecord;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Library extends Component
{
    use FocusOnRecord;

    public function mount(?int $open = null): void
    {
        abort_if(tenantOff('knowledge:media'), 404);

        $this->open = $open;
    }

    #[Computed]
    public function library(): array
    {
        return app(MediaPresenter::class)->library();
    }

    protected function recordFocusType(): string
    {
        return 'media';
    }

    public function focusRecord(int $id): bool
    {
        foreach ($this->library['items'] as $item) {
            if ($item['id'] === $id || in_array($id, array_column(array_filter($item['episodes'], fn (array $episode) => $episode['unlock_at'] === null), 'id'), true)) {
                return true;
            }
        }

        $this->open = null;

        return false;
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.knowledge.library.placeholder');
    }

    public function render(): View
    {
        abort_if(tenantOff('knowledge:media'), 404);

        return view('livewire.dashboard.knowledge.library');
    }
}
