<?php

namespace App\Livewire\Dashboard\Tab\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Documents extends Component
{
    use WithFileUploads;

    public $customFile;
    public $customType;

    public function updatedCustomFile()
    {
        $this->validate([
            'customFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
    }

    public function uploadCustom()
    {
        $this->validate([
            'customType' => 'required|string|max:100',
            'customFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $profile = $user->profile;
        $attachments = $profile->attachments ?? [];

        foreach ($attachments as $attachment) {
            if (isset($attachment['type']) && $attachment['type'] === $this->customType) {
                 $this->addError('customType', 'این نوع مدرک قبلاً بارگذاری شده است.');
                 return;
            }
        }

        $path = $this->customFile->store('documents/' . $user->id, 'public');

        $attachments[] = [
            'type' => $this->customType,
            'path' => $path,
            'name' => $this->customFile->getClientOriginalName(),
            'uploaded_at' => now()->toIso8601String(),
        ];

        $profile->update(['attachments' => $attachments]);

        $this->customFile = null;
        $this->customType = '';
        $this->dispatch('close-modal', name: 'upload-custom-modal');
        $this->dispatch('notify', message: 'مدرک با موفقیت بارگذاری شد.', type: 'success');
    }

    public function downloadDocument($index)
    {
        $user = Auth::user();
        $attachments = $user->profile->attachments ?? [];

        if (isset($attachments[$index])) {
             return Storage::disk('public')->download($attachments[$index]['path'], $attachments[$index]['name']);
        }

        $this->dispatch('notify', message: 'فایل مورد نظر یافت نشد.', type: 'error');
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.documents', [
            'user' => Auth::user(),
        ]);
    }
}
