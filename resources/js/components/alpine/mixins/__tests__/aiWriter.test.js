import { describe, expect, test, vi, beforeEach } from 'vitest'
import aiWriterMixin from '../aiWriter.js'

const BLANK = '«____»'

function make(result = { source: 'ai', question: null, blanks: 0, left: 5 }, ticket = {}) {
    const m = aiWriterMixin()
    m.$wire = {
        ticket: { subject: 'old subject', description: 'old description', ...ticket },
        suggestTicketText: vi.fn(() => Promise.resolve(result)),
    }
    m.$watch = vi.fn()
    m.$nextTick = (cb) => cb()
    m.$refs = { aiAccept: { focus: vi.fn() } }
    return m
}

function watchers(m) {
    return Object.fromEntries(m.$watch.mock.calls)
}

function field(value, host = {}, id = '') {
    const wrap = document.createElement('div')
    Object.assign(wrap.dataset, { aiBlank: BLANK, ...host })
    const el = document.createElement('textarea')
    el.id = id
    el.value = value
    wrap.append(el)
    document.body.append(wrap)
    el.setSelectionRange(0, 0)
    return el
}

describe('aiWriterMixin.aiRun', () => {
    test('a suggestion flags the state, keeps the question and blanks, and snapshots the original text', async () => {
        const m = make({ source: 'ai', question: 'why urgent?', blanks: 2, left: 4 })

        await m.aiRun()

        expect(m.aiSuggested).toBe(true)
        expect(m.aiQuestion).toBe('why urgent?')
        expect(m.aiBlanks).toBe(2)
        expect(m.aiOriginal).toEqual({ subject: 'old subject', description: 'old description' })
        expect(m.aiLoading).toBe(false)
        expect(m.$refs.aiAccept.focus).toHaveBeenCalled()
    })

    test('a skeleton fallback counts as a suggestion too', async () => {
        const m = make({ source: 'skeleton', question: null, blanks: 3, left: 4 })

        await m.aiRun()

        expect(m.aiSuggested).toBe(true)
        expect(m.aiBlanks).toBe(3)
    })

    test('source none leaves every piece of state untouched', async () => {
        const m = make({ source: 'none', question: null, blanks: 0, left: 0 })

        await m.aiRun()

        expect(m.aiSuggested).toBe(false)
        expect(m.aiOriginal).toBeNull()
        expect(m.aiQuestion).toBeNull()
        expect(m.aiBlanks).toBe(0)
        expect(m.aiLoading).toBe(false)
    })

    test('a request that resolves with nothing (validation failure) leaves state untouched', async () => {
        const m = make()
        m.$wire.suggestTicketText = vi.fn(() => Promise.resolve(undefined))

        await m.aiRun()

        expect(m.aiLoading).toBe(false)
        expect(m.aiSuggested).toBe(false)
        expect(m.aiOriginal).toBeNull()
    })

    test('a response that arrives after the classification changed is dropped', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        m.$wire.suggestTicketText = vi.fn(() => {
            m.$wire.ticket.requestArea = 'other'
            m.$wire.ticket.subject = 'server subject'
            m.$wire.ticket.description = 'server description'
            return Promise.resolve({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        })

        await m.aiRun()

        expect(m.aiSuggested).toBe(false)
        expect(m.aiApplied).toBeNull()
        expect(m.aiQuestion).toBeNull()
        expect(m.aiLoading).toBe(false)
        expect(m.$wire.ticket.subject).toBe('old subject')
        expect(m.$wire.ticket.description).toBe('old description')
    })

    test('an empty request area blocks the run without calling the server', async () => {
        const m = make(undefined, { requestArea: '' })

        await m.aiRun()

        expect(m.aiBlocked()).toBe(true)
        expect(m.$wire.suggestTicketText).not.toHaveBeenCalled()
    })

    test('a rejected request is swallowed, resets the loading flag and leaves state and fields untouched', async () => {
        const m = make()
        m.$wire.suggestTicketText = vi.fn(() => Promise.reject(new Error('boom')))

        await expect(m.aiRun()).resolves.toBeUndefined()

        expect(m.$wire.ticket.subject).toBe('old subject')
        expect(m.$wire.ticket.description).toBe('old description')
        expect(m.aiLoading).toBe(false)
        expect(m.aiSuggested).toBe(false)
        expect(m.aiOriginal).toBeNull()
    })

    test('focus returns to the description when nothing holds it after a run that changed nothing', async () => {
        document.body.innerHTML = ''
        const description = field('abc', {}, 'ticket.description')
        description.focus()
        const m = make({ source: 'none', question: null, blanks: 0, left: 0 })
        m.$wire.suggestTicketText = vi.fn(() => {
            description.blur()
            return Promise.resolve({ source: 'none', question: null, blanks: 0, left: 0 })
        })

        await m.aiRun()

        expect(document.activeElement).toBe(description)
    })

    test('focus is not pulled in when nothing held it before the run', async () => {
        document.body.innerHTML = ''
        field('abc', {}, 'ticket.description')
        document.activeElement?.blur()
        const m = make({ source: 'none', question: null, blanks: 0, left: 0 })
        const focus = vi.spyOn(m, 'aiFocus')

        await m.aiRun()

        expect(focus).not.toHaveBeenCalled()
    })

    test('focus is left alone when something already holds it', async () => {
        document.body.innerHTML = ''
        const description = field('abc', {}, 'ticket.description')
        const other = document.createElement('button')
        document.body.append(other)
        other.focus()
        const m = make({ source: 'none', question: null, blanks: 0, left: 0 })

        await m.aiRun()

        expect(document.activeElement).toBe(other)
        expect(document.activeElement).not.toBe(description)
    })

    test('a blocked run on an empty area toasts the need-area text, but not while loading', async () => {
        const m = make(undefined, { requestArea: '' })
        m.$dispatch = vi.fn()

        await m.aiRun('pick an area')
        expect(m.$dispatch).toHaveBeenCalledWith('toast', { message: 'pick an area', type: 'warning' })

        const busy = make()
        busy.$dispatch = vi.fn()
        busy.aiLoading = true
        await busy.aiRun('pick an area')
        expect(busy.$dispatch).not.toHaveBeenCalled()
    })

    test('a stale response while a suggestion is pending restores the true original, not the intermediate suggestion', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        await m.aiRun()
        m.$wire.ticket.subject = 'first suggestion'
        m.$wire.ticket.description = 'first suggestion body'
        m.$wire.suggestTicketText = vi.fn(() => {
            m.$wire.ticket.requestArea = 'other'
            m.$wire.ticket.subject = 'second suggestion'
            m.$wire.ticket.description = 'second suggestion body'
            return Promise.resolve({ source: 'ai', question: null, blanks: 0, left: 1 })
        })

        await m.aiRun()

        expect(m.$wire.ticket.subject).toBe('old subject')
        expect(m.$wire.ticket.description).toBe('old description')
    })

    test('a second run while loading is ignored', async () => {
        const m = make()

        const first = m.aiRun()
        await m.aiRun()
        await first

        expect(m.$wire.suggestTicketText).toHaveBeenCalledTimes(1)
    })

    test('re-running an unaccepted suggestion keeps the true original for undo', async () => {
        const m = make()
        await m.aiRun()
        m.$wire.ticket.subject = 'suggested subject'
        m.$wire.ticket.description = 'suggested description'

        await m.aiRun()

        expect(m.aiOriginal).toEqual({ subject: 'old subject', description: 'old description' })
    })
})

