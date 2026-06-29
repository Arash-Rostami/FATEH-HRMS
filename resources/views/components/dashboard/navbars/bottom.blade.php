@props(['activeTab', 'tabs'])

@php
    $perPage = max((int) ceil(count($tabs) / 2), 4);
    $chunks = array_chunk($tabs, $perPage, true);
    $pageOne = $chunks[0] ?? [];
    $pageTwo = $chunks[1] ?? [];
    $pageTwoKeys = array_keys($pageTwo);
    $initialPage = in_array($activeTab, $pageTwoKeys) ? 1 : 0;
@endphp

<aside
    x-data="{ activeTab: @entangle('activeTab'), ...mobile({{ $initialPage }}, @js($pageTwoKeys)) }"
    @keydown.window.arrow-right.prevent="!$event.repeat && $wire.navigateTab(1)"
    @keydown.window.arrow-left.prevent="!$event.repeat && $wire.navigateTab(-1)"
    :class="isAtBottom ? 'bottom-14' : 'bottom-6'"
    class="lg:hidden fixed left-1/2 -translate-x-1/2 w-[92%] max-w-[400px] z-50 h-[72px] bg-[var(--md-sys-color-primary-container)] rounded-2xl transition-[bottom] duration-300 ease-in-out"
>
    <div class="absolute -top-5 left-1/2 -translate-x-1/2 z-50">
        <button @click="toggle()" class="w-10 h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-lg border-[3px] border-[var(--md-sys-color-surface)] transition-all duration-500 hover:scale-110 active:scale-95">
          <span
              class="material-symbols-rounded text-[20px]"
              :style="{ transform: page === 0 ? 'rotate(0deg)' : 'rotate(180deg)', transition: 'transform 0.5s cubic-bezier(0.34,1.56,0.64,1)' }"
          >swap_horiz</span>
        </button>
    </div>

    <div class="relative w-full h-full bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/20 rounded-[24px] shadow-2xl overflow-hidden">
        <div class="flex w-[200%] h-full transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]" :class="page === 0 ? 'translate-x-0' : '-translate-x-1/2'">

            <div class="w-1/2 h-full flex items-center justify-between px-4 gap-2">
                @foreach($pageOne as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')" class="relative flex-1 h-full max-h-[48px] rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90 {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/10' : 'text-[var(--md-sys-color-on-surface-variant)] opacity-50' }}">
                        <span class="material-symbols-rounded text-[26px] {{ $activeTab === $key ? 'font-fill' : '' }}">{{ $tab['icon'] }}</span>
                        @if(isset($tab['badge']) && collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false))
                            <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-700 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-700 border-2 border-[var(--md-sys-color-surface)]"></span>
                            </span>
                        @endif
                    </button>
                @endforeach
                @for($i = count($pageOne); $i < $perPage; $i++)
                    <div class="flex-1"></div>
                @endfor
            </div>

            <div class="w-1/2 h-full flex items-center justify-between px-4 gap-2">
                @foreach($pageTwo as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')" class="relative flex-1 h-full max-h-[48px] rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90 {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/10' : 'text-[var(--md-sys-color-on-surface-variant)] opacity-50' }}">
                        <span class="material-symbols-rounded text-[26px] {{ $activeTab === $key ? 'font-fill' : '' }}">{{ $tab['icon'] }}</span>
                        @if(isset($tab['badge']) && collect((array) $tab['badge'])->contains(fn($k) => $menuState[$k] ?? false))
                            <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-700 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-700 border-2 border-[var(--md-sys-color-surface)]"></span>
                            </span>
                        @endif
                    </button>
                @endforeach
                @for($i = count($pageTwo); $i < $perPage; $i++)
                    <div class="flex-1"></div>
                @endfor
            </div>

        </div>
    </div>
</aside>
