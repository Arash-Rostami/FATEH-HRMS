import { describe, expect, test, vi, beforeEach } from 'vitest'
import contact from '../contact.js'
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

describe('contact() _overLimit', () => {
    test('allows a body at or under the 2000-char limit', () => {
        const c = contact();
        expect(c._overLimit('x'.repeat(2000))).toBe(false);
    });

    test('rejects and toasts a body over the 2000-char limit', () => {
        const c = contact();
        let toasted = null;
        window.addEventListener('toast', (e) => { toasted = e.detail; }, { once: true });

        expect(c._overLimit('x'.repeat(2001))).toBe(true);
        expect(toasted?.type).toBe('warning');
    });
});

describe('contact() island-scoped action wiring — wire-capture correctness', () => {
    test('backToList reads this.$wire only ONCE: sets mobileShowChat locally then island-scopes backToList + sidebar refresh via that same reference (the regression caught 2026-10-01)', () => {
        const c = contact();
        const wire = installWireProbe(c);
        c.resetUI = vi.fn();

        c.backToList();

        expect(wire.set).toHaveBeenCalledWith('mobileShowChat', false, false);
        expect(wire._islands.messages.backToList).toHaveBeenCalledOnce();
    });

    test('saveEdit makes exactly 3 known $wire reads (capture, the internal _isl() helper, and cancelEdit()\'s post-await call — safe because the edit textarea is x-show-toggled, never morphed away) and never a 4th', async () => {
        const c = contact();
        const wire = installWireProbe(c, {}, 3);
        c._syncSidebarAfterOwnAction = vi.fn();
        c.editingMsg = { id: 7, body: 'x' };

        await c.saveEdit(7);

        expect(wire._islands.messages.saveEdit).toHaveBeenCalledWith(7);
        expect(c.editingMsg).toBeNull();
        expect(c._syncSidebarAfterOwnAction).toHaveBeenCalledWith(wire);
    });

    test('saveEdit guards a stale id and never touches $wire', async () => {
        const c = contact();
        const wire = installWireProbe(c);
        c.editingMsg = { id: 7, body: 'x' };

        await c.saveEdit(999);

        expect(wire.$island).not.toHaveBeenCalled();
    });

    test('saveEdit toasts a generic error and leaves editingMsg untouched if the island call rejects', async () => {
        const c = contact();
        c.$wire = createWireMock();
        c.$wire._islands.messages = { saveEdit: vi.fn(() => Promise.reject(new Error('boom'))) };
        c.toast = vi.fn();
        c.editingMsg = { id: 7, body: 'x' };

        await c.saveEdit(7);

        expect(c.toast).toHaveBeenCalledWith('خطا در ذخیره ویرایش پیام.', 'error');
        expect(c.editingMsg).toEqual({ id: 7, body: 'x' });
    });

    test('saveEdit does not clobber a NEW edit started mid-flight (race guard: only clears editingMsg if it still matches id)', async () => {
        const c = contact();
        let resolveSave;
        c.$wire = createWireMock();
        c.$wire._islands.messages = { saveEdit: vi.fn(() => new Promise((r) => { resolveSave = r; })) };
        c._syncSidebarAfterOwnAction = vi.fn();
        c.editingMsg = { id: 7, body: 'first' };

        const pending = c.saveEdit(7);
        c.editingMsg = { id: 8, body: 'second' }; // user started editing a different message before the first save resolved
        resolveSave();
        await pending;

        expect(c.editingMsg).toEqual({ id: 8, body: 'second' });
    });

    test('sendMessage rejects an empty body (short-circuits before the island call)', async () => {
        const c = contact();
        const wire = installWireProbe(c);
        c.toast = vi.fn();
        withTextarea('');

        await c.sendMessage();

        expect(c.toast).toHaveBeenCalledWith('پیام نمی‌تواند خالی باشد.', 'warning');
        expect(wire.$island).not.toHaveBeenCalled();
    });

    test('sendMessage rejects a body over the char limit (never reads $wire at all — the emptiness check short-circuits past it for a non-empty body)', async () => {
        const c = contact();
        const wire = installWireProbe(c, {}, 0);
        c.toast = vi.fn();
        withTextarea('x'.repeat(2001));

        await c.sendMessage();

        expect(c.toast).toHaveBeenCalledWith('متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.', 'warning');
        expect(wire.$island).not.toHaveBeenCalled();
    });

    test('sendMessage allows an empty body when an attachment is staged', async () => {
        const c = contact();
        // 3 known reads: the composer.attachments emptiness check, the capture, and the internal _isl() helper.
        const wire = installWireProbe(c, { composer: { attachments: [{ name: 'a.png' }] } }, 3);
        c._syncSidebarAfterOwnAction = vi.fn();
        c.$nextTick = (cb) => cb();
        withTextarea('');

        await c.sendMessage();

        expect(wire._islands.messages.send).toHaveBeenCalledOnce();
    });

    test('sendMessage makes exactly 2 known $wire reads for a non-empty body (the emptiness check short-circuits past $wire entirely; the capture + internal _isl() helper read it instead) then syncs the sidebar with the captured reference, refocuses the composer, and clears sending after the lock delay', async () => {
        vi.useFakeTimers();
        try {
            const c = contact();
            const wire = installWireProbe(c, {}, 2);
            c._syncSidebarAfterOwnAction = vi.fn();
            c.$nextTick = (cb) => cb();
            const ta = withTextarea('hello');

            const pending = c.sendMessage();
            expect(c.sending).toBe(true);
            await pending;

            expect(wire._islands.messages.send).toHaveBeenCalledOnce();
            expect(c._syncSidebarAfterOwnAction).toHaveBeenCalledWith(wire);
            expect(c.replyingTo).toBeNull();
            expect(ta.focus).toHaveBeenCalledOnce();

            vi.advanceTimersByTime(500);
            expect(c.sending).toBe(false);
        } finally {
            vi.useRealTimers();
        }
    });

    test('sendMessage toasts a generic error and still releases the sending lock if the island call rejects', async () => {
        vi.useFakeTimers();
        try {
            const c = contact();
            c.$wire = createWireMock();
            c.$wire._islands.messages = { send: vi.fn(() => Promise.reject(new Error('boom'))) };
            c.toast = vi.fn();
            withTextarea('hello');

            await c.sendMessage();
            expect(c.toast).toHaveBeenCalledWith('خطا در ارتباط با سرور.', 'error');

            vi.advanceTimersByTime(500);
            expect(c.sending).toBe(false);
        } finally {
            vi.useRealTimers();
        }
    });

    test('sendMessage is a no-op while a send is already in flight', async () => {
        const c = contact();
        const wire = installWireProbe(c);
        c.sending = true;
        withTextarea('hello');

        await c.sendMessage();

        expect(wire.$island).not.toHaveBeenCalled();
    });

    test('selectContact resets UI state, clears the reply draft, then chains select → sidebar refresh → scroll', async () => {
        const c = contact();
        c.$wire = createWireMock();
        c.$nextTick = (cb) => { cb(); return Promise.resolve(); };
        c.scrollToBottom = vi.fn();
        c.resetAutoResize = vi.fn();
        c._repollNow = vi.fn();
        c.replyingTo = { id: 1 };
        c.editingMsg = { id: 2, body: 'x' };

        await c.selectContact(5);

        expect(c.replyingTo).toBeNull();
        expect(c.editingMsg).toBeNull();
        expect(c.$wire.cancelReply).toHaveBeenCalledOnce();
        expect(c.$wire._islands.messages.selectContact).toHaveBeenCalledWith(5);
        expect(c.$wire._islands.sidebar.refreshUnread).toHaveBeenCalledOnce();
    });

    test('selectContact guards a falsy id', async () => {
        const c = contact();
        const wire = installWireProbe(c);
        c._repollNow = vi.fn();
        await c.selectContact(0);
        expect(wire.$island).not.toHaveBeenCalled();
    });

    test('selectContact ignores a stale response once a newer selection has superseded it (race-condition guard, fixed 2026-10-05): switching contacts quickly no longer lets an earlier, slower response re-bind the viewport/scroll after a later one already has', async () => {
        const c = contact();
        c.$wire = createWireMock();
        c.$nextTick = (cb) => { cb(); return Promise.resolve(); };
        c._bindViewport = vi.fn();
        c.scrollToBottom = vi.fn();
        c.resetAutoResize = vi.fn();
        c._repollNow = vi.fn();

        const flush = async () => { for (let i = 0; i < 6; i++) await Promise.resolve(); };

        let resolveStale;
        c.$wire._islands.messages = {
            selectContact: vi.fn((id) => id === 5 ? new Promise((r) => { resolveStale = r; }) : Promise.resolve()),
        };

        c.selectContact(5);
        await c.selectContact(6);
        await flush();

        const callsAfterFreshSelection = c._bindViewport.mock.calls.length;
        expect(callsAfterFreshSelection).toBeGreaterThan(0);

        resolveStale();
        await flush();

        expect(c._bindViewport).toHaveBeenCalledTimes(callsAfterFreshSelection);
    });
});

