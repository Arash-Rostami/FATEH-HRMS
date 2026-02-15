<header
    class="sticky top-0 z-50 bg-[var(--header-bg)] backdrop-blur-md px-4 lg:px-8 flex justify-between items-center h-[60px] lg:h-[80px] transition-all duration-300 shrink-0
    border-b-4 border-[var(--header-border-color)]">

    <!-- LEFT: Logo -->
    <div class="flex items-center gap-4">
        <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-coral-500 to-salmon-400 rounded-full blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
            <img src="{{ Vite::asset('resources/assets/img/logo.png') }}"
                 alt="Fateh Logo"
                 class="animate-pop relative h-[32px] lg:h-[48px] w-auto transform transition-transform duration-500 hover:scale-105">
        </div>

        <!-- CENTER: Creative Timer (Added) -->
        <div class="hidden lg:flex items-center absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"
             x-data="{
                time: '',
                date: '',
                mode: localStorage.getItem('timer-mode') || 'fa',
                init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                },
                toggleMode() {
                    this.mode = this.mode === 'fa' ? 'en' : 'fa';
                    localStorage.setItem('timer-mode', this.mode);
                    this.updateTime();
                },
                updateTime() {
                    const now = new Date();

                    if (this.mode === 'fa') {
                        // Persian Date & Time
                        const dateOptions = { year: 'numeric', month: 'long', day: 'numeric', calendar: 'persian', numberingSystem: 'arab' };
                        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, numberingSystem: 'arab' };

                        this.date = new Intl.DateTimeFormat('fa-IR', dateOptions).format(now);
                        this.time = new Intl.DateTimeFormat('fa-IR', timeOptions).format(now);
                    } else {
                        // English Date & Time
                        const dateOptions = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };

                        this.date = new Intl.DateTimeFormat('en-US', dateOptions).format(now);
                        this.time = new Intl.DateTimeFormat('en-US', timeOptions).format(now);
                    }
                }
             }"
             x-cloak>

            <button @click="toggleMode()"
                    class="group relative flex items-center gap-3 px-4 py-2 rounded-2xl bg-[var(--md-sys-color-surface-container)]/30 border border-[var(--md-sys-color-outline-variant)]/30 backdrop-blur-md shadow-sm transition-all duration-300 hover:bg-[var(--md-sys-color-surface-container)]/50 hover:shadow-[var(--md-sys-color-primary)]/20 hover:scale-[1.02] active:scale-95 cursor-pointer">

                <!-- Icon Container -->
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] flex items-center justify-center shadow-lg shadow-[var(--md-sys-color-primary)]/30 group-hover:rotate-12 transition-transform duration-500">
                    <span class="material-symbols-rounded text-white text-[18px]" x-text="mode === 'fa' ? 'schedule' : 'public'"></span>
                </div>

                <!-- Time & Date -->
                <div class="flex flex-col items-start gap-0.5" :dir="mode === 'fa' ? 'rtl' : 'ltr'">
                    <span class="text-sm font-black tracking-wider text-[var(--md-sys-color-on-surface)] group-hover:text-[var(--md-sys-color-primary)] transition-colors duration-300 font-mono" x-text="time"></span>
                    <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wide opacity-80" x-text="date"></span>
                </div>

                <!-- Tooltip (Optional) -->
                <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                    <span class="text-[10px] bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] px-2 py-1 rounded-md shadow-lg whitespace-nowrap">
                        <span x-text="mode === 'fa' ? 'Switch to English' : 'تغییر به فارسی'"></span>
                    </span>
                </div>
            </button>
        </div>
    </div>

    <!-- RIGHT: User Profile -->
    <div class="flex flex-col items-end justify-center space-y-0.5">
        <h1 class="hidden sm:block font-bold tracking-widest text-white/95 "
            dir="rtl">
            اینترا، <span class="text-[#FF7F6E]">خانه دیجیتال ما</span>
        </h1>
        <div class="flex items-center gap-2 py-1 px-3 rounded-full bg-white/5 border border-white/10"
             dir="rtl">
            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
            <span
                class="text-[10px] lg:text-[11px] font-medium text-gray-400 leading-none truncate max-w-[150px] lg:max-w-none">
                        سید علی عزیز
            </span>
        </div>
    </div>
</header>
