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

    test('message downloads wait for each response, ignore repeat clicks and stop on chat switch', async () => {
        const c = chatBase('channel');
        let next;
        const downloadAttachment = vi.fn(() => new Promise(resolve => { next = resolve; }));
        c.$wire = { activeChannelId: 7, $el: document.body, $island: () => ({ downloadAttachment }) };
        const pending = c.downloadMessageFiles(42, [0, 3, 4]);
        await c.downloadMessageFiles(42, [0, 3, 4]);
        expect(downloadAttachment.mock.calls).toEqual([[42, 0]]);
        next();
        await Promise.resolve();
        expect(downloadAttachment.mock.calls).toEqual([[42, 0], [42, 3]]);
        c.$wire.activeChannelId = 8;
        next();
        await pending;
        expect(downloadAttachment).toHaveBeenCalledTimes(2);
        expect(c.downloadingAttachments).toBe(false);
    });

    test('autoDirection no-ops on a null element', () => {
        expect(() => base.autoDirection(null)).not.toThrow();
    });
});

describe('chatBase per-message font size — hover +/- buttons, saved with the module chat settings', () => {
    test('a message with no saved step has the default size and no inline style', () => {
        const c = chatBase('contact');
        expect(c.fontStep(5)).toBe(0);
        expect(c.fontStyle(5)).toBe('');
    });

    test('bumpFont moves one step at a time and the style follows the text scale', () => {
        const c = chatBase('contact');
        c.bumpFont(5, 1);
        expect(c.fontStyle(5)).toBe('font-size:1rem');
        c.bumpFont(5, 1);
        expect(c.fontStyle(5)).toBe('font-size:1.125rem');
        c.bumpFont(5, -2);
        expect(c.fontStyle(5)).toBe('');
        c.bumpFont(5, -1);
        expect(c.fontStyle(5)).toBe('font-size:0.75rem');
    });

    test('steps are clamped and fontAtMin and fontAtMax report the limits', () => {
        const c = chatBase('channel');
        for (let i = 0; i < 10; i++) c.bumpFont(1, 1);
        expect(c.fontAtMax(1)).toBe(true);
        expect(c.fontStyle(1)).toBe('font-size:1.25rem');
        for (let i = 0; i < 10; i++) c.bumpFont(1, -1);
        expect(c.fontAtMin(1)).toBe(true);
        expect(c.fontStyle(1)).toBe('font-size:0.75rem');
    });

    test('only non-default steps are saved under the module key, restored on load, and a return to default removes the entry', async () => {
        const c = chatBase('contact');
        c.bumpFont(3, 1);
        c.bumpFont(4, -1);
        await Promise.resolve();
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).fontSteps).toEqual({ 3: 1, 4: -1 });

        const again = chatBase('contact');
        again._loadChatSettings();
        expect(again.fontStep(3)).toBe(1);
        expect(again.fontStep(4)).toBe(-1);

        again.bumpFont(3, -1);
        await Promise.resolve();
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).fontSteps).toEqual({ 4: -1 });
    });

    test('the contact and channel modules keep separate sizes for the same message id', async () => {
        const dm = chatBase('contact');
        dm.bumpFont(7, 1);
        await Promise.resolve();
        const ch = chatBase('channel');
        ch._loadChatSettings();
        expect(dm.fontStep(7)).toBe(1);
        expect(ch.fontStep(7)).toBe(0);
    });

    test('corrupt or out-of-range saved steps are ignored and the saved map is capped', () => {
        localStorage.setItem('chat-settings:contact', JSON.stringify({ fontSteps: { 1: 99, 2: 'x', 3: 2 } }));
        const c = chatBase('contact');
        c._loadChatSettings();
        expect(c.fontStep(1)).toBe(0);
        expect(c.fontStep(2)).toBe(0);
        expect(c.fontStep(3)).toBe(2);

        const big = chatBase('contact');
        for (let i = 1; i <= 320; i++) big.bumpFont(i, 1);
        expect(Object.keys(big.fontSteps).length).toBeLessThanOrEqual(300);
        expect(big.fontStep(320)).toBe(1);
        expect(big.fontStep(1)).toBe(0);
    });
});

