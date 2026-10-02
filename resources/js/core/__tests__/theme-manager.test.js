import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

function mockMatchMedia(matches) {
    window.matchMedia = vi.fn().mockImplementation((query) => ({
        matches,
        media: query,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
    }))
}

let ThemeManager

beforeEach(async () => {
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
    document.documentElement.classList.remove('dark')
    delete window.Alpine
    mockMatchMedia(false)
    vi.resetModules()
    ;({ default: ThemeManager } = await import('../theme-manager.js'))
})

afterEach(() => {
    vi.restoreAllMocks()
    document.documentElement.removeAttribute('data-theme')
    document.documentElement.classList.remove('dark')
    document.head.querySelectorAll('meta[name="theme-color"]').forEach((m) => m.remove())
})

describe('ThemeManager.getColors', () => {
    test('returns the shared THEME_COLORS list', () => {
        const colors = ThemeManager.getColors()
        expect(colors.length).toBeGreaterThan(0)
        expect(colors).toEqual(expect.arrayContaining([expect.objectContaining({ name: 'default' })]))
    })
})

describe('ThemeManager.getSystemMode', () => {
    test('returns "dark" when the media query matches', () => {
        mockMatchMedia(true)
        ThemeManager._mediaQuery = null
        expect(ThemeManager.getSystemMode()).toBe('dark')
    })

    test('returns "light" when the media query does not match', () => {
        mockMatchMedia(false)
        ThemeManager._mediaQuery = null
        expect(ThemeManager.getSystemMode()).toBe('light')
    })

    test('caches the MediaQueryList after the first call', () => {
        mockMatchMedia(false)
        ThemeManager._mediaQuery = null
        ThemeManager.getSystemMode()
        const cached = ThemeManager._mediaQuery
        ThemeManager.getSystemMode()
        expect(ThemeManager._mediaQuery).toBe(cached)
        expect(window.matchMedia).toHaveBeenCalledTimes(1)
    })
})

describe('ThemeManager.getUserMode', () => {
    test('falls back to the system mode when nothing is stored', () => {
        mockMatchMedia(true)
        ThemeManager._mediaQuery = null
        expect(ThemeManager.getUserMode()).toBe('dark')
    })

    test('prefers the stored value over the system mode', () => {
        localStorage.setItem('user-mode', 'light')
        mockMatchMedia(true)
        ThemeManager._mediaQuery = null
        expect(ThemeManager.getUserMode()).toBe('light')
    })
})

describe('ThemeManager.getUserTheme', () => {
    test('defaults to "default" when nothing is stored', () => {
        expect(ThemeManager.getUserTheme()).toBe('default')
    })

    test('returns the stored theme name', () => {
        localStorage.setItem('user-theme', 'blue')
        expect(ThemeManager.getUserTheme()).toBe('blue')
    })
})

describe('ThemeManager.init', () => {
    test('exposes itself on window.ThemeManager and window.AdminThemeManager at import time', () => {
        expect(window.ThemeManager).toBe(ThemeManager)
        expect(window.AdminThemeManager).toBe(ThemeManager)
    })

    test('is idempotent — a second call does not re-run applyPreferences', () => {
        const spy = vi.spyOn(ThemeManager, 'applyPreferences')
        ThemeManager.init()
        expect(spy).not.toHaveBeenCalled()
    })
})

describe('ThemeManager.applyThemeDOM', () => {
    test('removes the data-theme attribute for the "default" theme', () => {
        document.documentElement.setAttribute('data-theme', 'blue')
        ThemeManager.applyThemeDOM('default')
        expect(document.documentElement.hasAttribute('data-theme')).toBe(false)
    })

    test('sets data-theme for a non-default theme', () => {
        ThemeManager.applyThemeDOM('midnight')
        expect(document.documentElement.getAttribute('data-theme')).toBe('midnight')
    })
})

describe('ThemeManager.applyModeDOM', () => {
    test('adds the dark class for mode "dark"', () => {
        ThemeManager.applyModeDOM('dark')
        expect(document.documentElement.classList.contains('dark')).toBe(true)
    })

    test('removes the dark class for any non-dark mode', () => {
        document.documentElement.classList.add('dark')
        ThemeManager.applyModeDOM('light')
        expect(document.documentElement.classList.contains('dark')).toBe(false)
    })
})

describe('ThemeManager.setTheme', () => {
    test('applies the DOM theme, persists it, and dispatches the sync event', () => {
        const handler = vi.fn()
        window.addEventListener('theme-system-updated', handler)

        ThemeManager.setTheme('ember')

        expect(document.documentElement.getAttribute('data-theme')).toBe('ember')
        expect(localStorage.getItem('user-theme')).toBe('ember')
        expect(handler).toHaveBeenCalledTimes(1)
        window.removeEventListener('theme-system-updated', handler)
    })
})

