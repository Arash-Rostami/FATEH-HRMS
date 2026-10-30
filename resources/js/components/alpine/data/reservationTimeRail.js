const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const DIGIT_REGEX = /[0-9]/g;

export default function reservationTimeRail(slots, busyIdx, nowIdx, startIdx, endIdx) {
    return {
        slots,
        busyIdx,
        nowIdx,
        startIdx,
        endIdx,
        dragging: null,

        _rectCache: null,
        _raf: null,
        _pending: 0,
        _unwatch: [],

        init() {
            if (typeof this.$wire?.$watch !== 'function') return;

            const sync = () => this._syncFromServer();
            this._unwatch = [this.$wire.$watch('startTime', sync), this.$wire.$watch('endTime', sync)];
        },

        destroy() {
            this._unwatch.forEach(u => typeof u === 'function' && u());
            this._unwatch = [];
        },

        _send(method, slot) {
            this._pending++;
            Promise.resolve(this.$wire[method](slot)).catch(() => {}).finally(() => {
                this._pending--;
                this._syncFromServer();
            });
        },

        _syncFromServer() {
            if (this.dragging || this._pending > 0 || typeof this.$wire?.get !== 'function') return;

            const s = this.slots.indexOf(this.$wire.get('startTime'));
            const e = this.slots.indexOf(this.$wire.get('endTime'));

            if (s === -1 || e === -1 || s >= e) return;

            this.startIdx = s;
            this.endIdx = e;
        },

        get _len() {
            const l = this.slots.length;
            return l > 1 ? l - 1 : 1;
        },

        get _last() {
            return Math.max(this.slots.length - 1, 0);
        },

        _clampStart(i) {
            return Math.max(0, Math.min(i, this.endIdx - 1));
        },

        _clampEnd(i) {
            return Math.min(this._last, Math.max(i, this.startIdx + 1));
        },

        pct(i) {
            return (i / this._len) * 100;
        },

        fa(s) {
            return String(s).replace(DIGIT_REGEX, c => FA_DIGITS[c]);
        },

        mins(t) {
            if (!t) return 0;
            return (parseInt(t.slice(0, 2), 10) * 60) + parseInt(t.slice(-2), 10);
        },

        duration() {
            const t1 = this.slots[this.startIdx] || '';
            const t2 = this.slots[this.endIdx] || '';

            let m = this.mins(t2) - this.mins(t1);
            if (m < 0) m = 0;

            const h = Math.floor(m / 60);
            const r = m % 60;

            if (h > 0 && r > 0) return this.fa(h) + ' ساعت و ' + this.fa(r) + ' دقیقه';
            if (h > 0) return this.fa(h) + ' ساعت';
            if (r > 0) return this.fa(r) + ' دقیقه';
            return '۰ دقیقه';
        },

        conflict() {
            const start = this.startIdx;
            const end = this.endIdx;
            const busy = this.busyIdx;
            const len = busy.length;

            for (let i = 0; i < len; i++) {
                const b = busy[i];
                if (start < b[1] && end > b[0]) return true;
            }
            return false;
        },

        _getClientX(e) {
            return e.touches && e.touches.length > 0 ? e.touches[0].clientX : e.clientX;
        },

        _getRatio(clientX) {
            if (!this._rectCache) {
                this._rectCache = this.$refs.rail.getBoundingClientRect();
            }

            const rect = this._rectCache;
            if (rect.width === 0) return 0;

            const fromRight = rect.right - clientX;
            const ratio = fromRight / rect.width;

            if (ratio < 0) return 0;
            if (ratio > 1) return 1;
            return ratio;
        },

        onDown(handle) {
            this.dragging = handle;
            this._rectCache = this.$refs.rail.getBoundingClientRect();
        },

        trackDown(e) {
            this._rectCache = this.$refs.rail.getBoundingClientRect();
            const ratio = this._getRatio(this._getClientX(e));
            const i = Math.round(ratio * this._len);

            const dStart = i - this.startIdx;
            const dEnd = i - this.endIdx;

            const absStart = dStart < 0 ? -dStart : dStart;
            const absEnd = dEnd < 0 ? -dEnd : dEnd;

            if (absStart <= absEnd) {
                const next = this._clampStart(i);
                if (this.startIdx !== next) this.startIdx = next;
                this.dragging = 'start';
            } else {
                const next = this._clampEnd(i);
                if (this.endIdx !== next) this.endIdx = next;
                this.dragging = 'end';
            }
        },

        onMove(e) {
            if (!this.dragging) return;

            if (e.cancelable !== false) {
                e.preventDefault();
            }

            const clientX = this._getClientX(e);

            if (this._raf) return;

            this._raf = requestAnimationFrame(() => {
                this._raf = null;

                const ratio = this._getRatio(clientX);
                const i = Math.round(ratio * this._len);

                if (this.dragging === 'start') {
                    const next = this._clampStart(i);
                    if (this.startIdx !== next) this.startIdx = next;
                } else {
                    const next = this._clampEnd(i);
                    if (this.endIdx !== next) this.endIdx = next;
                }
            });
        },

        onUp() {
            if (!this.dragging) return;

            if (this._raf) cancelAnimationFrame(this._raf);
            this._rectCache = null;

            const which = this.dragging;
            this.dragging = null;

            if (this.startIdx >= this.endIdx) return;

            if (which === 'start') {
                this._send('setStartTime', this.slots[this.startIdx]);
            } else {
                this._send('setEndTime', this.slots[this.endIdx]);
            }
        },

        nudge(handle, delta) {
            if (handle === 'start') {
                const next = this._clampStart(this.startIdx + delta);

                if (this.startIdx !== next) {
                    this.startIdx = next;
                    this._send('setStartTime', this.slots[next]);
                }
            } else {
                const next = this._clampEnd(this.endIdx + delta);

                if (this.endIdx !== next) {
                    this.endIdx = next;
                    this._send('setEndTime', this.slots[next]);
                }
            }
        }
    };
}
