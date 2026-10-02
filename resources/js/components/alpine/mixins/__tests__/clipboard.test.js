import { describe, expect, test, vi, beforeEach } from 'vitest'
import clipboardMixin from '../clipboard.js'

function withContext({ hasClipboard = true, secure = true } = {}) {
    Object.defineProperty(window, 'isSecureContext', { value: secure, configurable: true });
    Object.defineProperty(navigator, 'clipboard', {
        value: hasClipboard ? { writeText: vi.fn() } : undefined,
        configurable: true,
    });
}

const flush = () => new Promise((r) => setTimeout(r, 0));

beforeEach(() => {
    document.body.innerHTML = '';
});

describe('clipboardMixin.copyText — secure-context guard (resources/js/scriptPattern.md: "the secure-context trap")', () => {
    test('no-ops on empty/null/undefined text, never touches the clipboard or dispatches a toast', () => {
        withContext();
        const c = clipboardMixin();
        c.$dispatch = vi.fn();

        c.copyText('');
        c.copyText(null);
        c.copyText(undefined);

        expect(c.$dispatch).not.toHaveBeenCalled();
    });

    test('secure context with navigator.clipboard: writes via the Clipboard API and toasts success on resolve', async () => {
        withContext({ secure: true, hasClipboard: true });
        navigator.clipboard.writeText.mockResolvedValue(undefined);
        const c = clipboardMixin();
        c.$dispatch = vi.fn();

        c.copyText('hello', 'copied!', 'success');
        await flush();

        expect(navigator.clipboard.writeText).toHaveBeenCalledWith('hello');
        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: 'copied!', type: 'success' });
    });

    test('secure context, Clipboard API rejects: falls back to the hidden-textarea/execCommand path and still toasts success', async () => {
        withContext({ secure: true, hasClipboard: true });
        navigator.clipboard.writeText.mockRejectedValue(new Error('denied'));
        document.execCommand = vi.fn(() => true);
        const c = clipboardMixin();
        c.$dispatch = vi.fn();

        c.copyText('hello', 'copied!', 'success');
        await flush();

        expect(document.execCommand).toHaveBeenCalledWith('copy');
        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: 'copied!', type: 'success' });
    });

    test('an insecure context (plain HTTP over a LAN IP) skips the Clipboard API entirely and goes straight to the fallback', () => {
        withContext({ secure: false, hasClipboard: true });
        document.execCommand = vi.fn(() => true);
        const c = clipboardMixin();
        c.$dispatch = vi.fn();

        c.copyText('hello');

        expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
        expect(document.execCommand).toHaveBeenCalledWith('copy');
    });

    test('no navigator.clipboard object at all also uses the fallback, not a thrown TypeError', () => {
        withContext({ secure: true, hasClipboard: false });
        document.execCommand = vi.fn(() => true);
        const c = clipboardMixin();
        c.$dispatch = vi.fn();

        expect(() => c.copyText('hello')).not.toThrow();
        expect(document.execCommand).toHaveBeenCalledWith('copy');
    });
});

describe('clipboardMixin.fallbackCopyText — execCommand branch', () => {
    test('creates a hidden, off-screen, readonly textarea, selects it, copies, toasts success, and always removes the textarea', () => {
        document.execCommand = vi.fn(() => true);
        const c = clipboardMixin();
        c.$dispatch = vi.fn();

        c.fallbackCopyText('payload', 'done', 'success');

        expect(document.execCommand).toHaveBeenCalledWith('copy');
        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: 'done', type: 'success' });
        expect(document.querySelector('textarea')).toBeNull();
    });

    test('execCommand throwing still removes the textarea (finally block) and toasts a failure message instead', () => {
        document.execCommand = vi.fn(() => { throw new Error('unsupported'); });
        const c = clipboardMixin();
        c.$dispatch = vi.fn();

        c.fallbackCopyText('payload');

        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: 'کپی ناموفق بود.', type: 'error' });
        expect(document.querySelector('textarea')).toBeNull();
    });
});

describe('clipboardMixin._copyToast — overridable hook', () => {
    test('a consumer overriding _copyToast (e.g. settings.js\'s floating toast) is used instead of the default $dispatch', () => {
        document.execCommand = vi.fn(() => true);
        const c = clipboardMixin();
        c._copyToast = vi.fn();
        c.$dispatch = vi.fn();

        c.fallbackCopyText('x', 'done');

        expect(c._copyToast).toHaveBeenCalledWith('done', 'success');
        expect(c.$dispatch).not.toHaveBeenCalled();
    });
});
