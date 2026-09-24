@php
    $videos = collect(config('app.videos', []))->map(fn ($v) => asset($v))->values()->all();
@endphp
    <!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full antialiased" x-data="authBackground(@js($videos))">
<head>

    <x-dashboard.meta-tags/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body
    class="antialiased min-h-screen text-gray-900 dark:text-white selection:bg-[var(--md-sys-color-primary)]/30 selection:text-[var(--md-sys-color-primary)] overflow-hidden transition-colors duration-500">


<div class="fixed inset-0 z-0 bg-black overflow-hidden pointer-events-none h-screen">
    <div class="w-full h-full relative" x-show="useVideo" x-cloak>
        @for ($i = 0; $i < 2; $i++)
            <video x-ref="v{{ $i }}" muted playsinline preload="none" disablepictureinpicture
                   oncontextmenu="return false" controlslist="nodownload noplaybackrate"
                   class="absolute inset-0 w-full h-full object-cover object-center select-none transition-opacity duration-[1200ms] will-change-[opacity]"
                   :class="active==={{ $i }} ? 'opacity-100' : 'opacity-0'"
                   @timeupdate="check({{ $i }})" @ended="swap({{ $i }})"></video>
        @endfor
        <div class="absolute inset-0 bg-black pointer-events-none"
             :style="`opacity: ${(100 - videoBrightness) / 100}`"></div>
    </div>

    <div class="w-full h-full" x-show="!useVideo || !videos.length" x-cloak
         :style="`filter: brightness(${videoBrightness}%)`">
        <img
            src="{{ asset(config('app.background_image')) }}"
            :src="customImage || '{{ asset(config('app.background_image')) }}'"
            alt="Background"
            fetchpriority="high"
            decoding="async"
            class="w-full h-full object-cover will-change-transform animate-kenburns"
        >
    </div>

    <div

        class="absolute inset-0 bg-[var(--md-sys-color-primary)]/10 mix-blend-overlay transition-colors duration-500"></div>
    <div
        class="absolute inset-0 bg-gradient-to-l from-black/80 via-black/20 to-transparent dark:from-black/95 dark:via-black/50 dark:to-transparent"></div>
</div>

