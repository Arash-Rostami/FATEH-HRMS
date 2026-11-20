import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import {
    afterSuccess,
    correspondenceComposer,
    correspondenceLegend,
    correspondenceList,
    correspondencePage,
    correspondenceSignGuard,
    correspondenceTable,
    correspondenceViewer,
    formatJalali,
    jalaliField,
    letterBlockEditor,
    letterPreviewScale,
    parseJalali,
} from '../correspondence.js'

const h = vi.hoisted(() => {
    const s = { runs: [], config: null, html: '<p>x</p>', active: new Set(), attrs: {}, selectionEmpty: true, undo: true, redo: false, destroyed: 0 }

    class FakeEditor {
        constructor(config) {
            s.config = config
            this.state = { get selection() { return { empty: s.selectionEmpty, from: 1, to: 1 } } }
        }

        getHTML() {
            return s.html
        }

        isActive(name) {
            return s.active.has(name)
        }

        getAttributes() {
            return s.attrs
        }

        can() {
            return { undo: () => s.undo, redo: () => s.redo }
        }

        destroy() {
            s.destroyed++
        }

        chain() {
            const calls = []
            const proxy = new Proxy({}, {
                get: (_, name) => (name === 'run'
                    ? () => {
                        s.runs.push(calls.slice())
                        return true
                    }
                    : (...args) => {
                        calls.push([name, ...args])
                        return proxy
                    }),
            })
            return proxy
        }
    }

    const ext = (name) => ({ name, configure: (options) => ({ name, options }), extend: (spec) => ({ name, spec }) })

    return { s, FakeEditor, ext }
})

vi.mock('@tiptap/core', () => ({ Editor: h.FakeEditor, Extension: { create: (config) => config }, Node: { create: (config) => config } }))
vi.mock('@tiptap/starter-kit', () => ({ default: h.ext('StarterKit') }))
vi.mock('@tiptap/extension-underline', () => ({ default: h.ext('Underline') }))
vi.mock('@tiptap/extension-text-style', () => ({ default: h.ext('TextStyle') }))
vi.mock('@tiptap/extension-color', () => ({ default: h.ext('Color') }))
vi.mock('@tiptap/extension-font-family', () => ({ default: h.ext('FontFamily') }))
vi.mock('@tiptap/extension-text-align', () => ({ default: h.ext('TextAlign') }))
vi.mock('@tiptap/extension-table', () => ({ default: h.ext('Table') }))
vi.mock('@tiptap/extension-table-row', () => ({ default: h.ext('TableRow') }))
vi.mock('@tiptap/extension-table-cell', () => ({ default: h.ext('TableCell') }))
vi.mock('@tiptap/extension-table-header', () => ({ default: h.ext('TableHeader') }))
vi.mock('@tiptap/extension-link', () => ({ default: h.ext('Link') }))
vi.mock('@tiptap/extension-highlight', () => ({ default: h.ext('Highlight') }))
vi.mock('@tiptap/extension-subscript', () => ({ default: h.ext('Subscript') }))
vi.mock('@tiptap/extension-superscript', () => ({ default: h.ext('Superscript') }))

const toast = (target, type) => target.dispatchEvent(new CustomEvent('toast', { detail: { message: 'x', type } }))

describe('afterSuccess', () => {
    test('runs the callback when a success toast arrives before the call settles', async () => {
        const target = new EventTarget()
        const done = vi.fn()
        const call = new Promise((resolve) => setTimeout(resolve, 0))
        const result = afterSuccess(call, done, target)
        toast(target, 'success')
        await result
        expect(done).toHaveBeenCalledOnce()
    })

    test('skips the callback for error toasts or no toast', async () => {
        const target = new EventTarget()
        const done = vi.fn()
        const result = afterSuccess(Promise.resolve(), done, target)
        toast(target, 'error')
        await result
        await afterSuccess(undefined, done, target)
        expect(done).not.toHaveBeenCalled()
    })

    test('detaches the listener once settled', async () => {
        const target = new EventTarget()
        const remove = vi.spyOn(target, 'removeEventListener')
        await afterSuccess(Promise.resolve(), vi.fn(), target)
        expect(remove).toHaveBeenCalledWith('toast', expect.any(Function))
    })
})

describe('jalali helpers', () => {
    test('parseJalali reads latin and persian digits and trims zero padding', () => {
        expect(parseJalali('1404/03/09')).toEqual(['1404', '3', '9'])
        expect(parseJalali('۱۴۰۴/۱۲/۲۹')).toEqual(['1404', '12', '29'])
    })

    test('parseJalali returns empty parts for blank or malformed input', () => {
        expect(parseJalali('')).toEqual(['', '', ''])
        expect(parseJalali(null)).toEqual(['', '', ''])
        expect(parseJalali('1404-03-09')).toEqual(['', '', ''])
    })

    test('formatJalali pads month and day and returns empty when any part is missing', () => {
        expect(formatJalali('1404', '3', '9')).toBe('1404/03/09')
        expect(formatJalali('1404', '', '9')).toBe('')
    })
})

