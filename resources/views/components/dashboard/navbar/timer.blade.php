@props(['title' => 'تغییر نمایش زمان'])

<button @click="toggleMode()"
        class="group relative w-full h-full flex items-center justify-center gap-3 outline-none cursor-pointer"
    {{ $attributes->merge(['class' => '']) }}>

    <span
        class="material-symbols-rounded text-[20px] opacity-80 transition-transform duration-500 group-hover:rotate-180"
        x-text="mode === 'fa' ? 'schedule' : 'public'">
    </span>

    <div class="flex flex-col items-center leading-none" :dir="mode === 'fa' ? 'rtl' : 'ltr'">
        <span class="text-[13px] text-amber-300 tracking-wider" x-text="time"></span>
        <span class="text-[10px] opacity-60 font-medium" x-text="date"></span>
    </div>

    <x-dashboard.tooltip :text="$title" position="bottom" />
</button>
