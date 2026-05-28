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
        <div class="flex flex-col items-start gap-1">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight truncate max-w-[200px]">
                {{ $photo->title }}
            </h3>
            <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                {{ jdate($photo->event_date)->format('%d %B %Y') }}
            </span>
        </div>
        <div
            class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shadow-sm">
            <span class="material-symbols-rounded text-base">photo_library</span>
        </div>
    </div>

    {{-- Collage (Original Logic Restored) --}}
    <div
        class="flex-1 relative flex items-center justify-center min-h-[140px] w-full perspective-1000 py-2">
        @foreach($visibleImages as $index => $imagePath)
            @php
                $t = $transforms[$index] ?? ['z' => 'z-0', 'rotate' => '', 'hover' => ''];
                $url = Storage::disk('public')->exists($imagePath) ? Storage::disk('public')->url($imagePath) : asset($imagePath);
            @endphp
            <a href="{{ $url }}"
               data-fancybox="gallery-{{ $photo->id }}"
               class="absolute w-40 h-40 md:w-56 md:h-56 rounded-xl shadow-md overflow-hidden border-4 border-[var(--md-sys-color-surface)] transition-all duration-500 ease-out cursor-zoom-in hover:z-30 hover:scale-105 hover:-translate-y-2 hover:shadow-2xl hover:brightness-105 {{ $t['z'] }} {{ $t['rotate'] }} {{ $t['hover'] }}"
            >
                <img src="{{ $url }}" alt="{{ $photo->title }}" class="w-full h-full object-cover cursor-pointer" loading="lazy">
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
    @if(!empty($photo->description))
        <div
            class="mt-auto shrink-0 relative z-20 bg-[var(--md-sys-color-surface-variant)]/30 border-t border-[var(--md-sys-color-outline-variant)]/10 p-4 mx-4 mb-4 rounded-xl">
            <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] line-clamp-3 text-justify">
                {{ strip_tags($photo->description) }}
            </p>
        </div>
    @endif
</div>
