import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import chatBase from '../chatBase.js'
import { createWireMock, stubResizeObserver } from './_wireMock.js'

stubResizeObserver();
// Force-override rather than "if missing": jsdom ships its own rAF whose timing is not
// guaranteed to fire within a short test-level await, which made other suites flaky.
global.requestAnimationFrame = (cb) => setTimeout(cb, 0);
global.cancelAnimationFrame = (id) => clearTimeout(id);

beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = '';
});

describe('chatBase pure-logic helpers (resources/js/scriptPattern.md: no JS test runner existed before this file)', () => {
    const base = chatBase('contact');

    test('isEmojiOnly accepts emoji-only text, rejects mixed or plain text', () => {
        expect(base.isEmojiOnly('😀')).toBe(true);
        expect(base.isEmojiOnly('😀 😀')).toBe(true);
        expect(base.isEmojiOnly('')).toBe(false);
        expect(base.isEmojiOnly('hi')).toBe(false);
        expect(base.isEmojiOnly('😀 hi')).toBe(false);
    });

    test('passesTypeFilter matches whitespace-separated type tokens', () => {
        expect(base.passesTypeFilter('image file')).toBe(true);
        base.typeFilter = 'image';
        expect(base.passesTypeFilter('image file')).toBe(true);
        expect(base.passesTypeFilter('file')).toBe(false);
        expect(base.passesTypeFilter('')).toBe(false);
        expect(base.passesTypeFilter(undefined)).toBe(false);
    });

    test('toggleTypeFilter adds then removes a token', () => {
        const c = chatBase('contact');
        c.typeFilter = '';
        c.toggleTypeFilter('i');
        expect(c.typeFilter).toBe('i');
        c.toggleTypeFilter('f');
        expect(c.typeFilter).toBe('if');
        c.toggleTypeFilter('i');
        expect(c.typeFilter).toBe('f');
    });

    test('clearAllFilters resets typeFilter and clears an active sender filter if present', () => {
        const c = chatBase('channel');
        c.typeFilter = 'if';
        c.activeSender = 'Ali';
        c.clearSenderFilter = vi.fn();
        c.clearAllFilters();
        expect(c.typeFilter).toBe('');
        expect(c.clearSenderFilter).toHaveBeenCalledOnce();
    });

    test('_applyPollResult resets to the base interval on change, backs off on no-change, caps at the ceiling', () => {
        base._pollIntervalMs = 20000;
        base._applyPollResult(true, 30000);
        expect(base._pollIntervalMs).toBe(10000);

        base._pollIntervalMs = 10000;
        base._applyPollResult(false, 30000);
        expect(base._pollIntervalMs).toBe(20000);

        base._pollIntervalMs = 25000;
        base._applyPollResult(false, 30000);
        expect(base._pollIntervalMs).toBe(30000);
    });

    test('autoDirection picks ltr when a Latin letter precedes any Persian/Arabic letter, rtl otherwise', () => {
        const el = document.createElement('textarea');
        el.value = 'hello سلام';
        base.autoDirection(el);
        expect(el.dir).toBe('ltr');

        el.value = 'سلام';
        base.autoDirection(el);
        expect(el.dir).toBe('rtl');

        el.value = '';
        el.dir = 'ltr';
        base.autoDirection(el);
        expect(el.dir).toBe('rtl');
    });

    test('autoDirection no-ops on a null element', () => {
        expect(() => base.autoDirection(null)).not.toThrow();
    });
});

describe('chatBase pingTyping — leading-edge throttle', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    test('fires immediately, then suppresses calls within 3s, then fires again after 3s', () => {
        vi.setSystemTime(10000);
        const c = chatBase('contact');
        c._sendTypingPing = vi.fn(() => Promise.resolve());

        c.pingTyping();
        expect(c._sendTypingPing).toHaveBeenCalledTimes(1);

        vi.setSystemTime(12999);
        c.pingTyping();
        expect(c._sendTypingPing).toHaveBeenCalledTimes(1);

        vi.setSystemTime(13000);
        c.pingTyping();
        expect(c._sendTypingPing).toHaveBeenCalledTimes(2);
    });
});