describe('chatBase message font family — one module-wide choice from the correspondence font registry', () => {
    test('the default adds no font-family and a chosen font joins the size style', () => {
        const c = chatBase('contact');
        expect(c.messageFont).toBe('default');
        expect(c.fontFamilyStyle()).toBe('');
        c.setMessageFont('vazirmatn');
        expect(c.fontFamilyStyle()).toBe('font-family:"vazirmatn",Yekan,IranYekan,sans-serif');
        c.bumpFont(9, 1);
        expect(c.fontStyle(9)).toBe('font-size:1rem;font-family:"vazirmatn",Yekan,IranYekan,sans-serif');
        expect(c.fontStyle(10)).toBe('font-family:"vazirmatn",Yekan,IranYekan,sans-serif');
    });

    test('an invalid key falls back to the default instead of reaching a style string', () => {
        const c = chatBase('contact');
        c.setMessageFont('x";color:red');
        expect(c.messageFont).toBe('default');
        c.setMessageFont(undefined);
        expect(c.messageFont).toBe('default');
    });

    test('the choice is saved with the module chat settings, restored, and kept per module', async () => {
        const dm = chatBase('contact');
        dm.setMessageFont('parastoo');
        await Promise.resolve();
        expect(JSON.parse(localStorage.getItem('chat-settings:contact')).messageFont).toBe('parastoo');

        const again = chatBase('contact');
        again._loadChatSettings();
        expect(again.messageFont).toBe('parastoo');

        const ch = chatBase('channel');
        ch._loadChatSettings();
        expect(ch.messageFont).toBe('default');
    });

    test('a corrupt saved value is ignored on load', () => {
        localStorage.setItem('chat-settings:contact', JSON.stringify({ messageFont: '<script>' }));
        const c = chatBase('contact');
        c._loadChatSettings();
        expect(c.messageFont).toBe('default');
    });
});

describe('chatBase fitEdit — the inline edit box is sized to its content, capped at 320px', () => {
    const ta = (scrollHeight) => {
        const el = document.createElement('textarea');
        Object.defineProperty(el, 'scrollHeight', { value: scrollHeight, configurable: true });
        return el;
    };

    test('sizes the box to its scroll height at once', () => {
        const el = ta(180);
        chatBase('contact').fitEdit(el);
        expect(el.style.height).toBe('180px');
    });

    test('on open it also scrolls the box back to the top, while typing it leaves the scroll alone', () => {
        const el = ta(500);
        el.scrollTop = 120;
        const c = chatBase('contact');
        c.fitEdit(el);
        expect(el.scrollTop).toBe(120);
        c.fitEdit(el, true);
        expect(el.scrollTop).toBe(0);
    });

    test('on open it also scrolls the whole box into view so it never sits under the composer', async () => {
        const wrap = document.createElement('div');
        wrap.scrollIntoView = vi.fn();
        const el = ta(200);
        wrap.appendChild(el);
        chatBase('channel').fitEdit(el, true);
        await new Promise((r) => setTimeout(r, 5));
        expect(wrap.scrollIntoView).toHaveBeenCalledWith({ block: 'nearest' });

        const typing = document.createElement('div');
        typing.scrollIntoView = vi.fn();
        const el2 = ta(200);
        typing.appendChild(el2);
        chatBase('channel').fitEdit(el2);
        await new Promise((r) => setTimeout(r, 5));
        expect(typing.scrollIntoView).toHaveBeenCalledWith({ block: 'nearest' });
    });

    test('regrowing the box keeps the viewport scroll and flags it programmatic', () => {
        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        document.body.appendChild(vp);
        let top = 300;
        Object.defineProperty(vp, 'scrollTop', { get: () => top, set: (v) => { top = v; }, configurable: true });
        const el = ta(200);
        Object.defineProperty(el.style, 'height', {
            get: () => '',
            set: (v) => { if (v === 'auto') top = 0; },
            configurable: true,
        });
        const c = chatBase('contact');
        c.fitEdit(el);
        expect(top).toBe(300);
        expect(c._programmaticScroll).toBe(true);
        vp.remove();
    });

    test('openEdit focuses with the caret at the end and fits once visible, retrying while hidden', async () => {
        const el = ta(150);
        el.value = 'hello';
        document.body.appendChild(el);
        const c = chatBase('contact');
        const focus = vi.spyOn(el, 'focus');
        const fit = vi.spyOn(c, 'fitEdit');
        let visible = 0;
        el.getClientRects = () => (visible ? [1] : []);
        c.openEdit(el);
        await new Promise((r) => setTimeout(r, 40));
        expect(focus).not.toHaveBeenCalled();
        visible = 1;
        c.openEdit(el);
        await new Promise((r) => setTimeout(r, 40));
        expect(focus).toHaveBeenCalledWith({ preventScroll: true });
        expect(el.selectionStart).toBe(5);
        expect(fit).toHaveBeenCalledWith(el, true);
        el.remove();
    });

    test('caps tall content at 320px and ignores a missing element', () => {
        const el = ta(900);
        const c = chatBase('channel');
        c.fitEdit(el);
        expect(el.style.height).toBe('320px');
        expect(() => c.fitEdit(null)).not.toThrow();
    });
});