describe('aiWriterMixin.aiUndo / aiClear / aiReset', () => {
    test('undo restores the snapshot and clears every flag', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        await m.aiRun()
        m.$wire.ticket.subject = 'suggested subject'
        m.$wire.ticket.description = 'suggested description'

        m.aiUndo()

        expect(m.$wire.ticket.subject).toBe('old subject')
        expect(m.$wire.ticket.description).toBe('old description')
        expect(m.aiSuggested).toBe(false)
        expect(m.aiOriginal).toBeNull()
        expect(m.aiQuestion).toBeNull()
        expect(m.aiBlanks).toBe(0)
    })

    test('undo and clear are ignored while a request is in flight', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        await m.aiRun()
        m.$wire.ticket.subject = 'suggested subject'
        m.aiLoading = true

        m.aiUndo()
        m.aiClear()

        expect(m.$wire.ticket.subject).toBe('suggested subject')
        expect(m.aiSuggested).toBe(true)
        expect(m.aiOriginal).not.toBeNull()
    })

    test('explicit accept ends the suggestion, drops the blanks note and refocuses the description', async () => {
        document.body.innerHTML = ''
        const description = field('abc', {}, 'ticket.description')
        const m = make({ source: 'ai', question: 'q', blanks: 2, left: 1 })
        await m.aiRun()

        m.aiAccept()

        expect(m.aiSuggested).toBe(false)
        expect(m.aiBlanks).toBe(0)
        expect(m.aiQuestion).toBeNull()
        expect(document.activeElement).toBe(description)
    })

    test('explicit accept is ignored while loading', async () => {
        const m = make({ source: 'ai', question: null, blanks: 2, left: 1 })
        await m.aiRun()
        m.aiLoading = true

        m.aiAccept()

        expect(m.aiSuggested).toBe(true)
        expect(m.aiBlanks).toBe(2)
    })

    test('undo without a snapshot leaves the typed text alone', () => {
        const m = make()

        m.aiUndo()

        expect(m.$wire.ticket.subject).toBe('old subject')
    })

    test('clear (implicit accept) drops the suggested flag and the question but keeps the snapshot', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        await m.aiRun()

        m.aiClear()

        expect(m.aiSuggested).toBe(false)
        expect(m.aiQuestion).toBeNull()
        expect(m.aiOriginal).not.toBeNull()
    })

    test('the expected fields are watched', () => {
        const m = make()
        m.initAiWriter()

        expect(Object.keys(watchers(m)).sort()).toEqual([
            '$wire.ticket.description',
            '$wire.ticket.priority',
            '$wire.ticket.requestArea',
            '$wire.ticket.requestType',
            '$wire.ticket.subject',
            '$wire.ticket.targetDepartment',
        ])
    })

    test.each(['targetDepartment', 'requestType', 'requestArea', 'priority'])('a %s change while suggested restores the original text and clears the state', async (key) => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        m.initAiWriter()
        m.$wire.suggestTicketText = vi.fn(() => {
            m.$wire.ticket.subject = 'suggested subject'
            m.$wire.ticket.description = 'suggested «____»'
            return Promise.resolve({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        })
        await m.aiRun()

        watchers(m)[`$wire.ticket.${key}`]()

        expect(m.$wire.ticket.subject).toBe('old subject')
        expect(m.$wire.ticket.description).toBe('old description')
        expect(m.aiSuggested).toBe(false)
        expect(m.aiOriginal).toBeNull()
        expect(m.aiQuestion).toBeNull()
        expect(m.aiBlanks).toBe(0)
    })

    test.each([['classification first'], ['text first']])('a submit reset while a suggestion is pending never refills the form (%s)', async (order) => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        m.initAiWriter()
        m.$wire.suggestTicketText = vi.fn(() => {
            m.$wire.ticket.subject = 'suggested subject'
            m.$wire.ticket.description = 'suggested «____»'
            return Promise.resolve({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        })
        await m.aiRun()
        const watched = watchers(m)

        m.$wire.ticket.subject = ''
        m.$wire.ticket.description = ''
        m.$wire.ticket.requestArea = ''

        const classification = () => watched['$wire.ticket.requestArea']('')
        const text = () => {
            watched['$wire.ticket.subject']('')
            watched['$wire.ticket.description']('')
        }
        if (order === 'classification first') {
            classification()
            text()
        } else {
            text()
            classification()
        }

        expect(m.$wire.ticket.subject).toBe('')
        expect(m.$wire.ticket.description).toBe('')
        expect(m.aiSuggested).toBe(false)
        expect(m.aiOriginal).toBeNull()
        expect(m.aiApplied).toBeNull()
        expect(m.aiQuestion).toBeNull()
    })

    test('a classification change after an implicit accept keeps the text and clears the state', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        m.initAiWriter()
        await m.aiRun()
        m.$wire.ticket.subject = 'my edited subject'
        m.aiClear()

        watchers(m)['$wire.ticket.priority']()

        expect(m.$wire.ticket.subject).toBe('my edited subject')
        expect(m.aiOriginal).toBeNull()
        expect(m.aiQuestion).toBeNull()
    })

    test('a response that arrives after the priority changed is dropped', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        m.$wire.suggestTicketText = vi.fn(() => {
            m.$wire.ticket.priority = 'high'
            return Promise.resolve({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        })

        await m.aiRun()

        expect(m.aiSuggested).toBe(false)
        expect(m.aiQuestion).toBeNull()
    })

    test('an edit that differs from the applied text is an implicit accept, whatever input produced it', async () => {
        const m = make({ source: 'ai', question: 'q', blanks: 1, left: 1 })
        m.initAiWriter()
        const watched = watchers(m)
        await m.aiRun()

        watched['$wire.ticket.description']('edited in the overlay')

        expect(m.aiSuggested).toBe(false)
        expect(m.aiQuestion).toBeNull()
    })

    test('a change equal to the applied text is ignored', async () => {
        const m = make({ source: 'ai', question: null, blanks: 0, left: 1 })
        m.initAiWriter()
        await m.aiRun()

        const watched = watchers(m)
        watched['$wire.ticket.subject'](m.aiApplied.subject)
        watched['$wire.ticket.description'](m.aiApplied.description)

        expect(m.aiSuggested).toBe(true)
    })

    test('the watchers stay quiet after undo or reset', async () => {
        const m = make({ source: 'ai', question: null, blanks: 0, left: 1 })
        m.initAiWriter()
        await m.aiRun()
        m.aiUndo()
        m.aiSuggested = true

        watchers(m)['$wire.ticket.subject']('old subject')

        expect(m.aiSuggested).toBe(true)
        expect(m.aiApplied).toBeNull()
    })
})

