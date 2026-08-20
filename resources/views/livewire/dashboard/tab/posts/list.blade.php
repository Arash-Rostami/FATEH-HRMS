<section class="flex-1 min-w-0 h-full overflow-y-auto custom-scrollbar pr-1 pl-1 pb-20">
    <div class="flex items-center gap-3 mb-4 px-1">
        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-base font-fill">feed</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">تازه‌ترین‌ها</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    </div>

    <div class="space-y-3">
        @foreach($this->pins as $pin)
            @php
                $isFresh = $pin->isFresh();
                $isSeen = $this->seenIds->has($pin->id);
            @endphp
            <article wire:key="post-list-pin-{{ $pin->id }}" data-rf="posts-{{ $pin->id }}"
                @click="$dispatch('select-post', { id: {{ $pin->id }} })"
                class="flex items-center gap-4 p-3 bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-xl transition-all duration-300 border border-[var(--md-sys-color-secondary)]/30 hover:border-[var(--md-sys-color-primary)]/30 group cursor-pointer shadow-sm hover:shadow-md">
                <div class="shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-[var(--md-sys-color-surface-variant)] relative">
                    <img src="{{ $pin->image_url }}" alt="{{ superClean($pin->title, 200) }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    <span class="absolute top-1 right-1 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] text-[9px] font-bold">
                        <span class="material-symbols-rounded text-[11px] leading-none">keep</span>
                        سنجاق
                    </span>
                </div>
                <div class="flex-grow min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors">{{ superClean($pin->title, 100) }}</h4>
                        @if($isFresh)
                            <span class="material-symbols-rounded text-[14px] {{ $isSeen ? 'text-[var(--md-sys-color-tertiary)]' : 'text-[var(--md-sys-color-primary)]' }}">{{ $isSeen ? 'check_circle' : 'new_releases' }}</span>
                        @endif
                    </div>
                    <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface-variant)] line-clamp-1 text-justify mb-2">{{ superClean($pin->body, 120) }}</p>
                    <div class="flex items-center gap-3 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="inline-flex items-center gap-1"><span class="material-symbols-rounded text-[14px] leading-none">calendar_today</span><span dir="rtl">{{ toJalali($pin->created_at, 'j F Y') }}</span></span>
                        <span class="inline-flex items-center gap-1"><span class="material-symbols-rounded text-[14px] leading-none">person</span>{{ $pin->user?->name ?? 'ادمین' }}</span>
                    </div>
                </div>
                <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors flip-rtl shrink-0">arrow_left_alt</span>
            </article>
        @endforeach

        @if($this->posts->isNotEmpty())
            @foreach($this->posts as $post)
                @php
                    $isFresh = $post->isFresh();
                    $isSeen = $this->seenIds->has($post->id);
                @endphp
                <article wire:key="post-list-{{ $post->id }}" data-rf="posts-{{ $post->id }}"
                    @click="$dispatch('select-post', { id: {{ $post->id }} })"
                    class="flex items-center gap-4 p-3 bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-xl transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-primary)]/30 group cursor-pointer shadow-sm hover:shadow-md">
                    <div class="shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-[var(--md-sys-color-surface-variant)]">
                        <img src="{{ $post->image_url }}" alt="{{ superClean($post->title, 200) }}" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors">{{ superClean($post->title, 100) }}</h4>
                            @if($isFresh)
                                <span class="material-symbols-rounded text-[14px] {{ $isSeen ? 'text-[var(--md-sys-color-tertiary)]' : 'text-[var(--md-sys-color-primary)]' }}">{{ $isSeen ? 'check_circle' : 'new_releases' }}</span>
                            @endif
                        </div>
                        <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface-variant)] line-clamp-1 text-justify mb-2">{{ superClean($post->body, 120) }}</p>
                        <div class="flex items-center gap-3 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="inline-flex items-center gap-1"><span class="material-symbols-rounded text-[14px] leading-none">calendar_today</span><span dir="rtl">{{ toJalali($post->created_at, 'j F Y') }}</span></span>
                            <span class="inline-flex items-center gap-1"><span class="material-symbols-rounded text-[14px] leading-none">person</span>{{ $post->user?->name ?? 'ادمین' }}</span>
                        </div>
                    </div>
                    <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors flip-rtl shrink-0">arrow_left_alt</span>
                </article>
            @endforeach
        @elseif($this->pins->isEmpty())
            <div>
                <x-ui.empty icon="feed" title="هیچ اعلانی یافت نشد." variant="list" />
            </div>
        @endif
    </div>

    <div class="mt-8 mb-20 flex justify-center">
        <x-ui.buttons.load-more
            action="loadMore"
            text="نمایش بیشتر"
            loading-text="در حال دریافت..."
            icon="expand_more"
            icon-size="text-xl"
            class="text-xs font-bold px-5 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm hover:shadow-md hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] active:scale-95 duration-300"
        />
    </div>
</section>