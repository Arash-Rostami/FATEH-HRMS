@props([
    'tabs' => [],
    'activeTab' => '',
    'buttonBaseClass' => 'relative px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 z-10 flex items-center gap-2',
    'buttonActiveClass' => 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]',
    'buttonInactiveClass' => 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/60',
    'iconBaseClass' => 'material-symbols-rounded text-lg',
    'iconActiveClass' => '',
    'iconInactiveClass' => '',
    'hasA11y' => false,
])

<div
    {{ $attributes->merge(['class' => 'flex p-1 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 w-fit mb-6 shadow-sm overflow-hidden relative']) }}
    @if($hasA11y) role="tablist" @endif>

    @foreach($tabs as $tab)
        @if($tab['condition'] ?? true)
            @php($isDisabled = $tab['disabled'] ?? false)
            <button
                @if($isDisabled)
                    type="button"
                    disabled
                    title="{{ $tab['disabledReason'] ?? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' }}"
                @else
                    wire:click="switchTab('{{ $tab['id'] }}')"
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
                @if($isDisabled)
                    <span class="material-symbols-rounded text-[15px] opacity-80">lock</span>
                @endif
            </button>
        @endif
    @endforeach
</div>
