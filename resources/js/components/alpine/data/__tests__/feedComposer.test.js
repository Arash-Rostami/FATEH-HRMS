import { describe, expect, test, vi } from 'vitest'
import feedComposer from '../feedComposer.js'

function mockTextarea(value, start, end) {
    return {
        value,
        selectionStart: start,
        selectionEnd: end,
        focus: vi.fn(),
        setSelectionRange: vi.fn(),
        dispatchEvent: vi.fn(),
        style: {},
        setRangeText(text, s, e) {
            this.value = this.value.slice(0, s) + text + this.value.slice(e);
            this.selectionStart = this.selectionEnd = s + text.length;
        },
    };
}

describe('feedComposer.insertEmoji', () => {
    test('splices the emoji at the caret, moves the caret past it, refocuses, and dispatches input', () => {
        const c = feedComposer(1);
        const ta = mockTextarea('hello world', 5, 5);
        c.$refs = { commentInput: ta };

        c.insertEmoji('😀');

        expect(ta.value).toBe('hello😀 world');
        expect(ta.setSelectionRange).toHaveBeenCalledWith(7, 7);
        expect(ta.focus).toHaveBeenCalled();
        expect(ta.dispatchEvent).toHaveBeenCalled();
    });

    test('replaces an active selection rather than inserting at a bare caret', () => {
        const c = feedComposer(1);
        const ta = mockTextarea('hello world', 0, 5);
        c.$refs = { commentInput: ta };

        c.insertEmoji('!');

        expect(ta.value).toBe('! world');
    });

    test('falls back to value.length as the caret position when selectionStart/End are null', () => {
        const c = feedComposer(1);
        const ta = mockTextarea('abc', null, null);
        c.$refs = { commentInput: ta };

        c.insertEmoji('!');

        expect(ta.value).toBe('abc!');
    });
});

describe('feedComposer.toggleBold', () => {
    test('wraps an empty caret position with ** ** and places the caret between the markers', () => {
        const c = feedComposer(1);
        const ta = mockTextarea('hello world', 0, 0);
        c.$refs = { commentInput: ta };

        c.toggleBold();

        expect(ta.value).toBe('****hello world');
        expect(ta.setSelectionRange).toHaveBeenCalledWith(2, 2);
    });

    test('wraps an active selection with ** ** around it', () => {
        const c = feedComposer(1);
        const ta = mockTextarea('hello world', 0, 5);
        c.$refs = { commentInput: ta };

        c.toggleBold();

        expect(ta.value).toBe('**hello** world');
        expect(ta.setSelectionRange).toHaveBeenCalledWith(2, 7);
    });

    test('un-wraps a selection that is already surrounded by **', () => {
        const c = feedComposer(1);
        const ta = mockTextarea('**hello** world', 2, 7);
        c.$refs = { commentInput: ta };

        c.toggleBold();

        expect(ta.value).toBe('hello world');
        expect(ta.setSelectionRange).toHaveBeenCalledWith(0, 5);
    });

    test('is a no-op when the commentInput ref is absent', () => {
        const c = feedComposer(1);
        c.$refs = {};
        expect(() => c.toggleBold()).not.toThrow();
    });
});

describe('feedComposer.onEnter', () => {
    test('plain Enter submits the comment via $wire.addComment(feedId)', () => {
        const c = feedComposer(42);
        c.$wire = { addComment: vi.fn(() => Promise.resolve()) };
        const ta = mockTextarea('hi', 2, 2);

        c.onEnter({ shiftKey: false, target: ta });

        expect(c.$wire.addComment).toHaveBeenCalledWith(42);
        expect(ta.value).toBe('hi');
    });

    test('plain Enter sets sending synchronously while the addComment promise is in flight', () => {
        const c = feedComposer(42);
        c.$wire = { addComment: vi.fn(() => Promise.resolve()) };
        const ta = mockTextarea('hi', 2, 2);

        c.onEnter({ shiftKey: false, target: ta });

        expect(c.$wire.addComment).toHaveBeenCalledTimes(1);
        expect(c.sending).toBe(true);
    });

    test('a second plain Enter while sending is true does not call addComment again', () => {
        const c = feedComposer(42);
        c.$wire = { addComment: vi.fn(() => Promise.resolve()) };
        const ta = mockTextarea('hi', 2, 2);

        c.onEnter({ shiftKey: false, target: ta });
        c.onEnter({ shiftKey: false, target: ta });

        expect(c.$wire.addComment).toHaveBeenCalledTimes(1);
    });

    test('after addComment settles, sending resets and a second plain Enter submits again', async () => {
        const c = feedComposer(42);
        c.$wire = { addComment: vi.fn(() => Promise.resolve()) };
        const ta = mockTextarea('hi', 2, 2);

        c.onEnter({ shiftKey: false, target: ta });
        await new Promise((r) => setTimeout(r, 0));

        expect(c.sending).toBe(false);
        c.onEnter({ shiftKey: false, target: ta });
        expect(c.$wire.addComment).toHaveBeenCalledTimes(2);
    });

    test('Shift+Enter inserts a newline at the caret instead of submitting', () => {
        const c = feedComposer(1);
        c.$wire = { addComment: vi.fn(() => Promise.resolve()) };
        const ta = mockTextarea('ab', 1, 1);

        c.onEnter({ shiftKey: true, target: ta });

        expect(ta.value).toBe('a\nb');
        expect(ta.selectionStart).toBe(2);
        expect(ta.selectionEnd).toBe(2);
        expect(c.$wire.addComment).not.toHaveBeenCalled();
    });
});