describe('correspondenceTable — shared table tools', () => {
    test('init loads the correspondence toolbar state and destroy releases its listeners', () => {
        window.localStorage.setItem('table-tools:correspondence', 'true')
        const table = correspondenceTable()
        table.init()
        expect(table.headerDeclutter).toBe(true)
        table.destroy()
        window.localStorage.removeItem('table-tools:correspondence')
        expect(table._tableToolsAc.signal.aborted).toBe(true)
    })

    test('syncPinned sends correspondence ids only when the server state differs', () => {
        const sync = vi.fn()
        const table = correspondenceTable()
        table.$store = { pinned: { getPinned: (scope) => (scope === 'correspondence' ? [4, 9] : []) } }
        table.$wire = { pinned: [], syncPinned: sync }

        table.syncPinned()
        expect(sync).toHaveBeenCalledWith([4, 9])

        table.$wire.pinned = [4, 9]
        table.syncPinned()
        expect(sync).toHaveBeenCalledTimes(1)
    })
})

describe('correspondenceViewer — sign dialog', () => {
    test('openSign clears the code, resets the sign target with a deferred set and opens the sign modal', () => {
        const opened = vi.fn()
        window.addEventListener('open-modal', opened, { once: true })
        const viewer = correspondenceViewer()
        viewer.$wire = { $set: vi.fn() }

        viewer.openSign()

        expect(viewer.$wire.$set).toHaveBeenCalledWith('signCode', '', false)
        expect(viewer.$wire.$set).toHaveBeenCalledWith('signTarget', 0, false)
        expect(opened.mock.calls[0][0].detail).toEqual({ name: 'correspondence-sign' })
    })

    test('openSign still opens the modal when the wire proxy is missing', () => {
        const opened = vi.fn()
        window.addEventListener('open-modal', opened, { once: true })
        const viewer = correspondenceViewer()

        expect(() => viewer.openSign()).not.toThrow()
        expect(opened).toHaveBeenCalledTimes(1)
    })
})

describe('correspondenceSignGuard', () => {
    test('closing the sign dialog resets the code and the sign target without a request', () => {
        let watcher
        const guard = correspondenceSignGuard()
        guard.$watch = (key, callback) => {
            expect(key).toBe('show')
            watcher = callback
        }
        guard.$wire = { $set: vi.fn() }
        guard.init()

        watcher(true)
        expect(guard.$wire.$set).not.toHaveBeenCalled()

        watcher(false)
        expect(guard.$wire.$set).toHaveBeenCalledWith('signCode', '', false)
        expect(guard.$wire.$set).toHaveBeenCalledWith('signTarget', 0, false)
    })
})

const mounted = []

const succeeding = () => new Promise((resolve) => setTimeout(() => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'x', type: 'success' } }))
    resolve()
}, 0))

const mountEditor = async (data = {}) => {
    const el = document.createElement('div')
    Object.assign(el.dataset, { model: 'form.html', value: '<p>x</p>', linkError: 'bad-link', pasteWarning: 'big-paste', unit: 'B', ...data })
    const surface = document.createElement('div')
    el.append(surface)
    document.body.append(el)
    const api = letterBlockEditor()
    const watchers = {}
    api.$el = el
    api.$refs = { surface }
    api.$wire = { $set: vi.fn() }
    api.$watch = (key, callback) => {
        watchers[key] = callback
    }
    await api.init()
    mounted.push(api)
    return { api, el, watchers }
}

const lastRun = () => h.s.runs.at(-1)
const extension = (name) => h.s.config.extensions.find((e) => e?.name === name)
const cellOf = (style) => {
    const el = document.createElement('td')
    el.setAttribute('style', style)
    return el
}

beforeEach(() => {
    h.s.runs = []
    h.s.config = null
    h.s.html = '<p>x</p>'
    h.s.active.clear()
    h.s.attrs = {}
    h.s.selectionEmpty = true
    h.s.destroyed = 0
})

afterEach(() => {
    mounted.splice(0).forEach((api) => api.destroy())
    document.body.innerHTML = ''
    document.body.style.overflow = ''
    document.documentElement.removeAttribute('data-uid')
    window.sessionStorage.clear()
    delete window.Alpine
    vi.restoreAllMocks()
})

