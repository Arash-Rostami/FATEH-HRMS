import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import channel from '../channel.js'
import { createWireMock, installWireProbe } from '../../mixins/__tests__/_wireMock.js'

beforeEach(() => { document.body.innerHTML = ''; });

// Force-override rather than "if missing": jsdom ships its own rAF whose timing is not
// guaranteed to fire within a short test-level await, which made this suite flaky.
global.requestAnimationFrame = (cb) => setTimeout(cb, 0);
global.cancelAnimationFrame = (id) => clearTimeout(id);

function withTextarea(value = '', selectionStart = value.length) {
    const ta = document.createElement('textarea');
    ta.id = 'msg-ta';
    ta.value = value;
    ta.selectionStart = ta.selectionEnd = selectionStart;
    ta.focus = vi.fn();
    document.body.appendChild(ta);
    return ta;
}

function withViewport() {
    const vp = document.createElement('div');
    vp.id = 'msg-viewport';
    document.body.appendChild(vp);
    return vp;
}

describe('channel() mentionMatches pure-logic filtering', () => {
    const names = ['Ali', 'Sara', 'Aliya', 'Bob', 'Alireza', 'Nina', 'Omid', 'Parsa', 'Reza', 'Sam'];

    const withMentions = (overrides = {}) => Object.assign(channel(), {
        $wire: { mentionMemberNames: names },
        mentionOpen: true,
        mentionQuery: '',
        ...overrides,
    });

    test('returns empty array when the picker is closed, regardless of query', () => {
        const c = withMentions({ mentionOpen: false, mentionQuery: 'ali' });
        expect(c.mentionMatches).toEqual([]);
    });

    test('returns the first 8 names unfiltered when the query is empty', () => {
        const c = withMentions();
        expect(c.mentionMatches).toEqual(names.slice(0, 8));
    });

    test('filters case-insensitively by substring match', () => {
        const c = withMentions({ mentionQuery: 'ali' });
        expect(c.mentionMatches).toEqual(['Ali', 'Aliya', 'Alireza']);
    });

    test('caps results at 8 even when more than 8 names match', () => {
        const manyAs = Array.from({ length: 12 }, (_, i) => `Ali${i}`);
        const c = withMentions({ $wire: { mentionMemberNames: manyAs }, mentionQuery: 'ali' });
        expect(c.mentionMatches).toHaveLength(8);
        expect(c.mentionMatches).toEqual(manyAs.slice(0, 8));
    });

    test('returns an empty array when nothing matches', () => {
        const c = withMentions({ mentionQuery: 'zzz' });
        expect(c.mentionMatches).toEqual([]);
    });
});

describe('channel() _overLimit', () => {
    test('allows a body at or under the 4000-char limit', () => {
        const c = channel();
        expect(c._overLimit('x'.repeat(4000))).toBe(false);
    });

    test('rejects and toasts a body over the 4000-char limit', () => {
        const c = channel();
        let toasted = null;
        window.addEventListener('toast', (e) => { toasted = e.detail; }, { once: true });

        expect(c._overLimit('x'.repeat(4001))).toBe(true);
        expect(toasted?.type).toBe('warning');
    });
});

describe('channel() detectMention — @-segment extraction while typing', () => {
    test('opens the picker on a bare @ preceded by whitespace/start-of-line', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.resetPollInterval = vi.fn();
        c.pingTyping = vi.fn();

        const ta = withTextarea('hi @al', 6);
        c.detectMention({ target: ta });

        expect(c.mentionOpen).toBe(true);
        expect(c.mentionQuery).toBe('al');
        expect(c.$wire.loadMentionMemberNames).toHaveBeenCalledOnce();
    });

    test('closes the picker when there is no @ before the cursor', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.resetPollInterval = vi.fn();
        c.pingTyping = vi.fn();
        c.mentionOpen = true;

        const ta = withTextarea('hello', 5);
        c.detectMention({ target: ta });

        expect(c.mentionOpen).toBe(false);
    });

    test('closes the picker once the @-segment contains whitespace (mention finished/abandoned)', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.resetPollInterval = vi.fn();
        c.pingTyping = vi.fn();
        c.mentionOpen = true;

        const ta = withTextarea('@ali done ', 10);
        c.detectMention({ target: ta });

        expect(c.mentionOpen).toBe(false);
    });

    test('closes the picker when the @ is not itself preceded by whitespace (e.g. an email-like token)', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.resetPollInterval = vi.fn();
        c.pingTyping = vi.fn();

        const ta = withTextarea('a@b', 3);
        c.detectMention({ target: ta });

        expect(c.mentionOpen).toBe(false);
    });

    test('does not re-fetch member names on every keystroke while already open', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.resetPollInterval = vi.fn();
        c.pingTyping = vi.fn();
        c.mentionOpen = true;

        const ta = withTextarea('@a', 2);
        c.detectMention({ target: ta });

        expect(c.$wire.loadMentionMemberNames).not.toHaveBeenCalled();
    });
});

