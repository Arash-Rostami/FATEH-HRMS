import persistentStateMixin, {smsMemoryMixin} from "../mixins/persistentState.js";
import maximizeMixin from "../mixins/maximize.js";

const START_HOUR = 6;
const START_MINUTES = START_HOUR * 60;
const MIN_SLOT_MINUTES = 360;
const MAX_SLOT_MINUTES = 1425;
const NOW_TICK_MS = 60000;
const DEAD_ZONE_PX = 5;
const SNAP = 15;
const MIN_DURATION = 15;
const MAX_DURATION = 480;
const DAY_END = 1440;
const SCROLL_STORAGE_KEY = 'calendar-scroll-memory';
const LOCKED_MSG = 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.';
const MOVED_MSG = 'رویداد جابجا شد.';
const MOVE_FAILED_MSG = 'جابجایی ناموفق بود.';
const RESIZE_FAILED_MSG = 'تغییر مدت ناموفق بود.';

const PAD_2 = Array.from({length: 100}, (_, i) => String(i).padStart(2, '0'));
const pad = (n) => PAD_2[n] ?? String(n).padStart(2, '0');

const raf = (cb) => typeof requestAnimationFrame === 'function' ? requestAnimationFrame(cb) : setTimeout(cb, 16);
const caf = (id) => typeof cancelAnimationFrame === 'function' ? cancelAnimationFrame(id) : clearTimeout(id);

const formatTime = (totalMinutes) => `${pad(Math.floor(totalMinutes / 60))}:${pad(totalMinutes % 60)}`;

const isCacheableDateInput = (value) => {
    const type = typeof value;
    return value === null || type === 'string' || type === 'number' || type === 'boolean' || type === 'undefined';
};

const lockBodyInteractions = (cursorType) => {
    const style = document.body.style;
    style.cursor = cursorType;
    style.userSelect = 'none';
    style.pointerEvents = 'none';
};

const unlockBodyInteractions = () => {
    const style = document.body.style;
    style.cursor = '';
    style.userSelect = '';
    style.pointerEvents = '';
};

const toast = (instance, message, type) => instance.$dispatch('toast', {message, type});

const drag = {rec: null, active: false, started: false, startY: 0, startTop: 0, hourHeight: 60, ghost: null, frame: 0, pending: 0, last: 0};
const dragRecords = new WeakMap();

const cancelDragFrame = () => {
    if (drag.frame) {
        caf(drag.frame);
        drag.frame = 0;
    }
};

const removeGhost = () => {
    cancelDragFrame();
    drag.ghost?.remove();
    drag.ghost = null;
};

const createGhost = (root) => {
    const clone = root.cloneNode(true);
    clone.removeAttribute('x-data');
    clone.removeAttribute('data-event-id');
    clone.setAttribute('x-ignore', '');
    clone.classList.add('pointer-events-none');
    Object.assign(clone.style, {
        position: 'absolute',
        top: `${drag.startTop}px`,
        left: `${root.offsetLeft}px`,
        width: `${root.offsetWidth}px`,
        zIndex: '50',
        willChange: 'transform',
        transform: 'translate3d(0, 0, 0)',
        boxShadow: '0 16px 40px rgba(0,0,0,0.28)',
        opacity: '0.9',
    });
    root.parentElement.appendChild(clone);
    drag.ghost = clone;
    drag.pending = drag.last = 0;
};

const renderGhost = () => {
    drag.frame = 0;
    if (drag.ghost && drag.pending !== drag.last) {
        drag.last = drag.pending;
        drag.ghost.style.transform = `translate3d(0, ${drag.last}px, 0)`;
    }
};

const commitMove = (rec, offsetY) => {
    const {startTop, hourHeight} = drag;
    const {root, instance} = rec;
    const seq = ++rec.seq;
    const revert = () => { root.style.top = `${startTop}px`; };

    const snapped = Math.round((((startTop + offsetY) / hourHeight) * 60 + START_MINUTES) / SNAP) * SNAP;
    if (snapped < MIN_SLOT_MINUTES || snapped > MAX_SLOT_MINUTES) return revert();

    const date = root.closest('[data-date]')?.dataset.date;
    if (!date) return revert();

    root.style.top = `${((snapped - START_MINUTES) * hourHeight) / 60}px`;

    const fail = (detail, message) => {
        window.dispatchEvent(new CustomEvent(`revert-event-${instance.eventId}`, {detail}));
        revert();
        toast(instance, message, 'error');
    };

    instance.$wire.moveEvent(instance.eventId, date, formatTime(snapped), root.dataset.mtime || new Date().toISOString())
        .then((res) => {
            if (rec.destroyed || seq !== rec.seq) return;
            if (res?.ok) toast(instance, MOVED_MSG, 'success');
            else fail(res?.revertTo || {}, res?.reason === 'locked' ? LOCKED_MSG : MOVE_FAILED_MSG);
        })
        .catch(() => {
            if (rec.destroyed || seq !== rec.seq) return;
            fail({}, MOVE_FAILED_MSG);
        });
};

