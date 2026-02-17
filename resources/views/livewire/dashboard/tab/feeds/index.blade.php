<div
    x-data="feedTab"
    class="relative w-full h-full bg-[var(--md-sys-color-background)]"
    dir="rtl"
>
    @if($assetsLoaded)
        @include('livewire.dashboard.tab.feeds.styles')
    @endif

    <div
        x-ref="feedContainer"
        class="
            w-full h-full
            flex flex-col md:flex-row
            overflow-y-auto md:overflow-y-hidden md:overflow-x-auto
            md:snap-x md:snap-mandatory
            gap-6 p-4 md:p-8
            scrollbar-hide
            items-center md:items-stretch
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
                    transition-all duration-500 ease-out
                "
                :class="{
                    'scale-105 z-10 elevation-3': activeId == {{ $feed->id }},
                    'scale-95 opacity-80 elevation-1': activeId != {{ $feed->id }}
                }"
            >
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
    </div>

    {{-- Modal --}}
    <template x-teleport="body">
        <div
            x-show="deleteModalOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center px-4"
            style="display: none;"
        >
            <div
                x-show="deleteModalOpen"
                x-transition.opacity
                @click="cancelDelete"
                class="absolute inset-0 bg-black/40 backdrop-blur-sm"
            ></div>

            <div
                x-show="deleteModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                @click.stop
                x-trap.noscroll="deleteModalOpen"
                class="relative w-full max-w-sm bg-[var(--md-sys-color-surface)] rounded-[28px] p-6 shadow-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/20"
            >
                 <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">حذف نظر</h3>
                 <p class="text-[var(--md-sys-color-on-surface-variant)] mb-6 text-sm leading-relaxed">
                    آیا از حذف این نظر اطمینان دارید؟ این عملیات قابل بازگشت نیست.
                 </p>

                 <div class="flex items-center justify-end gap-2">
                    <button
                        @click="cancelDelete"
                        class="px-5 py-2.5 rounded-full text-[var(--md-sys-color-primary)] font-bold text-sm hover:bg-[var(--md-sys-color-surface-variant)] transition-colors"
                    >
                        انصراف
                    </button>
                    <button
                        @click="deleteComment"
                        class="px-5 py-2.5 rounded-full bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] font-bold text-sm shadow-md hover:shadow-lg hover:scale-105 transition-all"
                    >
                        حذف
                    </button>
                 </div>
            </div>
        </div>
    </template>
</div>