describe('aiWriterMixin.aiNextBlank', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    test('selects successive blanks inside one field', () => {
        const m = make()
        const el = field(`a ${BLANK} b ${BLANK} c`)

        expect(m.aiNextBlank(el)).toBe(true)
        expect([el.selectionStart, el.selectionEnd]).toEqual([2, 2 + BLANK.length])

        expect(m.aiNextBlank(el)).toBe(true)
        expect(el.selectionStart).toBe(el.value.lastIndexOf(BLANK))

        expect(m.aiNextBlank(el)).toBe(false)
    })

    test('moves from the subject to the description when the subject has no blank left', () => {
        const m = make()
        const description = field(`x ${BLANK}`, {}, 'ticket.description')
        const subject = field('plain subject', { aiNext: 'ticket.description' })

        expect(m.aiNextBlank(subject)).toBe(true)
        expect(description.selectionStart).toBe(2)
    })

    test('returns false when no blank is left anywhere so Tab proceeds normally', () => {
        const m = make()
        const description = field('done', {}, 'ticket.description')
        const subject = field('plain subject', { aiNext: 'ticket.description' })

        expect(m.aiNextBlank(subject)).toBe(false)
        expect(m.aiNextBlank(description)).toBe(false)
    })

    test('ignores elements that are not text fields or sit outside an AI host', () => {
        const m = make()
        const button = document.createElement('button')
        const wrap = document.createElement('div')
        wrap.dataset.aiBlank = BLANK
        wrap.append(button)

        expect(m.aiNextBlank(button)).toBe(false)
        expect(m.aiNextBlank(document.createElement('textarea'))).toBe(false)
    })
})

