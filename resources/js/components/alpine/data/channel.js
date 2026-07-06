import {emojis} from "../stores/emoji.js";

import maximizeMixin from "../mixins/maximize.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));

export default function channel() {
    return {
        ...maximizeMixin(),
        showScrollFab: false,
        showInfo: false,
        searchMessages: false,
        showUndo: false,
        undoTimeout: null,
        sending: false,
        channelCount: 0,
        replyingTo: null,
        editingOriginal: '',
        isEditing: false,
        editingMsgId: null,
        editingBody: '',
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

            this.$wire.on('message-sent', () => {
                this.scrollToBottom(true);
                this.sending = false;
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
            this._timer = setInterval(() => this.$wire.$island('sidebar').refreshUnread().then(() => this.syncChannelCount()), 10000);
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

        closeOverlays() {
            this.showInfo = false;
            this.searchMessages = false;
            this.replyingTo = null;
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

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => this.toast('پیام کپی شد', 'info'))
                    .catch(() => this.fallbackCopyText(text));
            } else {
                this.fallbackCopyText(text);
            }
        },

        fallbackCopyText(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.top = '0';
            ta.style.left = '0';
            ta.style.opacity = '0';
            ta.setAttribute('readonly', '');
            document.body.appendChild(ta);
            ta.select();

            try {
                document.execCommand('copy');
                this.toast('پیام کپی شد', 'info');
            } catch (err) {
                this.toast('خطا در کپی پیام', 'error');
            } finally {
                document.body.removeChild(ta);
            }
        },

        selectChannel(id) {
            if (!id) return;
            this.replyingTo = null;
            this.isEditing = false;
            this.editingMsgId = null;
            this.editingBody = '';
            this.editingOriginal = '';
            this.searchMessages = false;
            this.$wire.cancelReply();
            this.$wire.$island('messages').selectChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.$nextTick(() => this.scrollToBottom(true)));
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
            this.$wire.$island('messages').backToList()
                .then(() => this.$wire.$island('sidebar').refreshUnread());
        },

        leaveChannel(id) {
            if (!id) return;
            if (!confirm('از این کانال خارج می‌شوید؟')) return;
            this.searchMessages = false;
            this.$wire.$island('messages').leaveChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.syncChannelCount());
        },

        createChannel() {
            this.$wire.$island('messages').createChannel()
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.syncChannelCount());
        },

        joinChannel(id) {
            if (!id) return;
            this.$wire.$island('messages').joinChannel(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.syncChannelCount());
        },

        startReply(id, senderName, body) {
            if (!id) return;
            this.replyingTo = {id, sender: {name: senderName || 'Unknown'}, body: body || ''};
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
            this.$wire.$island('messages').deleteMessage(id);
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