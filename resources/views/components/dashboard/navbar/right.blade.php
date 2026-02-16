@props(['activeTab', 'tabs'])

<aside
    class="hidden lg:flex w-[70px] flex-col items-center gap-4 pt-10 backdrop-blur-md my-auto shrink-0 z-10 fixed top-1/2 -translate-y-1/2 right-2 rounded-2xl bg-white/5 border border-white/10 shadow-2xl transition-all duration-300">

    @foreach($tabs as $key => $tab)
        <button
            wire:click="setTab('{{ $key }}')"
            class="group relative w-12 h-12 flex items-center justify-center rounded-xl transition-all duration-300 ease-out
                   {{ $activeTab === $key
                       ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-lg shadow-[var(--md-sys-color-primary)]/40 scale-105'
                       : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] hover:shadow-md hover:scale-105'
                   }}"
            aria-label="{{ $tab['label'] }}"
        >
            <span class="material-symbols-rounded text-[24px] transition-transform duration-300 {{ $activeTab === $key ? 'scale-110' : 'group-hover:scale-110' }}">
                {{ $tab['icon'] }}
            </span>

            <!-- Tooltip -->
            <span
                class="absolute right-14 top-1/2 -translate-y-1/2 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[12px] font-medium px-3 py-1.5 rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap shadow-sm z-50 translate-x-2 group-hover:translate-x-0"
            >
                {{ $tab['label'] }}
            </span>
        </button>
    @endforeach

</aside>
