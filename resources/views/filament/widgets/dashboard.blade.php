<div class="flex flex-wrap items-center gap-2 w-full px-2 py-2 min-h-14"
     dir="rtl">

    {{-- TYPESETTER --}}
    <div
        x-data="{
            text: '',
            quotes: @js(quotes()),
            quoteIndex: 0,
            charIndex: 0,
            isDeleting: false,
            typeSpeed: 60,
            deleteSpeed: 15,
            delayAfterType: 4000,
            delayAfterDelete: 500,
            shuffle(arr) {
                const a = [...arr];
                for (let i = a.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [a[i], a[j]] = [a[j], a[i]];
                }
                return a;
            },
            type() {
                const currentQuote = this.quotes[this.quoteIndex] || '';
                if (this.isDeleting) {
                    this.text = currentQuote.substring(0, this.charIndex - 1);
                    this.charIndex--;
                } else {
                    this.text = currentQuote.substring(0, this.charIndex + 1);
                    this.charIndex++;
                }
                let nextSpeed = this.isDeleting ? this.deleteSpeed : this.typeSpeed;
                if (!this.isDeleting && this.text === currentQuote) {
                    nextSpeed = this.delayAfterType;
                    this.isDeleting = true;
                } else if (this.isDeleting && this.text === '') {
                    this.isDeleting = false;
                    this.quoteIndex = (this.quoteIndex + 1) % this.quotes.length;
                    nextSpeed = this.delayAfterDelete;
                }
                setTimeout(() => this.type(), nextSpeed);
            }
        }"
        x-init="quotes = shuffle(quotes); type()"
        class="hidden md:relative md:flex items-center gap-0 flex-[1_1_160px] min-w-0 px-2.5 overflow-hidden" dir="rtl"
    >
        <h1 class="relative z-10 text-[var(--md-sys-color-primary)] animate-pulse flex-shrink-0">✦</h1>
        <div class="relative z-10 w-px h-[18px] bg-[var(--md-sys-color-primary)]/20 mx-2 flex-shrink-0"></div>

        <div
            class="text-sm md:text-base font-semibold text-[var(--md-sys-color-on-surface-variant)] tracking-wide flex items-center"
            dir="rtl">
            <span x-text="text"></span>
            <span class="w-[3px] h-4 mx-1 bg-[var(--md-sys-color-primary)] animate-pulse"></span>
        </div>
    </div>

    @if(request()->is('*admin*'))
        {{-- CLOCK --}}
        <div
            x-data="{
        hours: '۰۰',
        minutes: '۰۰',
        seconds: '۰۰',
        tick: false,
        toPersian(n) {
            return String(n).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
        },
        init() {
            this.update();
            setInterval(() => this.update(), 250);
        },
        update() {
            const now = new Date();
            const s = this.toPersian(String(now.getSeconds()).padStart(2, '0'));
            if (s !== this.seconds) { this.seconds = s; this.tick = !this.tick; }
            this.hours   = this.toPersian(String(now.getHours()).padStart(2, '0'));
            this.minutes = this.toPersian(String(now.getMinutes()).padStart(2, '0'));
        }
    }"
            class="fixed left-2 flex items-stretch select-none overflow-hidden rounded-xl w-auto sm:w-auto border border-[var(--md-sys-color-primary)]/[0.18] bg-[var(--md-sys-color-primary-container)] flex-shrink-0 sm:flex-shrink-0"
            dir="ltr">

            <div class="absolute inset-0 pointer-events-none opacity-[0.06] z-0"
                 style="background-image: radial-gradient(circle, var(--md-sys-color-primary) 1px, transparent 1px); background-size: 16px 16px;"></div>

            <div class="relative z-10 flex flex-col items-center justify-center gap-[2px] px-[10px] py-[10px]">
        <span
            class="font-mono text-[13px] font-bold tracking-[0.05em] tabular-nums leading-none text-[var(--md-sys-color-on-primary-container)]"
            x-text="hours"></span>
                <span
                    class="text-[8px] font-semibold tracking-[0.14em] uppercase text-[var(--md-sys-color-on-primary-container)]/45">ساعت</span>
            </div>

            <div class="relative z-10 flex flex-col items-center justify-center gap-[3px] px-[1px] pb-[2px]">
                <div
                    class="w-[2.5px] h-[2.5px] rounded-full bg-[var(--md-sys-color-primary)]/70 transition-opacity duration-75"
                    :class="tick ? 'opacity-100' : 'opacity-20'"></div>
                <div
                    class="w-[2.5px] h-[2.5px] rounded-full bg-[var(--md-sys-color-primary)]/70 transition-opacity duration-75"
                    :class="tick ? 'opacity-100' : 'opacity-20'"></div>
            </div>

            <div class="relative z-10 flex flex-col items-center justify-center gap-[2px] px-[10px] py-[10px]">
        <span
            class="font-mono text-[13px] font-bold tracking-[0.05em] tabular-nums leading-none text-[var(--md-sys-color-on-primary-container)]"
            x-text="minutes"></span>
                <span
                    class="text-[8px] font-semibold tracking-[0.14em] uppercase text-[var(--md-sys-color-on-primary-container)]/45">دقیقه</span>
            </div>

            <div class="relative z-10 w-px self-stretch bg-[var(--md-sys-color-primary)]/[0.15]"></div>

            <div
                class="relative z-10 flex flex-col items-center justify-center gap-[2px] px-[10px] py-[10px] bg-[var(--md-sys-color-primary)] min-w-[38px]">
                <div class="relative h-[13px] w-full flex items-center justify-center overflow-hidden">
                    <template x-for="sec in [seconds]" :key="sec">
                <span
                    x-text="sec"
                    x-transition:enter="transition ease-[cubic-bezier(0.22,1,0.36,1)] duration-[180ms]"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-[0.93]"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    class="font-mono text-[13px] font-bold tracking-[0.05em] tabular-nums leading-none text-white absolute"
                ></span>
                    </template>
                </div>
                <span class="text-[8px] font-semibold tracking-[0.14em] uppercase text-white/50">ثانیه</span>
            </div>
        </div>
    @endif

</div>