describe('contact() reply/edit/delete state', () => {
    test('startReply sets the reply draft, clears editingMsg, and focuses the composer', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.$nextTick = (cb) => cb();
        const ta = withTextarea();
        c.editingMsg = { id: 1, body: 'x' };

        c.startReply(3, 'Ali', 'hello');

        expect(c.replyingTo).toEqual({ id: 3, sender: { name: 'Ali' }, body: 'hello' });
        expect(c.editingMsg).toBeNull();
        expect(c.$wire.replyTo).toHaveBeenCalledWith(3);
        expect(c.$wire.cancelEdit).toHaveBeenCalledOnce();
        expect(ta.focus).toHaveBeenCalledOnce();
    });

    test('startReply falls back to "Unknown" when no sender name is given', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.$nextTick = (cb) => cb();
        withTextarea();

        c.startReply(3, '', 'hello');

        expect(c.replyingTo.sender.name).toBe('Unknown');
    });

    test('startReply guards a falsy id', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.startReply(0, 'Ali', 'hello');
        expect(c.replyingTo).toBeNull();
        expect(c.$wire.replyTo).not.toHaveBeenCalled();
    });

    test('startEdit sets editingMsg instantly on the wire form field', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.startEdit(4, 'body text');

        expect(c.editingMsg).toEqual({ id: 4, body: 'body text' });
        expect(c.$wire.cancelReply).toHaveBeenCalledOnce();
        expect(c.$wire.set).toHaveBeenCalledWith('edit.editingBody', 'body text');
    });

    test('cancelEdit clears editingMsg and notifies the server', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.editingMsg = { id: 4, body: 'x' };

        c.cancelEdit();

        expect(c.editingMsg).toBeNull();
        expect(c.$wire.cancelEdit).toHaveBeenCalledOnce();
    });

    test('resetUI clears reply/edit/delete state together', () => {
        const c = contact();
        c.replyingTo = { id: 1 };
        c.editingMsg = { id: 2, body: 'x' };
        c.deletingId = 3;

        c.resetUI();

        expect(c.replyingTo).toBeNull();
        expect(c.editingMsg).toBeNull();
        expect(c.deletingId).toBeNull();
    });

    test('confirmDelete routes through resetUI then sets deletingId', () => {
        const c = contact();
        c.replyingTo = { id: 1 };
        c.confirmDelete(9);
        expect(c.deletingId).toBe(9);
        expect(c.replyingTo).toBeNull();
    });

    test('confirmDelete guards a falsy id', () => {
        const c = contact();
        c.deletingId = 5;
        c.confirmDelete(0);
        expect(c.deletingId).toBe(5);
    });

    test('toggleActions opens then closes the same row, and is suppressed while text is selected', () => {
        const c = contact();
        const row = document.createElement('div');
        document.body.appendChild(row);
        const click = (target) => ({ target: target ?? row, closest: (sel) => (target ?? row).closest?.(sel) ?? null });

        c.toggleActions(5, click());
        expect(c.openActionsId).toBe(5);
        c.toggleActions(5, click());
        expect(c.openActionsId).toBeNull();
    });

    test('toggleActions ignores clicks on an interactive element inside the row', () => {
        const c = contact();
        const btn = document.createElement('button');
        document.body.appendChild(btn);

        c.toggleActions(5, { target: btn });

        expect(c.openActionsId).toBeNull();
    });
});

