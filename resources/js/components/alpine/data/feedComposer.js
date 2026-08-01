import { feedEmojis } from "../stores/emoji.js";

export default (feedId) => ({
    feedId,
    showEmoji: false,
    fullscreen: false,
    panelStyle: '',
    emojis: feedEmojis,

    init() {
        // start the textarea at its natural single-line height (refs are ready next tick)
        this.$nextTick(() => {
            if (this.$refs.commentInput) this.$refs.commentInput.style.height = 'auto';
        });
    },

    toggleEmoji() {
        if (!this.showEmoji) {
            const r = this.$refs.emojiBtn.getBoundingClientRect();
            const panelW = 256;
            const panelH = 260;
            const vw = window.innerWidth;
            const above = r.top > panelH + 8;
            const topVal = above ? r.top - panelH - 8 : r.bottom + 8;

            let leftVal;
            if (vw < 640) {
                leftVal = Math.max(8, (vw - panelW) / 2);
            } else {
                leftVal = Math.min(r.left, vw - panelW - 8);
                leftVal = Math.max(8, leftVal);
            }

            this.panelStyle = `position:fixed;z-index:9999;left:${leftVal}px;top:${topVal}px;width:${panelW}px;`;
        }
        this.showEmoji = !this.showEmoji;
    },

    insertEmoji(emoji) {
        const ta = this.$refs.commentInput;
        const start = ta.selectionStart ?? ta.value.length;
        const end = ta.selectionEnd ?? ta.value.length;
        ta.value = ta.value.slice(0, start) + emoji + ta.value.slice(end);
        const pos = start + emoji.length;
        ta.focus();
        ta.setSelectionRange(pos, pos);
        ta.dispatchEvent(new Event('input'));
        this.autoGrow(ta);
    },

    toggleBold() {
        const ta = this.$refs.commentInput;
        if (!ta) return;
        const s = ta.selectionStart ?? 0;
        const e = ta.selectionEnd ?? 0;
        const val = ta.value;
        const before = val.slice(Math.max(0, s - 2), s);
        const after = val.slice(e, e + 2);
        const wrapped = before === '**' && after === '**';
        let next, sel;
        if (wrapped) {
            next = val.slice(0, s - 2) + val.slice(s, e) + val.slice(e + 2);
            sel = [s - 2, e - 2];
        } else if (s === e) {
            const marker = '**';
            next = val.slice(0, s) + marker + marker + val.slice(e);
            sel = [s + 2, s + 2];
        } else {
            next = val.slice(0, s) + '**' + val.slice(s, e) + '**' + val.slice(e);
            sel = [s + 2, e + 2];
        }
        ta.value = next;
        ta.focus();
        ta.setSelectionRange(sel[0], sel[1]);
        ta.dispatchEvent(new Event('input'));
        this.autoGrow(ta);
    },

    // Enter submits the comment; Shift+Enter inserts a newline (mirrors the original inline handler).
    onEnter(e) {
        if (!e.shiftKey) {
            this.$wire.addComment(this.feedId);
            return;
        }
        const el = e.target;
        const s = el.selectionStart;
        el.value = el.value.slice(0, s) + '\n' + el.value.slice(el.selectionEnd);
        el.selectionStart = el.selectionEnd = s + 1;
        el.dispatchEvent(new Event('input'));
    },

    autoGrow(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    },
});
