import { describe, expect, test, vi, afterEach } from 'vitest'
import appTheme, { THEME_COLORS } from '../theme.js'

function freshStore() {
    let def = null
    appTheme({ store: (name, d) => { def = d } })
    return def
}

afterEach(() => {
    delete window.ThemeManager
})

describe('THEME_COLORS', () => {
    test('every entry has a unique name and a unique color (no accidental duplicate theme)', () => {
        const names = THEME_COLORS.map((c) => c.name)
        const colors = THEME_COLORS.map((c) => c.color)
        expect(new Set(names).size).toBe(names.length)
        expect(new Set(colors).size).toBe(colors.length)
    })

    test('every entry has a non-empty name/color/title', () => {
        for (const c of THEME_COLORS) {
            expect(c.name).toBeTruthy()
            expect(c.color).toBeTruthy()
            expect(c.title).toBeTruthy()
        }
    })
})

describe('appTheme initial state', () => {
    test('falls back to default/light when window.ThemeManager is absent', () => {
        delete window.ThemeManager
        const s = freshStore()
        expect(s.current).toBe('default')
        expect(s.mode).toBe('light')
    })

    test('reads the initial theme/mode from window.ThemeManager when present', () => {
        window.ThemeManager = { getUserTheme: () => 'blue', getUserMode: () => 'dark' }
        const s = freshStore()
        expect(s.current).toBe('blue')
        expect(s.mode).toBe('dark')
    })

    test('exposes the full THEME_COLORS list as colors', () => {
        const s = freshStore()
        expect(s.colors).toBe(THEME_COLORS)
    })
})

describe('appTheme.set / toggleMode', () => {
    test('set delegates to window.ThemeManager.setTheme with the given theme', () => {
        window.ThemeManager = { getUserTheme: () => 'default', getUserMode: () => 'light', setTheme: vi.fn() }
        const s = freshStore()
        s.set('jade')
        expect(window.ThemeManager.setTheme).toHaveBeenCalledWith('jade')
    })

    test('set does not throw when window.ThemeManager is absent', () => {
        delete window.ThemeManager
        const s = freshStore()
        expect(() => s.set('jade')).not.toThrow()
    })

    test('toggleMode delegates to window.ThemeManager.toggleMode', () => {
        window.ThemeManager = { getUserTheme: () => 'default', getUserMode: () => 'light', toggleMode: vi.fn() }
        const s = freshStore()
        s.toggleMode()
        expect(window.ThemeManager.toggleMode).toHaveBeenCalled()
    })

    test('toggleMode does not throw when window.ThemeManager is absent', () => {
        delete window.ThemeManager
        const s = freshStore()
        expect(() => s.toggleMode()).not.toThrow()
    })
})

describe('appTheme.updateState', () => {
    test('sets current and mode directly, independent of ThemeManager', () => {
        const s = freshStore()
        s.updateState('ember', 'dark')
        expect(s.current).toBe('ember')
        expect(s.mode).toBe('dark')
    })
})