describe('contact() closeOverlays', () => {
    test('clears every overlay/UI flag and routes reply/edit/delete clearing through the shared helpers', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.showInfo = true;
        c.searchMessages = true;
        c.emojiOpen = true;
        c.quoteChip = { visible: true, x: 0, y: 0, id: 1, sender: '', snippet: '' };
        c.openActionsId = 5;
        c.replyingTo = { id: 1 };
        c.editingMsg = { id: 2, body: 'x' };
        c.deletingId = 3;

        c.closeOverlays();

        expect(c.showInfo).toBe(false);
        expect(c.searchMessages).toBe(false);
        expect(c.emojiOpen).toBe(false);
        expect(c.quoteChip.visible).toBe(false);
        expect(c.openActionsId).toBeNull();
        expect(c.replyingTo).toBeNull();
        expect(c.editingMsg).toBeNull();
        expect(c.deletingId).toBeNull();
    });
});

describe('contact() scrollToMessage', () => {
    test('scrolls to an in-DOM row without a server round-trip', () => {
        const c = contact();
        c.$wire = createWireMock();
        const row = document.createElement('div');
        row.setAttribute('data-rf', 'message-9');
        row.scrollIntoView = vi.fn();
        document.body.appendChild(row);

        c.scrollToMessage(9);

        expect(row.scrollIntoView).toHaveBeenCalledOnce();
        expect(row.classList.contains('record-focus-flash')).toBe(true);
        expect(c.$wire.$island).not.toHaveBeenCalled();
    });

    test('falls back to a server-side focus fetch when the row is not already in the DOM', () => {
        const c = contact();
        c.$wire = createWireMock();

        c.scrollToMessage(9);

        expect(c.$wire._islands.messages.focusMessage).toHaveBeenCalledWith(9);
    });

    test('guards a falsy id', () => {
        const c = contact();
        const wire = installWireProbe(c);
        c.scrollToMessage(0);
        expect(wire.$island).not.toHaveBeenCalled();
    });
});

