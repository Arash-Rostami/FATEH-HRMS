<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Documents extends Component
{
    use WithFileUploads;

    #[Validate([
        'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    ], message: [
        'files.*.required' => 'لطفاً یک فایل انتخاب کنید.',
        'files.*.file' => 'یک فایل معتبر بارگذاری کنید.',
        'files.*.mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
        'files.*.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
    ])]
    public array $files = [];

    #[Validate('required_with:customFile|string|max:100', message: [
        'required_with' => 'لطفاً عنوان مدرک سفارشی را وارد کنید.',
    ])]
    public string $customType = '';

    #[Validate('nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', message: [
        'required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
        'mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
        'max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
    ])]
    public $customFile;

    #[Locked]
    public string $pendingUploadKey = '';

    #[Locked]
    public string $pendingFileName = '';

    #[Locked]
    public ?string $errorMessage = null;

    #[Computed]
    public function profile(): Profile
    {
        return Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);
    }

    #[Computed]
    public function standardTypes(): array
    {
        return [
            'shenasnameh' => ['label' => 'تمام صفحات شناسنامه', 'icon' => 'badge'],
            'national_id' => ['label' => 'پشت و روی کارت ملی', 'icon' => 'id_card'],
            'diploma' => ['label' => 'آخرین مدرک تحصیلی', 'icon' => 'school'],
            'military_service' => ['label' => 'کارت پایان خدمت یا معافیت', 'icon' => 'military_tech'],
            'insurance_record' => ['label' => 'کلیه سوابق بیمه', 'icon' => 'receipt_long'],
        ];
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'files.')) {
            $key = str_replace('files.', '', $property);
            $this->showUploadConfirmation($key);
        }
    }

    public function removeFile(string $key): void
    {
        unset($this->files[$key]);
    }

    public function showUploadConfirmation(string $key): void
    {
        $this->errorMessage = null;

        $this->validateOnly("files.{$key}");

        $this->pendingUploadKey = $key;
        $this->pendingFileName = $this->files[$key]->getClientOriginalName() ?? 'فایل انتخاب شده';

        // Dispatch reusable confirmation modal
        $this->dispatch('open-confirmation',
            title: 'تایید نهایی بارگذاری',
            message: "آیا از صحت فایل «{$this->pendingFileName}» اطمینان دارید؟",
            method: 'confirmUpload'
        );
    }

    public function showCustomUploadConfirmation(): void
    {
        $this->errorMessage = null;

        $this->validate([
            'customType' => 'required_with:customFile|string|max:100',
            'customFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'customType.required_with' => 'لطفاً عنوان مدرک سفارشی را وارد کنید.',
            'customFile.required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
            'customFile.mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
            'customFile.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
        ]);

        $this->pendingUploadKey = 'custom_upload_pending';
        $this->pendingFileName = $this->customFile->getClientOriginalName() ?? 'فایل سفارشی';

        // Dispatch reusable confirmation modal
        $this->dispatch('open-confirmation',
            title: 'تایید نهایی بارگذاری',
            message: "آیا از صحت فایل سفارشی «{$this->pendingFileName}» اطمینان دارید؟",
            method: 'confirmUpload'
        );
    }

    public function confirmUpload(): void
    {
        if ($this->pendingUploadKey === 'custom_upload_pending') {
            $this->processCustomUpload();
            return;
        }

        if ($this->pendingUploadKey) {
            $this->processStandardUpload($this->pendingUploadKey);
        }
    }

    private function processStandardUpload(string $key): void
    {
        try {
            $uploadedFile = $this->files[$key];
            $timestamp = time();
            $extension = $uploadedFile->getClientOriginalExtension();

            $userProfile = $this->profile;

            if (!$userProfile->exists) {
                $userProfile->save();
            }

            $fileName = "doc_standard_{$key}_{$timestamp}.{$extension}";
            $newPath = $uploadedFile->storeAs("profiles/docs/{$userProfile->id}", $fileName, 'public');

            $currentAttachments = collect($userProfile->attachments ?? []);

            $userProfile->attachments = $currentAttachments
                ->reject(fn ($path) => str_contains($path, "doc_standard_{$key}_"))
                ->push($newPath)
                ->values()
                ->all();

            $userProfile->save();

            unset($this->files[$key]);
            $this->resetUploadState();
            $this->dispatch('notify', message: 'مدرک با موفقیت ثبت نهایی شد.', type: 'success');

        } catch (\Exception $e) {
            $this->errorMessage = 'خطایی در بارگذاری فایل رخ داد. لطفاً مجدداً تلاش کنید.';
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
            $this->resetUploadState();
        }
    }

    private function processCustomUpload(): void
    {
        try {
            $timestamp = time();
            $extension = $this->customFile->getClientOriginalExtension();
            $slug = Str::slug($this->customType, '-');

            if (empty($slug)) {
                $slug = 'doc';
            }

            $userProfile = $this->profile;

            if (!$userProfile->exists) {
                $userProfile->save();
            }

            $fileName = "doc_custom_{$slug}_{$timestamp}.{$extension}";
            $newPath = $this->customFile->storeAs("profiles/docs/{$userProfile->id}", $fileName, 'public');

            $currentAttachments = collect($userProfile->attachments ?? []);

            $userProfile->attachments = $currentAttachments
                ->reject(fn ($path) => str_contains($path, "doc_custom_{$slug}_"))
                ->push($newPath)
                ->values()
                ->all();

            $userProfile->save();

            $this->reset(['customType', 'customFile']);
            $this->resetUploadState();
            $this->dispatch('close-modal', name: 'upload-custom-modal');
            $this->dispatch('notify', message: 'مدرک سفارشی با موفقیت ثبت نهایی شد.', type: 'success');

        } catch (\Exception $e) {
            $this->errorMessage = 'خطایی در ذخیره مدرک سفارشی رخ داد.';
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
            $this->resetUploadState();
        }
    }

    private function resetUploadState(): void
    {
        $this->pendingUploadKey = '';
        $this->pendingFileName = '';
        // No need to dispatch close-modal as the reusable component handles it
    }

    #[Computed]
    public function parsedAttachments(): \Illuminate\Support\Collection
    {
        $allPaths = $this->profile->attachments ?? [];
        $parsed = collect();

        foreach ($allPaths as $path) {
            if (!is_string($path)) {
                continue;
            }

            $fileName = basename($path);

            if (preg_match('/doc_(standard|custom)_(.+)__?(\d{10,})\.\w+/', str_replace('__', '_', $fileName), $matches)) {
                $category = $matches[1];
                $keyOrSlug = $matches[2];
                $timestamp = (int) $matches[3];

                $parsed->push([
                    'category' => $category,
                    'key' => $keyOrSlug,
                    'uploadedTime' => Carbon::createFromTimestamp($timestamp, 'Asia/Tehran')->format('Y/m/d H:i'),
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                    'fileName' => $fileName,
                ]);
            }
        }

        return $parsed;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.documents', [
            'profile' => $this->profile,
            'standardTypes' => $this->standardTypes,
            'parsedAttachments' => $this->parsedAttachments,
        ]);
    }
}