describe('aiWriterMixin.aiOverLimit', () => {
    function trigger() {
        const el = document.createElement('button')
        Object.assign(el.dataset, { aiLimitSubject: '5', aiLimitDescription: '8', aiLong: 'too long', aiNeed: 'pick an area' })
        return el
    }

    test('is false at the boundary and true one character past it, per field', () => {
        const m = make(undefined, { subject: 'a'.repeat(5), description: 'b'.repeat(8) })
        expect(m.aiOverLimit(trigger())).toBe(false)

        m.$wire.ticket.subject = 'a'.repeat(6)
        expect(m.aiOverLimit(trigger())).toBe(true)

        m.$wire.ticket.subject = 'a'
        m.$wire.ticket.description = 'b'.repeat(9)
        expect(m.aiOverLimit(trigger())).toBe(true)
    })

    test('counts code points, not UTF-16 units', () => {
        const m = make(undefined, { subject: '😀'.repeat(5), description: '' })

        expect(m.aiOverLimit(trigger())).toBe(false)
    })

    test('an over-limit run toasts the long text and never calls the server', async () => {
        const m = make(undefined, { subject: 'a'.repeat(6) })
        m.$dispatch = vi.fn()

        await m.aiRun('pick an area', trigger())

        expect(m.$dispatch).toHaveBeenCalledWith('toast', { message: 'too long', type: 'warning' })
        expect(m.$wire.suggestTicketText).not.toHaveBeenCalled()
        expect(m.aiLoading).toBe(false)
    })

    test('a run within the limits is unaffected', async () => {
        const m = make({ source: 'ai', question: null, blanks: 0, left: 1 }, { subject: 'abc', description: 'def' })
        m.$dispatch = vi.fn()

        await m.aiRun('pick an area', trigger())

        expect(m.$wire.suggestTicketText).toHaveBeenCalledTimes(1)
        expect(m.$dispatch).not.toHaveBeenCalled()
        expect(m.aiSuggested).toBe(true)
    })
})