const dragMove = (e) => {
    if (!drag.active || drag.rec.destroyed) return;

    const offsetY = e.clientY - drag.startY;

    if (!drag.started) {
        if (Math.abs(offsetY) < DEAD_ZONE_PX) return;
        drag.started = true;
        const {root} = drag.rec;
        createGhost(root);
        root.style.opacity = '0';
        lockBodyInteractions('grabbing');
    }

    e.preventDefault();
    drag.pending = offsetY;

    if (offsetY === drag.last) {
        cancelDragFrame();
        return;
    }

    if (!drag.frame) drag.frame = raf(renderGhost);
};

const unlistenDrag = () => {
    window.removeEventListener('pointermove', dragMove);
    window.removeEventListener('pointerup', dragUp);
    window.removeEventListener('pointercancel', dragUp);
};

const dragUp = (e) => {
    if (!drag.active || drag.rec.destroyed) return;

    const {rec, started} = drag;
    drag.active = drag.started = false;
    unlistenDrag();
    unlockBodyInteractions();

    if (started) {
        removeGhost();
        rec.root.style.opacity = '';
        commitMove(rec, e.clientY - drag.startY);
        const {instance} = rec;
        instance.justDragged = true;
        setTimeout(() => { instance.justDragged = false; }, 0);
    }

    drag.rec = null;
};

const dragDown = (e) => {
    const rec = dragRecords.get(e.currentTarget);
    if (!rec || rec.destroyed || e.button !== 0 || drag.active) return;

    const {root} = rec;
    drag.rec = rec;
    drag.hourHeight = parseFloat(root.dataset.hourHeight) || 60;
    drag.startY = e.clientY;
    drag.startTop = root.offsetTop;
    drag.active = true;
    drag.started = false;

    window.addEventListener('pointermove', dragMove, {passive: false});
    window.addEventListener('pointerup', dragUp, {passive: true});
    window.addEventListener('pointercancel', dragUp, {passive: true});
};

const abortDrag = () => {
    drag.active = drag.started = false;
    drag.rec = null;
    unlistenDrag();
    unlockBodyInteractions();
    removeGhost();
};

export function calendarDrag({eventId, locked, isOwner}) {
    const rec = {instance: null, root: null, destroyed: false, seq: 0};

    return {
        eventId,
        locked: !!locked,
        isOwner: !!isOwner,
        justDragged: false,

        get _isDestroyed() { return rec.destroyed; },
        set _isDestroyed(value) { rec.destroyed = !!value; },

        init() {
            rec.destroyed = false;
            rec.instance = this;
            rec.root = this.$root;

            if (!this.locked && this.isOwner) {
                rec.root.style.touchAction = 'none';
                rec.root.addEventListener('pointerdown', dragDown, {passive: true});
                dragRecords.set(rec.root, rec);
            }
        },

        destroy() {
            rec.destroyed = true;
            const {root} = rec;

            if (root) {
                root.removeEventListener('pointerdown', dragDown);
                root.style.touchAction = '';
                root.style.opacity = '';
                dragRecords.delete(root);
            }

            if (drag.rec === rec) abortDrag();
        },
    };
}

const rsz = {rec: null, active: false, started: false, startY: 0, startHeightPx: 0, startDuration: 0, hourHeight: 60, pending: 0, frame: 0};
const resizeRecords = new WeakMap();

const clampDuration = (rec, duration) =>
    Math.max(MIN_DURATION, Math.min(duration, Math.min(MAX_DURATION, DAY_END - rec.startMinutes)));

const renderResize = () => {
    rsz.frame = 0;
    const block = rsz.rec?.block;
    if (block) block.style.height = `${(rsz.pending * rsz.hourHeight) / 60}px`;
};

const commitResize = (rec, deltaY) => {
    const {startHeightPx, startDuration, hourHeight} = rsz;
    const {block, instance} = rec;
    const seq = ++rec.seq;
    const revert = () => { block.style.height = `${startHeightPx}px`; };

    const next = clampDuration(rec, startDuration + Math.round((deltaY / hourHeight) * 60 / SNAP) * SNAP);
    if (next === startDuration) return revert();

    instance.$wire.resizeEvent(instance.eventId, next, block.dataset.mtime || new Date().toISOString())
        .then((res) => {
            if (rec.destroyed || seq !== rec.seq || !res || res.ok) return;
            revert();
            toast(instance, res.reason === 'locked' ? LOCKED_MSG : RESIZE_FAILED_MSG, 'error');
        })
        .catch(() => {
            if (rec.destroyed || seq !== rec.seq) return;
            revert();
            toast(instance, RESIZE_FAILED_MSG, 'error');
        });
};

