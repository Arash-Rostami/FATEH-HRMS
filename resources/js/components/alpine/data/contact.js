import settings from "./settings.js";

import maximizeMixin from "../mixins/maximize.js";
import lastOpenMixin from "../mixins/lastOpen.js";
import sidebarRailMixin from "../mixins/sidebarRail.js";
import clipboardMixin from "../mixins/clipboard.js";
import pasteImageMixin from "../mixins/pasteImage.js";
import chatBase from "../mixins/chatBase.js";
import fancyboxMixin from "../mixins/fancybox.js";

const SCOPE = 'contact';
const OPEN_POLL_CEILING_MS = 20000;
const IDLE_POLL_CEILING_MS = 30000;
const MOBILE_BREAKPOINT = 768;
const MAX_BODY_LENGTH = 2000;
const UNDO_TOAST_MS = 4000;
const POST_SEND_SCROLL_DELAY_MS = 120;
const SEND_LOCK_RESET_MS = 500;
const LOAD_OLDER_SCROLL_THRESHOLD = 80;
const SCROLL_FAB_DISTANCE_THRESHOLD = 200;
const MSG_VIEWPORT_ID = 'msg-viewport';
const MSG_TEXTAREA_ID = 'msg-ta';
const TOTAL_UNREAD_ATTR = 'data-total-unread';
const DATA_RF_MESSAGE_PREFIX = 'message';
const INPUT_TAGS = new Set(['INPUT', 'TEXTAREA', 'SELECT']);
const SEARCH_FOCUS_PARAM = 'focus_msg';
const POLL_FALLBACK_EVERY = 10;
const TOO_LONG_MSG = 'متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.';

const RF_ID_OFFSET = DATA_RF_MESSAGE_PREFIX.length + 1;
const ROW_SEL = `[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`;

const ta = () => document.getElementById(MSG_TEXTAREA_ID);

