import persistentStateMixin from "../mixins/persistentState.js";
import maximizeMixin from "../mixins/maximize.js";

const START_HOUR = 6;
const MIN_SLOT_MINUTES = 360;
const MAX_SLOT_MINUTES = 1425;
const NOW_TICK_MS = 60000;
const DEAD_ZONE_PX = 5;
const SCROLL_STORAGE_KEY = 'calendar-scroll-memory';

const PAD_2 = Array.from({length: 100}, (_, i) => String(i).padStart(2, '0'));

const raf = (cb) => typeof requestAnimationFrame === 'function' ? requestAnimationFrame(cb) : setTimeout(cb, 16);
const caf = (id) => typeof cancelAnimationFrame === 'function' ? cancelAnimationFrame(id) : clearTimeout(id);

function formatTime(totalMinutes) {
    const h = Math.floor(totalMinutes / 60);
    const m = totalMinutes % 60;
    const hh = h >= 0 && h < 100 ? PAD_2[h] : String(h).padStart(2, '0');
    const mm = m >= 0 && m < 100 ? PAD_2[m] : String(m).padStart(2, '0');
    return `${hh}:${mm}`;
}

function isCacheableDateInput(value) {
    const type = typeof value;
    return value === null || type === 'string' || type === 'number' || type === 'boolean' || type === 'undefined';
}

function lockBodyInteractions(cursorType) {
    document.body.style.cursor = cursorType;
    document.body.style.userSelect = 'none';
    document.body.style.pointerEvents = 'none';
}

function unlockBodyInteractions() {
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    document.body.style.pointerEvents = '';
}

