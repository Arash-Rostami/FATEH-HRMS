import settings from "./settings.js";
import {emojis} from "../stores/emoji.js";

import maximizeMixin from "../mixins/maximize.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));

export default function contact() {
    return {
        ...maximizeMixin(),
        bgOption: 'a',
        searchMessages: false,
        messageSearchFullscreen: false,
        messageSearchValue: '',
        showScrollFab: false,
        showInfo: false,
        showUndo: false,
        undoTimeout: null,
        sending: false,
        replyingTo: null,
        editingMsg: null,
        deletingId: null,
        emojiOpen: false,
        activeCat: 0,
        isHighlighted: false,
        backgroundPattern: 'off',
        emojis: emojis,
        quoteChip: {visible: false, x: 0, y: 0, id: null, sender: '', snippet: ''},
        openActionsId: null,
        _loadingOlder: false,
        _onScroll: null,
        _onSelectionChange: null,
        _onKeyDown: null,
        _selRaf: null,

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
                                this.$wire.loadMoreMessages()
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

            this._onSelectionChange = () => {
                if (this._selRaf) return;
                this._selRaf = requestAnimationFrame(() => {
                    this._selRaf = null;
                    this._updateQuoteChip();
                });
            };
            document.addEventListener('selectionchange', this._onSelectionChange);

            this._onKeyDown = (e) => {
                if (e.key !== '/' || e.isComposing) return;
                const el = document.activeElement;
                const tag = el?.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el?.isContentEditable) return;
                if (this.searchMessages) return;
                e.preventDefault();
                this.openMessageSearch();
            };
            document.addEventListener('keydown', this._onKeyDown);

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

        destroy() {
            const vp = document.getElementById('msg-viewport');
            if (vp && this._onScroll) vp.removeEventListener('scroll', this._onScroll);
            if (this._selRaf) cancelAnimationFrame(this._selRaf);
            if (this._onSelectionChange) document.removeEventListener('selectionchange', this._onSelectionChange);
            if (this._onKeyDown) document.removeEventListener('keydown', this._onKeyDown);
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
            this.searchMessages = false;
            this.emojiOpen = false;
            this.cancelEdit();
            this.cancelDelete();
            this.replyingTo = null;
            this.quoteChip.visible = false;
            this.openActionsId = null;
        },

        toggleActions(id, e) {
            if (window.getSelection().toString().trim() !== '') return;
            if (e.target.closest('a,button,[role="button"],[contenteditable],input,textarea')) return;
            this.openActionsId = (this.openActionsId === id ? null : id);
        },

        scrollToMessage(id) {
            if (!id) return;
            const el = document.querySelector(`[data-rf="message-${id}"]`);
            if (el) {
                document.querySelectorAll('.record-focus-flash').forEach(n => n.classList.remove('record-focus-flash'));
                el.style.animation = 'none';
                el.scrollIntoView({behavior: 'smooth', block: 'center'});
                el.classList.add('record-focus-flash');
                return;
            }
            this.$wire.focusMessage(id).catch(() => {});
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
            this.$wire.focusMessage(id).catch(() => {});
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
            this.quoteChip.visible = false;
            this.openActionsId = null;
            this.$nextTick(() => document.getElementById('msg-ta')?.focus());
        },

        useQuoteChip() {
            if (!this.quoteChip.visible || !this.quoteChip.id) return;
            this.startReply(this.quoteChip.id, this.quoteChip.sender, this.quoteChip.snippet);
        },

        _updateQuoteChip() {
            const sel = window.getSelection();
            if (!sel || sel.isCollapsed || sel.rangeCount === 0) {
                this.quoteChip.visible = false;
                return;
            }
            const anchor = sel.anchorNode;
            if (!anchor) { this.quoteChip.visible = false; return; }
            const vp = document.getElementById('msg-viewport');
            if (!vp || !vp.contains(anchor)) { this.quoteChip.visible = false; return; }
            let node = anchor.nodeType === 3 ? anchor.parentElement : anchor;
            if (node?.closest('textarea, input, [contenteditable="true"], [contenteditable=""]')) {
                this.quoteChip.visible = false;
                return;
            }
            const row = node?.closest('[data-rf^="message-"]');
            if (!row) { this.quoteChip.visible = false; return; }
            const text = sel.toString().trim();
            if (!text) { this.quoteChip.visible = false; return; }
            const id = parseInt(row.getAttribute('data-rf').split('-').pop(), 10);
            if (!id) { this.quoteChip.visible = false; return; }
            const senderEl = row.querySelector('[data-sender]');
            const rect = sel.getRangeAt(0).getBoundingClientRect();
            this.quoteChip = {
                visible: true,
                x: rect.left + (rect.width / 2),
                y: rect.top,
                id,
                sender: senderEl?.getAttribute('data-sender') || '',
                snippet: text.slice(0, 120),
            };
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