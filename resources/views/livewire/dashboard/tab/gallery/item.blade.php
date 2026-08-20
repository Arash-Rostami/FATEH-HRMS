@php
    $c = $presenter->collageData($photo);
    $visibleImages = $c['visibleImages'];
    $hiddenImages = $c['hiddenImages'];
    $hiddenImageCount = $c['hiddenImageCount'];
    $scope = $presenter?->scopeMeta($photo) ?? ['icon' => 'photo_library', 'label' => ''];
@endphp

<div
    data-rf="gallery-{{ $photo->id }}"
    x-data="{ titleExpanded: false, captionExpanded: false }"
    class="flex flex-col bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 relative group transition-all duration-300"
    :class="{
        'h-2/3': !titleExpanded && !captionExpanded,
        'min-h-[66%]': titleExpanded || captionExpanded,
        'shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] ring-1 ring-[var(--md-sys-color-primary)]/30': activeId == {{ $photo->id }}
    }">

    {{-- Active Stripe Indicator --}}
    <div
        x-show="activeId == {{ $photo->id }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-x-0"
        x-transition:enter-end="opacity-100 scale-x-100"
        class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] z-20"
        style="box-shadow: 0 2px 8px color-mix(in srgb, var(--md-sys-color-primary) 60%, transparent);">
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-2 px-4 pt-4 shrink-0">
        <div class="flex flex-col items-start gap-1 min-w-0">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight max-w-[200px] overflow-hidden transition-[max-height] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
                :style="titleExpanded ? ('max-height:' + $el.scrollHeight + 'px') : 'max-height: 2.4rem'">
                {{ $photo->title }}
            </h3>
            @if(mb_strlen($photo->title ?? '') > 40)
                <button type="button" @click="titleExpanded = !titleExpanded" class="text-[var(--md-sys-color-primary)] text-[11px] font-medium flex items-center gap-0.5 select-none rounded-md px-2 py-0.5 -mx-2 transition-colors duration-200 hover:bg-[var(--md-sys-color-primary-container)]/50">
                    <span class="material-symbols-rounded text-[12px] transition-transform duration-300" :class="titleExpanded ? 'rotate-180' : ''">expand_more</span>
                    <span x-text="titleExpanded ? 'بستن' : 'مشاهده بیشتر'"></span>
                </button>
            @endif
            <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                {{ toJalali($photo->event_date, 'j F Y') }}
            </span>
        </div>
        <div
            title="{{ $scope['label'] }}"
            class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shadow-sm cursor-help">
            <span class="material-symbols-rounded text-base">{{ $scope['icon'] }}</span>
        </div>
    </div>

    {{-- Collage --}}
    <div
        class="flex-1 relative flex items-center justify-center min-h-[168px] md:min-h-[232px] w-full perspective-1000 py-2">
        @foreach($visibleImages as $index => $url)
            @php
                $cell = $presenter->collageCellData($index, $url);
                $t = $cell['transform'];
                $video = $cell['isVideo'];
            @endphp
            <a href="{{ $url }}"
               @if($video) data-type="html5video" @endif
               data-fancybox="gallery-{{ $photo->id }}"
               class="collage-item absolute w-40 h-40 md:w-56 md:h-56 rounded-xl overflow-hidden border-4 border-[var(--md-sys-color-surface)] cursor-pointer {{ $t['z'] }} {{ $t['rotate'] }} {{ $t['hover'] }}"
            >
                @if($video)
                    <div
                        x-data="{ duration: null }"
                        @mouseenter="galleryPreviewPlay($refs.vid)"
                        @mouseleave="galleryPreviewStop($refs.vid)"
                        class="relative w-full h-full bg-black"
                    >
                        <video
                            x-ref="vid"
                            preload="metadata"
                            muted
                            playsinline
                            src="{{ $url }}#t=0.1"
                            class="w-full h-full object-cover"
                            @loadedmetadata="duration = $el.duration"
                        ></video>
                        <div class="absolute inset-0 bg-gradient-to-b from-black/35 via-transparent to-black/55 pointer-events-none"></div>
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <span class="w-12 h-12 rounded-full bg-[var(--md-sys-color-primary)]/90 text-[var(--md-sys-color-on-primary)] flex items-center justify-center shadow-lg ring-4 ring-white/15 transition-transform duration-300 group-hover:scale-110">
                                <span class="material-symbols-rounded text-2xl" style="margin-inline-start: 2px">play_arrow</span>
                            </span>
                        </div>
                        <span class="absolute top-2 left-2 z-10 px-2 py-0.5 rounded-md bg-black/55 text-white text-[10px] font-bold tracking-wide backdrop-blur-sm">
                            {{ __('resources/gallery/strings.fields.video_badge') }}
                        </span>
                        <span
                            x-show="duration"
                            x-cloak
                            class="absolute bottom-2 right-2 z-10 px-2 py-0.5 rounded-md bg-black/55 text-white text-[10px] font-medium tabular-nums backdrop-blur-sm"
                            x-text="duration ? formatDuration(duration) : ''"
                        ></span>
                    </div>
                @else
                    <img src="{{ $url }}" alt="{{ $photo->title }}" class="w-full h-full object-cover" loading="lazy">
                @endif
            </a>
        @endforeach

        @if($hiddenImageCount > 0)
            <div
                class="absolute bottom-4 right-4 z-30 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-full px-3 py-1 text-xs font-bold shadow-lg">
                +{{ $hiddenImageCount }}
            </div>
        @endif
    </div>

    {{-- Hidden items for Fancybox --}}
    <div class="hidden">
        @foreach($hiddenImages as $url)
            <a href="{{ $url }}"
               @if(isVideo($url)) data-type="html5video" @endif
               data-fancybox="gallery-{{ $photo->id }}"></a>
        @endforeach
    </div>

    @if(!empty($photo->description))
        @php $captionText = $presenter->captionText($photo); @endphp
        <div class="mt-auto shrink-0 relative z-20 px-4 pb-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/20">
            <div class="relative overflow-hidden transition-[max-height] duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
                 :style="captionExpanded ? ('max-height:' + $el.scrollHeight + 'px') : 'max-height: 2.6rem'">
                <p class="text-[var(--md-sys-color-on-surface-variant)] text-xs leading-relaxed font-light opacity-80">{{ $captionText }}</p>
                @if(mb_strlen($captionText) > 80)
                    <div x-show="!captionExpanded" x-transition.opacity.duration.200ms class="absolute bottom-0 inset-x-0 h-6 pointer-events-none bg-gradient-to-t from-[var(--md-sys-color-surface)] to-transparent"></div>
                @endif
            </div>
            @if(mb_strlen($captionText) > 80)
                <button type="button" @click="captionExpanded = !captionExpanded" class="text-[var(--md-sys-color-primary)] text-[11px] font-medium mt-2 flex items-center gap-0.5 select-none rounded-md px-2 py-0.5 -mx-2 transition-colors duration-200 hover:bg-[var(--md-sys-color-primary-container)]/50">
                    <span class="material-symbols-rounded text-[12px] transition-transform duration-300" :class="captionExpanded ? 'rotate-180' : ''">expand_more</span>
                    <span x-text="captionExpanded ? 'بستن' : 'مشاهده بیشتر'"></span>
                </button>
            @endif
        </div>
    @endif
</div>