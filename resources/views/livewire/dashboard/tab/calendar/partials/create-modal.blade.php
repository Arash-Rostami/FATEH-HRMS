<div
    x-show="$wire.isCreateModalOpen"
    x-transition.opacity.duration.300ms
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    style="display: none;"
>
    <div
        x-show="$wire.isCreateModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
        @click.away="$wire.set('isCreateModalOpen', false)"
        class="bg-[var(--md-sys-color-surface)] w-full max-w-md rounded-[28px] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
    >
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)] flex items-center justify-between bg-[var(--md-sys-color-surface-container)]">
            <h2 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">
                {{ $editingEventId ? 'ویرایش رویداد' : 'رویداد جدید' }}
            </h2>
            <button
                wire:click="$set('isCreateModalOpen', false)"
                class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] rounded-full p-2 transition-colors"
            >
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        {{-- Form --}}
        <div class="p-6 space-y-5 overflow-y-auto">
            {{-- Title --}}
            <div class="space-y-1">
                <label class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">عنوان</label>
                <input
                    type="text"
                    wire:model="eventTitle"
                    class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-xl px-4 py-3 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/20 transition-all outline-none"
                    placeholder="مثلاً: جلسه تیم فنی"
                >
                @error('eventTitle') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
            </div>

            {{-- Date & Time --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">تاریخ (شمسی)</label>
                    <input
                        type="text"
                        wire:model="eventDate"
                        placeholder="1403-01-01"
                        class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-xl px-4 py-3 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/20 transition-all outline-none text-center ltr"
                    >
                    @error('eventDate') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">زمان</label>
                    <input
                        type="time"
                        wire:model="eventTime"
                        class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-xl px-4 py-3 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/20 transition-all outline-none text-center"
                    >
                    @error('eventTime') <span class="text-xs text-[var(--md-sys-color-error)]">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Description --}}
            <div class="space-y-1">
                <label class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">توضیحات</label>
                <textarea
                    wire:model="eventDescription"
                    rows="3"
                    class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-xl px-4 py-3 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/20 transition-all outline-none resize-none"
                    placeholder="توضیحات تکمیلی..."
                ></textarea>
            </div>

            {{-- Private Toggle --}}
            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-container-low)] p-3 rounded-xl">
                <div class="flex items-center gap-2 text-[var(--md-sys-color-on-surface)]">
                    <span class="material-symbols-rounded">lock</span>
                    <span class="text-sm font-medium">رویداد خصوصی</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="eventPrivate" class="sr-only peer">
                    <div class="w-11 h-6 bg-[var(--md-sys-color-outline-variant)] peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[var(--md-sys-color-primary)]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--md-sys-color-primary)]"></div>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="p-6 pt-2 flex justify-end gap-3">
            <button
                wire:click="$set('isCreateModalOpen', false)"
                class="px-6 py-2.5 rounded-xl text-[var(--md-sys-color-primary)] font-medium hover:bg-[var(--md-sys-color-primary-container)] transition-colors"
            >
                انصراف
            </button>
            <button
                wire:click="saveEvent"
                class="px-6 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold shadow-lg shadow-[var(--md-sys-color-primary)]/30 hover:scale-105 active:scale-95 transition-all"
            >
                {{ $editingEventId ? 'بروزرسانی' : 'ثبت رویداد' }}
            </button>
        </div>
    </div>
</div>
