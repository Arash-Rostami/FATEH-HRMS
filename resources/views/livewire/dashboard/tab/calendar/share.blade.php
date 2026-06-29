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
            <div class="flex items-center gap-3 bg-[var(--md-sys-color-secondary-container)]/40 rounded-2xl p-4 border border-[var(--md-sys-color-outline-variant)]/30 mb-5">
                <span class="material-symbols-rounded text-[var(--md-sys-color-secondary)]">share</span>
                <div class="min-w-0">
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mb-0.5">رویداد</p>
                    <p class="font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $event->title }}</p>
                </div>
            </div>
        @endif

        <label class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">همکاران</label>

        @if(count($this->availableUsers) > 0)
            <div class="max-h-64 overflow-y-auto custom-scrollbar rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 divide-y divide-[var(--md-sys-color-outline-variant)]/20">
                @foreach($this->availableUsers as $user)
                    <label class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-surface-container)]/60 cursor-pointer transition-colors">
                        <input
                            type="checkbox"
                            value="{{ $user['id'] }}"
                            wire:model="shareRecipientIds"
                            class="w-4 h-4 rounded text-[var(--md-sys-color-primary)] border-[var(--md-sys-color-outline-variant)] focus:ring-[var(--md-sys-color-primary)]"
                        >
                        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface)]">{{ $user['full_name'] }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-4xl mb-2 block opacity-40">group</span>
                <p class="text-sm">همکار دیگری برای اشتراک‌گذاری موجود نیست.</p>
            </div>
        @endif

        @error('share')
        <p class="mt-3 text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
        @enderror
    </div>
</x-ui.modals.action>