<div class="flex min-h-screen w-full flex-col lg:flex-row relative z-10">

    <div
        class="relative hidden lg:flex lg:w-[40%] h-full min-h-screen flex-col justify-end px-12 pb-24 order-1 group cursor-default animate-slide-in-right animate-delay-1750">
        <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-700 ease-out">
            <h1 class="text-6xl font-black text-white mb-4 drop-shadow-2xl tracking-tight opacity-90 group-hover:opacity-100 transition-opacity duration-500"
                title="{{ config('app.name_en') }}">
                {{ config('app.name') }}
            </h1>
            <h2 class="text-3xl font-bold text-[#FF7F6E] mb-3 drop-shadow-lg"
                title="{{ config('app.slogan_en') }}">
                {{ config('app.slogan') }}
            </h2>
            <p class="text-gray-200 text-base font-medium leading-relaxed max-w-md bg-black/20 p-4 rounded-xl border border-white/10 opacity-80 group-hover:opacity-100 transition-all duration-500"
               title="{{ config('app.organization_name_en') }}">
                {{ config('app.organization_name') }}
            </p>
            <x-ui.social-links size="w-9 h-9" icon-size="w-[18px] h-[18px]"
                                link-class="text-white/70 hover:text-white hover:bg-white/10"
                                class="mt-4 opacity-80 group-hover:opacity-100 transition-opacity duration-500"/>
        </div>
    </div>

    <div
        class="w-full  lg:flex-1 flex flex-col justify-center items-right p-4 sm:p-8 lg:p-16 xl:p-24 z-20 order-2 transition-all duration-500 relative min-h-screen">
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
                <button @click="useVideo = !useVideo; useVideo && initVideo()"
                        class="group relative w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all">
                    <span class="material-symbols-rounded text-[22px]" x-text="useVideo ? 'image' : 'movie'"></span>
                    <x-ui.modals.tooltip position="left">
                        <span x-text="useVideo ? 'توقف ویدیو (سیستم‌های ضعیف)' : 'پخش ویدیو'"></span>
                    </x-ui.modals.tooltip>
                </button>

                <button type="button"
                        x-data="{
                            playing: false, audio: null,
                            persist() { localStorage.setItem('auth-music', this.playing ? 'on' : 'off'); },
                            toggle() {
                                if (!this.audio) {
                                    this.audio = new Audio('{{ asset('audio/music/login/auth.mp3') }}');
                                    this.audio.loop = true;
                                }
                                if (this.playing) { this.audio.pause(); this.playing = false; }
                                else { this.audio.play().then(() => this.playing = true).catch(() => {}); }
                                this.persist();
                            },
                            init() {
                                if (localStorage.getItem('auth-music') !== 'on') return;
                                const resume = () => {
                                    document.removeEventListener('click', resume);
                                    document.removeEventListener('keydown', resume);
                                    if (localStorage.getItem('auth-music') === 'on' && !this.playing) this.toggle();
                                };
                                document.addEventListener('click', resume);
                                document.addEventListener('keydown', resume);
                            }
                        }"
                        @click="toggle()"
                        class="group relative w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all">
                    <span class="material-symbols-rounded text-[22px] transition-colors duration-200"
                          :class="playing ? 'animate-pulse text-[var(--md-sys-color-primary)]' : ''"
                          x-text="playing ? 'volume_up' : 'volume_off'"></span>
                    <x-ui.modals.tooltip position="left">
                        <span x-text="playing ? 'غیر فعال سازی صدا' : 'فعال سازی صدا'"></span>
                    </x-ui.modals.tooltip>
                </button>

                <div class="w-[1px] h-5 bg-gray-300 dark:bg-white/10"></div>

                <div x-data="{ openSettings: false }" class="relative flex-shrink-0">
                    <button @click="openSettings = !openSettings"
                            class="group relative w-10 h-10 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-[var(--md-sys-color-primary)] hover:dark:text-white hover:bg-gray-100/50 hover:dark:bg-white/10 transition-all">
                        <span class="material-symbols-rounded text-[22px]">tune</span>
                        <x-ui.modals.tooltip text="تنظیمات پس‌زمینه" position="bottom"/>
                    </button>
                    <div x-show="openSettings" @click.outside="openSettings = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         class="absolute top-14 right-0 p-5 rounded-2xl bg-white/90 dark:bg-[#1A1F2B]/90 border border-gray-200 dark:border-white/10 shadow-2xl flex flex-col gap-4 min-w-[250px] text-start">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ذخیره فقط در همین مرورگر — بدون ارسال به سرور</p>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-200">تصویر</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-show="customImage" x-cloak
                                   x-text="'ذخیره‌شده: ' + (customImageSize / 1048576).toFixed(1) + ' مگابایت'"></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <label class="px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/20 transition-colors">
                                    <span x-text="customImage ? 'جایگزینی' : 'انتخاب'"></span>
                                    <input type="file" accept="image/*" class="hidden" @change="pickMedia('image', $event)">
                                </label>
                                <button x-show="customImage" x-cloak @click="clearMedia('image')"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-500 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                        title="حذف تصویر شخصی">
                                    <span class="material-symbols-rounded text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-200">ویدیو</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-show="customVideoUrl" x-cloak
                                   x-text="'ذخیره‌شده: ' + (customVideoSize / 1048576).toFixed(1) + ' مگابایت'"></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <label class="px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/20 transition-colors">
                                    <span x-text="customVideoUrl ? 'جایگزینی' : 'انتخاب'"></span>
                                    <input type="file" accept="video/*" class="hidden" @change="pickMedia('video', $event)">
                                </label>
                                <button x-show="customVideoUrl" x-cloak @click="clearMedia('video')"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-500 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                        title="حذف ویدیوی شخصی">
                                    <span class="material-symbols-rounded text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">حداکثر حجم: تصویر ۵ / ویدیو ۲۰ مگابایت — انتخاب فایل جدید، قبلی را جایگزین می‌کند</p>
                        <p x-show="mediaNote" x-cloak x-text="mediaNote" class="text-xs font-bold text-red-500"></p>

                        <div class="h-px bg-gray-200 dark:bg-white/10"></div>

                        <label class="flex items-center justify-between cursor-pointer select-none py-1">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">تکرار پس از پایان</span>
                            <x-ui.forms.switch x-model="loopVideo"/>
                        </label>

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

<div class="hidden md:flex md:fixed md:top-8 md:left-8 z-30 items-center gap-3 px-2 py-1.5 rounded-2xl
            bg-white/70 dark:bg-zinc-900/70 border border-white/20 dark:border-white/10
            shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5 animate-slide-in-left animate-delay-1500"
     style="backdrop-filter: blur(16px);">

    <img src="{{ asset(config('app.company_logo', 'build/assets/img/logo.svg')) }}"
         alt="{{ config('app.organization_name_en') }}"
         title="{{ config('app.organization_name_en') }}"
         class="h-11 w-auto transition-transform duration-300 hover:scale-105"
         fetchpriority="low"
         decoding="async">
</div>

<script>
    document.addEventListener('alpine:init', () => {
        let dbPromise;
        const openDb = () => dbPromise ??= new Promise((res, rej) => {
            const r = indexedDB.open('auth-media', 1);
            r.onupgradeneeded = () => r.result.createObjectStore('media');
            r.onsuccess = () => res(r.result);
            r.onerror = () => rej(r.error);
        });
        const mediaGet = async key => {
            try {
                const db = await openDb();
                return await new Promise((res, rej) => {
                    const rq = db.transaction('media').objectStore('media').get(key);
                    rq.onsuccess = () => res(rq.result);
                    rq.onerror = () => rej(rq.error);
                });
            } catch { return null; }
        };
        const mediaPut = async (key, blob) => {
            const db = await openDb();
            await new Promise((res, rej) => {
                const tx = db.transaction('media', 'readwrite');
                tx.objectStore('media').put(blob, key);
                tx.oncomplete = res;
                tx.onerror = () => rej(tx.error);
            });
        };
        const mediaDel = async key => {
            const db = await openDb();
            await new Promise((res, rej) => {
                const tx = db.transaction('media', 'readwrite');
                tx.objectStore('media').delete(key);
                tx.oncomplete = res;
                tx.onerror = () => rej(tx.error);
            });
        };

        Alpine.data('authBackground', (videos = []) => ({
            theme: localStorage.getItem('user-theme') || 'default',
            videoBrightness: localStorage.getItem('video-brightness') || 60,
            useVideo: localStorage.getItem('use-video') !== 'false',
            loopVideo: localStorage.getItem('video-loop') !== 'false',
            videos,
            active: 0,
            curIdx: 0,
            nextIdx: 0,
            triggered: false,
            fade: 1.2,
            rate: 1,
            customImage: null,
            customImageSize: 0,
            customVideoUrl: null,
            customVideoSize: 0,
            mediaNote: '',
            mediaDefaults: null,

            init() {
                this.$watch('videoBrightness', v => localStorage.setItem('video-brightness', v));
                this.$watch('loopVideo', v => localStorage.setItem('video-loop', v));
                this.$watch('useVideo', v => {
                    localStorage.setItem('use-video', v);
                    if (!v) ['v0', 'v1'].forEach(r => this.$refs[r]?.pause());
                });
                this.loadMedia().finally(() => this.initVideo());
            },

            initVideo(revokeUrl = null) {
                if (!this.videos.length) { this.useVideo = false; return; }
                if (!this.useVideo) return;
                clearTimeout(this._t1);
                clearTimeout(this._t2);
                ['v0', 'v1'].forEach(r => this.$refs[r]?.pause());
                this.active = 0;
                this.triggered = false;
                this.curIdx = Math.floor(Math.random() * this.videos.length);
                this.$nextTick(() => {
                    const v = this.$refs.v0;
                    if (!v) return;
                    v.preload = 'auto';
                    v.src = this.videos[this.curIdx];
                    v.playbackRate = this.rate;
                    const start = () => {
                        if (!this.useVideo) return;
                        v.play().finally(() => { if (revokeUrl) URL.revokeObjectURL(revokeUrl); }).catch(() => {});
                        this.preroll(1);
                    };
                    const fail = () => {
                        this.useVideo = false;
                        if (revokeUrl) URL.revokeObjectURL(revokeUrl);
                    };
                    v.addEventListener('canplay', start, { once: true });
                    v.addEventListener('error', fail, { once: true });
                });
            },

            preroll(b) {
                const n = this.videos.length;
                if (n <= 1) {
                    this.nextIdx = this.curIdx;
                } else {
                    let idx;
                    do { idx = Math.floor(Math.random() * n); } while (idx === this.curIdx);
                    this.nextIdx = idx;
                }
                const el = this.$refs['v' + b];
                if (!el) return;
                el.preload = 'auto';
                el.src = this.videos[this.nextIdx];
            },

            check(i) {
                if (i !== this.active || this.triggered) return;
                const el = this.$refs['v' + i];
                if (!el.duration) return;
                const nxt = this.$refs['v' + (i ? 0 : 1)];
                if ((el.duration - el.currentTime) / el.playbackRate <= this.fade && nxt?.readyState >= 3) this.swap(i);
            },

            swap(i) {
                if (!this.loopVideo) return;
                if (this.triggered || i !== this.active) return;
                this.triggered = true;
                const next = i ? 0 : 1;
                const el = this.$refs['v' + next];
                el.playbackRate = this.rate;
                el.play().then(() => {
                    this.$refs['v' + i].pause();
                    this.active = next;
                    this.curIdx = this.nextIdx;
                    this._t1 = setTimeout(() => this.triggered = false, this.fade * 1000);
                    this._t2 = setTimeout(() => this.preroll(i), this.fade * 1000 + 50);
                }).catch(() => this.triggered = false);
            },

            async loadMedia() {
                this.mediaDefaults = this.videos.slice();
                const [img, vid] = await Promise.all([mediaGet('image'), mediaGet('video')]);
                if (img) { this.customImage = URL.createObjectURL(img); this.customImageSize = img.size; }
                if (vid) { this.customVideoUrl = URL.createObjectURL(vid); this.customVideoSize = vid.size; this.videos = [this.customVideoUrl]; }
            },

            flashMediaNote(msg) {
                clearTimeout(this._noteTimer);
                this.mediaNote = msg;
                this._noteTimer = setTimeout(() => this.mediaNote = '', 4000);
            },

            async pickMedia(kind, ev) {
                const f = ev.target.files[0];
                ev.target.value = '';
                if (!f) return;
                const cap = (kind === 'image' ? 5 : 20) * 1024 * 1024;
                if (f.size > cap) {
                    this.flashMediaNote(`حجم فایل بیش از حد مجاز است (حداکثر ${kind === 'image' ? '۵' : '۲۰'} مگابایت)`);
                    return;
                }
                try { await mediaPut(kind, f); } catch { this.flashMediaNote('فضای ذخیره‌سازی مرورگر کافی نیست'); return; }
                if (kind === 'image') {
                    if (this.customImage) URL.revokeObjectURL(this.customImage);
                    this.customImage = URL.createObjectURL(f);
                    this.customImageSize = f.size;
                    this.useVideo = false;
                } else {
                    const oldUrl = this.customVideoUrl;
                    this.customVideoUrl = URL.createObjectURL(f);
                    this.customVideoSize = f.size;
                    this.videos = [this.customVideoUrl];
                    this.useVideo = true;
                    this.initVideo(oldUrl);
                }
            },

            async clearMedia(kind) {
                try { await mediaDel(kind); } catch { this.flashMediaNote('حذف با خطا مواجه شد، دوباره تلاش کنید'); return; }
                if (kind === 'image') {
                    if (this.customImage) URL.revokeObjectURL(this.customImage);
                    this.customImage = null;
                    this.customImageSize = 0;
                } else {
                    const oldUrl = this.customVideoUrl;
                    this.customVideoUrl = null;
                    this.customVideoSize = 0;
                    this.videos = this.mediaDefaults;
                    this.initVideo(oldUrl);
                }
            },
        }));
    });
</script>
@livewireScripts
<x-service-worker/>
</body>
</html>