describe('letterBlockEditor — commands', () => {
    test('halfSpace inserts a ZWNJ (U+200C) text node', async () => {
        const { api } = await mountEditor()
        api.halfSpace()
        expect(lastRun()).toEqual([['focus'], ['insertContent', { type: 'text', text: '‌' }]])
        expect(lastRun()[1][1].text.charCodeAt(0)).toBe(0x200c)
    })

    test('the Mod-Shift-2 shortcut inserts the same ZWNJ', async () => {
        await mountEditor()
        const insertContent = vi.fn(() => true)
        extension('letterHalfSpace').addKeyboardShortcuts.call({ editor: { commands: { insertContent } } })['Mod-Shift-2']()
        expect(insertContent).toHaveBeenCalledWith({ type: 'text', text: '‌' })
    })

    test('quote, rule, pageBreak, superscript and subscript map to their tiptap commands', async () => {
        const { api } = await mountEditor()
        api.quote()
        expect(lastRun()).toEqual([['focus'], ['toggleBlockquote']])
        api.rule()
        expect(lastRun()).toEqual([['focus'], ['setHorizontalRule']])
        api.pageBreak()
        expect(lastRun()).toEqual([['focus'], ['setPageBreak']])
        api.superscript()
        expect(lastRun()).toEqual([['focus'], ['toggleSuperscript']])
        api.subscript()
        expect(lastRun()).toEqual([['focus'], ['toggleSubscript']])
    })

    test('clearFormat removes nodes first and then every mark', async () => {
        const { api } = await mountEditor()
        api.clearFormat()
        expect(lastRun()).toEqual([['focus'], ['clearNodes'], ['unsetAllMarks']])
    })

    test('highlight accepts only 3 or 6 digit hex and clears on blank', async () => {
        const { api } = await mountEditor()
        api.highlight('#ff0')
        expect(lastRun()).toEqual([['focus'], ['setHighlight', { color: '#ff0' }]])
        api.highlight(' #FFEE00 ')
        expect(lastRun()).toEqual([['focus'], ['setHighlight', { color: '#FFEE00' }]])
        api.highlight('')
        expect(lastRun()).toEqual([['focus'], ['unsetHighlight']])

        const before = h.s.runs.length
        for (const bad of ['red', '#12345678', '#12', 'url(javascript:x)', 'rgb(1,2,3)', '#ggg']) api.highlight(bad)
        expect(h.s.runs.length).toBe(before)
    })

    test('color, size and fontFamily reject hostile values and only run for valid ones', async () => {
        const { api } = await mountEditor()
        api.color('#a1b2c3')
        expect(lastRun()).toEqual([['focus'], ['setColor', '#a1b2c3']])
        api.color('')
        expect(lastRun()).toEqual([['focus'], ['unsetColor']])
        api.size('14')
        expect(lastRun()).toEqual([['focus'], ['setMark', 'textStyle', { fontSize: '14px' }]])
        api.size('12pt')
        expect(lastRun()).toEqual([['focus'], ['setMark', 'textStyle', { fontSize: '12pt' }]])
        api.size('')
        expect(lastRun()).toEqual([['focus'], ['setMark', 'textStyle', { fontSize: null }]])
        api.fontFamily('Times New Roman')
        expect(lastRun()).toEqual([['focus'], ['setFontFamily', 'Times New Roman']])
        api.fontFamily('')
        expect(lastRun()).toEqual([['focus'], ['unsetFontFamily']])

        const before = h.s.runs.length
        api.color('red;}')
        api.size('1em')
        api.size('999999px')
        api.fontFamily('x;}</style>')
        expect(h.s.runs.length).toBe(before)
    })

    test('heading maps 0 to a paragraph, 2-4 to levels and ignores everything else', async () => {
        const { api } = await mountEditor()
        api.heading(0)
        expect(lastRun()).toEqual([['focus'], ['setParagraph']])
        api.heading(3)
        expect(lastRun()).toEqual([['focus'], ['setHeading', { level: 3 }]])
        const before = h.s.runs.length
        api.heading(1)
        api.heading(5)
        expect(h.s.runs.length).toBe(before)
    })

    test('align, list and dir ignore unknown values', async () => {
        const { api } = await mountEditor()
        api.align('center')
        expect(lastRun()).toEqual([['focus'], ['setTextAlign', 'center']])
        api.list('ordered')
        expect(lastRun()).toEqual([['focus'], ['toggleOrderedList']])
        const before = h.s.runs.length
        api.align('start')
        api.list('checklist')
        api.dir('sideways')
        expect(h.s.runs.length).toBe(before)
    })

    test('table clamps rows to 30 and columns to 20 and defaults to 2x2', async () => {
        const { api } = await mountEditor()
        api.table(100, 100)
        expect(lastRun()).toEqual([['focus'], ['insertTable', { rows: 30, cols: 20, withHeaderRow: true }]])
        api.table(0, 'x')
        expect(lastRun()).toEqual([['focus'], ['insertTable', { rows: 2, cols: 2, withHeaderRow: true }]])
    })

    test('cellBg accepts hex only', async () => {
        const { api } = await mountEditor()
        api.cellBg('#abc')
        expect(lastRun()).toEqual([['focus'], ['setCellAttribute', 'backgroundColor', '#abc']])
        const before = h.s.runs.length
        api.cellBg('blue')
        api.cellBg('')
        expect(h.s.runs.length).toBe(before)
    })

    test('insertToken strips angle brackets, caps at 60 characters and skips empty text', async () => {
        const { api } = await mountEditor()
        api.insertToken('<b>[نام]</b>')
        expect(lastRun()).toEqual([['focus'], ['insertContent', { type: 'text', text: 'b[نام]/b' }]])
        api.insertToken('x'.repeat(100))
        expect(lastRun()[1][1].text).toHaveLength(60)
        const before = h.s.runs.length
        api.insertToken('<>')
        expect(h.s.runs.length).toBe(before)
    })
})

