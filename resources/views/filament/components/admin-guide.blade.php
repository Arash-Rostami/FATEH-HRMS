@php
    $tabs = $tabs ?? [];
@endphp

@if(!empty($tabs))
    <div x-data="{ tab: 0 }" dir="rtl" class="flex flex-col">
        <div class="flex flex-wrap gap-1.5 border-b border-[var(--md-sys-color-outline-variant)]/60 pb-2 mb-3">
            @foreach($tabs as $i => $tab)
                <button type="button"
                    @click="tab = {{ $i }}"
                    :class="tab === {{ $i }} ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container)]'"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-bold transition-colors outline-none">
                    <span class="material-symbols-rounded text-[16px]">{{ $tab['icon'] ?? 'menu_book' }}</span>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        <div class="max-h-[60vh] overflow-y-auto pl-1 -mr-1" style="scrollbar-width: thin;">
            @foreach($tabs as $i => $tab)
                <div x-show="tab === {{ $i }}" x-cloak>
                    @include($tab['view'])
                </div>
            @endforeach
        </div>
    </div>
@endif