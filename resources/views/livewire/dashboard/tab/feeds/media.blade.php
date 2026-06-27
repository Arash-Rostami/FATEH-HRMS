@props(['media' => []])

@php
    $grid   = $presenter->mediaGrid($media ?? []);
    $items  = $grid['items'];
    $images = $grid['images'];
    $cols   = $grid['cols'];
    $rows   = $grid['rows'];
    $total  = count($items);
@endphp

<div
    x-data="{ open: false, index: 0 }"
    class="grid grid-cols-{{ $cols }} gap-1.5 w-full overflow-hidden @if($total === 1) h-auto @elseif($rows > 1) grid-rows-{{ $rows }} h-[400px] @else h-[320px] @endif"
>
    @php $imgIndex = 0; @endphp

    @foreach($items as $url)
        @php
            $isImage = !isVideo($url);
            $myIndex = $isImage ? $imgIndex : null;
            if ($isImage) $imgIndex++;
            $cellClass = 'relative group overflow-hidden w-full h-full' . ($isImage ? ' cursor-zoom-in' : '');
        @endphp

        <div
            @if($isImage)@click="index = {{ $myIndex }}; open = true"@endif
        class="{{ $cellClass }}"
        >
            @if(isVideo($url))
                <video controls class="w-full h-full object-cover bg-black/90">
                    <source src="{{ $url }}" type="video/{{ getFileExtension($url) }}">
                </video>
            @else
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

    @if(count($images) > 0)
        <template x-teleport="body">
            <div
                x-cloak
                x-show="open"
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-[99998] bg-[var(--md-sys-color-primary)]/90 animate-lightbox-in"
                @keydown.escape.window="open = false"
                @click.self="open = false"
            >
                <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-8">
                    <div class="relative w-full max-w-[min(96vw,1600px)]">
                        @foreach($images as $i => $url)
                            <img
                                src="{{ $url }}"
                                x-show="index === {{ $i }}"
                                alt="Feed Media"
                                class="max-h-[92vh] w-full select-none object-contain cursor-pointer"
                            >
                        @endforeach

                        @if(count($images) > 1)
                            <button
                                @click="index = (index - 1 + {{ count($images) }}) % {{ count($images) }}"
                                class="absolute right-2 top-1/2 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-xl bg-black/55 text-white shadow-lg transition hover:scale-105 hover:bg-black/75"
                            >
                                <span class="material-symbols-rounded">chevron_right</span>
                            </button>
                            <button
                                @click="index = (index + 1) % {{ count($images) }}"
                                class="absolute left-2 top-1/2 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-xl bg-black/55 text-white shadow-lg transition hover:scale-105 hover:bg-black/75"
                            >
                                <span class="material-symbols-rounded">chevron_left</span>
                            </button>
                        @endif

                        <button
                            @click="open = false"
                            class="absolute right-3 top-3 flex h-11 w-11 items-center justify-center rounded-xl bg-black/55 text-white shadow-lg transition hover:scale-105 hover:bg-black/75"
                        >
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
