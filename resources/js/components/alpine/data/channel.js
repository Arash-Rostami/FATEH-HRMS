import maximizeMixin from "../mixins/maximize.js";
import clipboardMixin from "../mixins/clipboard.js";
import pasteImageMixin from "../mixins/pasteImage.js";
import chatBase from "../mixins/chatBase.js";

const SCOPE = 'channel';
const POLL_INTERVAL_MS = 10000;
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
const SEEN_INVITES_KEY = 'channel-seen-invites';
const MENTION_SEEN_KEY = 'channel-mention-seen';

export default function channel() {
    return {
        ...maximizeMixin(),
        ...clipboardMixin(),
        ...pasteImageMixin(),
        ...chatBase(),
        channelCount: 0,
        editingOriginal: '',
        isEditing: false,
        editingMsgId: null,
        editingBody: '',
        inviteToasts: [],
        seenChannelIds: [],
        _firstVisit: false,
        quoteChip: {visible: false, x: 0, y: 0, id: 0, sender: '', snippet: ''},
        activeSender: null,
        chipsVisible: true,
        chipsFadeTimer: null,
        mentionOpen: false,
        mentionQuery: '',
        mentionActiveIndex: 0,
        mentionToasts: [],
        _scrollRaf: null,

        init() {
            const saved = localStorage.getItem('chat-settings');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.isHighlighted = data.isHighlighted ?? false;
                    this.backgroundPattern = data.backgroundPattern ?? 'off';
                } catch (e) {}
            }
            this.syncChannelCount();
            this.syncPushNotify();
            const seenKey = SEEN_INVITES_KEY;
            const stored = localStorage.getItem(seenKey);
            if (stored === null) {
                this._firstVisit = true;
                this.seenChannelIds = [];
            } else {
                this._firstVisit = false;
                try { this.seenChannelIds = JSON.parse(stored) || []; } catch (e) { this.seenChannelIds = []; }
            }
            this.$nextTick(() => this.syncInviteToasts());
            this.$nextTick(() => this.syncMentionToasts());
            this.startPolling();
            this._onVisibility = () => document.hidden ? this.stopPolling() : this.startPolling();
            document.addEventListener('visibilitychange', this._onVisibility);

            const vp = document.getElementById(MSG_VIEWPORT_ID);
            if (vp) {
                vp.style.overflowAnchor = 'none';
                let ticking = false;
                this._onScroll = () => {
                    if (!ticking) {
                        this._scrollRaf = requestAnimationFrame(() => {
                            this._scrollRaf = null;
                            this.quoteChip.visible = false;
                            this.openActionsId = null;
                            this.showScrollFab = (vp.scrollHeight - vp.scrollTop - vp.clientHeight) > SCROLL_FAB_DISTANCE_THRESHOLD;
                            if (vp.scrollTop < LOAD_OLDER_SCROLL_THRESHOLD && !this._loadingOlder && this.$wire.hasOlder) {
                                this._loadingOlder = true;
                                const prevHeight = vp.scrollHeight;
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
                vp.addEventListener('scroll', this._onScroll, {passive: true});
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
                    const vp = document.getElementById(MSG_VIEWPORT_ID);
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
                    const id = parseInt(row.getAttribute('data-rf').split('-').pop(), 10) || 0;
                    const senderEl = row.querySelector('[data-sender]');
                    const rect = sel.getRangeAt(0).getBoundingClientRect();
                    this.quoteChip = {visible: true, x: rect.left, y: rect.top, id, sender: senderEl ? senderEl.getAttribute('data-sender') : '', snippet: text.slice(0, 120)};
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

        startPolling() {
            if (this._timer) return;
            this._timer = setInterval(() => this.$wire.$island('sidebar').refreshUnread().then(() => { this.syncChannelCount(); this.syncInviteToasts(); this.syncMentionToasts(); this.syncPushNotify(); }), POLL_INTERVAL_MS);
        },

        destroy() {
            this.stopPolling();
            document.removeEventListener('visibilitychange', this._onVisibility);
            const vp = document.getElementById(MSG_VIEWPORT_ID);
            if (vp && this._onScroll) vp.removeEventListener('scroll', this._onScroll);
            document.removeEventListener('selectionchange', this._onSelection);
            document.removeEventListener('keydown', this._onSlash);
            if (this._scrollRaf) cancelAnimationFrame(this._scrollRaf);
            if (this._selRaf) cancelAnimationFrame(this._selRaf);
            if (this.chipsFadeTimer) clearTimeout(this.chipsFadeTimer);
        },

        syncChannelCount() {
            const el = document.querySelector('[data-channel-count]');
            if (el) this.channelCount = parseInt(el.dataset.channelCount) || 0;
        },

        syncPushNotify() {
            const el = document.querySelector(`[${TOTAL_UNREAD_ATTR}]`);
            const now = parseInt(el?.dataset.totalUnread) || 0;
            if (this._lastUnread !== undefined && now > this._lastUnread) {
                this.$store.push.notify('پیام جدید', 'یک کانال پیام جدید دارد', SCOPE);
            }
            this._lastUnread = now;
        },

        syncInviteToasts() {
            const els = document.querySelectorAll('[data-channel-id]');
            const current = [];
            const names = {};
            els.forEach(el => {
                const id = parseInt(el.dataset.channelId);
                if (id) { current.push(id); names[id] = el.dataset.channelName || ''; }
            });
            if (this._firstVisit) {
                this.seenChannelIds = current;
                this._firstVisit = false;
                this.saveSeenChannelIds();
                return;
            }
            const seen = new Set(this.seenChannelIds);
            const toastIds = new Set(this.inviteToasts.map(t => t.id));
            current.forEach(id => {
                if (!seen.has(id) && !toastIds.has(id)) {
                    this.inviteToasts.push({id, name: names[id]});
                }
            });
        },

        get mentionMatches() {
            if (!this.mentionOpen) return [];
            const q = (this.mentionQuery || '').toLowerCase();
            const list = this.$wire.mentionMemberNames || [];
            const filtered = q ? list.filter(n => (n || '').toLowerCase().includes(q)) : list;
            return filtered.slice(0, 8);
        },

        openMentionPicker() {
            const ta = document.getElementById(MSG_TEXTAREA_ID);
            if (!ta) return;
            const pos = ta.selectionStart ?? ta.value.length;
            const val = ta.value;
            const needsSpace = pos > 0 && !/\s/.test(val[pos - 1]);
            const insert = (needsSpace ? ' ' : '') + '@';
            const next = val.slice(0, pos) + insert + val.slice(pos);
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
            const ta = e.target;
            const pos = ta.selectionStart;
            const before = ta.value.slice(0, pos);
            const at = before.lastIndexOf('@');
            if (at < 0) { this.mentionOpen = false; return; }
            const segment = before.slice(at + 1);
            if (/[\s@]/.test(segment)) { this.mentionOpen = false; return; }
            const prev = at > 0 ? before[at - 1] : ' ';
            if (!/\s/.test(prev)) { this.mentionOpen = false; return; }
            if (!this.mentionOpen) this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = segment;
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        onComposerKeydown(e) {
            if (this.mentionOpen && this.mentionMatches.length) {
                if (e.key === 'ArrowDown') { e.preventDefault(); this.mentionActiveIndex = (this.mentionActiveIndex + 1) % this.mentionMatches.length; return; }
                if (e.key === 'ArrowUp') { e.preventDefault(); this.mentionActiveIndex = (this.mentionActiveIndex - 1 + this.mentionMatches.length) % this.mentionMatches.length; return; }
                if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); this.pickMention(this.mentionActiveIndex); return; }
                if (e.key === 'Escape') { e.preventDefault(); this.mentionOpen = false; return; }
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
            const at = val.slice(0, pos).lastIndexOf('@');
            if (at < 0) { this.mentionOpen = false; return; }
            const insert = name + ' ';
            const next = val.slice(0, at + 1) + insert + val.slice(pos);
            this.mentionOpen = false;
            this.mentionQuery = '';
            this.$wire.set('composer.body', next);
            this.$nextTick(() => { ta.focus(); ta.selectionStart = ta.selectionEnd = at + 1 + insert.length; });
        },

        syncMentionToasts() {
            const els = document.querySelectorAll('[data-mention-toast]');
            const seen = this.loadMentionSeen();
            const byChannel = {};
            els.forEach(el => {
                const cid = parseInt(el.dataset.mentionToast);
                const mid = parseInt(el.dataset.msgId) || 0;
                if (!cid || !mid || mid <= (seen[cid] || 0)) return;
                if (!byChannel[cid] || mid > byChannel[cid].message_id) {
                    byChannel[cid] = {
                        channel_id: cid,
                        message_id: mid,
                        sender_name: el.dataset.senderName || '',
                        channel_name: el.dataset.channelName || '',
                    };
                }
            });
            this.mentionToasts = Object.values(byChannel);
        },

        loadMentionSeen() {
            try { return JSON.parse(localStorage.getItem(MENTION_SEEN_KEY) || '{}') || {}; }
            catch (e) { return {}; }
        },

        advanceMentionSeen(cid, mid) {
            if (!cid || !mid) return;
            const seen = this.loadMentionSeen();
            if ((seen[cid] || 0) < mid) {
                seen[cid] = mid;
                try { localStorage.setItem(MENTION_SEEN_KEY, JSON.stringify(seen)); } catch (e) {}
            }
        },

        openMentionToast(t) {
            if (!t || !t.channel_id) return;
            this.advanceMentionSeen(t.channel_id, t.message_id);
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
            this.$wire.$island('messages').selectChannel(t.channel_id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.$nextTick(() => this.scrollToMessage(t.message_id)))
                .catch(() => {});
        },

        dismissMentionToast(t) {
            if (!t || !t.channel_id) return;
            this.advanceMentionSeen(t.channel_id, t.message_id);
            this.mentionToasts = this.mentionToasts.filter(x => x.channel_id !== t.channel_id);
        },

        markSeen(id) {
            if (!id) return;
            if (!this.seenChannelIds.includes(id)) {
                this.seenChannelIds.push(id);
                this.saveSeenChannelIds();
            }
            this.inviteToasts = this.inviteToasts.filter(t => t.id !== id);
        },

        saveSeenChannelIds() {
            try { localStorage.setItem(SEEN_INVITES_KEY, JSON.stringify(this.seenChannelIds)); } catch (e) {}
        },

        acceptInvite(id) {
            this.markSeen(id);
        },

        declineInvite(id) {
            this.inviteToasts = this.inviteToasts.filter(t => t.id !== id);
            this.$wire.$island('messages').leaveChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.syncChannelCount())
                .catch(() => {});
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
                el.scrollIntoView({behavior: 'smooth', block: 'center'});
                el.classList.add('record-focus-flash');
                return;
            }
            this.$wire.$island('messages').focusMessage(id).catch(() => {});
        },

        filterSender(name) {
            this.activeSender = name;
            let latestId = 0;
            document.querySelectorAll(`[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`).forEach(r => {
                if (r.dataset.senderName !== name) return;
                const id = parseInt(r.getAttribute('data-rf').slice(DATA_RF_MESSAGE_PREFIX.length + 1), 10);
                if (id > latestId) latestId = id;
            });
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
                .then(() => { if (window.innerWidth < MOBILE_BREAKPOINT) this.$nextTick(() => { document.getElementById(MSG_TEXTAREA_ID)?.focus(); }); });
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
            if (!confirm('از این کانال خارج می‌شوید؟')) return;
            this.searchMessages = false;
            const idx = this.seenChannelIds.indexOf(id);
            if (idx > -1) { this.seenChannelIds.splice(idx, 1); this.saveSeenChannelIds(); }
            this.inviteToasts = this.inviteToasts.filter(t => t.id !== id);
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
                    this.markSeen(this.$wire.activeChannelId);
                });
        },

        joinChannel(id) {
            if (!id) return;
            this.$wire.$island('messages').joinChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => {
                    this.syncChannelCount();
                    this.markSeen(id);
                })
                .catch(() => {});
        },

        startReply(id, senderName, body) {
            if (!id) return;
            this.replyingTo = {id, sender: {name: senderName || 'Unknown'}, body: body || ''};
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
            } catch (error) {
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

        async sendMessage() {
            if (this.sending) return;

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
            } catch (error) {
                this.toast('خطا در ارتباط با سرور.', 'error');
            } finally {
                setTimeout(() => {
                    this.sending = false;
                }, SEND_LOCK_RESET_MS);
            }
        },
    };
}