describe('channel() onComposerKeydown — mention picker navigation + send-on-enter', () => {
    function withOpenPicker(matchCount) {
        const c = channel();
        c.mentionOpen = true;
        c.mentionActiveIndex = 0;
        Object.defineProperty(c, 'mentionMatches', { get: () => Array.from({ length: matchCount }, (_, i) => `n${i}`) });
        c.pickMention = vi.fn();
        c.sendMessage = vi.fn();
        return c;
    }

    const keyEvent = (key, shiftKey = false) => ({ key, shiftKey, preventDefault: vi.fn() });

    test('ArrowDown/ArrowUp wrap around the match list', () => {
        const c = withOpenPicker(3);
        c.onComposerKeydown(keyEvent('ArrowDown'));
        expect(c.mentionActiveIndex).toBe(1);
        c.onComposerKeydown(keyEvent('ArrowUp'));
        expect(c.mentionActiveIndex).toBe(0);
        c.onComposerKeydown(keyEvent('ArrowUp'));
        expect(c.mentionActiveIndex).toBe(2);
    });

    test('Enter/Tab pick the active match instead of sending', () => {
        const c = withOpenPicker(3);
        c.onComposerKeydown(keyEvent('Enter'));
        expect(c.pickMention).toHaveBeenCalledWith(0);
        expect(c.sendMessage).not.toHaveBeenCalled();
    });

    test('Escape closes the picker without picking', () => {
        const c = withOpenPicker(3);
        c.onComposerKeydown(keyEvent('Escape'));
        expect(c.mentionOpen).toBe(false);
        expect(c.pickMention).not.toHaveBeenCalled();
    });

    test('plain Enter (no shift, picker closed) sends the message', () => {
        const c = channel();
        c.sendMessage = vi.fn();
        c.onComposerKeydown(keyEvent('Enter'));
        expect(c.sendMessage).toHaveBeenCalledOnce();
    });

    test('Shift+Enter does not send (newline)', () => {
        const c = channel();
        c.sendMessage = vi.fn();
        c.onComposerKeydown(keyEvent('Enter', true));
        expect(c.sendMessage).not.toHaveBeenCalled();
    });

    test('an empty match list falls through to the plain-Enter send path even while "open"', () => {
        const c = withOpenPicker(0);
        c.onComposerKeydown(keyEvent('Enter'));
        expect(c.sendMessage).toHaveBeenCalledOnce();
    });
});

describe('channel() pickMention / openMentionPicker — textarea splice logic', () => {
    test('pickMention replaces the @query with the chosen name and repositions the caret', () => {
        const c = channel();
        c.$nextTick = (cb) => cb();
        Object.defineProperty(c, 'mentionMatches', { get: () => ['Alireza'] });
        const ta = withTextarea('hi @ali', 7);
        // wire:model mirrors composer.body onto the textarea reactively in the real browser;
        // simulate that sync so the caret math (which reads the post-splice ta.value) is testable.
        c.$wire = createWireMock({ set: vi.fn((path, value) => { if (path === 'composer.body') ta.value = value; }) });

        c.pickMention(0);

        expect(c.$wire.set).toHaveBeenCalledWith('composer.body', 'hi @Alireza ');
        expect(c.mentionOpen).toBe(false);
        expect(ta.selectionStart).toBe('hi @Alireza '.length);
    });

    test('openMentionPicker inserts a leading @ (with a space if needed) and fetches member names', () => {
        const c = channel();
        c.$wire = createWireMock();
        const ta = withTextarea('hi', 2);

        c.openMentionPicker();

        expect(ta.value).toBe('hi @');
        expect(c.$wire.loadMentionMemberNames).toHaveBeenCalledOnce();
        expect(c.mentionOpen).toBe(true);
    });
});

