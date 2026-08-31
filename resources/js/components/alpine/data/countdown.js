export default function countdown({dateIso, messages, mood, confetti}) {
    const targetMs = Date.parse(dateIso);
    return {
        days: '۰۰',
        hours: '۰۰',
        minutes: '۰۰',
        seconds: '۰۰',
        tickIndex: 0,
        reachedZero: false,
        confettiFired: false,
        confettiInstance: null,
        reducedMotion: false,
        noTransition: false,
        intervalId: null,
        tickerId: null,
        transitionTimeout: null,
        zeroTimeouts: [],
        remaining: 0,
        messages: messages || [],
        mood: mood === 'mourning' ? 'mourning' : 'happy',
        confetti: confetti !== false,

        init() {
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this.remaining = this.computeRemaining();
            this.update();

            const ready = () => {
                this.confettiInstance = this.createConfetti();
                setTimeout(() => {
                    if (this.remaining > 0 && !this.reducedMotion) this.burstAppear();
                    else if (this.remaining <= 0 && this.reachedZero) this.fireZeroCelebration();
                }, 1000);
            };

            if (this.confetti) {
                if (window.confetti) {
                    ready();
                } else {
                    const s = document.createElement('script');
                    s.src = '/js/lib/confetti.browser.min.js';
                    s.onload = ready;
                    document.head.appendChild(s);
                }
            }

            if (this.remaining > 0) {
                this.intervalId = setInterval(() => this.tick(), 1000);
            } else {
                this.reachedZero = true;
            }

            if (this.messages.length > 1) {
                this.tickerId = setInterval(() => this.nextTick(), 3000);
            }
        },

        tick() {
            this.remaining = this.computeRemaining();
            this.update();

            if (this.remaining <= 0 && !this.reachedZero) {
                this.reachedZero = true;
                clearInterval(this.intervalId);
                this.fireZeroCelebration();
            }
        },

        computeRemaining() {
            return Number.isFinite(targetMs) ? Math.max(0, Math.floor((targetMs - Date.now()) / 1000)) : 0;
        },

        fireZeroCelebration() {
            if (this.confettiFired || this.reducedMotion) return;
            this.celebrateZero();
            this.confettiFired = true;
        },

        update() {
            const d = Math.floor(this.remaining / 86400);
            const h = Math.floor((this.remaining % 86400) / 3600);
            const m = Math.floor((this.remaining % 3600) / 60);
            const s = this.remaining % 60;
            this.days = this.fa(d);
            this.hours = this.fa(h);
            this.minutes = this.fa(m);
            this.seconds = this.fa(s);
        },

        nextTick() {
            const n = this.messages.length;
            if (n <= 1) return;
            clearTimeout(this.transitionTimeout);

            if (this.tickIndex < n - 1) {
                this.tickIndex++;
                return;
            }

            this.tickIndex = n;
            this.transitionTimeout = setTimeout(() => {
                this.noTransition = true;
                this.tickIndex = 0;
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    this.noTransition = false;
                }));
            }, 600);
        },

        fa(n) {
            return String(n || 0).padStart(2, '0').replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[+d]);
        },

        destroy() {
            clearInterval(this.intervalId);
            clearInterval(this.tickerId);
            clearTimeout(this.transitionTimeout);
            this.zeroTimeouts.forEach(clearTimeout);
            try {
                if (this.confettiInstance && this.confettiInstance.reset) this.confettiInstance.reset();
            } catch (e) {
            }
        },

        createConfetti() {
            if (!this.confetti || !window.confetti || !this.$refs.confettiCanvas) return null;
            try {
                return window.confetti.create(this.$refs.confettiCanvas, {resize: true, useWorker: false});
            } catch (e) {
                return null;
            }
        },

        readToken(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        confettiColors() {
            if (this.mood === 'mourning') return ['#0a0a0a', '#1f1f1f', '#3a3a3a', '#555555'];
            const palette = [
                this.readToken('--tool-gold-500') || this.readToken('--tool-gold') || '#FFD54F',
                this.readToken('--md-sys-color-primary') || '#6750A4',
                this.readToken('--md-sys-color-tertiary') || '#E6D2E8',
                this.readToken('--md-sys-color-secondary'),
                this.readToken('--tool-sage-500') || this.readToken('--tool-sage'),
                this.readToken('--tool-amethyst-500') || this.readToken('--tool-amethyst'),
            ].filter(Boolean);
            return palette.length ? palette : ['#FFD54F', '#6750A4', '#E6D2E8'];
        },

        burstAppear() {
            if (!this.confettiInstance) return;
            this.confettiInstance({
                particleCount: 110, spread: 75, startVelocity: 32, decay: 0.92, gravity: 0.9,
                scalar: 0.9, ticks: 200, origin: {x: 0.5, y: 0.6}, colors: this.confettiColors(),
                shapes: ['circle'], disableForReducedMotion: true,
            });
        },

        celebrateZero() {
            if (!this.confettiInstance) return;
            const palette = this.confettiColors();
            this.confettiInstance({
                particleCount: 130, spread: 100, startVelocity: 45, scalar: 1.1,
                origin: {x: 0.5, y: 0.6}, colors: palette, disableForReducedMotion: true,
            });
            this.zeroTimeouts.push(setTimeout(() => {
                if (!this.confettiInstance) return;
                this.confettiInstance({
                    particleCount: 70, angle: 60, spread: 75, origin: {x: 0.2, y: 0.7},
                    colors: palette, scalar: 1.0, disableForReducedMotion: true,
                });
            }, 250));
            this.zeroTimeouts.push(setTimeout(() => {
                if (!this.confettiInstance) return;
                this.confettiInstance({
                    particleCount: 70, angle: 120, spread: 75, origin: {x: 0.8, y: 0.7},
                    colors: palette, scalar: 1.0, disableForReducedMotion: true,
                });
            }, 400));
        },
    };
}