describe('ThemeManager.toggleMode', () => {
    test('flips from light to dark and persists it', () => {
        localStorage.setItem('user-mode', 'light')
        ThemeManager.toggleMode()
        expect(document.documentElement.classList.contains('dark')).toBe(true)
        expect(localStorage.getItem('user-mode')).toBe('dark')
    })

    test('flips from dark to light and persists it', () => {
        localStorage.setItem('user-mode', 'dark')
        ThemeManager.toggleMode()
        expect(document.documentElement.classList.contains('dark')).toBe(false)
        expect(localStorage.getItem('user-mode')).toBe('light')
    })
})

describe('ThemeManager.syncThemeColorMeta', () => {
    test('is a no-op when there is no theme-color meta tag', () => {
        expect(() => ThemeManager.syncThemeColorMeta()).not.toThrow()
    })

    test('updates the meta content from the computed --md-sys-color-primary value when present', () => {
        const meta = document.createElement('meta')
        meta.setAttribute('name', 'theme-color')
        meta.setAttribute('content', 'old')
        document.head.appendChild(meta)
        document.documentElement.style.setProperty('--md-sys-color-primary', '#123456')

        ThemeManager.syncThemeColorMeta()

        expect(meta.getAttribute('content')).toBe('#123456')
        document.documentElement.style.removeProperty('--md-sys-color-primary')
    })

    test('leaves the meta content untouched when the computed value is empty', () => {
        const meta = document.createElement('meta')
        meta.setAttribute('name', 'theme-color')
        meta.setAttribute('content', 'unchanged')
        document.head.appendChild(meta)

        ThemeManager.syncThemeColorMeta()

        expect(meta.getAttribute('content')).toBe('unchanged')
    })
})

describe('ThemeManager.syncStore', () => {
    test('no-ops when there is no appTheme Alpine store', () => {
        window.Alpine = { store: vi.fn(() => undefined) }
        expect(() => ThemeManager.syncStore('blue', 'dark')).not.toThrow()
    })

    test('no-ops when window.Alpine itself is not present', () => {
        expect(() => ThemeManager.syncStore('blue', 'dark')).not.toThrow()
    })

    test('updates the store state with the forced theme/mode when given', () => {
        const updateState = vi.fn()
        window.Alpine = { store: vi.fn(() => ({ updateState })) }
        ThemeManager.syncStore('ember', 'dark')
        expect(updateState).toHaveBeenCalledWith('ember', 'dark')
    })

    test('falls back to the persisted user theme/mode when not given', () => {
        localStorage.setItem('user-theme', 'jade')
        localStorage.setItem('user-mode', 'dark')
        const updateState = vi.fn()
        window.Alpine = { store: vi.fn(() => ({ updateState })) }
        ThemeManager.syncStore()
        expect(updateState).toHaveBeenCalledWith('jade', 'dark')
    })
})

describe('ThemeManager.dispatchSyncEvent', () => {
    test('dispatches a theme-system-updated CustomEvent on window', () => {
        const handler = vi.fn()
        window.addEventListener('theme-system-updated', handler)
        ThemeManager.dispatchSyncEvent()
        expect(handler).toHaveBeenCalledTimes(1)
        window.removeEventListener('theme-system-updated', handler)
    })
})

describe('ThemeManager.applyPreferences', () => {
    test('applies the stored theme/mode to the DOM and dispatches the sync event', () => {
        localStorage.setItem('user-theme', 'nebula')
        localStorage.setItem('user-mode', 'dark')
        const handler = vi.fn()
        window.addEventListener('theme-system-updated', handler)

        ThemeManager.applyPreferences()

        expect(document.documentElement.getAttribute('data-theme')).toBe('nebula')
        expect(document.documentElement.classList.contains('dark')).toBe(true)
        expect(handler).toHaveBeenCalled()
        window.removeEventListener('theme-system-updated', handler)
    })
})

describe('ThemeManager — window "storage" listener wired by init()', () => {
    test('re-applies preferences when the changed key is the theme key', () => {
        const spy = vi.spyOn(ThemeManager, 'applyPreferences')
        window.dispatchEvent(new StorageEvent('storage', { key: 'user-theme' }))
        expect(spy).toHaveBeenCalled()
    })

    test('re-applies preferences when the changed key is the mode key', () => {
        const spy = vi.spyOn(ThemeManager, 'applyPreferences')
        window.dispatchEvent(new StorageEvent('storage', { key: 'user-mode' }))
        expect(spy).toHaveBeenCalled()
    })

    test('ignores unrelated storage keys', () => {
        const spy = vi.spyOn(ThemeManager, 'applyPreferences')
        window.dispatchEvent(new StorageEvent('storage', { key: 'something-else' }))
        expect(spy).not.toHaveBeenCalled()
    })
})

describe('ThemeManager — document "livewire:navigated" listener wired by init()', () => {
    test('re-applies preferences on navigation', () => {
        const spy = vi.spyOn(ThemeManager, 'applyPreferences')
        document.dispatchEvent(new Event('livewire:navigated'))
        expect(spy).toHaveBeenCalled()
    })
})

describe('ThemeManager — document "alpine:init" listener wired by init()', () => {
    test('syncs the Alpine store on alpine:init', () => {
        const spy = vi.spyOn(ThemeManager, 'syncStore')
        document.dispatchEvent(new Event('alpine:init'))
        expect(spy).toHaveBeenCalled()
    })
})
