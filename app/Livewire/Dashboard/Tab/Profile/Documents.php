<?php

namespace App\Livewire\Dashboard\Tab\Profile;

use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class Documents extends Component
{
    use WithFileUploads;

    public Profile $profile;
    public $files = []; // For standard uploads keyed by type
    public $customType;
    public $customFile;
    public $confirmingDelete = null; // Path to delete

    public $standardTypes = [
        'shenasnameh' => ['label' => 'Shenasnameh', 'icon' => 'fas fa-id-card'],
        'national_id' => ['label' => 'National ID Card', 'icon' => 'fas fa-address-card'],
        'diploma' => ['label' => 'Latest Degree', 'icon' => 'fas fa-graduation-cap'],
        'military_service' => ['label' => 'Military Service', 'icon' => 'fas fa-user-shield'],
        'insurance_record' => ['label' => 'Insurance Records', 'icon' => 'fas fa-file-invoice'],
    ];

    public function mount()
    {
        $this->profile = Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);
    }

    public function uploadStandard($type)
    {
        $this->validate([
            "files.$type" => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $file = $this->files[$type];
        $filename = "doc_{}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profiles/docs/' . $this->profile->id, $filename, 'public');

        $attachments = $this->profile->attachments ?? [];

        // Remove old if exists for this type (standard types are unique)
        $attachments = array_filter($attachments, fn($a) => ($a['type'] ?? '') !== $type);

        $attachments[] = [
            'type' => $type,
            'name' => $this->standardTypes[$type]['label'],
            'path' => $path,
            'uploaded_at' => now()->toDateTimeString(),
            'is_custom' => false,
        ];

        $this->profile->attachments = array_values($attachments);
        $this->profile->save();

        unset($this->files[$type]);
        $this->dispatch('notify', message: 'Document uploaded successfully!', type: 'success');
    }

    public function uploadCustom()
    {
        $this->validate([
            'customType' => 'required|string|max:50',
            'customFile' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $filename = "doc_custom_" . Str::slug($this->customType) . "_" . time() . '.' . $this->customFile->getClientOriginalExtension();
        $path = $this->customFile->storeAs('profiles/docs/' . $this->profile->id, $filename, 'public');

        $attachments = $this->profile->attachments ?? [];
        $attachments[] = [
            'type' => 'custom',
            'name' => $this->customType,
            'path' => $path,
            'uploaded_at' => now()->toDateTimeString(),
            'is_custom' => true,
        ];

        $this->profile->attachments = $attachments;
        $this->profile->save();

        $this->reset(['customType', 'customFile']);
        $this->dispatch('close-modal', name: 'upload-custom-modal'); // Close modal
        $this->dispatch('notify', message: 'Custom document added!', type: 'success');
    }

    public function deleteDocument($path)
    {
        $attachments = $this->profile->attachments ?? [];
        $attachments = array_filter($attachments, fn($a) => $a['path'] !== $path);

        // Optionally delete file from storage
        // Storage::disk('public')->delete($path);

        $this->profile->attachments = array_values($attachments);
        $this->profile->save();

        $this->dispatch('notify', message: 'Document removed.', type: 'info');
    }

    public function getParsedAttachmentsProperty()
    {
        $attachments = $this->profile->attachments ?? [];
        // Handle legacy string paths if any (migration on the fly)
        return collect($attachments)->map(function($item) {
            if (is_string($item)) {
                return [
                    'type' => 'unknown',
                    'name' => basename($item),
                    'path' => $item,
                    'uploaded_at' => null,
                    'is_custom' => false,
                ];
            }
            return $item;
        });
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.documents');
    }
}
