<div class="space-y-8 animate-[fade-in_0.5s_ease-out]" dir="rtl">

    @if($errorMessage)
        <div class="p-4 mb-6 rounded-xl bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] border border-[var(--md-sys-color-error)]/20 flex items-center gap-3">
            <span class="material-symbols-rounded">error</span>
            <p class="font-medium text-sm">{{ $errorMessage }}</p>
        </div>
    @endif

    <div class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border border-[var(--md-sys-color-tertiary)]/20 shadow-sm p-6">
        <div class="absolute -left-6 -top-6 opacity-10 pointer-events-none">
            <span class="material-symbols-rounded text-[120px]">policy</span>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[var(--md-sys-color-tertiary)]/20 pb-4 mb-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-rounded text-3xl">warning</span>
                    <h3 class="text-lg font-bold tracking-tight">توجه بسیار مهم پیش از بارگذاری</h3>
                </div>
                <div class="bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] px-3 py-1 rounded-full text-xs font-bold tracking-wider inline-flex items-center gap-1">
                    <span class="material-symbols-rounded text-[14px]">lock</span>
                    یک‌بار آپلود
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <ul class="space-y-3">
                    <li class="flex items-start gap-2 text-sm">
                        <span class="material-symbols-rounded text-[18px] mt-0.5 opacity-70">check_circle</span>
                        <div>
                            <strong class="font-bold block">عدم امکان حذف یا تغییر:</strong>
                            پس از تأیید نهایی، دسترسی شما برای تغییر یا حذف فایل مسدود خواهد شد. قبل از آپلود فایل را به دقت کنترل کنید.
                        </div>
                    </li>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="material-symbols-rounded text-[18px] mt-0.5 opacity-70">check_circle</span>
                        <div>
                            <strong class="font-bold block">ادغام صفحات:</strong>
                            اگر مدارک چندصفحه‌ای‌اند، پیش از آپلود آن‌ها را در یک فایل واحد ادغام کنید.
                        </div>
                    </li>
                </ul>

                <div class="bg-[var(--md-sys-color-surface)]/40 rounded-xl p-4 border border-[var(--md-sys-color-tertiary)]/10">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-rounded text-[18px]">info</span>
                        <h4 class="font-bold text-sm">راهنمای دریافت سوابق بیمه</h4>
                    </div>
                    <ol class="list-decimal list-inside space-y-1 text-sm opacity-90 pl-2">
                        <li>ورود به سایت <a href="https://account.tamin.ir/auth/login" target="_blank" rel="noopener noreferrer" class="font-bold underline decoration-2 underline-offset-4 hover:text-[var(--md-sys-color-tertiary)] transition-colors">account.tamin.ir</a></li>
                        <li>دانلود فایل «کلیه سوابق بیمه»</li>
                        <li>آپلود فایل در بخش مربوطه در همین صفحه</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <x-dashboard.form.card title="مخزن مدارک و اسناد" description="بارگذاری و مدیریت مدارک پرسنلی.">
        <x-slot name="actions">
            <x-dashboard.form.button x-on:click="$dispatch('open-modal', { name: 'upload-custom-modal' })" icon="add" variant="primary">
                افزودن مدرک سفارشی
            </x-dashboard.form.button>
        </x-slot>

        <div class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($standardTypes as $key => $details)
                    @php
                        $uploadedDoc = $this->parsedAttachments->where('category', 'standard')->firstWhere('key', $key);
                    @endphp

                    <div class="relative flex flex-col rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden transition-all duration-300">

                        <div class="flex items-start justify-between p-5 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-lowest)]">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                    <span class="material-symbols-rounded text-[28px]">{{ $details['icon'] }}</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[var(--md-sys-color-on-surface)]">{{ $details['label'] }}</h4>
                                    @if($uploadedDoc)
                                        <div class="flex items-center gap-1.5 mt-1 text-[var(--md-sys-color-primary)]">
                                            <span class="material-symbols-rounded text-[16px]">lock</span>
                                            <p class="text-xs font-bold">تایید و قفل شده</p>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 mt-1 text-[var(--md-sys-color-on-surface-variant)] opacity-70">
                                            <span class="material-symbols-rounded text-[16px]">pending</span>
                                            <p class="text-xs">در انتظار بارگذاری</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="p-5 flex-grow flex flex-col justify-center bg-[var(--md-sys-color-surface)]">
                            @if($uploadedDoc)
                                <div class="w-full flex flex-col items-center justify-center py-4 bg-[var(--md-sys-color-secondary-container)]/30 rounded-xl border border-[var(--md-sys-color-secondary)]/20">
                                    <span class="material-symbols-rounded text-[40px] text-[var(--md-sys-color-secondary)] mb-2">task</span>
                                    <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-1">فایل با موفقیت ثبت شده است</p>
                                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mb-4" dir="ltr">{{ $uploadedDoc['uploadedTime'] }}</p>

                                    <a href="{{ $uploadedDoc['url'] }}" target="_blank" class="px-6 py-2 rounded-full bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] font-bold text-sm hover:opacity-90 transition-opacity flex items-center gap-2 shadow-sm">
                                        <span class="material-symbols-rounded text-[18px]">download</span>
                                        دریافت و مشاهده مدرک
                                    </a>
                                </div>
                            @else
                                <div x-data="{
                                         fileName: '',
                                         previewUrl: '',
                                         handleFileSelect(event) {
                                             if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                                             const file = event.target.files[0];
                                             if (!file || file.size > 5242880) {
                                                 this.fileName = '';
                                                 this.previewUrl = '';
                                                 event.target.value = null;
                                                 return;
                                             }
                                             this.fileName = file.name;
                                             if (['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'].includes(file.type)) {
                                                 this.previewUrl = URL.createObjectURL(file);
                                             } else {
                                                 this.previewUrl = '';
                                             }
                                         }
                                     }" class="w-full">

                                    <div x-show="!fileName" class="w-full">
                                        <label for="file-{{ $key }}" class="flex flex-col items-center justify-center w-full py-6 border-2 border-dashed border-[var(--md-sys-color-outline)] rounded-xl cursor-pointer bg-[var(--md-sys-color-surface-container-lowest)] hover:bg-[var(--md-sys-color-surface-variant)] hover:border-[var(--md-sys-color-primary)] transition-all">
                                            <span class="material-symbols-rounded text-[var(--md-sys-color-outline)] mb-2 text-[32px]">cloud_upload</span>
                                            <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">انتخاب فایل</span>
                                            <span class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-1">PDF, JPG, PNG (Max: 5MB)</span>
                                        </label>
                                        <input type="file" id="file-{{ $key }}" class="hidden" wire:model="files.{{ $key }}" @change="handleFileSelect($event)" accept=".pdf,.jpg,.jpeg,.png">
                                        <div wire:loading wire:target="files.{{ $key }}" class="w-full text-center mt-2 text-xs text-[var(--md-sys-color-primary)] font-bold animate-pulse">در حال آماده‌سازی فایل...</div>
                                        @error("files.{$key}") <p class="text-xs text-[var(--md-sys-color-error)] mt-2 font-medium text-center">{{ $message }}</p> @enderror
                                    </div>

                                    <div x-show="fileName" x-cloak class="w-full flex flex-col items-center gap-4">
                                        <div class="w-full flex items-center justify-between p-3 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="material-symbols-rounded text-[20px] flex-shrink-0">draft</span>
                                                <span x-text="fileName" class="text-sm font-medium truncate" dir="ltr"></span>
                                            </div>
                                            <button type="button" @click="fileName = ''; previewUrl = ''; $refs['fileInput{{ $key }}'].value = ''" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-error)] transition-colors">
                                                <span class="material-symbols-rounded text-[18px]">close</span>
                                            </button>
                                        </div>

                                        <div class="flex items-center gap-3 w-full">
                                            <a x-show="previewUrl" :href="previewUrl" target="_blank" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] hover:opacity-90 transition-opacity font-bold text-sm">
                                                <span class="material-symbols-rounded text-[20px]">visibility</span>
                                                پیش‌نمایش
                                            </a>
                                            <button type="button" wire:click="showUploadConfirmation('{{ $key }}')" wire:loading.attr="disabled" wire:target="files.{{ $key }}" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90 transition-opacity font-bold text-sm disabled:opacity-50">
                                                <span class="material-symbols-rounded text-[20px]">upload</span>
                                                تایید نهایی
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @php
                $customDocs = $this->parsedAttachments->where('category', 'custom');
            @endphp

            @if($customDocs->isNotEmpty())
                <div class="pt-8 mt-8 border-t border-[var(--md-sys-color-outline-variant)]">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-tertiary)] text-3xl">folder_special</span>
                        <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">مدارک سفارشی ثبت شده</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($customDocs as $doc)
                            <div class="flex items-center justify-between p-4 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-lowest)] shadow-sm">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                        <span class="material-symbols-rounded text-[20px]">lock</span>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h4 class="font-bold text-sm text-[var(--md-sys-color-on-surface)] truncate" title="{{ str_replace('-', ' ', $doc['key']) }}">
                                            {{ ucwords(str_replace('-', ' ', $doc['key'])) }}
                                        </h4>
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5" dir="ltr">{{ $doc['uploadedTime'] }}</p>
                                    </div>
                                </div>
                                <a href="{{ $doc['url'] }}" target="_blank" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-colors">
                                    <span class="material-symbols-rounded text-[18px]">download</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-dashboard.form.card>

    <x-dashboard.form.modal name="upload-custom-modal" title="افزودن مدرک سفارشی">
        <div x-data="{
                 fileName: '',
                 previewUrl: '',
                 handleCustomFileSelect(event) {
                     if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                     const file = event.target.files[0];
                     if (!file || file.size > 5242880) {
                         this.fileName = '';
                         this.previewUrl = '';
                         event.target.value = null;
                         return;
                     }
                     this.fileName = file.name;
                     if (['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'].includes(file.type)) {
                         this.previewUrl = URL.createObjectURL(file);
                     } else {
                         this.previewUrl = '';
                     }
                 }
             }" class="space-y-6">

            <x-dashboard.form.input label="عنوان مدرک (به انگلیسی یا فینگلیش)" name="customType" wire:model.defer="customType" placeholder="Example: Health Certificate" icon="label" />

            <div class="space-y-2 w-full">
                <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">فایل مدرک</label>

                <div x-show="!fileName" class="w-full">
                    <label for="custom-file-upload" class="flex flex-col items-center justify-center w-full py-8 border-2 border-dashed border-[var(--md-sys-color-outline)] rounded-xl cursor-pointer bg-[var(--md-sys-color-surface-container-lowest)] hover:bg-[var(--md-sys-color-surface-variant)] hover:border-[var(--md-sys-color-primary)] transition-all">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-outline)] mb-3 text-[40px]">note_add</span>
                        <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">انتخاب فایل مدرک</span>
                    </label>
                    <input id="custom-file-upload" type="file" class="hidden" wire:model="customFile" @change="handleCustomFileSelect($event)" accept=".pdf,.jpg,.png,.jpeg">
                    <div wire:loading wire:target="customFile" class="w-full text-center mt-2 text-xs text-[var(--md-sys-color-primary)] font-bold animate-pulse">در حال آماده‌سازی فایل...</div>
                </div>

                <div x-show="fileName" x-cloak class="w-full flex flex-col gap-4">
                    <div class="w-full flex items-center justify-between p-4 rounded-xl bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-surface-variant)]">draft</span>
                            <span x-text="fileName" class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate" dir="ltr"></span>
                        </div>
                        <button type="button" @click="fileName = ''; previewUrl = ''; document.getElementById('custom-file-upload').value = ''" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-error)] transition-colors">
                            <span class="material-symbols-rounded text-[18px]">close</span>
                        </button>
                    </div>
                    <a x-show="previewUrl" :href="previewUrl" target="_blank" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-bold text-sm">
                        <span class="material-symbols-rounded text-[20px]">visibility</span>
                        نمایش پیش‌نمایش
                    </a>
                </div>

                @error('customType') <p class="text-xs text-[var(--md-sys-color-error)] mt-2 font-medium">{{ $message }}</p> @enderror
                @error('customFile') <p class="text-xs text-[var(--md-sys-color-error)] mt-2 font-medium">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-[var(--md-sys-color-outline-variant)]">
                <x-dashboard.form.button type="button" variant="ghost" x-on:click="window.dispatchEvent(new CustomEvent('close-modal'))">انصراف</x-dashboard.form.button>
                <x-dashboard.form.button type="button" wire:click="showCustomUploadConfirmation" wire:loading.attr="disabled" wire:target="customFile" icon="upload" variant="primary">مرحله بعد</x-dashboard.form.button>
            </div>
        </div>
    </x-dashboard.form.modal>

    @if($showConfirmDialog)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">

            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm animate-[fade-in_0.2s_ease-out]" aria-hidden="true"></div>

            <div class="relative bg-[var(--md-sys-color-surface)] rounded-2xl shadow-xl border border-[var(--md-sys-color-outline-variant)] w-full max-w-lg overflow-hidden z-[101] animate-[slide-up_0.3s_ease-out]">

                <div class="p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-rounded text-[24px]">verified</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">تایید نهایی بارگذاری</h3>
                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] mt-1">آیا از صحت فایل انتخاب شده اطمینان دارید؟</p>
                        </div>
                    </div>

                    <div class="bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)] rounded-xl p-4 mb-6">
                        <p class="text-sm font-medium text-[var(--md-sys-color-on-surface)] mb-2">نام فایل:</p>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] font-mono truncate" dir="ltr">{{ $pendingFileName }}</p>
                    </div>

                    <div class="flex items-start gap-3 p-4 rounded-xl bg-[var(--md-sys-color-error-container)]/50 border border-[var(--md-sys-color-error)]/20 text-[var(--md-sys-color-on-error-container)]">
                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-error)]">lock</span>
                        <p class="text-xs font-bold leading-relaxed">یادآوری: پس از تایید نهایی امکان حذف یا تغییر این مدرک مسدود خواهد شد.</p>
                    </div>
                </div>

                <div class="bg-[var(--md-sys-color-surface-container)] px-6 py-4 flex items-center justify-end gap-3 border-t border-[var(--md-sys-color-outline-variant)]">
                    <button type="button" wire:click="cancelUpload" class="px-5 py-2.5 rounded-full text-sm font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                        انصراف و بازنگری
                    </button>
                    <button type="button" wire:click="confirmUpload" class="px-5 py-2.5 rounded-full text-sm font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90 transition-opacity flex items-center gap-2">
                        <span class="material-symbols-rounded text-[18px]">cloud_done</span>
                        بله، بارگذاری شود
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
