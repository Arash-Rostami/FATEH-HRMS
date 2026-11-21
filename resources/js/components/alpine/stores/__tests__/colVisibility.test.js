import { describe, expect, test, vi, beforeEach } from 'vitest'
import registerColVisibility from '../colVisibility.js'

function fresh() {
    let captured
    registerColVisibility({ store: (name, def) => { captured = def }, raw: (x) => x })
    return captured
}

const flushMicrotask = () => Promise.resolve()
const flushRaf = () => new Promise((r) => setTimeout(r, 0))

beforeEach(() => {
    localStorage.clear()
    global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
})

describe('colVisibility.init', () => {
    test('restores a valid saved array per domain', () => {
        localStorage.setItem('dms-col-hidden', JSON.stringify(['a', 'b']))
        localStorage.setItem('ths-col-hidden', JSON.stringify(['x']))
        const s = fresh()
        s.init()
        expect(s.maps.dms).toEqual(['a', 'b'])
        expect(s.maps.ths).toEqual(['x'])
        expect(s.maps.reservation).toEqual([])
    })

    test('ignores corrupt JSON for a domain and keeps it at the default empty array', () => {
        localStorage.setItem('dms-col-hidden', '{not json')
        const s = fresh()
        expect(() => s.init()).not.toThrow()
        expect(s.maps.dms).toEqual([])
    })

    test('ignores a saved value that parses but is not an array', () => {
        localStorage.setItem('dms-col-hidden', JSON.stringify({ a: 1 }))
        const s = fresh()
        s.init()
        expect(s.maps.dms).toEqual([])
    })
})

describe('colVisibility getters', () => {
    test('hidden/thsHidden/reservationHidden expose the per-domain maps', () => {
        const s = fresh()
        s.maps = { dms: ['a'], ths: ['b'], reservation: ['c'] }
        expect(s.hidden).toEqual(['a'])
        expect(s.thsHidden).toEqual(['b'])
        expect(s.reservationHidden).toEqual(['c'])
    })
})

describe('colVisibility.isHidden', () => {
    test('true when the key is present in the scope\'s map, false when absent', () => {
        const s = fresh()
        s.maps.dms = ['col1']
        expect(s.isHidden('col1')).toBe(true)
        expect(s.isHidden('col2')).toBe(false)
    })

    test('an unknown scope short-circuits to undefined, not strictly false', () => {
        const s = fresh()
        expect(s.isHidden('col1', 'unknownScope')).toBeUndefined()
    })
})

describe('colVisibility.toggle', () => {
    test('adds an absent key and removes a present one, for the given scope (default "dms")', () => {
        const s = fresh()
        s.toggle('col1')
        expect(s.maps.dms).toEqual(['col1'])

        s.toggle('col1')
        expect(s.maps.dms).toEqual([])
    })

    test('persists the new map to localStorage (microtask-deferred)', async () => {
        const s = fresh()
        s.toggle('col1', 'ths')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('ths-col-hidden'))).toEqual(['col1'])
    })

    test('schedules a style-sheet render reflecting the hidden column, via requestAnimationFrame', async () => {
        const s = fresh()
        s.toggle('col1', 'dms')
        await flushRaf()

        const styleEl = document.getElementById('dms-col-visibility-style')
        expect(styleEl.textContent).toContain('.dms-doc-table [data-col="col1"]{display:none!important}')
    })
})

describe('colVisibility.reset', () => {
    test('empties the scope\'s map, persists it, and re-renders', async () => {
        const s = fresh()
        s.maps.reservation = ['a', 'b']

        s.reset('reservation')
        expect(s.maps.reservation).toEqual([])

        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('reservation-col-hidden'))).toEqual([])
    })
})

describe('colVisibility taskboard scope', () => {
    test('restores, persists to taskboard-col-hidden, renders the card rule and resets', async () => {
        localStorage.setItem('taskboard-col-hidden', JSON.stringify(['labels']))
        const s = fresh()
        s.init()
        expect(s.maps.taskboard).toEqual(['labels'])

        s.toggle('people', 'taskboard')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('taskboard-col-hidden'))).toEqual(['labels', 'people'])

        await flushRaf()
        const css = document.getElementById('dms-col-visibility-style').textContent
        expect(css).toContain('.taskboard-card [data-col="labels"]{display:none!important}')
        expect(css).toContain('.taskboard-card [data-col="people"]{display:none!important}')

        s.reset('taskboard')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('taskboard-col-hidden'))).toEqual([])
    })
})

