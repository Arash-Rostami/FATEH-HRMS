@props(['activeTab', 'tabs'])

<aside
    x-data="peek"
    data-active="{{ $activeTab }}"
    @keydown.window.arrow-down="if (!document.querySelector('.fancybox__container')) { $event.preventDefault(); $wire.navigateTab(1) }"
    @keydown.window.arrow-up="if (!document.querySelector('.fancybox__container')) { $event.preventDefault(); $wire.navigateTab(-1) }"
    :class="$store.focus.active ? 'focus-dim' : ''"
    class="right-rail hidden lg:flex w-[70px] flex-col items-center gap-1 pt-10 my-auto shrink-0 fixed top-1/5 right-1 z-[9998]"
>
    @foreach($tabs as $key => $tab)
        <button
            wire:click="setTab('{{ $key }}')"
            class="group relative w-12 h-12 flex items-center justify-center rounded-xl
                   {{ $key === 'feed' && feedAiOn()
                       ? 'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,var(--md-sys-color-surface))] border !border-[color-mix(in_srgb,var(--ai-color)_25%,transparent)]'
                       : 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' }}
                   animate-slide-in-right animate-delay-500
                   cursor-pointer transition-all duration-300 ease-out
                   {{ $key === 'feed' && feedAiOn()
                       ? 'hover:!bg-[var(--ai-color)] hover:!border-transparent hover:!shadow-[0_4px_14px_color-mix(in_srgb,var(--ai-color)_45%,transparent)]'
                       : 'hover:bg-[var(--md-sys-color-primary-container)]' }}
                   hover:text-[var(--md-sys-color-on-primary-container)]
                   hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
                   hover:-translate-y-0.5 active:scale-95
                   {{ $key === 'feed' && feedAiOn() ? 'ai-halo' : '' }}
                   {{ $activeTab === $key
                       ? (($key === 'feed' && feedAiOn() ? '!bg-[var(--md-sys-color-primary-container)]' : 'bg-[var(--md-sys-color-primary-container)]') . ' text-[var(--md-sys-color-on-primary-container)] shadow-lg shadow-[var(--md-sys-color-primary)]/40 scale-105 ring-[var(--md-sys-color-on-primary-container)]')
                       : 'hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-primary)] hover:shadow-md hover:scale-105'
                   }} {{ $key === 'home' ? 'rail-home' : '' }}"
            aria-label="{{ $tab['label'] }}"
            @mouseenter="show('{{ $key }}', $el)"
            @mouseleave="hide()"
            @click="capture(); hide()"
        >
            <span class="material-symbols-rounded text-[22px] transition-all duration-300 group-hover:scale-110
                {{ $activeTab === $key ? 'animate-spring-pop text-[var(--md-sys-color-primary)]' : '' }}
                {{ $key === 'feed' && feedAiOn() ? '!text-[var(--ai-color)] group-hover:!text-[var(--md-sys-color-surface)]' : '' }}">
                {{ $tab['icon'] }}
            </span>

            @if($key === 'feed' && feedAiOn())
                <span wire:key="feed-ai-rail-sparkle" aria-hidden="true"
                      class="material-symbols-rounded !text-[11px] absolute top-1 left-1 z-10 text-[var(--ai-color)] group-hover:text-[var(--md-sys-color-surface)] transition-colors duration-300 pointer-events-none">auto_awesome</span>
            @endif

            @if(isset($tab['badge']) && collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false))
                <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-700 opacity-75"></span>
                    <span
                        class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-700 border-2 border-[var(--md-sys-color-surface)]"></span>
                </span>
            @endif
        </button>
    @endforeach

    <x-ui.peek/>
</aside>
