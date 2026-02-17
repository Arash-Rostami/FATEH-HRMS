@props(['media'])

<div class="grid grid-cols-{{ count($media) > 1 ? '2' : '1' }} gap-1.5 w-full h-full min-h-[200px] max-h-[400px]">
    @foreach($media as $path)
        <div class="relative group overflow-hidden w-full h-full cursor-zoom-in">
            @php
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
            @endphp

            @if($isVideo)
                <video controls class="w-full h-full object-cover bg-black/90">
                    <source src="{{ Storage::url($path) }}" type="video/{{ $extension }}">
                </video>
            @else
                <img src="{{ Storage::url($path) }}"
                     class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-110 group-hover:brightness-110"
                     loading="lazy"
                     alt="Feed Media">
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            @endif
        </div>
    @endforeach
</div>
