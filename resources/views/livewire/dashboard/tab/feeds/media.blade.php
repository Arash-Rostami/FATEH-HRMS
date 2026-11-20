@props(['media' => []])

@php
    $grid   = $presenter->mediaGrid($media ?? []);
    $items  = $grid['items'];
    $cols   = $grid['cols'];
    $rows   = $grid['rows'];
    $total  = count($items);
    $group  = 'feed-media-' . crc32(implode('|', $items));
@endphp

<div
    class="grid grid-cols-{{ $cols }} gap-1.5 w-full overflow-hidden @if($total === 1) h-auto @elseif($rows > 1) grid-rows-{{ $rows }} h-[400px] @else h-[320px] @endif"
>
    @foreach($items as $url)
        <div class="{{ $presenter->mediaCellData($url)['cellClass'] }}">
            @if(isVideo($url))
                <video controls class="w-full h-full object-cover bg-black/90">
                    <source src="{{ $url }}" type="video/{{ getFileExtension($url) }}">
                </video>
            @else
                <a href="{{ $url }}" data-type="image" data-fancybox="{{ $group }}" class="absolute inset-0 z-20" aria-label="Feed Media"></a>
                <img src="{{ $url }}"
                     class="@if($total === 1) w-full h-auto max-h-[70vh] object-contain @else w-full h-full object-cover @endif transition-transform duration-700 ease-in-out group-hover:scale-110 group-hover:brightness-110"
                     loading="lazy"
                     decoding="async"
                     alt="Feed Media">
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <span class="absolute right-2 top-2 z-10 flex h-8 w-8 items-center justify-center rounded-lg bg-black/40 text-white/90 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <span class="material-symbols-rounded text-[18px]">open_in_full</span>
                </span>
            @endif
        </div>
    @endforeach
</div>
