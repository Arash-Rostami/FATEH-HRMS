import settings from "./settings.js";

import maximizeMixin from "../mixins/maximize.js";
import clipboardMixin from "../mixins/clipboard.js";
import pasteImageMixin from "../mixins/pasteImage.js";
import chatBase from "../mixins/chatBase.js";

const SCOPE = 'contact';
const POLL_INTERVAL_MS = 10000;
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

export default function contact() {
    return {
        ...maximizeMixin(),
        ...clipboardMixin(),
        ...pasteImageMixin(),
        ...chatBase(),
        bgOption: 'a',
        editingMsg: null,
        quoteChip: {visible: false, x: 0, y: 0, id: null, sender: '', snippet: ''},
        _onSelectionChange: null,
        _onKeyDown: null,
        _unreadObserver: null,
        _scrollRaf: null,

        init() {
            this.initPattern();
            this.syncPushNotify();
            this._unreadObserver = new MutationObserver(() => this.syncPushNotify());
            this._unreadObserver.observe(document.body, {subtree: true, attributes: true, attributeFilter: [TOTAL_UNREAD_ATTR]});
            const saved = localStorage.getItem('chat-settings');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    this.isHighlighted = data.isHighlighted;
                    this.backgroundPattern = data.backgroundPattern;
                } catch (e) {
                }
            }

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
                                this.$wire.$island('messages').loadMoreMessages()
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
                if (INPUT_TAGS.has(tag) || el?.isContentEditable) return;
                if (this.searchMessages) return;
                e.preventDefault();
                this.openMessageSearch();
            };
            document.addEventListener('keydown', this._onKeyDown);

            this.$wire.on('message-sent', () => {
                this.scrollToBottom(true);
                this.sending = false;
                this.$store.sound?.playOutgoing(this.$wire.activeUserId, SCOPE);
                setTimeout(() => this.scrollToBottom(true), POST_SEND_SCROLL_DELAY_MS);
            });

            this.$wire.on('message-error', () => this.$nextTick(() => {
                this.sending = false;
            }));

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

            const focusMsg = parseInt(new URLSearchParams(window.location.search).get('focus_msg'), 10) || 0;
            if (this.$wire.activeUserId && focusMsg <= 0) {
                this.$nextTick(() => {
                    this.scrollToBottom(false);
                    if (window.innerWidth < MOBILE_BREAKPOINT) document.getElementById(MSG_TEXTAREA_ID)?.focus();
                });
            }
        },

        destroy() {
            this.stopPolling();
            if (this._onVisibility) document.removeEventListener('visibilitychange', this._onVisibility);
            const vp = document.getElementById(MSG_VIEWPORT_ID);
            if (vp && this._onScroll) vp.removeEventListener('scroll', this._onScroll);
            if (this._scrollRaf) cancelAnimationFrame(this._scrollRaf);
            if (this._selRaf) cancelAnimationFrame(this._selRaf);
            if (this._onSelectionChange) document.removeEventListener('selectionchange', this._onSelectionChange);
            if (this._onKeyDown) document.removeEventListener('keydown', this._onKeyDown);
            if (this._unreadObserver) this._unreadObserver.disconnect();
        },

        startPolling() {
            if (this._timer) return;
            this._timer = setInterval(() => {
                this.$wire.$island('sidebar').refreshUnread().catch(() => {});
                if (this.$wire.activeUserId) {
                    this.$wire.$island('messages').refreshActive().catch(() => {});
                }
            }, POLL_INTERVAL_MS);
        },

        syncPushNotify() {
            const el = document.querySelector(`[${TOTAL_UNREAD_ATTR}]`);
            const now = parseInt(el?.dataset.totalUnread) || 0;
            if (this._lastUnread !== undefined && now > this._lastUnread) {
                this.$store.push.notify('پیام جدید', 'یک گفتگو پیام جدید دارد', SCOPE);
            }
            this._lastUnread = now;
        },

        initPattern() {
            try {
                return settings().initPattern();
            } catch (error) {
                console.error(error);
            }
        },

        focusSearch() {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        },

        closeOverlays() {
            this.showInfo = false;
            this.searchMessages = false;
            this.emojiOpen = false;
            this.cancelReply();
            this.cancelEdit();
            this.cancelDelete();
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

        selectContact(id) {
            if (!id) return;
            this.replyingTo = null;
            this.editingMsg = null;
            this.deletingId = null;
            this.openActionsId = null;
            this.searchMessages = false;
            this.$wire.cancelReply();
            this.$wire.$island('messages').selectContact(id)
                .then(() => this.$wire.$island('sidebar').refreshUnread())
                .then(() => this.$nextTick(() => this.scrollToBottom(true)))
                .then(() => { if (window.innerWidth < MOBILE_BREAKPOINT) this.$nextTick(() => { document.getElementById(MSG_TEXTAREA_ID)?.focus(); }); });
        },

        resetUI() {
            this.replyingTo = null;
            this.editingMsg = null;
            this.deletingId = null;
        },

        startReply(id, senderName, body) {
            if (!id) return;
            this.editingMsg = null;
            this.deletingId = null;
            this.replyingTo = {id, sender: {name: senderName || 'Unknown'}, body: body || ''};
            this.quoteChip.visible = false;
            this.openActionsId = null;
            this.$wire.replyTo(id);
            this.$wire.cancelEdit();
            this.$nextTick(() => document.getElementById(MSG_TEXTAREA_ID)?.focus());
        },

        _updateQuoteChip() {
            const sel = window.getSelection();
            if (!sel || sel.isCollapsed || sel.rangeCount === 0) {
                this.quoteChip.visible = false;
                return;
            }
            const anchor = sel.anchorNode;
            if (!anchor) { this.quoteChip.visible = false; return; }
            const vp = document.getElementById(MSG_VIEWPORT_ID);
            if (!vp || !vp.contains(anchor)) { this.quoteChip.visible = false; return; }
            let node = anchor.nodeType === 3 ? anchor.parentElement : anchor;
            if (node?.closest('textarea, input, [contenteditable="true"], [contenteditable=""]')) {
                this.quoteChip.visible = false;
                return;
            }
            const row = node?.closest(`[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`);
            if (!row) { this.quoteChip.visible = false; return; }
            const text = sel.toString().trim();
            if (!text) { this.quoteChip.visible = false; return; }
            const id = parseInt(row.getAttribute('data-rf').split('-').pop(), 10);
            if (!id) { this.quoteChip.visible = false; return; }
            const senderEl = row.querySelector('[data-sender]');
            const rect = sel.getRangeAt(0).getBoundingClientRect();
            this.quoteChip = {
                visible: true,
                x: rect.left,
                y: rect.top,
                id,
                sender: senderEl?.getAttribute('data-sender') || '',
                snippet: text.slice(0, 120),
            };
        },

        startEdit(id, body) {
            if (!id) return;
            this.replyingTo = null;
            this.deletingId = null;
            this.editingMsg = {id, body: body || ''};
            this.$wire.cancelReply();
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
            this.$wire.cancelEdit();
        },

        async saveEdit(id) {
            if (!id || id !== this.editingMsg?.id) return;

            try {
                await this.$wire.$island('messages').saveEdit(id);
                if (this.editingMsg?.id === id) this.cancelEdit();
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
                this.toast('متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.', 'warning');
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
