import maximizeMixin from "../mixins/maximize.js";
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

const RF_ID_OFFSET = DATA_RF_MESSAGE_PREFIX.length + 1;

export default function channel() {
    return {
        ...maximizeMixin(),
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
        activeSender: null,
        chipsVisible: true,
        chipsFadeTimer: null,
        mentionOpen: false,
        mentionQuery: '',
        mentionActiveIndex: 0,
        _scrollRaf: null,

        init() {
            this.initFancybox();

            const saved = localStorage.getItem(`chat-settings:${SCOPE}`);
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.isHighlighted = data.isHighlighted ?? false;
                    this.backgroundPattern = data.backgroundPattern ?? 'off';
                    this.backgroundPatternType = data.backgroundPatternType ?? 'mesh';
                } catch {}
            }

            this.syncChannelCount();
            this.syncPushNotify();
            this.startPolling();

            this._onVisibility = () => {
                if (document.hidden) {
                    this.stopPolling();
                    return;
                }
                this.resetPollInterval();
                this.startPolling();
                this._pollTick().catch(() => {});
            };
            document.addEventListener('visibilitychange', this._onVisibility);

            const vp = document.getElementById(MSG_VIEWPORT_ID);
            this._msgViewportEl = vp;

            if (vp) {
                vp.style.overflowAnchor = 'none';
                let ticking = false;

                this._onScroll = () => {
                    if (!ticking) {
                        this._scrollRaf = requestAnimationFrame(() => {
                            this._scrollRaf = null;
                            this.quoteChip.visible = false;
                            this.openActionsId = null;

                            const st = vp.scrollTop;
                            const sh = vp.scrollHeight;
                            const ch = vp.clientHeight;

                            this.showScrollFab = (sh - st - ch) > SCROLL_FAB_DISTANCE_THRESHOLD;

                            if (st < LOAD_OLDER_SCROLL_THRESHOLD && !this._loadingOlder && this.$wire.hasOlder) {
                                this._loadingOlder = true;
                                const prevHeight = sh;

                                this.$wire.$island('messages').loadOlder()
                                    .then(() => {
                                        this.$nextTick(() => {
                                            const delta = vp.scrollHeight - prevHeight;
                                            if (delta > 0) vp.scrollTop += delta;
                                        });
                                    })
                                    .catch(() => {})
                                    .finally(() => { this._loadingOlder = false; });
                            }
                            ticking = false;
                        });
                        ticking = true;
                    }
                };
                vp.addEventListener('scroll', this._onScroll, { passive: true });
            }

            this._onSelection = () => {
                if (this._selRaf) return;
                this._selRaf = requestAnimationFrame(() => {
                    this._selRaf = null;

                    const sel = window.getSelection();
                    if (!sel || sel.isCollapsed || sel.rangeCount === 0) {
                        this.quoteChip.visible = false;
                        return;
                    }

                    const node = sel.anchorNode;
                    if (!node) { this.quoteChip.visible = false; return; }

                    const vp = this._msgViewportEl;
                    if (!vp || !vp.contains(node)) { this.quoteChip.visible = false; return; }

                    const el = node.nodeType === 1 ? node : node.parentElement;
                    if (!el || el.closest('input, textarea, [contenteditable]')) {
                        this.quoteChip.visible = false;
                        return;
                    }

                    const row = el.closest(`[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`);
                    if (!row) { this.quoteChip.visible = false; return; }

                    const text = sel.toString().trim();
                    if (!text) { this.quoteChip.visible = false; return; }

                    const attr = row.getAttribute('data-rf');
                    const id = parseInt(attr.substring(RF_ID_OFFSET), 10) || 0;

                    const senderEl = row.querySelector('[data-sender]');
                    const sender = senderEl ? senderEl.getAttribute('data-sender') : '';
                    const rect = sel.getRangeAt(0).getBoundingClientRect();

                    this.quoteChip = { visible: true, x: rect.left, y: rect.top, id, sender, snippet: text.substring(0, 120) };
                });
            };
            document.addEventListener('selectionchange', this._onSelection);

            this._onSlash = (e) => {
                if (e.key !== '/' || e.isComposing) return;
                if (!this.$root.contains(e.target)) return;

                const ae = document.activeElement;
                if (ae && ae.closest && ae.closest('input, textarea, select, [contenteditable]')) return;
                if (this.searchMessages) return;

                e.preventDefault();
                this.openMessageSearch();
            };
            document.addEventListener('keydown', this._onSlash);

            this.$wire.on('message-sent', () => {
                this.scrollToBottom(true);
                this.sending = false;
                this.$store.sound?.playOutgoing(this.$wire.activeChannelId, SCOPE);
                setTimeout(() => this.scrollToBottom(true), POST_SEND_SCROLL_DELAY_MS);
            });

            this.$wire.on('show-toast', (e) => this.toast(e.message, e.type ?? 'info'));
            this.$wire.on('show-undo-toast', (e) => this.toast(e.message, 'warning'));
            this.$wire.on('attachments-updated', () => {
                this.$wire.$island('messages').syncAttachments().catch(() => {});
            });

            this.$watch('$wire.lastDeleted', (val) => {
                clearTimeout(this.undoTimeout);
                if (val) {
                    this.showUndo = true;
                    this.undoTimeout = setTimeout(() => {
                        this.showUndo = false;
                    }, UNDO_TOAST_MS);
                } else {
                    this.showUndo = false;
                }
            });

            this.$watch('$wire.editingMsg', (val) => {
                if (!val) {
                    this.isEditing = false;
                    this.editingMsgId = null;
                    this.editingBody = '';
                    this.editingOriginal = '';
                }
            });
        },

        async _pollTick() {
            const unreadBefore = this._readUnread();
            const activeOpen = !!this.$wire.activeChannelId;
            const lastMsgBefore = activeOpen ? this._readLastMessageMarker() : null;
            const typingBefore = activeOpen ? this._readTypingMarker() : null;
            let changed = false;

            try {
                await this.$wire.$island('sidebar').refreshUnread();
                this.syncChannelCount();
                this.syncPushNotify();
                changed = this._readUnread() !== unreadBefore;

                if (activeOpen) {
                    await this.$wire.$island('messages').refreshActive();
                    if (this._readLastMessageMarker() !== lastMsgBefore) changed = true;
                    if (this._readTypingMarker() !== typingBefore) changed = true;
                }
            } catch {}

            this._applyPollResult(changed, activeOpen ? OPEN_POLL_CEILING_MS : IDLE_POLL_CEILING_MS);
        },

        _sendTypingPing() {
            return this.$wire.$island('messages').pingTyping();
        },

        _readUnread() {
            const el = document.querySelector(`[${TOTAL_UNREAD_ATTR}]`);
            return parseInt(el?.getAttribute(TOTAL_UNREAD_ATTR), 10) || 0;
        },

        _readLastMessageMarker() {
            const rows = this._msgViewportEl?.querySelectorAll(`[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`);
            return rows && rows.length ? rows[rows.length - 1].getAttribute('data-rf') : null;
        },

        _readTypingMarker() {
            return document.querySelector('[data-typing]')?.getAttribute('data-typing') ?? null;
        },

        destroy() {
            this.stopPolling();
            document.removeEventListener('visibilitychange', this._onVisibility);

            const vp = this._msgViewportEl;
            if (vp && this._onScroll) vp.removeEventListener('scroll', this._onScroll);

            document.removeEventListener('selectionchange', this._onSelection);
            document.removeEventListener('keydown', this._onSlash);

            if (this._scrollRaf) cancelAnimationFrame(this._scrollRaf);
            if (this._selRaf) cancelAnimationFrame(this._selRaf);
            if (this.chipsFadeTimer) clearTimeout(this.chipsFadeTimer);
        },

        syncChannelCount() {
            const el = document.querySelector('[data-channel-count]');
            if (el) this.channelCount = parseInt(el.getAttribute('data-channel-count'), 10) || 0;
        },

        syncPushNotify() {
            const el = document.querySelector(`[${TOTAL_UNREAD_ATTR}]`);
            const now = parseInt(el?.getAttribute(TOTAL_UNREAD_ATTR), 10) || 0;

            if (this._lastUnread !== undefined && now > this._lastUnread) {
                this.$store.push.notify('پیام جدید', 'یک گروه پیام جدید دارد', SCOPE);
            }
            this._lastUnread = now;
        },

        get mentionMatches() {
            if (!this.mentionOpen) return [];

            const q = (this.mentionQuery || '').toLowerCase();
            const list = this.$wire.mentionMemberNames || [];
            if (!q) return list.slice(0, 8);

            const filtered = [];
            for (let i = 0; i < list.length; i++) {
                const n = list[i];
                if ((n || '').toLowerCase().includes(q)) {
                    filtered.push(n);
                    if (filtered.length === 8) break;
                }
            }
            return filtered;
        },

        openMentionPicker() {
            const ta = document.getElementById(MSG_TEXTAREA_ID);
            if (!ta) return;

            const pos = ta.selectionStart ?? ta.value.length;
            const val = ta.value;
            const needsSpace = pos > 0 && !/\s/.test(val[pos - 1]);
            const insert = (needsSpace ? ' ' : '') + '@';
            const next = val.substring(0, pos) + insert + val.substring(pos);

            ta.value = next;
            const at = pos + (needsSpace ? 1 : 0);
            ta.focus();
            ta.selectionStart = ta.selectionEnd = at + 1;

            this.$wire.set('composer.body', next);
            this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = '';
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        detectMention(e) {
            this.resetPollInterval();
            this.pingTyping();
            const ta = e.target;
            const pos = ta.selectionStart;
            const before = ta.value.substring(0, pos);
            const at = before.lastIndexOf('@');

            if (at < 0) { this.mentionOpen = false; return; }
            const segment = before.substring(at + 1);
            if (/[\s@]/.test(segment)) { this.mentionOpen = false; return; }

            const prev = at > 0 ? before[at - 1] : ' ';
            if (!/\s/.test(prev)) { this.mentionOpen = false; return; }

            if (!this.mentionOpen) this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = segment;
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        onComposerKeydown(e) {
            if (this.mentionOpen) {
                const matches = this.mentionMatches;
                const len = matches.length;
                if (len) {
                    if (e.key === 'ArrowDown') { e.preventDefault(); this.mentionActiveIndex = (this.mentionActiveIndex + 1) % len; return; }
                    if (e.key === 'ArrowUp') { e.preventDefault(); this.mentionActiveIndex = (this.mentionActiveIndex - 1 + len) % len; return; }
                    if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); this.pickMention(this.mentionActiveIndex); return; }
                    if (e.key === 'Escape') { e.preventDefault(); this.mentionOpen = false; return; }
                }
            }
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.sendMessage(); }
        },

        pickMention(i) {
            const name = this.mentionMatches[i];
            if (!name) { this.mentionOpen = false; return; }

            const ta = document.getElementById(MSG_TEXTAREA_ID);
            if (!ta) { this.mentionOpen = false; return; }

            const pos = ta.selectionStart;
            const val = ta.value;
            const at = val.substring(0, pos).lastIndexOf('@');

            if (at < 0) { this.mentionOpen = false; return; }

            const insert = name + ' ';
            const next = val.substring(0, at + 1) + insert + val.substring(pos);

            this.mentionOpen = false;
            this.mentionQuery = '';
            this.$wire.set('composer.body', next);
            this.$nextTick(() => { ta.focus(); ta.selectionStart = ta.selectionEnd = at + 1 + insert.length; });
        },

        toggleActions(id) {
            this.openActionsId = (this.openActionsId === id ? null : id);
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
            if (this.$wire.createMode) this.$wire.set('createMode', false);
            if (this.$wire.browseMode) this.$wire.set('browseMode', false);
            this.$wire.cancelReply();
            this.$wire.cancelEdit();
        },

        scrollToMessage(id) {
            if (!id) return;
            const el = document.querySelector(`[data-rf="${DATA_RF_MESSAGE_PREFIX}-${id}"]`);
            if (el) {
                document.querySelectorAll('.record-focus-flash').forEach(n => n.classList.remove('record-focus-flash'));
                el.style.animation = 'none';
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('record-focus-flash');
                return;
            }
            this.$wire.$island('messages').focusMessage(id).catch(() => {});
        },

        filterSender(name) {
            this.activeSender = name;
            let latestId = 0;

            const rows = document.querySelectorAll(`[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`);
            const len = rows.length;

            for (let i = 0; i < len; i++) {
                const r = rows[i];
                if (r.getAttribute(DATA_SENDER_NAME_ATTR) !== name) continue;

                const attr = r.getAttribute('data-rf');
                const id = parseInt(attr.substring(RF_ID_OFFSET), 10);
                if (id > latestId) latestId = id;
            }

            if (latestId) {
                this.scrollToMessage(latestId);
                return;
            }
            this.$wire.$island('messages').focusSender(name).catch(() => {});
        },

        clearSenderFilter() {
            if (this.activeSender === null) return;
            this.activeSender = null;
            this.$wire.$island('messages').clearFocus()
                .then(() => this.$nextTick(() => this.scrollToBottom(true)))
                .catch(() => {});
        },

        revealChips() {
            if (this.chipsFadeTimer) clearTimeout(this.chipsFadeTimer);
            this.chipsVisible = true;
        },

        scheduleChipsFade() {
            if (this.chipsFadeTimer) clearTimeout(this.chipsFadeTimer);
            this.chipsFadeTimer = setTimeout(() => { this.chipsVisible = false; }, 3000);
        },

        selectChannel(id) {
            if (!id) return;
            this.resetPollInterval();

            const ta = document.getElementById(MSG_TEXTAREA_ID);
            this.resetAutoResize(ta);
            if (ta) ta.dir = 'rtl';

            this.replyingTo = null;
            this.activeSender = null;
            this.isEditing = false;
            this.editingMsgId = null;
            this.editingBody = '';
            this.editingOriginal = '';
            this.deletingId = null;
            this.openActionsId = null;
            this.searchMessages = false;

            this.$wire.cancelReply();
            this.$wire.$island('messages').selectChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.$nextTick(() => this.scrollToBottom(true)))
                .then(() => { this.chipsVisible = true; this.scheduleChipsFade(); })
                .then(() => {
                    if (window.innerWidth < MOBILE_BREAKPOINT && ta) {
                        this.$nextTick(() => ta.focus());
                    }
                });
        },

        toggleBrowse() {
            this.$wire.$island('messages').toggleBrowse()
                .then(() => this.$wire.$island('sidebar').refreshUnread());
        },

        openCreate() {
            this.$wire.$island('messages').openCreate()
                .then(() => this.$wire.$island('sidebar').refreshUnread());
        },

        closeCreate() {
            this.$wire.$island('messages').closeCreate()
                .then(() => this.$wire.$island('sidebar').refreshUnread());
        },

        backToList() {
            this.searchMessages = false;
            this.activeSender = null;
            this.openActionsId = null;
            this.$wire.$island('messages').backToList()
                .then(() => this.$wire.$island('sidebar').refreshUnread());
        },

        leaveChannel(id) {
            if (!id) return;
            if (!confirm('از این گروه خارج می‌شوید؟')) return;
            this.searchMessages = false;
            this.$wire.$island('messages').leaveChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.syncChannelCount())
                .catch(() => {});
        },

        createChannel() {
            this.$wire.$island('messages').createChannel()
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => {
                    this.syncChannelCount();
                });
        },

        joinChannel(id) {
            if (!id) return;
            this.$wire.$island('messages').joinChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => {
                    this.syncChannelCount();
                })
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
            this.$nextTick(() => document.getElementById(MSG_TEXTAREA_ID)?.focus());
        },

        startEdit(id, body) {
            if (!id) return;
            this.replyingTo = null;
            this.deletingId = null;
            this.editingMsgId = id;
            this.editingOriginal = body || '';
            this.editingBody = body || '';
            this.isEditing = true;

            this.$wire.cancelReply();
            this.$wire.editMessage(id);
        },

        cancelEdit() {
            this.isEditing = false;
            this.editingMsgId = null;
            this.editingBody = '';
            this.editingOriginal = '';
            this.$wire.cancelEdit();
        },

        async saveEdit(id) {
            if (!id || id !== this.editingMsgId) return;
            const body = this.editingBody || '';
            if (body === this.editingOriginal) return;

            if (body.length > MAX_BODY_LENGTH) {
                this.toast('متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.', 'warning');
                return;
            }

            try {
                await this.$wire.$island('messages').saveEdit(id, body);
            } catch {
                this.toast('خطا در ارتباط با سرور.', 'error');
            }
        },

        confirmDelete(id) {
            if (!id) return;
            this.replyingTo = null;
            this.isEditing = false;
            this.editingMsgId = null;
            this.editingBody = '';
            this.editingOriginal = '';
            this.deletingId = id;
        },

        confirmCancelInvite(id) {
            this.cancelInviteeId = id;
        },

        async doCancelInvite(channelId, userId) {
            try {
                await this.$wire.$island('messages').cancelInvite(channelId, userId);
                this.$wire.$island('sidebar').refreshUnread();
            } finally {
                this.cancelInviteeId = null;
            }
        },

        async sendMessage() {
            if (this.sending) return;
            this.resetPollInterval();

            const ta = document.getElementById(MSG_TEXTAREA_ID);
            const body = ta?.value ? ta.value.trim() : '';
            const attachments = this.$wire.composer?.attachments || [];

            if (body.length === 0 && attachments.length === 0) {
                this.toast('پیام نمی‌تواند خالی باشد.', 'warning');
                return;
            }

            if (body.length > MAX_BODY_LENGTH) {
                this.toast('متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.', 'warning');
                return;
            }

            this.sending = true;
            try {
                await this.$wire.$island('messages').send();
                this.replyingTo = null;
                this.$wire.$island('sidebar').refreshUnread().catch(() => {});
            } catch {
                this.toast('خطا در ارتباط با سرور.', 'error');
            } finally {
                setTimeout(() => {
                    this.sending = false;
                }, SEND_LOCK_RESET_MS);
            }
        },
    };
}
