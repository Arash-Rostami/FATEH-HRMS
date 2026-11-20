import { describe, expect, test, vi } from 'vitest'
import memberPicker, { normalize } from '../memberPicker.js'

const rows = [
    { id: 1, name: 'علی رضایی', dept: 'IT' },
    { id: 2, name: 'مریم کریمی', dept: 'HR' },
    { id: 3, name: 'عليرضا نوری', dept: 'IT' },
    { id: 4, name: 'سارا', dept: '' },
]

const mount = (options = {}, selected = []) => {
    const state = { value: selected, host: {} }
    const picker = memberPicker({ model: 'members', rows, ...options })
    const bulk = vi.fn()
    const watchers = []
    picker.$wire = { get: (key) => (key === 'members' ? state.value : state.host[key]), bulkSelectPeople: bulk }
    picker.$watch = (source, callback) => {
        const read = () => (typeof source === 'function' ? source() : picker[source])
        watchers.push({ read, callback, last: read() })
    }
    picker.init()
    const flush = () => {
        for (const watcher of watchers) {
            const next = watcher.read()
            if (next === watcher.last) continue
            watcher.last = next
            watcher.callback(next)
        }
    }
    return { picker, bulk, state, flush }
}

describe('normalize', () => {
    test('unifies Arabic yeh/kaf, folds case and turns ZWNJ into a space', () => {
        expect(normalize('عليرضا')).toBe('علیرضا')
        expect(normalize('كريم')).toBe('کریم')
        expect(normalize(' می‌خواهم ')).toBe('می خواهم')
        expect(normalize(null)).toBe('')
    })
})

describe('memberPicker filtering', () => {
    test('shows every row initially and counts them', () => {
        const { picker } = mount()
        expect(picker.matched).toBe(4)
        expect([1, 2, 3, 4].every((id) => picker.isVisible(id))).toBe(true)
    })

    test('visibility is keyed by candidate id, not by position', () => {
        const { picker } = mount()
        picker.dept = 'HR'
        picker.refresh()
        expect(picker.isVisible(2)).toBe(true)
        expect(picker.isVisible('2')).toBe(true)
        expect(picker.isVisible(1)).toBe(false)
    })

    test('query matches across Arabic and Persian letter forms', () => {
        const { picker } = mount()
        picker.memberQuery = 'علیرضا'
        picker.refresh()
        expect(picker.matched).toBe(1)
        expect(picker.isVisible(3)).toBe(true)
        expect(picker.isVisible(1)).toBe(false)
    })

    test('department filter keeps only that department', () => {
        const { picker } = mount()
        picker.dept = 'IT'
        picker.refresh()
        expect(picker.matched).toBe(2)
        expect(picker.isVisible(2)).toBe(false)
    })

    test('only-selected keeps the picked rows', () => {
        const { picker } = mount({}, [2, 4])
        picker.onlySelected = true
        picker.refresh()
        expect(picker.matched).toBe(2)
        expect(picker.isVisible(2)).toBe(true)
        expect(picker.isVisible(4)).toBe(true)
        expect(picker.isVisible(1)).toBe(false)
    })

    test('shown caps the visible set but not the match count', () => {
        const { picker } = mount({ page: 2 })
        expect(picker.matched).toBe(4)
        expect(picker.isVisible(2)).toBe(true)
        expect(picker.isVisible(3)).toBe(false)
        picker.showMore()
        expect(picker.isVisible(3)).toBe(true)
    })

    test('the first matching row carries no top border unless a none row leads the list', () => {
        const { picker } = mount()
        expect(picker.isFirst(1)).toBe(true)
        expect(picker.isFirst(2)).toBe(false)
        picker.dept = 'HR'
        picker.refresh()
        expect(picker.isFirst(2)).toBe(true)
        expect(mount({ hasNone: true }).picker.isFirst(1)).toBe(false)
    })

    test('server mode never filters locally', () => {
        const { picker } = mount({ server: true })
        picker.memberQuery = 'zzz'
        picker.refresh()
        expect(picker.isVisible(1)).toBe(true)
        expect(picker.matched).toBe(0)
    })

    test('typing a query, picking a department or toggling only-selected resets paging and refreshes the list through the watchers', () => {
        const { picker, flush } = mount({ page: 2 })
        picker.showMore()
        expect(picker.shown).toBe(4)

        picker.memberQuery = 'مریم'
        expect(picker.matched).toBe(4)
        flush()
        expect(picker.shown).toBe(2)
        expect(picker.matched).toBe(1)
        expect(picker.isVisible(2)).toBe(true)

        picker.memberQuery = ''
        picker.dept = 'IT'
        flush()
        expect(picker.matched).toBe(2)
        expect(picker.isVisible(2)).toBe(false)

        picker.dept = ''
        picker.onlySelected = true
        flush()
        expect(picker.matched).toBe(0)
    })

    test('a selection change on the wire syncs the selected map and, in the only-selected view, refreshes the rows', () => {
        const { picker, state, flush } = mount({}, [2])
        picker.onlySelected = true
        flush()
        expect(picker.matched).toBe(1)
        expect(picker.isVisible(4)).toBe(false)

        state.value = [2, 4]
        expect(picker.isSelected('4')).toBe(false)
        flush()
        expect(picker.isSelected('4')).toBe(true)
        expect(picker.matched).toBe(2)
        expect(picker.isVisible(4)).toBe(true)
    })

    test('a selection change outside the only-selected view updates the toggle state without touching the visible rows', () => {
        const { picker, state, flush } = mount({}, [1, 2, 3])
        expect(picker.allSelected).toBe(false)

        state.value = [1, 2, 3, 4]
        flush()
        expect(picker.allSelected).toBe(true)
        expect(picker.matched).toBe(4)
        expect([1, 2, 3, 4].every((id) => picker.isVisible(id))).toBe(true)
    })
})