export function calendarDrag({eventId, locked, isOwner}) {
    let destroyed = false;
    let ghost = null;
    let startY = 0;
    let startTop = 0;
    let dragging = false;
    let dragStarted = false;
    let hourHeight = 60;
    let frame = 0;
    let pendingOffsetY = 0;
    let lastOffsetY = 0;
    let activeInstance = null;

    const cancelGhostFrame = () => {
        if (frame) {
            caf(frame);
            frame = 0;
        }
    };

    const removeGhost = () => {
        cancelGhostFrame();
        if (ghost && ghost.parentNode) {
            ghost.parentNode.removeChild(ghost);
        }
        ghost = null;
    };

    const createGhost = (root) => {
        const clone = root.cloneNode(true);
        clone.removeAttribute('x-data');
        clone.removeAttribute('data-event-id');
        clone.classList.add('pointer-events-none');
        clone.style.position = 'absolute';
        clone.style.top = `${startTop}px`;
        clone.style.left = `${root.offsetLeft}px`;
        clone.style.width = `${root.offsetWidth}px`;
        clone.style.zIndex = '50';
        clone.style.willChange = 'transform';
        clone.style.transform = 'translate3d(0, 0, 0)';
        clone.style.boxShadow = '0 16px 40px rgba(0,0,0,0.28)';
        clone.style.opacity = '0.9';
        root.parentElement.appendChild(clone);
        ghost = clone;
        pendingOffsetY = 0;
        lastOffsetY = 0;
    };

    const renderGhost = () => {
        frame = 0;
        if (ghost && pendingOffsetY !== lastOffsetY) {
            lastOffsetY = pendingOffsetY;
            ghost.style.transform = `translate3d(0, ${lastOffsetY}px, 0)`;
        }
    };

    const commitMove = (instance, offsetY) => {
        if (!instance || destroyed) return;

        const finalTop = startTop + offsetY;
        const absoluteMinutes = (finalTop / hourHeight) * 60 + START_HOUR * 60;
        const snappedMinutes = Math.round(absoluteMinutes / 15) * 15;

        if (snappedMinutes < MIN_SLOT_MINUTES || snappedMinutes > MAX_SLOT_MINUTES) {
            instance.$root.style.top = `${startTop}px`;
            return;
        }

        const day = instance.$root.closest('[data-date]');
        const dateJalali = day ? day.dataset.date : null;

        if (!dateJalali) {
            instance.$root.style.top = `${startTop}px`;
            return;
        }

        const optimisticTop = (snappedMinutes - START_HOUR * 60) * hourHeight / 60;
        instance.$root.style.top = `${optimisticTop}px`;

        const timePart = formatTime(snappedMinutes);
        const clientMtime = instance.$root.dataset.mtime || new Date().toISOString();

        instance.$wire.moveEvent(instance.eventId, dateJalali, timePart, clientMtime)
            .then((res) => {
                if (destroyed) return;

                if (!res || !res.ok) {
                    window.dispatchEvent(new CustomEvent(`revert-event-${instance.eventId}`, {
                        detail: (res && res.revertTo) || {},
                    }));
                    instance.$root.style.top = `${startTop}px`;
                    instance.$dispatch('toast', {
                        message: res && res.reason === 'locked'
                            ? 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.'
                            : 'جابجایی ناموفق بود.',
                        type: 'error',
                    });
                } else {
                    instance.$dispatch('toast', {
                        message: 'رویداد جابجا شد.',
                        type: 'success',
                    });
                }
            })
            .catch(() => {
                if (destroyed) return;
                window.dispatchEvent(new CustomEvent(`revert-event-${instance.eventId}`, {detail: {}}));
                instance.$root.style.top = `${startTop}px`;
                instance.$dispatch('toast', {
                    message: 'جابجایی ناموفق بود.',
                    type: 'error',
                });
            });
    };

    const handleDown = (e) => {
        if (destroyed || e.button !== 0 || dragging) return;

        const root = activeInstance.$root;
        hourHeight = parseFloat(root.dataset.hourHeight) || 60;
        startY = e.clientY;
        startTop = root.offsetTop;
        dragging = true;
        dragStarted = false;

        window.addEventListener('pointermove', handleMove, {passive: false});
        window.addEventListener('pointerup', handleUp, {passive: true});
        window.addEventListener('pointercancel', handleUp, {passive: true});
    };

    const handleMove = (e) => {
        if (!dragging || destroyed) return;

        const offsetY = e.clientY - startY;

        if (!dragStarted) {
            if (Math.abs(offsetY) < DEAD_ZONE_PX) return;
            dragStarted = true;
            createGhost(activeInstance.$root);
            activeInstance.$root.style.opacity = '0';
            lockBodyInteractions('grabbing');
        }

        e.preventDefault();
        pendingOffsetY = offsetY;

        if (offsetY === lastOffsetY) {
            if (frame) cancelGhostFrame();
            return;
        }

        if (!frame) frame = raf(renderGhost);
    };

    const handleUp = (e) => {
        if (!dragging || destroyed) return;

        dragging = false;

        window.removeEventListener('pointermove', handleMove);
        window.removeEventListener('pointerup', handleUp);
        window.removeEventListener('pointercancel', handleUp);

        unlockBodyInteractions();

        if (dragStarted) {
            removeGhost();
            activeInstance.$root.style.opacity = '';
            commitMove(activeInstance, e.clientY - startY);
            activeInstance.justDragged = true;
            setTimeout(() => { activeInstance.justDragged = false; }, 0);
        }

        dragStarted = false;
    };

    return {
        eventId,
        locked: !!locked,
        isOwner: !!isOwner,
        justDragged: false,

        get _isDestroyed() { return destroyed; },
        set _isDestroyed(value) { destroyed = !!value; },

        init() {
            destroyed = false;
            activeInstance = this;

            if (!this.locked && this.isOwner) {
                this.$root.style.touchAction = 'none';
                this.$root.addEventListener('pointerdown', handleDown, {passive: true});
            }
        },

        destroy() {
            destroyed = true;
            cancelGhostFrame();

            if (this.$root) {
                this.$root.removeEventListener('pointerdown', handleDown);
                this.$root.style.touchAction = '';
                this.$root.style.opacity = '';
            }

            window.removeEventListener('pointermove', handleMove);
            window.removeEventListener('pointerup', handleUp);
            window.removeEventListener('pointercancel', handleUp);
            unlockBodyInteractions();

            removeGhost();
        },
    };
}

