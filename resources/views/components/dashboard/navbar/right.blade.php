@props(['activeTab', 'tabs'])
<aside
    class="hidden lg:flex w-[70px] flex-col items-center gap-2 pt-10 backdrop-blur-sm my-auto shrink-0 z-10 fixed top-1/5 right-1">
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
            <span
                class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">
                {{ $tab['label'] }}
            </span>
        </button>
    @endforeach
</aside>

