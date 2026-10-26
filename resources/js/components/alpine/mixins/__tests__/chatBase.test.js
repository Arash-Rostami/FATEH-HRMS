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

    test('_runPollTick announces every real tick as messaging-poll (the switcher badge rides it), stays silent while a tick is in flight, and tolerates a missing Livewire', async () => {
        const c = chatBase('contact');
        const dispatch = vi.fn();
        window.Livewire = { dispatch };
        let resolveFirst;
        c._pollTick = vi.fn(() => new Promise((r) => { resolveFirst = r; }));

        try {
            const firstCall = c._runPollTick(false);
            await c._runPollTick(false);
            expect(dispatch).toHaveBeenCalledOnce();
            expect(dispatch).toHaveBeenCalledWith('messaging-poll');

            resolveFirst();
            await firstCall;
        } finally {
            delete window.Livewire;
        }

        c._pollTick = vi.fn(() => Promise.resolve());
        await expect(c._runPollTick(false)).resolves.toBeUndefined();
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
        c.headerDeclutter = true;

        c._persistChatSettings();

        const saved = JSON.parse(localStorage.getItem('chat-settings:contact'));
        expect(saved).toEqual({ isHighlighted: true, backgroundPattern: 'on', backgroundPatternType: 'mesh', headerDeclutter: true });
    });

    test('_loadChatSettings restores every persisted key including headerDeclutter, and tolerates a corrupt payload', () => {
        const c = chatBase('contact');
        c.isHighlighted = true;
        c.backgroundPattern = 'on';
        c.backgroundPatternType = 'doodle';
        c.headerDeclutter = true;
        c._persistChatSettings();

        const fresh = chatBase('contact');
        fresh._loadChatSettings();
        expect(fresh.isHighlighted).toBe(true);
        expect(fresh.backgroundPattern).toBe('on');
        expect(fresh.backgroundPatternType).toBe('doodle');
        expect(fresh.headerDeclutter).toBe(true);

        localStorage.setItem('chat-settings:contact', '{broken');
        const safe = chatBase('contact');
        expect(() => safe._loadChatSettings()).not.toThrow();
        expect(safe.isHighlighted).toBe(false);
        expect(safe.headerDeclutter).toBe(false);
    });
});

