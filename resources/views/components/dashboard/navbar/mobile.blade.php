@props(['activeTab', 'tabs'])

@php
    $count = count($tabs);
    $perPage = $count > 4 ? 4 : 2;

    $chunks = array_chunk($tabs, $perPage, true);
    $pageOne = $chunks[0] ?? [];
    $pageTwo = $chunks[1] ?? [];
@endphp

<aside x-data="{
            page: 0,
            toggle() { this.page = this.page === 0 ? 1 : 0 }
        }"
       class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 w-[92%] max-w-[400px] z-50 h-[72px]">

    <div class="absolute -top-5 left-1/2 -translate-x-1/2 z-50">
        <button @click="toggle()"
                class="w-10 h-10 rounded-xl flex items-center justify-center
                       bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]
                       shadow-lg shadow-[var(--md-sys-color-primary)]/30
                       border-[3px] border-[var(--md-sys-color-surface)]
                       transition-all duration-500 hover:scale-110 active:scale-95 group">

            <span class="material-symbols-rounded text-[20px] transition-transform duration-500"
                  :class="page === 0 ? 'rotate-0' : '-rotate-180'">
                swap_horiz
            </span>
        </button>
    </div>

    <div class="relative w-full h-full bg-[var(--md-sys-color-surface-container-high)] backdrop-blur-3xl border border-[var(--md-sys-color-outline-variant)]/20 rounded-[24px] shadow-2xl shadow-black/10 overflow-hidden">

        <div class="flex w-[200%] h-full transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
             :class="page === 0 ? 'translate-x-0' : '-translate-x-1/2'">
            {{-- PAGE 1 --}}
            <div class="w-1/2 h-full flex items-center justify-between px-4 gap-2">
                @foreach($pageOne as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')"
                            class="flex-1 h-full max-h-[48px] rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90
                                   {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/10' : 'text-[var(--md-sys-color-on-surface-variant)] opacity-50' }}">
                        <span class="material-symbols-rounded text-[26px] {{ $activeTab === $key ? 'font-fill' : '' }}">{{ $tab['icon'] }}</span>
                    </button>
                @endforeach
                @for($i = count($pageOne); $i < $perPage; $i++)
                    <div class="flex-1"></div>
                @endfor
            </div>
            {{-- PAGE 2 --}}
            <div class="w-1/2 h-full flex items-center justify-between px-4 gap-2">
                @foreach($pageTwo as $key => $tab)
                    <button wire:click="setTab('{{ $key }}')"
                            class="flex-1 h-full max-h-[48px] rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90
                                   {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]/10' : 'text-[var(--md-sys-color-on-surface-variant)] opacity-50' }}">
                        <span class="material-symbols-rounded text-[26px] {{ $activeTab === $key ? 'font-fill' : '' }}">{{ $tab['icon'] }}</span>
                    </button>
                @endforeach
                @for($i = count($pageTwo); $i < $perPage; $i++)
                    <div class="flex-1"></div>
                @endfor
            </div>
        </div>
    </div>

</aside>
