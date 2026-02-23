<div
    class="flex flex-col h-full bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 relative group transition-all duration-300"
    :class="{
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
    <div class="flex items-center justify-between mb-4 px-5 pt-5 md:px-6 md:pt-6">
        <div class="flex flex-col items-start gap-1">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight">
                {{ $photo->title }}
            </h3>
            <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                {{ jdate($photo->event_date)->format('%d %B %Y') }}
            </span>
        </div>
        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shadow-sm">
            <span class="material-symbols-rounded text-base">photo_library</span>
        </div>
    </div>

    {{-- Body / Collage --}}
    <div class="flex-1 overflow-y-auto feed-scrollbar p-5 md:p-6 space-y-5 pb-24 relative">
        @php
            $visibleImages = array_slice($photo->path ?? [], 0, 4);
            $hiddenImages = array_slice($photo->path ?? [], 4);
            $hiddenCount = count($photo->path ?? []) - 4;
        @endphp

        <div class="grid grid-cols-2 gap-1.5 w-full min-h-[200px]">
            @foreach($visibleImages as $index => $imagePath)
                <a href="{{ asset($imagePath) }}"
                   data-fancybox="gallery-{{ $photo->id }}"
                   class="relative group/img overflow-hidden rounded-xl bg-[var(--md-sys-color-surface-variant)] aspect-square cursor-zoom-in border border-[var(--md-sys-color-outline-variant)]/10 shadow-sm"
                >
                    <img src="{{ asset($imagePath) }}"
                         alt="{{ $photo->title }}"
                         class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover/img:scale-110 group-hover/img:brightness-110"
                         loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-scrim,black)]/40 to-transparent opacity-0 group-hover/img:opacity-100 transition-opacity duration-300"></div>

                    @if($index === 3 && $hiddenCount > 0)
                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-sm">
                            <span class="text-white font-bold text-lg">+{{ $hiddenCount }}</span>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Hidden Images for Fancybox --}}
        <div class="hidden">
            @foreach($hiddenImages as $imagePath)
                <a href="{{ asset($imagePath) }}" data-fancybox="gallery-{{ $photo->id }}"></a>
            @endforeach
        </div>

        @if(!empty($photo->description))
            <div class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-right text-justify dir-rtl bg-[var(--md-sys-color-surface-variant)]/30 p-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/20">
                {{ strip_tags($photo->description) }}
            </div>
        @endif
    </div>
</div>
