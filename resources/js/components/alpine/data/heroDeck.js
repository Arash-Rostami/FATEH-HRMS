import maximizeMixin from "../mixins/maximize.js";
import tableToolsMixin from "../mixins/tableTools.js";
import { mountFrame, drop } from "../../../core/workspace.js";

const STORAGE_KEY = 'hero_gadgets';
const MAX_PANES = 6;
const NEW_PANE = '__new__';
const MAX_WIDGET_CLOSE_MS = 350;
const ESCAPE_REGEX = /[.*+?^${}()|[\]\\]/g;
const GROUPS = ['module', 'program', 'sso'];
const ARROWS = ['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp'];
const TILE = '[data-gadget-tile]';
const SEARCH = '[data-gadget-search]';
const DIALOG = '[role="dialog"]';

const words = (query) => String(query ?? '').trim().toLowerCase().split(/\s+/).filter(Boolean);

export function matchesQuery(haystack, query) {
    const text = String(haystack ?? '').toLowerCase();

    return words(query).every((word) => text.includes(word));
}

export function splitMatches(text, query) {
    const source = String(text ?? '');
    const needles = words(query);

    if (needles.length === 0) return [{ t: source, hit: false }];

    const pattern = new RegExp(`(${needles.map((word) => word.replace(ESCAPE_REGEX, '\\$&')).join('|')})`, 'gi');

    return source.split(pattern).map((t, i) => ({ t, hit: i % 2 === 1 })).filter((part) => part.t !== '');
}

const visibleTiles = (root) => [...root.querySelectorAll(TILE)].filter((tile) => tile.offsetParent !== null);

function isFrameSrc(src) {
    if (typeof src !== 'string' || src === '') return false;
    try {
        return /^https?:$/.test(new URL(src, window.location.href).protocol);
    } catch (e) {
        return false;
    }
}

function readDeck(saved) {
    const source = saved && typeof saved === 'object' ? saved : {};
    const stored = source.modules && typeof source.modules === 'object' ? source.modules : {};
    const panes = [];
    const modules = {};

    for (const id of Array.isArray(source.panes) ? source.panes : []) {
        if (panes.length >= MAX_PANES) break;
        if (typeof id !== 'string' || panes.includes(id) || !Object.hasOwn(stored, id)) continue;
        const mod = stored[id];
        if (!mod || !isFrameSrc(mod.src)) continue;
        panes.push(id);
        modules[id] = { title: String(mod.title ?? ''), icon: String(mod.icon ?? ''), src: mod.src };
    }

    return { panes, modules, slide: panes.includes(source.slide) ? source.slide : 0 };
}

export function launchpadPage() {
    return {
        ...tableToolsMixin('launchpad'),
        ...maximizeMixin(),

        init() {
            this.initMaximize();
            this.initTableTools();
        },

        destroy() {
            this.destroyTableTools();
        },

        limit(event) {
            const input = event.target;
            if (input.files && input.files.length > Number(input.dataset.max)) {
                input.value = '';
                event.stopPropagation();
                this.$dispatch('toast', { message: input.dataset.over, type: 'warning' });
            }
        },
    };
}

