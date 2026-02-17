@props(['media'])

<div class="grid grid-cols-{{ count($media) > 1 ? '2' : '1' }} gap-1 w-full h-full min-h-[160px] max-h-[300px]">
    @foreach($media as $path)
        <div class="relative overflow-hidden w-full h-full bg-gray-100">
            @php
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
            @endphp

            @if($isVideo)
                <video controls class="w-full h-full object-cover">
                    <source src="{{ Storage::url($path) }}" type="video/{{ $extension }}">
                </video>
            @else
                <img src="{{ Storage::url($path) }}"
                     class="w-full h-full object-cover"
                     loading="lazy"
                     decoding="async"
                     alt="Feed Media">
            @endif
        </div>
    @endforeach
</div>
