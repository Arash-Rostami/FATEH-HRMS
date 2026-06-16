<?php

namespace App\Livewire\Dashboard\Profile;

use App\Livewire\Dashboard\Profile\Actions\ResetDocumentStateAction;
use App\Livewire\Dashboard\Profile\Actions\UploadCustomDocumentAction;
use App\Livewire\Dashboard\Profile\Actions\UploadStandardDocumentAction;
use App\Livewire\Dashboard\Profile\Forms\DocumentForm;
use App\Livewire\Dashboard\Profile\Presentation\DocumentPresenter;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\{Computed, Locked, On};
use Livewire\Component;
use Livewire\WithFileUploads;

class Documents extends Component
{
    use WithFileUploads;

    public DocumentForm $form;

    #[Locked] public string $pendingUploadKey = '';
    #[Locked] public string $pendingFileName = '';
    #[Locked] public ?string $errorMessage = null;

    #[On('confirm-upload-documents')]
    public function confirmUpload(
        UploadStandardDocumentAction $standardAction,
        UploadCustomDocumentAction   $customAction): void
    {
        $isCustom = $this->pendingUploadKey === 'custom_upload_pending';

        $result = $isCustom
            ? $customAction->execute($this->form, $this->pendingFileName)
            : $standardAction->execute($this->form, $this->pendingUploadKey, $this->pendingFileName);

        $this->handleUploadResult($result, $isCustom ? 'custom' : 'standard');
    }

    #[Computed]
    public function profile(): Profile
    {
        return Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);
    }

    public function removeFile(string $key, ResetDocumentStateAction $resetAction): void
    {
        $resetAction->removeFile($this->form, $key);
    }

    public function render(DocumentPresenter $presenter, ResetDocumentStateAction $resetAction)
    {
        return view('livewire.dashboard.profile.documents', [
            'profile' => $this->profile,
            'standardTypes' => $presenter->standardTypes(),
            'parsedAttachments' => $presenter->parseAttachments($this->profile->attachments),
            'presenter' => $presenter,
            'resetAction' => $resetAction,
        ]);
    }

    public function showCustomUploadConfirmation(): void
    {
        $this->errorMessage = null;
        $this->form->validateCustomUpload();

        $this->pendingUploadKey = 'custom_upload_pending';
        $this->pendingFileName = $this->form->customFile?->getClientOriginalName() ?? 'فایل سفارشی';

        $this->dispatch('close-modal', name: 'upload-custom-modal');
        $this->dispatchConfirmation("آیا از صحت فایل سفارشی «{$this->pendingFileName}» اطمینان دارید؟");
    }

    public function showUploadConfirmation(string $key): void
    {
        $this->errorMessage = null;
        $this->form->validateStandardUpload($key);

        $file = $this->form->files[$key] ?? app(ResetDocumentStateAction::class)->getFile($this->form, $key);

        $this->pendingUploadKey = $key;
        $this->pendingFileName = $file?->getClientOriginalName() ?? 'فایل انتخاب شده';

        $this->dispatchConfirmation("آیا از صحت فایل «{$this->pendingFileName}» اطمینان دارید؟");
    }

    public function updated(string $property, mixed $value): void
    {
        if (str_starts_with($property, 'form.files.')) {
            $this->showUploadConfirmation(str_replace('form.files.', '', $property));
        }
    }

    private function dispatchConfirmation(string $message): void
    {
        $this->dispatch('open-confirmation',
            title: 'تایید نهایی بارگذاری',
            message: $message,
            method: 'confirmAction',
            params: 'confirm-upload-documents'
        );
    }

    private function handleUploadResult(array $result, string $type): void
    {
        if (!$result['success']) {
            $this->errorMessage = $result['error'];
            $this->dispatch('toast', message: $result['error'], type: 'error');
            $this->resetUploadState();
            return;
        }

        if ($type === 'custom') {
            $this->dispatch('close-modal', name: 'upload-custom-modal');
            $this->dispatch('toast', message: 'مدرک سفارشی با موفقیت ثبت نهایی شد.', type: 'success');
        } else {
            $this->dispatch('toast', message: 'مدرک با موفقیت ثبت نهایی شد.', type: 'success');
        }

        $this->resetUploadState();
    }

    private function resetUploadState(): void
    {
        $this->pendingUploadKey = '';
        $this->pendingFileName = '';
    }
}
