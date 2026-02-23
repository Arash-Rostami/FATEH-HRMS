<?php

namespace App\Livewire\Dashboard\Tab;

use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Documents extends Component
{
    use WithFileUploads;

    public Profile $profile;
    public array $files = [];
    public string $customType = '';
    public $customFile;

    public bool $showConfirmDialog = false;
    public string $pendingUploadKey = '';
    public string $pendingFileName = '';
    public ?string $errorMessage = null;

    public array $standardTypes = [
        'shenasnameh' => ['label' => 'تمام صفحات شناسنامه', 'icon' => 'badge'],
        'national_id' => ['label' => 'پشت و روی کارت ملی', 'icon' => 'id_card'],
        'diploma' => ['label' => 'آخرین مدرک تحصیلی', 'icon' => 'school'],
        'military_service' => ['label' => 'کارت پایان خدمت یا معافیت', 'icon' => 'military_tech'],
        'insurance_record' => ['label' => 'کلیه سوابق بیمه', 'icon' => 'receipt_long'],
    ];

    protected function rules(): array
    {
        return [
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'customFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'customType' => 'required_with:customFile|string|max:100',
        ];
    }

    protected function messages(): array
    {
        return [
            'files.*.required' => 'لطفاً یک فایل انتخاب کنید.',
            'files.*.file' => 'یک فایل معتبر بارگذاری کنید.',
            'files.*.mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
            'files.*.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
            'customFile.required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
            'customFile.mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
            'customFile.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
            'customType.required_with' => 'لطفاً عنوان مدرک سفارشی را وارد کنید.',
        ];
    }

    public function mount(): void
    {
        $this->profile = Auth::user()->profile ?? new Profile(['user_id' => Auth::id()]);
    }

    public function showUploadConfirmation(string $key): void
    {
        $this->errorMessage = null;

        $this->validate(["files.{$key}" => $this->rules()['files.*']]);

        $this->pendingUploadKey = $key;
        $this->pendingFileName = $this->files[$key]->getClientOriginalName() ?? 'فایل انتخاب شده';
        $this->showConfirmDialog = true;
    }

    public function showCustomUploadConfirmation(): void
    {
        $this->errorMessage = null;

        $this->validate([
            'customType' => $this->rules()['customType'],
            'customFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $this->pendingUploadKey = 'custom_upload_pending';
        $this->pendingFileName = $this->customFile->getClientOriginalName() ?? 'فایل سفارشی';
        $this->showConfirmDialog = true;
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

    public function cancelUpload(): void
    {
        $this->resetConfirmDialog();
    }

    private function processStandardUpload(string $key): void
    {
        try {
            $uploadedFile = $this->files[$key];
            $timestamp = time();
            $extension = $uploadedFile->getClientOriginalExtension();

            $fileName = "doc_standard_{$key}_{$timestamp}.{$extension}";
            $newPath = $uploadedFile->storeAs("profiles/docs/{$this->profile->id}", $fileName, 'public');

            $currentAttachments = collect($this->profile->attachments ?? []);

            $this->profile->attachments = $currentAttachments
                ->reject(fn ($path) => str_contains($path, "doc_standard_{$key}_"))
                ->push($newPath)
                ->values()
                ->all();

            $this->profile->save();

            unset($this->files[$key]);
            $this->resetConfirmDialog();
            $this->dispatch('notify', message: 'مدرک با موفقیت ثبت نهایی شد.', type: 'success');

        } catch (\Exception $e) {
            $this->errorMessage = 'خطایی در بارگذاری فایل رخ داد. لطفاً مجدداً تلاش کنید.';
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
            $this->resetConfirmDialog();
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

            $fileName = "doc_custom_{$slug}_{$timestamp}.{$extension}";
            $newPath = $this->customFile->storeAs("profiles/docs/{$this->profile->id}", $fileName, 'public');

            $currentAttachments = collect($this->profile->attachments ?? []);

            $this->profile->attachments = $currentAttachments
                ->reject(fn ($path) => str_contains($path, "doc_custom_{$slug}_"))
                ->push($newPath)
                ->values()
                ->all();

            $this->profile->save();

            $this->reset(['customType', 'customFile']);
            $this->resetConfirmDialog();
            $this->dispatch('close-modal', name: 'upload-custom-modal');
            $this->dispatch('notify', message: 'مدرک سفارشی با موفقیت ثبت نهایی شد.', type: 'success');

        } catch (\Exception $e) {
            $this->errorMessage = 'خطایی در ذخیره مدرک سفارشی رخ داد.';
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
            $this->resetConfirmDialog();
        }
    }

    private function resetConfirmDialog(): void
    {
        $this->showConfirmDialog = false;
        $this->pendingUploadKey = '';
        $this->pendingFileName = '';
    }

    public function getParsedAttachmentsProperty(): \Illuminate\Support\Collection
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
        return view('livewire.dashboard.tab.profile.documents');
    }
}
