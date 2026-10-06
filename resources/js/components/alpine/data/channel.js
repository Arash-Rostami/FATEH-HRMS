import maximizeMixin from "../mixins/maximize.js";
import lastOpenMixin from "../mixins/lastOpen.js";
import clipboardMixin from "../mixins/clipboard.js";
import pasteImageMixin from "../mixins/pasteImage.js";
import chatBase from "../mixins/chatBase.js";
import fancyboxMixin from "../mixins/fancybox.js";

const SCOPE = 'channel';
const OPEN_POLL_CEILING_MS = 20000;
const IDLE_POLL_CEILING_MS = 30000;
const MOBILE_BREAKPOINT = 768;
const MAX_BODY_LENGTH = 4000;
const UNDO_TOAST_MS = 4000;
const POST_SEND_SCROLL_DELAY_MS = 120;
const SEND_LOCK_RESET_MS = 500;
const LOAD_OLDER_SCROLL_THRESHOLD = 80;
const SCROLL_FAB_DISTANCE_THRESHOLD = 200;
const MSG_VIEWPORT_ID = 'msg-viewport';
const MSG_TEXTAREA_ID = 'msg-ta';
const TOTAL_UNREAD_ATTR = 'data-total-unread';
const DATA_SENDER_NAME_ATTR = 'data-sender-name';
const DATA_RF_MESSAGE_PREFIX = 'channel-message';
const POLL_FALLBACK_EVERY = 10;
const MENTION_LIMIT = 8;
const TOO_LONG_MSG = 'متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.';

const RF_ID_OFFSET = DATA_RF_MESSAGE_PREFIX.length + 1;
const ROW_SEL = `[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`;

const ta = () => document.getElementById(MSG_TEXTAREA_ID);
const rowId = (row) => parseInt(row.getAttribute('data-rf').substring(RF_ID_OFFSET), 10) || 0;
const lastAt = (v, pos) => pos ? v.lastIndexOf('@', pos - 1) : -1;