describe('letterBlockEditor — link', () => {
    test('a bad scheme raises the toast with the server-provided message and runs nothing', async () => {
        const { api } = await mountEditor()
        const toasts = []
        window.addEventListener('toast', (e) => toasts.push(e.detail), { once: true })

        for (const bad of ['javascript:alert(1)', 'data:text/html,x', 'ftp://example.com', 'java\tscript:alert(1)', 'https://a.com/ x', '/relative/path:1']) {
            api.linkUrl = bad
            api.applyLink()
        }

        expect(h.s.runs).toEqual([])
        expect(toasts[0]).toEqual({ message: 'bad-link', type: 'warning' })
    })

    test('a bare host gets https:// and is inserted as linked text when nothing is selected', async () => {
        const { api } = await mountEditor()
        api.linkUrl = ' example.com '
        api.applyLink()
        expect(lastRun()).toEqual([['focus'], ['insertContent', { type: 'text', text: 'https://example.com', marks: [{ type: 'link', attrs: { href: 'https://example.com' } }] }]])
    })

    test('with a selection the link is set on the extended mark range, and mailto is allowed', async () => {
        const { api } = await mountEditor()
        h.s.selectionEmpty = false
        api.linkUrl = 'mailto:a@b.co'
        api.applyLink()
        expect(lastRun()).toEqual([['focus'], ['extendMarkRange', 'link'], ['setLink', { href: 'mailto:a@b.co' }]])
    })

    test('a blank url removes the link and prepareLink reads the active href', async () => {
        const { api } = await mountEditor()
        api.linkUrl = '  '
        api.applyLink()
        expect(lastRun()).toEqual([['focus'], ['extendMarkRange', 'link'], ['unsetLink']])
        h.s.attrs = { href: 'https://x.org' }
        api.prepareLink()
        expect(api.linkUrl).toBe('https://x.org')
    })

    test('the Link extension only allows http, https and mailto uris', async () => {
        await mountEditor()
        const { isAllowedUri, openOnClick, autolink } = extension('Link').options
        expect(isAllowedUri('https://a.org/x?y=1')).toBe(true)
        expect(isAllowedUri('http://a.org')).toBe(true)
        expect(isAllowedUri('mailto:a@b.co')).toBe(true)
        for (const bad of ['javascript:alert(1)', 'data:text/html,1', '/relative', 'https://a.org/"x', '', null]) expect(isAllowedUri(bad)).toBe(false)
        expect(openOnClick).toBe(false)
        expect(autolink).toBe(false)
    })
})

describe('letterBlockEditor — indent', () => {
    const run = (api, direction, node, domDirection) => {
        direction === 'in' ? api.indent() : api.outdent()
        const command = lastRun().find((call) => call[0] === 'command')[1]
        const setNodeMarkup = vi.fn()
        const view = { nodeDOM: () => (domDirection ? document.createElement('p') : null) }
        const state = { selection: { from: 0, to: 9 }, doc: { nodesBetween: (from, to, visit) => visit(node, 4) } }
        command({ tr: { setNodeMarkup }, state, view })
        return setNodeMarkup
    }

    test('indent steps up one level and stops at 5; outdent steps down and stops at 0', async () => {
        const { api } = await mountEditor()
        const paragraph = (indent) => ({ type: { name: 'paragraph' }, attrs: { indent, dir: null } })

        expect(run(api, 'in', paragraph(2)).mock.calls[0][2]).toMatchObject({ indent: 3, indentSide: 'right' })
        expect(run(api, 'in', paragraph(5)).mock.calls[0][2].indent).toBe(5)
        expect(run(api, 'out', paragraph(3)).mock.calls[0][2].indent).toBe(2)
        expect(run(api, 'out', paragraph(0)).mock.calls[0][2].indent).toBe(0)
    })

    test('indent uses the left side for a left-to-right block', async () => {
        const { api } = await mountEditor()
        vi.spyOn(window, 'getComputedStyle').mockReturnValue({ direction: 'ltr' })
        const calls = run(api, 'in', { type: { name: 'heading' }, attrs: { indent: 0 } }, true).mock.calls
        expect(calls[0][2]).toMatchObject({ indent: 1, indentSide: 'left' })
    })

    test('only paragraphs and headings are indentable', async () => {
        const { api } = await mountEditor()
        expect(run(api, 'in', { type: { name: 'tableCell' }, attrs: { indent: 0 } })).not.toHaveBeenCalled()
    })

    test('inside a list item indent and outdent sink and lift the item instead', async () => {
        const { api } = await mountEditor()
        h.s.active.add('listItem')
        api.indent()
        expect(lastRun()).toEqual([['focus'], ['sinkListItem', 'listItem']])
        api.outdent()
        expect(lastRun()).toEqual([['focus'], ['liftListItem', 'listItem']])
    })

    test('the indent attribute parses margin pt into capped steps and renders on the chosen side', async () => {
        await mountEditor()
        const { indent, indentSide } = extension('letterIndent').addGlobalAttributes()[0].attributes
        const el = (style) => {
            const p = document.createElement('p')
            p.setAttribute('style', style)
            return p
        }

        expect(indent.parseHTML(el('margin-right: 48pt'))).toBe(2)
        expect(indent.parseHTML(el('margin-left: 600pt'))).toBe(5)
        expect(indent.parseHTML(el('color: red'))).toBe(0)
        expect(indentSide.parseHTML(el('margin-left: 24pt'))).toBe('left')
        expect(indentSide.parseHTML(el('margin-right: 24pt'))).toBe('right')
        expect(indent.renderHTML({ indent: 2, indentSide: 'left' })).toEqual({ style: 'margin-left: 48pt' })
        expect(indent.renderHTML({ indent: 3, indentSide: 'right' })).toEqual({ style: 'margin-right: 72pt' })
        expect(indent.renderHTML({ indent: 0 })).toEqual({})
    })
})

