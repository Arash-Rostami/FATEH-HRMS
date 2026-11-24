import { feedEmojis } from "../stores/emoji.js";

const EMOJIS = Object.freeze(feedEmojis);
const PANEL_W = 256;
const PANEL_H = 260;
const EDGE = 8;
const MOBILE_MAX = 640;
const BOLD = '**';

const grow = (el) => {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
};

const commit = (ta, start, end) => {
    ta.focus();
    ta.setSelectionRange(start, end);
    ta.dispatchEvent(new Event('input'));
    grow(ta);
};

export default (feedId) => ({
    feedId,
    showEmoji: false,
    fullscreen: false,
    panelStyle: '',
    emojis: EMOJIS,
    sending: false,
    replyingTo: null,
    sendingReply: null,
    openThreads: [],

    openReply(id) {
        this.replyingTo = id;
        this.$nextTick(() => this.$root.querySelector(`[data-reply-input="${id}"]`)?.focus());
    },

    closeReply() {
        this.replyingTo = null;
    },

    toggleReply(id) {
        if (this.replyingTo === id) this.closeReply();
        else this.openReply(id);
    },

    isThreadOpen(id) {
        return this.openThreads.includes(id);
    },

    toggleThread(id) {
        this.openThreads = this.isThreadOpen(id) ? this.openThreads.filter((t) => t !== id) : [...this.openThreads, id];
    },

    onReplyEnter(e, feedId, commentId) {
        if (e.shiftKey || e.isComposing) return;
        e.preventDefault();
        this.sendReply(feedId, commentId);
    },

    growOn(value, el) {
        if (el) this.$nextTick(() => grow(el));
    },

    async sendReply(feedId, commentId) {
        if (this.sendingReply) return;
        this.sendingReply = commentId;
        try {
            await this.$wire.addComment(feedId, commentId);
            if (this.$wire.get('replyComments.' + commentId) == null) {
                this.closeReply();
                if (!this.isThreadOpen(commentId)) this.toggleThread(commentId);
            }
        } catch {} finally {
            this.sendingReply = null;
        }
    },

    init() {
        this.$nextTick(() => {
            if (this.$refs.commentInput) this.$refs.commentInput.style.height = 'auto';
        });
    },

    toggleEmoji() {
        if (!this.showEmoji) {
            const r = this.$refs.emojiBtn.getBoundingClientRect();
            const vw = window.innerWidth;
            const top = r.top > PANEL_H + EDGE ? r.top - PANEL_H - EDGE : r.bottom + EDGE;
            const left = vw < MOBILE_MAX
                ? Math.max(EDGE, (vw - PANEL_W) / 2)
                : Math.max(EDGE, Math.min(r.left, vw - PANEL_W - EDGE));
            this.panelStyle = `position:fixed;z-index:9999;left:${left}px;top:${top}px;width:${PANEL_W}px;`;
        }
        this.showEmoji = !this.showEmoji;
    },

    insertEmoji(emoji) {
        const ta = this.$refs.commentInput;
        const start = ta.selectionStart ?? ta.value.length;
        const end = ta.selectionEnd ?? ta.value.length;
        ta.setRangeText(emoji, start, end, 'end');
        const pos = start + emoji.length;
        commit(ta, pos, pos);
    },

    toggleBold() {
        const ta = this.$refs.commentInput;
        if (!ta) return;
        const s = ta.selectionStart ?? 0;
        const e = ta.selectionEnd ?? 0;
        const v = ta.value;
        if (s >= 2 && v.startsWith(BOLD, s - 2) && v.startsWith(BOLD, e)) {
            ta.value = v.slice(0, s - 2) + v.slice(s, e) + v.slice(e + 2);
            commit(ta, s - 2, e - 2);
        } else {
            ta.value = v.slice(0, s) + BOLD + v.slice(s, e) + BOLD + v.slice(e);
            commit(ta, s + 2, e + 2);
        }
    },

    onEnter(e) {
        if (!e.shiftKey) {
            if (this.sending) return;
            this.sending = true;
            this.$wire.addComment(this.feedId).catch(() => {}).finally(() => { this.sending = false; });
            return;
        }
        const el = e.target;
        el.setRangeText('\n', el.selectionStart, el.selectionEnd, 'end');
        el.dispatchEvent(new Event('input'));
    },

    autoGrow: grow,
});
