@props(['title' => 'حالت تمام صفحه'])

<button x-data="fullscreen()"
        @click="toggle()"
        class="group relative sm:flex w-10 h-10 active:scale-95 transition-all duration-200 items-center justify-center"
    {{ $attributes->merge(['class' => '']) }}>
            <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100"
                  x-text="isFullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
    <x-ui.modals.tooltip :text="$title" position="bottom" />
</button>
