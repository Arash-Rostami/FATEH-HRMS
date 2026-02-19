<div
    x-show="$wire.isCreateModalOpen"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4"
    style="display: none;"
>
    <div
        x-show="$wire.isCreateModalOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$wire.set('isCreateModalOpen', false)"
        class="absolute inset-0 bg-black/40 backdrop-blur-md"
    ></div>

    <div
        x-show="$wire.isCreateModalOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        class="bg-[var(--md-sys-color-surface)] w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh] relative z-10 border border-[var(--md-sys-color-outline-variant)]/20"
    >
        <div class="px-8 py-6 bg-[var(--md-sys-color-surface-container)] flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] rounded-xl">
                    <span class="material-symbols-rounded">{{ $editingEventId ? 'edit_calendar' : 'add_circle' }}</span>
                </div>
                <h2 class="text-xl font-black text-[var(--md-sys-color-on-surface)]">
                    {{ $editingEventId ? 'ویرایش رویداد' : 'رویداد جدید' }}
                </h2>
            </div>
            <button
                wire:click="$set('isCreateModalOpen', false)"
                class="w-10 h-10 flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] rounded-full transition-colors"
            >
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <div class="p-8 space-y-6 overflow-y-auto custom-scrollbar">

            <div class="group relative">
                <input
                    type="text"
                    wire:model="eventTitle"
                    placeholder=" "
                    class="peer block w-full rounded-t-xl border-b-2 border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-highest)] px-4 pb-3 pt-6 text-[var(--md-sys-color-on-surface)] focus:border-[var(--md-sys-color-primary)] focus:outline-none transition-colors"
                >
                <label class="pointer-events-none absolute right-4 top-4 origin-[100%_0] -translate-y-3 scale-75 text-sm text-[var(--md-sys-color-on-surface-variant)] transition-all peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-3 peer-focus:scale-75 peer-focus:text-[var(--md-sys-color-primary)]">
                    عنوان رویداد
                </label>
                @error('eventTitle') <span class="text-xs text-[var(--md-sys-color-error)] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="group relative">
                    <input
                        type="text"
                        wire:model="eventDate"
                        placeholder=" "
                        class="peer block w-full rounded-t-xl border-b-2 border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-highest)] px-4 pb-3 pt-6 text-[var(--md-sys-color-on-surface)] text-center ltr focus:border-[var(--md-sys-color-primary)] focus:outline-none transition-colors"
                    >
                    <label class="pointer-events-none absolute right-4 top-4 origin-[100%_0] -translate-y-3 scale-75 text-sm text-[var(--md-sys-color-on-surface-variant)] transition-all peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-3 peer-focus:scale-75 peer-focus:text-[var(--md-sys-color-primary)]">
                        تاریخ
                    </label>
                    @error('eventDate') <span class="text-xs text-[var(--md-sys-color-error)] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="group relative">
                    <input
                        type="time"
                        wire:model="eventTime"
                        class="peer block w-full rounded-t-xl border-b-2 border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-highest)] px-4 pb-3 pt-6 text-[var(--md-sys-color-on-surface)] text-center focus:border-[var(--md-sys-color-primary)] focus:outline-none transition-colors"
                    >
                    <label class="pointer-events-none absolute right-4 top-4 origin-[100%_0] -translate-y-3 scale-75 text-sm text-[var(--md-sys-color-on-surface-variant)] transition-all peer-focus:text-[var(--md-sys-color-primary)]">
                        زمان
                    </label>
                    @error('eventTime') <span class="text-xs text-[var(--md-sys-color-error)] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="group relative">
                <textarea
                    wire:model="eventDescription"
                    rows="3"
                    placeholder=" "
                    class="peer block w-full rounded-t-xl border-b-2 border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-highest)] px-4 pb-3 pt-6 text-[var(--md-sys-color-on-surface)] focus:border-[var(--md-sys-color-primary)] focus:outline-none transition-colors resize-none"
                ></textarea>
                <label class="pointer-events-none absolute right-4 top-4 origin-[100%_0] -translate-y-3 scale-75 text-sm text-[var(--md-sys-color-on-surface-variant)] transition-all peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-3 peer-focus:scale-75 peer-focus:text-[var(--md-sys-color-primary)]">
                    توضیحات تکمیلی
                </label>
            </div>

            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20">
                <div class="flex items-center gap-3 text-[var(--md-sys-color-on-surface)]">
                    <div class="p-2 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] rounded-lg">
                        <span class="material-symbols-rounded">lock</span>
                    </div>
                    <div>
                        <p class="font-bold text-sm">حریم خصوصی</p>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">نمایش فقط برای خودم</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="eventPrivate" class="sr-only peer">
                    <div class="w-12 h-7 bg-[var(--md-sys-color-surface-variant)] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--md-sys-color-primary)] shadow-inner"></div>
                </label>
            </div>
        </div>

        <div class="p-6 border-t border-[var(--md-sys-color-outline-variant)]/20 flex justify-end gap-3 bg-[var(--md-sys-color-surface-container)]/50">
            <button
                wire:click="$set('isCreateModalOpen', false)"
                class="px-6 py-3 rounded-xl text-[var(--md-sys-color-primary)] font-bold hover:bg-[var(--md-sys-color-surface-variant)] transition-colors"
            >
                انصراف
            </button>
            <button
                wire:click="saveEvent"
                class="px-8 py-3 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold shadow-lg shadow-[var(--md-sys-color-primary)]/30 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all"
            >
                {{ $editingEventId ? 'بروزرسانی تغییرات' : 'ثبت نهایی رویداد' }}
            </button>
        </div>
    </div>
</div>
