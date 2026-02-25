@props(['activeTab', 'tabs'])

<aside
    wire:keydown.window.arrow-down.prevent="navigateTab(1)"
    wire:keydown.window.arrow-up.prevent="navigateTab(-1)"
    class="hidden lg:flex w-[70px] flex-col items-center gap-2 pt-10 my-auto shrink-0 fixed top-1/5 right-1 z-[9998]"
>
    @foreach($tabs as $key => $tab)
        <button
            wire:click="setTab('{{ $key }}')"
            class="group relative w-12 h-12 flex items-center justify-center rounded-xl
                   bg-[var(--md-sys-color-primary)]
                   text-[var(--md-sys-color-on-primary)]
                   cursor-pointer transition-all duration-300 ease-out
                   hover:bg-[var(--md-sys-color-primary-container)]
                   hover:text-[var(--md-sys-color-on-primary-container)]
                   hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
                   hover:-translate-y-0.5 active:scale-95
                   {{ $activeTab === $key
                       ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-lg shadow-[var(--md-sys-color-primary)]/40 scale-105 ring-[var(--md-sys-color-on-primary-container)]'
                       : 'hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] hover:shadow-md hover:scale-105'
                   }}"
            aria-label="{{ $tab['label'] }}"
        >
            <span class="material-symbols-rounded text-[22px] transition-all duration-300 group-hover:scale-110
                {{ $activeTab === $key ? 'animate-spring-pop text-[var(--md-sys-color-primary)]' : '' }}">
                {{ $tab['icon'] }}
            </span>

            <x-dashboard.tooltip :text="$tab['label']" position="left" />
        </button>
    @endforeach
</aside>
