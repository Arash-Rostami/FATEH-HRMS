@props([
    'tabs' => [],
    'activeTab' => '',
    'buttonBaseClass' => 'relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2 shrink-0 whitespace-nowrap',
    'buttonActiveClass' => 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]',
    'buttonInactiveClass' => 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60',
    'iconBaseClass' => 'material-symbols-rounded text-lg',
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
    {{ $attributes->merge(['class' => 'flex p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit max-w-full mb-6 shadow-sm overflow-x-auto overflow-y-hidden relative']) }}
    @if($hasA11y) role="tablist" @endif
    @if($hasRoutes) x-data="{ navigating: false }" @endif
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

    @if($hasRoutes)
        <div x-show="navigating" x-cloak x-transition.opacity
             x-on:livewire:navigate-failed.window="navigating = false"
             x-on:livewire:navigate-aborted.window="navigating = false"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-scrim)_45%,transparent)] backdrop-blur-sm">
            <x-ui.loaders.spin-badge text="در حال جابجایی..."/>
        </div>
    @endif
</div>