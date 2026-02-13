<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Stat Cards -->
    <div class="glass-panel p-6 rounded-2xl flex flex-col gap-2 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-[var(--md-sys-color-primary)]/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] z-10">کاربران فعال</span>
        <span class="text-4xl font-bold text-[var(--md-sys-color-on-surface)] z-10">1,234</span>
        <div class="flex items-center gap-1 text-sm text-emerald-500 z-10">
            <span class="material-symbols-rounded text-base">trending_up</span>
            <span>+12% نسبت به ماه قبل</span>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl flex flex-col gap-2 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-[var(--md-sys-color-tertiary)]/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] z-10">درآمد امروز</span>
        <span class="text-4xl font-bold text-[var(--md-sys-color-on-surface)] z-10">۵.۴M</span>
        <div class="flex items-center gap-1 text-sm text-[var(--md-sys-color-primary)] z-10">
            <span class="material-symbols-rounded text-base">payments</span>
            <span>تومان</span>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl flex flex-col gap-2 relative overflow-hidden group">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-[var(--md-sys-color-error)]/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
        <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] z-10">تیکت‌های باز</span>
        <span class="text-4xl font-bold text-[var(--md-sys-color-on-surface)] z-10">12</span>
        <div class="flex items-center gap-1 text-sm text-amber-500 z-10">
            <span class="material-symbols-rounded text-base">warning</span>
            <span>نیازمند رسیدگی</span>
        </div>
    </div>

    <!-- Main Chart Section (Placeholder) -->
    <div class="glass-panel p-6 rounded-2xl col-span-1 md:col-span-2 lg:col-span-2 h-80 flex flex-col gap-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">آمار بازدید</h3>
            <div class="flex gap-2">
                <button class="md3-btn-outlined h-8 px-3 text-sm rounded-lg border-0 bg-[var(--md-sys-color-surface-variant)]/30">هفتگی</button>
                <button class="md3-btn-outlined h-8 px-3 text-sm rounded-lg border-0 hover:bg-[var(--md-sys-color-surface-variant)]/30">ماهانه</button>
            </div>
        </div>
        <div class="flex-1 bg-[var(--md-sys-color-surface-variant)]/10 rounded-xl flex items-center justify-center border border-[var(--md-sys-color-outline-variant)]/10 relative overflow-hidden">
             <!-- Fake Chart Visuals -->
             <div class="absolute bottom-0 left-0 right-0 h-1/2 flex items-end justify-around px-4 pb-4 gap-2">
                <div class="w-full bg-[var(--md-sys-color-primary)]/30 h-[40%] rounded-t-sm"></div>
                <div class="w-full bg-[var(--md-sys-color-primary)]/40 h-[60%] rounded-t-sm"></div>
                <div class="w-full bg-[var(--md-sys-color-primary)]/50 h-[30%] rounded-t-sm"></div>
                <div class="w-full bg-[var(--md-sys-color-primary)]/60 h-[80%] rounded-t-sm"></div>
                <div class="w-full bg-[var(--md-sys-color-primary)]/70 h-[50%] rounded-t-sm"></div>
                <div class="w-full bg-[var(--md-sys-color-primary)]/80 h-[90%] rounded-t-sm"></div>
                <div class="w-full bg-[var(--md-sys-color-primary)] h-[75%] rounded-t-sm"></div>
             </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="glass-panel p-6 rounded-2xl h-80 flex flex-col gap-4">
        <div class="flex justify-between items-center">
             <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">فعالیت‌های اخیر</h3>
             <button class="text-sm text-[var(--md-sys-color-primary)] font-medium">مشاهده همه</button>
        </div>
        <div class="flex-1 overflow-y-auto space-y-3 pr-2 custom-scrollbar">
            @foreach(range(1, 5) as $i)
            <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)]/30 transition-colors cursor-pointer group">
                <div class="w-10 h-10 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-rounded">person</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">ورود کاربر جدید</p>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">۲ دقیقه پیش</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
