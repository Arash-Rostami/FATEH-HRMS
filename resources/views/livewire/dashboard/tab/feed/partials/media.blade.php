@props(['paths', 'feed'])

@if(!empty($paths))
    <div class="relative w-full overflow-hidden rounded-2xl mt-4 aspect-[4/3] group">
        @if(count($paths) === 1)
            @php $path = $paths[0]; $ext = pathinfo($path, PATHINFO_EXTENSION); @endphp
            @if(in_array(strtolower($ext), ['mp4', 'webm', 'ogg']))
                <video src="{{ $path }}" controls class="w-full h-full object-cover"></video>
            @else
                <img src="{{ $path }}" alt="Feed Media" loading="lazy" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
            @endif
        @else
            {{-- Simple grid for multiple images --}}
            <div class="grid grid-cols-2 gap-0.5 h-full">
                @foreach(array_slice($paths, 0, 4) as $index => $path)
                    <div class="relative w-full h-full overflow-hidden">
                        <img src="{{ $path }}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                        @if($index === 3 && count($paths) > 4)
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <span class="text-white font-bold text-xl">+{{ count($paths) - 4 }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
