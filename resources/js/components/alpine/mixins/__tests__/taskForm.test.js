import { describe, expect, test, vi } from 'vitest'
import taskFormMixin from '../taskForm.js'

function freshTaskForm(wireForm = {}) {
    const c = taskFormMixin();
    c.$wire = { form: wireForm, set: vi.fn() };
    c.syncFormArrays();
    return c;
}

describe('taskFormMixin.syncFormArrays — checklist sanitization + equalWeights redistribution', () => {
    test('defaults to empty arrays when $wire/$wire.form is absent', () => {
        const c = taskFormMixin();
        c.syncFormArrays();
        expect(c.labels).toEqual([]);
        expect(c.checklist).toEqual([]);
        expect(c.existingAttachments).toEqual([]);
    });

    test('weights that already sum to 100 are kept exactly as given, even if uneven', () => {
        const c = freshTaskForm({ checklist: [{ text: 'a', weight: 70 }, { text: 'b', weight: 30 }] });
        expect(c.checklist.map(i => i.weight)).toEqual([70, 30]);
    });

    test('weights that do NOT sum to 100 are redistributed via equal shares, remainder going to the earliest items', () => {
        const c = freshTaskForm({ checklist: [{ weight: 10 }, { weight: 10 }, { weight: 10 }] });
        expect(c.checklist.map(i => i.weight)).toEqual([34, 33, 33]);
        expect(c.checklist.reduce((s, i) => s + i.weight, 0)).toBe(100);
    });

    test('an evenly-divisible count (4 items) gets an exact equal share with no remainder', () => {
        const c = freshTaskForm({ checklist: [{ weight: 1 }, { weight: 1 }, { weight: 1 }, { weight: 1 }] });
        expect(c.checklist.map(i => i.weight)).toEqual([25, 25, 25, 25]);
    });

    test('out-of-range weights are clamped BEFORE the sum check, so a sum that lands on exactly 100 after clamping skips redistribution', () => {
        const c = freshTaskForm({ checklist: [{ weight: -10 }, { weight: 110 }] });
        expect(c.checklist.map(i => i.weight)).toEqual([0, 100]);
    });

    test('a non-numeric weight is treated as 0, contributing to a sum mismatch that triggers redistribution', () => {
        const c = freshTaskForm({ checklist: [{ weight: 'abc' }, { weight: 50 }] });
        expect(c.checklist.map(i => i.weight)).toEqual([50, 50]);
    });

    test('each row gets a unique, increasing _uid — not pinned to an absolute value, since the counter is module-level', () => {
        const c = freshTaskForm({ checklist: [{ weight: 50 }, { weight: 50 }, { weight: 0 }] });
        const uids = c.checklist.map(i => i._uid);
        expect(new Set(uids).size).toBe(3);
        expect(uids[0]).toBeLessThan(uids[1]);
        expect(uids[1]).toBeLessThan(uids[2]);
    });

    test('labels/existingAttachments are copied, not kept as the same array reference as $wire.form', () => {
        const wireLabels = ['a', 'b'];
        const c = freshTaskForm({ labels: wireLabels });
        expect(c.labels).toEqual(wireLabels);
        expect(c.labels).not.toBe(wireLabels);
    });
});

describe('taskFormMixin badge getters', () => {
    test('labelsCount/checklistTotal/infoBadge reflect array lengths', () => {
        const c = freshTaskForm({ labels: ['a', 'b'], checklist: [{ weight: 100 }] });
        expect(c.labelsCount).toBe(2);
        expect(c.checklistTotal).toBe(1);
        expect(c.infoBadge).toBe(2);
    });

    test('checklistCompleted/checklistCompletedWeight only count done items', () => {
        const c = freshTaskForm({ checklist: [{ weight: 60, done: true }, { weight: 40, done: false }] });
        expect(c.checklistCompleted).toBe(1);
        expect(c.checklistCompletedWeight).toBe(60);
        expect(c.checklistTotalWeight).toBe(100);
    });

    test('checklistProgress is the completed-weight share, and 0 when there is no weight at all', () => {
        const c = freshTaskForm({ checklist: [{ weight: 60, done: true }, { weight: 40, done: false }] });
        expect(c.checklistProgress).toBe(60);

        const empty = freshTaskForm({ checklist: [] });
        expect(empty.checklistProgress).toBe(0);
    });

    test('actionBadge sums existingAttachments plus $wire.form.attachments, guarding a non-array value as 0', () => {
        const c = freshTaskForm({ existingAttachments: [{}, {}] });
        c.$wire.form.attachments = [{}];
        expect(c.actionBadge).toBe(3);

        c.$wire.form.attachments = undefined;
        expect(c.actionBadge).toBe(2);
    });

    test('followupBadge sums checklist + action + collaborators, guarding a non-array collaborators value as 0', () => {
        const c = freshTaskForm({ checklist: [{ weight: 100 }], existingAttachments: [{}] });
        c.$wire.form.collaborators = [1, 2];
        expect(c.followupBadge).toBe(1 + 1 + 2);

        c.$wire.form.collaborators = null;
        expect(c.followupBadge).toBe(1 + 1 + 0);
    });
});