describe('feedComposer — reply box and thread state', () => {
    function withDom() {
        const c = feedComposer(7);
        const input = { focus: vi.fn() };
        c.$root = { querySelector: vi.fn(() => input) };
        c.$nextTick = (fn) => fn();
        return { c, input };
    }

    test('openReply selects the comment and focuses its reply input', () => {
        const { c, input } = withDom();

        c.openReply(5);

        expect(c.replyingTo).toBe(5);
        expect(c.$root.querySelector).toHaveBeenCalledWith('[data-reply-input="5"]');
        expect(input.focus).toHaveBeenCalled();
    });

    test('toggleReply opens, then closes on the second call, and switching comments moves the box', () => {
        const { c } = withDom();

        c.toggleReply(5);
        expect(c.replyingTo).toBe(5);
        c.toggleReply(9);
        expect(c.replyingTo).toBe(9);
        c.toggleReply(9);
        expect(c.replyingTo).toBeNull();
    });

    test('toggleThread flips a single comment thread without touching the others', () => {
        const { c } = withDom();

        c.toggleThread(1);
        c.toggleThread(2);
        c.toggleThread(1);

        expect(c.isThreadOpen(1)).toBe(false);
        expect(c.isThreadOpen(2)).toBe(true);
    });

    test('a successful sendReply closes the box and expands that thread so the new reply is visible', async () => {
        const { c } = withDom();
        c.replyingTo = 5;
        c.$wire = { addComment: vi.fn(() => Promise.resolve()), get: vi.fn(() => null) };

        await c.sendReply(7, 5);

        expect(c.$wire.addComment).toHaveBeenCalledWith(7, 5);
        expect(c.replyingTo).toBeNull();
        expect(c.isThreadOpen(5)).toBe(true);
        expect(c.sendingReply).toBeNull();
    });

    test('a rejected reply (text kept by the server) leaves the box open and the thread closed', async () => {
        const { c } = withDom();
        c.replyingTo = 5;
        c.$wire = { addComment: vi.fn(() => Promise.resolve()), get: vi.fn(() => 'still here') };

        await c.sendReply(7, 5);

        expect(c.replyingTo).toBe(5);
        expect(c.isThreadOpen(5)).toBe(false);
    });

    test('plain Enter in a reply box prevents the newline and sends that reply', () => {
        const { c } = withDom();
        c.$wire = { addComment: vi.fn(() => Promise.resolve()), get: vi.fn(() => 'x') };
        const e = { shiftKey: false, isComposing: false, preventDefault: vi.fn() };

        c.onReplyEnter(e, 7, 5);

        expect(e.preventDefault).toHaveBeenCalled();
        expect(c.$wire.addComment).toHaveBeenCalledWith(7, 5);
    });

    test('Shift+Enter and IME composition Enter in a reply box do not send', () => {
        const { c } = withDom();
        c.$wire = { addComment: vi.fn(() => Promise.resolve()), get: vi.fn(() => 'x') };
        const shift = { shiftKey: true, isComposing: false, preventDefault: vi.fn() };
        const ime = { shiftKey: false, isComposing: true, preventDefault: vi.fn() };

        c.onReplyEnter(shift, 7, 5);
        c.onReplyEnter(ime, 7, 5);

        expect(c.$wire.addComment).not.toHaveBeenCalled();
        expect(shift.preventDefault).not.toHaveBeenCalled();
    });

    test('growOn resizes the given textarea on the next tick and ignores a missing element', () => {
        const { c } = withDom();
        const el = { style: {}, scrollHeight: 90 };

        c.growOn('v', el);
        c.growOn('v', null);

        expect(el.style.height).toBe('90px');
    });

    test('sendReply ignores a second call while one is in flight and recovers from a thrown request', async () => {
        const { c } = withDom();
        c.$wire = { addComment: vi.fn(() => Promise.reject(new Error('x'))), get: vi.fn(() => null) };

        const first = c.sendReply(7, 5);
        await c.sendReply(7, 5);
        await first;

        expect(c.$wire.addComment).toHaveBeenCalledTimes(1);
        expect(c.sendingReply).toBeNull();
    });
});

describe('feedComposer.toggleEmoji — panel positioning', () => {
    function withButtonRect(rect) {
        const c = feedComposer(1);
        c.$refs = { emojiBtn: { getBoundingClientRect: () => rect } };
        return c;
    }

    test('opens upward and clamps left to the button position on a wide viewport with enough room above', () => {
        Object.defineProperty(window, 'innerWidth', { value: 1000, configurable: true });
        const c = withButtonRect({ top: 400, left: 500, bottom: 450 });

        c.toggleEmoji();

        expect(c.showEmoji).toBe(true);
        expect(c.panelStyle).toBe('position:fixed;z-index:9999;left:500px;top:132px;width:256px;');
    });

    test('opens downward and centers horizontally on a narrow (<640px) viewport with too little room above', () => {
        Object.defineProperty(window, 'innerWidth', { value: 375, configurable: true });
        const c = withButtonRect({ top: 100, left: 20, bottom: 140 });

        c.toggleEmoji();

        expect(c.panelStyle).toBe('position:fixed;z-index:9999;left:59.5px;top:148px;width:256px;');
    });

    test('a second call closes the panel without recomputing panelStyle', () => {
        Object.defineProperty(window, 'innerWidth', { value: 1000, configurable: true });
        const c = withButtonRect({ top: 400, left: 500, bottom: 450 });

        c.toggleEmoji();
        const style = c.panelStyle;
        c.toggleEmoji();

        expect(c.showEmoji).toBe(false);
        expect(c.panelStyle).toBe(style);
    });
});
