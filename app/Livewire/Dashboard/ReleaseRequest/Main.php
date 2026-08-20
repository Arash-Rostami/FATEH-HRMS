<?php

namespace App\Livewire\Dashboard\ReleaseRequest;

use App\Enums\ReleaseRequestType;
use App\Livewire\Dashboard\ReleaseRequest\Actions\SubmitReleaseRequestAction;
use App\Livewire\Dashboard\ReleaseRequest\Forms\ReleaseRequestForm;
use App\Livewire\Dashboard\ReleaseRequest\Presentation\ReleaseRequestPresenter;
use App\Models\ReleaseRequest;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination, WithFileUploads;

    public ReleaseRequestForm $form;
    public bool $show = false;
    public string $activeTab = 'submit';
    public int $perPage = 5;

    public function mount(): void
    {
        abort_unless(auth()->check(), 401);
    }

    public function open(?string $type = null): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->activeTab = 'submit';
        $this->perPage = 5;

        $resolved = ReleaseRequestType::tryFrom($type ?? '');
        $this->form->type = ($resolved ?? ReleaseRequestType::Recommendation)->value;

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->form->reset();
        $this->resetValidation();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['submit', 'history'], true) ? $tab : 'submit';
    }

    public function loadMore(): void
    {
        $this->perPage += 5;
    }

    public function removeAttachment(int $index): void
    {
        unset($this->form->attachments[$index]);
        $this->form->attachments = array_values($this->form->attachments);
    }

    #[Computed]
    public function myRequests()
    {
        return ReleaseRequest::where('user_id', auth()->id())
            ->latest()
            ->paginate($this->perPage);
    }

    public function submit(SubmitReleaseRequestAction $action): void
    {
        $action->execute($this->form);

        $this->dispatch('toast', message: 'درخواست شما با موفقیت ثبت شد.', type: 'success');
        $this->close();
    }

    #[Computed]
    public function types(): array
    {
        return ReleaseRequestType::cases();
    }

    public function render()
    {
        return view('livewire.dashboard.release-request');
    }

    #[Computed]
    public function presenter(): ReleaseRequestPresenter
    {
        return new ReleaseRequestPresenter();
    }
}