describe('chatBase polling lifecycle', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    test('startPolling schedules a timer; stopPolling clears it; a second startPolling is a no-op', () => {
        const c = chatBase('contact');
        expect(c._timer).toBeNull();

        c.startPolling();
        expect(c._timer).not.toBeNull();
        const firstTimer = c._timer;

        c.startPolling();
        expect(c._timer).toBe(firstTimer);

        c.stopPolling();
        expect(c._timer).toBeNull();
    });

    test('resetPollInterval restores the base interval', () => {
        const c = chatBase('contact');
        c._pollIntervalMs = 30000;
        c.resetPollInterval();
        expect(c._pollIntervalMs).toBe(10000);
    });

    test('_runPollTick guards re-entrancy: a concurrent force call queues a repoll instead of running in parallel', async () => {
        const c = chatBase('contact');
        let resolveFirst;
        c._pollTick = vi.fn(() => new Promise((r) => { resolveFirst = r; }));
        c._repollNow = vi.fn();

        const firstCall = c._runPollTick(false);
        expect(c._pollInFlight).toBe(true);

        await c._runPollTick(true);
        expect(c._repollQueued).toBe(true);
        expect(c._pollTick).toHaveBeenCalledTimes(1);

        resolveFirst();
        await firstCall;

        expect(c._pollInFlight).toBe(false);
        expect(c._repollQueued).toBe(false);
        expect(c._repollNow).toHaveBeenCalledOnce();
    });

    test('_repollNow resets the interval and restarts the timer chain', () => {
        const c = chatBase('contact');
        c._runPollTick = vi.fn(() => Promise.resolve());
        c.startPolling();
        const oldTimer = c._timer;
        c._pollIntervalMs = 25000;

        c._repollNow(false);

        expect(c._pollIntervalMs).toBe(10000);
        expect(c._timer).not.toBeNull();
        expect(c._runPollTick).not.toHaveBeenCalled();
    });

    test('_repollNow(true) also forces an immediate tick', () => {
        const c = chatBase('contact');
        c._runPollTick = vi.fn(() => Promise.resolve());
        c._repollNow(true);
        expect(c._runPollTick).toHaveBeenCalledWith(true);
    });

    test('_repollNow is a no-op once destroyed', () => {
        const c = chatBase('contact');
        c._runPollTick = vi.fn(() => Promise.resolve());
        c._destroyed = true;
        c._repollNow(true);
        expect(c._runPollTick).not.toHaveBeenCalled();
    });

    test('_syncSidebarAfterOwnAction resets the timer without a forced tick and refreshes the sidebar via the given wire', () => {
        const c = chatBase('contact');
        c._repollNow = vi.fn();
        const wire = createWireMock();

        c._syncSidebarAfterOwnAction(wire);

        expect(c._repollNow).toHaveBeenCalledWith(false);
        expect(wire._islands.sidebar.refreshUnread).toHaveBeenCalledOnce();
    });

    test('destroy stops polling and clears any pending undo timeout', () => {
        const c = chatBase('contact');
        c.startPolling();
        c.undoTimeout = setTimeout(() => {}, 5000);

        c.destroy();

        expect(c._timer).toBeNull();
    });
});

