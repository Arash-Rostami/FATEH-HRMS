import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import aiEditMixin from '../aiEdit.js'

const reply = { answer: 'بهبود متن', message: null, soft: false, note: 'پیشنهاد را بخوانید.', meter: '<div>meter</div>' };

function makeHost(over = {}) {
    const ta = document.createElement('textarea');
    ta.value = 'متن خام';
    ta.focus = vi.fn();
    document.body.appendChild(ta);
    const isl = { enhanceEdit: vi.fn(() => Promise.resolve(reply)) };
    const host = Object.assign(aiEditMixin(), {
        ta,
        isl,
        toast: vi.fn(),
        startEdit: vi.fn(),
        autoDirection: vi.fn(),
        fitEdit: vi.fn(),
        _isl: () => isl,
        _overLimit: () => false,
        aiEditCurrentId: () => 7,
        aiEditBox: () => ta,
        aiEditApply: vi.fn(),
    }, over);
    return host;
}

describe('aiEditMixin', () => {
    beforeEach(() => { document.body.innerHTML = ''; });
    afterEach(() => { document.body.innerHTML = ''; });

    test('starts with a quiet default state', () => {
        const h = aiEditMixin();
        expect(h.aiEditBusy).toBe(false);
        expect(h.aiEditId).toBeNull();
        expect(h.aiEditNote).toBe('');
        expect(h.aiEditSoft).toBe(true);
        expect(h.aiEditOk).toBe(false);
        expect(h.aiEditMeter).toBe('');
    });

    test('success writes the answer into the box, applies it to the host, and sets ok, note and meter', async () => {
        const h = makeHost();

        await h.enhanceEdit(7);

        expect(h.isl.enhanceEdit).toHaveBeenCalledWith(7, 'متن خام');
        expect(h.ta.value).toBe('بهبود متن');
        expect(h.aiEditApply).toHaveBeenCalledWith('بهبود متن');
        expect(h.aiEditOk).toBe(true);
        expect(h.aiEditNote).toBe(reply.note);
        expect(h.aiEditMeter).toBe(reply.meter);
        expect(h.aiEditSoft).toBe(false);
        expect(h.aiEditBusy).toBe(false);
        expect(h.aiEditId).toBeNull();
    });

    test('busy guard: a second call while one is in flight does nothing', async () => {
        let resolve;
        const h = makeHost();
        h.isl.enhanceEdit = vi.fn(() => new Promise((r) => { resolve = r; }));

        const first = h.enhanceEdit(7);
        expect(h.aiEditBusy).toBe(true);
        expect(h.aiEditId).toBe(7);
        await h.enhanceEdit(7);
        expect(h.isl.enhanceEdit).toHaveBeenCalledOnce();

        resolve(reply);
        await first;
    });

    test('no call when the id is not the one being edited', async () => {
        const h = makeHost({ aiEditCurrentId: () => 8 });

        await h.enhanceEdit(7);

        expect(h.isl.enhanceEdit).not.toHaveBeenCalled();
        expect(h.aiEditBusy).toBe(false);
    });

    test('no call without an id', async () => {
        const h = makeHost();

        await h.enhanceEdit(0);

        expect(h.isl.enhanceEdit).not.toHaveBeenCalled();
    });

    test('stale-text guard: text typed mid-flight is kept and the note says so', async () => {
        let resolve;
        const h = makeHost();
        h.isl.enhanceEdit = vi.fn(() => new Promise((r) => { resolve = r; }));

        const pending = h.enhanceEdit(7);
        h.ta.value = 'تایپ شده تازه';
        resolve(reply);
        await pending;

        expect(h.ta.value).toBe('تایپ شده تازه');
        expect(h.aiEditApply).not.toHaveBeenCalled();
        expect(h.aiEditOk).toBe(false);
        expect(h.aiEditSoft).toBe(true);
        expect(h.aiEditNote).toBe('متن تغییر کرد. برای متن تازه، دوباره پیشنهاد بگیرید.');
    });

    test('drops the answer when the edit session changed mid-flight but still stores the meter', async () => {
        let resolve;
        let current = 7;
        const h = makeHost({ aiEditCurrentId: () => current });
        h.isl.enhanceEdit = vi.fn(() => new Promise((r) => { resolve = r; }));

        const pending = h.enhanceEdit(7);
        current = null;
        resolve(reply);
        await pending;

        expect(h.ta.value).toBe('متن خام');
        expect(h.aiEditApply).not.toHaveBeenCalled();
        expect(h.aiEditMeter).toBe(reply.meter);
        expect(h.aiEditBusy).toBe(false);
    });

    test('soft and hard errors set the soft flag from the reply and write no text', async () => {
        const soft = makeHost();
        soft.isl.enhanceEdit = vi.fn(() => Promise.resolve({ answer: null, message: 'سهمیه تمام شد', soft: true, note: null, meter: '' }));
        await soft.enhanceEdit(7);
        expect(soft.aiEditNote).toBe('سهمیه تمام شد');
        expect(soft.aiEditSoft).toBe(true);
        expect(soft.aiEditOk).toBe(false);

        const hard = makeHost();
        hard.isl.enhanceEdit = vi.fn(() => Promise.resolve({ answer: null, message: 'خطا', soft: false, note: null }));
        await hard.enhanceEdit(7);
        expect(hard.aiEditNote).toBe('خطا');
        expect(hard.aiEditSoft).toBe(false);
        expect(hard.ta.value).toBe('متن خام');
        expect(hard.aiEditApply).not.toHaveBeenCalled();
    });

    test('toasts on empty text and never calls the island', async () => {
        const h = makeHost();
        h.ta.value = '   ';

        await h.enhanceEdit(7);

        expect(h.toast).toHaveBeenCalledWith('متن پیام خالی است.', 'warning');
        expect(h.isl.enhanceEdit).not.toHaveBeenCalled();
    });

    test('an over-limit text stops before the island call', async () => {
        const h = makeHost({ _overLimit: () => true });

        await h.enhanceEdit(7);

        expect(h.isl.enhanceEdit).not.toHaveBeenCalled();
        expect(h.aiEditBusy).toBe(false);
    });

    test('a rejected call toasts a generic error and clears busy; a closed session stays silent', async () => {
        const h = makeHost();
        h.isl.enhanceEdit = vi.fn(() => Promise.reject(new Error('boom')));
        await h.enhanceEdit(7);
        expect(h.toast).toHaveBeenCalledWith('ارتباط با سرویس هوش مصنوعی برقرار نشد. کمی بعد دوباره تلاش کنید.', 'error');
        expect(h.aiEditBusy).toBe(false);

        let reject;
        let current = 7;
        const closed = makeHost({ aiEditCurrentId: () => current });
        closed.isl.enhanceEdit = vi.fn(() => new Promise((_, rej) => { reject = rej; }));
        const pending = closed.enhanceEdit(7);
        current = null;
        reject(new Error('boom'));
        await pending;
        expect(closed.toast).not.toHaveBeenCalled();
        expect(closed.aiEditBusy).toBe(false);
    });

    test('the button path reads the textarea of the closest edit box', async () => {
        const h = makeHost({ aiEditBox: () => null });
        const box = document.createElement('div');
        box.setAttribute('data-edit-box', '7');
        const ta = document.createElement('textarea');
        ta.value = 'از دکمه';
        ta.focus = vi.fn();
        const btn = document.createElement('button');
        box.append(ta, btn);
        document.body.appendChild(box);

        await h.enhanceEdit(7, btn);

        expect(h.isl.enhanceEdit).toHaveBeenCalledWith(7, 'از دکمه');
        expect(ta.value).toBe('بهبود متن');
    });

    test('focus returns to the box only after busy is cleared, so the re-enabled textarea can take it (Enter works)', async () => {
        let busyAtFocus = null;
        const h = makeHost();
        h.ta.focus = vi.fn(() => { busyAtFocus = h.aiEditBusy; });
        h.$nextTick = (fn) => Promise.resolve().then(fn);

        await h.enhanceEdit(7);
        await Promise.resolve();

        expect(h.ta.focus).toHaveBeenCalledOnce();
        expect(busyAtFocus).toBe(false);
    });

    test('does not steal focus from another input when the answer lands', async () => {
        const h = makeHost();
        const other = document.createElement('input');
        document.body.appendChild(other);
        other.focus();

        await h.enhanceEdit(7);

        expect(h.ta.value).toBe('بهبود متن');
        expect(h.ta.focus).not.toHaveBeenCalled();
        expect(document.activeElement).toBe(other);
    });

    test('enhanceFromMenu opens the edit through the host, fills the box with the body, then enhances', async () => {
        const h = makeHost();
        h.ta.value = '';

        await h.enhanceFromMenu(7, 'متن خام');

        expect(h.startEdit).toHaveBeenCalledWith(7, 'متن خام');
        expect(h.isl.enhanceEdit).toHaveBeenCalledWith(7, 'متن خام');
        expect(h.ta.value).toBe('بهبود متن');
    });

    test('enhanceFromMenu waits for the edit to open on the server before it asks the AI', async () => {
        let open;
        const h = makeHost({ startEdit: vi.fn(() => new Promise((r) => { open = r; })) });

        const run = h.enhanceFromMenu(7, 'متن خام');
        await Promise.resolve();
        expect(h.isl.enhanceEdit).not.toHaveBeenCalled();

        open();
        await run;
        expect(h.isl.enhanceEdit).toHaveBeenCalledWith(7, expect.any(String));
    });

    test('enhanceFromMenu never asks the AI when opening the edit fails', async () => {
        const h = makeHost({ startEdit: vi.fn(() => Promise.reject(new Error('x'))) });

        await h.enhanceFromMenu(7, 'متن خام');

        expect(h.isl.enhanceEdit).not.toHaveBeenCalled();
        expect(h.aiEditBusy).toBe(false);
    });

    test('enhanceFromMenu is inert while busy', () => {
        const h = makeHost();
        h.aiEditBusy = true;

        h.enhanceFromMenu(7, 'x');

        expect(h.startEdit).not.toHaveBeenCalled();
    });

    test('resetAiEdit clears the note and the ok flag but keeps the meter', () => {
        const h = aiEditMixin();
        h.aiEditNote = 'x';
        h.aiEditOk = true;
        h.aiEditMeter = 'm';

        h.resetAiEdit();

        expect(h.aiEditNote).toBe('');
        expect(h.aiEditOk).toBe(false);
        expect(h.aiEditMeter).toBe('m');
    });
});