describe('chatBase react — shared by channel and contact, island-routed, bursts only when the give lands', () => {
    const chipEl = (mine) => {
        const chip = document.createElement('button');
        chip.className = mine ? 'reaction-burst reaction-mine' : 'reaction-burst';
        chip.dataset.reactionChip = '7';
        chip.dataset.emoji = 'x';
        document.body.appendChild(chip);
        return chip;
    };

    test.each(['contact', 'channel'])('%s: bursts the chip once the island call lands and it is now mine', async (scope) => {
        const c = chatBase(scope);
        const chip = chipEl(true);
        const toggleReaction = vi.fn().mockResolvedValue(undefined);
        c.$wire = { $island: vi.fn(() => ({ toggleReaction })) };

        await c.react(7, 'x');

        expect(c.$wire.$island).toHaveBeenCalledWith('messages');
        expect(toggleReaction).toHaveBeenCalledWith(7, 'x');
        expect(chip.classList.contains('burst-now')).toBe(true);
    });

    test('never bursts a chip that is not mine', async () => {
        const c = chatBase('contact');
        const chip = chipEl(false);
        c.$wire = { $island: () => ({ toggleReaction: vi.fn().mockResolvedValue(undefined) }) };

        await c.react(7, 'x');

        expect(chip.classList.contains('burst-now')).toBe(false);
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

    test('a wire:navigate click stops polling and no tick or restart happens during the page swap', async () => {
        const c = chatBase('contact');
        c._pollTick = vi.fn();
        c.startPolling();
        expect(c._timer).not.toBeNull();

        document.dispatchEvent(new Event('livewire:navigate'));

        expect(c._timer).toBeNull();
        c.startPolling();
        expect(c._timer).toBeNull();
        await c._runPollTick(true);
        expect(c._pollTick).not.toHaveBeenCalled();
    });

    test('polling resumes when a started navigation fails or is aborted', () => {
        const c = chatBase('contact');
        c._pollTick = vi.fn();
        c.startPolling();

        document.dispatchEvent(new Event('livewire:navigate'));
        expect(c._timer).toBeNull();

        document.dispatchEvent(new Event('livewire:navigate-failed'));
        expect(c._timer).not.toBeNull();
        c.stopPolling();
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

    test('_runPollTick stops a hidden tab whose bell is off, and leaves a tab without the push store alone', async () => {
        const hidden = (value) => Object.defineProperty(document, 'hidden', { configurable: true, value });
        const c = chatBase('contact');
        c._pollTick = vi.fn(async () => {});
        c.startPolling();
        c.$store = { push: { pollsHidden: vi.fn(() => false) } };
        hidden(true);

        await c._runPollTick(false);
        expect(c._pollTick).not.toHaveBeenCalled();
        expect(c._timer).toBeNull();

        c.$store = { push: { pollsHidden: vi.fn(() => true) } };
        await c._runPollTick(false);
        expect(c._pollTick).toHaveBeenCalledTimes(1);

        c.$store = {};
        await c._runPollTick(false);
        expect(c._pollTick).toHaveBeenCalledTimes(2);

        hidden(false);
        c.stopPolling();
    });

    test('a hidden tab with live Web Push keeps only a slow five-minute safety poll', () => {
        vi.useFakeTimers();
        const hidden = (value) => Object.defineProperty(document, 'hidden', { configurable: true, value });
        const c = chatBase('contact');
        c._pollTick = vi.fn(async () => {});
        c.$store = { push: { live: true, pollsHidden: () => true } };
        hidden(true);

        c._schedulePollTick();
        vi.advanceTimersByTime(200000);
        expect(c._pollTick).not.toHaveBeenCalled();
        vi.advanceTimersByTime(180000);
        expect(c._pollTick).toHaveBeenCalledTimes(1);

        hidden(false);
        c.stopPolling();
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
        expect(saved).toEqual({ isHighlighted: true, backgroundPattern: 'on', backgroundPatternType: 'mesh', headerDeclutter: true, fontSteps: {}, messageFont: 'default' });
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

    test('aborting the bridge signal stops an old room from reacting to the menu declutter switch', () => {
        localStorage.removeItem('menu_declutter');
        const ac = new AbortController();
        const c = chatBase('contact');
        c.headerDeclutterMenuBridge(ac.signal);

        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }));
        expect(c.headerDeclutter).toBe(true);

        ac.abort();
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }));
        expect(c.headerDeclutter).toBe(true);
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

    test('loadNewer runs the load, then records the last-message marker as _selfMarker and clears its flag', async () => {
        const c = chatBase('contact');
        c.$nextTick = () => Promise.resolve();
        c._readLastMessageMarker = vi.fn(() => 'message-9');
        const load = vi.fn(async () => { expect(c._loadingNewer).toBe(true); });

        await c.loadNewer(load);

        expect(load).toHaveBeenCalledOnce();
        expect(c._selfMarker).toBe('message-9');
        expect(c._loadingNewer).toBe(false);
    });

    test('loadNewer ignores a second call while one is running', async () => {
        const c = chatBase('contact');
        c.$nextTick = () => Promise.resolve();
        c._readLastMessageMarker = vi.fn(() => 'message-9');
        const load = vi.fn(async () => {});
        c._loadingNewer = true;

        await c.loadNewer(load);

        expect(load).not.toHaveBeenCalled();
        expect(c._selfMarker).toBeNull();
    });

    test('loadNewer swallows a failing load, leaves _selfMarker untouched and releases the flag', async () => {
        const c = chatBase('contact');
        c.$nextTick = () => Promise.resolve();
        c._readLastMessageMarker = vi.fn(() => 'message-9');

        await expect(c.loadNewer(async () => { throw new Error('boom'); })).resolves.toBeUndefined();

        expect(c._selfMarker).toBeNull();
        expect(c._loadingNewer).toBe(false);
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

        expect(c.$wire.set).toHaveBeenCalledWith('composer.body', 'hi 😀!', false);
        expect(ta.value).toBe('hi 😀!');
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

describe('chatBase.loadOlderKeepingPlace — anchor-row restore (added 2026-11-02)', () => {
    let docTop
    let vp
    let row
    let c

    const setup = (rowDocTop = 30) => {
        vi.useFakeTimers()
        vi.stubGlobal('requestAnimationFrame', (cb) => setTimeout(cb, 16))
        docTop = rowDocTop
        vp = document.createElement('div')
        vp.id = 'msg-viewport'
        vp.scrollTop = 30
        vp.getBoundingClientRect = () => ({ top: 0 })
        row = document.createElement('div')
        row.dataset.rf = 'message-7'
        row.getBoundingClientRect = () => ({ top: docTop - vp.scrollTop })
        vp.appendChild(row)
        document.body.appendChild(vp)
        c = chatBase('contact')
        c.$nextTick = () => Promise.resolve()
        c.$wire = { activeUserId: 5 }
    }

    afterEach(() => {
        document.body.innerHTML = ''
        vi.useRealTimers()
        vi.unstubAllGlobals()
    })

    test('keeps the same message on screen with a 96px peek of the new page above it', async () => {
        setup()
        await c.loadOlderKeepingPlace(async () => { docTop += 700 })
        expect(docTop - vp.scrollTop).toBe(96)
    })

    test('keeps correcting for height that changes above the message right after the load', async () => {
        setup()
        await c.loadOlderKeepingPlace(async () => { docTop += 700 })
        docTop += 40
        vi.advanceTimersByTime(40)
        expect(docTop - vp.scrollTop).toBe(96)
    })

    test('stops correcting once the reader scrolls', async () => {
        setup()
        await c.loadOlderKeepingPlace(async () => { docTop += 700 })
        vp.dispatchEvent(new Event('wheel'))
        const stopped = vp.scrollTop
        docTop += 40
        vi.advanceTimersByTime(100)
        expect(vp.scrollTop).toBe(stopped)
    })

    test('stops correcting after the settle window', async () => {
        setup()
        await c.loadOlderKeepingPlace(async () => { docTop += 700 })
        vi.advanceTimersByTime(600)
        const settled = vp.scrollTop
        docTop += 40
        vi.advanceTimersByTime(100)
        expect(vp.scrollTop).toBe(settled)
    })

    test('does not scroll when nothing was added above', async () => {
        setup()
        await c.loadOlderKeepingPlace(async () => {})
        expect(vp.scrollTop).toBe(30)
    })

    test('falls back to the height difference when the reference message disappears', async () => {
        setup()
        let height = 1000
        Object.defineProperty(vp, 'scrollHeight', { get: () => height })
        await c.loadOlderKeepingPlace(async () => { height = 1700; vp.removeChild(row) })
        expect(vp.scrollTop).toBe(30 + 700 - 96)
    })

    test('does not snap the reader back when they scrolled during the load round trip', async () => {
        setup()
        let height = 1000
        Object.defineProperty(vp, 'scrollHeight', { get: () => height })
        await c.loadOlderKeepingPlace(async () => { docTop += 700; height = 1700; vp.scrollTop += 500 })
        expect(vp.scrollTop).toBe(30 + 500 + 700 - 96)
        const settled = vp.scrollTop
        vi.advanceTimersByTime(100)
        expect(vp.scrollTop).toBe(settled)
    })

    test('a clamped short pane no longer lands inside the new block', async () => {
        setup(0)
        vp.scrollTop = 0
        let height = 300
        Object.defineProperty(vp, 'scrollHeight', { get: () => height })
        await c.loadOlderKeepingPlace(async () => { docTop += 900; height = 1200 })
        expect(docTop - vp.scrollTop).toBe(96)
    })
})

describe('chatBase refactor guards — edge cases that must stay identical', () => {
    test('_conv prefers a valid channel id over a valid user id, and treats non-integers as absent', () => {
        const c = chatBase('channel')

        c.$wire = { activeChannelId: 3, activeUserId: 8 }
        expect(c._conv()).toBe(3)

        c.$wire = { activeChannelId: 0, activeUserId: 8 }
        expect(c._conv()).toBe(0)

        for (const bad of ['4', 1.5, NaN, null, undefined, () => {}]) {
            c.$wire = { activeChannelId: bad, activeUserId: 8 }
            expect(c._conv()).toBe(8)
            c.$wire = { activeChannelId: bad, activeUserId: bad }
            expect(c._conv()).toBeNull()
        }
    })

    test('_conv reads $wire exactly once', () => {
        const c = chatBase('channel')
        let reads = 0
        Object.defineProperty(c, '$wire', { configurable: true, get() { reads++; return { activeChannelId: 2 } } })

        c._conv()

        expect(reads).toBe(1)
    })

    test('_rowsNewerThan returns 0 without a viewport, ignores rows without a numeric tail, and treats a missing marker as 0', () => {
        const c = chatBase('contact')
        expect(c._rowsNewerThan('message-1')).toBe(0)

        const vp = document.createElement('div')
        vp.innerHTML = '<div data-rf="message-4"></div><div data-rf="message-x"></div><div data-rf="channel-message-9"></div><div data-rf="message-2"></div>'
        document.body.appendChild(vp)
        c._msgViewportEl = vp

        expect(c._rowsNewerThan('message-3')).toBe(2)
        expect(c._rowsNewerThan(null)).toBe(3)
        expect(c._rowsNewerThan('zzz')).toBe(3)
    })

    test('_riseOnlyMuted: first call is never "muted", an unchanged total reuses the previous verdict, a muted-only rise is muted, any unmuted rise is not', () => {
        const c = chatBase('contact')
        const muted = new Set([1])
        c.$store = { sound: { isMuted: (id) => muted.has(id) } }
        let total = 0
        c._readUnread = () => total
        const rows = (map) => {
            document.body.innerHTML = ''
            for (const [id, n] of Object.entries(map)) {
                const el = document.createElement('div')
                el.dataset.unreadId = id
                el.dataset.unread = n
                document.body.appendChild(el)
            }
        }

        rows({ 1: 1, 2: 1 }); total = 2
        expect(c._riseOnlyMuted()).toBe(false)

        rows({ 1: 2, 2: 1 }); total = 3
        expect(c._riseOnlyMuted()).toBe(true)

        rows({ 1: 2, 2: 1 })
        expect(c._riseOnlyMuted()).toBe(true)

        rows({ 1: 2, 2: 2 }); total = 4
        expect(c._riseOnlyMuted()).toBe(false)
    })

    test('_pushMessage shows an identifiable incoming preview and falls back for ambiguous changes', () => {
        const c = chatBase('channel')
        document.body.innerHTML = '<div data-unread-id="1" data-unread="0" data-notify-name="گروه" data-notify-sender="علی" data-notify-body="سلام"></div><div data-unread-id="2" data-unread="0" data-notify-name="دیگر"></div>'
        const [first, second] = document.querySelectorAll('[data-unread-id]')
        expect(c._pushMessage(0, 'عمومی')).toEqual(['پیام جدید', 'عمومی'])
        first.dataset.unread = '1'
        expect(c._pushMessage(1, 'عمومی')).toEqual(['گروه', 'علی: سلام'])
        first.dataset.unread = '2'
        second.dataset.unread = '1'
        expect(c._pushMessage(3, 'عمومی')).toEqual(['پیام جدید', 'عمومی'])
        first.dataset.unread = '3'
        first.dataset.notifyMine = '1'
        expect(c._pushMessage(4, 'عمومی')).toEqual(['پیام جدید', 'عمومی'])
        first.dataset.notifyMine = '0'
        first.dataset.notifySender = ''
        first.dataset.notifyBody = 'پیوست'
        first.dataset.unread = '4'
        expect(c._pushMessage(5, 'عمومی')).toEqual(['گروه', 'پیوست'])
        first.dataset.unread = '5'
        expect(c._pushMessage(7, 'عمومی')).toEqual(['پیام جدید', 'عمومی'])
    })

    test('_settlePin stops on wheel/touchstart/pointerdown/keydown only', () => {
        vi.useFakeTimers()
        try {
            for (const type of ['wheel', 'touchstart', 'pointerdown', 'keydown', 'click', 'scroll']) {
                const c = chatBase('contact')
                const vp = document.createElement('div')
                document.body.appendChild(vp)
                const pin = vi.fn(() => true)
                c._settlePin(vp, pin)
                vi.advanceTimersByTime(5)
                vp.dispatchEvent(new Event(type))
                const before = pin.mock.calls.length
                vi.advanceTimersByTime(60)
                expect(pin.mock.calls.length === before).toBe(['wheel', 'touchstart', 'pointerdown', 'keydown'].includes(type))
            }
        } finally {
            vi.useRealTimers()
        }
    })
})

describe('chatBase _conv — the open conversation id survives Livewire\'s function fallback for foreign properties (added 2026-11-02)', () => {
    const livewireWire = (state) => new Proxy(state, { get: (t, p) => (p in t ? t[p] : () => {}) })

    test('returns whichever of the two ids is a real integer, and null when none is', () => {
        const c = chatBase('channel')

        c.$wire = livewireWire({ activeChannelId: 3 })
        expect(c._conv()).toBe(3)

        c.$wire = livewireWire({ activeUserId: 8 })
        expect(c._conv()).toBe(8)

        c.$wire = livewireWire({ activeChannelId: null })
        expect(c._conv()).toBeNull()
    })

    test('loadOlderKeepingPlace still restores the scroll position inside a channel, where activeUserId is not a property', async () => {
        const c = chatBase('channel')
        c.$nextTick = () => Promise.resolve()
        c.$wire = livewireWire({ activeChannelId: 3 })

        const vp = document.createElement('div')
        vp.id = 'msg-viewport'
        let height = 1000
        Object.defineProperty(vp, 'scrollHeight', { get: () => height })
        vp.scrollTop = 30
        document.body.appendChild(vp)

        await c.loadOlderKeepingPlace(async () => { height = 1700 })

        expect(vp.scrollTop).toBe(30 + 700 - 96)
    })
})

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

describe('chatBase send ghost', () => {
    const seconds = new Intl.NumberFormat('fa-IR');

    function withViewport() {
        const vp = document.createElement('div');
        vp.id = 'msg-viewport';
        vp.scrollTo = vi.fn();
        document.body.appendChild(vp);
        return vp;
    }

    test('_ghostShow returns null and does nothing when the viewport is missing', () => {
        const c = chatBase('contact');
        expect(c._ghostShow('hello')).toBeNull();
        expect(c._ghost).toBeNull();
        expect(document.querySelector('[data-ghost]')).toBeNull();
    });

    test('_ghostShow appends one hidden ghost as the last child and escapes HTML into text', () => {
        const c = chatBase('contact');
        const vp = withViewport();
        vp.appendChild(document.createElement('div'));
        const raw = '<img src=x onerror=alert(1)>';

        const el = c._ghostShow(raw);

        expect(el).toBe(vp.lastElementChild);
        expect(vp.querySelectorAll('[data-ghost]')).toHaveLength(1);
        expect(el.getAttribute('aria-hidden')).toBe('true');
        expect(el.querySelector('img')).toBeNull();
        expect(el.querySelector('[dir="auto"]').textContent).toBe(raw);
        expect(el.dataset.rf).toBeUndefined();
        expect(el.dataset.day).toBeUndefined();
        c._ghostHide();
    });

    test('_ghostHide removes the node, clears the timer, and is a no-op when called twice', () => {
        vi.useFakeTimers();
        try {
            const c = chatBase('contact');
            withViewport();
            const el = c._ghostShow('hello');
            const time = el.querySelector('time');

            c._ghostHide(el);
            vi.advanceTimersByTime(5000);

            expect(time.textContent).toBe('');
            expect(document.querySelector('[data-ghost]')).toBeNull();
            expect(c._ghost).toBeNull();
            expect(() => c._ghostHide()).not.toThrow();
            expect(() => c._ghostHide(el)).not.toThrow();
        } finally {
            vi.useRealTimers();
        }
    });

    test('the timer stays blank until one second, then shows whole Persian seconds', () => {
        vi.useFakeTimers();
        try {
            const c = chatBase('contact');
            withViewport();
            const el = c._ghostShow('hello');
            const time = el.querySelector('time');

            vi.advanceTimersByTime(999);
            expect(time.textContent).toBe('');

            vi.advanceTimersByTime(1);
            expect(time.textContent).toBe(`${seconds.format(1)} ث`);

            vi.advanceTimersByTime(1000);
            expect(time.textContent).toBe(`${seconds.format(2)} ث`);
            c._ghostHide();
        } finally {
            vi.useRealTimers();
        }
    });
});

describe('poll marker and day-picker fixes (2026-11-24)', () => {
    test('chars counts Unicode code points like the server max rule, so an emoji is one character', () => {
        const c = chatBase('contact');
        expect(c.chars('🙂')).toBe(1);
        expect(c.chars('a'.repeat(1999) + '🙂')).toBe(2000);
        expect(c.chars(null)).toBe(0);
    });

    test('a deleted last message is not an advance: only a larger row id counts as new', () => {
        const c = chatBase('contact');
        expect(c._markerAdvanced('message-13', 'message-12')).toBe(true);
        expect(c._markerAdvanced('message-12', 'message-13')).toBe(false);
        expect(c._markerAdvanced('message-12', 'message-12')).toBe(false);
    });

    test('dropping the Files cache always voids a pending request; an open panel that was loading is replaced by one fresh request', () => {
        const c = chatBase('contact');
        c.retryFiles = vi.fn();
        c._filesCache = { conv: 1, at: Date.now(), list: [1] };
        c._filesToken = 4;
        c.filesLoading = false;
        c.filesOpen = false;
        c._dropFiles();
        expect(c._filesCache).toBeNull();
        expect(c._filesToken).toBe(5);
        expect(c.retryFiles).not.toHaveBeenCalled();

        c.filesLoading = true;
        c.filesOpen = true;
        c._dropFiles();
        expect(c._filesToken).toBe(6);
        expect(c.filesLoading).toBe(false);
        expect(c.retryFiles).toHaveBeenCalledOnce();
    });

    test('two Jalali month headings never share a key even inside one Gregorian month', () => {
        const rows = chatBase('contact')._dayRows([{ day: '2026-03-23', id: 2 }, { day: '2026-03-19', id: 1 }]);
        const keys = rows.filter(r => r.header).map(r => r.day);
        expect(keys.length).toBe(2);
        expect(new Set(keys).size).toBe(2);
    });
});

describe('swipe-to-reply (initSwipeReply — touch-only, direction-locked, chatBase shared by channel/contact)', () => {
    const fire = (target, type, props = {}) => {
        const e = new Event(type, { bubbles: true, cancelable: true });
        Object.assign(e, { pointerType: 'touch', clientX: 0, clientY: 0, button: 0, ...props });
        target.dispatchEvent(e);
    };

    const instances = [];

    const start = () => {
        const c = chatBase('contact');
        c.startReply = vi.fn();
        c.initSwipeReply();
        instances.push(c);
        return c;
    };

    const fixture = (insideViewport = true) => {
        document.body.innerHTML = insideViewport
            ? `<div id="msg-viewport"><div data-rf="message-12"><div class="msg-bubble"></div><button data-body="plain text" data-sender="Ali"></button></div></div>`
            : `<div><div data-rf="message-12"><div class="msg-bubble"></div></div></div>`;
        return document.querySelector('.msg-bubble');
    };

    afterEach(() => {
        for (const c of instances.splice(0)) c._swipeAc?.abort();
    });

    test('a horizontal touch drag past the commit threshold replies with the row id, sender and body', () => {
        const bubble = fixture();
        const c = start();

        fire(bubble, 'pointerdown', { clientX: 100, clientY: 100 });
        fire(bubble, 'pointermove', { clientX: 150, clientY: 102 });
        expect(bubble.style.transform).toContain('translateX(50px)');

        fire(bubble, 'pointerup', { clientX: 150, clientY: 102 });
        expect(c.startReply).toHaveBeenCalledWith(12, 'Ali', 'plain text');
        expect(bubble.style.transform).toBe('');
    });

    test('a read-only bubble (no Reply button) never moves during the drag and never replies', () => {
        document.body.innerHTML = `<div id="msg-viewport"><div data-rf="message-12"><div class="msg-bubble"></div></div></div>`;
        const bubble = document.querySelector('.msg-bubble');
        const c = start();

        fire(bubble, 'pointerdown', { clientX: 100, clientY: 100 });
        fire(bubble, 'pointermove', { clientX: 160, clientY: 102 });
        expect(bubble.style.transform).toBe('');

        fire(bubble, 'pointerup', { clientX: 160, clientY: 102 });
        expect(c.startReply).not.toHaveBeenCalled();
    });

    test('a second finger cannot move or finish the first finger drag', () => {
        const bubble = fixture();
        const c = start();

        fire(bubble, 'pointerdown', { clientX: 100, clientY: 100, pointerId: 1 });
        fire(bubble, 'pointermove', { clientX: 400, clientY: 100, pointerId: 2 });
        expect(bubble.style.transform).toBe('');

        fire(bubble, 'pointerup', { clientX: 400, clientY: 100, pointerId: 2 });
        expect(c.startReply).not.toHaveBeenCalled();

        fire(bubble, 'pointermove', { clientX: 150, clientY: 102, pointerId: 1 });
        fire(bubble, 'pointerup', { clientX: 150, clientY: 102, pointerId: 1 });
        expect(c.startReply).toHaveBeenCalledWith(12, 'Ali', 'plain text');
    });

    test('a vertical drag never engages and never replies', () => {
        const bubble = fixture();
        const c = start();

        fire(bubble, 'pointerdown', { clientX: 100, clientY: 100 });
        fire(bubble, 'pointermove', { clientX: 104, clientY: 150 });
        expect(bubble.style.transform).toBe('');

        fire(bubble, 'pointerup', { clientX: 104, clientY: 150 });
        expect(c.startReply).not.toHaveBeenCalled();
    });

    test('an engaged drag under the commit threshold snaps back without replying', () => {
        const bubble = fixture();
        const c = start();

        fire(bubble, 'pointerdown', { clientX: 100, clientY: 100 });
        fire(bubble, 'pointermove', { clientX: 130, clientY: 100 });
        expect(bubble.style.transform).toContain('translateX(30px)');

        fire(bubble, 'pointerup', { clientX: 130, clientY: 100 });
        expect(c.startReply).not.toHaveBeenCalled();
        expect(bubble.style.transform).toBe('');
    });

    test('a mouse pointer, a row outside the viewport and an interactive target are all ignored', () => {
        const bubble = fixture();
        const c = start();

        fire(bubble, 'pointerdown', { pointerType: 'mouse', clientX: 100, clientY: 100 });
        fire(bubble, 'pointermove', { pointerType: 'mouse', clientX: 150, clientY: 100 });
        fire(bubble, 'pointerup', { pointerType: 'mouse', clientX: 150, clientY: 100 });

        const orphan = fixture(false);
        fire(orphan, 'pointerdown', { clientX: 100, clientY: 100 });
        fire(orphan, 'pointermove', { clientX: 150, clientY: 100 });
        fire(orphan, 'pointerup', { clientX: 150, clientY: 100 });

        fixture();
        const button = document.querySelector('button[data-body]');
        fire(button, 'pointerdown', { clientX: 100, clientY: 100 });
        fire(button, 'pointermove', { clientX: 150, clientY: 100 });
        fire(button, 'pointerup', { clientX: 150, clientY: 100 });

        expect(c.startReply).not.toHaveBeenCalled();
    });

    test('pointercancel releases the drag and the bubble returns without replying', () => {
        const bubble = fixture();
        const c = start();

        fire(bubble, 'pointerdown', { clientX: 100, clientY: 100 });
        fire(bubble, 'pointermove', { clientX: 160, clientY: 100 });
        fire(bubble, 'pointercancel', { clientX: 160, clientY: 100 });

        expect(bubble.style.transform).toBe('');
        expect(c.startReply).not.toHaveBeenCalled();

        fire(bubble, 'pointerup', { clientX: 160, clientY: 100 });
        expect(c.startReply).not.toHaveBeenCalled();
    });
});
