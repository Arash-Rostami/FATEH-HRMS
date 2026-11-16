<div wire:key="messaging-pin-banner" class="flex-shrink-0 flex items-center gap-2 px-3 md:px-6 py-2 bg-[var(--md-sys-color-surface)] border-b border-[var(--md-sys-color-outline-variant)]/20">
    <button type="button" x-on:click="scrollToMessage({{ $pin['id'] }})" title="رفتن به پیام سنجاق‌شده" aria-label="رفتن به پیام سنجاق‌شده"
            class="flex-1 min-w-0 flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-start cursor-pointer transition-all duration-150 hover:brightness-105 active:scale-[0.99] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] border-r-[3px] border-r-[var(--md-sys-color-primary)] shadow-sm">
        <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
            <span class="material-symbols-rounded text-[18px] font-fill">push_pin</span>
        </span>
        <span class="min-w-0">
            <span class="flex items-center gap-1.5 mb-0.5 text-[10px] font-bold">
                <span class="truncate">پیام سنجاق‌شده</span>
                <span class="opacity-60">·</span>
                <span class="truncate opacity-80">{{ $pin['sender'] }}</span>
            </span>
            <span dir="auto" class="block text-[11px] truncate opacity-90">{{ $pin['body'] }}</span>
        </span>
    </button>
    <x-ui.modals.close-button size="sm" label="برداشتن سنجاق" close="$wire.$island('messages').toggleGroupPin({{ $pin['id'] }}, true)"/>
</div>