describe('channel() island-scoped action wiring — wire-capture correctness', () => {
    test('doCancelInvite captures $wire ONCE and reuses it for both the cancelInvite call and the trailing sidebar refresh (the regression caught 2026-10-01)', async () => {
        const c = channel();
        const wire = installWireProbe(c);
        c.cancelInviteeId = 9;

        await c.doCancelInvite(1, 2);

        expect(wire._islands.messages.cancelInvite).toHaveBeenCalledWith(1, 2);
        expect(wire._islands.sidebar.refreshUnread).toHaveBeenCalledOnce();
        expect(c.cancelInviteeId).toBeNull();
    });

    test('closeCreate/leaveChannel/createChannel/joinChannel/toggleBrowse/openCreate each read this.$wire only ONCE and reuse it for both calls (toggleBrowse/openCreate fixed 2026-10-01, Fable-5 review)', async () => {
        for (const [method, args] of [['closeCreate', []], ['leaveChannel', [5]], ['createChannel', []], ['joinChannel', [5]], ['toggleBrowse', []], ['openCreate', []]]) {
            const c = channel();
            c.syncChannelCount = vi.fn();
            c.$nextTick = (cb) => cb();
            const wire = installWireProbe(c);

            await c[method](...args);

            expect(wire._islands.messages[method]).toHaveBeenCalled();
            expect(wire._islands.sidebar.refreshUnread).toHaveBeenCalledOnce();
        }
    });

    test('leaveChannel no longer gates on a native confirm() — confirmation now happens upstream via the shared open-confirmation modal before this method is ever invoked', async () => {
        const c = channel();
        global.confirm = () => false;
        const wire = installWireProbe(c);

        await c.leaveChannel(5);

        expect(wire._islands.messages.leaveChannel).toHaveBeenCalled();
    });

    test('leaveChannel guards a falsy id', async () => {
        const c = channel();
        const wire = installWireProbe(c);

        await c.leaveChannel(0);

        expect(wire.$island).not.toHaveBeenCalled();
    });

    test('backToList reads this.$wire only ONCE: sets mobileShowChat locally then island-scopes the backToList + sidebar refresh via that same reference', () => {
        const c = channel();
        const wire = installWireProbe(c);

        c.backToList();

        expect(wire.set).toHaveBeenCalledWith('mobileShowChat', false, false);
        expect(wire._islands.messages.backToList).toHaveBeenCalledOnce();
    });

    test('selectChannel resets per-channel UI state, clears the reply draft, then chains select → sidebar refresh → scroll', async () => {
        const c = channel();
        c.$wire = createWireMock();
        c.$nextTick = (cb) => { cb(); return Promise.resolve(); };
        c.scrollToBottom = vi.fn();
        c.scheduleChipsFade = vi.fn();
        c.resetAutoResize = vi.fn();
        c._repollNow = vi.fn();
        c.replyingTo = { id: 1 };
        c.activeSender = 'Ali';
        c.isEditing = true;

        await c.selectChannel(5);

        expect(c.replyingTo).toBeNull();
        expect(c.activeSender).toBeNull();
        expect(c.isEditing).toBe(false);
        expect(c.$wire.cancelReply).toHaveBeenCalledOnce();
        expect(c.$wire._islands.messages.selectChannel).toHaveBeenCalledWith(5);
        expect(c.$wire._islands.sidebar.refreshUnread).toHaveBeenCalledOnce();
    });

    test('selectChannel guards a falsy id', async () => {
        const c = channel();
        c.$wire = createWireMock();
        c._repollNow = vi.fn();
        await c.selectChannel(0);
        expect(c.$wire.$island).not.toHaveBeenCalled();
    });
});

