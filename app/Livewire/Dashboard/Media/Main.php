<?php

namespace App\Livewire\Dashboard\Media;

use App\Livewire\Dashboard\Media\Presentation\MediaPresenter;
use App\Traits\Livewire\FocusOnRecord;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Main extends Component
{
    use FocusOnRecord;

    #[Computed]
    public function library(): array
    {
        abort_if(tenantOff('reports:media'), 404);

        return app(MediaPresenter::class)->library();
    }

    protected function recordFocusType(): string
    {
        return 'media';
    }

    public function focusRecord(int $id): bool
    {
        foreach ($this->library['items'] as $item) {
            if ($item['id'] === $id || in_array($id, array_column($item['episodes'], 'id'), true)) {
                return true;
            }
        }

        $this->open = null;

        return false;
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.media.placeholder');
    }

    public function render(): View
    {
        abort_if(tenantOff('reports:media'), 404);

        return view('livewire.dashboard.media');
    }
}
