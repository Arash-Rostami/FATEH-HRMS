<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Department;
use App\Models\Profile as ProfileModel;
use App\Traits\HasDashboardTabs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Url;

class Profile extends Component
{
    use HasDashboardTabs, WithFileUploads;

    public $activeTab = 'profile';

    #[Url(as: 'view')]
    public $activeSubTab = 'info';

    public $profileData = [];
    public $search = '';
    public $customFile;
    public $customType;

    public function rules()
    {
        return [
            'profileData.personnel_id' => 'nullable|string|max:50',
            'profileData.gender' => 'nullable|in:male,female',
            'profileData.marital_status' => 'nullable|in:single,married',
            'profileData.number_of_children' => 'nullable|integer|min:0',
            'profileData.id_card_number' => 'nullable|string|max:20',
            'profileData.id_booklet_number' => 'nullable|string|max:20',
            'profileData.birthdate' => 'nullable|date',
            'profileData.department_id' => 'nullable|string|exists:departments,code',
            'profileData.position' => 'nullable|string|max:100',
            'profileData.employment_type' => 'nullable|in:fulltime,parttime,contract',
            'profileData.employment_status' => 'nullable|in:probational,working,terminated',
            'profileData.insurance' => 'nullable|string|max:50',
            'profileData.work_experience' => 'nullable|integer|min:0',
            'profileData.cellphone' => 'nullable|string|max:20',
            'profileData.landline' => 'nullable|string|max:20',
            'profileData.emergency_phone' => 'nullable|string|max:20',
            'profileData.emergency_relationship' => 'nullable|string|max:50',
            'profileData.zip_code' => 'nullable|string|max:20',
            'profileData.address' => 'nullable|string|max:500',
            'profileData.degree' => 'nullable|in:undergraduate,graduate,postgraduate',
            'profileData.field' => 'nullable|string|max:100',
            'profileData.interests' => 'nullable|string|max:500',
            'profileData.accessibility' => 'nullable|string|max:500',
            'customFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'customType' => 'nullable|string|max:100',
        ];
    }

    public function mount()
    {
        $user = Auth::user();

        if (!$user->profile) {
            $user->profile()->create();
            $user->load('profile');
        }

        $this->profileData = $user->profile->toArray();
    }

    public function updatedCustomFile()
    {
        $this->validate([
            'customFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
    }

    public function save()
    {
        // Only validate profileData fields here to avoid validating empty customFile
        $validated = $this->validate(collect($this->rules())->filter(fn($v, $k) => str_starts_with($k, 'profileData'))->toArray());

        Auth::user()->profile->update($validated['profileData']);

        $this->dispatch('notify', message: 'اطلاعات پروفایل با موفقیت ذخیره شد.', type: 'success');
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

    public function setTab($tabId)
    {
        return redirect()->route('dashboard', ['tab' => $tabId]);
    }

    public function setSubTab($subTab)
    {
        $this->activeSubTab = $subTab;
    }

    public function getCredentialsProperty()
    {
        return Auth::user()->credentials()
            ->where(function($query) {
                $query->where('app_name', 'like', '%' . $this->search . '%')
                      ->orWhere('username', 'like', '%' . $this->search . '%');
            })
            ->get();
    }

    public function render()
    {
        $departments = Department::pluck('name', 'code')->toArray();

        return view('livewire.dashboard.tab.profile.index', [
            'user' => Auth::user(),
            'departments' => $departments,
            'credentials' => $this->getCredentialsProperty(),
            'completion' => $this->calculateCompletion(),
        ])->extends('layouts.app')->section('content');
    }

    private function calculateCompletion(): int
    {
        $profile = Auth::user()->profile;
        if (!$profile) return 0;

        $fields = [
            'personnel_id', 'gender', 'employment_type', 'marital_status',
            'id_card_number', 'degree', 'field', 'birthdate',
            'cellphone', 'address', 'department_id', 'position',
            'insurance', 'emergency_phone', 'start_date'
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($profile->$field)) {
                $filled++;
            }
        }

        return (int)round(($filled / count($fields)) * 100);
    }
}
