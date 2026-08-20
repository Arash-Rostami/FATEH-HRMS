@if($this->feeds->isNotEmpty())
    <div x-show="month && visibleCount === 0" x-cloak
         class="flex flex-col items-center justify-center gap-3 py-16 text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-5xl opacity-40">filter_alt_off</span>
        <p class="text-sm font-medium opacity-80">موردی در این ماه یافت نشد</p>
    </div>

    <div class="columns-1 sm:columns-2 lg:columns-3 gap-4 [column-fill:balance]">
        @foreach($this->feeds as $feed)
            @php
                $m = $presenter->magazineData($feed);
                $monthKey = $m['monthKey'];
                $dateLabel = $m['dateLabel'];
                $relLabel = $m['relLabel'];
                $media = $m['media'];
                $lead = $m['lead'];
                $snippet = $m['snippet'];
                $flags = $m['flags'];
                $commentCount = $m['commentCount'];
                $reactionCount = $m['reactionCount'];
                $hasEngagement = $m['hasEngagement'];
                $barClass = $m['barClass'];
            @endphp
            <div wire:key="mag-{{ $feed->id }}"
                 data-feed="{{ $feed->id }}"
                 data-feed-id="{{ $feed->id }}"
                 x-show="!month || month === @js($monthKey)"
                 @click="focusFeed(@js($feed->id))"
                 class="mb-4 break-inside-avoid cursor-pointer rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] shadow-sm hover:shadow-md hover:border-[var(--md-sys-color-primary)]/40 hover:-translate-y-0.5 transition-all duration-300 group/mag">
                <div class="h-1 {{ $barClass }}"></div>
                @if(!empty($media) && $lead)
                    <div class="relative overflow-hidden">
                        <img src="{{ $lead }}" alt="" class="w-full h-auto object-cover transition-transform duration-500 group-hover/mag:scale-105" loading="lazy">
                        @if($feed->category)
                            <span class="absolute top-2 right-2 text-[10px] font-medium px-2 py-1 rounded-full bg-[var(--md-sys-color-surface)]/90 text-[var(--md-sys-color-on-surface)] shadow-sm backdrop-blur">
                                {{ $presenter->categoryEmoji($feed) }} {{ $feed->category }}
                            </span>
                        @endif
                    </div>
                @endif
                <div class="p-4 space-y-2.5">
                    @if($snippet !== '')
                        <p class="text-sm leading-7 text-[var(--md-sys-color-on-surface)] line-clamp-4 text-justify" dir="rtl">{!! $snippet !!}</p>
                    @endif
                    @if($hasEngagement)
                        <div class="flex items-center gap-3 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                            @if($commentCount > 0)
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[14px]">chat_bubble_outline</span>
                                    <span class="font-medium">{{ convertToPersian($commentCount) }}</span>
                                </span>
                            @endif
                            @if($reactionCount > 0)
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[14px]">favorite</span>
                                    <span class="font-medium">{{ convertToPersian($reactionCount) }}</span>
                                </span>
                            @endif
                            @if($flags['isPoll'])
                                <span class="flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                    <span class="material-symbols-rounded text-[14px]">poll</span>
                                    <span class="font-medium">نظرسنجی</span>
                                </span>
                            @endif
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/15">
                        <div class="flex items-center gap-2 min-w-0">
                            <img src="{{ $presenter->avatarUrl($feed->user) }}" alt="" class="w-6 h-6 rounded-full object-cover ring-1 ring-[var(--md-sys-color-outline-variant)]">
                            <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">{{ $feed->user?->name ?? 'کاربر ناشناس' }}</span>
                        </div>
                        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] shrink-0" title="{{ $dateLabel }}">{{ $relLabel }}</span>
                    </div>
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
    <div class="w-full h-full flex items-center justify-center px-8">
        <x-ui.empty icon="feed" title="هیچ خبری برای نمایش وجود ندارد" description="هنوز هیچ پستی در فید منتشر نشده است." variant="welcome" />
    </div>
@endif