describe('letterBlockEditor — document model', () => {
    test('the page break node round-trips only an hr carrying page-break-after: always', async () => {
        await mountEditor()
        const node = extension('pageBreak')
        const hr = (style) => {
            const el = document.createElement('hr')
            if (style) el.setAttribute('style', style)
            return el
        }
        const rule = node.parseHTML()[0]

        expect(rule.getAttrs(hr('page-break-after: always'))).toEqual({})
        expect(rule.getAttrs(hr('PAGE-BREAK-AFTER:ALWAYS'))).toEqual({})
        expect(rule.getAttrs(hr())).toBe(false)
        expect(rule.getAttrs(hr('border: 1px solid'))).toBe(false)
        expect(node.renderHTML()).toEqual(['hr', { style: 'page-break-after: always' }])
        expect(node.atom).toBe(true)
    })

    test('the highlight fill keeps hex only on parse and renders it as background-color', async () => {
        await mountEditor()
        const color = extension('Highlight').spec.addAttributes().color

        expect(color.parseHTML(cellOf('background-color: #ff0'))).toBe('#ff0')
        expect(color.parseHTML(cellOf('background: #FFEE00'))).toBe('#FFEE00')
        for (const bad of ['background-color: red', 'background-color: #12345678', 'background-color: rgb(1,2,3)', 'color: #fff']) {
            expect(color.parseHTML(cellOf(bad))).toBeNull()
        }
        expect(color.renderHTML({ color: '#ff0' })).toEqual({ style: 'background-color: #ff0' })
        expect(color.renderHTML({ color: null })).toEqual({})
        expect(extension('Highlight').spec.addInputRules()).toEqual([])
        expect(extension('Highlight').spec.addPasteRules()).toEqual([])
    })

    test('block direction only parses rtl, ltr or auto', async () => {
        await mountEditor()
        const dir = extension('letterDir').addGlobalAttributes()[0].attributes.dir
        const p = (value) => {
            const el = document.createElement('p')
            if (value !== null) el.setAttribute('dir', value)
            return el
        }

        expect(dir.parseHTML(p('RTL'))).toBe('rtl')
        expect(dir.parseHTML(p('auto'))).toBe('auto')
        expect(dir.parseHTML(p('sideways'))).toBeNull()
        expect(dir.parseHTML(p(null))).toBeNull()
        expect(dir.renderHTML({ dir: 'ltr' })).toEqual({ dir: 'ltr' })
        expect(dir.renderHTML({ dir: null })).toEqual({})
    })

    test('font size and table cell attributes parse only safe values', async () => {
        await mountEditor()
        const size = extension('letterFontSize').addGlobalAttributes()[0].attributes.fontSize
        const sized = (value) => {
            const el = document.createElement('span')
            el.style.fontSize = value
            return el
        }
        expect(size.parseHTML(sized('12pt'))).toBe('12pt')
        expect(size.parseHTML(sized('1em'))).toBeNull()
        expect(size.renderHTML({ fontSize: '12pt' })).toEqual({ style: 'font-size: 12pt' })

        const { backgroundColor, textAlign } = extension('letterCells').addGlobalAttributes()[0].attributes
        expect(backgroundColor.parseHTML(cellOf('background-color: #fff'))).toBe('#fff')
        expect(backgroundColor.parseHTML(cellOf('background: url(javascript:x)'))).toBeNull()
        expect(textAlign.parseHTML(cellOf('text-align: center'))).toBe('center')
        expect(textAlign.parseHTML(cellOf('text-align: evil'))).toBeNull()
    })
})

describe('letterBlockEditor — paste cleaning', () => {
    const paste = async (html) => {
        await mountEditor()
        const box = document.createElement('div')
        box.innerHTML = h.s.config.editorProps.transformPastedHTML(html)
        return box
    }

    test('strips style, align and class but keeps dir, colspan and a safe link', async () => {
        const box = await paste(
            '<p style="text-align:center" align="center" class="MsoNormal" dir="rtl">hi</p>'
            + '<table><tbody><tr><td colspan="2" style="background:red" class="x">c</td></tr></tbody></table>'
            + '<a href="https://example.org/a" style="color:red" class="l">ok</a>',
        )

        const p = box.querySelector('p')
        expect(p.getAttribute('dir')).toBe('rtl')
        expect(p.hasAttribute('style')).toBe(false)
        expect(p.hasAttribute('align')).toBe(false)
        expect(p.hasAttribute('class')).toBe(false)
        const td = box.querySelector('td')
        expect(td.getAttribute('colspan')).toBe('2')
        expect(td.hasAttribute('style')).toBe(false)
        const a = box.querySelector('a')
        expect(a.getAttribute('href')).toBe('https://example.org/a')
        expect(a.getAttribute('rel')).toBe('noopener')
        expect(a.hasAttribute('style')).toBe(false)
    })

    test('drops unsafe hrefs, scripts, images, comments and keeps emphasis from inline styles', async () => {
        const box = await paste(
            '<a href="javascript:alert(1)">x</a><script>alert(1)</script><img src="x"><!-- c -->'
            + '<span style="font-weight:700">bold</span>',
        )

        expect(box.querySelector('a').hasAttribute('href')).toBe(false)
        expect(box.querySelector('script')).toBeNull()
        expect(box.querySelector('img')).toBeNull()
        expect(box.innerHTML).not.toContain('<!--')
        expect(box.querySelector('span strong')?.textContent).toBe('bold')
    })

    test('an oversized paste falls back to plain text with a warning toast', async () => {
        await mountEditor()
        const toasts = []
        window.addEventListener('toast', (e) => toasts.push(e.detail), { once: true })

        const out = h.s.config.editorProps.transformPastedHTML(`<p>${'a'.repeat(1000001)}</p>`)

        expect(out).not.toContain('<')
        expect(out.length).toBeLessThanOrEqual(1000000)
        expect(toasts[0]).toEqual({ message: 'big-paste', type: 'warning' })
    })
})

