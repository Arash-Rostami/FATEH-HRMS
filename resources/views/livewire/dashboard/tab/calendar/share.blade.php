<x-ui.modals.action
    wire:model="isShareModalOpen"
    title="اشتراک‌گذاری رویداد"
    action="shareEvent"
    confirm-text="اشتراک‌گذاری"
    cancel-text="انصراف"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl"
         x-data="{ ready: false }"
         x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
         x-show="ready">

        @php $event = $this->sharingEvent; @endphp

        @if($event)
            <div wire:key="calendar-share-summary" class="flex items-center gap-3 bg-[var(--md-sys-color-secondary-container)]/40 rounded-2xl p-4 border border-[var(--md-sys-color-outline-variant)]/30 mb-5">
                <span class="material-symbols-rounded text-[var(--md-sys-color-secondary)]">share</span>
                <div class="min-w-0">
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mb-0.5">رویداد</p>
                    <p class="font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $event->title }}</p>
                </div>
            </div>
        @endif

        <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">همکاران</label>

        <x-dashboard.member-picker wire:key="calendar-share-users" model="shareRecipientIds" :candidates="$this->availableUsers" nameKey="full_name" height="max-h-64" emptyTitle="همکار دیگری برای اشتراک‌گذاری موجود نیست."/>

        @error('share')
        <p class="mt-3 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
        @enderror
    </div>
</x-ui.modals.action>