describe('aiWriterMixin source and remaining uses', () => {
    test('source and remaining come from the result; reset clears the source only', async () => {
        const m = make({ source: 'skeleton', question: null, blanks: 1, left: 7 })

        await m.aiRun()

        expect(m.aiSource).toBe('skeleton')
        expect(m.aiRemaining).toBe(7)

        m.aiReset()

        expect(m.aiSource).toBeNull()
        expect(m.aiRemaining).toBe(7)
    })

    test('a run that changes nothing leaves both untouched', async () => {
        const m = make({ source: 'none', question: null, blanks: 0, left: 0 })

        await m.aiRun()

        expect(m.aiSource).toBeNull()
        expect(m.aiRemaining).toBeNull()
    })
})

describe('aiWriterMixin template focus, ready state and another version', () => {
    function templateForm() {
        document.body.innerHTML = ''
        const description = field(`x ${BLANK}`, {}, 'ticket.description')
        const subject = field(`a ${BLANK}`, { aiNext: 'ticket.description' }, 'ticket.subject')
        document.activeElement?.blur()
        return { subject, description }
    }

    function writes(m, source) {
        m.$wire.suggestTicketText = vi.fn(() => {
            m.$wire.ticket.subject = `a ${BLANK}`
            m.$wire.ticket.description = `x ${BLANK}`
            return Promise.resolve({ source, question: null, blanks: 2, left: 3 })
        })
    }

    test('a skeleton run opens on the first blank instead of focusing the accept chip', async () => {
        const { subject } = templateForm()
        const m = make()
        writes(m, 'skeleton')

        await m.aiRun()

        expect(document.activeElement).toBe(subject)
        expect(subject.selectionStart).toBe(2)
        expect(m.$refs.aiAccept.focus).not.toHaveBeenCalled()
    })

    test('an ai run keeps focusing the accept chip', async () => {
        const { subject } = templateForm()
        const m = make()
        writes(m, 'ai')

        await m.aiRun()

        expect(m.$refs.aiAccept.focus).toHaveBeenCalled()
        expect(document.activeElement).not.toBe(subject)
    })

    test('ready follows the live count: hidden at 2 and 1 blanks, shown at 0, hidden after accept or reset', async () => {
        templateForm()
        const m = make()
        writes(m, 'ai')
        await m.aiRun()
        expect(m.aiReady()).toBe(false)

        m.$wire.ticket.subject = 'a filled'
        expect(m.aiReady()).toBe(false)

        m.$wire.ticket.description = 'x filled'
        expect(m.aiReady()).toBe(true)

        m.aiAccept()
        expect(m.aiReady()).toBe(false)

        m.aiBlanks = 2
        m.aiReset()
        expect(m.aiReady()).toBe(false)
    })

    test('another version re-runs through the trigger dataset, and is blocked while loading', async () => {
        templateForm()
        const trigger = document.createElement('button')
        Object.assign(trigger.dataset, { aiNeed: 'pick an area', aiLimitSubject: '255', aiLimitDescription: '1500' })
        document.body.append(trigger)
        const m = make({ source: 'ai', question: null, blanks: 0, left: 1 })

        await m.aiAgain()
        expect(m.$wire.suggestTicketText).toHaveBeenCalledTimes(1)

        m.aiLoading = true
        await m.aiAgain()
        expect(m.$wire.suggestTicketText).toHaveBeenCalledTimes(1)
    })
})

describe('aiWriterMixin.aiGuardSubmit', () => {
    function submit() {
        return { preventDefault: vi.fn(), stopImmediatePropagation: vi.fn() }
    }

    function guarded(ticket) {
        document.body.innerHTML = ''
        field('x', {}, 'ticket.subject')
        const m = make(undefined, ticket)
        m.$dispatch = vi.fn()
        return m
    }

    test('blocks and toasts while a blank remains, even after explicit accept', () => {
        const m = guarded({ subject: `a ${BLANK}`, description: 'b' })
        m.aiBlanks = 0
        const e = submit()

        m.aiGuardSubmit(e, 'fill the blanks')

        expect(e.preventDefault).toHaveBeenCalled()
        expect(e.stopImmediatePropagation).toHaveBeenCalled()
        expect(m.$dispatch).toHaveBeenCalledWith('toast', { message: 'fill the blanks', type: 'warning' })
    })

    test('allows the submit once the blanks are filled or removed', () => {
        const m = guarded({ subject: 'a', description: `b ${BLANK}` })
        m.$wire.ticket.description = 'b filled'
        const e = submit()

        m.aiGuardSubmit(e, 'fill the blanks')

        expect(e.preventDefault).not.toHaveBeenCalled()
        expect(m.$dispatch).not.toHaveBeenCalled()
    })

    test('blocks silently while loading', () => {
        const m = guarded({ subject: 'a', description: 'b' })
        m.aiLoading = true
        const e = submit()

        m.aiGuardSubmit(e, 'fill the blanks')

        expect(e.preventDefault).toHaveBeenCalled()
        expect(e.stopImmediatePropagation).toHaveBeenCalled()
        expect(m.$dispatch).not.toHaveBeenCalled()
    })
})

