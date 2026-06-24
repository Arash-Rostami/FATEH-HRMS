import settings from "./settings.js";
import {emojis} from "../stores/emoji.js";

export default function contact() {
    return {
        bgOption: 'a',
        isTyping: false,
        typingTimeout: null,
        showScrollFab: false,
        showInfo: false,
        showUndo: false,
        undoTimeout: null,
        sending: false,
        replyingTo: null,
        editingMsg: null,
        deletingId: null,
        emojiOpen: false,
        max: false,
        activeCat: 0,
        isHighlighted: false,
        backgroundPattern: 'off',
        emojis: emojis,

        toggleMaximize() {
            this.max = !this.max;

            ['footer', 'header', 'navbar'].forEach(id => {
                document.getElementById(id)
                    ?.classList.toggle('layout-hidden', this.max);
            });

            this.$nextTick(() => {
                this.scrollToBottom(false);
                this.focusSearch();
            });
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

        init() {
            this.initPattern();
            const saved = localStorage.getItem('chat-settings');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.isHighlighted = data.isHighlighted;
                    this.backgroundPattern = data.backgroundPattern;
                } catch (e) {
                }
            }

            const vp = document.getElementById('msg-viewport');
            if (vp) {
                let ticking = false;
                vp.addEventListener('scroll', () => {
                    if (!ticking) {
                        requestAnimationFrame(() => {
                            this.showScrollFab = (vp.scrollHeight - vp.scrollTop - vp.clientHeight) > 200;
                            ticking = false;
                        });
                        ticking = true;
                    }
                }, {passive: true});
            }

            window.addEventListener('typing-indicator', () => {
                this.isTyping = true;
                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => {
                    this.isTyping = false;
                }, 2500);
            });

            this.$wire.on('chat-ready', () => this.$nextTick(() => {
                this.scrollToBottom(false);
                this.resetUI();
            }));

            this.$wire.on('message-sent', () => this.$nextTick(() => {
                this.scrollToBottom(true);
                this.sending = false;
            }));

            this.$wire.on('message-error', () => this.$nextTick(() => {
                this.sending = false;
            }));

            this.$wire.on('show-toast', (e) => this.toast(e.message, e.type ?? 'info'));

            this.$wire.on('show-undo-toast', (e) => this.toast(e.message, 'warning'));

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
        },

        initPattern() {
            try {
                const settingInstance = settings();
                return settingInstance.initPattern();
            } catch (error) {
                console.error(error);
            }
        },

        isEmojiOnly(text) {
            const stripped = text?.replace(/<[^>]*>/g, '').trim() ?? '';
            if (!stripped) return false;
            const emojiSet = new Set(emojis.flatMap(c => c.items));
            return [...new Intl.Segmenter().segment(stripped)]
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

        focusSearch() {
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        },

        closeOverlays() {
            this.showInfo = false;
            this.cancelEdit();
            this.cancelDelete();
        },

        resetUI() {
            this.replyingTo = null;
            this.editingMsg = null;
            this.deletingId = null;
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

        startReply(id, senderName, body) {
            if (!id) return;
            this.editingMsg = null;
            this.deletingId = null;
            this.replyingTo = {id, sender: {name: senderName || 'Unknown'}, body: body || ''};
            this.$nextTick(() => document.getElementById('msg-ta')?.focus());
        },

        cancelReply() {
            this.replyingTo = null;
        },

        startEdit(id, body) {
            if (!id) return;
            this.replyingTo = null;
            this.deletingId = null;
            this.editingMsg = {id, body: body || ''};
            this.$wire.set('edit.editingBody', body);
            this.$nextTick(() => {
                const ta = document.querySelector('textarea[wire\\:model\\.live="edit.editingBody"]');
                if (ta) {
                    ta.focus();
                    ta.selectionStart = ta.selectionEnd = ta.value.length;
                }
            });
        },

        cancelEdit() {
            this.editingMsg = null;
        },

        async saveEdit() {
            if (!this.editingMsg?.id) return;

            try {
                await this.$wire.saveEdit(this.editingMsg.id);
                this.cancelEdit();
            } catch (error) {
                this.toast('خطا در ذخیره ویرایش پیام.', 'error');
            }
        },

        confirmDelete(id) {
            if (!id) return;
            this.replyingTo = null;
            this.editingMsg = null;
            this.deletingId = id;
        },

        cancelDelete() {
            this.deletingId = null;
        },

        async deleteMessage() {
            if (!this.deletingId) return;

            try {
                await this.$wire.deleteMessage(this.deletingId);
                this.cancelDelete();
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

            if (body.length > 2000) {
                this.toast('متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.', 'warning');
                return;
            }

            this.sending = true;
            try {
                await this.$wire.send(this.replyingTo?.id ?? null);
                this.replyingTo = null;
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
