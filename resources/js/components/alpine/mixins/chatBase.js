import { emojis } from "../stores/emoji.js";
import highlightMatchMixin from "./highlightMatch.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));
const HTML_TAG_RE = /<[^>]*>/g;
const WS_ONLY_RE = /^\s+$/;
const DIR_RE = /[A-Za-z؀-ۿ]/;

const SCROLL_TO_BOTTOM_PX = 999999;
const POLL_BASE_MS = 10000;

const EVENT_TOAST = 'toast';
const ID_VIEWPORT = 'msg-viewport';
const ID_TA = 'msg-ta';
const ID_CHIP_TRACK = 'sender-chip-track';
const ID_SEARCH_INPUT = 'msg-search-input';

const ATTR_MANUAL_RESIZE = 'data-manual-resize';

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

        pingTyping() {
            const now = Date.now();
            if (now - this._lastTypingPing < 3000) return;
            this._lastTypingPing = now;
            this._sendTypingPing().catch(() => {});
        },

        startPolling() {
            if (this._timer !== null) return;
            this._schedulePollTick();
        },

        stopPolling() {
            if (this._timer !== null) {
                clearTimeout(this._timer);
                this._timer = null;
            }
        },

        resetPollInterval() {
            this._pollIntervalMs = POLL_BASE_MS;
        },

        initAutoResize(el) {
            if (el === null || el._autoResizeInit) return;
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
            if (el === null || el._setAutoHeight === undefined) return;

            if (el._agRaf !== null && el._agRaf !== undefined) {
                cancelAnimationFrame(el._agRaf);
            }

            el._agRaf = requestAnimationFrame(() => {
                el._agRaf = null;

                if (el.getAttribute(ATTR_MANUAL_RESIZE) === '1') {
                    const sh = el.scrollHeight;
                    if (sh > el.clientHeight) el._setAutoHeight(sh + 'px');
                    return;
                }

                el._setAutoHeight('auto');
                const sh = el.scrollHeight;
                el._setAutoHeight((sh < maxPx ? sh : maxPx) + 'px');
            });
        },

        autoDirection(el) {
            if (el === null || typeof el.value !== 'string') return;

            const val = el.value;
            const idx = val.search(DIR_RE);
            let nextDir = 'rtl';

            if (idx !== -1) {
                const c = val.charCodeAt(idx);
                if ((c >= 65 && c <= 90) || (c >= 97 && c <= 122)) {
                    nextDir = 'ltr';
                }
            }

            if (el.dir !== nextDir) {
                el.dir = nextDir;
            }
        },

        resetAutoResize(el) {
            if (el === null) return;

            el.removeAttribute(ATTR_MANUAL_RESIZE);
            el.style.height = '';

            if (el._agRaf) {
                cancelAnimationFrame(el._agRaf);
                el._agRaf = null;
            }
        },

        _applyPollResult(changed, ceilingMs) {
            const next = this._pollIntervalMs * 2;
            this._pollIntervalMs = changed ? POLL_BASE_MS : (next < ceilingMs ? next : ceilingMs);
        },

        _schedulePollTick() {
            const delay = this._pollIntervalMs * (0.9 + Math.random() * 0.2);
            this._timer = setTimeout(() => {
                this._pollTick().finally(() => {
                    if (this._timer !== null) this._schedulePollTick();
                });
            }, delay);
        },

        toast(message, type = 'info') {
            if (!message) return;
            window.dispatchEvent(new CustomEvent(EVENT_TOAST, { detail: { message, type } }));
        },

        scrollToBottom(smooth = false) {
            const vp = document.getElementById(ID_VIEWPORT);
            if (vp === null) return;

            vp.scrollTo({
                top: SCROLL_TO_BOTTOM_PX,
                behavior: smooth ? 'smooth' : 'instant'
            });
        },

        isEmojiOnly(text) {
            if (typeof text !== 'string' || text === '') return false;

            const stripped = text.replace(HTML_TAG_RE, '').trim();
            if (stripped === '') return false;

            const segments = segmenter.segment(stripped);
            // Avoid object destructuring {segment} in hot loops
            for (const data of segments) {
                const seg = data.segment;
                if (!emojiSet.has(seg) && !WS_ONLY_RE.test(seg)) {
                    return false;
                }
            }
            return true;
        },

        toggleHighlight() {
            const next = !this.isHighlighted;
            this.isHighlighted = next;
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
            const next = !this.searchMessages;
            this.searchMessages = next;

            if (next) {
                this.typeFilterOpen = false;
                this.$nextTick(() => {
                    const input = document.getElementById(ID_SEARCH_INPUT);
                    if (input !== null) input.focus();
                });
            }
        },

        toggleFilterPanel() {
            const next = !this.typeFilterOpen;
            this.typeFilterOpen = next;
            if (next) this.searchMessages = false;
        },

        focusSearchResult(id) {
            if (!id) return;
            this.searchMessages = false;
            if (this.$wire !== undefined) {
                this.$wire.$island('messages').focusMessage(id).catch(() => {});
            }
        },

        insertEmoji(e) {
            if (typeof e !== 'string') return;

            const ta = document.getElementById(ID_TA);
            if (ta === null) return;

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
            const current = this.typeFilter;
            if (current.includes(t)) {
                this.typeFilter = current.replace(t, '');
            } else {
                this.typeFilter = current + t;
            }
        },

        clearAllFilters() {
            this.typeFilter = '';
            if (this.activeSender != null) this.clearSenderFilter();
        },

        passesTypeFilter(types) {
            const filter = this.typeFilter;
            if (filter === '') return true;
            if (types === undefined || types === null || types === '') return false;

            let start = 0;
            const len = types.length;

            while (start < len) {
                let end = types.indexOf(' ', start);
                if (end === -1) end = len;

                if (end > start) {
                    if (filter.includes(types.substring(start, end))) return true;
                }
                start = end + 1;
            }
            return false;
        },

        syncChipScroll() {
            const el = document.getElementById(ID_CHIP_TRACK);
            if (el === null) return;

            const max = el.scrollWidth - el.clientWidth;
            const pos = Math.abs(el.scrollLeft);

            const prev = max > 2 && pos > 2;
            const next = pos < max - 2;

            const state = this.chipScroll;
            if (state.prev !== prev) state.prev = prev;
            if (state.next !== next) state.next = next;
        },

        pageChips(dir) {
            const el = document.getElementById(ID_CHIP_TRACK);
            if (el === null) return;
            el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
        },

        useQuoteChip() {
            const q = this.quoteChip;
            if (q === undefined || !q.visible || !q.id) return;
            this.startReply(q.id, q.sender, q.snippet);
        },

        cancelReply() {
            this.replyingTo = null;
            if (this.$wire !== undefined) {
                this.$wire.cancelReply();
            }
        },

        cancelDelete() {
            this.deletingId = null;
        },

        async deleteMessage() {
            const id = this.deletingId;
            if (!id) return;

            try {
                await this.$wire.$island('messages').deleteMessage(id);
                this.deletingId = null;
                this.openActionsId = null;
            } catch {
                this.toast('خطا در حذف پیام.', 'error');
            }
        },

        destroy() {
            this.stopPolling();
            if (this.undoTimeout !== null) {
                clearTimeout(this.undoTimeout);
            }
        }
    };
}