export default function heroDeck() {
    return {
        ...maximizeMixin('hero'),

        slide: 0,
        panes: [],
        modules: {},
        loaded: {},
        frameReady: {},
        maximizedPane: null,
        pickerFor: null,
        deckH: 0,
        rows: [],
        q: '',
        tab: '',

        init() {
            try {
                const deck = readDeck(JSON.parse(localStorage.getItem(STORAGE_KEY)));

                this.panes = deck.panes;
                this.modules = deck.modules;

                if (deck.slide !== 0) {
                    this.slide = deck.slide;
                    this.loaded[deck.slide] = true;
                }
            } catch (e) {
                try { localStorage.removeItem(STORAGE_KEY); } catch (e2) {}
            }

            this.$watch('slide', (id) => {
                if (id !== 0 && this.modules[id] !== undefined) {
                    this.loaded[id] = true;
                }
                this.persist();
            });

            this.$watch('pickerFor', (target) => {
                if (target === null) {
                    this.q = '';
                    this.tab = '';
                    return;
                }
                this.settle();
                if (window.matchMedia?.('(pointer: fine)').matches) {
                    this.$nextTick(() => document.querySelector(SEARCH)?.focus());
                }
            });

            this.$watch('q', () => this.settle());

            this.storageAbort = new AbortController();
            window.addEventListener('storage', (e) => {
                if (e.key !== STORAGE_KEY) return;
                try {
                    const saved = JSON.parse(e.newValue);
                    if (!saved || typeof saved !== 'object') return;
                    const deck = readDeck(saved);
                    this.panes = deck.panes;
                    this.modules = deck.modules;
                    if (this.slide !== 0 && !this.panes.includes(this.slide)) this.slide = 0;
                    if (this.maximizedPane !== null && !this.panes.includes(this.maximizedPane)) this.releaseMaximize();
                } catch (err) {}
            }, { signal: this.storageAbort.signal });

            this.initMaximize();
        },

        destroy() {
            this.storageAbort?.abort();
            if (this.maximizedPane !== null) this.applyMaximize(false);
        },

        releaseMaximize() {
            this.maximizedPane = null;
            this._persistMaximize(null);
            this.applyMaximize(false);
        },

        goto(target) {
            this.slide = target;
        },

        canAddPane() {
            return this.panes.length < MAX_PANES;
        },

        openAddPicker() {
            if (this.canAddPane()) this.pickerFor = NEW_PANE;
        },

        usedSrc(src) {
            return this.panes.some((p) => p !== this.pickerFor && this.modules[p]?.src === src);
        },

        show(index) {
            const row = this.rows[index];

            return row.g === this.tab && matchesQuery(row.f, this.q);
        },

        count(group) {
            return this.rows.filter((row) => row.g === group && matchesQuery(row.f, this.q)).length;
        },

        settle() {
            if (this.count(this.tab) > 0) return;

            this.tab = GROUPS.find((group) => this.count(group) > 0) ?? this.tab;
        },

        parts(text) {
            return splitMatches(text, this.q);
        },

        onSearch(event) {
            if (event.isComposing || (event.key !== 'ArrowDown' && event.key !== 'Enter')) return;

            const root = event.currentTarget.closest(DIALOG);
            const first = root && visibleTiles(root).find((tile) => !tile.disabled);

            if (!first) return;

            event.preventDefault();
            if (event.key === 'Enter') first.click();
            else first.focus();
        },

        onGrid(event) {
            if (!ARROWS.includes(event.key)) return;

            const grid = event.currentTarget;
            const tiles = visibleTiles(grid);
            const at = tiles.indexOf(event.target.closest(TILE));

            if (at < 0) return;

            event.preventDefault();

            const style = getComputedStyle(grid);
            const rtl = style.direction === 'rtl';
            const cols = style.gridTemplateColumns.split(' ').length;
            const step = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1, ArrowDown: cols, ArrowUp: -cols }[event.key];

            let next = at + step;
            while (tiles[next]?.disabled) next += step;

            if (tiles[next]) tiles[next].focus();
            else if (event.key === 'ArrowUp') grid.closest(DIALOG)?.querySelector(SEARCH)?.focus();
        },

        pick(mod) {
            const picker = this.pickerFor;
            if (picker === null) return;

            const isNew = picker === NEW_PANE;
            const dup = this.panes.find((p) => p !== picker && this.modules[p]?.src === mod.src);

            if (dup) {
                this.slide = dup;
                this.pickerFor = null;
                return;
            }

            const id = isNew ? 'p_' + Date.now() : picker;

            if (isNew) {
                this.panes.push(id);
            }

            this.modules[id] = { title: mod.title, icon: mod.icon, src: mod.src };
            this.loaded[id] = true;
            this.frameReady[id] = false;

            if (isNew) {
                this.slide = id;
            }

            this.pickerFor = null;
            this.persist();
        },

        remove(pane) {
            const arr = this.panes;
            const idx = arr.indexOf(pane);

            if (idx !== -1) {
                arr.splice(idx, 1);
            }

            Reflect.deleteProperty(this.modules, pane);
            Reflect.deleteProperty(this.loaded, pane);
            Reflect.deleteProperty(this.frameReady, pane);
            drop(pane);

            if (this.maximizedPane === pane) this.releaseMaximize();

            if (this.slide === pane) this.slide = 0;

            this.persist();
        },

        mountFrame(pane, slot) {
            if (this.modules[pane]) mountFrame(pane, slot, this.modules[pane], () => this.onFrameLoad(pane));
        },

        onFrameLoad(pane) {
            this.frameReady[pane] = true;
        },

        closeOverlays() {
            if (!this.pickerFor) return false;
            this.pickerFor = null;
            return true;
        },

        toggleMaximize(pane) {
            if (pane === null) pane = this.maximizedPane;
            if (this.maximizedPane === pane) {
                this.maxLeaving = true;
                this._persistMaximize(null);
                this.applyMaximize(false);
                setTimeout(() => {
                    this.maximizedPane = null;
                    this.maxLeaving = false;
                }, MAX_WIDGET_CLOSE_MS);
                return;
            }

            this.maximizedPane = pane;
            this._persistMaximize(pane);
            this.applyMaximize(true);
        },

        restoreMaximize(value) {
            if (!this.panes.includes(value)) {
                this._persistMaximize(null);
                return;
            }
            this.slide = value;
            this.maximizedPane = value;
            this.applyMaximize(true);
        },

        persist() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify({
                    panes: this.panes,
                    modules: this.modules,
                    slide: this.slide
                }));
            } catch (e) {}
        }
    };
}
