import { emojis } from "../stores/emoji.js";
import highlightMatchMixin from "./highlightMatch.js";
import headerDeclutterMixin from "./headerDeclutter.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));
const emojiCache = new Map();
const EMOJI_CACHE_MAX = 1000;
const HTML_TAG_RE = /<[^>]*>/g;
const WS_ONLY_RE = /^\s+$/;
const DIR_RE = /([A-Za-z])|[؀-ۿ]/;

const SCROLL_TO_BOTTOM_PX = 999999;
const POLL_BASE_MS = 10000;
const INCOMING_DEDUPE_MS = 1500;

const EVENT_TOAST = 'toast';
const ID_VIEWPORT = 'msg-viewport';
const DAYS_CLIENT_TTL_MS = 300000;
let fmt;
const formats = () => fmt ??= {
    day: new Intl.DateTimeFormat('fa-IR-u-ca-persian', { day: 'numeric', month: 'long' }),
    month: new Intl.DateTimeFormat('fa-IR-u-ca-persian', { month: 'long', year: 'numeric' }),
    weekday: new Intl.DateTimeFormat('fa-IR', { weekday: 'long' }),
};
const ID_TA = 'msg-ta';
const ID_CHIP_TRACK = 'sender-chip-track';
const ID_SEARCH_INPUT = 'msg-search-input';

const ATTR_MANUAL_RESIZE = 'data-manual-resize';

const byId = (id) => document.getElementById(id);

function computeEmojiOnly(text) {
    const stripped = text.replace(HTML_TAG_RE, '').trim();
    if (stripped === '') return false;

    for (const data of segmenter.segment(stripped)) {
        const seg = data.segment;
        if (!emojiSet.has(seg) && !WS_ONLY_RE.test(seg)) return false;
    }
    return true;
}

export default function chatBase(scope) {
    const settingsKey = 'chat-settings:' + scope;

    return {
        ...highlightMatchMixin(),
        ...headerDeclutterMixin(),
        _lastIncomingAt: 0,
        _ringIncoming(id) {
            const now = Date.now();
            if (now - this._lastIncomingAt < INCOMING_DEDUPE_MS) return;
            this._lastIncomingAt = now;
            this.$store.sound?.playIncoming(id, scope);
        },
        showScrollFab: false,
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
        _onVisibility: null,
        _onScroll: null,
        _loadingOlder: false,
        _selRaf: null,
        _pollIntervalMs: POLL_BASE_MS,
        _lastTypingPing: 0,
        _pollInFlight: false,
        _repollQueued: false,
        _destroyed: false,

        pingTyping() {
            const now = Date.now();
            if (now - this._lastTypingPing < 3000) return;
            this._lastTypingPing = now;
            this._sendTypingPing().catch(() => {});
        },

        startPolling() {
            if (this._destroyed || this._timer !== null) return;
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
            if (this._pollInFlight) {
                if (force) this._repollQueued = true;
                return;
            }
            this._pollInFlight = true;
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
            const delay = this._pollIntervalMs * (0.9 + Math.random() * 0.2);
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

        scrollToBottom(smooth = false) {
            byId(ID_VIEWPORT)?.scrollTo({ top: SCROLL_TO_BOTTOM_PX, behavior: smooth ? 'smooth' : 'instant' });
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

        _persistChatSettings() {
            try {
                localStorage.setItem(settingsKey, JSON.stringify({
                    isHighlighted: this.isHighlighted,
                    backgroundPattern: this.backgroundPattern,
                    backgroundPatternType: this.backgroundPatternType,
                    headerDeclutter: this.headerDeclutter && !this._menuForced
                }));
            } catch {}
        },

        _loadChatSettings() {
            try {
                const saved = localStorage.getItem(settingsKey);
                if (!saved) return;
                const d = JSON.parse(saved);
                this.isHighlighted = d.isHighlighted ?? false;
                this.backgroundPattern = d.backgroundPattern ?? 'off';
                this.backgroundPatternType = d.backgroundPatternType ?? 'mesh';
                this.headerDeclutter = d.headerDeclutter ?? false;
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
            const byNewest = (a, b) => b.id - a.id;
            const found = [];
            const missing = [];
            for (const id of this.$store.pinned.getPinned('message')) {
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
                    const live = (b) => this.$store.pinned.isPinned(b.id, 'message');
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
                if (monthLabel !== month) rows.push({ header: true, day: `m-${day.slice(0, 7)}`, label: month = monthLabel });
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
            byId(ID_VIEWPORT)?.querySelectorAll('[data-day]').forEach(el => {
                let row = el.nextElementSibling;
                while (row && !/message-\d+$/.test(row.dataset.rf || '')) row = row.nextElementSibling;
                const id = parseInt(row?.dataset.rf.match(/(\d+)$/)[1], 10);
                if (id) loaded.push({ day: el.dataset.day, id });
            });

            const byNewestDay = (a, b) => (a.day < b.day ? 1 : -1);
            const token = this._daysToken = (this._daysToken || 0) + 1;
            const merge = (server) => {
                const firstIds = new Map(server.map(d => [d.day, d.id]));
                for (const d of loaded) d.id = Math.min(d.id, firstIds.get(d.day) ?? d.id);
                const have = new Set(loaded.map(d => d.day));
                this._setDays([...loaded, ...server.filter(d => !have.has(d.day))].sort(byNewestDay));
            };
            this._setDays(loaded.sort(byNewestDay));
            if (!loaded.length || (!this.$wire.hasOlder && this.$wire.focusAnchorId == null)) return;

            const conv = this.$wire.activeUserId ?? this.$wire.activeChannelId;
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

        jumpToDay(item) {
            if (!item?.id) return;
            this.daysOpen = false;
            this.scrollToMessage(item.id);
        },

        toggleFiles() {
            this.filesOpen = !this.filesOpen;
            if (!this.filesOpen) return;

            this.filesFilter = 'all';
            const conv = this.$wire.activeChannelId ?? this.$wire.activeUserId;
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

            this.$wire?.$island('messages').files()
                .then(list => {
                    if (token !== this._filesToken || !Array.isArray(list)) return;
                    this.filesList = list;
                    this._filesCache = { conv, at: Date.now(), list };
                })
                .catch(() => {})
                .finally(() => { if (token === this._filesToken) this.filesLoading = false; });
        },

        downloadGalleryFile(msgId, index) {
            this.$wire.downloadAttachment(msgId, index);
        },

        removeBookmark(id) {
            if (this.$store.pinned.isPinned(id, 'message')) this.$store.pinned.togglePin(id, 'message');
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
            if (!ta) return;

            const s = ta.selectionStart;
            const val = ta.value;

            this.$wire.set('composer.body', val.substring(0, s) + e + val.substring(s));

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

        destroy() {
            this.stopPolling();
            if (this.undoTimeout !== null) clearTimeout(this.undoTimeout);
        }
    };
}
