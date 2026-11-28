@props(['tabs' => [], 'active' => null])

@php
    $iconOnly = collect($tabs)->filter(fn ($t) => ($t['condition'] ?? true) && ($t['id'] ?? '') !== $active)->count() > 1;
@endphp

<div class="flex items-center gap-1.5">
    @foreach($tabs as $tab)
        @if(($tab['condition'] ?? true) && ($tab['id'] ?? '') !== $active)
            @php
                $isButton = isset($tab['click']);
                $isBlank = !$isButton && ($tab['blank'] ?? false);
                $el = $isButton ? 'button' : 'a';
            @endphp
            <div wire:key="module-links-{{ $tab['id'] }}" x-data="{ navigating: false }" class="flex items-center">
                <{{ $el }}
                   @if($isButton)
                       type="button"
                       x-on:click="{{ $tab['click'] }}"
                   @elseif($isBlank)
                       href="{{ $tab['route'] }}"
                       target="_blank"
                       rel="noopener noreferrer"
                   @else
                       href="{{ $tab['route'] }}"
                       wire:navigate
                       x-on:click="navigating = true; setTimeout(() => navigating = false, 6000)"
                   @endif
                   aria-label="{{ $tab['label'] . (($tab['unread'] ?? 0) > 0 ? '، ' . convertToPersian(min($tab['unread'], 99)) . ' پیام خوانده‌نشده' : '') }}"
                   title="{{ $tab['label'] }}"
                   class="group relative flex items-center gap-1.5 px-3 {{ $iconOnly && !isset($tab['short']) ? 'max-sm:px-2' : '' }} py-1.5 rounded-xl text-xs font-bold
                          bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]
                          shadow-sm transition-all duration-200 active:scale-95
                          hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)]
                          hover:shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]">
                    <span class="material-symbols-rounded text-[16px] transition-transform duration-200 group-hover:scale-110">{{ $tab['icon'] }}</span>
                    @if(isset($tab['short']))
                        <span class="tracking-tight max-sm:hidden">{{ $tab['label'] }}</span>
                        <span class="tracking-tight sm:hidden">{{ $tab['short'] }}</span>
                    @else
                        <span class="tracking-tight {{ $iconOnly ? 'max-sm:hidden' : '' }}">{{ $tab['label'] }}</span>
                    @endif
                    @if(($tab['unread'] ?? 0) > 0)
                        <span wire:key="module-links-unread-{{ $tab['id'] }}"
                              class="flex-shrink-0 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center
                                     bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]
                                     group-hover:bg-[var(--md-sys-color-on-primary)]/15 group-hover:text-[var(--md-sys-color-on-primary)]">
                            {{ $tab['unread'] > 99 ? '⁹⁹⁺' : convertToPersian($tab['unread']) }}
                        </span>
                    @endif
                </{{ $el }}>
                @unless($isButton || $isBlank)
                    <div x-show="navigating" x-cloak x-transition.opacity.duration.200ms
                         x-on:livewire:navigate-failed.window="navigating = false"
                         x-on:livewire:navigate-aborted.window="navigating = false"
                         class="fixed inset-0 z-[9999] flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-primary)_60%,transparent)] backdrop-blur-sm">
                        <x-ui.loaders.spin-badge text="در حال باز کردن صفحه…"/>
                    </div>
                @endunless
            </div>
        @endif
    @endforeach
</div>
