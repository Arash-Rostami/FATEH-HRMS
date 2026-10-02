import { describe, expect, test, vi } from 'vitest'
import deckMixin from '../deck.js'

describe('deckMixin.deckSync — order-preserving merge', () => {
    test('with an empty deckOrder, adopts the incoming ids in their given order', () => {
        const d = deckMixin();
        d.deckSync([3, 1, 2]);
        expect(d.deckOrder).toEqual([3, 1, 2]);
    });

    test('ids already known keep their EXISTING relative order, ignoring the incoming order', () => {
        const d = deckMixin();
        d.deckOrder = [2, 1, 3];
        d.deckSync([1, 2, 3]);
        expect(d.deckOrder).toEqual([2, 1, 3]);
    });

    test('newly-seen ids are appended, in their incoming order, after the known ones', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2];
        d.deckSync([3, 1, 4, 2]);
        expect(d.deckOrder).toEqual([1, 2, 3, 4]);
    });

    test('ids no longer present in the incoming set are dropped', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2, 3];
        d.deckSync([1, 3]);
        expect(d.deckOrder).toEqual([1, 3]);
    });

    test('an empty incoming list resets a non-empty deckOrder to []', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2];
        d.deckSync([]);
        expect(d.deckOrder).toEqual([]);
    });

    test('string ids are coerced to numbers and matched against existing numeric ids', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2];
        d.deckSync(['2', '1']);
        expect(d.deckOrder).toEqual([1, 2]);
    });

    test('a no-op sync (identical resulting order) does not replace the deckOrder array reference', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2];
        const ref = d.deckOrder;
        d.deckSync([1, 2]);
        expect(d.deckOrder).toBe(ref);
    });

    test('deckSyncFromDom reads data-resource-id attributes from the given root, in document order, and syncs by numeric id', () => {
        document.body.innerHTML = '<div><span data-resource-id="5"></span><span data-resource-id="9"></span></div>';
        const d = deckMixin();
        d.deckSyncFromDom(document.body);
        expect(d.deckOrder).toEqual([5, 9]);
    });
});

describe('deckMixin.deckDepth', () => {
    test('returns the index of a known id, and the full deck length for an unknown id (so it sorts last)', () => {
        const d = deckMixin();
        d.deckOrder = [5, 9, 2];
        expect(d.deckDepth(9)).toBe(1);
        expect(d.deckDepth('2')).toBe(2);
        expect(d.deckDepth(99)).toBe(3);
    });
});

describe('deckMixin.deckBringToFront', () => {
    test('moves a known id to the front, preserving the relative order of the rest', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2, 3, 4];
        d.deckBringToFront(3);
        expect(d.deckOrder).toEqual([3, 1, 2, 4]);
    });

    test('is a no-op (same array reference) when the id is already at the front', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2, 3];
        const ref = d.deckOrder;
        d.deckBringToFront(1);
        expect(d.deckOrder).toBe(ref);
    });

    test('an unknown id is inserted at the front rather than silently dropped', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2];
        d.deckBringToFront(9);
        expect(d.deckOrder).toEqual([9, 1, 2]);
    });

    test('a string id is coerced to a number before comparing/storing', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2, 3];
        d.deckBringToFront('3');
        expect(d.deckOrder).toEqual([3, 1, 2]);
    });
});

describe('deckMixin.deckCycle — rotation', () => {
    test('returns null and does not mutate deckOrder when there are fewer than 2 entries', () => {
        const d = deckMixin();
        d.deckOrder = [1];
        expect(d.deckCycle(1)).toBeNull();
        expect(d.deckOrder).toEqual([1]);

        d.deckOrder = [];
        expect(d.deckCycle(1)).toBeNull();
    });

    test('a positive direction rotates left (the first id moves to the end), returning the new first id', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2, 3];
        expect(d.deckCycle(1)).toBe(2);
        expect(d.deckOrder).toEqual([2, 3, 1]);
    });

    test('a non-positive direction rotates right (the last id moves to the front), returning the new first id', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2, 3];
        expect(d.deckCycle(-1)).toBe(3);
        expect(d.deckOrder).toEqual([3, 1, 2]);
    });
});

describe('deckMixin.deckOpen / deckCycleAndOpen — $wire.focusDeck wiring', () => {
    test('deckOpen brings the id to front and focuses it via $wire with a numeric id', () => {
        const d = deckMixin();
        d.deckOrder = [1, 2, 3];
        d.$wire = { focusDeck: vi.fn() };

        d.deckOpen('3');

        expect(d.deckOrder).toEqual([3, 1, 2]);
        expect(d.$wire.focusDeck).toHaveBeenCalledWith(3);
    });

    test('deckCycleAndOpen focuses the new front id after rotating, and never calls $wire when the deck has fewer than 2 entries', () => {
        const d = deckMixin();
        d.$wire = { focusDeck: vi.fn() };

        d.deckOrder = [1];
        d.deckCycleAndOpen(1);
        expect(d.$wire.focusDeck).not.toHaveBeenCalled();

        d.deckOrder = [1, 2, 3];
        d.deckCycleAndOpen(1);
        expect(d.$wire.focusDeck).toHaveBeenCalledWith(2);
    });
});
