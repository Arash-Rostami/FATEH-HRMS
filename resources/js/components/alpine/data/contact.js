import settings from "./settings.js";

import maximizeMixin from "../mixins/maximize.js";
import lastOpenMixin from "../mixins/lastOpen.js";
import sidebarRailMixin from "../mixins/sidebarRail.js";
import chatScaleMixin from "../mixins/chatScale.js";
import clipboardMixin from "../mixins/clipboard.js";
import pasteImageMixin from "../mixins/pasteImage.js";
import chatBase from "../mixins/chatBase.js";
import fancyboxMixin from "../mixins/fancybox.js";
import aiEditMixin from "../mixins/aiEdit.js";
import { smsMemoryMixin } from "../mixins/persistentState.js";

const SCOPE = 'contact';
const OPEN_POLL_CEILING_MS = 20000;
const IDLE_POLL_CEILING_MS = 30000;
const MOBILE_BREAKPOINT = 768;
const MAX_BODY_LENGTH = 2000;
const UNDO_TOAST_MS = 4000;
const SEND_LOCK_RESET_MS = 500;
const NEAR_BOTTOM_PX = 120;
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
const UNREAD_SEL = `[${TOTAL_UNREAD_ATTR}]`;
const TYPING_SEL = '[data-typing]';
const EDITABLE_SEL = 'textarea, input, [contenteditable="true"], [contenteditable=""]';
const INTERACTIVE_SEL = 'a,button,[role="button"],[contenteditable],input,textarea';

const ta = () => document.getElementById(MSG_TEXTAREA_ID);

