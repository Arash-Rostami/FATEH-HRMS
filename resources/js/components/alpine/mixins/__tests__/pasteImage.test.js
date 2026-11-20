import { describe, expect, test, vi, beforeEach } from 'vitest'
import pasteImageMixin from '../pasteImage.js'

const INPUT_ID = 'composer-attachments'

class TestDataTransfer {
    constructor() {
        this._files = []
        this.items = { add: (file) => { this._files.push(file) } }
    }

    get files() {
        return this._files
    }
}

function imageFile(name = 'shot.png', type = 'image/png') {
    return new File(['img'], name, { type })
}

function mountInput() {
    const el = document.createElement('input')
    el.id = INPUT_ID
    el.type = 'file'
    el.multiple = true
    const chosen = []
    Object.defineProperty(el, 'files', {
        configurable: true,
        get: () => chosen,
        set: (list) => {
            chosen.length = 0
            for (let i = 0, len = list.length; i < len; i++) chosen.push(list[i])
        },
    })
    document.body.appendChild(el)
    return el
}

function pasteEvent({ text = '', html = '', files = [] } = {}) {
    const items = files.map((file) => ({
        kind: 'file',
        type: file.type,
        getAsFile: () => file,
    }))
    if (text !== '') {
        items.push({ kind: 'string', type: 'text/plain', getAsFile: () => null })
    }
    return {
        preventDefault: vi.fn(),
        clipboardData: {
            items,
            getData: (mime) => {
                if (mime === 'text/plain') return text
                if (mime === 'text/html') return html
                return ''
            },
        },
    }
}

function input() {
    return document.getElementById(INPUT_ID)
}

beforeEach(() => {
    globalThis.DataTransfer = TestDataTransfer
    document.body.innerHTML = ''
    mountInput()
})

describe('pasteImage — text stays text, a picture attaches only when there is no text', () => {
    test('text plus an image snapshot is left for the message box and is not attached', () => {
        const c = pasteImageMixin()
        const e = pasteEvent({ text: 'سلام ۱۲۳\r\n', html: '<p>سلام ۱۲۳</p>', files: [imageFile()] })
        const onChange = vi.fn()
        input().addEventListener('change', onChange)

        c.pasteImage(e, INPUT_ID)

        expect(e.preventDefault).not.toHaveBeenCalled()
        expect(input().files).toHaveLength(0)
        expect(onChange).not.toHaveBeenCalled()
    })

    test('a bare zero is text, not an attachment', () => {
        const c = pasteImageMixin()
        const e = pasteEvent({ text: '0', html: '<td>0</td>', files: [imageFile()] })

        c.pasteImage(e, INPUT_ID)

        expect(e.preventDefault).not.toHaveBeenCalled()
        expect(input().files).toHaveLength(0)
    })

    test('whitespace or a null byte plus an image still attaches the image', () => {
        const c = pasteImageMixin()
        for (const text of ['  \n', '\u0000']) {
            document.body.innerHTML = ''
            mountInput()
            const e = pasteEvent({ text, files: [imageFile('blank.png')] })

            c.pasteImage(e, INPUT_ID)

            expect(e.preventDefault).toHaveBeenCalled()
            expect(input().files).toHaveLength(1)
            expect(input().files[0].name).toBe('blank.png')
        }
    })

    test('an image with no text is attached and the file input is notified', () => {
        const c = pasteImageMixin()
        const e = pasteEvent({ files: [imageFile('shot.png')] })
        const onChange = vi.fn()
        input().addEventListener('change', onChange)

        c.pasteImage(e, INPUT_ID)

        expect(e.preventDefault).toHaveBeenCalledOnce()
        expect(input().files).toHaveLength(1)
        expect(input().files[0].name).toBe('shot.png')
        expect(onChange).toHaveBeenCalledOnce()
    })

    test('a second image is added beside a file already chosen', () => {
        const c = pasteImageMixin()
        const el = input()
        el.files = [imageFile('first.png')]
        const e = pasteEvent({ files: [imageFile('second.png')] })

        c.pasteImage(e, INPUT_ID)

        expect([...el.files].map((file) => file.name)).toEqual(['first.png', 'second.png'])
    })

    test('a copied image file whose name is plain text still attaches', () => {
        const c = pasteImageMixin()
        const e = pasteEvent({ text: 'photo.png', files: [imageFile('photo.png')] })

        c.pasteImage(e, INPUT_ID)

        expect(e.preventDefault).toHaveBeenCalled()
        expect(input().files).toHaveLength(1)
        expect(input().files[0].name).toBe('photo.png')
    })

    test('an image tag with no caption still attaches, even if the address is plain text', () => {
        const c = pasteImageMixin()
        const e = pasteEvent({
            text: 'https://example.com/shot.png',
            html: '<img src="https://example.com/shot.png">',
            files: [imageFile('shot.png')],
        })

        c.pasteImage(e, INPUT_ID)

        expect(e.preventDefault).toHaveBeenCalled()
        expect(input().files[0].name).toBe('shot.png')
    })

    test('a copied document or spreadsheet file is attached even with its filename as text', () => {
        const c = pasteImageMixin()
        const sheet = new File(['x'], 'report.xlsx', { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const doc = new File(['x'], 'letter.docx', { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
        const e = pasteEvent({ text: 'report.xlsx', files: [sheet, doc] })

        c.pasteImage(e, INPUT_ID)

        expect(e.preventDefault).toHaveBeenCalledOnce()
        expect([...input().files].map((file) => file.name)).toEqual(['report.xlsx', 'letter.docx'])
    })

    test('no clipboard does nothing', () => {
        const c = pasteImageMixin()

        expect(() => c.pasteImage({}, INPUT_ID)).not.toThrow()
        expect(input().files).toHaveLength(0)
    })
})
