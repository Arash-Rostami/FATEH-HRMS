import { emojis } from "../stores/emoji.js";
import highlightMatchMixin from "./highlightMatch.js";
import headerDeclutterMixin from "./headerDeclutter.js";
import { applyMine, MINE_STEPS } from "./chatScale.js";
import persistentStateMixin from "./persistentState.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));
const emojiCache = new Map();
const EMOJI_CACHE_MAX = 1000;
const HTML_TAG_RE = /<[^>]*>/g;
const WS_ONLY_RE = /^\s+$/;
const DIR_RE = /([A-Za-z])|[؀-ۿ]/;
const ID_TAIL_RE = /(\d+)$/;
const MSG_ROW_RE = /message-\d+$/;

const SCROLL_TO_BOTTOM_PX = 999999;
const LOAD_OLDER_PEEK_PX = 96;
const SETTLE_PIN_MS = 300;
const SCROLL_SETTLE_MS = 150;
const POLL_BASE_MS = 10000;
const INCOMING_DEDUPE_MS = 1500;
const RETURN_RING_GRACE_MS = 8000;
const POLL_JITTER = 0.2;
const POLL_JITTER_HIDDEN = 0.5;
const SAFETY_POLL_MS = 5 * 60 * 1000;
const TYPING_PING_MS = 3000;

const EVENT_TOAST = 'toast';
const ID_VIEWPORT = 'msg-viewport';
const GHOST_TICK_MS = 1000;
const GHOST_BUBBLE_CLASS = 'px-4 py-2.5 text-sm leading-relaxed break-words whitespace-pre-wrap rounded-2xl bubble-mine shadow-[0_3px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_28%,transparent),inset_0_1px_0_color-mix(in_srgb,white_12%,transparent)] [unicode-bidi:plaintext]';
const GHOST_HTML = `<div class="flex-shrink-0 w-7"></div><div class="max-w-[70%] md:max-w-[60%] lg:max-w-[55%]"><div dir="auto" class="${GHOST_BUBBLE_CLASS}"></div><div class="flex items-center gap-1.5 mt-1.5 justify-start pl-1"><span class="material-symbols-rounded text-[13px] opacity-50">schedule</span><time dir="ltr" class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-60"></time></div></div>`;
const DAYS_CLIENT_TTL_MS = 300000;
const LEAVE_DRAFT_MS = 4000;
const DRAFT_PREFIX = 'chat-draft:';
const DRAFT_TTL_MS = 24 * 60 * 60 * 1000;
const SEND_WATCHDOG_MS = 30000;
const ROW_WAIT_MS = 15000;
const ROW_REFRESHES = 3;
const UNDELIVERED_MSG = 'وضعیت ارسال پیام مشخص نیست';
const CONNECTION_MSG = 'وضعیت ارسال پیام مشخص نیست. پیش از ارسال دوباره، اتصال اینترنت را بررسی کنید. اگر VPN روشن است، آن را خاموش کنید.';
const NOT_SHOWN_MSG = 'پیام ارسال شد. ممکن است کمی دیرتر نمایش داده شود.';
const RELOCATED_MSG = 'برای ادامهٔ نوشتن پیام، به گفت‌وگوی قبلی برگردید.';
const TIMED_OUT = Symbol('timed-out');
const ROOM_SEL = '[data-room]';
const newRev = () => globalThis.crypto?.randomUUID?.() ?? `${Date.now().toString(36)}${Math.random().toString(36).slice(2)}`;
const SETTLE_STOP_EVENTS = ['wheel', 'touchstart', 'pointerdown', 'keydown'];
const MSG_ROW_SEL = '[data-rf*="message-"]';
const UNREAD_ID_SEL = '[data-unread-id]';
const DAY_SEL = '[data-day]';
const SWIPE_ACTIVATE_PX = 14;
const SWIPE_MAX_PX = 72;
const SWIPE_COMMIT_PX = 40;
let fmt;
const formats = () => fmt ??= {
    day: new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric', month: 'long' }),
    month: new Intl.DateTimeFormat('fa-IR-u-ca-persian', { month: 'long', year: 'numeric' }),
    weekday: new Intl.DateTimeFormat('fa-IR', { weekday: 'long' }),
    seconds: new Intl.NumberFormat('fa-IR'),
};
const ID_TA = 'msg-ta';
const ID_CHIP_TRACK = 'sender-chip-track';
const ID_SEARCH_INPUT = 'msg-search-input';

const ATTR_MANUAL_RESIZE = 'data-manual-resize';

const byId = (id) => document.getElementById(id);
const idOf = (rf) => parseInt(rf?.match(ID_TAIL_RE)?.[1], 10) || 0;
const byNewest = (a, b) => b.id - a.id;
const byNewestDay = (a, b) => (a.day < b.day ? 1 : -1);

function computeEmojiOnly(text) {
    const stripped = text.replace(HTML_TAG_RE, '').trim();
    if (stripped === '') return false;

    for (const data of segmenter.segment(stripped)) {
        const seg = data.segment;
        if (!emojiSet.has(seg) && !WS_ONLY_RE.test(seg)) return false;
    }
    return true;
}

const FONT_SIZES = [0.75, null, 1, 1.125, 1.25];
const FONT_MAX_ENTRIES = 300;
const FONT_KEY_RE = /^[a-z][a-z0-9-]{0,40}$/;

