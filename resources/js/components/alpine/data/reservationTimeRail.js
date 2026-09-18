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

        get _len() {
            const l = this.slots.length;
            return l > 1 ? l - 1 : 1;
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
                const next = i < this.endIdx ? i : this.endIdx;
                if (this.startIdx !== next) this.startIdx = next;
                this.dragging = 'start';
            } else {
                const next = i > this.startIdx ? i : this.startIdx;
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
                    const next = i < this.endIdx ? i : this.endIdx;
                    if (this.startIdx !== next) this.startIdx = next;
                } else {
                    const next = i > this.startIdx ? i : this.startIdx;
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

            if (which === 'start') {
                this.$wire.setStartTime(this.slots[this.startIdx]);
            } else {
                this.$wire.setEndTime(this.slots[this.endIdx]);
            }
        },

        nudge(handle, delta) {
            const len = this._len;

            if (handle === 'start') {
                let next = this.startIdx + delta;
                if (next < 0) next = 0;
                if (next > this.endIdx) next = this.endIdx;

                if (this.startIdx !== next) {
                    this.startIdx = next;
                    this.$wire.setStartTime(this.slots[next]);
                }
            } else {
                let next = this.endIdx + delta;
                if (next > len) next = len;
                if (next < this.startIdx) next = this.startIdx;

                if (this.endIdx !== next) {
                    this.endIdx = next;
                    this.$wire.setEndTime(this.slots[next]);
                }
            }
        }
    };
}