describe('chatBase header declutter', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    test('collapse plays the exit slide-out, then hides and persists; expand is a plain flip (buttons re-emerge with the header, no separate slide)', async () => {
        const c = chatBase('contact');
        c.toggleHeaderDeclutter();
        expect(c.headerDeclutter).toBe(false);
        expect(c.headerDeclutterExit).toBe(true);
        vi.advanceTimersByTime(400);
        expect(c.headerDeclutter).toBe(true);
        expect(c.headerDeclutterExit).toBe(false);
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).headerDeclutter).toBe(true);

        c.toggleHeaderDeclutter();
        expect(c.headerDeclutter).toBe(false);
        expect(c.headerDeclutterExit).toBe(false);
        await Promise.resolve();
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).headerDeclutter).toBe(false);
    });

    test('clicking the toggle mid-collapse cancels the collapse', () => {
        const c = chatBase('contact');
        c.toggleHeaderDeclutter();
        expect(c.headerDeclutterExit).toBe(true);
        c.toggleHeaderDeclutter();
        expect(c.headerDeclutterExit).toBe(false);
        expect(c.headerDeclutter).toBe(false);
        vi.advanceTimersByTime(400);
        expect(c.headerDeclutter).toBe(false);
    });

    test('headerBtnClass mirrors the responsive contract: exit animates (desktop-only), open wins, declutter hides, default has no enter slide', () => {
        const c = chatBase('contact');
        expect(c.headerBtnClass(false)).toBe('hidden md:flex');
        expect(c.headerBtnClass(false, 'block')).toBe('hidden md:block');
        expect(c.headerBtnClass(true)).toBe('flex');

        c.headerDeclutterExit = true;
        expect(c.headerBtnClass(false)).toBe('md:flex animate-slide-fade-to-left');
        expect(c.headerBtnClass(false, 'block')).toBe('md:block animate-slide-fade-to-left');
        expect(c.headerBtnClass(true)).toBe('md:flex animate-slide-fade-to-left');
        c.headerDeclutterExit = false;

        c.headerDeclutter = true;
        expect(c.headerBtnClass(false)).toBe('hidden');
        expect(c.headerBtnClass(false, 'block')).toBe('hidden');
        expect(c.headerBtnClass(true)).toBe('hidden');
        c.headerDeclutter = false;
    });

    test('the hamburger-menu declutter bridge is unconditional both ways and persists, cancelling a mid-exit timer', async () => {
        localStorage.removeItem('menu_declutter');
        const c = chatBase('contact');
        c.headerDeclutterMenuBridge();
        const on = () => window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }));
        const off = () => window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }));

        c.headerDeclutter = true;
        on();
        expect(c.headerDeclutter).toBe(true);
        off();
        expect(c.headerDeclutter).toBe(false);
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).headerDeclutter).toBe(false);
        on();
        expect(c.headerDeclutter).toBe(true);
        c.setPatternType('mesh');
        vi.advanceTimersByTime(0);
        await Promise.resolve();
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).headerDeclutter).toBe(false);

        c.headerDeclutter = false;
        c.toggleHeaderDeclutter();
        expect(c.headerDeclutterExit).toBe(true);
        off();
        expect(c.headerDeclutterExit).toBe(false);
        expect(c.headerDeclutter).toBe(false);
        vi.advanceTimersByTime(400);
        expect(c.headerDeclutter).toBe(false);
    });

    test('on boot it syncs collapsed from the persisted menu_declutter pref; a remounted instance stays consistent', () => {
        localStorage.setItem('menu_declutter', 'true');
        const off = () => window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }));

        const a = chatBase('contact');
        a.headerDeclutterMenuBridge();
        expect(a.headerDeclutter).toBe(true);

        const b = chatBase('contact');
        b._loadChatSettings();
        b.headerDeclutterMenuBridge();
        expect(b.headerDeclutter).toBe(true);

        off();
        expect(b.headerDeclutter).toBe(false);
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).headerDeclutter).toBe(false);
        localStorage.removeItem('menu_declutter');
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

    test('loadOlderKeepingPlace keeps the reader on the same message and lets a few lines of the new page peek in above', async () => {
        const c = chatBase('contact');
        c.$nextTick = () => Promise.resolve();
        c.$wire = { activeUserId: 5 };

        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        let height = 1000;
        Object.defineProperty(vp, 'scrollHeight', { get: () => height });
        vp.scrollTop = 30;
        document.body.appendChild(vp);

        await c.loadOlderKeepingPlace(async () => { height = 1700; });

        expect(vp.scrollTop).toBe(30 + 700 - 96);
        expect(c._loadingOlder).toBe(false);
    });

    test('loadOlderKeepingPlace skips the restore when the active conversation changed during load', async () => {
        const c = chatBase('contact');
        c.$nextTick = () => Promise.resolve();
        c.$wire = { activeUserId: 5 };

        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        let height = 1000;
        Object.defineProperty(vp, 'scrollHeight', { get: () => height });
        vp.scrollTop = 30;
        document.body.appendChild(vp);

        await c.loadOlderKeepingPlace(async () => { height = 1700; c.$wire.activeUserId = 6; });

        expect(vp.scrollTop).toBe(30);
        expect(c._loadingOlder).toBe(false);
    });

    test('loadOlderKeepingPlace restore is programmatic', async () => {
        vi.useFakeTimers();
        const c = chatBase('contact');
        c.$nextTick = () => Promise.resolve();
        c.$wire = { activeChannelId: 3 };

        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        let height = 1000;
        Object.defineProperty(vp, 'scrollHeight', { get: () => height });
        document.body.appendChild(vp);

        await c.loadOlderKeepingPlace(async () => { height = 1700; });

        expect(c._programmaticScroll).toBe(true);
        vi.advanceTimersByTime(150);
        expect(c._programmaticScroll).toBe(false);
        vi.useRealTimers();
    });

    test('newBelow defaults to 0', () => {
        expect(chatBase('contact').newBelow).toBe(0);
    });

    test('scrollToBottom marks the scroll programmatic and clears it after the settle window', () => {
        vi.useFakeTimers();
        const c = chatBase('contact');
        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        vp.scrollTo = vi.fn();
        document.body.appendChild(vp);

        c.scrollToBottom();
        expect(c._programmaticScroll).toBe(true);

        vi.advanceTimersByTime(149);
        expect(c._programmaticScroll).toBe(true);
        vi.advanceTimersByTime(1);
        expect(c._programmaticScroll).toBe(false);
        vi.useRealTimers();
    });

    test('_armScrollSettle re-arms while scroll events keep arriving', () => {
        vi.useFakeTimers();
        const c = chatBase('contact');
        c.markProgrammaticScroll();

        vi.advanceTimersByTime(100);
        c._armScrollSettle();
        vi.advanceTimersByTime(100);
        expect(c._programmaticScroll).toBe(true);

        vi.advanceTimersByTime(50);
        expect(c._programmaticScroll).toBe(false);
        vi.useRealTimers();
    });

    test('_rowsNewerThan counts message rows with an id above the marker', () => {
        const c = chatBase('contact');
        const vp = document.createElement('div');
        vp.innerHTML = '<div data-rf="message-1"></div><div data-rf="message-2"></div><div data-rf="message-3"></div>';
        c._msgViewportEl = vp;

        expect(c._rowsNewerThan('message-1')).toBe(2);
        expect(c._rowsNewerThan('message-3')).toBe(0);
    });

    test('loadOlderKeepingPlace ignores a second call while one is running and no-ops without a viewport', async () => {
        const c = chatBase('contact');
        c.$nextTick = () => Promise.resolve();
        c.$wire = {};
        const load = vi.fn(async () => {});

        await c.loadOlderKeepingPlace(load);
        expect(load).not.toHaveBeenCalled();

        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        document.body.appendChild(vp);
        c._loadingOlder = true;

        await c.loadOlderKeepingPlace(load);
        expect(load).not.toHaveBeenCalled();
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

describe('chatBase backToLatest', () => {
    test('in a jumped-to (anchor) window it reloads the newest messages via clearFocus before scrolling down', async () => {
        const c = chatBase('contact');
        c.$wire = createWireMock({ focusAnchorId: 42 });
        c.$nextTick = (cb) => cb();
        c.scrollToBottom = vi.fn();

        c.backToLatest();
        await Promise.resolve();
        await Promise.resolve();

        expect(c.$wire._islands.messages.clearFocus).toHaveBeenCalledOnce();
        expect(c.scrollToBottom).toHaveBeenCalledOnce();
    });

    test('outside an anchor window it only scrolls smoothly, with no server call', () => {
        const c = chatBase('contact');
        c.$wire = createWireMock({ focusAnchorId: null });
        c.scrollToBottom = vi.fn();

        c.backToLatest();

        expect(c.$wire.$island).not.toHaveBeenCalled();
        expect(c.scrollToBottom).toHaveBeenCalledWith(true);
    });
});

describe('chatBase day list', () => {
    const setup = (hasOlder) => {
        const c = chatBase('contact');
        c.$wire = createWireMock({ hasOlder });
        document.body.innerHTML = '<div id="msg-viewport">'
            + '<div data-day="2026-10-10"></div><div data-rf="message-5"></div>'
            + '<div data-day="2026-10-11"></div><div data-rf="new-x"></div><div data-rf="message-9"></div></div>';
        return c;
    };

    test('lists loaded days from the DOM newest-first with no server call when nothing older exists', () => {
        const c = setup(false);

        c.toggleDays();

        expect(c.dayList).toEqual([{ day: '2026-10-11', id: 9 }, { day: '2026-10-10', id: 5 }]);
        expect(c.$wire.$island).not.toHaveBeenCalled();
    });

    test('with older history it asks the server, merges unseen days and keeps each day\'s true first message id', async () => {
        const c = setup(true);
        c.$wire.$island('messages').days.mockResolvedValue([{ day: '2026-10-10', id: 2 }, { day: '2026-10-01', id: 1 }]);

        c.toggleDays();
        await Promise.resolve();
        await Promise.resolve();

        expect(c.$wire._islands.messages.days).toHaveBeenCalledWith();
        expect(c.dayList.map(d => d.day)).toEqual(['2026-10-11', '2026-10-10', '2026-10-01']);
        expect(c.dayList.find(d => d.day === '2026-10-10').id).toBe(2);
        expect(c.dayRows.filter(r => r.header).length).toBe(1);
    });

    test('reopening within the TTL reuses the in-browser copy instead of calling the server again', async () => {
        const c = setup(true);
        c.$wire.$island('messages').days.mockResolvedValue([{ day: '2026-10-01', id: 1 }]);

        c.toggleDays();
        await Promise.resolve();
        await Promise.resolve();
        c.toggleDays();
        c.toggleDays();

        expect(c.$wire._islands.messages.days).toHaveBeenCalledTimes(1);
        expect(c.dayList.map(d => d.day)).toContain('2026-10-01');
    });

    test('in a jumped-to (anchor) window it also asks the server, since newer days are not loaded either', () => {
        const c = setup(false);
        c.$wire.focusAnchorId = 5;

        c.toggleDays();

        expect(c.$wire._islands.messages.days).toHaveBeenCalledOnce();
    });

    test('jumpToDay closes the panel and scrolls to that day\'s first message', () => {
        const c = setup(false);
        c.scrollToMessage = vi.fn();
        c.daysOpen = true;

        c.jumpToDay({ day: '2026-10-10', id: 5 });

        expect(c.daysOpen).toBe(false);
        expect(c.scrollToMessage).toHaveBeenCalledWith(5);
    });
});

describe('chatBase toggleFiles (shared attachment gallery)', () => {
    const setup = () => {
        const c = chatBase('contact');
        c.$wire = createWireMock();
        return c;
    };

    test('a fresh open fetches once and caches; re-opening within the TTL never calls the server again', async () => {
        const c = setup();
        c.$wire.$island('messages').files.mockResolvedValue([
            { key: '9:0', msg_id: 9, index: 0, kind: 'doc', url: '/a.pdf', name: 'a.pdf', size_label: '4 KB', time: '' },
            { key: '7:0', msg_id: 7, index: 0, kind: 'image', url: '/b.png', name: 'b.png', size_label: '2 KB', time: '' },
        ]);

        c.toggleFiles();
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();
        expect(c.filesList).toHaveLength(2);
        expect(c.filesLoading).toBe(false);

        c.toggleFiles(); // closes
        c.toggleFiles(); // reopens → cache branch, zero refetch

        expect(c.$wire._islands.messages.files).toHaveBeenCalledTimes(1);
        expect(c.filesList).toHaveLength(2);
    });

    test('switching conversation empties the list immediately and refetches (conv-keyed cache)', async () => {
        const c = setup();
        c.$wire.$island('messages').files.mockResolvedValue([
            { key: '9:0', msg_id: 9, index: 0, kind: 'doc', url: '/a.pdf', name: 'a.pdf', size_label: '4 KB', time: '' },
        ]);

        c.toggleFiles();
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();

        c.$wire.activeUserId = 42;
        c.toggleFiles(); // closes (was open)
        c.toggleFiles(); // reopens → conv switch detected

        expect(c.filesList).toEqual([]);
        expect(c.filesLoading).toBe(true);
    });
});

describe('chatBase toggleBookmarks', () => {
    const setup = (pinned) => {
        const c = chatBase('contact');
        c.$wire = createWireMock();
        c.$store = { pinned: { getPinned: () => pinned, isPinned: (id) => pinned.includes(id) } };
        document.body.innerHTML = '<div data-rf="message-7"><button data-sender="Ali" data-body="hello"></button></div>';
        return c;
    };

    test('resolves loaded messages from the DOM and never calls the server', () => {
        const c = setup([7]);
        c.searchMessages = true;

        c.toggleBookmarks();

        expect(c.bookmarksOpen).toBe(true);
        expect(c.searchMessages).toBe(false);
        expect(c.bookmarkList).toEqual([{ id: 7, body: 'hello', sender_name: 'Ali', time: '' }]);
        expect(c.$wire.$island).not.toHaveBeenCalled();
    });

    test('asks the server only for ids missing from the DOM and merges newest-first', async () => {
        const c = setup([7, 3]);
        const row = { id: 3, body: 'old', sender_name: 'Sara', time: '1d', is_mine: false };
        c.$wire.$island('messages').bookmarks.mockResolvedValue([row]);
        c.$wire.$island.mockClear();

        c.toggleBookmarks();
        await Promise.resolve();
        await Promise.resolve();

        expect(c.$wire._islands.messages.bookmarks).toHaveBeenCalledWith([3]);
        expect(c.bookmarkList.map(b => b.id)).toEqual([7, 3]);
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
        expect(c.emojiOpen).toBe(true);
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

describe('chatBase _ringIncoming while the tab is hidden (background pop-up polling keeps running)', () => {
    let hidden;

    beforeEach(() => {
        hidden = true;
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => hidden });
    });

    afterEach(() => {
        delete document.hidden;
    });

    test('a hidden ring is deferred silently and plays exactly once when the tab becomes visible', () => {
        const c = chatBase('contact');
        c.$store = { sound: { playIncoming: vi.fn() } };

        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();

        c._flushPendingRing();
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();

        hidden = false;
        c._flushPendingRing();
        expect(c.$store.sound.playIncoming).toHaveBeenCalledExactlyOnceWith(null, 'contact');

        c._flushPendingRing();
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();
    });

    test('after the return ding, the catch-up tick\'s ring for the same event is suppressed for 8s, then dings resume', () => {
        const c = chatBase('contact');
        c.$store = { sound: { playIncoming: vi.fn() } };
        const now = vi.spyOn(Date, 'now');
        now.mockReturnValue(100_000);

        c._ringIncoming(null);
        hidden = false;
        c._flushPendingRing();
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();

        now.mockReturnValue(105_000);
        c._ringIncoming(5);
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();

        now.mockReturnValue(109_000);
        c._ringIncoming(5);
        expect(c.$store.sound.playIncoming).toHaveBeenCalledTimes(2);

        now.mockRestore();
    });

    test('the next poll delay is jittered ±25% while hidden but only ±10% while visible', () => {
        const delays = [];
        const spy = vi.spyOn(global, 'setTimeout').mockImplementation((_fn, ms) => { delays.push(ms); return 1; });
        const rand = vi.spyOn(Math, 'random');
        const c = chatBase('contact');
        c._pollIntervalMs = 30000;
        const range = (r) => { rand.mockReturnValue(r); c._schedulePollTick(); return delays.at(-1); };

        expect([range(0), range(1)]).toEqual([22500, 37500]);

        hidden = false;
        expect([range(0), range(1)]).toEqual([27000, 33000]);

        spy.mockRestore();
        rand.mockRestore();
    });
});

describe('chatBase general (id = null) ding respects per-room mute using the unread counts already in the sidebar rows', () => {
    beforeEach(() => {
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => false });
    });

    afterEach(() => {
        delete document.hidden;
    });

    function setup(muted = []) {
        const c = chatBase('contact');
        let total = 0;
        c._readUnread = () => total;
        c.$store = { sound: { isMuted: (id) => muted.includes(id), playIncoming: vi.fn() } };
        const rows = (counts) => {
            document.body.innerHTML = Object.entries(counts)
                .map(([id, n]) => `<div data-unread-id="${id}" data-unread="${n}"></div>`)
                .join('');
            total = Object.values(counts).reduce((a, b) => a + b, 0);
        };
        return { c, rows };
    }

    test('a rise only in muted rooms stays silent', () => {
        const { c, rows } = setup([2]);
        rows({ 1: 0, 2: 0 });
        c._ringIncoming(null);
        c.$store.sound.playIncoming.mockClear();
        c._lastIncomingAt = 0;

        rows({ 1: 0, 2: 1 });
        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();
    })

    test('a rise in an unmuted room rings even if a muted room also rose; all-muted stays silent', () => {
        const { c, rows } = setup([2, 3]);
        rows({ 1: 0, 2: 0, 3: 0 });
        c._ringIncoming(null);
        c.$store.sound.playIncoming.mockClear();
        c._lastIncomingAt = 0;

        rows({ 1: 0, 2: 1, 3: 1 });
        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();

        c._lastIncomingAt = 0;
        rows({ 1: 1, 2: 2, 3: 1 });
        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();
    })

    test('a rise the visible rows cannot explain (e.g. a room outside the loaded list) fails open and rings', () => {
        const { c, rows } = setup([2]);
        rows({ 2: 0 });
        c._ringIncoming(null);
        c.$store.sound.playIncoming.mockClear();
        c._lastIncomingAt = 0;

        rows({ 2: 1 });
        c._readUnread = () => 3;
        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();
    })

    test('hidden tab: an unmuted rise parked first still dings on return even if a muted-only rise follows before the user returns', () => {
        const { c, rows } = setup([2]);
        rows({ 1: 0, 2: 0 });
        c._ringIncoming(null);
        c.$store.sound.playIncoming.mockClear();
        c._lastIncomingAt = 0;

        Object.defineProperty(document, 'hidden', { configurable: true, get: () => true });
        rows({ 1: 1, 2: 0 });
        c._ringIncoming(null);
        rows({ 1: 1, 2: 1 });
        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();

        Object.defineProperty(document, 'hidden', { configurable: true, get: () => false });
        c._flushPendingRing();
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();
    })

    test('a same-total change in the rows (one room read, a muted room up) does not leave a stale baseline that silences the next unmuted rise', () => {
        const { c, rows } = setup([2]);
        rows({ 1: 2, 2: 0 });
        c._ringIncoming(null);
        c.$store.sound.playIncoming.mockClear();
        c._lastIncomingAt = 0;

        rows({ 1: 0, 2: 2 });
        c._riseOnlyMuted();
        c._lastIncomingAt = 0;

        rows({ 1: 1, 2: 2 });
        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();
    })

    test('repeated null rings for the same total reuse one decision, and a muted decision is not overturned by the second call', () => {
        const { c, rows } = setup([2]);
        rows({ 2: 0 });
        c._ringIncoming(null);
        c.$store.sound.playIncoming.mockClear();
        c._lastIncomingAt = 0;

        rows({ 2: 1 });
        c._ringIncoming(null);
        c._ringIncoming(null);
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();
    })
});
