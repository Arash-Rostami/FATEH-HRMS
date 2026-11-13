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
const DROP_TAGS = new Set(['SCRIPT', 'TEMPLATE', 'NOSCRIPT', 'IFRAME', 'VIDEO', 'AUDIO', 'CANVAS']);
const KEEP_ATTRIBUTES = new Set(['x-cloak']);
const LIVE_ATTRIBUTE = /^(?:id$|wire:|x-|ax-|[:@]|on[a-z]+$)/;

const snaps = Object.create(null);

let stylesheetLinks = '';

const viewportWidth = () => document.documentElement.clientWidth || window.innerWidth;

const isDesktop = () => typeof window.matchMedia !== 'function' || window.matchMedia('(min-width: 1024px)').matches;

function cleanAttributes(el) {
    const attrs = el.attributes;
    for (let i = attrs.length - 1; i >= 0; i--) {
        const name = attrs[i].name;
        if (name.startsWith('wire:loading') || name === 'wire:offline') el.style.display = 'none';
        if (!KEEP_ATTRIBUTES.has(name) && LIVE_ATTRIBUTE.test(name)) el.removeAttribute(name);
    }
}

function inertCopy(src) {
    const copy = src.cloneNode(false);
    cleanAttributes(copy);
    if (copy.tagName === 'IMG') copy.loading = 'lazy';

    const crowded = src.children.length > LIST_LIMIT;
    let kept = 0;

    for (let child = src.firstChild; child; child = child.nextSibling) {
        if (crowded && kept >= LIST_KEEP) break;

        if (child.nodeType === Node.TEXT_NODE) {
            copy.appendChild(child.cloneNode(false));
        } else if (child.nodeType === Node.ELEMENT_NODE && !DROP_TAGS.has(child.tagName) && child.style.display !== 'none') {
            copy.appendChild(inertCopy(child));
            kept++;
        }
    }

    return copy;
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

    return {
        open: false,
        frame: null,
        label: '',
        icon: 'tab',
        pos: {top: 0, right: 0},

        destroy() {
            clearTimeout(timer);
        },

        capture() {
            if (document.hidden || !isDesktop()) return;

            const tab = this.$root.dataset.active;
            if (!tab) return;

            const now = Date.now();
            const last = snaps[tab];
            if (last && now - last.at < SNAP_REFRESH_MS) return;

            const host = findTabRoot();
            if (!host) return;

            const html = inertCopy(host).outerHTML;
            snaps[tab] = {html: html.length > SNAP_MAX_CHARS ? null : html, at: now};
        },

        frameFor(tab) {
            const snap = snaps[tab];

            return snap && snap.html ? buildFrameDocument(snap.html) : null;
        },

        place() {
            if (!anchor) return;

            const card = document.querySelector('[data-peek-card]');
            const height = card && card.offsetHeight ? card.offsetHeight : CARD_HEIGHT;
            const width = card && card.offsetWidth ? card.offsetWidth : CARD_WIDTH;
            const viewport = viewportWidth();
            const centered = anchor.top + anchor.height / 2 - height / 2;
            const top = Math.max(EDGE, Math.min(centered, window.innerHeight - height - EDGE));
            const right = Math.max(EDGE, Math.min(viewport - anchor.left + GAP, viewport - width - EDGE));

            if (this.pos.top !== top || this.pos.right !== right) this.pos = {top, right};
        },

        show(tab, el) {
            clearTimeout(timer);
            timer = setTimeout(() => this.load(tab, el), this.open ? SWITCH_DELAY : OPEN_DELAY);
        },

        hide() {
            clearTimeout(timer);
            timer = setTimeout(() => {
                this.open = false;
            }, CLOSE_DELAY);
        },

        keep() {
            clearTimeout(timer);
        },

        load(tab, el) {
            const box = el.getBoundingClientRect();
            anchor = {top: box.top, left: box.left, height: box.height};
            this.label = el.getAttribute('aria-label') || '';
            this.icon = el.querySelector('.material-symbols-rounded')?.textContent.trim() || 'tab';
            if (tab === this.$root.dataset.active) this.capture();
            this.frame = this.frameFor(tab);
            this.open = true;
            this.place();
            this.$nextTick(() => this.place());
        },
    };
}
