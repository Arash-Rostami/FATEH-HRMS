@php
    $user = auth()->user();

@endphp
<div class="shrink-0 snap-start snap-always"
     x-data="{ open: false, top: 0, left: 0 }"
     @click.window="open && ! $event.target.closest('[data-popover=palette]') && ! $el.contains($event.target) && (open = false)"
     @keydown.escape.window="open = false">
    <button type="button"
            @click="const r = $event.currentTarget.getBoundingClientRect(); top = r.bottom + 8; left = Math.max(8, Math.min(r.left + r.width / 2 - 36, window.innerWidth - 88)); open = ! open"
            class="{{ $btn }}" aria-haspopup="true" :aria-expanded="open">
        <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100" data-icon="palette">palette</span>
        <x-ui.modals.tooltip text="شخصی‌سازی ظاهر" position="bottom"/>
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open" data-popover="palette" dir="rtl"
             :style="`top:${top}px;left:${left}px`"
             class="fixed z-[60] w-[72px] rounded-2xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface)] p-3 shadow-[0_12px_32px_-6px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]">
            <x-dashboard.settings.theme-swatches close-action="open = false"/>
        </div>
    </template>
</div>

<button type="button"
        x-data="{ lit: @js((bool) $user?->unreadNotifications()->exists()) }"
        x-on:bell-unread.window="lit = $event.detail.lit"
        @click="$dispatch('open-modal', { id: 'database-notifications' })"
        class="{{ $btn }}">
    <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">notifications</span>
    <span x-show="lit" x-cloak
          class="absolute top-2 right-2 h-2.5 w-2.5 rounded-full bg-[var(--md-sys-color-error)] ring-2 ring-[var(--md-sys-color-primary)]"></span>
    <x-ui.modals.tooltip text="اعلان‌ها" position="bottom"/>
</button>