const resizeMove = (e) => {
    if (!rsz.active || rsz.rec.destroyed) return;
    e.preventDefault();

    const offsetY = e.clientY - rsz.startY;

    if (!rsz.started) {
        if (Math.abs(offsetY) < DEAD_ZONE_PX) return;
        rsz.started = true;
        lockBodyInteractions('ns-resize');
    }

    const next = clampDuration(rsz.rec, rsz.startDuration + Math.round((offsetY / rsz.hourHeight) * 60 / SNAP) * SNAP);
    if (next === rsz.pending) return;
    rsz.pending = next;

    if (!rsz.frame) rsz.frame = raf(renderResize);
};

const releaseResize = () => {
    rsz.active = rsz.started = false;
    window.removeEventListener('pointermove', resizeMove);
    window.removeEventListener('pointerup', resizeUp);
    window.removeEventListener('pointercancel', resizeCancel);
    unlockBodyInteractions();
    if (rsz.frame) {
        caf(rsz.frame);
        rsz.frame = 0;
    }
};

const resizeUp = (e) => {
    if (!rsz.active || rsz.rec.destroyed) return;
    const {rec} = rsz;
    const deltaY = e.clientY - rsz.startY;
    releaseResize();
    commitResize(rec, deltaY);
    rsz.rec = null;
};

const resizeCancel = () => {
    if (!rsz.active || rsz.rec.destroyed) return;
    const {rec} = rsz;
    releaseResize();
    rec.block.style.height = `${rsz.startHeightPx}px`;
    rsz.rec = null;
};

const resizeDown = (e) => {
    const rec = resizeRecords.get(e.currentTarget);
    if (!rec || rec.destroyed || e.button !== 0 || rsz.active) return;
    e.preventDefault();
    e.stopPropagation();

    const {block} = rec;
    rsz.rec = rec;
    rsz.hourHeight = parseFloat(block.dataset.hourHeight) || 60;
    rsz.startY = e.clientY;
    rsz.startHeightPx = block.offsetHeight;
    rsz.startDuration = rsz.pending = rec.durationMinutes;
    rsz.active = true;
    rsz.started = false;

    window.addEventListener('pointermove', resizeMove, {passive: false});
    window.addEventListener('pointerup', resizeUp, {passive: true});
    window.addEventListener('pointercancel', resizeCancel, {passive: true});
};

export function calendarResize({eventId, startMinutes, durationMinutes, isOwner, locked}) {
    const rec = {instance: null, root: null, block: null, startMinutes, durationMinutes, destroyed: false, seq: 0};

    return {
        eventId,
        startMinutes,
        durationMinutes,
        isOwner: !!isOwner,
        locked: !!locked,

        get _isDestroyed() { return rec.destroyed; },
        set _isDestroyed(value) { rec.destroyed = !!value; },

        init() {
            rec.destroyed = false;
            rec.instance = this;
            rec.root = this.$root;
            rec.block = rec.root.closest('[data-event-id]');

            if (!this.locked && this.isOwner && rec.block) {
                rec.root.style.touchAction = 'none';
                rec.root.addEventListener('pointerdown', resizeDown);
                resizeRecords.set(rec.root, rec);
            }
        },

        destroy() {
            rec.destroyed = true;
            const {root, block} = rec;

            if (root) {
                root.removeEventListener('pointerdown', resizeDown);
                root.style.touchAction = '';
                resizeRecords.delete(root);
            }

            if (rsz.rec === rec) {
                if (rsz.active) block.style.height = `${rsz.startHeightPx}px`;
                releaseResize();
                rsz.rec = null;
            }
        },
    };
}

const nowRecords = new Set();
let nowTimer = 0;
let nowDayKey = null;

const stampNow = () => {
    const nowMs = Date.now();
    const dayKey = new Date(nowMs).toDateString();
    if (nowDayKey && nowDayKey !== dayKey) Livewire.dispatch('refresh-calendar');
    nowDayKey = dayKey;
    return nowMs;
};

