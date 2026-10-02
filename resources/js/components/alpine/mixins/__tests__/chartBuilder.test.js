import { describe, expect, test, vi } from 'vitest'
import chartBuilderMixin from '../chartBuilder.js'

describe('chartBuilderMixin.getCssVar', () => {
    test('reads and trims the given style object\'s property value', () => {
        const b = chartBuilderMixin();
        const style = { getPropertyValue: vi.fn(() => '  #abc123  ') };

        expect(b.getCssVar('--primary', style)).toBe('#abc123');
        expect(style.getPropertyValue).toHaveBeenCalledWith('--primary');
    });
});

describe('chartBuilderMixin.resolveColor — var(--x) resolution + array recursion', () => {
    const style = { getPropertyValue: (name) => ({ '--primary': '#111', '--secondary': '#222' }[name] || '') };

    test('a plain string that is not var(...) passes through unchanged', () => {
        const b = chartBuilderMixin();
        expect(b.resolveColor('#fff', style)).toBe('#fff');
    });

    test('a var(--x) string resolves through getCssVar against the given style', () => {
        const b = chartBuilderMixin();
        expect(b.resolveColor('var(--primary)', style)).toBe('#111');
    });

    test('an array of values recurses element-by-element, mixing literals and var() refs', () => {
        const b = chartBuilderMixin();
        expect(b.resolveColor(['var(--primary)', '#fff', 'var(--secondary)'], style)).toEqual(['#111', '#fff', '#222']);
    });

    test('non-string, non-array values (undefined/null) pass through unchanged', () => {
        const b = chartBuilderMixin();
        expect(b.resolveColor(undefined, style)).toBeUndefined();
        expect(b.resolveColor(null, style)).toBeNull();
    });
});

describe('chartBuilderMixin.buildOptions — merge logic', () => {
    function withMockedCssVars() {
        const b = chartBuilderMixin();
        b.getCssVar = vi.fn((name) => name.includes('on-surface-variant') ? 'TEXT' : 'GRID');
        return b;
    }

    test('injects text/grid colors into every axis scale that does not already specify its own', () => {
        const b = withMockedCssVars();
        const out = b.buildOptions({ options: { scales: { x: {}, y: { ticks: { stepSize: 5 } } } } });

        expect(out.scales.x.ticks).toEqual({ color: 'TEXT' });
        expect(out.scales.x.grid).toEqual({ color: 'GRID' });
        expect(out.scales.y.ticks).toEqual({ stepSize: 5, color: 'TEXT' });
    });

    test('caller-specified ticks/grid color wins over the computed default', () => {
        const b = withMockedCssVars();
        const out = b.buildOptions({ options: { scales: { x: { ticks: { color: 'red' } } } } });
        expect(out.scales.x.ticks.color).toBe('red');
    });

    test('ticks: false / grid: false (explicit axis disable) is preserved, not merged into an object', () => {
        const b = withMockedCssVars();
        const out = b.buildOptions({ options: { scales: { x: { ticks: false, grid: false } } } });
        expect(out.scales.x.ticks).toBe(false);
        expect(out.scales.x.grid).toBe(false);
    });

    test('a config with no scales at all does not throw and yields an empty scales object', () => {
        const b = withMockedCssVars();
        expect(() => b.buildOptions({})).not.toThrow();
        expect(b.buildOptions({}).scales).toEqual({});
    });

    test('animation defaults to {duration:700, easing:easeOutQuad} unless the caller overrides fields or disables it entirely', () => {
        const b = withMockedCssVars();
        expect(b.buildOptions({}).animation).toEqual({ duration: 700, easing: 'easeOutQuad' });
        expect(b.buildOptions({ options: { animation: { duration: 200 } } }).animation).toEqual({ duration: 200, easing: 'easeOutQuad' });
        expect(b.buildOptions({ options: { animation: false } }).animation).toBe(false);
    });

    test('legend labels color is injected while preserving other legend/plugin config via spread', () => {
        const b = withMockedCssVars();
        const out = b.buildOptions({ options: { plugins: { legend: { position: 'bottom' }, customPlugin: { on: true } } } });
        expect(out.plugins.legend).toEqual({ position: 'bottom', labels: { color: 'TEXT' } });
        expect(out.plugins.customPlugin).toEqual({ on: true });
    });

    test('tooltip rtl defaults true, a caller override wins, and tooltip:false is preserved', () => {
        const b = withMockedCssVars();
        expect(b.buildOptions({}).plugins.tooltip).toEqual({ rtl: true });
        expect(b.buildOptions({ options: { plugins: { tooltip: { rtl: false, mode: 'index' } } } }).plugins.tooltip).toEqual({ rtl: false, mode: 'index' });
        expect(b.buildOptions({ options: { plugins: { tooltip: false } } }).plugins.tooltip).toBe(false);
    });

    test('responsive/maintainAspectRatio are always forced regardless of caller input', () => {
        const b = withMockedCssVars();
        const out = b.buildOptions({ options: { responsive: false, maintainAspectRatio: true } });
        expect(out.responsive).toBe(true);
        expect(out.maintainAspectRatio).toBe(false);
    });
});

describe('chartBuilderMixin.buildData — dataset color resolution', () => {
    test('resolves backgroundColor/borderColor per dataset via resolveColor, preserving other dataset fields', () => {
        const b = chartBuilderMixin();
        b.resolveColor = vi.fn((v) => `R(${v})`);

        const out = b.buildData({ labels: ['a', 'b'], datasets: [{ label: 'S1', data: [1, 2], backgroundColor: 'var(--x)', borderColor: 'var(--y)' }] });

        expect(out.labels).toEqual(['a', 'b']);
        expect(out.datasets[0]).toEqual({ label: 'S1', data: [1, 2], backgroundColor: 'R(var(--x))', borderColor: 'R(var(--y))' });
    });

    test('data.datasets undefined yields an empty datasets array, not a throw', () => {
        const b = chartBuilderMixin();
        b.resolveColor = vi.fn();
        expect(b.buildData({ labels: [] }).datasets).toEqual([]);
    });
});