describe('aiWriterMixin.aiJumpBlank', () => {
    function form() {
        document.body.innerHTML = ''
        const description = field(`x ${BLANK}`, {}, 'ticket.description')
        const subject = field(`a ${BLANK} b`, { aiNext: 'ticket.description' }, 'ticket.subject')
        document.activeElement?.blur()
        return { subject, description }
    }

    test('jumps from outside any field to the first blank, then onward subject to description', () => {
        const { subject, description } = form()
        const m = make()

        expect(m.aiJumpBlank()).toBe(true)
        expect(document.activeElement).toBe(subject)
        expect(subject.selectionStart).toBe(2)

        subject.blur()
        expect(m.aiJumpBlank()).toBe(true)
        expect(document.activeElement).toBe(description)
        expect(description.selectionStart).toBe(2)
    })

    test('wraps to the first blank after the last one and is a no-op without blanks', () => {
        const { subject, description } = form()
        const m = make()
        m.aiJumpBlank()
        subject.blur()
        m.aiJumpBlank()
        description.blur()

        expect(m.aiJumpBlank()).toBe(true)
        expect(document.activeElement).toBe(subject)

        subject.value = 'clean'
        description.value = 'clean'
        subject.blur()
        expect(m.aiJumpBlank()).toBe(false)
    })
})

describe('aiWriterMixin.aiBlankLeft — live blank count', () => {
    function withBlanks() {
        document.body.innerHTML = ''
        field('x', {}, 'ticket.subject')
        const m = make({ source: 'ai', question: null, blanks: 2, left: 1 })
        m.$wire.suggestTicketText = vi.fn(() => {
            m.$wire.ticket.subject = `a ${BLANK}`
            m.$wire.ticket.description = `b ${BLANK}`
            return Promise.resolve({ source: 'ai', question: null, blanks: 2, left: 1 })
        })
        return m
    }

    test('counts the marker across both fields and follows typing', async () => {
        const m = withBlanks()
        await m.aiRun()
        expect(m.aiBlankLeft()).toBe(2)

        m.$wire.ticket.subject = 'a filled'
        expect(m.aiBlankLeft()).toBe(1)

        m.$wire.ticket.description = 'b filled'
        expect(m.aiBlankLeft()).toBe(0)
    })

    test('survives an implicit accept', async () => {
        const m = withBlanks()
        await m.aiRun()

        m.aiClear()

        expect(m.aiBlanks).toBe(2)
        expect(m.aiBlankLeft()).toBe(2)
    })

    test('is gated off by an explicit accept and by a classification reset', async () => {
        const m = withBlanks()
        await m.aiRun()
        m.aiAccept()
        expect(m.aiBlanks).toBe(0)

        const other = withBlanks()
        other.initAiWriter()
        await other.aiRun()
        watchers(other)['$wire.ticket.requestType']()
        expect(other.aiBlanks).toBe(0)
    })

    test('returns zero without a host marker', () => {
        document.body.innerHTML = ''
        const m = make()

        expect(m.aiBlankLeft()).toBe(0)
    })
})

describe('aiWriterMixin.aiFocus', () => {
    test('focuses the description and keeps its caret', () => {
        document.body.innerHTML = ''
        const m = make()
        const description = field('abc', {}, 'ticket.description')
        description.setSelectionRange(1, 1)

        m.aiFocus()

        expect(document.activeElement).toBe(description)
        expect(description.selectionStart).toBe(1)
    })
})

describe('aiWriterMixin.aiAsk', () => {
    test('focuses the description with the caret at the end', () => {
        document.body.innerHTML = ''
        const m = make()
        const description = field('abc', {}, 'ticket.description')

        m.aiAsk()

        expect(document.activeElement).toBe(description)
        expect(description.selectionStart).toBe(3)
    })
})