export default function contact() {
    let ac, vpAc, scrollRaf, selRaf, unreadObs, digest, ticks = 0, selectSeq = 0;

    return {
        ...maximizeMixin('contact'),
        ...lastOpenMixin('contact'),
        ...sidebarRailMixin('contact'),
        ...clipboardMixin(),
        ...pasteImageMixin(),
        ...chatBase(SCOPE),
        ...fancyboxMixin(),
        bgOption: 'a',
        editingMsg: null,
        quoteChip: { visible: false, x: 0, y: 0, id: null, sender: '', snippet: '' },

        init() {
            this.initFancybox();
            this.initPattern();
            this.syncPushNotify();

            unreadObs = new MutationObserver(() => this.syncPushNotify());
            unreadObs.observe(document.body, { subtree: true, attributes: true, attributeFilter: [TOTAL_UNREAD_ATTR] });

            this._loadChatSettings();
            this.headerDeclutterMenuBridge();

            this.startPolling();

            ac = new AbortController();
            const { signal } = ac;

            document.addEventListener('visibilitychange', () => document.hidden ? this.stopPolling() : this._repollNow(), { signal });

            this._bindViewport();

            document.addEventListener('selectionchange', () => {
                if (selRaf) return;
                selRaf = requestAnimationFrame(() => {
                    selRaf = null;
                    this._updateQuoteChip();
                });
            }, { signal });

            document.addEventListener('keydown', (e) => {
                if (e.key !== '/' || e.isComposing) return;
                const el = document.activeElement;
                if (INPUT_TAGS.has(el?.tagName) || el?.isContentEditable) return;
                if (this.searchMessages) return;
                e.preventDefault();
                this.openMessageSearch();
            }, { signal });

            this.$wire.on('message-sent', () => {
                this.scrollToBottom(true);
                this.sending = false;
                this.$store.sound?.playOutgoing(this.$wire.activeUserId, SCOPE);
                setTimeout(() => this.scrollToBottom(true), POST_SEND_SCROLL_DELAY_MS);
            });

            this.$wire.on('message-error', () => this.$nextTick(() => { this.sending = false; }));
            this.$wire.on('show-toast', (e) => this.toast(e.message, e.type ?? 'info'));
            this.$wire.on('show-undo-toast', (e) => this.toast(e.message, 'warning'));
            this.$wire.on('attachments-updated', () => {
                this._isl().syncAttachments().catch(() => {});
            });

            this.$watch('$wire.lastDeleted', (val) => {
                clearTimeout(this.undoTimeout);
                this.showUndo = !!val;
                if (val) this.undoTimeout = setTimeout(() => { this.showUndo = false; }, UNDO_TOAST_MS);
            });

            const focusMsg = parseInt(new URLSearchParams(location.search).get(SEARCH_FOCUS_PARAM), 10) || 0;
            if (this.$wire.activeUserId && focusMsg <= 0) {
                this.$nextTick(() => {
                    this.scrollToBottom(false);
                    if (innerWidth < MOBILE_BREAKPOINT) ta()?.focus();
                });
            }

            const lastId = this.$wire.activeUserId ? null : this._readLastOpenId();
            if (lastId) this.selectContact(lastId).finally(() => this.initMaximize());
            else this.initMaximize();
        },

        destroy() {
            this._destroyed = true;
            this.stopPolling();
            ac?.abort();
            vpAc?.abort();
            unreadObs?.disconnect();
            cancelAnimationFrame(scrollRaf);
            cancelAnimationFrame(selRaf);
        },

        // #msg-viewport lives inside the messages island's pane-active branch, destroyed and
        // re-mounted every time a conversation opens/closes — this component's own init() runs
        // once, so a reference captured there goes stale (null forever if mounted with no
        // conversation open, detached after the first close-then-reopen). Re-binding here, called
        // from both init() and every selectContact() resolution, keeps scroll-to-load-older, the
        // scroll FAB, and the quote-chip feature working across conversation switches.
        _bindViewport() {
            const vp = document.getElementById(MSG_VIEWPORT_ID);
            this._msgViewportEl = vp;
            if (!vp) return;

            vp.style.overflowAnchor = 'none';
            vpAc?.abort();
            vpAc = new AbortController();

            vp.addEventListener('scroll', () => {
                if (scrollRaf) return;
                scrollRaf = requestAnimationFrame(() => {
                    scrollRaf = null;
                    this.quoteChip.visible = false;
                    this.openActionsId = null;

                    const { scrollTop: st, scrollHeight: sh, clientHeight: ch } = vp;
                    this.showScrollFab = sh - st - ch > SCROLL_FAB_DISTANCE_THRESHOLD;

                    if (st < LOAD_OLDER_SCROLL_THRESHOLD && !this._loadingOlder && this.$wire.hasOlder) {
                        this._loadingOlder = true;
                        this._isl().loadMoreMessages()
                            .then(() => this.$nextTick(() => {
                                const delta = vp.scrollHeight - sh;
                                if (delta > 0) vp.scrollTop += delta;
                            }))
                            .catch(() => {})
                            .finally(() => { this._loadingOlder = false; });
                    }
                });
            }, { passive: true, signal: vpAc.signal });
        },

        _isl() { return this.$wire.$island('messages'); },
        _sidebar() { return this.$wire.$island('sidebar').refreshUnread(); },

        _overLimit(body) {
            if (body.length <= MAX_BODY_LENGTH) return false;
            this.toast(TOO_LONG_MSG, 'warning');
            return true;
        },

        async _pollTick(force = false) {
            const fullSidebar = force || ++ticks % POLL_FALLBACK_EVERY === 0;
            const open = !!this.$wire.activeUserId;
            const lastBefore = open ? this._readLastMessageMarker() : null;
            const typingBefore = open ? this._readTypingMarker() : null;
            let changed = false;
            let unreadRose = false;

            try {
                const d = await this.$wire.pollDigest();
                if (fullSidebar || d !== digest) {
                    const unreadBefore = this._readUnread();
                    await this._sidebar();
                    digest = d;
                    const unreadAfter = this._readUnread();
                    changed = unreadAfter !== unreadBefore;
                    unreadRose = unreadAfter > unreadBefore;
                }

                if (open) {
                    await this._isl().refreshActive();
                    const lastAfter = this._readLastMessageMarker();
                    const threadChanged = lastAfter !== lastBefore && lastBefore !== null && lastAfter !== null;
                    const incoming = threadChanged && !this._lastMessageIsMine();
                    if (incoming && !document.hidden && this.$wire.focusAnchorId == null) this.$wire.markRead(this.$wire.activeUserId);
                    if (incoming) this._ringIncoming(this.$wire.activeUserId);
                    else if (unreadRose && !threadChanged) this._ringIncoming(null);
                    changed ||= lastAfter !== lastBefore || this._readTypingMarker() !== typingBefore;
                }
            } catch {}

            this._applyPollResult(changed, open ? OPEN_POLL_CEILING_MS : IDLE_POLL_CEILING_MS);
        },

        _sendTypingPing() { return this._isl().pingTyping(); },

        _readUnread() {
            return parseInt(document.querySelector(`[${TOTAL_UNREAD_ATTR}]`)?.getAttribute(TOTAL_UNREAD_ATTR), 10) || 0;
        },

        _readTypingMarker() {
            return document.querySelector('[data-typing]')?.getAttribute('data-typing') ?? null;
        },

        _readLastMessageMarker() {
            const rows = this._msgViewportEl?.querySelectorAll(ROW_SEL);
            return rows?.length ? rows[rows.length - 1].getAttribute('data-rf') : null;
        },

        syncPushNotify() {
            const now = this._readUnread();
            if (this._lastUnread !== undefined && now > this._lastUnread) {
                this.$store.push.notify('پیام جدید', 'یک گفتگو پیام جدید دارد', SCOPE);
                if (!this.$wire.activeUserId) this._ringIncoming(null);
            }
            if (this._lastUnread !== undefined) this.$store.menuBadges?.set('contacts-controller', now > 0);
            this._lastUnread = now;
        },

        _lastMessageIsMine() {
            const rows = this._msgViewportEl?.querySelectorAll(ROW_SEL);
            const last = rows?.length ? rows[rows.length - 1] : null;
            return !!last?.classList.contains('justify-start');
        },

        initPattern() {
            try {
                return settings().initPattern();
            } catch {}
        },

        focusSearch() {
            const el = document.getElementById('search');
            if (el) {
                el.focus();
                el.select();
            }
        },

        closeOverlays() {
            this.showInfo = false;
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;
            this.emojiOpen = false;
            this.cancelReply();
            this.cancelEdit();
            this.cancelDelete();
            this.quoteChip.visible = false;
            this.openActionsId = null;
        },

        toggleActions(id, e) {
            const sel = getSelection();
            if (sel && sel.toString().trim() !== '') return;
            if (e.target.closest('a,button,[role="button"],[contenteditable],input,textarea')) return;
            this.openActionsId = this.openActionsId === id ? null : id;
        },

        scrollToMessage(id) {
            if (!id) return;
            const el = document.querySelector(`[data-rf="${DATA_RF_MESSAGE_PREFIX}-${id}"]`);
            if (!el) { this._isl().focusMessage(id).catch(() => {}); return; }

            document.querySelectorAll('.record-focus-flash').forEach(n => n.classList.remove('record-focus-flash'));
            el.style.animation = 'none';
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('record-focus-flash');
        },

        selectContact(id) {
            if (!id) return;
            const seq = ++selectSeq;
            this._persistLastOpen(id);
            this._repollNow(false);

            const el = ta();
            this.resetAutoResize(el);
            if (el) el.dir = 'rtl';

            this.resetUI();
            this.openActionsId = null;
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;

            this.$wire.cancelReply();
            return this._isl().selectContact(id)
                .then(() => this._sidebar())
                .then(() => {
                    if (seq !== selectSeq) return;
                    this.$nextTick(() => { this._bindViewport(); this.scrollToBottom(true); });
                })
                .then(() => {
                    if (seq !== selectSeq) return;
                    if (innerWidth < MOBILE_BREAKPOINT && el) this.$nextTick(() => el.focus());
                });
        },

        resetUI() {
            this.replyingTo = null;
            this.editingMsg = null;
            this.deletingId = null;
        },

        backToList() {
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;
            this._clearLastOpen();
            this.resetUI();
            this.openActionsId = null;
            this._msgViewportEl = null;
            const wire = this.$wire;
            wire.set('mobileShowChat', false, false);
            wire.$island('messages').backToList().then(() => wire.$island('sidebar').refreshUnread());
        },

        startReply(id, senderName, body) {
            if (!id) return;
            this.editingMsg = null;
            this.deletingId = null;
            this.replyingTo = { id, sender: { name: senderName || 'Unknown' }, body: body || '' };
            this.quoteChip.visible = false;
            this.openActionsId = null;

            this.$wire.replyTo(id);
            this.$wire.cancelEdit();
            this.$nextTick(() => ta()?.focus());
        },

        _updateQuoteChip() {
            const chip = this._quoteChipFromSelection();
            if (chip) this.quoteChip = chip;
            else this.quoteChip.visible = false;
        },

        _quoteChipFromSelection() {
            const sel = getSelection();
            if (!sel || sel.isCollapsed || !sel.rangeCount) return;

            const anchor = sel.anchorNode;
            if (!anchor || !this._msgViewportEl?.contains(anchor)) return;

            const node = anchor.nodeType === 3 ? anchor.parentElement : anchor;
            if (node?.closest('textarea, input, [contenteditable="true"], [contenteditable=""]')) return;

            const row = node?.closest(ROW_SEL);
            if (!row) return;

            const text = sel.toString().trim();
            if (!text) return;

            const id = parseInt(row.getAttribute('data-rf').substring(RF_ID_OFFSET), 10);
            if (!id) return;

            const { left: x, top: y } = sel.getRangeAt(0).getBoundingClientRect();
            return {
                visible: true, x, y, id,
                sender: row.querySelector('[data-sender]')?.getAttribute('data-sender') || '',
                snippet: text.substring(0, 120),
            };
        },

        startEdit(id, body) {
            if (!id) return;
            this.replyingTo = null;
            this.deletingId = null;
            this.editingMsg = { id, body: body || '' };

            this.$wire.cancelReply();
            this.$wire.set('edit.editingBody', body);
        },

        cancelEdit() {
            this.editingMsg = null;
            this.$wire.cancelEdit();
        },

        async saveEdit(id) {
            if (!id || id !== this.editingMsg?.id) return;

            const wire = this.$wire;
            try {
                await this._isl().saveEdit(id);
                if (this.editingMsg?.id === id) this.cancelEdit();
                this._syncSidebarAfterOwnAction(wire);
            } catch {
                this.toast('خطا در ذخیره ویرایش پیام.', 'error');
            }
        },

        confirmDelete(id) {
            if (!id) return;
            this.resetUI();
            this.deletingId = id;
        },

        async undoDelete() {
            const wire = this.$wire;
            try {
                await wire.$island('messages').undoDelete();
                this._syncSidebarAfterOwnAction(wire);
            } catch {}
        },

        async sendMessage() {
            if (this.sending) return;

            const body = ta()?.value?.trim() ?? '';
            if (!body && !this.$wire.composer?.attachments?.length) {
                this.toast('پیام نمی‌تواند خالی باشد.', 'warning');
                return;
            }
            if (this._overLimit(body)) return;

            this.sending = true;
            const wire = this.$wire;
            try {
                await this._isl().send();
                this.replyingTo = null;
                this._syncSidebarAfterOwnAction(wire);
                this.$nextTick(() => ta()?.focus());
            } catch {
                this.toast('خطا در ارتباط با سرور.', 'error');
            } finally {
                setTimeout(() => { this.sending = false; }, SEND_LOCK_RESET_MS);
            }
        },
    };
}