describe('colVisibility ai-report scope', () => {
    test('restores, persists to ai-report-col-hidden, renders the table rule and resets', async () => {
        localStorage.setItem('ai-report-col-hidden', JSON.stringify(['owner']))
        const s = fresh()
        s.init()
        expect(s.maps['ai-report']).toEqual(['owner'])
        expect(s.isHidden('owner', 'ai-report')).toBe(true)

        s.toggle('state', 'ai-report')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('ai-report-col-hidden'))).toEqual(['owner', 'state'])

        await flushRaf()
        const css = document.getElementById('dms-col-visibility-style').textContent
        expect(css).toContain('.ai-report-table [data-col="owner"]{display:none!important}')
        expect(css).toContain('.ai-report-table [data-col="state"]{display:none!important}')

        s.reset('ai-report')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('ai-report-col-hidden'))).toEqual([])
        expect(s.maps['ai-report']).toEqual([])
    })

    test('other scopes keep their own keys and the ai-report map starts empty', () => {
        localStorage.setItem('ths-col-hidden', JSON.stringify(['x']))
        const s = fresh()
        s.init()
        expect(s.maps.ths).toEqual(['x'])
        expect(s.maps['ai-report']).toEqual([])
    })
})

describe('colVisibility media scope', () => {
    test('persists to media-col-hidden and renders the episode table rule', async () => {
        const s = fresh()
        s.init()
        expect(s.maps.media).toEqual([])

        s.toggle('notes', 'media')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('media-col-hidden'))).toEqual(['notes'])

        await flushRaf()
        expect(document.getElementById('dms-col-visibility-style').textContent)
            .toContain('.media-episode-table [data-col="notes"]{display:none!important}')
    })
})

describe('colVisibility launchpad scope', () => {
    test('restores, persists to launchpad-col-hidden, renders the table rule and resets', async () => {
        localStorage.setItem('launchpad-col-hidden', JSON.stringify(['owner']))
        const s = fresh()
        s.init()
        expect(s.maps.launchpad).toEqual(['owner'])

        s.toggle('audience', 'launchpad')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('launchpad-col-hidden'))).toEqual(['owner', 'audience'])

        await flushRaf()
        expect(document.getElementById('dms-col-visibility-style').textContent)
            .toContain('.launchpad-table [data-col="audience"]{display:none!important}')

        s.reset('launchpad')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('launchpad-col-hidden'))).toEqual([])
        expect(s.maps['ai-report']).toEqual([])
    })
})

describe('colVisibility.render — multi-domain rule building', () => {
    test('builds one display:none rule per hidden column across all three scoped tables, skipping empty domains', async () => {
        const s = fresh()
        s.maps = { dms: ['a'], ths: [], reservation: ['z'] }
        s.render()
        await flushRaf()

        const styleEl = document.getElementById('dms-col-visibility-style')
        expect(styleEl.textContent).toBe(
            '.dms-doc-table [data-col="a"]{display:none!important}.reservation-command-table [data-col="z"]{display:none!important}'
        )
    })

    test('a render already pending (same animation frame) is not scheduled twice', () => {
        const s = fresh()
        const rafSpy = vi.spyOn(global, 'requestAnimationFrame')
        s.render()
        s.render()
        expect(rafSpy).toHaveBeenCalledTimes(1)
        rafSpy.mockRestore()
    })
})

describe('colVisibility correspondence scope', () => {
    test('restores, persists and renders the correspondence table mask independently', async () => {
        localStorage.setItem('correspondence-col-hidden', JSON.stringify(['subject']))
        const s = fresh()
        s.init()
        expect(s.maps.correspondence).toEqual(['subject'])
        expect(s.isHidden('subject', 'correspondence')).toBe(true)

        s.toggle('number', 'correspondence')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('correspondence-col-hidden'))).toEqual(['subject', 'number'])

        await flushRaf()
        expect(document.getElementById('dms-col-visibility-style').textContent)
            .toContain('.correspondence-table [data-col="number"]{display:none!important}')
        expect(s.maps['ai-report']).toEqual([])

        s.reset('correspondence')
        await flushMicrotask()
        expect(JSON.parse(localStorage.getItem('correspondence-col-hidden'))).toEqual([])
    })
})