export function calendarResize({eventId, startMinutes, durationMinutes, isOwner, locked}) {
    let destroyed = false;
    let activeInstance = null;
    let block = null;
    let startY = 0;
    let startHeightPx = 0;
    let startDuration = 0;
    let hourHeight = 60;
    let dragging = false;
    let dragStarted = false;
    let pendingDuration = 0;
    let frame = 0;

    const SNAP = 15;
    const MIN_DURATION = 15;
    const MAX_DURATION = 480;
    const DAY_END = 1440;

    const clampDuration = (d) => {
        const maxForDay = Math.min(MAX_DURATION, DAY_END - startMinutes);
        return Math.max(MIN_DURATION, Math.min(d, maxForDay));
    };

    const renderFrame = () => {
        frame = 0;
        if (block) {
            block.style.height = `${(pendingDuration * hourHeight) / 60}px`;
        }
    };

    const commitResize = (instance, deltaY) => {
        const deltaMinutes = Math.round((deltaY / hourHeight) * 60 / SNAP) * SNAP;
        const newDuration = clampDuration(startDuration + deltaMinutes);

        if (newDuration === startDuration) {
            if (block) block.style.height = `${startHeightPx}px`;
            return;
        }

        const clientMtime = (block && block.dataset.mtime) || new Date().toISOString();

        instance.$wire.resizeEvent(instance.eventId, newDuration, clientMtime)
            .then((res) => {
                if (destroyed || !res || res.ok) return;
                if (block) block.style.height = `${startHeightPx}px`;
                instance.$dispatch('toast', {
                    message: res.reason === 'locked'
                        ? 'این رویداد از طریق سیستم رزرو مدیریت می‌شود؛ برای تغییر آن به تب رزرو مراجعه کنید.'
                        : 'تغییر مدت ناموفق بود.',
                    type: 'error',
                });
            })
            .catch(() => {
                if (destroyed) return;
                if (block) block.style.height = `${startHeightPx}px`;
                instance.$dispatch('toast', {message: 'تغییر مدت ناموفق بود.', type: 'error'});
            });
    };

    const handleMove = (e) => {
        if (!dragging || destroyed) return;
        e.preventDefault();

        const offsetY = e.clientY - startY;

        if (!dragStarted) {
            if (Math.abs(offsetY) < DEAD_ZONE_PX) return;
            dragStarted = true;
            lockBodyInteractions('ns-resize');
        }

        const deltaMinutes = Math.round((offsetY / hourHeight) * 60 / SNAP) * SNAP;
        const nextDuration = clampDuration(startDuration + deltaMinutes);

        if (nextDuration === pendingDuration) {
            return;
        }
        pendingDuration = nextDuration;

        if (!frame) frame = raf(renderFrame);
    };

    const release = () => {
        dragging = false;
        dragStarted = false;
        window.removeEventListener('pointermove', handleMove);
        window.removeEventListener('pointerup', handleUp);
        window.removeEventListener('pointercancel', handleCancel);
        unlockBodyInteractions();
        if (frame) { caf(frame); frame = 0; }
    };

    const handleUp = (e) => {
        if (!dragging || destroyed) return;
        const finalY = e.clientY;
        release();
        commitResize(activeInstance, finalY - startY);
    };

    const handleCancel = () => {
        if (!dragging || destroyed) return;
        release();
        if (block) block.style.height = `${startHeightPx}px`;
    };

    const handleDown = (e) => {
        if (destroyed || e.button !== 0 || dragging) return;
        e.preventDefault();
        e.stopPropagation();

        hourHeight = parseFloat(block && block.dataset.hourHeight) || 60;
        startY = e.clientY;
        startHeightPx = block ? block.offsetHeight : 0;
        startDuration = durationMinutes;
        pendingDuration = durationMinutes;
        dragging = true;
        dragStarted = false;

        window.addEventListener('pointermove', handleMove, {passive: false});
        window.addEventListener('pointerup', handleUp, {passive: true});
        window.addEventListener('pointercancel', handleCancel, {passive: true});
    };

    return {
        eventId,
        startMinutes,
        durationMinutes,
        isOwner: !!isOwner,
        locked: !!locked,

        get _isDestroyed() { return destroyed; },
        set _isDestroyed(value) { destroyed = !!value; },

        init() {
            destroyed = false;
            activeInstance = this;
            block = this.$root.closest('[data-event-id]');

            if (!this.locked && this.isOwner && block) {
                this.$root.style.touchAction = 'none';
                this.$root.addEventListener('pointerdown', handleDown);
            }
        },

        destroy() {
            destroyed = true;
            if (frame) { caf(frame); frame = 0; }
            if (this.$root) {
                this.$root.removeEventListener('pointerdown', handleDown);
                this.$root.style.touchAction = '';
            }
            window.removeEventListener('pointermove', handleMove);
            window.removeEventListener('pointerup', handleUp);
            window.removeEventListener('pointercancel', handleCancel);
            if (block && dragging) block.style.height = `${startHeightPx}px`;
            unlockBodyInteractions();
        },
    };
}

