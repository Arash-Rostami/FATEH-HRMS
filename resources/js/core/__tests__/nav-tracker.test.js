import { describe, expect, test, beforeEach, afterEach } from 'vitest'
import initNavTracker, {
    MAX_VISITS,
    VISITS_KEY,
    clearVisits,
    readVisits,
    recordVisit,
    visitIdForItem,
    visitIdForPath,
    visitIdForTab,
} from '../nav-tracker.js'

beforeEach(() => {
    localStorage.clear()
    history.pushState({}, '', '/')
})

describe('visit ids', () => {
    test('a module path becomes a route id, ignoring a trailing slash and the query string', () => {
        expect(visitIdForPath('/projects')).toBe('route:/projects')
        expect(visitIdForPath('/projects/')).toBe('route:/projects')
    })

    test('the root and the dashboard are never recorded as a route', () => {
        expect(visitIdForPath('/')).toBeNull()
        expect(visitIdForPath('/dashboard')).toBeNull()
        expect(visitIdForPath('')).toBeNull()
    })

    test('a tab becomes a tab id, but home and empty are ignored', () => {
        expect(visitIdForTab('reports')).toBe('tab:reports')
        expect(visitIdForTab('home')).toBeNull()
        expect(visitIdForTab(undefined)).toBeNull()
    })

    test('a catalog item maps to the same id its visit is recorded under', () => {
        expect(visitIdForItem({ type: 'tab', target: 'reports', url: null })).toBe('tab:reports')
        expect(visitIdForItem({ type: 'route', target: 'projects', url: 'http://localhost/projects' })).toBe('route:/projects')
        expect(visitIdForItem({ type: 'tab', target: 'home', url: null })).toBeNull()
        expect(visitIdForItem({ type: 'route', url: null })).toBeNull()
    })
})

describe('recordVisit', () => {
    test('puts the newest first and moves a repeated id to the front without duplicating', () => {
        recordVisit('route:/a')
        recordVisit('route:/b')
        recordVisit('route:/a')
        expect(readVisits()).toEqual(['route:/a', 'route:/b'])
    })

    test('keeps only the newest MAX_VISITS entries', () => {
        for (let i = 0; i < MAX_VISITS + 3; i++) recordVisit(`route:/m${i}`)
        const visits = readVisits()
        expect(visits).toHaveLength(MAX_VISITS)
        expect(visits[0]).toBe(`route:/m${MAX_VISITS + 2}`)
    })

    test('does not rewrite storage when the id is already the newest', () => {
        recordVisit('route:/a')
        const before = localStorage.getItem(VISITS_KEY)
        localStorage.setItem(VISITS_KEY, before + ' ')
        recordVisit('route:/a')
        expect(localStorage.getItem(VISITS_KEY)).toBe(before + ' ')
    })

    test('ignores an empty id', () => {
        recordVisit(null)
        expect(readVisits()).toEqual([])
    })

    test('clearVisits empties the history', () => {
        recordVisit('route:/a')
        clearVisits()
        expect(readVisits()).toEqual([])
        expect(localStorage.getItem(VISITS_KEY)).toBeNull()
    })

    test('a corrupt stored value reads as an empty history', () => {
        localStorage.setItem(VISITS_KEY, '{not json')
        expect(readVisits()).toEqual([])
        localStorage.setItem(VISITS_KEY, '"text"')
        expect(readVisits()).toEqual([])
    })
})

describe('initNavTracker', () => {
    let stop

    afterEach(() => stop?.())

    test('records the page it starts on', () => {
        history.pushState({}, '', '/projects')
        stop = initNavTracker()
        expect(readVisits()).toEqual(['route:/projects'])
    })

    test('records the destination after an in-app navigation', () => {
        stop = initNavTracker()
        history.pushState({}, '', '/dms')
        document.dispatchEvent(new Event('livewire:navigated'))
        expect(readVisits()).toEqual(['route:/dms'])
    })

    test('records an in-page tab switch from any source and ignores home', () => {
        stop = initNavTracker()
        window.dispatchEvent(new CustomEvent('tab-switched', { detail: { tab: 'calendar' } }))
        window.dispatchEvent(new CustomEvent('tab-switched', { detail: { tab: 'home' } }))
        expect(readVisits()).toEqual(['tab:calendar'])
    })

    test('the returned disposer stops all recording', () => {
        stop = initNavTracker()
        stop()
        window.dispatchEvent(new CustomEvent('tab-switched', { detail: { tab: 'calendar' } }))
        history.pushState({}, '', '/dms')
        document.dispatchEvent(new Event('livewire:navigated'))
        expect(readVisits()).toEqual([])
    })
})
