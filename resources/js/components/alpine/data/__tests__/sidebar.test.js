import { describe, expect, test, vi } from 'vitest'
import sidebar from '../sidebar.js'

function touchEvent(x, y, cancelable = true) {
    return { changedTouches: [{ screenX: x, screenY: y }], cancelable, preventDefault: vi.fn() };
}

describe('sidebar.init', () => {
    test('sets activeTab from the current URL pathname', () => {
        window.history.pushState({}, '', '/admin/projects');
        const s = sidebar();
        s.init();
        expect(s.activeTab).toBe('/admin/projects');
    });
});

describe('sidebar touch gestures', () => {
    test('handleTouchStart captures the start position and sets isDragging', () => {
        const s = sidebar();
        s.handleTouchStart(touchEvent(100, 200));
        expect(s.touchStartX).toBe(100);
        expect(s.touchStartY).toBe(200);
        expect(s.isDragging).toBe(true);
    });

    test('handleTouchStart is a no-op when there is no touch point', () => {
        const s = sidebar();
        s.handleTouchStart({ changedTouches: [] });
        expect(s.isDragging).toBe(false);
    });

    test('handleTouchMove prevents default when vertical movement stays below the move threshold (treated as a horizontal swipe)', () => {
        const s = sidebar();
        s.handleTouchStart(touchEvent(0, 100));
        const e = touchEvent(50, 110);
        s.handleTouchMove(e);
        expect(e.preventDefault).toHaveBeenCalled();
    });

    test('handleTouchMove does not prevent default once vertical movement exceeds the move threshold (treated as a vertical scroll)', () => {
        const s = sidebar();
        s.handleTouchStart(touchEvent(0, 100));
        const e = touchEvent(50, 140);
        s.handleTouchMove(e);
        expect(e.preventDefault).not.toHaveBeenCalled();
    });

    test('handleTouchMove is a no-op when not currently dragging', () => {
        const s = sidebar();
        const e = touchEvent(50, 110);
        s.handleTouchMove(e);
        expect(e.preventDefault).not.toHaveBeenCalled();
    });

    test('handleTouchMove respects a non-cancelable event', () => {
        const s = sidebar();
        s.handleTouchStart(touchEvent(0, 100));
        const e = touchEvent(50, 110, false);
        expect(() => s.handleTouchMove(e)).not.toThrow();
        expect(e.preventDefault).not.toHaveBeenCalled();
    });

    test('handleTouchEnd expands on a sufficiently rightward horizontal swipe within the vertical tolerance', () => {
        const s = sidebar();
        s.handleTouchStart(touchEvent(0, 100));
        s.handleTouchEnd(touchEvent(90, 110));
        expect(s.isExpanded).toBe(true);
        expect(s.isDragging).toBe(false);
    });

    test('handleTouchEnd collapses on a sufficiently leftward horizontal swipe within the vertical tolerance', () => {
        const s = sidebar();
        s.isExpanded = true;
        s.handleTouchStart(touchEvent(100, 100));
        s.handleTouchEnd(touchEvent(10, 105));
        expect(s.isExpanded).toBe(false);
    });

    test('handleTouchEnd ignores a swipe that does not clear the horizontal expand threshold', () => {
        const s = sidebar();
        s.handleTouchStart(touchEvent(0, 100));
        s.handleTouchEnd(touchEvent(50, 105));
        expect(s.isExpanded).toBe(false);
    });

    test('handleTouchEnd ignores a swipe whose vertical travel exceeds the end tolerance, even with a large horizontal delta', () => {
        const s = sidebar();
        s.handleTouchStart(touchEvent(0, 100));
        s.handleTouchEnd(touchEvent(90, 200));
        expect(s.isExpanded).toBe(false);
    });

    test('handleTouchEnd still clears isDragging when there is no touch point', () => {
        const s = sidebar();
        s.isDragging = true;
        s.handleTouchEnd({ changedTouches: [] });
        expect(s.isDragging).toBe(false);
    });
});

describe('sidebar.expand / collapse — desktop breakpoint gated', () => {
    test('expand only takes effect at or above the desktop breakpoint', () => {
        const s = sidebar();
        Object.defineProperty(window, 'innerWidth', { value: 500, configurable: true });
        s.expand();
        expect(s.isExpanded).toBe(false);

        Object.defineProperty(window, 'innerWidth', { value: 1024, configurable: true });
        s.expand();
        expect(s.isExpanded).toBe(true);
    });

    test('collapse only takes effect at or above the desktop breakpoint', () => {
        const s = sidebar();
        s.isExpanded = true;
        Object.defineProperty(window, 'innerWidth', { value: 500, configurable: true });
        s.collapse();
        expect(s.isExpanded).toBe(true);

        Object.defineProperty(window, 'innerWidth', { value: 1024, configurable: true });
        s.collapse();
        expect(s.isExpanded).toBe(false);
    });

    test('toggleExpand flips regardless of viewport width', () => {
        const s = sidebar();
        Object.defineProperty(window, 'innerWidth', { value: 500, configurable: true });
        s.toggleExpand();
        expect(s.isExpanded).toBe(true);
        s.toggleExpand();
        expect(s.isExpanded).toBe(false);
    });
});

describe('sidebar hover state', () => {
    test('setHover/clearHover/isHovered track the currently hovered item', () => {
        const s = sidebar();
        expect(s.isHovered('projects')).toBe(false);
        s.setHover('projects');
        expect(s.isHovered('projects')).toBe(true);
        expect(s.isHovered('tasks')).toBe(false);
        s.clearHover();
        expect(s.isHovered('projects')).toBe(false);
    });
});

describe('sidebar.isActive', () => {
    test('matches when the active tab path contains the given item segment', () => {
        const s = sidebar();
        s.activeTab = '/admin/projects/5';
        expect(s.isActive('projects')).toBe(true);
        expect(s.isActive('tasks')).toBe(false);
    });
});

describe('sidebar.getTransitionDelay', () => {
    test('returns 0ms when the sidebar is collapsed, regardless of index', () => {
        const s = sidebar();
        expect(s.getTransitionDelay(3)).toBe('0ms');
    });

    test('staggers by 50ms per index on top of a 100ms base once expanded', () => {
        const s = sidebar();
        s.isExpanded = true;
        expect(s.getTransitionDelay(0)).toBe('100ms');
        expect(s.getTransitionDelay(2)).toBe('200ms');
    });
});
