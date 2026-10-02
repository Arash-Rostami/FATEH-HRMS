import { describe, expect, test, vi } from 'vitest'
import feedComposer from '../feedComposer.js'

function mockTextarea(value, start, end) {
    return { value, selectionStart: start, selectionEnd: end, focus: vi.fn(), setSelectionRange: vi.fn(), dispatchEvent: vi.fn(), style: {} };
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
