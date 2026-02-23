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
    public array  = [];

    #[Validate('required_with:customFile|string|max:100', message: [
        'required_with' => 'لطفاً عنوان مدرک سفارشی را وارد کنید.',
    ])]
    public string  = '';

    #[Validate('nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', message: [
        'required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
        'mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
        'max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
    ])]
    public ;

    #[Locked]
    public string  = '';

    #[Locked]
    public string  = '';

    #[Locked]
    public ?string  = null;

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

    public function updated(string ): void
    {
        if (str_starts_with(, 'files.')) {
             = str_replace('files.', '', );
            ->showUploadConfirmation();
        }
    }

    public function removeFile(string ): void
    {
        unset(->files[]);
    }

    public function showUploadConfirmation(string ): void
    {
        ->errorMessage = null;

        ->validateOnly("files.{}");

        ->pendingUploadKey = ;
        ->pendingFileName = ->files[]->getClientOriginalName() ?? 'فایل انتخاب شده';

        // Dispatch reusable confirmation modal
        ->dispatch('open-confirmation',
            title: 'تایید نهایی بارگذاری',
            message: "آیا از صحت فایل «{->pendingFileName}» اطمینان دارید؟",
            method: 'confirmUpload'
        );
    }

    public function showCustomUploadConfirmation(): void
    {
        ->errorMessage = null;

        ->validate([
            'customType' => 'required_with:customFile|string|max:100',
            'customFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'customType.required_with' => 'لطفاً عنوان مدرک سفارشی را وارد کنید.',
            'customFile.required' => 'لطفاً فایل مدرک سفارشی را انتخاب کنید.',
            'customFile.mimes' => 'فرمت فایل باید PDF, JPG, JPEG یا PNG باشد.',
            'customFile.max' => 'حجم فایل نباید بیشتر از 5 مگابایت باشد.',
        ]);

        ->pendingUploadKey = 'custom_upload_pending';
        ->pendingFileName = ->customFile->getClientOriginalName() ?? 'فایل سفارشی';

        // Dispatch reusable confirmation modal
        ->dispatch('open-confirmation',
            title: 'تایید نهایی بارگذاری',
            message: "آیا از صحت فایل سفارشی «{->pendingFileName}» اطمینان دارید؟",
            method: 'confirmUpload'
        );
    }

    public function confirmUpload(): void
    {
        if (->pendingUploadKey === 'custom_upload_pending') {
            ->processCustomUpload();
            return;
        }

        if (->pendingUploadKey) {
            ->processStandardUpload(->pendingUploadKey);
        }
    }

    private function processStandardUpload(string ): void
    {
        try {
             = ->files[];
             = time();
             = ->getClientOriginalExtension();

             = ->profile;

            if (!->exists) {
                ->save();
            }

             = "doc_standard_{}_{}.{}";
             = ->storeAs("profiles/docs/{->id}", , 'public');

             = collect(->attachments ?? []);

            ->attachments =
                ->reject(fn () => str_contains(, "doc_standard_{}_"))
                ->push()
                ->values()
                ->all();

            ->save();

            unset(->files[]);
            ->resetUploadState();
            ->dispatch('notify', message: 'مدرک با موفقیت ثبت نهایی شد.', type: 'success');

        } catch (\Exception ) {
            ->errorMessage = 'خطایی در بارگذاری فایل رخ داد. لطفاً مجدداً تلاش کنید.';
            ->dispatch('notify', message: ->errorMessage, type: 'error');
            ->resetUploadState();
        }
    }

    private function processCustomUpload(): void
    {
        try {
             = time();
             = ->customFile->getClientOriginalExtension();
             = Str::slug(->customType, '-');

            if (empty()) {
                 = 'doc';
            }

             = ->profile;

            if (!->exists) {
                ->save();
            }

             = "doc_custom_{}_{}.{}";
             = ->customFile->storeAs("profiles/docs/{->id}", , 'public');

             = collect(->attachments ?? []);

            ->attachments =
                ->reject(fn () => str_contains(, "doc_custom_{}_"))
                ->push()
                ->values()
                ->all();

            ->save();

            ->reset(['customType', 'customFile']);
            ->resetUploadState();
            ->dispatch('close-modal', name: 'upload-custom-modal');
            ->dispatch('notify', message: 'مدرک سفارشی با موفقیت ثبت نهایی شد.', type: 'success');

        } catch (\Exception ) {
            ->errorMessage = 'خطایی در ذخیره مدرک سفارشی رخ داد.';
            ->dispatch('notify', message: ->errorMessage, type: 'error');
            ->resetUploadState();
        }
    }

    private function resetUploadState(): void
    {
        ->pendingUploadKey = '';
        ->pendingFileName = '';
        // No need to dispatch close-modal as the reusable component handles it
    }

    #[Computed]
    public function parsedAttachments(): \Illuminate\Support\Collection
    {
         = ->profile->attachments ?? [];
         = collect();

        foreach ( as ) {
            if (!is_string()) {
                continue;
            }

             = basename();

            if (preg_match('/doc_(standard|custom)_(.+)__?(\d{10,})\.\w+/', str_replace('__', '_', ), )) {
                 = [1];
                 = [2];
                 = (int) [3];

                ->push([
                    'category' => ,
                    'key' => ,
                    'uploadedTime' => Carbon::createFromTimestamp(, 'Asia/Tehran')->format('Y/m/d H:i'),
                    'path' => ,
                    'url' => Storage::disk('public')->url(),
                    'fileName' => ,
                ]);
            }
        }

        return ;
    }

    public function render()
    {
        return view('livewire.dashboard.tab.profile.documents', [
            'profile' => ->profile,
            'standardTypes' => ->standardTypes,
            'parsedAttachments' => ->parsedAttachments,
        ]);
    }
}
