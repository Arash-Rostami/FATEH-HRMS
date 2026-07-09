@php
$tabs = [
    ['id' => 'contacts', 'label' => 'پیام‌رسان', 'icon' => 'perm_contact_calendar', 'route' => route('contact'), 'unread' => (int) $contactsUnread],
    ['id' => 'channels', 'label' => 'کانال‌ها', 'icon' => 'campaign', 'route' => route('channels'), 'unread' => (int) $channelsUnread],
];
@endphp
<div wire:poll.10s="refreshCounts" x-data="{ navigating: false }" class="flex p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit mb-6 shadow-sm overflow-hidden relative">
    @foreach($tabs as $tab)
        @php($isActive = $active === $tab['id'])
        @if($isActive)
            <div class="relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]">
                <span class="material-symbols-rounded text-lg">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
                @if($tab['unread'] > 0)
                    <span class="flex-shrink-0 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center bg-[var(--md-sys-color-on-primary)]/15 text-[var(--md-sys-color-on-primary)]">
                        {{ $tab['unread'] > 99 ? '⁹⁹⁺' : $tab['unread'] }}
                    </span>
                @endif
            </div>
        @else
            <a href="{{ $tab['route'] }}" wire:navigate x-on:click="navigating = true; setTimeout(() => navigating = false, 6000)" class="relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60">
                <span class="material-symbols-rounded text-lg">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
                @if($tab['unread'] > 0)
                    <span class="flex-shrink-0 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                        {{ $tab['unread'] > 99 ? '⁹⁹⁺' : $tab['unread'] }}
                    </span>
                @endif
            </a>
        @endif
    @endforeach
    <div x-show="navigating" x-cloak x-transition.opacity
         x-on:livewire:navigate-failed.window="navigating = false"
         x-on:livewire:navigate-aborted.window="navigating = false"
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-scrim)_45%,transparent)] backdrop-blur-sm">
        <div class="grid place-items-center gap-3 text-[var(--md-sys-color-on-surface-variant)]">
            <div class="size-12 animate-spin rounded-full bg-[conic-gradient(var(--md-sys-color-primary)_0%,transparent_35%,var(--md-sys-color-primary)_50%,transparent_85%)] shadow-[0_0_12px_var(--md-sys-color-primary)] [mask-image:radial-gradient(farthest-side,transparent_calc(100%-3px),#000_calc(100%-3px))] [-webkit-mask-image:radial-gradient(farthest-side,transparent_calc(100%-3px),#000_calc(100%-3px))]"></div>
            <span class="text-xs font-bold opacity-70">در حال جابجایی...</span>
        </div>
    </div>
</div>