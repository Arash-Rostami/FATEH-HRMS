<section class="flex-1 min-w-0 h-full overflow-y-auto custom-scrollbar pr-1 pl-1 pb-20">
    <div class="flex items-center gap-3 mb-4 px-1">
        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
             <span class="material-symbols-rounded text-base font-fill">feed</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">تازه ترین‌ها</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-5">
        @island(name: 'posts', lazy: true)
        @if(->posts->isNotEmpty())
            @foreach(->posts as )
                <article
                    class="group relative flex flex-col bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 transition-all duration-300 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:-translate-y-1 hover:border-[var(--md-sys-color-primary)]/30"
                    wire:key="post-{{ ->id }}"
                >
                    <div class="relative h-52 overflow-hidden cursor-pointer bg-[var(--md-sys-color-surface-variant)]" wire:click="selectPost({{ ->id }})">
                        <img
                            src="{{ ->image }}"
                            alt="{{ superClean(->title, 200) }}"
                            loading="lazy"
                            class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-110"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-scrim,black)]/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>

                    <div class="p-5 flex flex-col flex-grow">
                        <div class="flex items-center justify-between mb-3">
                             <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border border-[var(--md-sys-color-secondary)]/20 shadow-sm">
                                <span class="text-[10px] font-bold tracking-wide">اخبار</span>
                            </div>
                            <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] mt-0.5">
                                {{ ->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <h4
                            class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2 line-clamp-2 leading-snug cursor-pointer group-hover:text-[var(--md-sys-color-primary)] transition-colors"
                            wire:click="selectPost({{ ->id }})"
                        >
                            {{ superClean(->title, 100) }}
                        </h4>

                        <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] line-clamp-3 mb-5 flex-grow text-justify">
                            {{ superClean(->body, 100) }}
                        </p>

                        <div class="pt-4 mt-auto border-t border-[var(--md-sys-color-outline-variant)]/20 flex items-center justify-between">
                            <button
                                @click="('select-post', { id: {{ ->id }} })"
                                class="flex items-center gap-2 text-xs font-bold text-[var(--md-sys-color-primary)] transition-all hover:gap-3 px-3 py-1.5 rounded-full hover:bg-[var(--md-sys-color-primary-container)]/30"
                            >
                                <span>ادامه مطلب</span>
                                <span class="material-symbols-rounded text-base flip-rtl">arrow_left_alt</span>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        @else
            <div class="col-span-full flex flex-col items-center justify-center p-12 bg-[var(--md-sys-color-surface)]/50 border border-[var(--md-sys-color-outline-variant)]/20 rounded-2xl">
                <div class="w-16 h-16 rounded-2xl bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center mb-4">
                     <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-outline)] opacity-50">feed</span>
                </div>
                <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">هیچ پستی یافت نشد.</span>
            </div>
        @endif
        @endisland
    </div>

    <div class="mt-8 mb-12 flex justify-center">
        <button
            wire:click="loadMore" wire:island="posts"
            class="group px-5 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm hover:shadow-md hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] active:scale-95 transition-all duration-300 flex items-center gap-2"
        >
            <span class="text-xs font-bold">نمایش بیشتر</span>
            <span class="material-symbols-rounded text-xl group-hover:translate-y-0.5 transition-transform" wire:loading.remove target="loadMore">expand_more</span>
            <div class="w-4 h-4 border-2 border-[var(--md-sys-color-primary)] border-t-transparent rounded-full animate-spin" wire:loading target="loadMore"></div>
        </button>
    </div>
</section>
