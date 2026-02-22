<div>
    <x-dashboard.form.card title="مخزن مدارک و اسناد" description="بارگذاری و مدیریت مدارک پرسنلی.">
        <x-slot name="actions">
            <x-dashboard.form.button x-on:click="$dispatch('open-modal', { name: 'upload-custom-modal' })" icon="add" variant="primary">
                افزودن مدرک سفارشی
            </x-dashboard.form.button>
        </x-slot>

        <div class="space-y-8">
            <!-- Standard Documents -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($standardTypes as $type => $details)
                    @php
                        $uploadedDoc = collect($this->parsedAttachments)->firstWhere('type', $type);
                    @endphp
                    <div class="relative group rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]/50 p-5 hover:bg-[var(--md-sys-color-surface)] hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="p-3 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-inner">
                                    <span class="material-symbols-rounded text-2xl">{{ str_replace('fas fa-', '', $details['icon']) }}</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[var(--md-sys-color-on-surface)]">{{ $details['label'] }}</h4>
                                    @if($uploadedDoc)
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="flex h-2 w-2 relative">
                                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                              <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                            </span>
                                            <p class="text-xs text-green-600 font-medium">
                                                بارگذاری شده {{ \Carbon\Carbon::parse($uploadedDoc['uploaded_at'])->diffForHumans() }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--md-sys-color-outline)]"></span>
                                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">هنوز بارگذاری نشده</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if($uploadedDoc)
                                <div class="flex gap-2">
                                    <a href="{{ Storage::url($uploadedDoc['path']) }}" target="_blank" class="text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors p-2 hover:bg-[var(--md-sys-color-primary-container)] rounded-lg">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                    <button wire:click="deleteDocument('{{ $uploadedDoc['path'] }}')" wire:confirm="آیا از حذف این مدرک اطمینان دارید؟" class="text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)] transition-colors p-2 hover:bg-[var(--md-sys-color-error-container)] rounded-lg">
                                        <span class="material-symbols-rounded">delete</span>
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if(!$uploadedDoc)
                            <div class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]">
                                <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-[var(--md-sys-color-outline)] rounded-lg cursor-pointer bg-[var(--md-sys-color-surface-variant)]/30 hover:bg-[var(--md-sys-color-surface-variant)] hover:border-[var(--md-sys-color-primary)] transition-all">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <span class="material-symbols-rounded text-[var(--md-sys-color-outline)] mb-2">cloud_upload</span>
                                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]"><span class="font-semibold">برای آپلود کلیک کنید</span></p>
                                    </div>
                                    <input type="file" class="hidden" wire:model="files.{{ $type }}" wire:change="uploadStandard('{{ $type }}')" accept=".pdf,.jpg,.png,.jpeg">
                                </label>
                                @error("files.$type") <p class="text-xs text-[var(--md-sys-color-error)] mt-1 animate-pulse">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Custom Documents -->
            @if(collect($this->parsedAttachments)->where('is_custom', true)->isNotEmpty())
                <div class="pt-8 border-t border-[var(--md-sys-color-outline-variant)]">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-tertiary)] text-2xl">folder_special</span>
                        <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">مدارک سفارشی</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach(collect($this->parsedAttachments)->where('is_custom', true) as $doc)
                            <div class="relative group rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]/50 p-5 hover:bg-[var(--md-sys-color-surface)] hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] shadow-inner">
                                            <span class="material-symbols-rounded text-2xl">description</span>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-[var(--md-sys-color-on-surface)] capitalize">{{ $doc['name'] }}</h4>
                                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-1">
                                                بارگذاری شده {{ \Carbon\Carbon::parse($doc['uploaded_at'])->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors p-2 hover:bg-[var(--md-sys-color-primary-container)] rounded-lg">
                                            <span class="material-symbols-rounded">visibility</span>
                                        </a>
                                        <button wire:click="deleteDocument('{{ $doc['path'] }}')" wire:confirm="آیا از حذف این مدرک اطمینان دارید؟" class="text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)] transition-colors p-2 hover:bg-[var(--md-sys-color-error-container)] rounded-lg">
                                            <span class="material-symbols-rounded">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-dashboard.form.card>

    <!-- Upload Custom Modal -->
    <x-dashboard.form.modal name="upload-custom-modal" title="افزودن مدرک جدید">
        <form wire:submit="uploadCustom" class="space-y-6">
            <x-dashboard.form.input label="عنوان مدرک" name="customType" wire:model="customType" placeholder="مثال: کارت باشگاه ورزشی" icon="label" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] ml-1">انتخاب فایل</label>
                <div class="flex items-center justify-center w-full">
                    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-[var(--md-sys-color-outline)] border-dashed rounded-xl cursor-pointer bg-[var(--md-sys-color-surface-variant)]/30 hover:bg-[var(--md-sys-color-surface-variant)] hover:border-[var(--md-sys-color-primary)] transition-all">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-outline)] mb-3">cloud_upload</span>
                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]"><span class="font-semibold">برای انتخاب فایل کلیک کنید</span></p>
                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-1">PDF, PNG, JPG (حداکثر 5 مگابایت)</p>
                        </div>
                        <input id="dropzone-file" type="file" wire:model="customFile" class="hidden" accept=".pdf,.jpg,.png,.jpeg" />
                    </label>
                </div>
                @error('customFile') <p class="text-xs text-[var(--md-sys-color-error)] mt-1 animate-pulse">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-[var(--md-sys-color-outline-variant)]">
                <x-dashboard.form.button type="button" variant="ghost" x-on:click="$dispatch('close-modal', { name: 'upload-custom-modal' })">انصراف</x-dashboard.form.button>
                <x-dashboard.form.button type="submit" loading="uploadCustom" icon="upload" variant="primary">بارگذاری مدرک</x-dashboard.form.button>
            </div>
        </form>
    </x-dashboard.form.modal>
</div>