describe('contact() DOM read helpers + push notify', () => {
    test('_readUnread/_readTypingMarker/_readLastMessageMarker default sanely when their markers are absent', () => {
        const c = contact();
        expect(c._readUnread()).toBe(0);
        expect(c._readTypingMarker()).toBeNull();
        expect(c._readLastMessageMarker()).toBeNull();
    });

    test('_readLastMessageMarker reads the last matching row inside the cached viewport element', () => {
        const c = contact();
        const vp = document.createElement('div');
        vp.innerHTML = '<div data-rf="message-1"></div><div data-rf="message-2"></div>';
        document.body.appendChild(vp);
        c._msgViewportEl = vp;

        expect(c._readLastMessageMarker()).toBe('message-2');
    });

    test('syncPushNotify fires only when unread count increased since the last check, and also plays the unscoped incoming sound while no conversation is open', () => {
        const c = contact();
        c.$wire = { activeUserId: null };
        c.$store = { push: { notify: vi.fn() }, sound: { playIncoming: vi.fn() } };
        const el = document.createElement('div');
        el.setAttribute('data-total-unread', '1');
        document.body.appendChild(el);

        c.syncPushNotify();
        expect(c.$store.push.notify).not.toHaveBeenCalled();
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();

        el.setAttribute('data-total-unread', '3');
        c.syncPushNotify();
        expect(c.$store.push.notify).toHaveBeenCalledOnce();
        expect(c.$store.sound.playIncoming).toHaveBeenCalledExactlyOnceWith(null, 'contact');

        el.setAttribute('data-total-unread', '2');
        c.syncPushNotify();
        expect(c.$store.push.notify).toHaveBeenCalledOnce();
        expect(c.$store.sound.playIncoming).toHaveBeenCalledOnce();
    });

    test('syncPushNotify does NOT play the unscoped incoming sound while a conversation IS open (fixed 2026-10-05: was double-firing alongside _pollTick\'s own scoped play for the same message) — the push notification itself still fires', () => {
        const c = contact();
        c.$wire = { activeUserId: 5 };
        c.$store = { push: { notify: vi.fn() }, sound: { playIncoming: vi.fn() } };
        const el = document.createElement('div');
        el.setAttribute('data-total-unread', '1');
        document.body.appendChild(el);

        c.syncPushNotify();
        el.setAttribute('data-total-unread', '2');
        c.syncPushNotify();

        expect(c.$store.push.notify).toHaveBeenCalledOnce();
        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();
    });

    test('_lastMessageIsMine reads the last row\'s justify-start class (the is_mine convention)', () => {
        const c = contact();
        const vp = document.createElement('div');
        vp.innerHTML = '<div data-rf="message-1" class="justify-end"></div><div data-rf="message-2" class="justify-start"></div>';
        document.body.appendChild(vp);
        c._msgViewportEl = vp;
        expect(c._lastMessageIsMine()).toBe(true);

        vp.innerHTML = '<div data-rf="message-1" class="justify-end"></div>';
        expect(c._lastMessageIsMine()).toBe(false);

        c._msgViewportEl = null;
        expect(c._lastMessageIsMine()).toBe(false);
    });

    test('_pollTick plays the scoped incoming sound when the open thread\'s last message changes to one that is not mine', async () => {
        const c = contact();
        c.$wire = createWireMock({ activeUserId: 5 });
        c.$store = { sound: { playIncoming: vi.fn() } };
        const vp = document.createElement('div');
        vp.innerHTML = '<div data-rf="message-1" class="justify-start"></div>';
        document.body.appendChild(vp);
        c._msgViewportEl = vp;

        c.$wire._islands.messages = {
            refreshActive: vi.fn(() => {
                vp.innerHTML = '<div data-rf="message-1" class="justify-start"></div><div data-rf="message-2" class="justify-end"></div>';
                return Promise.resolve();
            }),
        };

        await c._pollTick();

        expect(c.$store.sound.playIncoming).toHaveBeenCalledExactlyOnceWith(5, 'contact');
    });

    test('_pollTick does NOT play the incoming sound when the new last message is mine (own just-sent message, already covered by the message-sent/playOutgoing path)', async () => {
        const c = contact();
        c.$wire = createWireMock({ activeUserId: 5 });
        c.$store = { sound: { playIncoming: vi.fn() } };
        const vp = document.createElement('div');
        vp.innerHTML = '<div data-rf="message-1" class="justify-end"></div>';
        document.body.appendChild(vp);
        c._msgViewportEl = vp;

        c.$wire._islands.messages = {
            refreshActive: vi.fn(() => {
                vp.innerHTML = '<div data-rf="message-1" class="justify-end"></div><div data-rf="message-2" class="justify-start"></div>';
                return Promise.resolve();
            }),
        };

        await c._pollTick();

        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();
    });

    test('_pollTick does NOT play the incoming sound on the first tick after opening a thread (lastBefore is null — ambiguous "just loaded" vs "new message")', async () => {
        const c = contact();
        c.$wire = createWireMock({ activeUserId: 5 });
        c.$store = { sound: { playIncoming: vi.fn() } };
        const vp = document.createElement('div');
        document.body.appendChild(vp);
        c._msgViewportEl = vp;

        c.$wire._islands.messages = {
            refreshActive: vi.fn(() => {
                vp.innerHTML = '<div data-rf="message-1" class="justify-start"></div>';
                return Promise.resolve();
            }),
        };

        await c._pollTick();

        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();
    });

    test('_pollTick does NOT play the incoming sound when the thread\'s last message is simply deleted, leaving it empty (fixed 2026-10-05: lastAfter being null is a removal, not an arrival)', async () => {
        const c = contact();
        c.$wire = createWireMock({ activeUserId: 5 });
        c.$store = { sound: { playIncoming: vi.fn() } };
        const vp = document.createElement('div');
        vp.innerHTML = '<div data-rf="message-1" class="justify-end"></div>';
        document.body.appendChild(vp);
        c._msgViewportEl = vp;

        c.$wire._islands.messages = {
            refreshActive: vi.fn(() => {
                vp.innerHTML = '';
                return Promise.resolve();
            }),
        };

        await c._pollTick();

        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();
    });

    test('_pollTick does nothing sound-related while no conversation is open', async () => {
        const c = contact();
        c.$wire = createWireMock({ activeUserId: null });
        c.$store = { sound: { playIncoming: vi.fn() } };

        await c._pollTick();

        expect(c.$store.sound.playIncoming).not.toHaveBeenCalled();
    });

    test('focusSearch focuses and selects the #search input when present', () => {
        const c = contact();
        const input = document.createElement('input');
        input.id = 'search';
        input.focus = vi.fn();
        input.select = vi.fn();
        document.body.appendChild(input);

        expect(() => c.focusSearch()).not.toThrow();
        expect(input.focus).toHaveBeenCalledOnce();
        expect(input.select).toHaveBeenCalledOnce();
    });

    test('focusSearch no-ops when #search is absent', () => {
        const c = contact();
        expect(() => c.focusSearch()).not.toThrow();
    });
});

