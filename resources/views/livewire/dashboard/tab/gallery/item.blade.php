@php
    $transforms = [
        ['z' => 'z-20', 'rotate' => 'rotate-6', 'hover' => 'group-hover:-translate-x-12 group-hover:-rotate-12'],
        ['z' => 'z-10', 'rotate' => '-rotate-2', 'hover' => 'group-hover:translate-x-0 group-hover:rotate-3'],
        ['z' => 'z-0', 'rotate' => 'rotate-3', 'hover' => 'group-hover:translate-x-12 group-hover:rotate-12'],
    ];
    $paths = $photo->image_urls;
    $visibleImages = array_slice($paths, 0, 3);
    $hiddenImages = array_slice($paths, 3);
    $hiddenImageCount = count($paths) - count($visibleImages);
    $scope = $presenter?->scopeMeta($photo) ?? ['icon' => 'photo_library', 'label' => ''];
@endphp

<div

    class="flex flex-col h-2/3 bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 relative group transition-all duration-300"
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
    <div class="flex items-center justify-between mb-2 px-4 pt-4 shrink-0">
        <div class="flex flex-col items-start gap-1 min-w-0">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight truncate max-w-[200px]">
                {{ $photo->title }}
            </h3>
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

    {{-- Collage (Original Logic Restored) --}}
    <div
        class="flex-1 relative flex items-center justify-center min-h-[140px] w-full perspective-1000 py-2">
        @foreach($visibleImages as $index => $url)
            @php
                $t = $transforms[$index] ?? ['z' => 'z-0', 'rotate' => '', 'hover' => ''];
            @endphp
            <a href="{{ $url }}"
               data-fancybox="gallery-{{ $photo->id }}"
               class="collage-item absolute w-40 h-40 md:w-56 md:h-56 rounded-xl overflow-hidden border-4 border-[var(--md-sys-color-surface)] cursor-pointer {{ $t['z'] }} {{ $t['rotate'] }} {{ $t['hover'] }}"
            >
                <img src="{{ $url }}" alt="{{ $photo->title }}" class="w-full h-full object-cover" loading="lazy">
            </a>
        @endforeach

        @if($hiddenImageCount > 0)
            <div
                class="absolute bottom-4 right-4 z-30 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-full px-3 py-1 text-xs font-bold shadow-lg">
                +{{ $hiddenImageCount }}
            </div>
        @endif
    </div>

    {{-- Hidden Images for Fancybox --}}
    <div class="hidden">
        @foreach($hiddenImages as $url)
            <a href="{{ $url }}"
               data-fancybox="gallery-{{ $photo->id }}"></a>
        @endforeach
    </div>

    @if(!empty($photo->description))
        <div class="mt-auto shrink-0 relative z-20 px-4 pb-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/20">
            <p class="text-[var(--md-sys-color-on-surface-variant)] text-xs line-clamp-2 leading-relaxed font-light opacity-80">
                {{ Str::limit(strip_tags($photo->description), 100) }}
            </p>
        </div>
    @endif
</div>
