<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full antialiased"
      x-data="{
          theme: localStorage.getItem('user-theme') || 'default',
          isDark: document.documentElement.classList.contains('dark'),
          videoBrightness: localStorage.getItem('video-brightness') || 60,
          useVideo: localStorage.getItem('use-video') !== 'false',
          videoIndex: 0,
          videos: [
                '{{ asset('build/assets/video/mining-01.mp4') }}',
                '{{ asset('build/assets/video/mining-02.mp4') }}',
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
    {{--    '{{ asset('build/assets/video/1.mp4') }}',--}}
    {{--    '{{ asset('build/assets/video/2.mp4') }}',--}}
    {{--    '{{ asset('build/assets/video/3.mp4') }}',--}}
    {{--    '{{ asset('build/assets/video/4.mp4') }}',--}}
    {{--    '{{ asset('build/assets/video/5.mp4') }}',--}}
    {{--    '{{ asset('build/assets/video/6.mp4') }}',--}}


    <x-dashboard.meta-tags/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body
    class="antialiased min-h-screen text-gray-900 dark:text-white selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-primary)] overflow-hidden transition-colors duration-500">


<div class="fixed inset-0 z-0 bg-black overflow-hidden pointer-events-none h-screen">
    <template x-if="useVideo">
        <video x-ref="bgVideo" autoplay muted playsinline disablepictureinpicture preload="metadata" oncontextmenu="return false" controlslist="nodownload noplaybackrate"
               class="w-full h-full object-cover scale-110 select-none"
               :style="`filter: brightness(${videoBrightness}%)`"
               @ended="playNext">
            <source :src="videos[videoIndex]" type="video/mp4">
        </video>
    </template>

    <template x-if="!useVideo">
        <div class="w-full h-full"
             :style="`filter: brightness(${videoBrightness}%)`">
            <img src="{{ asset('build/assets/img/mining.webp') }}"
                 alt="Background"
                 fetchpriority="high"
                 decoding="async"
                 class="w-full md:w-2/3 h-full object-cover animate-kenburns-infinite">
        </div>
    </template>

    <div
        class="absolute inset-0 bg-[var(--md-sys-color-primary)]/10 mix-blend-overlay transition-colors duration-500"></div>
    <div
        class="absolute inset-0 bg-gradient-to-l from-black/80 via-black/20 to-transparent dark:from-black/95 dark:via-black/50 dark:to-transparent"></div>
</div>

<div class="flex min-h-screen w-full flex-col lg:flex-row relative z-10">

    <div
        class="relative hidden lg:flex lg:w-[40%] h-full min-h-screen flex-col justify-end px-12 pb-24 order-1 group cursor-default animate-slide-in-right animate-delay-1750">
        <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-700 ease-out">
            <h1 class="text-6xl font-black text-white mb-4 drop-shadow-2xl tracking-tight opacity-90 group-hover:opacity-100 transition-opacity duration-500">
                اینترا</h1>
            <h2 class="text-3xl font-bold text-[#FF7F6E] mb-3 drop-shadow-lg">خانه دیجیتال سازمان ما</h2>
            <p class="text-gray-200 text-base font-medium leading-relaxed max-w-md bg-black/20 p-4 rounded-xl border border-white/10 opacity-80 group-hover:opacity-100 transition-all duration-500">
                شرکت توسعه معادن و صنایع معدنی فاتح</p>
        </div>
    </div>

    <div
        class="w-full  lg:flex-1 flex flex-col justify-center items-right p-4 sm:p-8 lg:p-16 xl:p-24 z-20 order-2 transition-all duration-500 relative min-h-screen">
        <div class="md:fixed md:top-8 md:right-8 z-50 flex items-center gap-2 md:animate-slide-in-right md:animate-delay-1500" x-cloak>
            <div
                class="glass-panel p-1.5 rounded-xl flex items-center gap-1.5 bg-white/80 dark:bg-black/50 opacity-[0.6] border border-gray-200 dark:border-white/10 shadow-2xl transition-all hover:bg-white hover:dark:bg-black/70">
                <button @click="useVideo = !useVideo"
                        class="group relative w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all">
                    <span class="material-symbols-rounded text-[22px]" x-text="useVideo ? 'image' : 'movie'"></span>
                    <x-ui.modals.tooltip position="left">
                        <span x-text="useVideo ? 'توقف ویدیو (سیستم‌های ضعیف)' : 'پخش ویدیو'"></span>
                    </x-ui.modals.tooltip>
                </button>

                <div class="w-[1px] h-5 bg-gray-300 dark:bg-white/10"></div>

                <div x-data="{ openVideo: false }"
                     class="relative flex-shrink-0">
                    <button @click="openVideo = !openVideo"
                            class="group relative w-10 h-10 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all">
                        <span class="material-symbols-rounded text-[22px]">tune</span>
                        <x-ui.modals.tooltip text="تنظیمات ویدیو" position="bottom"/>
                    </button>
                    <div x-show="openVideo" @click.outside="openVideo = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         class="absolute top-14 right-0 p-5 rounded-2xl bg-white/90 dark:bg-[#1A1F2B]/90 border border-gray-200 dark:border-white/10 shadow-2xl flex flex-col gap-4 min-w-[220px]">
                        <label
                            class="text-sm font-bold text-gray-700 dark:text-gray-200 flex justify-between items-center">
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
        <div class="w-full max-w-[650px] relative z-30 flex flex-col justify-center items-center my-auto opacity-[0.9]">
            <div class="group w-full">{{ $slot }}</div>
        </div>
    </div>
</div>

<div class="hidden md:flex md:fixed md:top-8 md:left-8 z-30 items-center gap-2.5 px-3.5 py-2 rounded-2xl shadow-xl ring-1 ring-inset ring-white/10 bg-black/55 dark:bg-black/70 animate-slide-in-left animate-delay-1500 hover:-translate-y-0.5 transition-all duration-300"
     style="backdrop-filter:blur(12px);">
    <img src="{{ asset('build/assets/img/logo.png') }}" alt="{{ config('app.name', 'INTERRA') }}" class="h-12 w-auto rounded-lg" fetchpriority="low" decoding="async">
</div>

@livewireScripts
<x-service-worker/>
</body>
</html>
