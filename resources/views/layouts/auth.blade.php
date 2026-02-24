<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full antialiased"
      x-data="{
          theme: localStorage.getItem('user-theme') || 'default',
          isDark: document.documentElement.classList.contains('dark'),
          videoBrightness: localStorage.getItem('video-brightness') || 60,
          useVideo: localStorage.getItem('use-video') !== 'false',
          videoIndex: 0,
          videos: [
              '{{ Vite::asset('resources/assets/video/mining-01.mp4') }}',
              '{{ Vite::asset('resources/assets/video/mining-02.mp4') }}'
          ],
          playNext() {
            const nextIndex = (this.videoIndex + 1) % this.videos.length;
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = this.videos[nextIndex];
            document.head.appendChild(link);

            this.videoIndex = nextIndex;
            this.$nextTick(() => {
                this.$refs.bgVideo.load();
                this.$refs.bgVideo.play();
            });
        }
      }"
      x-init="
          $watch('videoBrightness', val => localStorage.setItem('video-brightness', val));
          $watch('useVideo', val => localStorage.setItem('use-video', val));
      ">
<head>
    <x-dashboard.meta-tags/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen text-gray-900 dark:text-white selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-primary)] overflow-hidden transition-colors duration-500">

<div class="fixed inset-0 z-0 bg-black overflow-hidden pointer-events-none h-screen">
    <template x-if="useVideo">
        <video x-ref="bgVideo" autoplay muted playsinline preload="metadata"
               class="w-full h-full object-cover scale-110"
               :style="`filter: brightness(${videoBrightness}%)`"
               @ended="playNext">
            <source :src="videos[videoIndex]" type="video/mp4">
        </video>
    </template>

    <template x-if="!useVideo">
        <div class="w-full h-full scale-110" :style="`filter: brightness(${videoBrightness}%)`">
            <img src="{{ Vite::asset('resources/assets/img/mining.jpg') }}"
                 alt="Background"
                 fetchpriority="high"
                 decoding="async"
                 class="w-full h-full object-cover">
        </div>
    </template>

    <div class="absolute inset-0 bg-[var(--md-sys-color-primary)]/10 mix-blend-overlay transition-colors duration-500"></div>
    <div class="absolute inset-0 bg-gradient-to-l from-black/80 via-black/20 to-transparent dark:from-black/95 dark:via-black/50 dark:to-transparent"></div>
</div>

<div class="flex min-h-screen w-full flex-col lg:flex-row relative z-10">
    <div class="relative hidden lg:flex lg:w-[40%] h-full min-h-screen flex-col justify-end px-12 pb-24 order-1 group cursor-default">
        <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-700 ease-out">
            <h1 class="text-6xl font-black text-white mb-4 drop-shadow-2xl tracking-tight opacity-90 group-hover:opacity-100 transition-opacity duration-500">اینـتـرا</h1>
            <h2 class="text-3xl font-bold text-[var(--md-sys-color-primary)] mb-3 drop-shadow-lg">خـانـه دیجیتـال مـا</h2>
            <p class="text-gray-200 text-base font-medium leading-relaxed max-w-md bg-black/20 p-4 rounded-xl border border-white/10 opacity-80 group-hover:opacity-100 transition-all duration-500">ابـزاری کـه شمـا در دفتـر کـار بـه آن نیـاز داریـد</p>
        </div>
    </div>

    <div class="w-full lg:flex-1 flex flex-col justify-center items-right p-4 sm:p-8 lg:p-16 xl:p-24 z-20 order-2 transition-all duration-500 relative min-h-screen">
        <div class="md:fixed md:top-8 md:right-8 z-50 flex items-center gap-2" x-cloak>
            <div class="glass-panel p-1.5 rounded-xl flex items-center gap-1.5 bg-white/80 dark:bg-black/50 opacity-[0.6] border border-gray-200 dark:border-white/10 shadow-2xl transition-all hover:bg-white hover:dark:bg-black/70">

                <button @click="window.ThemeManager.toggleMode($event); isDark = !isDark" class="w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all" :title="isDark ? 'تغییر به روز' : 'تغییر به شب'">
                    <span class="material-symbols-rounded text-[22px]" x-text="isDark ? 'dark_mode' : 'light_mode'"></span>
                </button>

                <div class="w-[1px] h-5 bg-gray-300 dark:bg-white/10"></div>

                <div x-data="{ open: false }" class="relative flex-shrink-0">
                    <button @click="open = !open" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all" title="رنگ‌بندی">
                        <span class="material-symbols-rounded text-[22px]">palette</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" class="absolute top-14 left-0 p-3 rounded-2xl bg-white/90 dark:bg-[#1A1F2B]/90 border border-gray-200 dark:border-white/10 shadow-2xl flex flex-col gap-3 min-w-[40px]">
                        <template x-for="color in $store.theme.colors" :key="color.name">
                            <button @click="$store.theme.set(color.name); open = false" class="w-7 h-7 rounded-xl border-2 border-transparent hover:border-gray-400 dark:hover:border-white/50 hover:scale-110 transition-all shadow-sm" :style="'background: ' + color.color"></button>
                        </template>
                    </div>
                </div>

                <div class="w-[1px] h-5 bg-gray-300 dark:bg-white/10"></div>

                <div x-data="{ openVideo: false }" class="relative flex-shrink-0">
                    <button @click="openVideo = !openVideo" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all" title="تنظیمات ویدیو">
                        <span class="material-symbols-rounded text-[22px]">tune</span>
                    </button>
                    <div x-show="openVideo" @click.outside="openVideo = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" class="absolute top-14 right-0 p-5 rounded-2xl bg-white/90 dark:bg-[#1A1F2B]/90 border border-gray-200 dark:border-white/10 shadow-2xl flex flex-col gap-4 min-w-[220px]">
                        <label class="text-sm font-bold text-gray-700 dark:text-gray-200 flex justify-between items-center">
                            <span>روشنایی پس‌زمینه</span>
                            <span class="bg-gray-100 dark:bg-black/30 px-2 py-1 rounded-md text-xs" x-text="videoBrightness + '%'"></span>
                        </label>
                        <input type="range" x-model="videoBrightness" min="10" max="100" class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-black/50 accent-[var(--md-sys-color-primary)]">
                    </div>
                </div>

                <div class="w-[1px] h-5 bg-gray-300 dark:bg-white/10"></div>

                <button @click="useVideo = !useVideo" class="w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all" :title="useVideo ? 'توقف ویدیو (سیستم‌های ضعیف)' : 'پخش ویدیو'">
                    <span class="material-symbols-rounded text-[22px]" x-text="useVideo ? 'image' : 'movie'"></span>
                </button>

            </div>
        </div>
        <div class="w-full max-w-[650px] relative z-30 flex flex-col justify-center items-center my-auto opacity-[0.9]">
            <div class="group w-full">{{ $slot }}</div>
        </div>
    </div>
</div>

@livewireScripts
<x-service-worker />
</body>
</html>
