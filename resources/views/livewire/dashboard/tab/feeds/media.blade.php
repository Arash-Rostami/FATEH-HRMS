@props(['media' => []])

<div class="grid grid-cols-{{ count($media ?? []) > 1 ? '2' : '1' }} gap-1.5 w-full h-full min-h-[200px] max-h-[400px]">
    @foreach($media ?? [] as $path)
        @if(!empty($path))
            <div class="relative group overflow-hidden w-full h-full cursor-zoom-in">
                @if(isVideo($path))
                    <video controls class="w-full h-full object-cover bg-black/90">
                        <source src="{{ Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : asset($path) }}" type="video/{{ getFileExtension($path) }}">
                    </video>
                @else
                    <img src="{{ Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : asset($path) }}"
                         class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-110 group-hover:brightness-110"
                         loading="lazy"
                         decoding="async"
                         alt="Feed Media">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                @endif
            </div>
        @endif
    @endforeach
</div>