const computeNow = (rec, nowMs) => {
    const instance = rec.instance;
    if (rec.destroyed || !instance) return;

    const value = instance.startIso;
    let startMs, endMs;

    if (isCacheableDateInput(value)) {
        if (!rec.cached || value !== rec.cacheKey) {
            rec.cacheKey = value;
            rec.startMs = new Date(value).getTime();
            rec.endMs = rec.startMs + rec.spanMs;
            rec.cached = true;
        }
        startMs = rec.startMs;
        endMs = rec.endMs;
    } else {
        startMs = new Date(value).getTime();
        endMs = startMs + rec.spanMs;
    }

    if (Number.isNaN(startMs) || nowMs < startMs || nowMs > endMs) {
        if (instance.nowTop !== -1) instance.nowTop = -1;
        return;
    }

    const top = ((nowMs - startMs) / rec.spanMs) * 100;
    if (instance.nowTop !== top) instance.nowTop = top;
};

const tickNow = () => {
    const nowMs = stampNow();
    for (const rec of nowRecords) computeNow(rec, nowMs);
};

const onNowVisibility = () => {
    if (!document.hidden) tickNow();
};

const autoScrollNow = (rec) => {
    const {instance, root} = rec;
    if (rec.destroyed || instance.nowTop < 0) return;

    const container = root.parentElement;
    if (!container) return;

    const lineTop = root.getBoundingClientRect().top - container.getBoundingClientRect().top + container.scrollTop;
    container.scrollTop = Math.max(0, lineTop - container.clientHeight / 2);
};

export function calendarNow({startIso, spanHours}) {
    const rec = {
        instance: null, root: null, destroyed: false,
        spanMs: (spanHours || 18) * 3600000,
        cached: false, cacheKey: undefined, startMs: NaN, endMs: NaN,
    };

    return {
        startIso,
        spanHours,
        nowTop: -1,

        get _isDestroyed() { return rec.destroyed; },
        set _isDestroyed(value) { rec.destroyed = !!value; },

        init() {
            rec.destroyed = false;
            rec.instance = this;
            rec.root = this.$root;
            nowRecords.add(rec);

            computeNow(rec, stampNow());

            if (!nowTimer) {
                nowTimer = setInterval(tickNow, NOW_TICK_MS);
                document.addEventListener('visibilitychange', onNowVisibility);
            }

            this.$nextTick(() => autoScrollNow(rec));
        },

        destroy() {
            rec.destroyed = true;
            nowRecords.delete(rec);

            if (!nowRecords.size && nowTimer) {
                clearInterval(nowTimer);
                nowTimer = 0;
                nowDayKey = null;
                document.removeEventListener('visibilitychange', onNowVisibility);
            }
        },
    };
}

export function calendarDay({hourHeight, startHour, endHour, isToday = false, firstTop = null}) {
    return {
        showAllDay: false,

        init() {
            setTimeout(() => this.scrollToTarget(), 120);
        },

        scrollTarget() {
            if (isToday) {
                const now = new Date();
                const hours = now.getHours() + now.getMinutes() / 60;

                return hours >= startHour && hours <= endHour ? (hours - startHour) * hourHeight : null;
            }

            return firstTop;
        },

        scrollToTarget() {
            const target = this.scrollTarget();
            const container = this.$refs.gridWrapper?.closest('.overflow-y-auto, .overflow-auto');

            if (target === null || !container) return;

            container.scrollTo({top: Math.max(0, target - 100), behavior: 'smooth'});
        },
    };
}

export function calendarView({senderId = null, smsYear = null} = {}) {
    let destroyed = false;
    let unwatch = null;

    return {
        ...persistentStateMixin(),
        ...smsMemoryMixin({senderId, smsYear}),
        ...maximizeMixin('calendar'),

        view: 'month',
        scrollMemory: {},

        get _isDestroyed() { return destroyed; },
        set _isDestroyed(value) { destroyed = !!value; },

        init() {
            destroyed = false;
            this.initMaximize();
            this.view = this.$wire.get('view') || 'month';
            this.scrollMemory = this._loadState(SCROLL_STORAGE_KEY, {}) || {};
            this.loadSmsMemory();

            if (typeof unwatch === 'function') unwatch();

            unwatch = this.$watch('$wire.view', (v) => {
                this.saveScroll(this.view);
                this.view = v;
                this.$nextTick(() => this.restoreScroll(v));
            });

            this.$nextTick(() => this.restoreScroll(this.view));
        },

        saveScroll(mode) {
            if (!this.$root) return;
            const top = this.$root.scrollTop;
            if (this.scrollMemory[mode] === top) return;
            this.scrollMemory[mode] = top;
            this._saveState(SCROLL_STORAGE_KEY, this.scrollMemory);
        },

        restoreScroll(mode) {
            if (this.scrollMemory[mode] != null) {
                this.$root.scrollTop = this.scrollMemory[mode];
            }
        },

        destroy() {
            destroyed = true;
            this.saveScroll(this.view);
            if (typeof unwatch === 'function') unwatch();
        },
    };
}
