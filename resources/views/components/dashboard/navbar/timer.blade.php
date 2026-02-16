<button @click="toggleMode()"
        title="تغییر نمایش زمان"
        class="w-full h-full flex items-center justify-center gap-3 outline-none cursor-pointer group">

    <span
        class="material-symbols-rounded text-[20px] opacity-80 transition-transform duration-500 group-hover:rotate-180"
        x-text="mode === 'fa' ? 'schedule' : 'public'">
    </span>

    <div class="flex flex-col items-center leading-none" :dir="mode === 'fa' ? 'rtl' : 'ltr'">
        <span class="text-[13px] font-bold font-mono tracking-wider" x-text="time"></span>
        <span class="text-[10px] opacity-60 font-medium" x-text="date"></span>
    </div>
</button>