export default function contact({ senderId = null, smsYear = null } = {}) {
    let ac, vpAc, vpRo, scrollRaf, selRaf, unreadObs, digest, ticks = 0, selectSeq = 0;

    return {
        ...maximizeMixin('contact'),
        ...lastOpenMixin('contact'),
        ...sidebarRailMixin('contact'),
        ...chatScaleMixin(),
        ...clipboardMixin(),
        ...pasteImageMixin(),
        ...chatBase(SCOPE),
        ...fancyboxMixin(),
        ...smsMemoryMixin({ senderId, smsYear }),
        bgOption: 'a',
        ...aiEditMixin(),
        editingMsg: null,
        quoteChip: { visible: false, x: 0, y: 0, id: null, sender: '', snippet: '' },

        init() {
            ac = new AbortController();
            const { signal } = ac;
            this.initChatScale(signal);
            this.initFancybox();
            this.initPattern();
            this.loadSmsMemory();
            this.$watch('showInfo', (open) => { if (open && this.filesOpen && !this._filesCache) this.retryFiles(); });
            this.syncPushNotify();

            unreadObs = new MutationObserver(() => this.syncPushNotify());
            unreadObs.observe(document.body, { subtree: true, attributes: true, attributeFilter: [TOTAL_UNREAD_ATTR] });

            this._loadChatSettings();
            this.headerDeclutterMenuBridge(signal);

            this.startPolling();

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this._flushPendingRing();
                    this._repollNow();
                } else if (!this.$store.push.pollsHidden(SCOPE)) this.stopPolling();
            }, { signal });

            this._bindViewport();
            this.initSwipeReply();

            document.addEventListener('selectionchange', () => {
                if (selRaf) return;
                if (!this.quoteChip.visible && getSelection()?.isCollapsed) return;
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

            this._draftInit(signal);

            this.$wire.on('message-sent', (e) => {
                this.sending = false;
                this.replyingTo = null;
                this._draftSent(e);
                this.$store.sound?.playOutgoing(this.$wire.activeUserId, SCOPE);
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
            this._ghostHide();
            this._destroyed = true;
            this.stopPolling();
            ac?.abort();
            clearTimeout(this._declutterTimer);
            this._swipeAc?.abort();
            vpAc?.abort();
            vpRo?.disconnect();
            clearTimeout(this._scrollSettleTimer);
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

            vpAc?.abort();
            vpRo?.disconnect();
            vpAc = new AbortController();
            const { signal } = vpAc;
            let lastSt = vp.scrollTop;

            vp.addEventListener('programmatic-scroll', () => this.markProgrammaticScroll(), { signal });

            vp.addEventListener('scroll', () => {
                if (this._programmaticScroll) this._armScrollSettle();
                if (scrollRaf) return;
                scrollRaf = requestAnimationFrame(() => {
                    scrollRaf = null;
                    const programmatic = this._programmaticScroll;
                    const { scrollTop: st, scrollHeight: sh, clientHeight: ch } = vp;
                    this.showScrollFab = sh - st - ch > SCROLL_FAB_DISTANCE_THRESHOLD;

                    if (!this.showScrollFab && this.newBelow > 0) {
                        this.newBelow = 0;
                        if (this.$wire.focusAnchorId == null) this.$wire.markRead(this.$wire.activeUserId);
                    }

                    const up = st < lastSt;
                    lastSt = st;
                    if (programmatic) return;

                    this.quoteChip.visible = false;
                    this.openActionsId = null;

                    if (up && st < LOAD_OLDER_SCROLL_THRESHOLD && this.$wire.hasOlder) {
                        this.loadOlderKeepingPlace(() => this._isl().loadMoreMessages());
                    }

                    if (!up && sh - st - ch < LOAD_OLDER_SCROLL_THRESHOLD && this.$wire.hasNewer && !this._loadingNewer) {
                        this.loadNewer(() => this._isl().loadNewerMessages());
                    }
                });
            }, { passive: true, signal });

            let prevCh = vp.clientHeight;
            vpRo = new ResizeObserver(() => {
                const ch = vp.clientHeight;
                if (ch < prevCh && vp.scrollHeight - vp.scrollTop - prevCh < NEAR_BOTTOM_PX) this.scrollToBottom(false);
                prevCh = ch;
            });
            vpRo.observe(vp);
        },

        _isl() { return this.$wire.$island('messages'); },
        _sidebar() { return this.$wire.$island('sidebar').refreshUnread(); },

        _overLimit(body) {
            if (this.chars(body) <= MAX_BODY_LENGTH) return false;
            this.toast(TOO_LONG_MSG, 'warning');
            return true;
        },

        async _pollTick(force = false) {
            const fullSidebar = force || ++ticks % POLL_FALLBACK_EVERY === 0;
            const open = !!this.$wire.activeUserId && !document.hidden;
            const lastBefore = open ? this._readLastMessageMarker() : null;
            const typingBefore = open ? this._readTypingMarker() : null;
            let changed = false;
            let unreadRose = false;

            try {
                const d = await this.$wire.pollDigest();
                if (fullSidebar || d !== digest) {
                    this._riseOnlyMuted();
                    const unreadBefore = this._readUnread();
                    await this._sidebar();
                    digest = d;
                    const unreadAfter = this._readUnread();
                    changed = unreadAfter !== unreadBefore;
                    unreadRose = unreadAfter > unreadBefore;
                    if (unreadRose && document.hidden) this._ringIncoming(null);
                }

                if (open) {
                    const vp = this._msgViewportEl;
                    const nearBottom = !!vp && vp.scrollHeight - vp.scrollTop - vp.clientHeight < NEAR_BOTTOM_PX;
                    await this._isl().refreshActive();
                    this.autoDirection(ta());
                    const lastAfter = this._readLastMessageMarker();
                    if (lastAfter !== lastBefore) this._dropFiles();
                    const threadChanged = lastAfter !== lastBefore && lastBefore !== null && lastAfter !== null && lastAfter !== this._selfMarker && this._markerAdvanced(lastAfter, lastBefore);
                    const incoming = threadChanged && !this._lastMessageIsMine();
                    if (threadChanged) {
                        if (nearBottom) this.$nextTick(() => this.scrollToBottom(true));
                        else this.newBelow += this._rowsNewerThan(lastBefore);
                    }
                    if (incoming && nearBottom && !document.hidden && this.$wire.focusAnchorId == null) this.$wire.markRead(this.$wire.activeUserId);
                    if (incoming) this._ringIncoming(this.$wire.activeUserId);
                    else if (unreadRose && !threadChanged) this._ringIncoming(null);
                    changed ||= lastAfter !== lastBefore || this._readTypingMarker() !== typingBefore;
                }
            } catch {}

            this._applyPollResult(changed, open ? OPEN_POLL_CEILING_MS : IDLE_POLL_CEILING_MS);
        },

        _sendTypingPing() { return this._isl().pingTyping(); },

        _readUnread() {
            return parseInt(document.querySelector(UNREAD_SEL)?.getAttribute(TOTAL_UNREAD_ATTR), 10) || 0;
        },

        _readTypingMarker() {
            return document.querySelector(TYPING_SEL)?.getAttribute('data-typing') ?? null;
        },

        _readLastMessageMarker() {
            const rows = this._msgViewportEl?.querySelectorAll(ROW_SEL);
            return rows?.length ? rows[rows.length - 1].getAttribute('data-rf') : null;
        },

        syncPushNotify() {
            this._riseOnlyMuted();
            const now = this._readUnread();
            const message = this._pushMessage(now, 'یک گفتگو پیام جدید دارد');
            const last = this._lastUnread;
            if (last !== undefined) {
                if (now > last) {
                    this.$store.push.notify(...message, SCOPE);
                    if (!this.$wire.activeUserId) this._ringIncoming(null);
                }
                this.$store.menuBadges?.set('contacts-controller', now > 0);
            }
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
            const wasOpen = this.showInfo || this.searchMessages || this.bookmarksOpen || this.daysOpen || this.emojiOpen || this.replyingTo || this.editingMsg || this.quoteChip.visible || this.openActionsId || this.deletingId;
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
            return Boolean(wasOpen);
        },

        toggleActions(id, e) {
            const sel = getSelection();
            if (sel && sel.toString().trim() !== '') return;
            if (e.target.closest(INTERACTIVE_SEL)) return;
            this.openActionsId = this.openActionsId === id ? null : id;
        },

        scrollToMessage(id) {
            if (!id) return;
            const el = document.querySelector(`[data-rf="${DATA_RF_MESSAGE_PREFIX}-${id}"]`);
            if (!el) { this.typeFilter = ''; this._isl().focusMessage(id).catch(() => {}); return; }
            if (this.typeFilter && el.offsetParent === null) {
                this.typeFilter = '';
                this.$nextTick(() => this.scrollToMessage(id));
                return;
            }

            document.querySelectorAll('.record-focus-flash').forEach(n => n.classList.remove('record-focus-flash'));
            el.style.animation = 'none';
            this.programmaticScroll(() => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            el.classList.add('record-focus-flash');
        },

        selectContact(id) {
            if (!id) return;
            if (id === this.$wire.activeUserId && this.$wire.mobileShowChat) return;
            if (id !== this.$wire.activeUserId && this.holdDraft(id, ta())) return;
            const seq = ++selectSeq;
            this._persistLastOpen(id);
            this._repollNow(false);

            const el = ta();
            this.resetAutoResize(el);
            if (el) el.dir = 'rtl';

            this.resetUI();
            this.resetRoomScoped();
            this.openActionsId = null;
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;

            this.$wire.cancelReply();
            return this._isl().selectContact(id)
                .then(() => {
                    if (seq !== selectSeq) return;
                    this.showScrollFab = false;
                    this.newBelow = 0;
                    this.$nextTick(() => { this._bindViewport(); this.scrollToBottom(false); this._draftRestore(); });
                    return this._sidebar();
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
            const wire = this.$wire;
            if (this.holdDraft('back', ta(), wire)) return;
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;
            this._clearLastOpen();
            this.resetUI();
            this.openActionsId = null;
            this._msgViewportEl = null;
            this.showScrollFab = false;
            this.newBelow = 0;
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
            if (node?.closest(EDITABLE_SEL)) return;

            const row = node?.closest(ROW_SEL);
            if (!row || !row.querySelector('button[data-body]')) return;

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
            this.resetAiEdit();

            this.$wire.cancelReply();
            this.$wire.set('edit.editingBody', body);
        },

        cancelEdit() {
            this.editingMsg = null;
            this.$wire.cancelEdit();
        },

        async saveEdit(id) {
            if (!id || id !== this.editingMsg?.id || this._savingEdit || (this.aiEditBusy && this.aiEditId === id)) return;

            const wire = this.$wire;
            const body = wire.edit?.editingBody || '';
            if (body === this.editingMsg.body) {
                this.toast('متن تغییری نکرده است؛ چیزی برای ذخیره نبود.', 'warning');
                return this.cancelEdit();
            }
            if (!body.trim()) return this.toast('متن پیام نباید خالی باشد.', 'warning');
            if (this._overLimit(body)) return;

            this._savingEdit = true;
            try {
                const saved = await this._isl().saveEdit(id);
                if (saved !== false && this.editingMsg?.id === id) this.cancelEdit();
                this._syncSidebarAfterOwnAction(wire);
            } catch {
                this.toast('ذخیرهٔ ویرایش تأیید نشد. پیش از تلاش دوباره، اتصال اینترنت را بررسی کنید.', 'error');
            } finally {
                this._savingEdit = false;
            }
        },

        _editDirty(wire = this.$wire) { return !!this.editingMsg && (wire.edit?.editingBody ?? '') !== this.editingMsg.body; },

        aiEditCurrentId() { return this.editingMsg?.id ?? null; },
        aiEditBox(id) { return document.querySelector(`[data-edit-box="${id}"] textarea`); },
        aiEditApply(text) { this.$wire.set('edit.editingBody', text); },

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
            const seq = this._sendSeq = (this._sendSeq || 0) + 1;
            const wire = this.$wire;
            const ghost = !wire.composer?.attachments?.length && !this.replyingTo && !wire.hasNewer
                ? this._ghostShow(body)
                : null;
            try {
                if (await this._deliver(body) === null) return;
                this._syncSidebarAfterOwnAction(wire);
                this.$nextTick(() => { this.scrollToBottom(true); ta()?.focus(); this.autoGrow(ta(), 160); this.autoDirection(ta()); });
            } catch {
                this.toast('هنگام پردازش پیام مشکلی پیش آمد. پیش از ارسال دوباره، اتصال اینترنت را بررسی کنید.', 'error');
            } finally {
                this._ghostHide(ghost);
                setTimeout(() => { if (this._sendSeq === seq) this.sending = false; }, SEND_LOCK_RESET_MS);
            }
        },
    };
}
