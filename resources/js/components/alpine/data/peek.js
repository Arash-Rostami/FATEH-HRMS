const OPEN_DELAY = 450;
const SWITCH_DELAY = 80;
const CLOSE_DELAY = 120;
const GAP = 12;
const EDGE = 8;
const CARD_WIDTH = 336;
const CARD_HEIGHT = 260;

const SNAP_MAX_CHARS = 600000;
const LIST_LIMIT = 40;
const LIST_KEEP = 24;
const SNAP_REFRESH_MS = 10000;
const SLICE_NODES = 500;
const DROP_TAGS = new Set(['SCRIPT', 'TEMPLATE', 'NOSCRIPT', 'IFRAME', 'VIDEO', 'AUDIO', 'CANVAS']);
const LIVE_ATTRIBUTE = /^(?:id$|wire:|x-|ax-|[:@]|on[a-z]+$)/;

const verdicts = new Map();

function verdict(name) {
    let kind = verdicts.get(name);

    if (kind === undefined) {
        kind = name === 'x-cloak' ? 0 : (name.startsWith('wire:loading') || name === 'wire:offline') ? 2 : LIVE_ATTRIBUTE.test(name) ? 1 : 0;
        verdicts.set(name, kind);
    }

    return kind;
}

const PERSIST_MAX_CHARS = 120000;

const snaps = Object.create(null);

let stylesheetLinks = '';

const idle = (fn, timeout, wait = 1000) => (typeof requestIdleCallback === 'function' ? requestIdleCallback(fn, timeout ? {timeout} : undefined) : setTimeout(fn, wait));

const storeKey = (tab) => 'peek:' + (document.documentElement.dataset.uid || '') + ':' + tab;

function persist(tab, html) {
    if (html.length > PERSIST_MAX_CHARS) return;

    idle(() => {
        try {
            sessionStorage.setItem(storeKey(tab), html);
        } catch {}
    }, 2000);
}

function recall(tab) {
    try {
        const html = sessionStorage.getItem(storeKey(tab));

        return html && html[0] === '<' ? html : null;
    } catch {
        return null;
    }
}

const viewportWidth = () => document.documentElement.clientWidth || window.innerWidth;

const isDesktop = () => typeof window.matchMedia !== 'function' || window.matchMedia('(min-width: 1024px)').matches;

