<div class="space-y-6">
    <!-- Header/Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-panel p-6 rounded-2xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                <span class="material-symbols-rounded text-2xl">folder_open</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">{{ count($user->profile->attachments ?? []) }}</h3>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">مدارک بارگذاری شده</p>
            </div>
        </div>

        <div class="md:col-span-2 glass-panel p-6 rounded-2xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">بارگذاری مدارک</h3>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">مدارک خود را جهت تکمیل پرونده پرسنلی بارگذاری نمایید.</p>
            </div>
            <x-dashboard.form.button type="button" icon="add" variant="primary" x-on:click="$dispatch('open-modal', { name: 'upload-custom-modal' })">
                مدرک جدید
            </x-dashboard.form.button>
        </div>
    </div>

    <!-- Documents Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($user->profile->attachments ?? [] as $index => $doc)
            <div class="group relative bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] rounded-2xl p-4 transition-all duration-300 hover:shadow-lg">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center text-[var(--md-sys-color-primary)]">
                        <span class="material-symbols-rounded text-2xl">description</span>
                    </div>
                    <span class="text-xs font-mono text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-variant)] px-2 py-1 rounded-lg">
                        {{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('Y/m/d') }}
                    </span>
                </div>

                <h4 class="font-bold text-[var(--md-sys-color-on-surface)] mb-1 truncate" title="{{ $doc['type'] }}">{{ $doc['type'] }}</h4>
                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] truncate mb-4" title="{{ $doc['name'] }}">{{ $doc['name'] }}</p>

                <div class="flex items-center gap-2 mt-auto">
                    <button wire:click="downloadDocument({{ $index }})" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-sm font-medium hover:brightness-95 transition-all">
                        <span class="material-symbols-rounded text-base">download</span>
                        دانلود
                    </button>
                    <div class="w-8 h-8 flex items-center justify-center rounded-xl bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400" title="تایید شده">
                         <span class="material-symbols-rounded text-base">check_circle</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-center text-[var(--md-sys-color-on-surface-variant)] opacity-60">
                <span class="material-symbols-rounded text-6xl mb-4">folder_off</span>
                <p>هیچ مدرکی بارگذاری نشده است.</p>
            </div>
        @endforelse
    </div>

    <!-- Upload Modal -->
    <x-dashboard.form.modal name="upload-custom-modal" title="افزودن مدرک جدید">
        <form wire:submit="uploadCustom" class="space-y-6">
            <x-dashboard.form.input label="عنوان مدرک" name="customType" wire:model="customType" placeholder="مثال: کارت ملی، شناسنامه" icon="label" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] ml-1">انتخاب فایل</label>
                <div class="flex items-center justify-center w-full">
                    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-[var(--md-sys-color-outline)] border-dashed rounded-xl cursor-pointer bg-[var(--md-sys-color-surface-variant)]/30 hover:bg-[var(--md-sys-color-surface-variant)] hover:border-[var(--md-sys-color-primary)] transition-all">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-outline)] mb-3">cloud_upload</span>
                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]"><span class="font-semibold">برای انتخاب فایل کلیک کنید</span></p>
                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-1">PDF, JPG, PNG (حداکثر 5 مگابایت)</p>
                        </div>
                        <input id="dropzone-file" type="file" wire:model="customFile" class="hidden" accept=".pdf,.jpg,.png,.jpeg" />
                    </label>
                </div>
                @error('customFile') <p class="text-xs text-[var(--md-sys-color-error)] mt-1 animate-pulse">{{ $message }}</p> @enderror
                @if($customFile)
                    <p class="text-xs text-[var(--md-sys-color-primary)] mt-1 flex items-center gap-1">
                        <span class="material-symbols-rounded text-sm">check</span>
                        {{ $customFile->getClientOriginalName() }}
                    </p>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-[var(--md-sys-color-outline-variant)]">
                <x-dashboard.form.button type="button" variant="ghost" x-on:click="$dispatch('close-modal', { name: 'upload-custom-modal' })">انصراف</x-dashboard.form.button>
                <x-dashboard.form.button type="submit" loading="uploadCustom" icon="upload" variant="primary">بارگذاری مدرک</x-dashboard.form.button>
            </div>
        </form>
    </x-dashboard.form.modal>
</div>
