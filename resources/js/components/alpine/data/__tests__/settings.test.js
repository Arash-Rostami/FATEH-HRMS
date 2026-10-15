import { describe, expect, test, vi, beforeEach } from 'vitest'
import settings from '../settings.js'

function make({ compact = false } = {}) {
    const s = settings()
    s.$store = {
        colVisibility: { maps: { dms: ['a', 'b'], ths: ['c'], reservation: [] }, reset: vi.fn() },
        density: { compact, toggle: vi.fn() }
    }
    return s
}

beforeEach(() => {
    localStorage.clear()
    document.documentElement.dataset.uid = '7'
    document.documentElement.classList.remove('declutter')
})

describe('settings table panel', () => {
    test('resetTableModule clears only that module keys and column mask', () => {
        localStorage.setItem('table-tools:dms', 'true')
        localStorage.setItem('max:dms:7', 'true')
        localStorage.setItem('table-tools:ths', 'true')
        localStorage.setItem('max:ths:7', 'true')
        const s = make()
        s.resetTableModule('dms')
        expect(localStorage.getItem('table-tools:dms')).toBeNull()
        expect(localStorage.getItem('max:dms:7')).toBeNull()
        expect(localStorage.getItem('table-tools:ths')).toBe('true')
        expect(localStorage.getItem('max:ths:7')).toBe('true')
        expect(s.$store.colVisibility.reset).toHaveBeenCalledTimes(1)
        expect(s.$store.colVisibility.reset).toHaveBeenCalledWith('dms')
    })

    test('taskboard module reset clears its card-field mask', () => {
        const s = make()
        s.resetTableModule('taskboard')
        expect(s.$store.colVisibility.reset).toHaveBeenCalledWith('taskboard')
    })

    test('taskboard counts hidden card fields and labels them as card sections', () => {
        const s = make()
        s.$store.colVisibility.maps.taskboard = ['labels', 'people', 'deadline']
        s.refreshTableState()
        expect(s.tableState.modules.taskboard.hiddenCols).toBe(3)
        expect(s.tableModules.find(m => m.id === 'taskboard').colsLabel).toBe('بخش کارت')
    })

    test('refreshTableState reports hidden columns, collapsed toolbar and maximized flags', () => {
        localStorage.setItem('table-tools:ths', 'true')
        localStorage.setItem('max:taskboard:7', 'true')
        localStorage.setItem('menu_declutter', 'true')
        const s = make({ compact: true })
        s.refreshTableState()
        expect(s.tableState.modules.dms.hiddenCols).toBe(2)
        expect(s.tableState.modules.ths.toolbarCollapsed).toBe(true)
        expect(s.tableState.modules.dms.toolbarCollapsed).toBe(false)
        expect(s.tableState.modules.taskboard.maximized).toBe(true)
        expect(s.tableState.compact).toBe(true)
        expect(s.tableState.menuDeclutter).toBe(true)
    })

    test('resetDeclutter writes false, removes html class and dispatches the event', () => {
        localStorage.setItem('menu_declutter', 'true')
        document.documentElement.classList.add('declutter')
        const listener = vi.fn()
        window.addEventListener('declutter', listener)
        const s = make()
        s.resetDeclutter()
        window.removeEventListener('declutter', listener)
        expect(localStorage.getItem('menu_declutter')).toBe('false')
        expect(document.documentElement.classList.contains('declutter')).toBe(false)
        expect(listener.mock.calls[0][0].detail.on).toBe(false)
        expect(s.tableState.menuDeclutter).toBe(false)
    })

    test('resetDensity toggles only when compact', () => {
        const off = make()
        off.resetDensity()
        expect(off.$store.density.toggle).not.toHaveBeenCalled()
        const on = make({ compact: true })
        on.resetDensity()
        expect(on.$store.density.toggle).toHaveBeenCalledTimes(1)
    })

    test('resetTables resets every module, density and declutter', () => {
        ;['dms', 'ths', 'reservation', 'taskboard'].forEach(id => {
            localStorage.setItem(`table-tools:${id}`, 'true')
            localStorage.setItem(`max:${id}:7`, 'true')
        })
        localStorage.setItem('menu_declutter', 'true')
        const s = make({ compact: true })
        s.resetTables()
        expect(localStorage.length).toBe(1)
        expect(localStorage.getItem('menu_declutter')).toBe('false')
        expect(s.$store.colVisibility.reset.mock.calls.map(c => c[0])).toEqual(['dms', 'ths', 'reservation', 'taskboard'])
        expect(s.$store.density.toggle).toHaveBeenCalledTimes(1)
    })
})