const VOID_TAGS = new Set(['AREA', 'BASE', 'BR', 'COL', 'EMBED', 'HR', 'IMG', 'INPUT', 'LINK', 'META', 'SOURCE', 'TRACK', 'WBR']);
const TEXT_ESC = /[&<>]/g;
const ATTR_ESC = /[&"]/g;
const ENTITIES = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'};

const entity = (char) => ENTITIES[char];

let out = '';
let walked = 0;
let job = null;

function* writeInert(el) {
    const attrs = el.attributes;
    for (let i = 0; i < attrs.length; i++) {
        if (verdict(attrs[i].name) === 2) return false;
    }

    const isImage = el.tagName === 'IMG';
    const tag = el.localName;
    out += '<' + tag + (isImage ? ' loading="lazy" decoding="async"' : '');

    for (let i = 0; i < attrs.length; i++) {
        const {name, value} = attrs[i];
        if (isImage && (name === 'loading' || name === 'decoding')) continue;
        if (verdict(name) === 0) out += ' ' + name + '="' + value.replace(ATTR_ESC, entity) + '"';
    }

    out += '>';
    if (VOID_TAGS.has(el.tagName)) return true;

    const raw = el.tagName === 'STYLE';
    const cap = el.childElementCount > LIST_LIMIT ? LIST_KEEP : Infinity;
    let kept = 0;

    for (let child = el.firstChild; child && kept < cap && out.length <= SNAP_MAX_CHARS; child = child.nextSibling) {
        if (child.nodeType === Node.TEXT_NODE) {
            out += raw ? child.data : child.data.replace(TEXT_ESC, entity);
        } else if (child.nodeType === Node.ELEMENT_NODE && !DROP_TAGS.has(child.tagName) && child.style?.display !== 'none') {
            if (++walked % SLICE_NODES === 0) yield child;
            if (yield* writeInert(child)) kept++;
        }
    }

    out += '</' + tag + '>';

    return true;
}

function findTabRoot() {
    const main = document.querySelector('main');
    if (!main) return null;

    const walker = document.createTreeWalker(main, NodeFilter.SHOW_ELEMENT);
    while (walker.nextNode()) {
        if (walker.currentNode.hasAttribute('wire:id')) return walker.currentNode;
    }

    return null;
}

function pageStylesheetLinks() {
    if (stylesheetLinks) return stylesheetLinks;

    const origin = window.location.origin;
    let links = '';

    for (const sheet of document.styleSheets) {
        if (sheet.href && sheet.href.startsWith(origin)) links += '<link rel="stylesheet" href="' + sheet.href + '">';
    }

    for (const style of document.head.querySelectorAll('style')) links += style.outerHTML;

    stylesheetLinks = links;

    return links;
}

export function buildFrameDocument(html) {
    const root = document.documentElement;

    return '<!doctype html><html class="' + (root.getAttribute('class') || '') + '" dir="rtl"'
        + ' data-theme="' + (root.getAttribute('data-theme') || '') + '"><head><meta charset="utf-8">' + pageStylesheetLinks()
        + '<style>*{animation:none!important;transition:none!important}</style></head>'
        + '<body style="margin:0;height:100vh;display:flex;flex-direction:column;overflow:hidden;background:var(--md-sys-color-background);color:var(--md-sys-color-on-surface)">'
        + html + '</body></html>';
}

export default function peek() {
    let timer = null;
    let anchor = null;
    let card = null;
    let shown = null;

    return {
        open: false,
        frame: null,
        label: '',
        icon: 'tab',
        pos: {top: 0, right: 0},

        destroy() {
            clearTimeout(timer);
            job = null;
        },

        capture() {
            if (document.hidden || !isDesktop()) return;

            const tab = this.$root.dataset.active;
            if (!tab || job?.tab === tab) return;

            const last = snaps[tab];
            if (last && Date.now() - last.at < SNAP_REFRESH_MS) return;

            const run = job = {tab};
            let host = null;
            let walk = null;
            let node = null;

            const step = () => {
                if (job !== run) return;

                try {
                    if (!walk) {
                        host = findTabRoot();
                        out = '';
                        walked = 0;
                        walk = host && writeInert(host);
                    }

                    const live = walk && !document.hidden && this.$root.dataset.active === tab && host.isConnected && (!node || host.contains(node));
                    const turn = live && walk.next();

                    if (turn && !turn.done) {
                        node = turn.value;
                        idle(step, 0, 0);
                        return;
                    }

                    const html = out;
                    out = '';
                    job = null;
                    if (!turn) return;

                    const prev = snaps[tab];

                    if (html.length > SNAP_MAX_CHARS) {
                        snaps[tab] = {html: prev?.html ?? recall(tab), at: Date.now()};
                        return;
                    }

                    if (html !== prev?.html) persist(tab, html);
                    snaps[tab] = {html, at: Date.now()};
                    if (this.open && shown === tab && !this.frame) this.frame = buildFrameDocument(html);
                } catch {
                    out = '';
                    job = null;
                }
            };

            idle(step, 0, 0);
        },

        frameFor(tab) {
            const snap = snaps[tab] ?? (snaps[tab] = {html: recall(tab), at: 0});

            return snap.html ? buildFrameDocument(snap.html) : null;
        },

        place() {
            if (!anchor) return;

            if (!card?.isConnected) card = document.querySelector('[data-peek-card]');

            const height = card?.offsetHeight || CARD_HEIGHT;
            const width = card?.offsetWidth || CARD_WIDTH;
            const viewport = viewportWidth();
            const centered = anchor.top + anchor.height / 2 - height / 2;
            const top = Math.max(EDGE, Math.min(centered, window.innerHeight - height - EDGE));
            const right = Math.max(EDGE, Math.min(viewport - anchor.left + GAP, viewport - width - EDGE));

            if (this.pos.top !== top || this.pos.right !== right) this.pos = {top, right};
        },

        show(tab, el) {
            clearTimeout(timer);
            if (tab === this.$root.dataset.active) this.capture();
            timer = setTimeout(() => this.load(tab, el), this.open ? SWITCH_DELAY : OPEN_DELAY);
        },

        hide() {
            clearTimeout(timer);
            if (this.open) timer = setTimeout(() => (this.open = false), CLOSE_DELAY);
        },

        keep() {
            clearTimeout(timer);
        },

        load(tab, el) {
            const box = el.getBoundingClientRect();
            anchor = {top: box.top, left: box.left, height: box.height};
            this.label = el.getAttribute('aria-label') || '';
            this.icon = el.querySelector('.material-symbols-rounded')?.textContent.trim() || 'tab';
            shown = tab;
            this.frame = this.frameFor(tab);
            this.open = true;
            this.place();
            this.$nextTick(() => this.place());
        },
    };
}
