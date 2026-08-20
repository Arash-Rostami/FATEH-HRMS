@if($this->photos->isNotEmpty())
    <div x-show="month && visibleCount === 0" x-cloak
         class="flex flex-col items-center justify-center gap-3 py-16 text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-5xl opacity-40">filter_alt_off</span>
        <p class="text-sm font-medium opacity-80">موردی در این ماه یافت نشد</p>
    </div>

    <div class="columns-2 sm:columns-3 md:columns-4 lg:columns-5 gap-3 [column-fill:balance]">
        @foreach($this->photos as $photo)
            @php
                $w = $presenter->wallCardData($photo);
                $lead = $w['lead'];
                $rest = $w['rest'];
                $monthKey = $w['monthKey'];
                $dateLabel = $w['dateLabel'];
                $extraCount = $w['extraCount'];
            @endphp
            <div wire:key="wall-{{ $photo->id }}"
                 data-photo-id="{{ $photo->id }}"
                 x-show="!month || month === @js($monthKey)"
                 class="mb-3 break-inside-avoid relative group/wall rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm bg-[var(--md-sys-color-surface)] ring-1 ring-transparent hover:ring-[var(--md-sys-color-primary)]/30 transition-all duration-300">
                @if($lead)
                    <a href="{{ $lead }}" data-fancybox="gallery-{{ $photo->id }}" class="block relative overflow-hidden">
                        <img src="{{ $lead }}" alt="{{ $photo->title }}" class="w-full h-auto object-cover transition-transform duration-500 group-hover/wall:scale-105" loading="lazy">
                        @if($extraCount > 1)
                            <span class="absolute top-2 left-2 z-10 flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-[var(--md-sys-color-surface)]/90 text-[var(--md-sys-color-on-surface-variant)] text-[10px] font-bold shadow-sm backdrop-blur">
                                <span class="material-symbols-rounded text-[12px]">photo_library</span>
                                <span>+{{ convertToPersian($extraCount - 1) }}</span>
                            </span>
                        @endif
                        @if($photo->department)
                            <span class="absolute top-2 right-2 z-10 px-2 py-0.5 rounded-full bg-[var(--md-sys-color-surface)]/90 text-[var(--md-sys-color-on-surface-variant)] text-[10px] font-medium shadow-sm backdrop-blur opacity-0 group-hover/wall:opacity-100 transition-opacity duration-300">{{ $photo->department?->name }}</span>
                        @endif
                    </a>
                    @foreach($rest as $url)
                        <a href="{{ $url }}" data-fancybox="gallery-{{ $photo->id }}" class="hidden"></a>
                    @endforeach
                @else
                    <div class="w-full aspect-square flex items-center justify-center bg-[var(--md-sys-color-surface-variant)]/40">
                        <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-on-surface-variant)] opacity-40">photo_library</span>
                    </div>
                @endif
                <div class="p-2 space-y-0.5">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] line-clamp-1">{{ $photo->title }}</p>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $dateLabel }}</p>
                </div>
            </div>
        @endforeach
    </div>

    @if($this->hasMorePages)
        <div class="flex justify-center py-6">
            <x-ui.buttons.load-more
                action="loadMore"
                text="بارگذاری بیشتر"
                loading-text="در حال دریافت..."
                icon="expand_more"
                class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
            />
        </div>
    @endif
@else
    <div class="w-full h-full">
        <x-ui.empty icon="photo_library" title="گالری هنوز خالی است" description="هنوز هیچ محتوایی بارگذاری نشده است." variant="list" :fill="true"/>
    </div>
@endif