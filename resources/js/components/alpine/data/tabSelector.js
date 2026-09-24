const KEBAB_RESERVE = 58;

export default function tabSelector() {
    return {
        navigating: false,
        moreOpen: false,
        pos: {},
        hidden: [],
        _ro: null,
        _mo: null,
        _raf: null,
        _onResize: null,
        _widths: {},
        _gap: null,

        _schedule() {
            if (this._raf !== null) return;
            this._raf = requestAnimationFrame(() => {
                this._raf = null;
                this._fit();
            });
        },

        _setPills(hidden, pills) {
            const hiddenSet = new Set(hidden);
            const len = pills.length;

            for (let i = 0; i < len; i++) {
                const p = pills[i];
                const shouldHide = hiddenSet.has(p.dataset.tabPill);
                if (shouldHide !== (p.style.display === 'none')) {
                    p.style.display = shouldHide ? 'none' : '';
                }
            }
        },

        _apply(hidden) {
            const cur = this.hidden;
            const len = hidden.length;

            if (len !== cur.length) {
                this.hidden = hidden;
                return;
            }

            for (let i = 0; i < len; i++) {
                if (hidden[i] !== cur[i]) {
                    this.hidden = hidden;
                    return;
                }
            }
        },

        _soleChild(row, bar) {
            const children = row.children;
            const len = children.length;

            for (let i = 0; i < len; i++) {
                const c = children[i];
                if (c !== bar && c.offsetParent !== null) return false;
            }
            return true;
        },

        _avail(bar) {
            let row = bar.parentElement;
            if (row === null) return bar.clientWidth;

            while (
                row !== null &&
                row !== document.body &&
                (this._soleChild(row, bar) || getComputedStyle(row).display === 'contents')
                ) {
                row = row.parentElement;
            }

            if (row === null) return bar.clientWidth;

            const cs = getComputedStyle(row);
            const flexRow = (cs.display === 'flex' || cs.display === 'inline-flex') && !cs.flexDirection.startsWith('column');

            let cap = row.clientWidth - (parseFloat(cs.paddingLeft) || 0) - (parseFloat(cs.paddingRight) || 0);

            if (flexRow) {
                if (this._gap === null) {
                    this._gap = parseFloat(cs.columnGap) || 0;
                }

                const children = row.children;
                const len = children.length;
                for (let i = 0; i < len; i++) {
                    const c = children[i];
                    if (c !== bar && c.offsetParent !== null) {
                        cap -= c.offsetWidth + this._gap;
                    }
                }
            }
            return cap;
        },

        _fit() {
            const bar = this.$el;
            if (bar === null || bar === undefined || bar.clientWidth === 0) return;

            const pillsNodeList = bar.querySelectorAll('[data-tab-pill]');
            const len = pillsNodeList.length;

            if (len === 0) {
                this._apply([]);
                return;
            }

            const pills = new Array(len);
            for (let i = 0; i < len; i++) {
                pills[i] = pillsNodeList[i];
            }

            const avail = this._avail(bar);
            if (avail <= 0) return;

            let total = 0;
            const widths = new Array(len);
            let activeIdx = -1;

            for (let i = 0; i < len; i++) {
                const p = pills[i];
                const key = p.dataset.tabPill;
                let w;

                    if (p.style.display !== 'none') {
                    const offset = p.offsetWidth;
                    if (offset > 0) {
                        this._widths[key] = offset;
                        w = offset;
                    } else {
                        w = this._widths[key] || 0;
                    }
                } else {
                    w = this._widths[key] || 0;
                }

                widths[i] = w;
                total += w;

                if (p.hasAttribute('data-active')) {
                    activeIdx = i;
                }
            }

            if (total <= avail) {
                this._setPills([], pills);
                this._apply([]);
                return;
            }

            const budget = avail - KEBAB_RESERVE;
            let used = 0;
            const hidden = [];

            for (let i = 0; i < len; i++) {
                if (used + widths[i] > budget && i !== activeIdx && len - hidden.length > 1) {
                    hidden.push(pills[i].dataset.tabPill);
                    continue;
                }
                used += widths[i];
            }

            while (used > budget && len - hidden.length > 1) {
                let v = len - 1;
                while (v >= 0) {
                    if (v !== activeIdx && !hidden.includes(pills[v].dataset.tabPill)) {
                        break;
                    }
                    v--;
                }

                if (v < 0) break;

                hidden.push(pills[v].dataset.tabPill);
                used -= widths[v];
            }

            this._setPills(hidden, pills);
            this._apply(hidden);
        },

        init() {
            this._onResize = () => this._schedule();
            window.addEventListener('resize', this._onResize);

            this._ro = new ResizeObserver(() => this._schedule());
            this._ro.observe(this.$el);

            this._mo = new MutationObserver(() => this._schedule());
            this._mo.observe(this.$el, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style', 'data-active'],
            });

            this._fit();
        },

        destroy() {
            if (this._onResize !== null) window.removeEventListener('resize', this._onResize);
            if (this._ro !== null) this._ro.disconnect();
            if (this._mo !== null) this._mo.disconnect();
            if (this._raf !== null) cancelAnimationFrame(this._raf);
        },
    };
}
