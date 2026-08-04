import {emojis} from "../stores/emoji.js";

const segmenter = new Intl.Segmenter();
const emojiSet = new Set(emojis.flatMap(c => c.items));
const HTML_TAG_RE = /<[^>]*>/g;
const WS_ONLY_RE = /^\s+$/;

export default function chatBase() {
    return {
        showScrollFab: false,
        showInfo: false,
        searchMessages: false,
        showUndo: false,
        undoTimeout: null,
        sending: false,
        openActionsId: null,
        replyingTo: null,
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
        _selRaf: null,

        stopPolling() {
            clearInterval(this._timer);
            this._timer = null;
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
            const stripped = text?.replace(HTML_TAG_RE, '').trim() ?? '';
            if (!stripped) return false;
            for (const {segment} of segmenter.segment(stripped)) {
                if (!emojiSet.has(segment) && !WS_ONLY_RE.test(segment)) return false;
            }
            return true;
        },

        toggleHighlight() {
            this.isHighlighted = !this.isHighlighted;
            this.backgroundPattern = this.backgroundPattern === 'on' ? 'off' : 'on';
            localStorage.setItem('chat-settings', JSON.stringify({
                isHighlighted: this.isHighlighted,
                backgroundPattern: this.backgroundPattern
            }));
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
            this.copyText(text, 'پیام کپی شد', 'info');
        },

        useQuoteChip() {
            if (!this.quoteChip.visible || !this.quoteChip.id) return;
            this.startReply(this.quoteChip.id, this.quoteChip.sender, this.quoteChip.snippet);
        },

        cancelReply() {
            this.replyingTo = null;
            this.$wire.cancelReply();
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
    };
}
