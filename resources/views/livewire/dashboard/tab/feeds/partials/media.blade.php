@props(['media' => []])

<div class="grid grid-cols-{{ count($media ?? []) > 1 ? '2' : '1' }} gap-1 w-full h-full min-h-[200px] max-h-[400px]">
    @foreach($media ?? [] as $path)
        @if(!empty($path))
            <div class="relative group overflow-hidden w-full h-full cursor-zoom-in bg-[var(--md-sys-color-surface-variant)]">
                @php
                    $extension = strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION));
                    $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
                @endphp

                @if($isVideo)
                    <video controls class="w-full h-full object-cover">
                        <source src="{{ Storage::url($path) }}" type="video/{{ $extension }}">
                    </video>
                @else
                    <img src="{{ Storage::url($path) }}"
                         class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-110 group-hover:brightness-110"
                         loading="lazy"
                         decoding="async"
                         alt="Feed Media">
                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-scrim,black)]/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                @endif
            </div>
        @endif
    @endforeach
</div>