export default function chatBase(scope) {
    const settingsKey = 'chat-settings:' + scope;
    const BOOKMARKS = scope === 'channel' ? 'channel-message' : 'message';
    const drafts = new Map();
    const cleared = new Set();
    let sends = 0;
    const draftKey = (room) => `${DRAFT_PREFIX}${document.documentElement.dataset.uid ?? ''}:${scope}:${room}`;
    const rowSel = (id) => `[data-rf="${BOOKMARKS}-${id}"]`;

    return {
        ...persistentStateMixin(),
        ...highlightMatchMixin(),
        ...headerDeclutterMixin(),
        fontSteps: {},
        messageFont: 'default',
        mineOpacity: 100,
        _lastIncomingAt: 0,
        _ringPending: null,
        _muteEval: null,
        _riseOnlyMuted() {
            if (typeof this._readUnread !== 'function') return false;

            const total = this._readUnread();
            const previous = this._muteEval;
            const sameTotal = previous?.total === total;
            const snap = new Map();
            let mutedRise = 0;
            let otherRise = 0;

            document.querySelectorAll(UNREAD_ID_SEL).forEach((el) => {
                const id = Number(el.dataset.unreadId);
                const count = Number(el.dataset.unread) || 0;
                snap.set(id, count);

                if (sameTotal || !previous) return;
                const rise = count - (previous.snap.get(id) ?? 0);
                if (rise <= 0) return;
                if (this.$store.sound?.isMuted(id, scope)) mutedRise += rise;
                else otherRise += rise;
            });

            const muted = sameTotal
                ? previous.muted
                : previous !== null && previous.snap.size > 0 && otherRise === 0 && mutedRise > 0 && mutedRise >= total - previous.total;
            this._muteEval = { total, snap, muted };
            return muted;
        },
        _pushMessage(total, fallback) {
            const previous = this._pushSnapshot;
            const rows = [...document.querySelectorAll(UNREAD_ID_SEL)];
            this._pushSnapshot = {
                total,
                snap: new Map(rows.map((el) => [Number(el.dataset.unreadId), Number(el.dataset.unread) || 0])),
            };
            if (!previous || total <= previous.total) return ['پیام جدید', fallback];

            const rises = rows.filter((el) => {
                const id = Number(el.dataset.unreadId);
                return Number(el.dataset.unread) > (previous.snap.get(id) ?? 0);
            });
            const row = rises[0];
            const rise = row ? Number(row.dataset.unread) - (previous.snap.get(Number(row.dataset.unreadId)) ?? 0) : 0;
            if (rises.length !== 1 || rise !== total - previous.total || row.dataset.notifyMine === '1' || !row.dataset.notifyName) {
                return ['پیام جدید', fallback];
            }

            const body = row.dataset.notifyBody?.trim() || 'پیام جدید';
            const sender = row.dataset.notifySender?.trim();
            return [row.dataset.notifyName, sender ? `${sender}: ${body}` : body];
        },
        _playRing(id) {
            const now = Date.now();
            if (now - this._lastIncomingAt < INCOMING_DEDUPE_MS) return;
            this._lastIncomingAt = now;
            this.$store.sound?.playIncoming(id, scope);
        },
        _ringIncoming(id) {
            if (id === null && this._riseOnlyMuted()) return;
            if (document.hidden) {
                this._ringPending = { id };
                return;
            }
            this._playRing(id);
        },
        _flushPendingRing() {
            const pending = this._ringPending;
            if (!pending || document.hidden) return;
            this._ringPending = null;
            this._playRing(pending.id);
            this._lastIncomingAt = Date.now() + RETURN_RING_GRACE_MS - INCOMING_DEDUPE_MS;
        },
        showScrollFab: false,
        newBelow: 0,
        _programmaticScroll: false,
        _savingEdit: false,
        _scrollSettleTimer: null,
        showInfo: false,
        searchMessages: false,
        bookmarksOpen: false,
        bookmarkList: [],
        daysOpen: false,
        dayList: [],
        dayRows: [],
        daysLoading: false,
        filesOpen: false,
        filesFilter: 'all',
        filesList: [],
        filesLoading: false,
        filesError: false,
        showUndo: false,
        undoTimeout: null,
        sending: false,
        openActionsId: null,
        replyingTo: null,
        deletingId: null,
        emojiOpen: false,
        activeCat: 0,
        emojis,
        isHighlighted: false,
        backgroundPattern: 'off',
        backgroundPatternType: 'mesh',
        searchFullscreen: false,
        searchValue: '',
        messageSearchFullscreen: false,
        messageSearchValue: '',
        typeFilter: '',
        typeFilterOpen: false,
        chipScroll: { prev: false, next: false },

        _timer: null,
        _loadingOlder: false,
        _loadingNewer: false,
        _selfMarker: null,
        _markerAdvanced: (after, before) => idOf(after) > idOf(before),
        chars: (text) => Array.from(text || '').length,
        _pollIntervalMs: POLL_BASE_MS,
        _lastTypingPing: 0,
        _pollInFlight: false,
        _repollQueued: false,
        _destroyed: false,
        _navigating: false,
        _leaveArm: null,
        _leaveUntil: 0,
        _navWatch: false,
        _ghost: null,
        _pendingSend: null,
        _draftQuiet: false,
        _sendSeq: 0,

        _draftRoom() {
            return byId(ID_TA)?.closest(ROOM_SEL)?.dataset.room ?? null;
        },

        _draftDrop(room) {
            if (room == null) return;
            const own = drafts.get(room)?.rev;
            cleared.add(room);
            drafts.delete(room);
            if (own !== undefined && this._loadState(draftKey(room))?.rev === own) this._clearState(draftKey(room));
        },

        _draftSave(room, body) {
            if (room == null) return;
            if (body.trim() === '') return this._draftDrop(room);
            const draft = { body, rev: newRev() };
            cleared.delete(room);
            drafts.set(room, draft);
            this._saveState(draftKey(room), { ...draft, at: Date.now() });
        },

        _draftStored(room) {
            const key = draftKey(room);
            const stored = this._loadState(key);
            if (typeof stored?.body !== 'string' || typeof stored.rev !== 'string') return null;
            if (stored.body.trim() === '' || !(Date.now() - stored.at <= DRAFT_TTL_MS)) {
                this._clearState(key);
                return null;
            }
            return { body: stored.body, rev: stored.rev };
        },

        _draftWrite(body) {
            const el = byId(ID_TA);
            if (el && el.value !== body) el.value = body;
            this.$wire.set('composer.body', body, false);
            if (!el) return;
            this.autoGrow(el, 160);
            this.autoDirection(el);
        },

        _draftGuard(body) {
            const room = this._draftRoom();
            const draft = room == null ? undefined : drafts.get(room);
            if (!draft && room != null && cleared.has(room) && typeof body === 'string' && body.trim() !== '') return this._draftWrite('');
            if (!draft || body === draft.body) return;

            const pending = this._pendingSend;
            if (pending && !pending.failed && pending.rev === draft.rev) return;

            this._draftWrite(draft.body);
        },

        _draftRepair(room) {
            if (room == null || this._draftRoom() !== room) return;
            const draft = drafts.get(room);
            const box = byId(ID_TA);
            if (draft && box && box.value !== draft.body) this._draftWrite(draft.body);
        },

        _draftRestore() {
            const room = this._draftRoom();
            const el = byId(ID_TA);
            if (room == null || !el) return;

            if (el.value !== '') {
                const own = drafts.get(room);
                const foreign = [...drafts].some(([other, draft]) => other !== room && draft.body === el.value);
                if (!foreign || own?.body === el.value) return;

                const next = own ?? this._draftStored(room);
                if (next && !own) drafts.set(room, next);
                this._draftWrite(next?.body ?? '');
                this.toast(RELOCATED_MSG, 'info');
                return;
            }

            const memory = drafts.get(room);
            const stored = memory ?? this._draftStored(room);
            if (!stored) return;

            if (!memory) drafts.set(room, { body: stored.body, rev: stored.rev });
            this._draftQuiet = true;
            this._lastTypingPing = Date.now();
            el.value = stored.body;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            this._draftQuiet = false;
            this.$wire.set('composer.body', stored.body, false);
            this.autoGrow(el, 160);
            this.autoDirection(el);
        },

        _draftInit(signal) {
            document.addEventListener('input', (e) => {
                if (this._draftQuiet || e.target?.id !== ID_TA) return;
                this._draftSave(this._draftRoom(), e.target.value);
            }, { capture: true, signal });
            this.$watch('$wire.composer.body', (body) => this._draftGuard(body));
            this.$nextTick(() => this._draftRestore());
        },

        _draftSent(detail) {
            const pending = this._pendingSend;
            if (!pending || detail?.rev == null || detail.rev !== pending.rev) return;

            pending.confirmed = true;
            if (drafts.get(pending.room)?.rev !== pending.rev) return;

            drafts.delete(pending.room);
            cleared.add(pending.room);
            if (this._loadState(draftKey(pending.room))?.rev === pending.rev) this._clearState(draftKey(pending.room));
        },

        _undelivered(connection = false) {
            this.toast(connection ? CONNECTION_MSG : UNDELIVERED_MSG, 'error');
        },

        async _awaitRow(id) {
            let timer;
            const limit = new Promise((resolve) => { timer = setTimeout(resolve, ROW_WAIT_MS); });
            const redraw = async () => {
                for (let i = 0; i < ROW_REFRESHES && !document.querySelector(rowSel(id)); i++) {
                    await this._isl().refreshActive().catch(() => {});
                    await this.$nextTick();
                }
            };
            try {
                await Promise.race([redraw(), limit]);
            } finally {
                clearTimeout(timer);
            }
            return !!document.querySelector(rowSel(id));
        },

        async _deliver(body) {
            const room = this._draftRoom();
            const retry = room != null ? this._lastFailed?.[room] ?? null : null;
            const rev = (room != null && drafts.get(room)?.rev) || retry || newRev();
            const n = ++sends;
            const pending = this._pendingSend = { room, rev, n, confirmed: false };
            const el = byId(ID_TA);
            if (el) el.readOnly = true;
            let timer;

            try {
                let call;
                try {
                    call = this._isl().send(body, room, rev);
                } catch (e) {
                    pending.failed = true;
                    throw e;
                }
                const settled = Promise.resolve(call).catch(() => TIMED_OUT);
                const limit = new Promise((resolve) => {
                    timer = setTimeout(() => {
                        resolve(TIMED_OUT);
                        try {
                            call?._livewireAction?.cancel?.();
                        } catch {
                        }
                    }, SEND_WATCHDOG_MS);
                });
                let id = await Promise.race([settled, limit]);
                const lost = id === TIMED_OUT;
                if (lost) id = null;
                if (Number.isInteger(id)) {
                    this._draftSent({ id, rev });
                    if (this._lastFailed) delete this._lastFailed[room];
                    if (!(await this._awaitRow(id))) this.toast(NOT_SHOWN_MSG, 'info');
                }
                if (!pending.confirmed) {
                    pending.failed = true;
                    if (room != null) this._lastFailed = { ...this._lastFailed, [room]: rev };
                    this._undelivered(lost);
                    this._draftRepair(room);
                }
                return id;
            } finally {
                clearTimeout(timer);
                if (this._pendingSend?.n === n) {
                    const box = byId(ID_TA);
                    if (box) box.readOnly = false;
                    if (el) el.readOnly = false;
                }
            }
        },

        pingTyping() {
            const now = Date.now();
            if (now - this._lastTypingPing < TYPING_PING_MS) return;
            this._lastTypingPing = now;
            this._sendTypingPing().catch(() => {});
        },

        startPolling() {
            if (this._destroyed || this._navigating || this._timer !== null) return;
            if (!this._navWatch) {
                this._navWatch = true;
                const resume = () => {
                    if (this._destroyed) return;
                    this._navigating = false;
                    this._navWatch = false;
                    this.startPolling();
                };
                document.addEventListener('livewire:navigate', () => {
                    this._navigating = true;
                    this.stopPolling();
                }, { once: true });
                document.addEventListener('livewire:navigate-failed', resume, { once: true });
                document.addEventListener('livewire:navigate-aborted', resume, { once: true });
            }
            this._schedulePollTick();
        },

        stopPolling() {
            if (this._timer === null) return;
            clearTimeout(this._timer);
            this._timer = null;
        },

        resetPollInterval() {
            this._pollIntervalMs = POLL_BASE_MS;
        },

        async _runPollTick(force = false) {
            if (this._navigating) return;
            if (document.hidden && this.$store?.push && !this.$store.push.pollsHidden(scope)) {
                this.stopPolling();
                return;
            }
            if (this._pollInFlight) {
                if (force) this._repollQueued = true;
                return;
            }
            this._pollInFlight = true;
            if (!document.hidden) window.Livewire?.dispatch('messaging-poll');
            try {
                await this._pollTick(force);
            } finally {
                this._pollInFlight = false;
                if (this._repollQueued) {
                    this._repollQueued = false;
                    if (!this._destroyed && !document.hidden) this._repollNow();
                }
            }
        },

        _repollNow(tick = true) {
            this.resetPollInterval();
            if (this._destroyed || document.hidden) return;
            if (this._pollInFlight) {
                if (tick) this._repollQueued = true;
                return;
            }
            this.stopPolling();
            this.startPolling();
            if (tick) this._runPollTick(true).catch(() => {});
        },

        _syncSidebarAfterOwnAction(wire = this.$wire) {
            this._dropFiles();
            this._repollNow(false);
            wire.$island('sidebar').refreshUnread().catch(() => {});
        },

        initAutoResize(el) {
            if (!el || el._autoResizeInit) return;
            el._autoResizeInit = true;
            let programmatic = false;

            new ResizeObserver(() => {
                if (programmatic) {
                    programmatic = false;
                    return;
                }
                el.setAttribute(ATTR_MANUAL_RESIZE, '1');
            }).observe(el);

            el._setAutoHeight = (value) => {
                programmatic = true;
                el.style.height = value;
            };
        },

        autoGrow(el, maxPx) {
            if (!el || el._setAutoHeight === undefined) return;

            if (el._agRaf != null) cancelAnimationFrame(el._agRaf);

            el._agRaf = requestAnimationFrame(() => {
                el._agRaf = null;
                const manual = el.getAttribute(ATTR_MANUAL_RESIZE) === '1';

                if (manual) {
                    if (el.scrollHeight > el.clientHeight) el._setAutoHeight(el.scrollHeight + 'px');
                    return;
                }

                el._setAutoHeight('auto');
                el._setAutoHeight(Math.min(el.scrollHeight, maxPx) + 'px');
            });
        },

        fitEdit(el, top = false) {
            if (!el) return;
            const vp = byId(ID_VIEWPORT);
            const st = vp?.scrollTop;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 320) + 'px';
            if (vp && vp.scrollTop !== st) this.programmaticScroll(() => { vp.scrollTop = st; });
            if (top) el.scrollTop = 0;
            requestAnimationFrame(() => this.programmaticScroll(() => el.parentElement?.scrollIntoView?.({ block: 'nearest' })));
        },

        openEdit(el, tries = 3) {
            requestAnimationFrame(() => {
                if (!el?.isConnected) return;
                if (!el.getClientRects().length) {
                    if (tries) this.openEdit(el, tries - 1);
                    return;
                }
                el.focus({ preventScroll: true });
                el.selectionStart = el.selectionEnd = el.value.length;
                this.fitEdit(el, true);
            });
        },

        autoDirection(el) {
            if (!el || typeof el.value !== 'string') return;
            const dir = DIR_RE.exec(el.value)?.[1] ? 'ltr' : 'rtl';
            if (el.dir !== dir) el.dir = dir;
        },

        resetAutoResize(el) {
            if (!el) return;

            el.removeAttribute(ATTR_MANUAL_RESIZE);
            el.style.height = '';

            if (el._agRaf) {
                cancelAnimationFrame(el._agRaf);
                el._agRaf = null;
            }
        },

        _applyPollResult(changed, ceilingMs) {
            this._pollIntervalMs = changed ? POLL_BASE_MS : Math.min(this._pollIntervalMs * 2, ceilingMs);
        },

        _schedulePollTick() {
            const spread = document.hidden ? POLL_JITTER_HIDDEN : POLL_JITTER;
            const base = document.hidden && this.$store?.push?.live ? SAFETY_POLL_MS : this._pollIntervalMs;
            const delay = base * (1 - spread / 2 + Math.random() * spread);
            clearTimeout(this._timer);
            this._timer = setTimeout(() => {
                this._runPollTick().finally(() => {
                    if (this._timer !== null) this._schedulePollTick();
                });
            }, delay);
        },

        toast(message, type = 'info') {
            if (!message) return;
            window.dispatchEvent(new CustomEvent(EVENT_TOAST, { detail: { message, type } }));
        },

        markProgrammaticScroll() {
            this._programmaticScroll = true;
            this._armScrollSettle();
        },

        _armScrollSettle() {
            clearTimeout(this._scrollSettleTimer);
            this._scrollSettleTimer = setTimeout(() => { this._programmaticScroll = false; }, SCROLL_SETTLE_MS);
        },

        programmaticScroll(fn) {
            this.markProgrammaticScroll();
            fn();
        },

        scrollToBottom(smooth = false) {
            this.programmaticScroll(() => byId(ID_VIEWPORT)?.scrollTo({ top: SCROLL_TO_BOTTOM_PX, behavior: smooth ? 'smooth' : 'instant' }));
        },

        _rowsNewerThan(marker) {
            const floor = idOf(marker);
            let n = 0;
            this._msgViewportEl?.querySelectorAll(MSG_ROW_SEL).forEach(r => { if (idOf(r.dataset.rf) > floor) n++; });
            return n;
        },

        _conv(wire = this.$wire) {
            return [wire.activeChannelId, wire.activeUserId].find(Number.isInteger) ?? null;
        },

        _rowOffset(vp, rf) {
            const row = rf && vp.querySelector(`[data-rf="${rf}"]`);

            return row ? row.getBoundingClientRect().top - vp.getBoundingClientRect().top : null;
        },

        _settlePin(vp, pin) {
            const ac = new AbortController();
            const stop = () => ac.abort();
            const end = Date.now() + SETTLE_PIN_MS;

            for (const type of SETTLE_STOP_EVENTS) {
                vp.addEventListener(type, stop, { once: true, passive: true, signal: ac.signal });
            }

            const tick = () => {
                if (ac.signal.aborted) return;
                if (!vp.isConnected || !pin() || Date.now() > end) return stop();
                requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
        },

        initSwipeReply() {
            this._swipeAc?.abort();
            this._swipeAc = new AbortController();
            const { signal } = this._swipeAc;
            const sw = { on: false, engaged: false, row: null, bubble: null, id: null, pid: null, x0: 0, y0: 0, dx: 0 };

            const reset = () => {
                if (!sw.on) return;
                const bubble = sw.bubble;
                if (bubble) {
                    bubble.style.transition = 'transform 150ms';
                    bubble.style.transform = '';
                    setTimeout(() => { if (bubble.style.transition) bubble.style.transition = ''; }, 200);
                }
                sw.on = false; sw.engaged = false; sw.row = null; sw.bubble = null; sw.id = null; sw.dx = 0;
            };

            document.addEventListener('pointerdown', (e) => {
                if (sw.on || e.pointerType !== 'touch') return;
                if (e.target.closest?.('a,button,[role=button],label,input,textarea,select,[contenteditable]')) return;
                const row = e.target.closest?.(MSG_ROW_SEL);
                if (!row || !row.closest(`#${ID_VIEWPORT}`)) return;
                const bubble = row.querySelector('.msg-bubble');
                if (!bubble || !row.querySelector('button[data-body]')) return;
                sw.on = true; sw.row = row; sw.bubble = bubble; sw.id = idOf(row.dataset.rf); sw.pid = e.pointerId;
                sw.x0 = e.clientX; sw.y0 = e.clientY; sw.dx = 0;
            }, { passive: true, signal });

            document.addEventListener('pointermove', (e) => {
                if (!sw.on || e.pointerId !== sw.pid) return;
                const dx = e.clientX - sw.x0;
                const dy = e.clientY - sw.y0;
                if (!sw.engaged && (Math.abs(dx) < SWIPE_ACTIVATE_PX || Math.abs(dx) <= Math.abs(dy))) return;
                sw.engaged = true;
                sw.dx = Math.max(-SWIPE_MAX_PX, Math.min(SWIPE_MAX_PX, dx));
                sw.bubble.style.transition = '';
                sw.bubble.style.transform = `translateX(${sw.dx}px)`;
            }, { passive: true, signal });

            document.addEventListener('pointerup', (e) => {
                if (!sw.on || e.pointerId !== sw.pid) return;
                const { row, id, dx, engaged } = sw;
                const commit = engaged && Math.abs(dx) >= SWIPE_COMMIT_PX;
                reset();
                if (!commit) return;
                const btn = row.querySelector('button[data-body]');
                if (!btn) return;
                this.startReply(id, btn.dataset.sender, btn.dataset.body);
            }, { signal });

            document.addEventListener('pointercancel', (e) => { if (e.pointerId === sw.pid) reset(); }, { signal });
        },

        async loadOlderKeepingPlace(load) {
            const vp = byId(ID_VIEWPORT);
            if (!vp || this._loadingOlder) return;

            this._loadingOlder = true;
            const rf = vp.querySelector(MSG_ROW_SEL)?.dataset.rf ?? null;
            const before = this._rowOffset(vp, rf);
            const beforeHeight = vp.scrollHeight;
            const startTop = vp.scrollTop;
            const conv = this._conv();

            try {
                await load();
                await this.$nextTick();
                if (conv !== this._conv()) return;

                const now = this._rowOffset(vp, rf);

                if (before === null || now === null || vp.scrollTop !== startTop) {
                    const added = vp.scrollHeight - beforeHeight;
                    if (added > 0) this.programmaticScroll(() => { vp.scrollTop += Math.max(added - LOAD_OLDER_PEEK_PX, 0); });
                    return;
                }

                const target = before + Math.min(LOAD_OLDER_PEEK_PX, Math.max(now - before, 0));
                const pin = () => {
                    const current = this._rowOffset(vp, rf);
                    if (current === null) return false;
                    const delta = current - target;
                    if (Math.abs(delta) > 1) this.programmaticScroll(() => { vp.scrollTop += delta; });
                    return true;
                };

                pin();
                this._settlePin(vp, pin);
            } catch {
            } finally {
                this._loadingOlder = false;
            }
        },

        async loadNewer(load) {
            if (this._loadingNewer) return;

            this._loadingNewer = true;
            try {
                await load();
                await this.$nextTick();
                this._selfMarker = this._readLastMessageMarker();
            } catch {
            } finally {
                this._loadingNewer = false;
            }
        },

        backToLatest() {
            if (this.$wire.focusAnchorId == null) return this.scrollToBottom(true);
            this.$wire.$island('messages').clearFocus()
                .then(() => this.$nextTick(() => this.scrollToBottom()))
                .catch(() => {});
        },

        isEmojiOnly(text) {
            if (typeof text !== 'string' || text === '') return false;

            let result = emojiCache.get(text);
            if (result === undefined) {
                result = computeEmojiOnly(text);
                if (emojiCache.size >= EMOJI_CACHE_MAX) emojiCache.clear();
                emojiCache.set(text, result);
            }
            return result;
        },

        toggleHighlight() {
            this.isHighlighted = !this.isHighlighted;
            this.backgroundPattern = this.backgroundPattern === 'on' ? 'off' : 'on';
            queueMicrotask(() => this._persistChatSettings());
        },

        setPatternType(id) {
            this.backgroundPatternType = id;
            queueMicrotask(() => this._persistChatSettings());
        },

        fontStep(id) {
            return this.fontSteps[id] || 0;
        },

        fontAtMin(id) {
            return this.fontStep(id) <= -1;
        },

        fontAtMax(id) {
            return this.fontStep(id) >= FONT_SIZES.length - 2;
        },

        fontFamilyValue() {
            return this.messageFont === 'default' ? '' : '"' + this.messageFont + '",Yekan,IranYekan,sans-serif';
        },

        fontFamilyStyle() {
            const family = this.fontFamilyValue();
            return family ? 'font-family:' + family : '';
        },

        fontStyle(id) {
            const size = FONT_SIZES[this.fontStep(id) + 1];
            return [size ? 'font-size:' + size + 'rem' : '', this.fontFamilyStyle()].filter(Boolean).join(';');
        },

        setMessageFont(key) {
            this.messageFont = typeof key === 'string' && FONT_KEY_RE.test(key) ? key : 'default';
            queueMicrotask(() => this._persistChatSettings());
        },

        bumpFont(id, delta) {
            const next = Math.max(-1, Math.min(FONT_SIZES.length - 2, this.fontStep(id) + delta));
            const copy = { ...this.fontSteps };
            delete copy[id];
            if (next !== 0) copy[id] = next;

            const keys = Object.keys(copy);
            for (let i = 0; i < keys.length - FONT_MAX_ENTRIES; i++) delete copy[keys[i]];

            this.fontSteps = copy;
            queueMicrotask(() => this._persistChatSettings());
        },

        mineAtMin() {
            return this.mineOpacity <= MINE_STEPS[0];
        },

        mineAtMax() {
            return this.mineOpacity >= MINE_STEPS[MINE_STEPS.length - 1];
        },

        stepMine(delta) {
            const next = MINE_STEPS[MINE_STEPS.indexOf(this.mineOpacity) + delta];
            if (!next) return;

            this.mineOpacity = next;
            applyMine(next);
            try {
                const stored = JSON.parse(localStorage.getItem(settingsKey)) || {};
                localStorage.setItem(settingsKey, JSON.stringify({ ...stored, mineOpacity: next }));
            } catch {}
        },

        _persistChatSettings() {
            try {
                let stored = {};
                try { stored = JSON.parse(localStorage.getItem(settingsKey)) || {}; } catch {}
                localStorage.setItem(settingsKey, JSON.stringify({
                    ...stored,
                    isHighlighted: this.isHighlighted,
                    backgroundPattern: this.backgroundPattern,
                    backgroundPatternType: this.backgroundPatternType,
                    headerDeclutter: this.headerDeclutter && !this._menuForced,
                    fontSteps: this.fontSteps,
                    messageFont: this.messageFont
                }));
            } catch {}
        },

        _loadChatSettings() {
            applyMine(this.mineOpacity);
            try {
                const saved = localStorage.getItem(settingsKey);
                if (!saved) return;
                const d = JSON.parse(saved);
                this.isHighlighted = d.isHighlighted ?? false;
                this.backgroundPattern = d.backgroundPattern ?? 'off';
                this.backgroundPatternType = d.backgroundPatternType ?? 'mesh';
                this.headerDeclutter = d.headerDeclutter ?? false;

                const steps = {};
                for (const [id, v] of Object.entries(d.fontSteps || {})) {
                    if (Number.isInteger(v) && v >= -1 && v <= FONT_SIZES.length - 2 && v !== 0) steps[id] = v;
                }
                this.fontSteps = steps;
                this.messageFont = typeof d.messageFont === 'string' && FONT_KEY_RE.test(d.messageFont) ? d.messageFont : 'default';
                this.mineOpacity = MINE_STEPS.includes(d.mineOpacity) ? d.mineOpacity : 100;
                applyMine(this.mineOpacity);
            } catch {}
        },

        openMessageSearch() {
            const next = this.searchMessages = !this.searchMessages;
            if (!next) return;

            this.typeFilterOpen = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;
            this.$nextTick(() => byId(ID_SEARCH_INPUT)?.focus());
        },

        toggleFilterPanel() {
            const next = this.typeFilterOpen = !this.typeFilterOpen;
            if (next) {
                this.searchMessages = false;
                this.bookmarksOpen = false;
                this.daysOpen = false;
            }
        },

        toggleBookmarks() {
            const next = this.bookmarksOpen = !this.bookmarksOpen;
            if (!next) return;

            this.searchMessages = false;
            this.typeFilterOpen = false;
            this.daysOpen = false;
            const found = [];
            const missing = [];
            for (const id of this.$store.pinned.getPinned(BOOKMARKS)) {
                const btn = document.querySelector(`[data-rf$="message-${id}"] button[data-sender]`);
                if (btn) found.push({ id, body: btn.dataset.body, sender_name: btn.dataset.sender, time: '' });
                else missing.push(id);
            }

            this.bookmarkList = found.sort(byNewest);
            if (!missing.length) return;

            const token = this._bookmarkToken = (this._bookmarkToken || 0) + 1;
            this.$wire?.$island('messages').bookmarks(missing)
                .then(list => {
                    if (token !== this._bookmarkToken || !this.bookmarksOpen || !Array.isArray(list)) return;
                    const live = (b) => this.$store.pinned.isPinned(b.id, BOOKMARKS);
                    this.bookmarkList = [...found, ...list].filter(live).sort(byNewest);
                })
                .catch(() => {});
        },

        focusBookmark(id) {
            if (!id) return;
            this.bookmarksOpen = false;
            this.scrollToMessage(id);
        },

        _dayRows(days) {
            const f = formats();
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const rows = [];
            let month = '';
            for (const { day, id } of days) {
                const d = new Date(`${day}T00:00:00`);
                const monthLabel = f.month.format(d);
                if (monthLabel !== month) rows.push({ header: true, day: `m-${monthLabel}`, label: month = monthLabel });
                const diff = Math.round((today - d) / 864e5);
                rows.push({
                    day, id,
                    label: diff === 0 ? 'امروز' : diff === 1 ? 'دیروز' : f.day.format(d),
                    weekday: diff > 1 ? f.weekday.format(d) : '',
                    today: diff === 0,
                });
            }
            return rows;
        },

        _setDays(days) {
            this.dayList = days;
            this.dayRows = this._dayRows(days);
        },

        toggleDays() {
            const next = this.daysOpen = !this.daysOpen;
            if (!next) return;

            this.searchMessages = false;
            this.typeFilterOpen = false;
            this.bookmarksOpen = false;
            this.daysLoading = false;

            const loaded = [];
            byId(ID_VIEWPORT)?.querySelectorAll(DAY_SEL).forEach(el => {
                let row = el.nextElementSibling;
                while (row && !MSG_ROW_RE.test(row.dataset.rf || '')) row = row.nextElementSibling;
                const id = parseInt(row?.dataset.rf.match(ID_TAIL_RE)[1], 10);
                if (id) loaded.push({ day: el.dataset.day, id });
            });

            const token = this._daysToken = (this._daysToken || 0) + 1;
            const merge = (server) => {
                const firstIds = new Map(server.map(d => [d.day, d.id]));
                for (const d of loaded) d.id = Math.min(d.id, firstIds.get(d.day) ?? d.id);
                const have = new Set(loaded.map(d => d.day));
                this._setDays([...loaded, ...server.filter(d => !have.has(d.day))].sort(byNewestDay));
            };
            this._setDays(loaded.sort(byNewestDay));
            if (!loaded.length || (!this.$wire.hasOlder && this.$wire.focusAnchorId == null)) return;

            const conv = this._conv();
            const cached = this._daysCache;
            if (cached && cached.conv === conv && Date.now() - cached.at < DAYS_CLIENT_TTL_MS) return merge(cached.list);

            this.daysLoading = true;
            this.$wire.$island('messages').days()
                .then(older => {
                    if (!Array.isArray(older)) return;
                    this._daysCache = { conv, at: Date.now(), list: older };
                    if (token === this._daysToken && this.daysOpen) merge(older);
                })
                .catch(() => {})
                .finally(() => { if (token === this._daysToken) this.daysLoading = false; });
        },

        async react(id, emoji) {
            const wire = this.$wire;
            await wire.$island('messages').toggleReaction(id, emoji).catch(() => {});

            const fresh = document.querySelector(`[data-reaction-chip="${id}"][data-emoji="${emoji}"]`);
            if (fresh && fresh.classList.contains('reaction-mine')) this._burstChip(fresh);
        },

        _burstChip(chip) {
            chip.classList.remove('burst-now');
            void chip.offsetWidth;
            chip.classList.add('burst-now');
            clearTimeout(chip._burstTimer);
            chip._burstTimer = setTimeout(() => chip.classList.remove('burst-now'), 1100);
        },

        resetRoomScoped() {
            this.filesOpen = false;
            this.filesList = [];
            this.filesLoading = false;
            this.filesError = false;
            this._filesToken = (this._filesToken || 0) + 1;
            this.bookmarkList = [];
            this._bookmarkToken = (this._bookmarkToken || 0) + 1;
            this.typeFilter = '';
            this.typeFilterOpen = false;
        },

        _editDirty() { return false; },

        holdDraft(id, el, wire = this.$wire) {
            const room = this._draftRoom();
            const text = el?.value ?? '';
            const textKept = room != null && drafts.get(room)?.body === text;
            const dirty = (text.trim() !== '' && !textKept) || (wire.composer?.attachments?.length ?? 0) > 0 || this._editDirty(wire);
            if (!dirty) return false;
            if (this._leaveArm === id && Date.now() < this._leaveUntil) {
                this._leaveArm = null;
                return false;
            }
            this._leaveArm = id;
            this._leaveUntil = Date.now() + LEAVE_DRAFT_MS;
            this.toast('با خروج، پیوست‌ها و تغییرات ذخیره‌نشده از دست می‌روند. برای خروج، دوباره بزنید.', 'warning');
            return true;
        },

        jumpToDay(item) {
            if (!item?.id) return;
            this.daysOpen = false;
            this.scrollToMessage(item.id);
        },

        toggleFiles() {
            this.filesOpen = !this.filesOpen;
            if (!this.filesOpen) return;

            this.filesFilter = 'all';
            const conv = this._conv();
            const cached = this._filesCache;

            if (cached && cached.conv === conv && Date.now() - cached.at < DAYS_CLIENT_TTL_MS) {
                this.filesList = cached.list;
                this.filesLoading = false;
                return;
            }

            const token = this._filesToken = (this._filesToken || 0) + 1;
            const stale = !cached || cached.conv !== conv;

            this.filesList = stale ? [] : cached.list;
            this.filesLoading = stale;
            this.filesError = false;

            this.$wire?.$island('messages').files()
                .then(list => {
                    if (token !== this._filesToken || !Array.isArray(list)) return;
                    this.filesList = list;
                    this._filesCache = { conv, at: Date.now(), list };
                })
                .catch(() => { if (token === this._filesToken) this.filesError = true; })
                .finally(() => { if (token === this._filesToken) this.filesLoading = false; });
        },

        _dropFiles() {
            const loading = this.filesLoading;
            this._filesCache = null;
            this._filesToken = (this._filesToken || 0) + 1;
            this.filesLoading = false;
            if (loading && this.filesOpen) this.retryFiles();
        },

        retryFiles() {
            this.filesOpen = false;
            this.toggleFiles();
        },

        downloadingAttachments: false,
        async downloadMessageFiles(msgId, indices) {
            if (this.downloadingAttachments) return;
            const wire = this.$wire;
            const conv = this._conv(wire);
            this.downloadingAttachments = true;
            this.open = false;
            try {
                for (const index of indices) {
                    if (!wire.$el.isConnected || conv !== this._conv(wire)) break;
                    await wire.$island('messages').downloadAttachment(msgId, index);
                }
            } catch {
                this.toast('دانلود متوقف شد. فایل‌های باقی‌مانده را جداگانه دانلود کنید.', 'error');
            } finally {
                this.downloadingAttachments = false;
            }
        },

        downloadGalleryFile(msgId, index) {
            this.$wire.downloadAttachment(msgId, index);
        },

        removeBookmark(id) {
            if (this.$store.pinned.isPinned(id, BOOKMARKS)) this.$store.pinned.togglePin(id, BOOKMARKS);
            this.bookmarkList = this.bookmarkList.filter(b => b.id !== id);
        },

        focusSearchResult(id) {
            if (!id) return;
            this.searchMessages = false;
            this.$wire?.$island('messages').focusMessage(id).catch(() => {});
        },

        insertEmoji(e) {
            if (typeof e !== 'string') return;

            const ta = byId(ID_TA);
            if (!ta || ta.readOnly) return;

            const s = ta.selectionStart;
            const val = ta.value;

            const next = val.substring(0, s) + e + val.substring(s);
            ta.value = next;
            this._draftSave(this._draftRoom(), next);
            this.$wire.set('composer.body', next, false);

            this.$nextTick(() => {
                ta.focus();
                ta.selectionStart = ta.selectionEnd = s + e.length;
            });
        },

        copyMessage(text) {
            if (typeof text !== 'string' || text === '') return;
            this.copyText(text, 'پیام کپی شد', 'info');
        },

        toggleTypeFilter(t) {
            const cur = this.typeFilter;
            this.typeFilter = cur.includes(t) ? cur.replace(t, '') : cur + t;
        },

        clearAllFilters() {
            this.typeFilter = '';
            if (this.activeSender != null) this.clearSenderFilter();
        },

        passesTypeFilter(types) {
            const filter = this.typeFilter;
            if (filter === '') return true;
            if (!types) return false;

            const len = types.length;
            let start = 0;

            while (start < len) {
                let end = types.indexOf(' ', start);
                if (end === -1) end = len;
                if (end > start && filter.includes(types.substring(start, end))) return true;
                start = end + 1;
            }
            return false;
        },

        syncChipScroll() {
            const el = byId(ID_CHIP_TRACK);
            if (!el) return;

            const max = el.scrollWidth - el.clientWidth;
            const pos = Math.abs(el.scrollLeft);
            const prev = max > 2 && pos > 2;
            const next = pos < max - 2;

            const state = this.chipScroll;
            if (state.prev !== prev) state.prev = prev;
            if (state.next !== next) state.next = next;
        },

        pageChips(dir) {
            const el = byId(ID_CHIP_TRACK);
            if (el) el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
        },

        useQuoteChip() {
            const q = this.quoteChip;
            if (q?.visible && q.id) this.startReply(q.id, q.sender, q.snippet);
        },

        cancelReply() {
            this.replyingTo = null;
            this.$wire?.cancelReply();
        },

        cancelDelete() {
            this.deletingId = null;
        },

        async deleteMessage() {
            const id = this.deletingId;
            if (!id) return;

            const wire = this.$wire;
            try {
                await wire.$island('messages').deleteMessage(id);
                this.deletingId = null;
                this.openActionsId = null;
                this._syncSidebarAfterOwnAction(wire);
            } catch {
                this.toast('خطا در حذف پیام.', 'error');
            }
        },

        _ghostShow(body) {
            const viewport = byId(ID_VIEWPORT);
            if (!viewport) return null;

            const el = document.createElement('div');
            el.className = 'flex items-end gap-2 opacity-60 justify-start';
            el.setAttribute('aria-hidden', 'true');
            el.setAttribute('data-ghost', '');
            el.innerHTML = GHOST_HTML;
            el.querySelector('[dir="auto"]').textContent = body;
            const time = el.querySelector('time');
            const started = Date.now();
            el._timer = setInterval(() => {
                time.textContent = `${formats().seconds.format(Math.floor((Date.now() - started) / 1000))} ث`;
            }, GHOST_TICK_MS);
            viewport.appendChild(el);
            this._ghost = el;
            this.scrollToBottom();
            return el;
        },

        _ghostHide(el = this._ghost) {
            if (!el) return;
            clearInterval(el._timer);
            el.remove();
            if (this._ghost === el) this._ghost = null;
        },

        destroy() {
            this.stopPolling();
            clearTimeout(this._scrollSettleTimer);
            if (this.undoTimeout !== null) clearTimeout(this.undoTimeout);
        }
    };
}
