import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import projectComposer from '../projectComposer.js'

function makeComposer(value = '', names = ['علی‌رضا', 'سارا', 'مهدی']) {
    const c = projectComposer(value, names);
    c.$watch = vi.fn();
    c.$dispatch = vi.fn();
    c.$nextTick = (cb) => cb();
    c.$refs = {
        composer: {
            selectionStart: 0,
            selectionEnd: 0,
            focus: vi.fn(),
            setSelectionRange: vi.fn(),
        },
    };
    return c;
}

beforeEach(() => {
    vi.clearAllMocks();
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('projectComposer init', () => {
    test('seeds body from the passed value and watches value for external changes', () => {
        const c = makeComposer('متن اولیه');
        let watcher;
        c.$watch = (name, cb) => { if (name === 'value') watcher = cb; };
        c.init();

        expect(c.body).toBe('متن اولیه');
        watcher('متن جدید');
        expect(c.body).toBe('متن جدید');
        watcher(null);
        expect(c.body).toBe('');
    });
});

describe('projectComposer.candidates', () => {
    test('normalizes mixed string/object name lists and drops empty names', () => {
        const c = makeComposer('', ['نام رشته', { name: 'شیء نام' }, { label: 'بدون name' }, '']);
        expect(c.candidates).toEqual(['نام رشته', 'شیء نام']);
    });

    test('builds the candidate list once and reuses the cache', () => {
        const c = makeComposer('', ['الف']);
        c.init();
        const first = c.candidates;
        const second = c.candidates;
        expect(second).toBe(first);
    });
});

describe('projectComposer.willNotify', () => {
    test('collects only the candidates actually mentioned in the body', () => {
        const c = makeComposer('سلام @سارا');
        c.init();
        expect(c.willNotify).toEqual(['سارا']);
    });

    test('a longer candidate name is not falsely matched by a prefix mention', () => {
        const c = makeComposer('سلام @علی', ['علی‌رضا', 'علی']);
        c.init();
        expect(c.willNotify).toEqual(['علی']);
    });

    test('recomputes when the body changes and caches while it does not', () => {
        const c = makeComposer('سلام @سارا');
        c.init();
        const first = c.willNotify;
        expect(c.willNotify).toBe(first);

        c.body = 'سلام @مهدی';
        expect(c.willNotify).toEqual(['مهدی']);
    });
});

describe('projectComposer.counterTone', () => {
    test('crossing the thresholds picks error then gold then the default tone', () => {
        const c = makeComposer('');
        c.maxLength = 100;

        c.body = 'a'.repeat(89);
        expect(c.counterTone).toBe('var(--md-sys-color-on-surface-variant)');

        c.body = 'a'.repeat(90);
        expect(c.counterTone).toBe('var(--tool-gold-color)');

        c.body = 'a'.repeat(100);
        expect(c.counterTone).toBe('var(--md-sys-color-error)');
    });
});

describe('projectComposer.onInput', () => {
    test('dispatches activity-typed, syncs value, and opens the picker only for a live @term with matches', () => {
        const c = makeComposer('');
        c.init();

        c.onInput({ target: { value: 'سلام @سا', selectionStart: 8 } });
        expect(c.$dispatch).toHaveBeenCalledWith('activity-typed');
        expect(c.value).toBe('سلام @سا');
        expect(c.query).toBe('سا');
        expect(c.open).toBe(true);

        c.onInput({ target: { value: 'سلام', selectionStart: 5 } });
        expect(c.open).toBe(false);
    });

    test('a live @term with no matches keeps the picker closed', () => {
        const c = makeComposer('', ['سارا']);
        c.onInput({ target: { value: '@غ', selectionStart: 3 } });
        expect(c.open).toBe(false);
    });
});

describe('projectComposer.pick', () => {
    test('splices the mention into the value, closes the picker, and restores focus + caret', () => {
        const c = makeComposer('سلام @سا');
        c.value = 'سلام @سا';
        c.$refs.composer.selectionStart = 8;
        c.open = true;

        c.pick('سارا');

        expect(c.value).toBe('سلام @سارا ');
        expect(c.open).toBe(false);
        expect(c.$refs.composer.focus).toHaveBeenCalled();
        const [start, end] = c.$refs.composer.setSelectionRange.mock.calls[0];
        expect(start).toBe(11);
        expect(end).toBe(11);
    });

    test('a failed build (null result) just closes the picker without touching the value', () => {
        const c = makeComposer('سلام @عا');
        c.value = 'سلام @عا';
        c.$refs.composer.selectionStart = 0;

        c.pick('علی‌رضا');

        expect(c.value).toBe('سلام @عا');
        expect(c.open).toBe(false);
    });
});