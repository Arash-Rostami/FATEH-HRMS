import tableToolsMixin from '../mixins/tableTools.js';

const STORAGE_PREFIX = 'correspondence:';
const MAX_BYTES = 65000;
const PASTE_CAP = 1000000;
const DRAFT_CAP = 200;
const DIRS = new Set(['ltr', 'rtl', 'auto']);
const ALIGNS = new Set(['left', 'center', 'right', 'justify']);
const LISTS = new Set(['bullet', 'ordered']);
const BLOCKS = ['paragraph', 'heading', 'tableCell', 'tableHeader'];
const INDENTABLE = ['paragraph', 'heading'];
const INDENT_STEP = 24;
const INDENT_MAX = 5;
const ZWNJ = '‌';
const PAGE_BREAK_STYLE = 'page-break-after: always';
const PAGE_BREAK = /page-break-after\s*:\s*always/i;
const FONT_SIZES = /^\d{1,3}(\.\d{1,2})?(px|pt)$/i;
const BARE_SIZE = /^\d{1,3}(\.\d{1,2})?$/;
const HEX = /^#[0-9a-f]{3,8}$/i;
const HEX_FILL = /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i;
const FONT = /^[\p{L}0-9 ,'\-]{1,60}$/u;
const HREF = /^(?:https?:\/\/|mailto:)[^\s<>"'`\\]{1,2000}$/i;
const COLOR_VALUE = /^(#[0-9a-f]{3,8}|[a-z]{3,20})$/i;
const DRAFT_ID = 'correspondence';
const ROUTE_CALLS = new Set(['rerouteLetter', 'routeLetter']);

let encoder;
const byteLength = (text) => {
    if (typeof TextEncoder === 'undefined') return String(text).length;
    encoder ??= new TextEncoder();
    return encoder.encode(String(text)).length;
};

const safeJson = (raw, fallback) => {
    try {
        const parsed = JSON.parse(raw);
        return parsed ?? fallback;
    } catch {
        return fallback;
    }
};

const storageGet = (key) => {
    try {
        return window.sessionStorage.getItem(key);
    } catch {
        return null;
    }
};

const storageSet = (key, value) => {
    try {
        window.sessionStorage.setItem(key, value);
    } catch {
    }
};

const storageRemove = (key) => {
    try {
        window.sessionStorage.removeItem(key);
    } catch {
    }
};

const inlineStyle = (el) => {
    try {
        return el.getAttribute('style') || '';
    } catch {
        return '';
    }
};

const styleWeight = (el) => {
    const match = inlineStyle(el).match(/font-weight\s*:\s*([^;]+)/i);
    if (!match) return null;
    const raw = match[1].trim().toLowerCase();
    if (raw === 'bold' || raw === 'bolder') return 700;
    if (raw === 'normal') return 400;
    const numeric = parseInt(raw, 10);
    return Number.isFinite(numeric) ? numeric : null;
};

const styleHas = (el, keyword) => new RegExp(`font-style\\s*:\\s*${keyword}`, 'i').test(inlineStyle(el));

const hasDecoration = (el, keyword) => new RegExp(`text-decoration[^;]*\\b${keyword}\\b`, 'i').test(inlineStyle(el));

const unwrap = (el) => {
    const parent = el.parentNode;
    if (!parent) return;
    while (el.firstChild) parent.insertBefore(el.firstChild, el);
    parent.removeChild(el);
};

const wrapChildren = (el, tags) => {
    if (!tags.length) return;
    const children = [...el.childNodes];
    const wraps = tags.map((tag) => el.ownerDocument.createElement(tag));
    for (let i = 1; i < wraps.length; i++) wraps[i - 1].appendChild(wraps[i]);
    wraps[wraps.length - 1].append(...children);
    el.appendChild(wraps[0]);
};

const toStrong = (el) => styleWeight(el) !== null && styleWeight(el) >= 600;
const toItalic = (el) => styleHas(el, 'italic');
const toUnderline = (el) => hasDecoration(el, 'underline');
const toStrike = (el) => hasDecoration(el, 'line-through');

const inlineMarks = (el) => {
    const marks = [];
    if (toStrong(el)) marks.push('strong');
    if (toItalic(el)) marks.push('em');
    if (toUnderline(el)) marks.push('u');
    if (toStrike(el)) marks.push('s');
    return marks;
};

const convertInline = (el) => {
    if ((el.tagName === 'B' || el.tagName === 'STRONG') && styleWeight(el) === 400) {
        unwrap(el);
        return;
    }
    if (['B', 'STRONG', 'I', 'EM', 'U', 'S'].includes(el.tagName)) return;
    const marks = inlineMarks(el);
    if (marks.length) wrapChildren(el, marks);
};

const styleCells = (el) => {
    const marks = inlineMarks(el);
    if (marks.length) wrapChildren(el, marks);
};

const cleanPasted = (html) => {
    if (!html || html.length > PASTE_CAP) return null;
    if (typeof DOMParser !== 'function' || typeof NodeFilter === 'undefined') return null;
    const doc = new DOMParser().parseFromString(html, 'text/html');
    doc.querySelectorAll('script,style,iframe,object,embed,img,svg,video,audio,canvas,form,input,button,link,meta,noscript,template').forEach((el) => el.remove());
    const comments = [];
    const walker = doc.createTreeWalker(doc.body, NodeFilter.SHOW_COMMENT);
    while (walker.nextNode()) comments.push(walker.currentNode);
    comments.forEach((node) => node.remove());
    doc.querySelectorAll('td,th').forEach(styleCells);
    doc.querySelectorAll('b,strong,i,em,u,s,span,font,a').forEach(convertInline);
    doc.querySelectorAll('*').forEach((el) => {
        for (const attr of [...el.attributes]) {
            const name = attr.name.toLowerCase();
            if (name === 'colspan' || name === 'rowspan' || name === 'dir') continue;
            if (name === 'href' && el.tagName === 'A' && HREF.test(attr.value.trim())) {
                el.setAttribute('rel', 'noopener');
                continue;
            }
            el.removeAttribute(attr.name);
        }
    });
    return doc.body.innerHTML;
};

const indentOf = (el) => {
    const match = inlineStyle(el).match(/margin-(?:left|right)\s*:\s*(\d{1,3})pt/i);
    return match ? Math.min(INDENT_MAX, Math.max(0, Math.round(Number(match[1]) / INDENT_STEP))) : 0;
};

const fillOf = (el) => {
    const match = inlineStyle(el).match(/background(?:-color)?\s*:\s*([^;]+)/i);
    const value = match?.[1]?.trim() ?? '';
    return HEX_FILL.test(value) ? value : null;
};

const pastePayload = (html, warning = '') => {
    const cleaned = cleanPasted(html);
    if (cleaned !== null) return cleaned;
    const text = String(html ?? '').replace(/[<>&]/g, ' ').slice(0, PASTE_CAP);
    window.dispatchEvent(new CustomEvent('toast', { detail: { message: warning, type: 'warning' } }));
    return text;
};

const userId = () => String(document.documentElement?.dataset?.uid ?? '');

const emit = (name, detail = {}) => window.dispatchEvent(new CustomEvent(name, { detail }));

const flushEditors = () => emit('letter-editor-flush');

export function afterSuccess(call, onSuccess, target = window) {
    let ok = false;
    const mark = (event) => {
        if (event?.detail?.type === 'success') ok = true;
    };
    target.addEventListener('toast', mark);
    return Promise.resolve(call).finally(() => {
        target.removeEventListener('toast', mark);
        if (ok) onSuccess?.();
    });
}

export function parseJalali(value) {
    const text = String(value ?? '').replace(/[۰-۹]/g, (c) => String(c.charCodeAt(0) - 1776));
    const parts = text.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
    return parts ? [parts[1], String(Number(parts[2])), String(Number(parts[3]))] : ['', '', ''];
}

export function formatJalali(y, m, d) {
    const pad = (n) => String(n).padStart(2, '0');
    return y && m && d ? `${y}/${pad(m)}/${pad(d)}` : '';
}

const draftStore = (draftId) => {
    const key = `${STORAGE_PREFIX}draft:${userId()}:${draftId}`;

    return {
        draft() {
            return storageGet(key) ?? '';
        },

        rememberDraft(short) {
            const text = String(short ?? '').slice(0, DRAFT_CAP);
            if (text) storageSet(key, text);
            else storageRemove(key);
        },

        clearDraft() {
            storageRemove(key);
        },
    };
};

const dialogs = {
    openModal(name) {
        emit('open-modal', { name: String(name ?? '') });
    },

    confirmAction(el) {
        const { title = '', message = '', method = '' } = el?.dataset ?? {};
        emit('open-confirmation', { title, message, method });
    },
};

export function correspondencePage() {
    return {
        ...dialogs,

        scrollToLetter() {
            document.getElementById('correspondence-letter')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        print() {
            window.print();
        },

        activate(el) {
            el?.click?.();
        },
    };
}

export function correspondenceTable() {
    return {
        ...tableToolsMixin('correspondence'),

        init() {
            this.initTableTools();
            this.syncPinned();
        },

        destroy() {
            this.destroyTableTools();
        },

        syncPinned() {
            const ids = this.$store?.pinned?.getPinned('correspondence') ?? [];
            const current = this.$wire?.pinned ?? [];
            if (ids.join() !== current.join()) this.$wire.syncPinned(ids)?.catch?.(() => {});
        },
    };
}

export function letterBlockEditor() {
    let editor = null;

    return {
        destroyed: false,
        fullscreen: false,
        count: 0,
        tick: 0,
        frame: 0,
        maxBytes: MAX_BYTES,
        tokens: [],
        flushHandler: null,
        linkUrl: '',
        linkError: '',
        pasteWarning: '',
        unit: '',

        push() {
            if (!editor || this.destroyed) return;
            const html = editor.getHTML();
            this.count = byteLength(html);
            const model = this.$el?.dataset?.model;
            if (model) this.$wire?.$set?.(model, html, false);
        },

        close() {
            this.fullscreen = false;
        },

        async init() {
            const surface = this.$refs.surface;
            if (!surface) return;
            this.$watch('fullscreen', (open) => {
                document.body.style.overflow = open ? 'hidden' : '';
            });
            const data = this.$el?.dataset ?? {};
            const value = String(data.value ?? '');
            const dir = data.dir;
            const lang = String(data.lang ?? 'fa');
            this.tokens = safeJson(data.tokens, []);
            this.linkError = String(data.linkError ?? '');
            this.pasteWarning = String(data.pasteWarning ?? '');
            this.unit = String(data.unit ?? '');
            this.flushHandler = () => this.flush();
            window.addEventListener('letter-editor-flush', this.flushHandler);
            try {
                const [core, kit, underline, textStyle, color, fontFamily, textAlign, table, tableRow, tableCell, tableHeader, link, highlight, subscript, superscript] = await Promise.all([
                    import('@tiptap/core'),
                    import('@tiptap/starter-kit'),
                    import('@tiptap/extension-underline'),
                    import('@tiptap/extension-text-style'),
                    import('@tiptap/extension-color'),
                    import('@tiptap/extension-font-family'),
                    import('@tiptap/extension-text-align'),
                    import('@tiptap/extension-table'),
                    import('@tiptap/extension-table-row'),
                    import('@tiptap/extension-table-cell'),
                    import('@tiptap/extension-table-header'),
                    import('@tiptap/extension-link'),
                    import('@tiptap/extension-highlight'),
                    import('@tiptap/extension-subscript'),
                    import('@tiptap/extension-superscript'),
                ]);
                if (this.destroyed || !surface.isConnected) return;
                const pick = (mod, name) => mod?.default ?? mod?.[name];
                const { Editor, Extension, Node: BlockNode } = core;
                editor = new Editor({
                    element: surface,
                    content: value,
                    extensions: [
                        pick(kit, 'StarterKit').configure({
                            code: false,
                            codeBlock: false,
                            heading: { levels: [2, 3, 4] },
                            history: { depth: 30, newGroupDelay: 400 },
                        }),
                        pick(underline, 'Underline'),
                        pick(subscript, 'Subscript'),
                        pick(superscript, 'Superscript'),
                        pick(link, 'Link').configure({
                            openOnClick: false,
                            autolink: false,
                            linkOnPaste: false,
                            HTMLAttributes: { rel: 'noopener', target: '_blank' },
                            isAllowedUri: (url) => HREF.test(String(url ?? '').trim()),
                        }),
                        pick(highlight, 'Highlight').extend({
                            addAttributes() {
                                return {
                                    color: {
                                        default: null,
                                        parseHTML: fillOf,
                                        renderHTML: (attrs) => (attrs.color ? { style: `background-color: ${attrs.color}` } : {}),
                                    },
                                };
                            },
                            addInputRules() {
                                return [];
                            },
                            addPasteRules() {
                                return [];
                            },
                        }),
                        BlockNode.create({
                            name: 'pageBreak',
                            group: 'block',
                            atom: true,
                            selectable: true,
                            parseHTML() {
                                return [{ tag: 'hr', priority: 100, getAttrs: (el) => (PAGE_BREAK.test(inlineStyle(el)) ? {} : false) }];
                            },
                            renderHTML() {
                                return ['hr', { style: PAGE_BREAK_STYLE }];
                            },
                            addCommands() {
                                return {
                                    setPageBreak: () => ({ chain }) => chain().insertContent([{ type: this.name }, { type: 'paragraph' }]).run(),
                                };
                            },
                        }),
                        Extension.create({
                            name: 'letterHalfSpace',
                            addKeyboardShortcuts() {
                                return {
                                    'Mod-Shift-2': () => this.editor.commands.insertContent({ type: 'text', text: ZWNJ }),
                                };
                            },
                        }),
                        Extension.create({
                            name: 'letterIndent',
                            addGlobalAttributes() {
                                return [{
                                    types: INDENTABLE,
                                    attributes: {
                                        indent: {
                                            default: 0,
                                            parseHTML: indentOf,
                                            renderHTML: (attrs) => (attrs.indent > 0 ? { style: `margin-${attrs.indentSide === 'left' ? 'left' : 'right'}: ${attrs.indent * INDENT_STEP}pt` } : {}),
                                        },
                                        indentSide: {
                                            default: 'right',
                                            parseHTML: (el) => (/margin-left\s*:/i.test(inlineStyle(el)) ? 'left' : 'right'),
                                            renderHTML: () => ({}),
                                        },
                                    },
                                }];
                            },
                        }),
                        pick(textStyle, 'TextStyle'),
                        pick(color, 'Color'),
                        pick(fontFamily, 'FontFamily'),
                        pick(textAlign, 'TextAlign').configure({ types: ['heading', 'paragraph'] }),
                        pick(table, 'Table').configure({ resizable: false }),
                        pick(tableRow, 'TableRow'),
                        pick(tableCell, 'TableCell'),
                        pick(tableHeader, 'TableHeader'),
                        Extension.create({
                            name: 'letterFontSize',
                            addGlobalAttributes() {
                                return [{
                                    types: ['textStyle'],
                                    attributes: {
                                        fontSize: {
                                            default: null,
                                            parseHTML: (el) => (FONT_SIZES.test(el.style?.fontSize ?? '') ? el.style.fontSize : null),
                                            renderHTML: (attrs) => (attrs.fontSize ? { style: `font-size: ${attrs.fontSize}` } : {}),
                                        },
                                    },
                                }];
                            },
                        }),
                        Extension.create({
                            name: 'letterCells',
                            addGlobalAttributes() {
                                return [{
                                    types: ['tableCell', 'tableHeader'],
                                    attributes: {
                                        backgroundColor: {
                                            default: null,
                                            parseHTML: (el) => {
                                                const inline = inlineStyle(el).match(/background(?:-color)?\s*:\s*([^;]+)/i);
                                                const value = inline?.[1]?.trim() || el.getAttribute('bgcolor') || '';
                                                return COLOR_VALUE.test(value) ? value : null;
                                            },
                                            renderHTML: (attrs) => (attrs.backgroundColor ? { style: `background-color: ${attrs.backgroundColor}` } : {}),
                                        },
                                        textAlign: {
                                            default: null,
                                            parseHTML: (el) => {
                                                const inline = inlineStyle(el).match(/text-align\s*:\s*([^;]+)/i);
                                                const value = inline?.[1]?.trim().toLowerCase() || el.getAttribute('align')?.toLowerCase() || '';
                                                return ALIGNS.has(value) ? value : null;
                                            },
                                            renderHTML: (attrs) => (attrs.textAlign ? { style: `text-align: ${attrs.textAlign}` } : {}),
                                        },
                                    },
                                }];
                            },
                        }),
                        Extension.create({
                            name: 'letterDir',
                            addGlobalAttributes() {
                                return [{
                                    types: BLOCKS,
                                    attributes: {
                                        dir: {
                                            default: null,
                                            parseHTML: (el) => {
                                                const value = el.getAttribute('dir');
                                                return value && DIRS.has(value.toLowerCase()) ? value.toLowerCase() : null;
                                            },
                                            renderHTML: (attrs) => (attrs.dir ? { dir: attrs.dir } : {}),
                                        },
                                    },
                                }];
                            },
                        }),
                    ],
                    editorProps: {
                        attributes: { dir: DIRS.has(dir) ? dir : 'rtl', lang },
                        transformPastedHTML: (html) => pastePayload(html, this.pasteWarning),
                    },
                    onCreate: () => {
                        this.push();
                    },
                    onUpdate: () => {
                        if (this.frame) return;
                        this.frame = requestAnimationFrame(() => {
                            this.frame = 0;
                            this.push();
                        });
                    },
                    onTransaction: () => {
                        this.tick++;
                    },
                    onBlur: () => {
                        this.flush();
                    },
                });
            } catch {
                this.destroyed = true;
            }
        },

        destroy() {
            if (this.frame) cancelAnimationFrame(this.frame);
            this.frame = 0;
            if (!this.destroyed && editor) this.push();
            this.destroyed = true;
            if (this.fullscreen) document.body.style.overflow = '';
            if (this.flushHandler) window.removeEventListener('letter-editor-flush', this.flushHandler);
            try {
                editor?.destroy();
            } catch {
            }
            editor = null;
        },

        flush() {
            if (!editor || this.destroyed) return;
            if (this.frame) cancelAnimationFrame(this.frame);
            this.frame = 0;
            this.push();
        },

        isActive(name, attrs) {
            void this.tick;
            return editor?.isActive(name, attrs ?? {}) ?? false;
        },

        canUndo() {
            void this.tick;
            return editor?.can().undo() ?? false;
        },

        canRedo() {
            void this.tick;
            return editor?.can().redo() ?? false;
        },

        get over() {
            return this.count > this.maxBytes;
        },

        get undoOff() {
            return !this.canUndo();
        },

        get redoOff() {
            return !this.canRedo();
        },

        get counter() {
            return `${this.count.toLocaleString('fa-IR')} / ${this.maxBytes.toLocaleString('fa-IR')} ${this.unit}`;
        },

        bold() {
            editor?.chain().focus().toggleBold().run();
        },

        italic() {
            editor?.chain().focus().toggleItalic().run();
        },

        underline() {
            editor?.chain().focus().toggleUnderline().run();
        },

        strike() {
            editor?.chain().focus().toggleStrike().run();
        },

        list(kind) {
            if (!editor || !LISTS.has(kind)) return;
            editor.chain().focus()[kind === 'bullet' ? 'toggleBulletList' : 'toggleOrderedList']().run();
        },

        heading(level) {
            if (!editor) return;
            const target = Number(level) || 0;
            if (target === 0) {
                editor.chain().focus().setParagraph().run();
                return;
            }
            if (target < 2 || target > 4) return;
            editor.chain().focus().setHeading({ level: target }).run();
        },

        align(direction) {
            if (!editor || !ALIGNS.has(direction)) return;
            editor.chain().focus().setTextAlign(direction).run();
        },

        color(hex) {
            if (!editor) return;
            const value = String(hex ?? '').trim();
            if (!value) {
                editor.chain().focus().unsetColor().run();
                return;
            }
            if (!HEX.test(value)) return;
            editor.chain().focus().setColor(value).run();
        },

        size(value) {
            if (!editor) return;
            const raw = typeof value === 'number' ? String(value) : String(value ?? '').trim();
            if (!raw) {
                editor.chain().focus().setMark('textStyle', { fontSize: null }).run();
                return;
            }
            const size = BARE_SIZE.test(raw) ? `${raw}px` : raw;
            if (!FONT_SIZES.test(size)) return;
            editor.chain().focus().setMark('textStyle', { fontSize: size }).run();
        },

        fontFamily(name) {
            if (!editor) return;
            const value = String(name ?? '').trim();
            if (!value) {
                editor.chain().focus().unsetFontFamily().run();
                return;
            }
            if (!FONT.test(value)) return;
            editor.chain().focus().setFontFamily(value).run();
        },

        dir(mode) {
            if (!editor || !DIRS.has(mode)) return;
            editor.chain().focus().command(({ tr, state }) => {
                const { from, to } = state.selection;
                state.doc.nodesBetween(from, to, (node, pos) => {
                    if (BLOCKS.includes(node.type.name)) tr.setNodeMarkup(pos, undefined, { ...node.attrs, dir: mode });
                });
                return true;
            }).run();
        },

        table(rows, cols) {
            if (!editor) return;
            editor.chain().focus().insertTable({
                rows: Math.min(30, Math.max(1, Number(rows) || 2)),
                cols: Math.min(20, Math.max(1, Number(cols) || 2)),
                withHeaderRow: true,
            }).run();
        },

        addRow() {
            editor?.chain().focus().addRowAfter().run();
        },

        addCol() {
            editor?.chain().focus().addColumnAfter().run();
        },

        delRow() {
            editor?.chain().focus().deleteRow().run();
        },

        delCol() {
            editor?.chain().focus().deleteColumn().run();
        },

        delTable() {
            editor?.chain().focus().deleteTable().run();
        },

        mergeCells() {
            editor?.chain().focus().mergeCells().run();
        },

        toggleHeaderRow() {
            editor?.chain().focus().toggleHeaderRow().run();
        },

        cellBg(hex) {
            if (!editor || !HEX.test(String(hex ?? ''))) return;
            editor.chain().focus().setCellAttribute('backgroundColor', String(hex)).run();
        },

        superscript() {
            editor?.chain().focus().toggleSuperscript().run();
        },

        subscript() {
            editor?.chain().focus().toggleSubscript().run();
        },

        quote() {
            editor?.chain().focus().toggleBlockquote().run();
        },

        rule() {
            editor?.chain().focus().setHorizontalRule().run();
        },

        pageBreak() {
            editor?.chain().focus().setPageBreak().run();
        },

        halfSpace() {
            editor?.chain().focus().insertContent({ type: 'text', text: ZWNJ }).run();
        },

        clearFormat() {
            editor?.chain().focus().clearNodes().unsetAllMarks().run();
        },

        highlight(hex) {
            if (!editor) return;
            const value = String(hex ?? '').trim();
            if (!value) {
                editor.chain().focus().unsetHighlight().run();
                return;
            }
            if (!HEX_FILL.test(value)) return;
            editor.chain().focus().setHighlight({ color: value }).run();
        },

        indent() {
            if (!editor) return;
            if (editor.isActive('listItem')) {
                editor.chain().focus().sinkListItem('listItem').run();
                return;
            }
            this.shift(1);
        },

        outdent() {
            if (!editor) return;
            if (editor.isActive('listItem')) {
                editor.chain().focus().liftListItem('listItem').run();
                return;
            }
            this.shift(-1);
        },

        shift(step) {
            if (!editor) return;
            editor.chain().focus().command(({ tr, state, view }) => {
                const { from, to } = state.selection;
                state.doc.nodesBetween(from, to, (node, pos) => {
                    if (!INDENTABLE.includes(node.type.name)) return;
                    const level = Math.min(INDENT_MAX, Math.max(0, (Number(node.attrs.indent) || 0) + step));
                    const dom = view.nodeDOM(pos);
                    const ltr = dom instanceof Element && window.getComputedStyle(dom).direction === 'ltr';
                    tr.setNodeMarkup(pos, undefined, { ...node.attrs, indent: level, indentSide: ltr ? 'left' : 'right' });
                });
                return true;
            }).run();
        },

        prepareLink() {
            this.linkUrl = String(editor?.getAttributes('link')?.href ?? '');
        },

        applyLink() {
            if (!editor) return;
            const raw = this.linkUrl.trim();
            if (!raw) {
                this.removeLink();
                return;
            }
            const href = raw.includes(':') ? raw : `https://${raw}`;
            if (!HREF.test(href)) {
                emit('toast', { message: this.linkError, type: 'warning' });
                return;
            }
            if (editor.state.selection.empty && !editor.isActive('link')) {
                editor.chain().focus().insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] }).run();
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
        },

        removeLink() {
            editor?.chain().focus().extendMarkRange('link').unsetLink().run();
        },

        submitLink(event) {
            event?.target?.closest?.('[data-link-form]')?.querySelector('[data-link-apply]')?.click();
        },

        insertToken(text) {
            const token = String(text ?? '').replace(/[<>]/g, '').slice(0, 60);
            if (!editor || !token) return;
            editor.chain().focus().insertContent({ type: 'text', text: token }).run();
        },

        undo() {
            editor?.chain().focus().undo().run();
        },

        redo() {
            editor?.chain().focus().redo().run();
        },

        clear() {
            editor?.chain().focus().clearContent().run();
        },
    };
}

export function correspondenceComposer() {
    let observer = null;

    return {
        ...draftStore(DRAFT_ID),
        ...dialogs,
        n: 0,

        get over() {
            void this.n;
            return Array.from(this.$el.querySelectorAll('[data-letter-editor]')).some((el) => window.Alpine?.$data(el)?.over === true);
        },

        get blocked() {
            void this.n;
            return this.$el.dataset.locked === '1' || this.over;
        },

        init() {
            observer = new MutationObserver(() => {
                this.n++;
            });
            observer.observe(this.$el, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-locked'] });
        },

        destroy() {
            observer?.disconnect();
            observer = null;
        },

        remembers(el) {
            return el?.dataset?.fresh === '1' && el.dataset.private !== '1';
        },

        initSubject(el) {
            if (!this.remembers(el)) {
                this.clearDraft();
                return;
            }
            const saved = el.value ? '' : this.draft();
            if (!saved) return;
            el.value = saved;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        },

        rememberSubject(el) {
            if (this.remembers(el)) this.rememberDraft(el.value);
            else this.clearDraft();
        },

        classify(value) {
            if (value !== this.$el.dataset.normal) this.clearDraft();
        },

        generate() {
            flushEditors();
            return this.$wire?.generate?.();
        },

        save() {
            flushEditors();
            const wire = this.$wire;
            const run = Promise.resolve(wire?.$set?.('form.mode', 'simple', false)).then(() => wire?.save?.());
            return afterSuccess(run, () => this.clearDraft());
        },

        openShare() {
            flushEditors();
            this.openModal('correspondence-share');
        },

        share() {
            flushEditors();
            return afterSuccess(this.$wire?.shareLetter?.(), () => emit('close-modal'));
        },
    };
}

export function jalaliField() {
    return {
        field: '',
        y: '',
        m: '',
        d: '',

        init() {
            this.field = String(this.$el?.dataset?.field ?? '');
            this.load();
        },

        load() {
            [this.y, this.m, this.d] = parseJalali(this.$wire?.$get?.(`form.${this.field}`));
        },

        push() {
            this.$wire?.$set?.(`form.${this.field}`, formatJalali(this.y, this.m, this.d), false);
        },
    };
}

export function correspondenceViewer() {
    return {
        ...dialogs,
        showDiff: false,

        toggleDiff() {
            this.showDiff = !this.showDiff;
        },

        openSign() {
            this.$wire?.$set?.('signCode', '', false);
            this.$wire?.$set?.('signTarget', 0, false);
            this.openModal('correspondence-sign');
        },

        openReject() {
            this.openModal('correspondence-reject');
        },

        runAndClose(el) {
            const method = el?.dataset?.method;
            if (!ROUTE_CALLS.has(method)) return undefined;
            return afterSuccess(this.$wire?.[method]?.(), () => emit('close-modal'));
        },
    };
}

export function correspondenceSignGuard() {
    return {
        init() {
            this.$watch('show', (open) => {
                if (open) return;
                this.$wire?.$set?.('signCode', '', false);
                this.$wire?.$set?.('signTarget', 0, false);
            });
        },
    };
}

export function correspondenceList() {
    return {
        ...dialogs,

        openBatch(id) {
            this.openModal(`correspondence-batch-${Number(id)}`);
        },

        useTemplate(id) {
            return afterSuccess(this.$wire?.useTemplate?.(Number(id)), () => emit('close-modal'));
        },
    };
}

export function correspondenceLegend() {
    return {
        tab: 'route',

        select(id) {
            this.tab = String(id);
        },

        isTab(id) {
            return this.tab === id;
        },
    };
}

export function letterPreviewScale() {
    return {
        scale: 1,
        observer: null,
        resizeHandler: null,

        paperEl() {
            return this.$refs.paper ?? this.$el?.firstElementChild ?? null;
        },

        init() {
            this.apply();
            if (typeof ResizeObserver === 'function') {
                this.observer = new ResizeObserver(() => this.apply());
                if (this.$el) this.observer.observe(this.$el);
                const paper = this.paperEl();
                if (paper) this.observer.observe(paper);
            } else {
                this.resizeHandler = () => this.apply();
                window.addEventListener('resize', this.resizeHandler);
            }
        },

        destroy() {
            this.observer?.disconnect();
            if (this.resizeHandler) window.removeEventListener('resize', this.resizeHandler);
        },

        apply() {
            const paper = this.paperEl();
            if (!paper || !this.$el || typeof window.getComputedStyle !== 'function') return;
            const style = window.getComputedStyle(this.$el);
            const avail = this.$el.clientWidth - (parseFloat(style.paddingLeft) || 0) - (parseFloat(style.paddingRight) || 0);
            const width = paper.offsetWidth;
            if (!avail || !width) {
                this.scale = 1;
                return;
            }
            const rtl = (style.direction || window.getComputedStyle(paper).direction) === 'rtl';
            const scale = Math.min(1, avail / width);
            this.scale = scale;
            paper.style.transformOrigin = `top ${rtl ? 'right' : 'left'}`;
            paper.style.transform = scale < 1 ? `scale(${scale})` : '';
            paper.style.marginBottom = scale < 1 ? `${Math.round(paper.offsetHeight * (scale - 1))}px` : '';
        },
    };
}
