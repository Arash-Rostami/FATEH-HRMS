import { emojis } from "../stores/emoji.js";
import highlightMatchMixin from "./highlightMatch.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));
const emojiCache = new Map();
const EMOJI_CACHE_MAX = 1000;
const HTML_TAG_RE = /<[^>]*>/g;
const WS_ONLY_RE = /^\s+$/;
const DIR_RE = /([A-Za-z])|[؀-ۿ]/;

const SCROLL_TO_BOTTOM_PX = 999999;
const POLL_BASE_MS = 10000;

const EVENT_TOAST = 'toast';
const ID_VIEWPORT = 'msg-viewport';
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
        showScrollFab: false,
        showInfo: false,
        searchMessages: false,
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
                    backgroundPatternType: this.backgroundPatternType
                }));
            } catch {}
        },

        openMessageSearch() {
            const next = this.searchMessages = !this.searchMessages;
            if (!next) return;

            this.typeFilterOpen = false;
            this.$nextTick(() => byId(ID_SEARCH_INPUT)?.focus());
        },

        toggleFilterPanel() {
            const next = this.typeFilterOpen = !this.typeFilterOpen;
            if (next) this.searchMessages = false;
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
            this.emojiOpen = false;

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
