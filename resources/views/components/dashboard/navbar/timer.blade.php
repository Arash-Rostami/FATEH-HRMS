<button @click="toggleMode()"
        title="تغییر نمایش زمان"
        class="group flex items-center gap-3 px-4 py-2 rounded-xl
                           hover:bg-[var(--md-sys-color-on-primary)]/10
                           cursor-pointer overflow-hidden relative shadow-sm">

    <div
        class="absolute inset-0 bg-gradient-to-r from-transparent via-[var(--md-sys-color-on-primary)]/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000 ease-in-out"></div>

    <span
        class="material-symbols-rounded text-[20px] opacity-80 transition-transform duration-500 group-hover:rotate-180"
        x-text="mode === 'fa' ? 'schedule' : 'public'">
                </span>

    <div class="flex flex-col items-center leading-none" :dir="mode === 'fa' ? 'rtl' : 'ltr'">
        <span class="text-[13px] font-bold font-mono tracking-wider" x-text="time"></span>
        <span class="text-[10px] opacity-60 font-medium" x-text="date"></span>
    </div>
</button>