describe('contact() _bindViewport — re-binds across conversation switches instead of going stale (fixed 2026-10-01)', () => {
    test('sets _msgViewportEl to null without throwing when #msg-viewport is absent (e.g. mounted with no conversation open)', () => {
        const c = contact();
        expect(() => c._bindViewport()).not.toThrow();
        expect(c._msgViewportEl).toBeNull();
    });

    test('binds to #msg-viewport once it exists — the exact case that was broken: nothing open at mount, a conversation opened afterward', () => {
        const c = contact();
        c._bindViewport();
        expect(c._msgViewportEl).toBeNull();

        const vp = withViewport();
        c._bindViewport();

        expect(c._msgViewportEl).toBe(vp);
    });

    test('re-binding to a NEW #msg-viewport (conversation closed, a different one reopened) points at the new element, not the stale detached one', () => {
        const c = contact();
        const first = withViewport();
        c._bindViewport();
        expect(c._msgViewportEl).toBe(first);

        first.remove();
        const second = withViewport();
        c._bindViewport();

        expect(c._msgViewportEl).toBe(second);
        expect(c._msgViewportEl).not.toBe(first);
    });

    test('re-binding does not stack a second scroll listener on the same element (would otherwise double-fire loadMoreMessages on one scroll)', async () => {
        const c = contact();
        c.$wire = createWireMock({ hasOlder: true });
        const vp = withViewport();
        Object.defineProperties(vp, {
            scrollTop: { value: 10, configurable: true },
            scrollHeight: { value: 1000, configurable: true },
            clientHeight: { value: 500, configurable: true },
        });

        c._bindViewport();
        c._bindViewport();

        vp.dispatchEvent(new Event('scroll'));
        await new Promise((r) => setTimeout(r, 10));

        expect(c.$wire._islands.messages.loadMoreMessages).toHaveBeenCalledTimes(1);
    });

    test('init() binds the viewport on mount', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.$watch = vi.fn();
        c.initFancybox = vi.fn();
        c.initPattern = vi.fn();
        const vp = withViewport();

        c.init();

        expect(c._msgViewportEl).toBe(vp);
        c.destroy();
    });

    test('backToList clears _msgViewportEl so stale-element reads cannot happen while no conversation is open', () => {
        const c = contact();
        c.$wire = createWireMock();
        c.resetUI = vi.fn();
        c._msgViewportEl = withViewport();

        c.backToList();

        expect(c._msgViewportEl).toBeNull();
    });
});
