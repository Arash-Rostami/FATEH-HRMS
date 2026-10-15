import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'
import taskboard from '../taskboard.js'

const FAVORITES_KEY = 'taskboard-favorites'
const FILTERS_OPEN_KEY = 'taskboard-filters-open'
const SWIPE_HINT_KEY = 'taskboard-swipe-hint-dismissed'
const COLLAPSED_PREFIX = 'taskboard-collapsed-'

beforeEach(() => {
    localStorage.clear()
    document.body.innerHTML = ''
})

describe('taskboard.loadFavorites', () => {
    test('with nothing stored, resets favorites to an empty array/set', () => {
        const c = taskboard()
        c.loadFavorites()
        expect(c.favorites).toEqual([])
        expect(c.isFavorite('x')).toBe(false)
    })

    test('restores a valid stored favorites list', () => {
        localStorage.setItem(FAVORITES_KEY, JSON.stringify(['a', 'b']))
        const c = taskboard()
        c.loadFavorites()
        expect(c.favorites).toEqual(['a', 'b'])
        expect(c.isFavorite('a')).toBe(true)
    })

    test('corrupt JSON falls back to an empty list without throwing', () => {
        localStorage.setItem(FAVORITES_KEY, '{not json')
        const c = taskboard()
        expect(() => c.loadFavorites()).not.toThrow()
        expect(c.favorites).toEqual([])
        expect(c.isFavorite('a')).toBe(false)
    })
})

describe('taskboard.toggleFavorite', () => {
    test('adds an id not already favorited and persists', () => {
        const c = taskboard()
        c.loadFavorites()
        c.toggleFavorite(1)
        expect(c.favorites).toEqual([1])
        expect(c.isFavorite(1)).toBe(true)
        expect(JSON.parse(localStorage.getItem(FAVORITES_KEY))).toEqual([1])
    })

    test('removes an id already favorited and persists', () => {
        const c = taskboard()
        c.loadFavorites()
        c.toggleFavorite(1)
        c.toggleFavorite(1)
        expect(c.favorites).toEqual([])
        expect(c.isFavorite(1)).toBe(false)
    })
})

describe('taskboard.toggleFavoritesOnly / toggleFilters / dismissSwipeHint', () => {
    test('toggleFavoritesOnly flips the flag with no persistence', () => {
        const c = taskboard()
        c.toggleFavoritesOnly()
        expect(c.showFavoritesOnly).toBe(true)
        expect(localStorage.getItem('taskboard-favorites-only')).toBeNull()
    })

    test('toggleFilters flips and persists "1"/"0"', () => {
        const c = taskboard()
        c.toggleFilters()
        expect(c.filtersOpen).toBe(true)
        expect(localStorage.getItem(FILTERS_OPEN_KEY)).toBe('1')
        c.toggleFilters()
        expect(localStorage.getItem(FILTERS_OPEN_KEY)).toBe('0')
    })

    test('dismissSwipeHint sets the flag and persists "1"', () => {
        const c = taskboard()
        c.dismissSwipeHint()
        expect(c.swipeHintDismissed).toBe(true)
        expect(localStorage.getItem(SWIPE_HINT_KEY)).toBe('1')
    })
})

describe('taskboard.isCollapsed / toggleCollapsed', () => {
    test('toggleCollapsed flips per-column state and persists under a column-specific key', () => {
        const c = taskboard()
        expect(c.isCollapsed('todo')).toBe(false)
        c.toggleCollapsed('todo')
        expect(c.isCollapsed('todo')).toBe(true)
        expect(localStorage.getItem(COLLAPSED_PREFIX + 'todo')).toBe('1')

        c.toggleCollapsed('todo')
        expect(c.isCollapsed('todo')).toBe(false)
        expect(localStorage.getItem(COLLAPSED_PREFIX + 'todo')).toBe('0')
    })
})

describe('taskboard.toggleMaximize', () => {
    test('opening a column sets maximizedColumn, clears spotlight, and calls applyMaximize(true)', () => {
        const c = taskboard()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.toggleMaximize('todo')
        expect(c.maximizedColumn).toBe('todo')
        expect(spy).toHaveBeenCalledWith(true)
    })

    test('opening persists the column name via _persistMaximize', () => {
        const c = taskboard()
        const spy = vi.spyOn(c, '_persistMaximize')
        c.toggleMaximize('todo')
        expect(spy).toHaveBeenCalledWith('todo')
    })

    test('toggling the already-maximized column closes it', () => {
        const c = taskboard()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.maximizedColumn = 'todo'
        c.toggleMaximize('todo')
        expect(c.maximizedColumn).toBeNull()
        expect(spy).toHaveBeenCalledWith(false)
    })

    test('closing persists null via _persistMaximize', () => {
        const c = taskboard()
        c.maximizedColumn = 'todo'
        const spy = vi.spyOn(c, '_persistMaximize')
        c.toggleMaximize('todo')
        expect(spy).toHaveBeenCalledWith(null)
    })
})

describe('taskboard.restoreMaximize', () => {
    test('a value matching an existing [data-column] element re-applies maximize', () => {
        document.body.innerHTML = '<div data-column="todo"></div>'
        const c = taskboard()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.restoreMaximize('todo')
        expect(c.maximizedColumn).toBe('todo')
        expect(spy).toHaveBeenCalledWith(true)
    })

    test('a stale value with no matching [data-column] element cleans up and does nothing else', () => {
        document.body.innerHTML = '<div data-column="todo"></div>'
        const c = taskboard()
        const persistSpy = vi.spyOn(c, '_persistMaximize')
        const applySpy = vi.spyOn(c, 'applyMaximize')
        c.restoreMaximize('gone')
        expect(persistSpy).toHaveBeenCalledWith(null)
        expect(applySpy).not.toHaveBeenCalled()
        expect(c.maximizedColumn).toBeNull()
    })
})

