        {{-- Load More Button --}}
        <div class="mt-8 mb-12 flex justify-center">
            <button
                wire:click="loadMore;" wire:island="posts"
                class="group px-6 py-2.5 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-primary)] font-bold text-sm border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-md hover:bg-[var(--md-sys-color-surface-container-highest)] hover:scale-105 active:scale-95 transition-all duration-300 flex items-center gap-2"
            >
                <span>نمایش بیشتر</span>
                <span class="material-symbols-rounded text-[20px] group-hover:translate-y-0.5 transition-transform"
                      wire:loading.remove target="loadMore">expand_more</span>
                <span
                    class="w-4 h-4 border-2 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin"
                    wire:loading target="loadMore"></span>
            </button>
        </div>
