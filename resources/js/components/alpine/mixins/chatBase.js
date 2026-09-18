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

export default function chatBase(scope) {
    const settingsKey = `chat-settings:${scope}`;

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
            if (this._timer) return;
            this._schedulePollTick();
        },

        stopPolling() {
            if (this._timer) clearTimeout(this._timer);
            this._timer = null;
        },

        resetPollInterval() {
            this._pollIntervalMs = POLL_BASE_MS;
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
                el.dataset.manualResize = '1';
            }).observe(el);

            el._setAutoHeight = (value) => {
                programmatic = true;
                el.style.height = value;
            };
        },

        autoGrow(el, maxPx) {
            if (!el || !el._setAutoHeight) return;

            if (el._agRaf) cancelAnimationFrame(el._agRaf);

            el._agRaf = requestAnimationFrame(() => {
                el._agRaf = null;

                if (el.dataset.manualResize === '1') {
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
            if (!el || typeof el.value !== 'string') return;

            const val = el.value;
            const idx = val.search(DIR_RE);
            let dir = 'rtl';

            if (idx !== -1) {
                const c = val.charCodeAt(idx);
                if ((c >= 65 && c <= 90) || (c >= 97 && c <= 122)) {
                    dir = 'ltr';
                }
            }

            if (el.dir !== dir) {
                el.dir = dir;
            }
        },

        resetAutoResize(el) {
            if (!el) return;
            delete el.dataset.manualResize;
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
            const jitter = this._pollIntervalMs * (0.9 + Math.random() * 0.2);
            this._timer = setTimeout(() => {
                this._pollTick().finally(() => {
                    if (this._timer !== null) this._schedulePollTick();
                });
            }, jitter);
        },

        toast(message, type = 'info') {
            if (!message) return;
            window.dispatchEvent(new CustomEvent(EVENT_TOAST, { detail: { message, type } }));
        },

        scrollToBottom(smooth = false) {
            const vp = document.getElementById(ID_VIEWPORT);
            if (!vp) return;

            vp.scrollTo({
                top: SCROLL_TO_BOTTOM_PX,
                behavior: smooth ? 'smooth' : 'instant'
            });
        },

        isEmojiOnly(text) {
            if (!text || typeof text !== 'string') return false;
            const stripped = text.replace(HTML_TAG_RE, '').trim();
            if (!stripped) return false;

            const segments = segmenter.segment(stripped);
            for (const { segment } of segments) {
                if (!emojiSet.has(segment) && !WS_ONLY_RE.test(segment)) return false;
            }
            return true;
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
            this.searchMessages = !this.searchMessages;
            if (this.searchMessages) {
                this.$nextTick(() => {
                    const input = document.getElementById(ID_SEARCH_INPUT);
                    if (input) input.focus();
                });
            }
        },

        focusSearchResult(id) {
            if (!id) return;
            this.searchMessages = false;
            this.$wire.$island('messages').focusMessage(id).catch(() => {});
        },

        insertEmoji(e) {
            if (typeof e !== 'string') return;
            const ta = document.getElementById(ID_TA);
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
            if (typeof text !== 'string' || !text) return;
            this.copyText(text, 'پیام کپی شد', 'info');
        },

        toggleTypeFilter(t) {
            const current = this.typeFilter;
            this.typeFilter = current.includes(t)
                ? current.replace(t, '')
                : current + t;
        },

        clearAllFilters() {
            this.typeFilter = '';
            if (this.activeSender != null) this.clearSenderFilter();
        },

        passesTypeFilter(types) {
            const filter = this.typeFilter;
            if (!filter) return true;
            if (!types) return false;

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
            if (!el) return;

            const cw = el.clientWidth;
            const max = el.scrollWidth - cw;
            const pos = Math.abs(el.scrollLeft);

            const prev = max > 2 && pos > 2;
            const next = pos < max - 2;

            if (this.chipScroll.prev !== prev) this.chipScroll.prev = prev;
            if (this.chipScroll.next !== next) this.chipScroll.next = next;
        },

        pageChips(dir) {
            const el = document.getElementById(ID_CHIP_TRACK);
            if (!el) return;
            el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
        },

        useQuoteChip() {
            if (!this.quoteChip?.visible || !this.quoteChip?.id) return;
            this.startReply(this.quoteChip.id, this.quoteChip.sender, this.quoteChip.snippet);
        },

        cancelReply() {
            this.replyingTo = null;
            this.$wire.cancelReply();
        },

        cancelDelete() {
            this.deletingId = null;
        },

        async deleteMessage() {
            if (!this.deletingId) return;

            try {
                await this.$wire.$island('messages').deleteMessage(this.deletingId);
                this.deletingId = null;
                this.openActionsId = null;
            } catch {
                this.toast('خطا در حذف پیام.', 'error');
            }
        },

        destroy() {
            this.stopPolling();
            if (this.undoTimeout) clearTimeout(this.undoTimeout);
        }
    };
}