describe('letterBlockEditor — state, sync and fullscreen', () => {
    test('push stores the html on the bound model silently and tracks the byte count', async () => {
        const { api } = await mountEditor()
        h.s.html = '<p>ش</p>'

        api.push()

        expect(api.$wire.$set).toHaveBeenCalledWith('form.html', '<p>ش</p>', false)
        expect(api.count).toBe(new TextEncoder().encode('<p>ش</p>').length)
        expect(api.over).toBe(false)
        api.count = api.maxBytes + 1
        expect(api.over).toBe(true)
        expect(api.counter).toContain('B')
    })

    test('flush and push do nothing after destroy and destroy tears the editor down once', async () => {
        const { api } = await mountEditor()

        api.destroy()
        api.$wire.$set.mockClear()
        api.push()
        api.flush()

        expect(api.$wire.$set).not.toHaveBeenCalled()
        expect(h.s.destroyed).toBe(1)
        expect(api.destroyed).toBe(true)
    })

    test('the letter-editor-flush event pushes the pending html and is detached on destroy', async () => {
        const { api } = await mountEditor()
        api.$wire.$set.mockClear()

        window.dispatchEvent(new CustomEvent('letter-editor-flush'))
        expect(api.$wire.$set).toHaveBeenCalledTimes(1)

        api.destroy()
        api.$wire.$set.mockClear()
        window.dispatchEvent(new CustomEvent('letter-editor-flush'))
        expect(api.$wire.$set).not.toHaveBeenCalled()
    })

    test('fullscreen locks body scrolling through the watcher and close() leaves fullscreen', async () => {
        const { api, watchers } = await mountEditor()
        expect(api.fullscreen).toBe(false)

        watchers.fullscreen(true)
        expect(document.body.style.overflow).toBe('hidden')
        watchers.fullscreen(false)
        expect(document.body.style.overflow).toBe('')

        api.fullscreen = true
        api.close()
        expect(api.fullscreen).toBe(false)
    })

    test('destroying while fullscreen restores body scrolling', async () => {
        const { api, watchers } = await mountEditor()
        api.fullscreen = true
        watchers.fullscreen(true)
        expect(document.body.style.overflow).toBe('hidden')

        api.destroy()

        expect(document.body.style.overflow).toBe('')
    })

    test('init without a surface ref does nothing', async () => {
        const api = letterBlockEditor()
        api.$refs = {}
        api.$watch = vi.fn()
        await api.init()
        expect(api.$watch).not.toHaveBeenCalled()
        expect(h.s.config).toBeNull()
    })

    test('undo and redo availability follows the editor and tick-based reactivity', async () => {
        const { api } = await mountEditor()
        h.s.undo = true
        h.s.redo = false
        expect(api.undoOff).toBe(false)
        expect(api.redoOff).toBe(true)
        api.undo()
        expect(lastRun()).toEqual([['focus'], ['undo']])
        api.clear()
        expect(lastRun()).toEqual([['focus'], ['clearContent']])
    })
})

describe('fullscreen letter editor — CSS guard', () => {
    const css = readFileSync(resolve(process.cwd(), 'resources/css/core/dashboard.css'), 'utf8')
    const escape = (text) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const block = (selector) => css.match(new RegExp(`${escape(selector)}\\s*\\{([^}]*)\\}`))?.[1] ?? null

    test('the fullscreen layer is a fixed viewport box and never uses a transform', () => {
        const layer = block('[data-letter-editor][data-fullscreen]')
        expect(layer).toContain('position: fixed')
        expect(layer).toContain('inset: 0')
        expect(layer).not.toMatch(/transform/)
    })

    test('direct children of the fullscreen layer drop their max-width', () => {
        const children = block('[data-letter-editor][data-fullscreen] > *')
        expect(children).toContain('max-width: none')
        expect(children).not.toMatch(/transform/)
    })

    test('the two body:has rules lift the content shell and the teleported popovers above the layer', () => {
        expect(block('body:has([data-letter-editor][data-fullscreen]) #content-shell')).toMatch(/z-index:\s*9999/)
        expect(block('body:has([data-letter-editor][data-fullscreen]) > .z-\\[110\\]')).toMatch(/z-index:\s*10000/)
    })
})