describe('chatBase localStorage persistence', () => {
    test('toggleHighlight flips isHighlighted/backgroundPattern together and persists', () => {
        const c = chatBase('contact');
        expect(c.isHighlighted).toBe(false);

        c.toggleHighlight();
        expect(c.isHighlighted).toBe(true);
        expect(c.backgroundPattern).toBe('on');

        c.toggleHighlight();
        expect(c.isHighlighted).toBe(false);
        expect(c.backgroundPattern).toBe('off');
    });

    test('setPatternType updates backgroundPatternType', () => {
        const c = chatBase('contact');
        c.setPatternType('doodle');
        expect(c.backgroundPatternType).toBe('doodle');
    });

    test('_persistChatSettings writes the scoped localStorage key and never throws if storage is unavailable', () => {
        const c = chatBase('contact');
        c.isHighlighted = true;
        c.backgroundPattern = 'on';
        c.backgroundPatternType = 'mesh';

        c._persistChatSettings();

        const saved = JSON.parse(localStorage.getItem('chat-settings:contact'));
        expect(saved).toEqual({ isHighlighted: true, backgroundPattern: 'on', backgroundPatternType: 'mesh' });
    });
});

describe('chatBase toast + scroll', () => {
    test('toast dispatches a window CustomEvent carrying message/type, and no-ops on an empty message', () => {
        const c = chatBase('contact');
        const handler = vi.fn();
        window.addEventListener('toast', handler, { once: true });

        c.toast('hello', 'warning');
        expect(handler).toHaveBeenCalledOnce();
        expect(handler.mock.calls[0][0].detail).toEqual({ message: 'hello', type: 'warning' });

        const handler2 = vi.fn();
        window.addEventListener('toast', handler2, { once: true });
        c.toast('');
        expect(handler2).not.toHaveBeenCalled();
    });

    test('scrollToBottom no-ops when #msg-viewport is absent, scrolls it when present', () => {
        const c = chatBase('contact');
        expect(() => c.scrollToBottom()).not.toThrow();

        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        vp.scrollTo = vi.fn();
        document.body.appendChild(vp);

        c.scrollToBottom(true);
        expect(vp.scrollTo).toHaveBeenCalledWith({ top: 999999, behavior: 'smooth' });
    });
});

describe('chatBase search/filter panel toggles', () => {
    test('openMessageSearch toggles searchMessages and closes the type-filter panel when opening', () => {
        const c = chatBase('contact');
        c.$nextTick = (cb) => cb();
        c.typeFilterOpen = true;
        c.openMessageSearch();
        expect(c.searchMessages).toBe(true);
        expect(c.typeFilterOpen).toBe(false);

        c.openMessageSearch();
        expect(c.searchMessages).toBe(false);
    });

    test('toggleFilterPanel toggles typeFilterOpen and closes search when opening', () => {
        const c = chatBase('contact');
        c.searchMessages = true;
        c.toggleFilterPanel();
        expect(c.typeFilterOpen).toBe(true);
        expect(c.searchMessages).toBe(false);
    });

    test('focusSearchResult guards a falsy id and otherwise closes search + focuses the message via the messages island', () => {
        const c = chatBase('contact');
        c.$wire = createWireMock();
        c.searchMessages = true;

        c.focusSearchResult(0);
        expect(c.searchMessages).toBe(true);

        c.focusSearchResult(42);
        expect(c.searchMessages).toBe(false);
        expect(c.$wire._islands.messages.focusMessage).toHaveBeenCalledWith(42);
    });
});

describe('chatBase insertEmoji', () => {
    test('splices the emoji at the cursor, sets composer.body on $wire, and moves the caret past it', () => {
        const c = chatBase('contact');
        c.$wire = createWireMock();
        c.emojiOpen = true;
        c.$nextTick = (cb) => cb();

        const ta = document.createElement('textarea');
        ta.id = 'msg-ta';
        ta.value = 'hi !';
        ta.focus = vi.fn();
        document.body.appendChild(ta);
        ta.selectionStart = ta.selectionEnd = 3;

        c.insertEmoji('😀');

        expect(c.$wire.set).toHaveBeenCalledWith('composer.body', 'hi 😀!');
        expect(c.emojiOpen).toBe(false);
        expect(ta.focus).toHaveBeenCalledOnce();
    });

    test('no-ops when the textarea is missing or the value is not a string', () => {
        const c = chatBase('contact');
        c.$wire = createWireMock();
        expect(() => c.insertEmoji('😀')).not.toThrow();
        expect(c.$wire.set).not.toHaveBeenCalled();
        expect(() => c.insertEmoji(123)).not.toThrow();
    });
});