describe('memberPicker selection', () => {
    test('single mode reads one value and ignores empties', () => {
        expect(mount({ mode: 'single' }, 3).picker.selectedIds).toEqual(['3'])
        expect(mount({ mode: 'single' }, '').picker.selectedIds).toEqual([])
        expect(mount({ mode: 'single' }, null).picker.selectedIds).toEqual([])
    })

    test('multi mode stringifies ids and tolerates a non-array', () => {
        expect(mount({}, [1, 2]).picker.selectedIds).toEqual(['1', '2'])
        expect(mount({}, 'x').picker.selectedIds).toEqual([])
    })

    test('isSelected compares as strings', () => {
        const { picker } = mount({}, [1])
        expect(picker.isSelected('1')).toBe(true)
        expect(picker.isSelected(2)).toBe(false)
    })

    test('the selected count only counts people this picker lists', () => {
        expect(mount({}, [1, 2, 99]).picker.selectedCount).toBe(2)
        expect(mount({ server: true }, [1, 2, 99]).picker.selectedCount).toBe(3)
    })
})

describe('memberPicker bulk toggle', () => {
    test('allSelected is true only when every row is picked', () => {
        expect(mount({}, [1, 2, 3, 4]).picker.allSelected).toBe(true)
        expect(mount({}, [1, 2, 3]).picker.allSelected).toBe(false)
        expect(mount({ rows: [] }, []).picker.allSelected).toBe(false)
    })

    test('allSelected is scoped to the chosen department in client mode', () => {
        const { picker } = mount({}, [1, 3])
        picker.dept = 'IT'
        expect(picker.allSelected).toBe(true)
    })

    test('with nothing picked it asks the server for all', () => {
        const { picker, bulk } = mount()
        picker.toggleBulk()
        expect(bulk).toHaveBeenCalledWith('members', 'all')
    })

    test('with a department chosen it asks for that department only', () => {
        const { picker, bulk } = mount()
        picker.dept = 'HR'
        picker.toggleBulk()
        expect(bulk).toHaveBeenCalledWith('members', 'department', 'HR')
    })

    test('when everything is picked it clears and drops the only-selected view', () => {
        const { picker, bulk } = mount({}, [1, 2, 3, 4])
        picker.onlySelected = true
        picker.toggleBulk()
        expect(bulk).toHaveBeenCalledWith('members', 'none')
        expect(picker.onlySelected).toBe(false)
    })

    test('an active search text blocks the bulk toggle so the whole organisation is never picked by accident', () => {
        const { picker, bulk } = mount()
        picker.memberQuery = 'علی'
        expect(picker.bulkBlocked).toBe(true)
        picker.toggleBulk()
        expect(bulk).not.toHaveBeenCalled()
    })

    test('clearing while a department is chosen asks only for that department', () => {
        const { picker, bulk } = mount({}, [1, 3])
        picker.dept = 'IT'
        picker.toggleBulk()
        expect(bulk).toHaveBeenCalledWith('members', 'none', 'IT')
    })

    test('a disabled picker never calls the server', () => {
        const { picker, bulk } = mount({ disabled: true })
        picker.toggleBulk()
        expect(bulk).not.toHaveBeenCalled()
    })

    test('a second click is ignored while the first call is still running', () => {
        const { picker, bulk } = mount()
        bulk.mockReturnValue(new Promise(() => {}))
        picker.toggleBulk()
        picker.toggleBulk()
        expect(bulk).toHaveBeenCalledTimes(1)
        expect(picker.busy).toBe(true)
    })

    test('server mode reads the department and search from the host and never trusts a partial page', () => {
        const { picker, bulk, state, flush } = mount({ server: true, deptModel: 'userDept', searchModel: 'userSearch' }, [1, 2])
        state.host = { userDept: 'HR', userSearch: '' }
        picker.sync([1, 2], false)
        expect(picker.allSelected).toBe(true)
        picker.sync([1, 2], true)
        expect(picker.allSelected).toBe(false)
        state.value = [1, 2, 3]
        flush()
        expect(picker.allSelected).toBe(true)
        state.value = [1, 2]
        flush()
        expect(picker.allSelected).toBe(false)
        picker.toggleBulk()
        expect(bulk).toHaveBeenCalledWith('members', 'department', 'HR')
        state.host.userSearch = 'x'
        expect(picker.bulkBlocked).toBe(true)
    })

    test('server mode re-syncs the selected map after the wire value changes, so a stale tick never keeps the label on «select all»', () => {
        const { picker, state, flush } = mount({ server: true }, [1, 2])
        picker.sync([1, 2], false)
        expect(picker.allSelected).toBe(true)

        state.value = [1]
        expect(picker.allSelected).toBe(true)
        flush()
        expect(picker.allSelected).toBe(false)

        state.value = [1, 2]
        flush()
        expect(picker.allSelected).toBe(true)
    })
})