describe('correspondencePage', () => {
    test('openModal names the modal and confirmations no longer live in the page factory', () => {
        const page = correspondencePage()
        expect(page.confirmAction).toBeUndefined()

        const opened = []
        window.addEventListener('open-modal', (e) => opened.push(e.detail), { once: true })
        page.openModal('correspondence-share')
        expect(opened[0]).toEqual({ name: 'correspondence-share' })
    })
})

describe('correspondenceComposer', () => {
    const composer = (dataset = {}) => {
        const el = document.createElement('div')
        Object.assign(el.dataset, dataset)
        document.body.append(el)
        const c = correspondenceComposer()
        c.$el = el
        c.$wire = {}
        return { c, el }
    }

    test('blocked reflects the locked flag or any block editor over its byte cap', () => {
        const { c, el } = composer()
        const editor = document.createElement('div')
        editor.setAttribute('data-letter-editor', '')
        el.append(editor)
        window.Alpine = { $data: (node) => ({ over: node.dataset.over === '1' }) }

        expect(c.over).toBe(false)
        expect(c.blocked).toBe(false)
        editor.dataset.over = '1'
        expect(c.over).toBe(true)
        expect(c.blocked).toBe(true)
        editor.dataset.over = '0'
        el.dataset.locked = '1'
        expect(c.blocked).toBe(true)
    })

    test('subject drafts are remembered only for fresh non-private letters, keyed by user', () => {
        document.documentElement.dataset.uid = '7'
        const { c } = composer()
        const input = document.createElement('input')
        input.dataset.fresh = '1'
        input.value = 'موضوع'

        c.rememberSubject(input)
        expect(window.sessionStorage.getItem('correspondence:draft:7:correspondence')).toBe('موضوع')

        input.dataset.private = '1'
        c.rememberSubject(input)
        expect(window.sessionStorage.getItem('correspondence:draft:7:correspondence')).toBeNull()
    })

    test('initSubject restores a saved draft into an empty field and clears it for non-fresh letters', () => {
        document.documentElement.dataset.uid = '7'
        const { c } = composer()
        window.sessionStorage.setItem('correspondence:draft:7:correspondence', 'saved')
        const input = document.createElement('input')
        input.dataset.fresh = '1'
        const fired = vi.fn()
        input.addEventListener('input', fired)

        c.initSubject(input)
        expect(input.value).toBe('saved')
        expect(fired).toHaveBeenCalledOnce()

        const filled = document.createElement('input')
        filled.dataset.fresh = '1'
        filled.value = 'typed'
        c.initSubject(filled)
        expect(filled.value).toBe('typed')

        const stored = document.createElement('input')
        c.initSubject(stored)
        expect(window.sessionStorage.getItem('correspondence:draft:7:correspondence')).toBeNull()
    })

    test('rememberDraft caps the saved text at 200 characters and removes it when blank', () => {
        document.documentElement.dataset.uid = '7'
        const { c } = composer()
        c.rememberDraft('x'.repeat(500))
        expect(c.draft()).toHaveLength(200)
        c.rememberDraft('')
        expect(c.draft()).toBe('')
    })

    test('classify drops the draft when the classification leaves normal', () => {
        document.documentElement.dataset.uid = '7'
        const { c } = composer({ normal: 'normal' })
        c.rememberDraft('keep')
        c.classify('normal')
        expect(c.draft()).toBe('keep')
        c.classify('secret')
        expect(c.draft()).toBe('')
    })

    test('save flushes the editors, switches to simple mode first and clears the draft only after a success toast', async () => {
        document.documentElement.dataset.uid = '7'
        const { c } = composer()
        const order = []
        const flushed = vi.fn(() => order.push('flush'))
        window.addEventListener('letter-editor-flush', flushed, { once: true })
        c.rememberDraft('draft')
        c.$wire = {
            $set: vi.fn(() => {
                order.push('set')
                return Promise.resolve()
            }),
            save: vi.fn(() => {
                order.push('save')
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success' } }))
                return Promise.resolve()
            }),
        }

        await c.save()

        expect(order).toEqual(['flush', 'set', 'save'])
        expect(c.$wire.$set).toHaveBeenCalledWith('form.mode', 'simple', false)
        expect(c.draft()).toBe('')
    })

    test('save keeps the draft when the request ends without a success toast', async () => {
        document.documentElement.dataset.uid = '7'
        const { c } = composer()
        c.rememberDraft('draft')
        c.$wire = { $set: vi.fn(() => Promise.resolve()), save: vi.fn(() => Promise.resolve()) }

        await c.save()

        expect(c.draft()).toBe('draft')
    })

    test('share flushes, closes the modal on success only, and openShare flushes before opening', async () => {
        const { c } = composer()
        const closed = vi.fn()
        const opened = []
        window.addEventListener('close-modal', closed)
        window.addEventListener('open-modal', (e) => opened.push(e.detail), { once: true })
        c.$wire = { shareLetter: vi.fn(succeeding) }

        await c.share()
        expect(closed).toHaveBeenCalledOnce()

        c.$wire = { shareLetter: vi.fn(() => Promise.resolve()) }
        await c.share()
        expect(closed).toHaveBeenCalledOnce()

        c.openShare()
        expect(opened[0]).toEqual({ name: 'correspondence-share' })
        window.removeEventListener('close-modal', closed)
    })

    test('generate flushes the editors before calling the wire method', () => {
        const { c } = composer()
        const order = []
        window.addEventListener('letter-editor-flush', () => order.push('flush'), { once: true })
        c.$wire = { generate: vi.fn(() => order.push('generate')) }

        c.generate()

        expect(order).toEqual(['flush', 'generate'])
    })

    test('init observes the composer and destroy disconnects the observer', () => {
        const { c } = composer()
        const disconnect = vi.spyOn(MutationObserver.prototype, 'disconnect')
        c.init()
        c.destroy()
        expect(disconnect).toHaveBeenCalledOnce()
    })
})

