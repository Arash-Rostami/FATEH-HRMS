<x-dashboard.modal.create
    wire:model="isCreateModalOpen"
    :title="$editingEventId ? 'ویرایش رویداد' : 'رویداد جدید'"
    action="saveEvent"
    :confirm-text="$editingEventId ? 'بروزرسانی تغییرات' : 'ثبت نهایی رویداد'"
    cancel-text="انصراف"
>
    <!-- Title Input -->
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

    <!-- Date & Time -->
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

    <!-- Description -->
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

    <!-- Privacy Toggle -->
    <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm">
        <div class="flex items-center gap-3 text-[var(--md-sys-color-on-surface)]">
            <div class="p-2 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] rounded-xl">
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
</x-dashboard.modal.create>