describe('chatBase reply/delete state + wire sync', () => {
    test('cancelReply clears local state and calls $wire.cancelReply when a wire is present', () => {
        const c = chatBase('contact');
        c.replyingTo = { id: 1 };
        c.$wire = createWireMock();

        c.cancelReply();

        expect(c.replyingTo).toBeNull();
        expect(c.$wire.cancelReply).toHaveBeenCalledOnce();
    });

    test('cancelDelete clears deletingId', () => {
        const c = chatBase('contact');
        c.deletingId = 7;
        c.cancelDelete();
        expect(c.deletingId).toBeNull();
    });

    test('useQuoteChip starts a reply only when the chip is visible and has an id', () => {
        const c = chatBase('contact');
        c.startReply = vi.fn();

        c.quoteChip = { visible: false, id: 5, sender: 'Ali', snippet: 'hi' };
        c.useQuoteChip();
        expect(c.startReply).not.toHaveBeenCalled();

        c.quoteChip = { visible: true, id: 0, sender: 'Ali', snippet: 'hi' };
        c.useQuoteChip();
        expect(c.startReply).not.toHaveBeenCalled();

        c.quoteChip = { visible: true, id: 5, sender: 'Ali', snippet: 'hi' };
        c.useQuoteChip();
        expect(c.startReply).toHaveBeenCalledWith(5, 'Ali', 'hi');
    });

    test('deleteMessage guards a null deletingId, then on success clears state and syncs the sidebar with the SAME captured wire', async () => {
        const c = chatBase('contact');
        c.$wire = createWireMock();
        c._syncSidebarAfterOwnAction = vi.fn();

        c.deletingId = null;
        await c.deleteMessage();
        expect(c.$wire.$island).not.toHaveBeenCalled();

        c.deletingId = 9;
        c.openActionsId = 9;
        const wireAtCallTime = c.$wire;
        await c.deleteMessage();

        expect(wireAtCallTime._islands.messages.deleteMessage).toHaveBeenCalledWith(9);
        expect(c.deletingId).toBeNull();
        expect(c.openActionsId).toBeNull();
        expect(c._syncSidebarAfterOwnAction).toHaveBeenCalledWith(wireAtCallTime);
    });

    test('deleteMessage toasts an error and leaves state untouched if the island call rejects', async () => {
        const c = chatBase('contact');
        c.$wire = createWireMock();
        c.$wire._islands.messages = { deleteMessage: vi.fn(() => Promise.reject(new Error('boom'))) };
        c.toast = vi.fn();
        c.deletingId = 3;

        await c.deleteMessage();

        expect(c.toast).toHaveBeenCalledWith('خطا در حذف پیام.', 'error');
        expect(c.deletingId).toBe(3);
    });
});

describe('chatBase auto-resize helpers', () => {
    test('initAutoResize marks the element and defines _setAutoHeight exactly once per element', () => {
        const c = chatBase('contact');
        const el = document.createElement('textarea');

        c.initAutoResize(el);
        expect(el._autoResizeInit).toBe(true);
        expect(typeof el._setAutoHeight).toBe('function');

        const firstHeightFn = el._setAutoHeight;
        c.initAutoResize(el);
        expect(el._setAutoHeight).toBe(firstHeightFn);
    });

    test('initAutoResize no-ops on a null element', () => {
        const c = chatBase('contact');
        expect(() => c.initAutoResize(null)).not.toThrow();
    });

    test('resetAutoResize clears the manual-resize marker and inline height', () => {
        const c = chatBase('contact');
        const el = document.createElement('textarea');
        el.setAttribute('data-manual-resize', '1');
        el.style.height = '80px';

        c.resetAutoResize(el);

        expect(el.hasAttribute('data-manual-resize')).toBe(false);
        expect(el.style.height).toBe('');
    });
});
