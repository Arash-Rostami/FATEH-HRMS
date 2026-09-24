@props([
    'tabs' => [],
    'activeTab' => '',
    'buttonBaseClass' => 'relative px-3 py-2 sm:px-6 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all duration-300 z-10 flex items-center gap-1.5 sm:gap-2 shrink-0 whitespace-nowrap',
    'buttonActiveClass' => 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]',
    'buttonInactiveClass' => 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60',
    'iconBaseClass' => 'material-symbols-rounded text-base sm:text-lg',
    'iconActiveClass' => '',
    'iconInactiveClass' => '',
    'hasA11y' => false,
    'fastSwitch' => false,
    'warmMs' => null,
])

@php
    $hasRoutes = collect($tabs)->contains(fn ($t) => isset($t['route']));
@endphp

<div
    {{ $attributes->merge(['class' => 'flex p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit max-w-full mb-6 shadow-sm overflow-x-hidden sm:overflow-x-auto overflow-y-hidden custom-scrollbar relative']) }}
    x-data="tabSelector()"
    @if($hasA11y) role="tablist" @endif
>
    @foreach($tabs as $tab)
        @if($tab['condition'] ?? true)
            @php
                $isDisabled = $tab['disabled'] ?? false;
                $isRoute = isset($tab['route']);
                $isActive = $activeTab === $tab['id'];
                $unread = $tab['unread'] ?? 0;
                $badgeBase = 'flex-shrink-0 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center';
                $badgeActive = 'bg-[var(--md-sys-color-on-primary)]/15 text-[var(--md-sys-color-on-primary)]';
                $badgeInactive = 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]';
            @endphp

            @if($isRoute && $isActive)
                <span
                    wire:key="tab-{{ $tab['id'] }}"
                    data-tab-pill="{{ $tab['id'] }}" data-active
                    @if($hasA11y) role="tab" aria-selected="true" tabindex="0" @endif
                    class="{{ $buttonBaseClass }} {{ $buttonActiveClass }}"
                >
                    <span class="{{ $iconBaseClass }} {{ $iconActiveClass }}">{{ $tab['icon'] }}</span>
                    {{ $tab['label'] }}
                    @if($unread > 0)
                        <span class="{{ $badgeBase }} {{ $badgeActive }}">{{ $unread > 99 ? '⁹⁹⁺' : $unread }}</span>
                    @endif
                </span>
            @elseif($isRoute && $isDisabled)
                <span
                    wire:key="tab-{{ $tab['id'] }}"
                    data-tab-pill="{{ $tab['id'] }}"
                    title="{{ $tab['disabledReason'] ?? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' }}"
                    @if($hasA11y) role="tab" aria-selected="false" aria-disabled="true" tabindex="-1" @endif
                    class="{{ $buttonBaseClass }} opacity-40 grayscale-[35%] cursor-not-allowed"
                >
                    <span class="{{ $iconBaseClass }} opacity-70">{{ $tab['icon'] }}</span>
                    {{ $tab['label'] }}
                    <span class="material-symbols-rounded text-[15px] opacity-80">lock</span>
                </span>
            @elseif($isRoute)
                <a
                    wire:key="tab-{{ $tab['id'] }}"
                    data-tab-pill="{{ $tab['id'] }}"
                    href="{{ $tab['route'] }}"
                    wire:navigate
                    x-on:click="navigating = true; setTimeout(() => navigating = false, 6000)"
                    @if($hasA11y) role="tab" aria-selected="false" tabindex="-1" @endif
                    class="{{ $buttonBaseClass }} {{ $buttonInactiveClass }}"
                >
                    <span class="{{ $iconBaseClass }} {{ $iconInactiveClass }}">{{ $tab['icon'] }}</span>
                    {{ $tab['label'] }}
                    @if($unread > 0)
                        <span class="{{ $badgeBase }} {{ $badgeInactive }}">{{ $unread > 99 ? '⁹⁹⁺' : $unread }}</span>
                    @endif
                </a>
            @else
                <button
                    wire:key="tab-{{ $tab['id'] }}"
                    data-tab-pill="{{ $tab['id'] }}"
                    @if($isActive) data-active @endif
                    @if($isDisabled)
                        type="button"
                        disabled
                        title="{{ $tab['disabledReason'] ?? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' }}"
                    @elseif($fastSwitch)
                        wire:pointerdown="switchTab('{{ $tab['id'] }}')"
                        x-on:click="if ($event.detail === 0) $wire.switchTab('{{ $tab['id'] }}')"
                        @if($warmMs)
                            x-on:pointerenter="warmTab('{{ $tab['id'] }}')"
                            x-on:pointerleave="cancelWarm()"
                        @endif
                        @if(!empty($tab['description'])) title="{{ $tab['description'] }}" @endif
                    @else
                        wire:click="switchTab('{{ $tab['id'] }}')"
                        @if(!empty($tab['description'])) title="{{ $tab['description'] }}" @endif
                    @endif
                    @if($hasA11y)
                        role="tab"
                        aria-disabled="{{ $isDisabled ? 'true' : 'false' }}"
                        :aria-selected="$wire.activeTab === '{{ $tab['id'] }}'"
                    @endif
                    class="{{ $buttonBaseClass }} {{ $isDisabled ? 'opacity-40 grayscale-[35%] cursor-not-allowed hover:bg-transparent' : ($activeTab === $tab['id'] ? $buttonActiveClass : $buttonInactiveClass) }}"
                >
                    <span
                        class="{{ $iconBaseClass }} {{ $isDisabled ? 'opacity-70' : ($activeTab === $tab['id'] ? $iconActiveClass : $iconInactiveClass) }}">
                        {{ $tab['icon'] }}
                    </span>
                    {{ $tab['label'] }}
                    @if($unread > 0)
                        <span class="{{ $badgeBase }} {{ $isActive ? $badgeActive : $badgeInactive }}">{{ $unread > 99 ? '⁹⁹⁺' : $unread }}</span>
                    @endif
                    @if($isDisabled)
                        <span class="material-symbols-rounded text-[15px] opacity-80">lock</span>
                    @endif
                </button>
            @endif
        @endif
    @endforeach

    <div x-show="hidden.length > 0" x-cloak class="shrink-0 self-center mx-auto flex items-center">
        <button type="button" x-ref="moreTrigger"
                x-on:click="pos = $refs.moreTrigger.getBoundingClientRect().toJSON(); moreOpen = !moreOpen"
                aria-label="زبانه‌های بیشتر" title="زبانه‌های بیشتر"
                class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] transition-all duration-200 ease-out active:scale-95">
            <span class="material-symbols-rounded text-base">more_horiz</span>
        </button>
    </div>
    <template x-teleport="body">
        <div x-show="moreOpen" x-cloak dir="rtl"
             x-on:click.away="if (!$refs.moreTrigger?.contains($event.target)) moreOpen = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :style="{ position: 'fixed', top: (pos.bottom + 8) + 'px', left: pos.left + 'px' }"
             class="p-1.5 rounded-xl z-40 max-h-[70vh] overflow-y-auto custom-scrollbar bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
             role="menu" aria-label="زبانه‌های بیشتر">
            @foreach($tabs as $tab)
                @if($tab['condition'] ?? true)
                    @php
                        $rowDisabled = $tab['disabled'] ?? false;
                        $rowRoute = $tab['route'] ?? null;
                        $rowUnread = $tab['unread'] ?? 0;
                    @endphp
                    @if($rowRoute && !$rowDisabled)
                        <a href="{{ $rowRoute }}" wire:navigate x-show="hidden.includes('{{ $tab['id'] }}')"
                           x-on:click="moreOpen = false; navigating = true; setTimeout(() => navigating = false, 6000)"
                           class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                            <span class="material-symbols-rounded text-[16px]">{{ $tab['icon'] }}</span>
                            <span>{{ $tab['label'] }}</span>
                            @if($rowUnread > 0)
                                <span class="ms-auto min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">{{ $rowUnread > 99 ? '⁹⁹⁺' : $rowUnread }}</span>
                            @endif
                        </a>
                    @elseif($rowRoute)
                        <span x-show="hidden.includes('{{ $tab['id'] }}')"
                              title="{{ $tab['disabledReason'] ?? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' }}"
                              class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium opacity-40 grayscale-[35%] cursor-not-allowed">
                            <span class="material-symbols-rounded text-[16px]">{{ $tab['icon'] }}</span>
                            <span>{{ $tab['label'] }}</span>
                            <span class="material-symbols-rounded text-[15px] opacity-80 ms-auto">lock</span>
                        </span>
                    @else
                        <button type="button" x-show="hidden.includes('{{ $tab['id'] }}')"
                                @if($rowDisabled)
                                    disabled
                                    title="{{ $tab['disabledReason'] ?? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' }}"
                                @else
                                    x-on:click="moreOpen = false; $wire.switchTab('{{ $tab['id'] }}')"
                                @endif
                                @if(!empty($tab['description'])) title="{{ $tab['description'] }}" @endif
                                class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium {{ $rowDisabled ? 'opacity-40 grayscale-[35%] cursor-not-allowed hover:bg-transparent' : 'hover:bg-[var(--md-sys-color-surface-container-high)]/50' }}">
                            <span class="material-symbols-rounded text-[16px]">{{ $tab['icon'] }}</span>
                            <span>{{ $tab['label'] }}</span>
                            @if($rowUnread > 0)
                                <span class="ms-auto min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">{{ $rowUnread > 99 ? '⁹⁹⁺' : $rowUnread }}</span>
                            @endif
                            @if($rowDisabled)
                                <span class="material-symbols-rounded text-[15px] opacity-80 ms-auto">lock</span>
                            @endif
                        </button>
                    @endif
                @endif
            @endforeach
        </div>
    </template>

    @if($hasRoutes)
        <div x-show="navigating" x-cloak x-transition.opacity
             x-on:livewire:navigate-failed.window="navigating = false"
             x-on:livewire:navigate-aborted.window="navigating = false"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-scrim)_45%,transparent)] backdrop-blur-sm">
            <x-ui.loaders.spin-badge text="در حال جابجایی..."/>
        </div>
    @endif
</div>