describe('correspondenceViewer', () => {
    test('toggleDiff flips the diff flag and openReject opens the reject modal', () => {
        const viewer = correspondenceViewer()
        const opened = []
        window.addEventListener('open-modal', (e) => opened.push(e.detail), { once: true })

        viewer.toggleDiff()
        expect(viewer.showDiff).toBe(true)
        viewer.toggleDiff()
        expect(viewer.showDiff).toBe(false)
        viewer.openReject()
        expect(opened[0]).toEqual({ name: 'correspondence-reject' })
    })

    test('runAndClose runs only the route methods and closes the modal after a success toast', async () => {
        const viewer = correspondenceViewer()
        const closed = vi.fn()
        window.addEventListener('close-modal', closed)
        viewer.$wire = { routeLetter: vi.fn(succeeding), voidLetter: vi.fn() }
        const button = (method) => {
            const el = document.createElement('button')
            el.dataset.method = method
            return el
        }

        expect(viewer.runAndClose(button('voidLetter'))).toBeUndefined()
        expect(viewer.$wire.voidLetter).not.toHaveBeenCalled()

        await viewer.runAndClose(button('routeLetter'))
        expect(viewer.$wire.routeLetter).toHaveBeenCalledOnce()
        expect(closed).toHaveBeenCalledOnce()
        window.removeEventListener('close-modal', closed)
    })
})

describe('correspondenceList and legend', () => {
    test('openBatch names the template batch modal by numeric id', () => {
        const list = correspondenceList()
        const opened = []
        window.addEventListener('open-modal', (e) => opened.push(e.detail), { once: true })
        list.openBatch('12')
        expect(opened[0]).toEqual({ name: 'correspondence-batch-12' })
    })

    test('useTemplate passes a numeric id and closes the modal only after success', async () => {
        const list = correspondenceList()
        const closed = vi.fn()
        window.addEventListener('close-modal', closed)
        list.$wire = { useTemplate: vi.fn(succeeding) }

        await list.useTemplate('5')

        expect(list.$wire.useTemplate).toHaveBeenCalledWith(5)
        expect(closed).toHaveBeenCalledOnce()
        window.removeEventListener('close-modal', closed)
    })

    test('the legend starts on the route tab and select changes the active tab', () => {
        const legend = correspondenceLegend()
        expect(legend.isTab('route')).toBe(true)
        legend.select('flow')
        expect(legend.isTab('flow')).toBe(true)
        expect(legend.isTab('route')).toBe(false)
    })
})

describe('jalaliField', () => {
    test('init loads the parts from the bound form field and push writes the formatted date silently', () => {
        const field = jalaliField()
        field.$el = document.createElement('div')
        field.$el.dataset.field = 'letter_date'
        field.$wire = { $get: vi.fn(() => '1404/03/09'), $set: vi.fn() }

        field.init()
        expect([field.y, field.m, field.d]).toEqual(['1404', '3', '9'])
        expect(field.$wire.$get).toHaveBeenCalledWith('form.letter_date')

        field.m = '12'
        field.d = '5'
        field.push()
        expect(field.$wire.$set).toHaveBeenCalledWith('form.letter_date', '1404/12/05', false)

        field.d = ''
        field.push()
        expect(field.$wire.$set).toHaveBeenLastCalledWith('form.letter_date', '', false)
    })
})

describe('letterPreviewScale', () => {
    const scaler = (avail, width, height = 800) => {
        const el = document.createElement('div')
        const paper = document.createElement('div')
        el.append(paper)
        Object.defineProperty(el, 'clientWidth', { value: avail })
        Object.defineProperty(paper, 'offsetWidth', { value: width })
        Object.defineProperty(paper, 'offsetHeight', { value: height })
        const api = letterPreviewScale()
        api.$el = el
        api.$refs = {}
        return { api, paper }
    }

    test('a paper wider than its container is scaled down and its bottom margin compensates the height', () => {
        const { api, paper } = scaler(500, 1000)
        api.apply()
        expect(api.scale).toBe(0.5)
        expect(paper.style.transform).toBe('scale(0.5)')
        expect(paper.style.marginBottom).toBe('-400px')
    })

    test('a paper that fits keeps scale 1 and clears the inline transform', () => {
        const { api, paper } = scaler(1200, 1000)
        api.apply()
        expect(api.scale).toBe(1)
        expect(paper.style.transform).toBe('')
        expect(paper.style.marginBottom).toBe('')
    })

    test('an unmeasurable container falls back to scale 1', () => {
        const { api } = scaler(0, 1000)
        api.apply()
        expect(api.scale).toBe(1)
    })
})
