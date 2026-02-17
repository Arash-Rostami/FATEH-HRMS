<div
    x-data="feedTab"
    class="relative w-full h-full bg-[var(--md-sys-color-background)]"
    dir="rtl"
>
    @if($assetsLoaded)
        @include('livewire.dashboard.tab.feeds.styles')
    @endif

    {{-- The Timeline Track (Desktop Only) --}}
    <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-[var(--md-sys-color-outline-variant)] opacity-20 -translate-y-1/2 z-0 hidden md:block"></div>

    <div
        x-ref="feedContainer"
        class="
            w-full h-full
            flex flex-col md:flex-row
            overflow-y-auto md:overflow-y-hidden md:overflow-x-auto
            md:snap-x md:snap-mandatory
            gap-8 md:gap-16
            px-[10%] md:px-[20%]
            py-8 md:py-0
            scrollbar-hide
            items-center md:items-center
        "
    >
        @foreach($this->feeds as $feed)
            <div
                wire:key="feed-{{ $feed->id }}"
                data-feed-id="{{ $feed->id }}"
                class="
                    shrink-0
                    w-full max-w-md
                    h-[70vh] md:h-[80vh] md:w-[400px]
                    snap-center
                    flex flex-col justify-center relative group
                    transition-all duration-500 ease-out
                "
                :class="{
                    'scale-105 z-10 elevation-3': activeId == {{ $feed->id }},
                    'scale-95 opacity-80 elevation-1': activeId != {{ $feed->id }}
                }"
            >
                {{-- Timeline Node Container (Desktop Only) --}}
                <div class="absolute top-1/2 -right-8 z-20 hidden md:flex flex-col items-center justify-center -translate-y-1/2 translate-x-1/2 pointer-events-none">

                    {{-- Time Label --}}
                    <div class="absolute bottom-10 whitespace-nowrap px-2 py-1 rounded-md bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-[10px] font-bold text-[var(--md-sys-color-primary)]">
                            {{ $feed->created_at->format('H:i') }}
                        </span>
                        <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-[var(--md-sys-color-surface-container-high)] rotate-45 border-r border-b border-[var(--md-sys-color-outline-variant)]/20"></div>
                    </div>

                    {{-- The Dot --}}
                    <div class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-high)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-transform duration-300"
                        :class="activeId == {{ $feed->id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
                    >
                        <div class="w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                    </div>

                    {{-- Date Label --}}
                    <div class="absolute top-10 whitespace-nowrap opacity-60 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                            {{ $feed->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                @include('livewire.dashboard.tab.feeds.item', ['feed' => $feed])
            </div>
        @endforeach

        @if($hasMorePages)
            <div
                x-ref="loadTrigger"
                wire:key="loader-{{ count($feedIds) }}"
                class="shrink-0 w-full md:w-24 h-24 md:h-full snap-center flex items-center justify-center"
            >
                 <x-dashboard.loader.spinner />
            </div>
        @endif

        {{-- Trailing Spacer (Replaces padding-end behavior in some browsers or improves feel) --}}
        <div class="shrink-0 w-4 md:w-[20%] snap-align-none pointer-events-none h-1"></div>
    </div>
</div>