export function calendarNow({startIso, spanHours}) {
    const spanMs = (spanHours || 18) * 3600000;
    let destroyed = false;
    let timer = 0;
    let lastDayKey = null;
    let instance = null;
    let hasStartCache = false;
    let cachedStartValue;
    let cachedStartMs = NaN;
    let cachedEndMs = NaN;

    const compute = () => {
        if (destroyed || !instance) return;

        const nowMs = Date.now();
        const dayKey = new Date(nowMs).toDateString();

        if (lastDayKey && lastDayKey !== dayKey) {
            Livewire.dispatch('refresh-calendar');
        }

        lastDayKey = dayKey;

        const value = instance.startIso;
        let startMs, endMs;

        if (isCacheableDateInput(value)) {
            if (!hasStartCache || value !== cachedStartValue) {
                cachedStartValue = value;
                cachedStartMs = new Date(value).getTime();
                cachedEndMs = cachedStartMs + spanMs;
                hasStartCache = true;
            }
            startMs = cachedStartMs;
            endMs = cachedEndMs;
        } else {
            startMs = new Date(value).getTime();
            endMs = startMs + spanMs;
        }

        if (Number.isNaN(startMs) || nowMs < startMs || nowMs > endMs) {
            if (instance.nowTop !== -1) instance.nowTop = -1;
            return;
        }

        const nextTop = ((nowMs - startMs) / spanMs) * 100;
        if (instance.nowTop !== nextTop) instance.nowTop = nextTop;
    };

    const autoScroll = () => {
        if (destroyed || !instance || instance.nowTop < 0) return;

        const container = instance.$root.parentElement;
        if (!container) return;

        const cRect = container.getBoundingClientRect();
        const lRect = instance.$root.getBoundingClientRect();
        const lineTop = lRect.top - cRect.top + container.scrollTop;

        container.scrollTop = Math.max(0, lineTop - container.clientHeight / 2);
    };

    const onVisibility = () => {
        if (!document.hidden && instance) compute();
    };

    return {
        startIso,
        spanHours,
        nowTop: -1,

        get _isDestroyed() { return destroyed; },
        set _isDestroyed(value) { destroyed = !!value; },

        init() {
            destroyed = false;
            instance = this;

            clearInterval(timer);
            document.removeEventListener('visibilitychange', onVisibility);

            compute();

            timer = setInterval(compute, NOW_TICK_MS);
            document.addEventListener('visibilitychange', onVisibility);

            this.$nextTick(autoScroll);
        },

        destroy() {
            destroyed = true;
            clearInterval(timer);
            document.removeEventListener('visibilitychange', onVisibility);
        },
    };
}

export function calendarView() {
    let destroyed = false;
    let unwatch = null;

    return {
        ...persistentStateMixin(),
        ...maximizeMixin(),

        view: 'month',
        scrollMemory: {},

        get _isDestroyed() { return destroyed; },
        set _isDestroyed(value) { destroyed = !!value; },

        init() {
            destroyed = false;
            this.view = this.$wire.get('view') || 'month';
            this.scrollMemory = this._loadState(SCROLL_STORAGE_KEY, {}) || {};

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
            this.scrollMemory[mode] = this.$root.scrollTop;
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
