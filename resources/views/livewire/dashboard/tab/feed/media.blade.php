@props(['media'])

<div class="grid grid-cols-{{ count($media) > 1 ? '2' : '1' }} gap-2 w-full">
    @foreach($media as $path)
        <div class="relative group overflow-hidden rounded-xl aspect-square shadow-sm hover:shadow-md transition-shadow">
            @php
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
            @endphp

            @if($isVideo)
                <video controls class="w-full h-full object-cover rounded-xl bg-black">
                    <source src="{{ Storage::url($path) }}" type="video/{{ $extension }}">
                </video>
            @else
                <img src="{{ Storage::url($path) }}"
                     class="w-full h-full object-cover rounded-xl transition-transform duration-500 group-hover:scale-105"
                     loading="lazy"
                     alt="Feed Media">
            @endif
        </div>
    @endforeach
</div>
