@props(['maxHeight' => 'max-h-[calc(100svh-10rem)]', 'gap' => 'gap-4'])

<div {{ $attributes->merge(['class' => "relative w-full max-w-[88rem] mx-auto h-screen overflow-x-visible overflow-y-clip flex flex-col $gap $maxHeight"]) }}>
    {{ $slot }}
</div>
