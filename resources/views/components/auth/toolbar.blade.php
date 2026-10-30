@php
    $btn = 'group relative w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all';
@endphp

<div
    class="fixed top-3 right-3 md:top-8 md:right-8 z-50 flex items-center gap-2 animate-slide-in-right animate-delay-1500"
    x-data="{
        card: !!document.querySelector('[data-auth-card]'),
        desktop: window.matchMedia('(min-width: 768px)').matches,
        minimized: false,
        init() {
            window.matchMedia('(min-width: 768px)').addEventListener('change', e => this.desktop = e.matches);
        },
        get shown() { return this.desktop || this.minimized || !this.card; }
    }"
    x-show="shown"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    @card-minimize.window="minimized = true"
    @card-restore.window="minimized = false"
    x-cloak>
    <div
        class="glass-panel p-1.5 rounded-xl flex items-center gap-1.5 border border-gray-200 dark:border-white/10 shadow-2xl transition-all">
        <button @click="useVideo = !useVideo; useVideo && initVideo()" class="{{ $btn }}">
            <span class="material-symbols-rounded text-[22px]" x-text="useVideo ? 'image' : 'movie'"></span>
            <x-ui.modals.tooltip position="left">
                <span x-text="useVideo ? 'توقف ویدیو (سیستم‌های ضعیف)' : 'پخش ویدیو'"></span>
            </x-ui.modals.tooltip>
        </button>

        <button type="button" x-data="authMusic('{{ asset('audio/music/login/auth.mp3') }}')" @click="toggle()"
                class="{{ $btn }}">
            <span class="material-symbols-rounded text-[22px] transition-colors duration-200"
                  :class="playing ? 'animate-pulse text-[var(--md-sys-color-primary)]' : ''"
                  x-text="playing ? 'volume_up' : 'volume_off'"></span>
            <x-ui.modals.tooltip position="left">
                <span x-text="playing ? 'غیر فعال سازی صدا' : 'فعال سازی صدا'"></span>
            </x-ui.modals.tooltip>
        </button>

        <div class="w-[1px] h-5 bg-gray-300 dark:bg-white/10"></div>

        <div x-data="{ openSettings: false }" class="relative flex-shrink-0">
            <button @click="openSettings = !openSettings" class="{{ $btn }}">
                <span class="material-symbols-rounded text-[22px]">tune</span>
                <x-ui.modals.tooltip text="تنظیمات پس‌زمینه" position="bottom"/>
            </button>
            <div x-show="openSettings" @click.outside="openSettings = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="absolute top-14 right-0 p-5 rounded-2xl bg-white/90 dark:bg-[#1A1F2B]/90 border border-gray-200 dark:border-white/10 shadow-2xl flex flex-col gap-4 min-w-[250px] text-start">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ذخیره فقط در همین مرورگر — بدون ارسال به سرور</p>
                <x-auth.toolbar.media-row kind="image"/>
                <x-auth.toolbar.media-row kind="video"/>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">حداکثر حجم: تصویر ۵ / ویدیو ۲۰ مگابایت — انتخاب فایل جدید، قبلی را جایگزین می‌کند</p>
                <p x-show="mediaNote" x-cloak x-text="mediaNote" class="text-xs font-bold text-red-500"></p>

                <div class="h-px bg-gray-200 dark:bg-white/10"></div>

                <label class="flex items-center justify-between cursor-pointer select-none py-1">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200">تکرار پس از پایان</span>
                    <x-ui.forms.switch x-model="loopVideo"/>
                </label>

                <label class="text-sm font-bold text-gray-700 dark:text-gray-200 flex justify-between items-center">
                    <span>روشنایی پس‌زمینه</span>
                    <span class="bg-gray-100 dark:bg-black/30 px-2 py-1 rounded-md text-xs"
                          x-text="videoBrightness + '%'"></span>
                </label>
                <input type="range" x-model="videoBrightness" min="10" max="100"
                       class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-black/50 accent-[var(--md-sys-color-primary)]">
            </div>
        </div>

        <div class="w-[1px] h-5 bg-gray-300 dark:bg-white/10"></div>

        <x-dashboard.navbars.top.palette/>
    </div>
</div>