export default function channel() {
    let ac, vpAc, scrollRaf, selRaf, unreadObs, fadeTimer, digest, ticks = 0, selectSeq = 0;

    return {
        ...maximizeMixin('channel'),
        ...lastOpenMixin('channel'),
        ...clipboardMixin(),
        ...pasteImageMixin(),
        ...chatBase(SCOPE),
        ...fancyboxMixin(),
        channelCount: 0,
        cancelInviteeId: null,
        editingOriginal: '',
        isEditing: false,
        editingMsgId: null,
        editingBody: '',
        quoteChip: { visible: false, x: 0, y: 0, id: 0, sender: '', snippet: '' },
        confirmingDeleteChannel: false,
        activeSender: null,
        chipsVisible: true,
        mentionOpen: false,
        mentionQuery: '',
        mentionActiveIndex: 0,

        init() {
            this.initFancybox();

            const saved = localStorage.getItem(`chat-settings:${SCOPE}`);
            if (saved) try {
                const d = JSON.parse(saved);
                this.isHighlighted = d.isHighlighted ?? false;
                this.backgroundPattern = d.backgroundPattern ?? 'off';
                this.backgroundPatternType = d.backgroundPatternType ?? 'mesh';
            } catch {}

            this.syncChannelCount();
            this.syncPushNotify();

            unreadObs = new MutationObserver(() => this.syncPushNotify());
            unreadObs.observe(document.body, { subtree: true, attributes: true, attributeFilter: [TOTAL_UNREAD_ATTR] });

            this.startPolling();

            ac = new AbortController();
            const { signal } = ac;

            document.addEventListener('visibilitychange', () => document.hidden ? this.stopPolling() : this._repollNow(), { signal });

            this._bindViewport();

            document.addEventListener('selectionchange', () => {
                if (selRaf) return;
                selRaf = requestAnimationFrame(() => {
                    selRaf = null;
                    const chip = this._selectionChip();
                    if (chip) this.quoteChip = chip;
                    else this.quoteChip.visible = false;
                });
            }, { signal });

            document.addEventListener('keydown', (e) => {
                if (e.key !== '/' || e.isComposing || !this.$root.contains(e.target)) return;
                if (document.activeElement?.closest?.('input, textarea, select, [contenteditable]')) return;
                if (this.searchMessages) return;
                e.preventDefault();
                this.openMessageSearch();
            }, { signal });

            this.$wire.on('message-sent', () => {
                this.scrollToBottom(true);
                this.sending = false;
                this.$store.sound?.playOutgoing(this.$wire.activeChannelId, SCOPE);
                setTimeout(() => this.scrollToBottom(true), POST_SEND_SCROLL_DELAY_MS);
            });

            this.$wire.on('show-toast', (e) => this.toast(e.message, e.type ?? 'info'));
            this.$wire.on('show-undo-toast', (e) => this.toast(e.message, 'warning'));
            this.$wire.on('attachments-updated', () => {
                this._isl().syncAttachments().catch(() => {});
            });

            this.$wire.on('channel-members-updated', () => {
                this._isl().refreshActive().catch(() => {});
                this._sidebar().catch(() => {});
            });

            this.$watch('$wire.lastDeleted', (val) => {
                clearTimeout(this.undoTimeout);
                this.showUndo = !!val;
                if (val) this.undoTimeout = setTimeout(() => { this.showUndo = false; }, UNDO_TOAST_MS);
            });

            this.$watch('$wire.editingMsg', (val, old) => {
                if (val || (old?.id && this.editingMsgId && old.id !== this.editingMsgId)) return;
                this._clearEdit();
            });

            const lastId = this.$wire.activeChannelId ? null : this._readLastOpenId();
            if (lastId) this.selectChannel(lastId).finally(() => this.initMaximize());
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
            clearTimeout(fadeTimer);
        },

        // #msg-viewport lives inside the messages island's pane-active branch, destroyed and
        // re-mounted every time a channel opens/closes — this component's own init() runs once,
        // so a reference captured there goes stale (null forever if mounted with no channel open,
        // detached after the first close-then-reopen). Re-binding here, called from both init()
        // and every selectChannel() resolution, keeps scroll-to-load-older, the scroll FAB, and
        // the quote-chip feature working across channel switches.
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
                        this._isl().loadOlder()
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

        // Every transition that swaps #msg-viewport's content (join/create/leave/delete/browse
        // toggle/create-pane toggle) must re-bind it the same way selectChannel() does, or
        // scroll-to-load-older/the scroll FAB/the quote-chip go dead until the next channel pick.
        _afterPaneSwap() {
            this.$nextTick(() => {
                this._bindViewport();
                if (this._msgViewportEl) this.scrollToBottom();
            });
        },

        _isl() { return this.$wire.$island('messages'); },
        _sidebar() { return this.$wire.$island('sidebar').refreshUnread(); },

        _clearEdit() {
            this.isEditing = false;
            this.editingMsgId = null;
            this.editingBody = '';
            this.editingOriginal = '';
        },

        _overLimit(body) {
            if (body.length <= MAX_BODY_LENGTH) return false;
            this.toast(TOO_LONG_MSG, 'warning');
            return true;
        },

        _selectionChip() {
            const sel = getSelection();
            if (!sel || sel.isCollapsed || !sel.rangeCount) return;

            const node = sel.anchorNode;
            if (!node || !this._msgViewportEl?.contains(node)) return;

            const el = node.nodeType === 1 ? node : node.parentElement;
            if (!el || el.closest('input, textarea, [contenteditable]')) return;

            const row = el.closest(ROW_SEL);
            if (!row) return;

            const text = sel.toString().trim();
            if (!text) return;

            const { left: x, top: y } = sel.getRangeAt(0).getBoundingClientRect();
            return {
                visible: true, x, y,
                id: rowId(row),
                sender: row.querySelector('[data-sender]')?.getAttribute('data-sender') ?? '',
                snippet: text.substring(0, 120),
            };
        },

        async _pollTick(force = false) {
            const fullSidebar = force || ++ticks % POLL_FALLBACK_EVERY === 0;
            const open = !!this.$wire.activeChannelId;
            const lastBefore = open ? this._readLastMessageMarker() : null;
            const typingBefore = open ? this._readTypingMarker() : null;
            let changed = false;

            try {
                const d = await this.$wire.pollDigest();
                if (fullSidebar || d !== digest) {
                    const unreadBefore = this._readUnread();
                    await this._sidebar();
                    this.syncChannelCount();
                    digest = d;
                    changed = this._readUnread() !== unreadBefore;
                }

                if (open) {
                    await this._isl().refreshActive();
                    const lastAfter = this._readLastMessageMarker();
                    if (lastAfter !== lastBefore && lastBefore !== null && lastAfter !== null && !this._lastMessageIsMine()) {
                        this.$store.sound?.playIncoming(this.$wire.activeChannelId, SCOPE);
                    }
                    changed ||= lastAfter !== lastBefore || this._readTypingMarker() !== typingBefore;
                }
            } catch {}

            this._applyPollResult(changed, open ? OPEN_POLL_CEILING_MS : IDLE_POLL_CEILING_MS);
        },

        _sendTypingPing() { return this._isl().pingTyping(); },

        _readUnread() {
            return parseInt(document.querySelector(`[${TOTAL_UNREAD_ATTR}]`)?.getAttribute(TOTAL_UNREAD_ATTR), 10) || 0;
        },

        _readLastMessageMarker() {
            const rows = this._msgViewportEl?.querySelectorAll(ROW_SEL);
            return rows?.length ? rows[rows.length - 1].getAttribute('data-rf') : null;
        },

        _readTypingMarker() {
            return document.querySelector('[data-typing]')?.getAttribute('data-typing') ?? null;
        },

        syncChannelCount() {
            const el = document.querySelector('[data-channel-count]');
            if (el) this.channelCount = parseInt(el.getAttribute('data-channel-count'), 10) || 0;
        },

        syncPushNotify() {
            const now = this._readUnread();
            if (this._lastUnread !== undefined && now > this._lastUnread) {
                this.$store.push.notify('پیام جدید', 'یک گروه پیام جدید دارد', SCOPE);
                if (!this.$wire.activeChannelId) this.$store.sound?.playIncoming(null, SCOPE);
            }
            this._lastUnread = now;
        },

        _lastMessageIsMine() {
            const rows = this._msgViewportEl?.querySelectorAll(ROW_SEL);
            const last = rows?.length ? rows[rows.length - 1] : null;
            return !!last?.classList.contains('justify-start');
        },

        get mentionMatches() {
            if (!this.mentionOpen) return [];

            const list = this.$wire.mentionMemberNames || [];
            const q = (this.mentionQuery || '').toLowerCase();
            if (!q) return list.slice(0, MENTION_LIMIT);

            const out = [];
            for (let i = 0; i < list.length && out.length < MENTION_LIMIT; i++) {
                if ((list[i] || '').toLowerCase().includes(q)) out.push(list[i]);
            }
            return out;
        },

        openMentionPicker() {
            const el = ta();
            if (!el) return;

            const v = el.value;
            const pos = el.selectionStart ?? v.length;
            const sp = pos > 0 && !/\s/.test(v[pos - 1]) ? ' ' : '';
            const next = v.substring(0, pos) + sp + '@' + v.substring(pos);

            el.value = next;
            el.focus();
            el.selectionStart = el.selectionEnd = pos + sp.length + 1;

            this.$wire.set('composer.body', next);
            this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = '';
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        detectMention(e) {
            this.resetPollInterval();
            this.pingTyping();

            const { value: v, selectionStart: pos } = e.target;
            const at = lastAt(v, pos);
            const segment = at < 0 ? '' : v.substring(at + 1, pos);

            if (at < 0 || /\s/.test(segment) || (at > 0 && !/\s/.test(v[at - 1]))) {
                this.mentionOpen = false;
                return;
            }

            if (!this.mentionOpen) this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = segment;
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        onComposerKeydown(e) {
            const { key } = e;
            if (this.mentionOpen) {
                const len = this.mentionMatches.length;
                if (len) {
                    if (key === 'ArrowDown' || key === 'ArrowUp') {
                        e.preventDefault();
                        this.mentionActiveIndex = (this.mentionActiveIndex + (key === 'ArrowDown' ? 1 : len - 1)) % len;
                        return;
                    }
                    if (key === 'Enter' || key === 'Tab') { e.preventDefault(); this.pickMention(this.mentionActiveIndex); return; }
                    if (key === 'Escape') { e.preventDefault(); this.mentionOpen = false; return; }
                }
            }
            if (key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.sendMessage(); }
        },

        pickMention(i) {
            const name = this.mentionMatches[i];
            const el = ta();
            const at = name && el ? lastAt(el.value, el.selectionStart) : -1;

            if (at < 0) { this.mentionOpen = false; return; }

            const insert = name + ' ';
            const next = el.value.substring(0, at + 1) + insert + el.value.substring(el.selectionStart);

            this.mentionOpen = false;
            this.mentionQuery = '';
            this.$wire.set('composer.body', next);
            this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = at + 1 + insert.length; });
        },

        toggleActions(id) {
            this.openActionsId = this.openActionsId === id ? null : id;
        },

        openManageMembers(id) {
            if (!id) return;
            if (this.max) this.toggleMaximize(null);
            this.$wire.openManageMembers(id).catch(() => {});
        },

        closeOverlays() {
            this.showInfo = false;
            this.searchMessages = false;
            this.replyingTo = null;
            this.deletingId = null;
            this.openActionsId = null;
            this.emojiOpen = false;
            this.quoteChip.visible = false;
            this.activeSender = null;
            this.confirmingDeleteChannel = false;
            if (this.$wire.createMode) this.closeCreate();
            if (this.$wire.browseMode) this.toggleBrowse();
            this.$wire.cancelReply();
            this.$wire.cancelEdit();
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

        filterSender(name) {
            this.activeSender = name;
            let latest = 0;
            for (const r of document.querySelectorAll(ROW_SEL)) {
                if (r.getAttribute(DATA_SENDER_NAME_ATTR) === name) latest = Math.max(latest, rowId(r));
            }

            if (latest) this.scrollToMessage(latest);
            else this._isl().focusSender(name).catch(() => {});
        },

        clearSenderFilter() {
            if (this.activeSender === null) return;
            this.activeSender = null;
            this._isl().clearFocus()
                .then(() => this.$nextTick(() => this.scrollToBottom(true)))
                .catch(() => {});
        },

        revealChips() {
            clearTimeout(fadeTimer);
            this.chipsVisible = true;
        },

        scheduleChipsFade() {
            clearTimeout(fadeTimer);
            fadeTimer = setTimeout(() => { this.chipsVisible = false; }, 3000);
        },

        selectChannel(id) {
            if (!id) return;
            const seq = ++selectSeq;
            this._persistLastOpen(id);
            this._repollNow(false);

            const el = ta();
            this.resetAutoResize(el);
            if (el) el.dir = 'rtl';

            this.replyingTo = null;
            this.activeSender = null;
            this.deletingId = null;
            this.openActionsId = null;
            this.searchMessages = false;
            this.confirmingDeleteChannel = false;
            this._clearEdit();

            this.$wire.cancelReply();
            return this._isl().selectChannel(id)
                .then(() => this._sidebar())
                .then(() => {
                    if (seq !== selectSeq) return;
                    this.$nextTick(() => { this._bindViewport(); this.scrollToBottom(true); });
                })
                .then(() => {
                    if (seq !== selectSeq) return;
                    this.chipsVisible = true;
                    this.scheduleChipsFade();
                })
                .then(() => {
                    if (seq !== selectSeq) return;
                    if (innerWidth < MOBILE_BREAKPOINT && el) this.$nextTick(() => el.focus());
                });
        },

        toggleBrowse() {
            const wire = this.$wire;
            return wire.$island('messages').toggleBrowse()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); });
        },
        openCreate() {
            const wire = this.$wire;
            return wire.$island('messages').openCreate()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); });
        },

        closeCreate() {
            const wire = this.$wire;
            return wire.$island('messages').closeCreate()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); });
        },

        backToList() {
            this.searchMessages = false;
            this.activeSender = null;
            this.openActionsId = null;
            this._msgViewportEl = null;
            const wire = this.$wire;
            wire.set('mobileShowChat', false, false);
            wire.$island('messages').backToList().then(() => wire.$island('sidebar').refreshUnread());
        },

        leaveChannel(id) {
            if (!id) return;
            this.searchMessages = false;
            const wire = this.$wire;
            wire.$island('messages').leaveChannel(id)
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); })
                .then(() => this.syncChannelCount())
                .catch(() => {});
        },

        createChannel() {
            const wire = this.$wire;
            wire.$island('messages').createChannel()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); })
                .then(() => this.syncChannelCount());
        },

        joinChannel(id) {
            if (!id) return;
            const wire = this.$wire;
            wire.$island('messages').joinChannel(id)
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); })
                .then(() => this.syncChannelCount())
                .catch(() => {});
        },

        startReply(id, senderName, body) {
            if (!id) return;
            this.replyingTo = { id, sender: { name: senderName || 'Unknown' }, body: body || '' };
            this.quoteChip.visible = false;
            this.deletingId = null;
            this.openActionsId = null;

            this.$wire.replyTo(id);
            this.$wire.cancelEdit();
            this.$nextTick(() => ta()?.focus());
        },

        startEdit(id, body) {
            if (!id) return;
            this.replyingTo = null;
            this.deletingId = null;
            this.editingMsgId = id;
            this.editingOriginal = this.editingBody = body || '';
            this.isEditing = true;

            this.$wire.cancelReply();
            this.$wire.editMessage(id);
        },

        cancelEdit() {
            this._clearEdit();
            this.$wire.cancelEdit();
        },

        async saveEdit(id) {
            if (!id || id !== this.editingMsgId) return;
            const body = this.editingBody || '';
            if (body === this.editingOriginal || this._overLimit(body)) return;

            const wire = this.$wire;
            try {
                await this._isl().saveEdit(id, body);
                this._syncSidebarAfterOwnAction(wire);
            } catch {
                this.toast('خطا در ارتباط با سرور.', 'error');
            }
        },

        confirmDelete(id) {
            if (!id) return;
            this.replyingTo = null;
            this._clearEdit();
            this.deletingId = id;
        },

        confirmCancelInvite(id) {
            this.cancelInviteeId = id;
        },

        async doCancelInvite(channelId, userId) {
            const wire = this.$wire;
            try {
                await wire.$island('messages').cancelInvite(channelId, userId);
                wire.$island('sidebar').refreshUnread();
            } finally {
                this.cancelInviteeId = null;
            }
        },

        confirmDeleteChannel() {
            this.confirmingDeleteChannel = true;
        },

        cancelDeleteChannel() {
            this.confirmingDeleteChannel = false;
        },

        async doDeleteChannel(id) {
            if (!id) return;
            const wire = this.$wire;
            try {
                await wire.$island('messages').deleteChannel(id);
                this._afterPaneSwap();
                wire.$island('sidebar').refreshUnread();
                this.syncChannelCount();
            } catch {
                this.toast('خطا در حذف گروه.', 'error');
            } finally {
                this.confirmingDeleteChannel = false;
            }
        },

        async sendMessage() {
            if (this.sending) return;

            const body = ta()?.value?.trim() ?? '';
            if (!body && !(this.$wire.composer?.attachments?.length)) {
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
