import {emojis} from "../stores/emoji.js";

import maximizeMixin from "../mixins/maximize.js";
import clipboardMixin from "../mixins/clipboard.js";
import pasteImageMixin from "../mixins/pasteImage.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));

export default function channel() {
    return {
        ...maximizeMixin(),
        ...clipboardMixin(),
        ...pasteImageMixin(),
        showScrollFab: false,
        showInfo: false,
        searchMessages: false,
        showUndo: false,
        undoTimeout: null,
        sending: false,
        channelCount: 0,
        openActionsId: null,
        replyingTo: null,
        editingOriginal: '',
        isEditing: false,
        editingMsgId: null,
        editingBody: '',
        deletingId: null,
        emojiOpen: false,
        activeCat: 0,
        emojis: emojis,
        isHighlighted: false,
        backgroundPattern: 'off',
        searchFullscreen: false,
        searchValue: '',
        messageSearchFullscreen: false,
        messageSearchValue: '',
        _timer: null,
        _onVisibility: null,
        _onScroll: null,
        _loadingOlder: false,
        inviteToasts: [],
        seenChannelIds: [],
        _firstVisit: false,
        quoteChip: {visible: false, x: 0, y: 0, id: 0, sender: '', snippet: ''},
        activeSender: null,
        senderChips: [],
        _selRaf: null,
        _unregisterMorph: null,

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
            const seenKey = 'channel-seen-invites';
            const stored = localStorage.getItem(seenKey);
            if (stored === null) {
                this._firstVisit = true;
                this.seenChannelIds = [];
            } else {
                this._firstVisit = false;
                try { this.seenChannelIds = JSON.parse(stored) || []; } catch (e) { this.seenChannelIds = []; }
            }
            this.$nextTick(() => this.syncInviteToasts());
            this.startPolling();
            this._onVisibility = () => document.hidden ? this.stopPolling() : this.startPolling();
            document.addEventListener('visibilitychange', this._onVisibility);

            const vp = document.getElementById('msg-viewport');
            if (vp) {
                vp.style.overflowAnchor = 'none';
                let ticking = false;
                this._onScroll = () => {
                    if (!ticking) {
                        requestAnimationFrame(() => {
                            this.quoteChip.visible = false;
                            this.openActionsId = null;
                            this.showScrollFab = (vp.scrollHeight - vp.scrollTop - vp.clientHeight) > 200;
                            if (vp.scrollTop < 80 && !this._loadingOlder && this.$wire.hasOlder) {
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
                    const vp = document.getElementById('msg-viewport');
                    if (!vp || !vp.contains(node)) { this.quoteChip.visible = false; return; }
                    const el = node.nodeType === 1 ? node : node.parentElement;
                    if (!el || el.closest('input, textarea, [contenteditable]')) {
                        this.quoteChip.visible = false;
                        return;
                    }
                    const row = el.closest('[data-rf^="channel-message-"]');
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

            this._collectSenders = () => {
                const set = new Set();
                document.querySelectorAll('[data-rf^="channel-message-"][data-sender-name]').forEach(r => {
                    const n = r.getAttribute('data-sender-name');
                    if (n) set.add(n);
                });
                this.senderChips = [...set];
            };
            this._collectSenders();
            this._unregisterMorph = Livewire.hook('morph', ({el}) => {
                if (this.$root.contains(el)) {
                    this.$nextTick(() => this._collectSenders());
                }
            });

            this.$wire.on('message-sent', () => {
                this.scrollToBottom(true);
                this.sending = false;
                this.$store.sound?.playOutgoing(this.$wire.activeChannelId);
                setTimeout(() => this.scrollToBottom(true), 120);
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
                    }, 4000);
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
            this._timer = setInterval(() => this.$wire.$island('sidebar').refreshUnread().then(() => { this.syncChannelCount(); this.syncInviteToasts(); this.syncPushNotify(); }), 10000);
        },

        stopPolling() {
            clearInterval(this._timer);
            this._timer = null;
        },

        destroy() {
            this.stopPolling();
            document.removeEventListener('visibilitychange', this._onVisibility);
            const vp = document.getElementById('msg-viewport');
            if (vp && this._onScroll) vp.removeEventListener('scroll', this._onScroll);
            document.removeEventListener('selectionchange', this._onSelection);
            document.removeEventListener('keydown', this._onSlash);
            if (this._selRaf) cancelAnimationFrame(this._selRaf);
            if (this._unregisterMorph) this._unregisterMorph();
        },

        toast(message, type = 'info') {
            if (!message) return;
            window.dispatchEvent(new CustomEvent('toast', {detail: {message, type}}));
        },

        scrollToBottom(smooth = false) {
            document.getElementById('msg-viewport')?.scrollTo({
                top: 999999,
                behavior: smooth ? 'smooth' : 'instant'
            });
        },

        isEmojiOnly(text) {
            const stripped = text?.replace(/<[^>]*>/g, '').trim() ?? '';
            if (!stripped) return false;
            return [...segmenter.segment(stripped)]
                .every(({segment}) => emojiSet.has(segment) || /^\s+$/.test(segment));
        },

        toggleHighlight() {
            this.isHighlighted = !this.isHighlighted;
            this.backgroundPattern = this.backgroundPattern === 'on' ? 'off' : 'on';
            localStorage.setItem('chat-settings', JSON.stringify({
                isHighlighted: this.isHighlighted,
                backgroundPattern: this.backgroundPattern
            }));
        },

        syncChannelCount() {
            const el = document.querySelector('[data-channel-count]');
            if (el) this.channelCount = parseInt(el.dataset.channelCount) || 0;
        },

        syncPushNotify() {
            const el = document.querySelector('[data-total-unread]');
            const now = parseInt(el?.dataset.totalUnread) || 0;
            if (this._lastUnread !== undefined && now > this._lastUnread) {
                this.$store.push.notify('پیام جدید', 'یک کانال پیام جدید دارد', 'channel');
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

        markSeen(id) {
            if (!id) return;
            if (!this.seenChannelIds.includes(id)) {
                this.seenChannelIds.push(id);
                this.saveSeenChannelIds();
            }
            this.inviteToasts = this.inviteToasts.filter(t => t.id !== id);
        },

        saveSeenChannelIds() {
            try { localStorage.setItem('channel-seen-invites', JSON.stringify(this.seenChannelIds)); } catch (e) {}
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

        openMessageSearch() {
            this.searchMessages = !this.searchMessages;
            if (this.searchMessages) {
                this.$nextTick(() => document.getElementById('msg-search-input')?.focus());
            }
        },

        focusSearchResult(id) {
            if (!id) return;
            this.searchMessages = false;
            this.$wire.$island('messages').focusMessage(id).catch(() => {});
        },

        scrollToMessage(id) {
            if (!id) return;
            const el = document.querySelector(`[data-rf="channel-message-${id}"]`);
            if (el) {
                document.querySelectorAll('.record-focus-flash').forEach(n => n.classList.remove('record-focus-flash'));
                el.style.animation = 'none';
                el.scrollIntoView({behavior: 'smooth', block: 'center'});
                el.classList.add('record-focus-flash');
                return;
            }
            this.$wire.$island('messages').focusMessage(id).catch(() => {});
        },

        insertEmoji(e) {
            const ta = document.getElementById('msg-ta');
            if (!ta || typeof e !== 'string') return;

            const s = ta.selectionStart;
            const val = ta.value;

            this.$wire.set('composer.body', val.slice(0, s) + e + val.slice(s));
            this.emojiOpen = false;

            this.$nextTick(() => {
                ta.focus();
                ta.selectionStart = ta.selectionEnd = s + e.length;
            });
        },

        copyMessage(text) {
            if (!text || typeof text !== 'string') return;
            this.copyText(text, 'پیام کپی شد', 'info');
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
                .then(() => { if (window.innerWidth < 768) this.$nextTick(() => { document.getElementById('msg-ta')?.focus(); }); });
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
            this.deletingId = null;
            this.openActionsId = null;
            this.$wire.replyTo(id);
            this.$wire.cancelEdit();
            this.$nextTick(() => document.getElementById('msg-ta')?.focus());
        },

        cancelReply() {
            this.replyingTo = null;
            this.$wire.cancelReply();
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
            if (body.length > 4000) {
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

        cancelDelete() {
            this.deletingId = null;
        },

        async deleteMessage() {
            if (!this.deletingId) return;

            try {
                await this.$wire.$island('messages').deleteMessage(this.deletingId);
                this.cancelDelete();
                this.openActionsId = null;
            } catch (error) {
                this.toast('خطا در حذف پیام.', 'error');
            }
        },

        async sendMessage() {
            if (this.sending) return;

            const ta = document.getElementById('msg-ta');
            const body = ta?.value ? ta.value.trim() : '';
            const attachments = this.$wire.composer?.attachments || [];

            if (body.length === 0 && attachments.length === 0) {
                this.toast('پیام نمی‌تواند خالی باشد.', 'warning');
                return;
            }

            if (body.length > 4000) {
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
                }, 500);
            }
        },
    };
}