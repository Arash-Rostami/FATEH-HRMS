@props([
    'show'        => 'minimized',
    'onClick'     => 'restore()',
    'iconExpr'    => "'timer'",
    'iconClass'   => '',
    'label'       => '',
    'valueExpr'   => "''",
    'alarm'       => 'false',
    'restoreFn'   => 'restore()',
    'closeFn'     => 'closeModal()',
])

<div x-show="{{ $show }}"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-8"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-x-0"
     x-transition:leave-end="opacity-0 translate-x-8"
     class="flex items-center justify-between overflow-hidden select-none rounded-md shadow-md
            pr-1.5 pl-3 py-1.5 transition-colors group w-[220px]"
     :class="{{ $alarm }} ? 'bg-red-500/20 border border-red-500/50 animate-pulse cursor-default'
                           : 'bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface)] cursor-pointer'"
     @click="if(!({{ $alarm }})) {{ $onClick }}">

    {{-- ── Left: icon + text ───────────────────────────────────────── --}}
    <div class="flex items-center gap-3 min-w-0">

        {{-- Icon chip --}}
        <div class="flex h-[36px] w-[36px] shrink-0 items-center justify-center rounded-md shadow-inner
                    {{ $iconClass }}"
             :class="{{ $alarm }} ? 'bg-red-500 text-white'
                                  : 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]'">
            {{ $icon }}
        </div>

        {{-- Label + value (hidden during alarm so alarm slot shows) --}}
        <div class="flex flex-col justify-center min-w-0" :class="{{ $alarm }} ? 'hidden' : ''">
            <span class="text-[10px] font-medium leading-tight text-[var(--md-sys-color-outline)]">
                {{ $label }}
            </span>
            <span class="text-[13px] font-bold leading-tight truncate text-[var(--md-sys-color-on-surface)]
                         [font-feature-settings:'tnum']"
                  x-text="{{ $valueExpr }}" dir="ltr"></span>
        </div>

        {{-- Alarm text slot (only shown when alarming) --}}
        @isset($alarmText)
            <div class="flex flex-col justify-center min-w-0" x-show="{{ $alarm }}" style="display:none;">
                {{ $alarmText }}
            </div>
        @endisset
    </div>

    {{-- ── Right: controls + hover actions ───────────────────────────── --}}
    <div class="flex items-center gap-1 shrink-0">

        {{-- Always-visible inline controls (play/pause etc.) --}}
        @isset($controls)
            <div x-show="!({{ $alarm }})">
                {{ $controls }}
            </div>
        @endisset

        {{-- Alarm stop slot --}}
        @isset($alarmControls)
            <div x-show="{{ $alarm }}" style="display:none;">
                {{ $alarmControls }}
            </div>
        @endisset

        {{-- Hover-revealed: expand --}}
        @if($restoreFn)
            <button @click.stop="{{ $restoreFn }}" title="بزرگنمایی"
                    x-show="!({{ $alarm }})"
                    class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center
                           justify-center rounded-md w-[28px] h-[28px]
                           hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[18px]">open_in_full</span>
            </button>
        @endif

        {{-- Hover-revealed: close --}}
        @if($closeFn)
            <button @click.stop="{{ $closeFn }}" title="بستن"
                    x-show="!({{ $alarm }})"
                    class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center
                           justify-center rounded-md w-[28px] h-[28px]
                           hover:bg-red-500/10 hover:text-red-500 text-[var(--md-sys-color-outline)]">
                <span class="material-symbols-rounded text-[18px]">close</span>
            </button>
        @endif
    </div>
</div>
