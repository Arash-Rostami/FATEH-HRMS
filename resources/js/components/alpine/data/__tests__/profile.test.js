import { beforeEach, describe, expect, test, vi } from 'vitest'
import profile from '../profile.js'

beforeEach(() => {
    window.scrollTo = vi.fn();
});

describe('profile step navigation', () => {
    test('nextStep increments while below maxSteps and scrolls to top', () => {
        const p = profile();
        p.nextStep();
        expect(p.step).toBe(2);
        expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    });

    test('nextStep is a no-op at maxSteps and does not scroll', () => {
        const p = profile();
        p.step = p.maxSteps;
        p.nextStep();
        expect(p.step).toBe(p.maxSteps);
        expect(window.scrollTo).not.toHaveBeenCalled();
    });

    test('prevStep decrements while above 1 and scrolls to top', () => {
        const p = profile();
        p.step = 2;
        p.prevStep();
        expect(p.step).toBe(1);
        expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    });

    test('prevStep is a no-op at step 1 and does not scroll', () => {
        const p = profile();
        p.prevStep();
        expect(p.step).toBe(1);
        expect(window.scrollTo).not.toHaveBeenCalled();
    });

    test('setStep jumps directly to any step within [1, maxSteps] and scrolls', () => {
        const p = profile();
        p.setStep(3);
        expect(p.step).toBe(3);
        expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    });

    test('setStep ignores an out-of-range target and does not scroll', () => {
        const p = profile();
        p.setStep(0);
        expect(p.step).toBe(1);
        p.setStep(4);
        expect(p.step).toBe(1);
        expect(window.scrollTo).not.toHaveBeenCalled();
    });
});

describe('profile.setDirection', () => {
    test('sets direction to rtl when the field value contains Persian/Arabic/Hebrew script', () => {
        const p = profile();
        const el = { value: 'سلام دنیا', style: {} };
        p.setDirection({ target: el });
        expect(el.style.direction).toBe('rtl');
    });

    test('sets direction to ltr for plain Latin text', () => {
        const p = profile();
        const el = { value: 'hello world', style: {} };
        p.setDirection({ target: el });
        expect(el.style.direction).toBe('ltr');
    });

    test('accepts a bare element (no .target) as the argument, falling back to e itself', () => {
        const p = profile();
        const el = { value: '123', style: {} };
        p.setDirection(el);
        expect(el.style.direction).toBe('ltr');
    });
});