describe('taskboard.afterSpotlightChange — maximize/spotlight mutual exclusion', () => {
    test('spotlighting a column while another is maximized forces the maximize to close', () => {
        const c = taskboard()
        c.maximizedColumn = 'todo'
        const spy = vi.spyOn(c, 'applyMaximize')

        c.toggleSpotlight('done')

        expect(c.spotlightColumn).toBe('done')
        expect(c.maximizedColumn).toBeNull()
        expect(spy).toHaveBeenCalledWith(false)
    })

    test('spotlighting while another column is maximized also persists the maximize clear', () => {
        const c = taskboard()
        c.maximizedColumn = 'todo'
        const spy = vi.spyOn(c, '_persistMaximize')

        c.toggleSpotlight('done')

        expect(spy).toHaveBeenCalledWith(null)
    })

    test('spotlighting with nothing maximized does not touch maximize state', () => {
        const c = taskboard()
        const spy = vi.spyOn(c, 'applyMaximize')

        c.toggleSpotlight('done')

        expect(c.spotlightColumn).toBe('done')
        expect(c.maximizedColumn).toBeNull()
        expect(spy).not.toHaveBeenCalled()
    })
})

describe('taskboard.init', () => {
    test('restores per-column collapsed state from localStorage based on [data-column] elements in the DOM', () => {
        document.body.innerHTML = '<div data-column="todo"></div><div data-column="done"></div>'
        localStorage.setItem(COLLAPSED_PREFIX + 'todo', '1')

        const c = taskboard()
        c.$watch = vi.fn()
        c.init()

        expect(c.isCollapsed('todo')).toBe(true)
        expect(c.isCollapsed('done')).toBe(false)
    })

    test('restores filtersOpen/swipeHintDismissed booleans from localStorage', () => {
        localStorage.setItem(FILTERS_OPEN_KEY, '1')
        localStorage.setItem(SWIPE_HINT_KEY, '1')

        const c = taskboard()
        c.$watch = vi.fn()
        c.init()

        expect(c.filtersOpen).toBe(true)
        expect(c.swipeHintDismissed).toBe(true)
    })

    test('registers the kanban-drag $watch on dragTask', () => {
        const c = taskboard()
        c.$watch = vi.fn()
        c.init()
        expect(c.$watch).toHaveBeenCalledWith('dragTask', expect.any(Function))
    })
})

describe('taskboard header declutter', () => {
    const SETTINGS_KEY = 'table-tools:taskboard'
    const instance = () => {
        const c = taskboard()
        c.$watch = vi.fn()
        return c
    }

    afterEach(() => vi.useRealTimers())

    test('manual collapse persists after the 400ms exit window and round-trips through init', () => {
        vi.useFakeTimers()
        localStorage.setItem(SETTINGS_KEY, 'false')

        const c = instance()
        c.init()
        expect(c.headerDeclutter).toBe(false)

        c.toggleHeaderDeclutter()
        expect(c.headerDeclutterExit).toBe(true)
        vi.advanceTimersByTime(400)
        expect(c.headerDeclutter).toBe(true)
        expect(localStorage.getItem(SETTINGS_KEY)).toBe('true')

        const c2 = instance()
        c2.init()
        expect(c2.headerDeclutter).toBe(true)
    })

    test('manual expand persists immediately; corrupt stored settings fall back to expanded', () => {
        localStorage.setItem(SETTINGS_KEY, '{not json')

        const c = instance()
        c.init()
        expect(c.headerDeclutter).toBe(false)

        localStorage.removeItem(SETTINGS_KEY)
        c.headerDeclutter = true
        c.toggleHeaderDeclutter()
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(SETTINGS_KEY)).toBe('false')
    })

    test('the hamburger-menu bridge forces collapse without persisting; OFF persists and clears _menuForced', () => {
        const c = instance()
        c.init()

        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        expect(c.headerDeclutter).toBe(true)
        expect(localStorage.getItem(SETTINGS_KEY)).toBeNull()

        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }))
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(SETTINGS_KEY)).toBe('false')

        c.toggleHeaderDeclutter()
        expect(c.headerDeclutter).toBe(false)

        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        c.toggleHeaderDeclutter()
        expect(c.headerDeclutter).toBe(false)
        expect(localStorage.getItem(SETTINGS_KEY)).toBe('false')

        c.destroy()
        window.dispatchEvent(new CustomEvent('declutter', { detail: { on: true } }))
        expect(c.headerDeclutter).toBe(false)
    })

    test('on boot it syncs collapsed from the persisted menu_declutter pref', () => {
        localStorage.setItem('menu_declutter', 'true')
        const c = instance()
        c.init()
        expect(c.headerDeclutter).toBe(true)
        localStorage.removeItem('menu_declutter')
    })
})

describe('taskboard composition with taskFormMixin', () => {
    test('merges taskFormMixin methods/getters via defineProperties, bound to the board instance', () => {
        const c = taskboard()
        c.$wire = { form: { labels: [] }, set: vi.fn() }
        c.syncFormArrays()
        c.newLabel = 'urgent'

        c.addLabelItem()

        expect(c.labels).toEqual(['urgent'])
        expect(c.labelsCount).toBe(1)
        expect(c.$wire.set).toHaveBeenCalledWith('form.labels', ['urgent'], false)
    })
})
