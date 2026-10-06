import { describe, expect, test, vi, beforeEach } from 'vitest'
import energyChart from '../energyChart.js'

beforeEach(() => {
    document.body.innerHTML = ''
})

describe('energyChart.restoreMaximize', () => {
    test('a value matching an existing [data-widget] element re-applies maximize', () => {
        document.body.innerHTML = '<div data-widget="history"></div>'
        const c = energyChart()
        const spy = vi.spyOn(c, 'applyMaximize')
        c.restoreMaximize('history')
        expect(c.maximizedWidget).toBe('history')
        expect(spy).toHaveBeenCalledWith(true)
    })

    test('a stale value with no matching [data-widget] element cleans up and does nothing else', () => {
        document.body.innerHTML = '<div data-widget="history"></div>'
        const c = energyChart()
        const persistSpy = vi.spyOn(c, '_persistMaximize')
        const applySpy = vi.spyOn(c, 'applyMaximize')
        c.restoreMaximize('team')
        expect(persistSpy).toHaveBeenCalledWith(null)
        expect(applySpy).not.toHaveBeenCalled()
        expect(c.maximizedWidget).toBeNull()
    })

    test('a value that is no longer rendered at all (e.g. the team widget after losing manager view) cleans up', () => {
        document.body.innerHTML = ''
        const c = energyChart()
        const persistSpy = vi.spyOn(c, '_persistMaximize')
        c.restoreMaximize('team')
        expect(persistSpy).toHaveBeenCalledWith(null)
        expect(c.maximizedWidget).toBeNull()
    })
})