describe('channel() edit flow', () => {
    test('startEdit populates local edit state and requests the server-authoritative reload', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.startEdit(7, 'original body');

        expect(c.isEditing).toBe(true);
        expect(c.editingMsgId).toBe(7);
        expect(c.editingBody).toBe('original body');
        expect(c.editingOriginal).toBe('original body');
        expect(c.$wire.cancelReply).toHaveBeenCalledOnce();
        expect(c.$wire.editMessage).toHaveBeenCalledWith(7);
    });

    test('cancelEdit clears all four edit fields and notifies the server', () => {
        const c = channel();
        c.$wire = createWireMock();
        Object.assign(c, { isEditing: true, editingMsgId: 7, editingBody: 'x', editingOriginal: 'x' });

        c.cancelEdit();

        expect(c.isEditing).toBe(false);
        expect(c.editingMsgId).toBeNull();
        expect(c.editingBody).toBe('');
        expect(c.editingOriginal).toBe('');
        expect(c.$wire.cancelEdit).toHaveBeenCalledOnce();
    });

    test('saveEdit no-ops for a stale id, an unchanged body, or an over-limit body', async () => {
        const c = channel();
        c.$wire = createWireMock();
        c.editingMsgId = 7;
        c.editingOriginal = 'same';
        c.editingBody = 'same';

        await c.saveEdit(999); // stale id
        await c.saveEdit(7);   // unchanged body
        expect(c.$wire.$island).not.toHaveBeenCalled();

        c.editingBody = 'x'.repeat(4001);
        c.toast = vi.fn();
        await c.saveEdit(7);
        expect(c.$wire.$island).not.toHaveBeenCalled();
        expect(c.toast).toHaveBeenCalledWith('متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.', 'warning');
    });

    test('saveEdit reads this.$wire for the capture + the internal _isl() helper (2 pre-await reads, both the same object) then syncs the sidebar with that captured reference — never a 3rd, post-await read', async () => {
        const c = channel();
        const wire = installWireProbe(c, {}, 2);
        c._syncSidebarAfterOwnAction = vi.fn();
        c.editingMsgId = 7;
        c.editingOriginal = 'old';
        c.editingBody = 'new';

        await c.saveEdit(7);

        expect(wire._islands.messages.saveEdit).toHaveBeenCalledWith(7, 'new');
        expect(c._syncSidebarAfterOwnAction).toHaveBeenCalledWith(wire);
    });

    test('saveEdit toasts a generic error if the island call rejects', async () => {
        const c = channel();
        c.$wire = createWireMock();
        c.$wire._islands.messages = { saveEdit: vi.fn(() => Promise.reject(new Error('boom'))) };
        c.toast = vi.fn();
        c.editingMsgId = 7;
        c.editingOriginal = 'old';
        c.editingBody = 'new';

        await c.saveEdit(7);

        expect(c.toast).toHaveBeenCalledWith('خطا در ارتباط با سرور.', 'error');
    });
});

describe('channel() reply/delete/sender-filter state', () => {
    test('startReply sets the reply draft, clears the quote chip, and focuses the composer', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.$nextTick = (cb) => cb();
        const ta = withTextarea();

        c.startReply(3, 'Ali', 'hello');

        expect(c.replyingTo).toEqual({ id: 3, sender: { name: 'Ali' }, body: 'hello' });
        expect(c.$wire.replyTo).toHaveBeenCalledWith(3);
        expect(c.$wire.cancelEdit).toHaveBeenCalledOnce();
        expect(ta.focus).toHaveBeenCalledOnce();
    });

    test('confirmDelete sets deletingId and clears any in-progress reply/edit', () => {
        const c = channel();
        c.replyingTo = { id: 1 };
        c.isEditing = true;
        c.confirmDelete(9);
        expect(c.deletingId).toBe(9);
        expect(c.replyingTo).toBeNull();
        expect(c.isEditing).toBe(false);
    });

    test('confirmCancelInvite just stores the target id', () => {
        const c = channel();
        c.confirmCancelInvite(4);
        expect(c.cancelInviteeId).toBe(4);
    });

    test('clearSenderFilter is a no-op when no sender is active, otherwise clears it via the messages island', async () => {
        const c = channel();
        c.$wire = createWireMock();
        c.$nextTick = (cb) => cb();
        c.scrollToBottom = vi.fn();

        await c.clearSenderFilter();
        expect(c.$wire.$island).not.toHaveBeenCalled();

        c.activeSender = 'Ali';
        await c.clearSenderFilter();
        expect(c.activeSender).toBeNull();
        expect(c.$wire._islands.messages.clearFocus).toHaveBeenCalledOnce();
    });

    test('toggleActions opens then closes the same row', () => {
        const c = channel();
        c.toggleActions(5);
        expect(c.openActionsId).toBe(5);
        c.toggleActions(5);
        expect(c.openActionsId).toBeNull();
    });

    test('closeOverlays routes create/browse-mode exit through the island-scoped wrappers, never a bare $wire.set (fixed 2026-10-01, Fable-5 review — a bare set() skips the island, leaving the pane stuck on screen)', () => {
        const c = channel();
        c.$wire = createWireMock();
        c.closeCreate = vi.fn();
        c.toggleBrowse = vi.fn();
        c.$wire.set.mockClear();

        c.$wire.createMode = false;
        c.$wire.browseMode = false;
        c.closeOverlays();
        expect(c.closeCreate).not.toHaveBeenCalled();
        expect(c.toggleBrowse).not.toHaveBeenCalled();

        c.$wire.createMode = true;
        c.closeOverlays();
        expect(c.closeCreate).toHaveBeenCalledOnce();

        c.$wire.createMode = false;
        c.$wire.browseMode = true;
        c.closeOverlays();
        expect(c.toggleBrowse).toHaveBeenCalledOnce();

        expect(c.$wire.set).not.toHaveBeenCalledWith('createMode', false);
        expect(c.$wire.set).not.toHaveBeenCalledWith('browseMode', false);
    });
});