describe('taskFormMixin label CRUD', () => {
    test('addLabelItem trims, adds, and syncs form.labels via $wire.set; newLabel is always cleared afterward', () => {
        const c = freshTaskForm();
        c.newLabel = '  urgent  ';

        c.addLabelItem();

        expect(c.labels).toEqual(['urgent']);
        expect(c.newLabel).toBe('');
        expect(c.$wire.set).toHaveBeenCalledWith('form.labels', ['urgent'], false);
    });

    test('ignores a blank/whitespace-only label, but still clears newLabel', () => {
        const c = freshTaskForm();
        c.newLabel = '   ';
        c.addLabelItem();
        expect(c.labels).toEqual([]);
        expect(c.newLabel).toBe('');
        expect(c.$wire.set).not.toHaveBeenCalled();
    });

    test('ignores a duplicate label already present', () => {
        const c = freshTaskForm({ labels: ['urgent'] });
        c.newLabel = 'urgent';
        c.addLabelItem();
        expect(c.labels).toEqual(['urgent']);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });

    test('ignores a new label once MAX_LABELS (10) is already reached', () => {
        const ten = Array.from({ length: 10 }, (_, i) => `l${i}`);
        const c = freshTaskForm({ labels: ten });
        c.newLabel = 'eleventh';
        c.addLabelItem();
        expect(c.labels).toHaveLength(10);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });

    test('removeLabel removes by index and syncs form.labels; an out-of-range index is a no-op', () => {
        const c = freshTaskForm({ labels: ['a', 'b', 'c'] });

        c.removeLabel(1);
        expect(c.labels).toEqual(['a', 'c']);
        expect(c.$wire.set).toHaveBeenCalledWith('form.labels', ['a', 'c'], false);

        c.$wire.set.mockClear();
        c.removeLabel(99);
        expect(c.labels).toEqual(['a', 'c']);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });
});

describe('taskFormMixin checklist CRUD', () => {
    test('addChecklist adds a new item, redistributes weights equally across the whole list, and persists the sanitized payload', () => {
        const c = freshTaskForm({ checklist: [{ weight: 100 }] });
        c.newChecklistItem = '  wash dishes  ';

        c.addChecklist();

        expect(c.checklist.map(i => i.text)).toEqual(['', 'wash dishes']);
        expect(c.checklist.map(i => i.weight)).toEqual([50, 50]);
        expect(c.newChecklistItem).toBe('');
        expect(c.$wire.set).toHaveBeenCalledWith('form.checklist', [
            { text: '', done: false, weight: 50 },
            { text: 'wash dishes', done: false, weight: 50 },
        ], false);
    });

    test('ignores a blank/whitespace-only checklist entry', () => {
        const c = freshTaskForm();
        c.newChecklistItem = '   ';
        c.addChecklist();
        expect(c.checklist).toEqual([]);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });

    test('removeChecklistItem removes by index, redistributes the remaining weights, and persists; an out-of-range index is a no-op', () => {
        const c = freshTaskForm({ checklist: [{ weight: 34 }, { weight: 33 }, { weight: 33 }] });

        c.removeChecklistItem(0);

        expect(c.checklist).toHaveLength(2);
        expect(c.checklist.map(i => i.weight)).toEqual([50, 50]);
        expect(c.$wire.set).toHaveBeenCalled();

        c.$wire.set.mockClear();
        c.removeChecklistItem(99);
        expect(c.checklist).toHaveLength(2);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });

    test('toggleChecklistItem flips done and persists; a non-existent index is a no-op', () => {
        const c = freshTaskForm({ checklist: [{ weight: 100, done: false }] });

        c.toggleChecklistItem(0);
        expect(c.checklist[0].done).toBe(true);
        expect(c.$wire.set).toHaveBeenCalled();

        c.$wire.set.mockClear();
        c.toggleChecklistItem(5);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });
});

