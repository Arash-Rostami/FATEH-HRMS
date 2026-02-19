@php
    $transforms = [
        ['z' => 'z-20', 'rotate' => 'rotate-6', 'hover' => 'group-hover:-translate-x-12 group-hover:-rotate-12'],
        ['z' => 'z-10', 'rotate' => '-rotate-2', 'hover' => 'group-hover:translate-x-0 group-hover:rotate-3'],
        ['z' => 'z-0', 'rotate' => 'rotate-3', 'hover' => 'group-hover:translate-x-12 group-hover:rotate-12'],
    ];
    $paths = $photo->path ?? [];
    $visibleImages = array_slice($paths, 0, 3);
    $hiddenImages = array_slice($paths, 3);
    $hiddenImageCount = count($paths) - count($visibleImages);
@endphp

<div class="h-full w-full bg-[var(--md-sys-color-surface-container-low)] rounded-3xl p-6 flex flex-col shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden relative group transition-transform duration-300 hover:shadow-lg will-change-transform">
    {{-- Header --}}
    <div class="mb-4 shrink-0">
        <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $photo->title }}</h3>
        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">{{ jdate($photo->event_date)->format('%d %B %Y') }}</p>
    </div>

    {{-- Collage --}}
    <div class="flex-1 relative flex items-center justify-center min-h-[200px] w-full perspective-1000 py-8">
        @foreach($visibleImages as $index => $imagePath)
            @php $t = $transforms[$index] ?? ['z' => 'z-0', 'rotate' => '', 'hover' => '']; @endphp
            <a href="{{ asset($imagePath) }}"
               data-fancybox="gallery-{{ $photo->id }}"
               class="absolute w-40 h-40 md:w-56 md:h-56 rounded-2xl shadow-xl overflow-hidden border-4 border-[var(--md-sys-color-surface)] transition-transform duration-300 ease-out cursor-zoom-in will-change-transform {{ $t['z'] }} {{ $t['rotate'] }} {{ $t['hover'] }}"
            >
                <img src="{{ asset($imagePath) }}" alt="{{ $photo->title }}" class="w-full h-full object-cover" loading="lazy" decoding="async">
            </a>
        @endforeach

        @if($hiddenImageCount > 0)
            <div class="absolute bottom-4 right-4 z-30 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-full px-3 py-1 text-xs font-bold shadow-lg">
                +{{ $hiddenImageCount }}
            </div>
        @endif
    </div>

    {{-- Hidden Images for Fancybox --}}
    <div class="hidden">
        @foreach($hiddenImages as $imagePath)
            <a href="{{ asset($imagePath) }}" data-fancybox="gallery-{{ $photo->id }}"></a>
        @endforeach
    </div>

    {{-- Description --}}
    {{-- Optimized: Removed backdrop-blur-sm, used high opacity background instead for performance --}}
    <div class="mt-4 shrink-0 relative z-20 bg-[var(--md-sys-color-surface-container-low)]/95 rounded-xl p-2 border border-[var(--md-sys-color-outline-variant)]/10">
         <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-3">
            {{ strip_tags($photo->description) }}
        </p>
    </div>
</div>
