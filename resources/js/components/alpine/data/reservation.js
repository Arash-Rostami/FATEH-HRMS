import settings from "./settings.js";
import deckMixin from "../mixins/deck.js";
import maximizeMixin from "../mixins/maximize.js";

const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const DIGIT_REGEX = /[0-9]/g;

const CLASS_DISABLED = 'opacity-30 cursor-not-allowed line-through bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/40 shadow-none';
const CLASS_SELECTED = 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] scale-102 z-10';
const CLASS_DEFAULT = 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] shadow-sm';
const STYLE_DEFAULT = 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);';
const STATE_RANK = { ok: 0, soon: 1, past: 2 };

export default function reservation() {
    return {
        ...deckMixin(),
        ...maximizeMixin(),
        view: 'classic',
        start: null,
        end: null,
        nowTick: 0,
        _morphHook: null,
        _clockInterval: null,
        _timeCache: { tick: -1, date: '', isToday: false, now: 0, todayStart: 0 },

        init() {
            this.view = this.$wire.get('view') || 'classic';
            this.deckSyncFromDom();
            this.deckReconcileOpen();
            this.syncTimePreview();

            if (this.view === 'avantgarde') {
                const pinnedStore = this.$store.pinned;
                if (pinnedStore) {
                    const pinnedRows = pinnedStore.getPinned('reservation');
                    if (pinnedRows && pinnedRows.length > 0) {
                        this.$wire.set('gridPinned', pinnedRows);
                    }
                }
            }

            this._clockInterval = setInterval(() => { this.nowTick++; }, 60000);

            this._morphHook = Livewire.hook('morph', ({ component }) => {
                if (component.id === this.$wire.__instance.id) {
                    this.$nextTick(() => {
                        this.deckSyncFromDom();
                        this.deckReconcileOpen();
                    });
                    this.syncTimePreview();
                }
            });
        },

        deckReconcileOpen() {
            const open = Number(this.$wire.get('open'));
            if (!open) return;

            const order = this.deckOrder;
            if (order.length > 0 && order[0] !== open && order.indexOf(open) !== -1) {
                this.deckBringToFront(open);
            }
        },

        slotState(time, minNoticeHours, serverState) {
            void this.nowTick;

            const date = this.$wire.get('date');
            const cache = this._timeCache;

            if (cache.tick !== this.nowTick || cache.date !== date) {
                cache.tick = this.nowTick;
                cache.date = date;
                const now = new Date();
                cache.now = now.getTime();
                const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
                cache.todayStart = todayStart;
                const slotDate = new Date(`${date}T00:00:00`).getTime();
                cache.isToday = (slotDate === todayStart);
            }

            let state = 'ok';
            if (cache.isToday) {
                const h = parseInt(time.slice(0, 2), 10);
                const m = parseInt(time.slice(3, 5), 10);
                const slot = cache.todayStart + (h * 3600000) + (m * 60000);
                const cutoff = cache.now + (minNoticeHours || 0) * 3600000;

                state = slot < cache.now ? 'past' : (slot < cutoff ? 'soon' : 'ok');
            }

            const serverRank = STATE_RANK[serverState] || 0;
            const localRank = STATE_RANK[state] || 0;

            return serverRank > localRank ? serverState : state;
        },

        slotTitle(state) {
            if (state === 'past') return 'زمان گذشته';
            if (state === 'soon') return 'نزدیک به زمان حال — قبل از مهلت اعلام';
            return '';
        },

        slotButtonClass(state, isSelected) {
            if (state !== 'ok') return CLASS_DISABLED;
            return isSelected ? CLASS_SELECTED : CLASS_DEFAULT;
        },

        slotButtonStyle(state, isSelected) {
            return (state === 'ok' && !isSelected) ? STYLE_DEFAULT : '';
        },

        syncTimePreview() {
            this.start = this.$wire.get('startTime');
            this.end = this.$wire.get('endTime');
        },

        pickStart(t) { this.start = t; },
        pickEnd(t) { this.end = t; },

        fa(n) {
            return String(n).replace(DIGIT_REGEX, d => FA_DIGITS[d]);
        },

        humanize(m) {
            if (m < 60) return this.fa(m) + ' دقیقه';
            const h = Math.floor(m / 60);
            const r = m % 60;
            return r === 0 ? (this.fa(h) + ' ساعت') : (this.fa(h) + ' ساعت و ' + this.fa(r) + ' دقیقه');
        },

        durationPreview(min, max) {
            const st = this.start;
            const en = this.end;

            if (!st || !en) {
                return { valid: false, text: 'زمان پایان باید بعد از شروع باشد' };
            }

            const h1 = parseInt(st.slice(0, 2), 10);
            const m1 = parseInt(st.slice(3, 5), 10);
            const h2 = parseInt(en.slice(0, 2), 10);
            const m2 = parseInt(en.slice(3, 5), 10);

            const m = ((h2 * 60) + m2) - ((h1 * 60) + m1);

            const valid = m > 0 && (min === null || m >= min) && (max === null || m <= max);

            return {
                valid,
                text: m <= 0 ? 'زمان پایان باید بعد از شروع باشد' : ('مدت انتخابی: ' + this.humanize(m))
            };
        },

        destroy() {
            if (typeof this._morphHook === 'function') {
                this._morphHook();
                this._morphHook = null;
            }
            if (this._clockInterval) {
                clearInterval(this._clockInterval);
                this._clockInterval = null;
            }
        },

        initPattern() {
            return settings().initPattern();
        },

        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        scrollNext(btnEl) {
            const container = btnEl.previousElementSibling;
            if (container) {
                container.scrollBy({ left: -250, behavior: 'smooth' });
            }
        },

        scrollPrev(btnEl) {
            const container = btnEl.nextElementSibling;
            if (container) {
                container.scrollBy({ left: 250, behavior: 'smooth' });
            }
        }
    };
}