describe('taskFormMixin.setChecklistWeight — proportional redistribution among the others', () => {
    test('the only item in the list is always forced to 100 regardless of the requested value', () => {
        const c = freshTaskForm({ checklist: [{ weight: 50 }] });
        c.setChecklistWeight(0, 10);
        expect(c.checklist[0].weight).toBe(100);
    });

    test('raising one item proportionally shrinks the others, so the total still sums to exactly 100', () => {
        const c = freshTaskForm({ checklist: [{ weight: 34 }, { weight: 33 }, { weight: 33 }] });
        c.setChecklistWeight(0, 50);
        expect(c.checklist.map(i => i.weight)).toEqual([50, 25, 25]);
    });

    test('when every other item is already at 0, the freed-up remainder is split equally among them', () => {
        const c = freshTaskForm({ checklist: [{ weight: 70 }, { weight: 30 }] });
        c.checklist[0].weight = 100;
        c.checklist[1].weight = 0;

        c.setChecklistWeight(0, 20);

        expect(c.checklist.map(i => i.weight)).toEqual([20, 80]);
    });

    test('clamps the requested value to 0-100, and the last "other" absorbs any rounding drift so the total is always exactly 100', () => {
        const c = freshTaskForm({ checklist: [{ weight: 50 }, { weight: 50 }] });
        c.setChecklistWeight(0, 500);
        expect(c.checklist.map(i => i.weight)).toEqual([100, 0]);
    });

    test('treats a non-finite requested value (NaN) as 0', () => {
        const c = freshTaskForm({ checklist: [{ weight: 50 }, { weight: 50 }] });
        c.setChecklistWeight(0, NaN);
        expect(c.checklist.map(i => i.weight)).toEqual([0, 100]);
    });

    test('persist=false updates in-memory weights without calling $wire.set (mid-drag)', () => {
        const c = freshTaskForm({ checklist: [{ weight: 50 }, { weight: 50 }] });
        c.setChecklistWeight(0, 70, false);
        expect(c.checklist[0].weight).toBe(70);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });

    test('a non-existent index is a no-op', () => {
        const c = freshTaskForm({ checklist: [{ weight: 100 }] });
        expect(() => c.setChecklistWeight(5, 50)).not.toThrow();
        expect(c.$wire.set).not.toHaveBeenCalled();
    });
});

describe('taskFormMixin checklist inline text editing', () => {
    test('startEditingChecklistItem sets the editing index only for an existing item', () => {
        const c = freshTaskForm({ checklist: [{ weight: 100 }] });
        c.startEditingChecklistItem(0);
        expect(c.editingChecklistIndex).toBe(0);

        c.editingChecklistIndex = null;
        c.startEditingChecklistItem(9);
        expect(c.editingChecklistIndex).toBeNull();
    });

    test('saveChecklistItemText trims and persists non-empty text, then exits edit mode', () => {
        const c = freshTaskForm({ checklist: [{ text: 'old', weight: 100 }] });
        c.editingChecklistIndex = 0;
        c.checklist[0].text = '  new text  ';

        c.saveChecklistItemText(0);

        expect(c.checklist[0].text).toBe('new text');
        expect(c.editingChecklistIndex).toBeNull();
        expect(c.$wire.set).toHaveBeenCalled();
    });

    test('saveChecklistItemText does not persist a blank result, but still exits edit mode', () => {
        const c = freshTaskForm({ checklist: [{ text: 'old', weight: 100 }] });
        c.editingChecklistIndex = 0;
        c.checklist[0].text = '   ';

        c.saveChecklistItemText(0);

        expect(c.checklist[0].text).toBe('   ');
        expect(c.editingChecklistIndex).toBeNull();
        expect(c.$wire.set).not.toHaveBeenCalled();
    });
});

describe('taskFormMixin.removeExistingAttachment', () => {
    test('removes by index and syncs form.existingAttachments; an out-of-range index is a no-op', () => {
        const c = freshTaskForm({ existingAttachments: [{ name: 'a' }, { name: 'b' }] });

        c.removeExistingAttachment(0);
        expect(c.existingAttachments).toEqual([{ name: 'b' }]);
        expect(c.$wire.set).toHaveBeenCalledWith('form.existingAttachments', [{ name: 'b' }], false);

        c.$wire.set.mockClear();
        c.removeExistingAttachment(99);
        expect(c.$wire.set).not.toHaveBeenCalled();
    });
});