describe('channel() _bindViewport — re-binds across channel switches instead of going stale (fixed 2026-10-01)', () => {
    test('sets _msgViewportEl to null without throwing when #msg-viewport is absent (e.g. mounted with no channel open)', () => {
        const c = channel();
        expect(() => c._bindViewport()).not.toThrow();
        expect(c._msgViewportEl).toBeNull();
    });

    test('binds to #msg-viewport once it exists — the exact case that was broken: nothing open at mount, a channel opened afterward', () => {
        const c = channel();
        c._bindViewport();
        expect(c._msgViewportEl).toBeNull();

        const vp = withViewport();
        c._bindViewport();

        expect(c._msgViewportEl).toBe(vp);
    });

    test('re-binding to a NEW #msg-viewport (channel closed, a different one reopened) points at the new element, not the stale detached one', () => {
        const c = channel();
        const first = withViewport();
        c._bindViewport();
        expect(c._msgViewportEl).toBe(first);

        first.remove();
        const second = withViewport();
        c._bindViewport();

        expect(c._msgViewportEl).toBe(second);
        expect(c._msgViewportEl).not.toBe(first);
    });

    test('re-binding does not stack a second scroll listener on the same element (would otherwise double-fire loadOlder on one scroll)', async () => {
        const c = channel();
        c.$wire = createWireMock({ hasOlder: true });
        const vp = withViewport();
        Object.defineProperties(vp, {
            scrollTop: { value: 10, configurable: true },
            scrollHeight: { value: 1000, configurable: true },
            clientHeight: { value: 500, configurable: true },
        });

        c._bindViewport();
        c._bindViewport(); // re-bind to the SAME element — must replace, not add, the listener

        vp.dispatchEvent(new Event('scroll'));
        await new Promise((r) => setTimeout(r, 10));

        expect(c.$wire._islands.messages.loadOlder).toHaveBeenCalledTimes(1);
    });

    test("init() binds the viewport on mount", () => {
        const c = channel();
        c.$wire = createWireMock();
        c.$watch = vi.fn();
        c.initFancybox = vi.fn();
        const vp = withViewport();

        c.init();

        expect(c._msgViewportEl).toBe(vp);
        c.destroy();
    });

    test('backToList clears _msgViewportEl so stale-element reads cannot happen while no channel is open', () => {
        const c = channel();
        c.$wire = createWireMock();
        c._msgViewportEl = withViewport();

        c.backToList();

        expect(c._msgViewportEl).toBeNull();
    });
});

describe('channel() chip auto-fade', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    test('scheduleChipsFade hides the chip strip after 3s; revealChips cancels a pending fade', () => {
        const c = channel();
        c.scheduleChipsFade();
        expect(c.chipsVisible).toBe(true);

        vi.advanceTimersByTime(3000);
        expect(c.chipsVisible).toBe(false);

        c.chipsVisible = true;
        c.scheduleChipsFade();
        c.revealChips();
        vi.advanceTimersByTime(3000);
        expect(c.chipsVisible).toBe(true);
    });
});

describe('channel() DOM read helpers', () => {
    test('syncChannelCount reads data-channel-count, defaulting to 0 when absent/invalid', () => {
        const c = channel();
        expect(() => c.syncChannelCount()).not.toThrow();
        expect(c.channelCount).toBe(0);

        const el = document.createElement('div');
        el.setAttribute('data-channel-count', '7');
        document.body.appendChild(el);
        c.syncChannelCount();
        expect(c.channelCount).toBe(7);
    });

    test('syncPushNotify fires a push notification only when unread count increased since last check', () => {
        const c = channel();
        c.$store = { push: { notify: vi.fn() } };
        const el = document.createElement('div');
        el.setAttribute('data-total-unread', '2');
        document.body.appendChild(el);

        c.syncPushNotify();
        expect(c.$store.push.notify).not.toHaveBeenCalled();

        el.setAttribute('data-total-unread', '5');
        c.syncPushNotify();
        expect(c.$store.push.notify).toHaveBeenCalledOnce();

        el.setAttribute('data-total-unread', '5');
        c.syncPushNotify();
        expect(c.$store.push.notify).toHaveBeenCalledOnce();
    });
});
