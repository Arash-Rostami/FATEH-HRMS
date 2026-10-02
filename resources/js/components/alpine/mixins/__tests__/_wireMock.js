import { vi } from 'vitest';

function createIslandMock() {
    const store = {};
    return new Proxy(store, {
        get(target, prop) {
            if (typeof prop !== 'string') return target[prop];
            if (!(prop in target)) target[prop] = vi.fn(() => Promise.resolve());
            return target[prop];
        },
    });
}

export function createWireMock(props = {}) {
    const islands = {};
    const wire = {
        set: vi.fn(),
        on: vi.fn(),
        cancelReply: vi.fn(),
        cancelEdit: vi.fn(),
        replyTo: vi.fn(),
        pollDigest: vi.fn(() => Promise.resolve('digest-1')),
        editMessage: vi.fn(),
        openManageMembers: vi.fn(() => Promise.resolve()),
        loadMentionMemberNames: vi.fn(() => Promise.resolve()),
        composer: { attachments: [], body: '' },
        $island: vi.fn((name) => {
            if (!islands[name]) islands[name] = createIslandMock();
            return islands[name];
        }),
        ...props,
    };
    wire._islands = islands;
    return wire;
}

// Proves a method captures `this.$wire` early and reuses that SAME reference, rather than
// re-reading `this.$wire` post-morph — the exact regression class this session fixed
// (backToList, doCancelInvite, toggleBrowse, ...). `allowedAccesses` is the number of
// legitimate synchronous, pre-await reads the method under test is KNOWN (by reading its
// source) to make — e.g. 1 for `const wire = this.$wire` alone, 2 if it also calls a
// helper like `_isl()` that reads `this.$wire` internally before the same await point.
// Every access beyond that count hits a Proxy that throws instead of silently resolving
// against what would, in the real DOM, be a stale/detached lookup. Count-based rather than
// timing-based (a microtask-boundary version proved unreliable — test-runner machinery
// itself incurs microtask ticks before a test body even starts, so "first tick after
// install" doesn't reliably line up with "first await inside the method under test").
export function installWireProbe(c, props = {}, allowedAccesses = 1) {
    const first = createWireMock(props);
    let count = 0;
    const poisonProxy = new Proxy({}, {
        get(target, prop) {
            throw new Error(`$wire accessed beyond the expected ${allowedAccesses} pre-await read(s) (prop: ${String(prop)}) — re-reads this.$wire instead of reusing a captured reference`);
        },
    });
    Object.defineProperty(c, '$wire', {
        configurable: true,
        get() {
            count++;
            return count <= allowedAccesses ? first : poisonProxy;
        },
    });
    return first;
}

export function stubResizeObserver() {
    global.ResizeObserver = class {
        observe() {}
        unobserve() {}
        disconnect() {}
    };
}
