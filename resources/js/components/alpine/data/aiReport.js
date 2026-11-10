import clipboardMixin from "../mixins/clipboard.js";
import tableToolsMixin from "../mixins/tableTools.js";
import chartBuilderMixin from "../mixins/chartBuilder.js";

const DOC_SELECTOR = '.ai-report-doc';
const SKIP_HIDDEN = 'print:hidden';
const SKIP_DISCLAIMER = 'ai-report-disclaimer';
const HEADINGS = new Set(['H1', 'H2', 'H3']);
const GRID_CLASS = 'grid';
const BULLET = '• ';
const ON = '1';
const PRINT = ['beforeprint', 'afterprint'];
const PALETTE = ['primary', 'secondary', 'tertiary', 'error', 'on-surface-variant', 'outline'].map((n) => `var(--md-sys-color-${n})`);

const toNumber = (v) => {
    if (v == null || v === '') return null;
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
};

const DRAFT_KEY = 'ai-report:draft:';
const draftKey = () => DRAFT_KEY + (document.documentElement.dataset.uid ?? '');
const draftRead = () => {
    try {
        return window.sessionStorage.getItem(draftKey()) ?? '';
    } catch {
        return '';
    }
};
const draftWrite = (value) => {
    try {
        if (value) window.sessionStorage.setItem(draftKey(), value);
        else window.sessionStorage.removeItem(draftKey());
    } catch {
    }
};

const formatters = {};
const formatNumber = (n, fa) => {
    try {
        const locale = fa ? 'fa-IR' : 'en-US';
        return (formatters[locale] ??= new Intl.NumberFormat(locale)).format(Number(n));
    } catch {
        return String(n);
    }
};

let faPlain;
const faDigits = (n) => (faPlain ??= new Intl.NumberFormat('fa-IR', { useGrouping: false })).format(n);

const parseChart = (json) => {
    const raw = JSON.parse(json || 'null');
    const kind = raw?.kind === 'pie' || raw?.kind === 'bar' ? raw.kind : null;
    if (!kind || !Array.isArray(raw.labels) || !raw.labels.length) return null;

    const pie = kind === 'pie';
    const labels = raw.labels.map((l) => (l == null ? '' : String(l)));
    const series = [];
    for (const s of Array.isArray(raw.series) ? raw.series : []) {
        if (!s || typeof s !== 'object') continue;
        const vals = Array.isArray(s.values) ? s.values : [];
        series.push({ name: String(s.name ?? ''), values: labels.map((_, i) => toNumber(vals[i])) });
        if (pie) break;
    }
    const drawable = series.some((s) => s.values.some((v) => v !== null && (!pie || v > 0)));
    return drawable ? { kind, labels, series } : null;
};

const textOf = (el) => el.textContent.trim();

const isSkipped = (el) => {
    const { tagName, classList } = el;
    return (tagName === 'SPAN' && !el.querySelector('bdi'))
        || el.getAttribute('aria-hidden') === 'true'
        || classList.contains(SKIP_HIDDEN)
        || classList.contains(SKIP_DISCLAIMER);
};

const pushText = (out, el) => {
    const t = textOf(el);
    if (t) out.push(t);
};

const pushTable = (out, el) => {
    for (const tr of el.querySelectorAll('tr')) out.push(Array.from(tr.children, textOf).join('\t'));
};

const pushList = (out, el) => {
    for (const li of el.children) {
        const t = textOf(li);
        if (t) out.push(BULLET + t);
    }
};

const pushTiles = (out, el) => {
    for (const tile of el.children) {
        let head = '';
        let count = 0;
        const rest = [];
        for (const c of tile.children) {
            if (c.tagName !== 'P') continue;
            const t = textOf(c);
            if (count++ === 0) head = t;
            else if (t) rest.push(t);
        }
        if (count > 1) out.push(`${head}: ${rest.join(' — ')}`);
    }
};

const walk = (out, parent) => {
    for (const el of parent.children) {
        if (isSkipped(el)) continue;
        const tag = el.tagName;
        if (tag === 'TABLE') pushTable(out, el);
        else if (HEADINGS.has(tag)) pushText(out, el);
        else if (tag === 'UL') pushList(out, el);
        else if (el.classList.contains(GRID_CLASS)) pushTiles(out, el);
        else if (tag === 'P' || tag === 'SPAN') pushText(out, el);
        else walk(out, el);
    }
};

export const collectReportText = (root) => {
    const out = [];
    walk(out, root);
    return out.join('\n');
};

export function aiReportComposer() {
    return {
        open: false,
        uploading: false,
        sending: false,
        n: 0,
        max: 0,

        init() {
            const data = this.$el.dataset;
            this.open = data.open === ON;
            this.max = parseInt(data.max, 10) || 0;
            const textarea = this.$el.querySelector('textarea');
            this.n = textarea?.value.length ?? 0;
            if (textarea && !textarea.value) {
                const draft = draftRead();
                if (draft) {
                    textarea.value = draft;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    this.n = draft.length;
                    this.$wire?.$set?.('form.prompt', draft, false);
                }
            }
            this.unwatch = this.$wire?.$watch('form.prompt', (v) => {
                this.n = (v ?? '').length;
                if (!v) draftWrite('');
            });
        },

        destroy() {
            if (typeof this.unwatch === 'function') this.unwatch();
        },

        toggle() {
            this.open = !this.open;
        },

        count(e) {
            const { value } = e.target;
            this.n = value.length;
            draftWrite(value);
        },

        faCount() {
            return faDigits(this.n);
        },

        submit() {
            const { canSubmit, action } = this.$el.dataset;
            if (canSubmit !== ON || !action || this.uploading || this.sending) return;
            this.sending = true;
            const done = () => {
                this.sending = false;
            };
            try {
                const call = this.$wire[action]?.();
                if (!call?.then) return done();
                call.then(() => draftWrite('')).catch(() => {}).finally(done);
            } catch {
                done();
            }
        },
    };
}

export function aiReportTable() {
    return {
        ...tableToolsMixin('ai-report'),

        init() {
            this.initTableTools();
            this.syncPinned();
        },

        destroy() {
            this.destroyTableTools();
        },

        syncPinned() {
            const ids = this.$store?.pinned?.getPinned('ai-report') ?? [];
            const current = this.$wire?.pinned ?? [];
            if (ids.join() !== current.join()) this.$wire.syncPinned(ids)?.catch?.(() => {});
        },
    };
}

export function aiReportCopy() {
    return {
        ...clipboardMixin(),

        copy() {
            const doc = document.querySelector(DOC_SELECTOR);
            if (!doc) return;
            this.copyText(collectReportText(doc), this.$el.dataset.toast, 'success');
        },

        copyLetter() {
            const { text, toast } = this.$el.dataset;
            if (text) this.copyText(text, toast, 'success');
        },
    };
}

export function aiReportChart() {
    return {
        ...chartBuilderMixin(),
        chart: null,
        drawn: false,
        spec: null,
        inited: false,
        ready: false,
        gone: false,
        failed: false,
        dark: false,
        sizeWatch: null,
        themeWatch: null,
        onPrint: null,

        async init() {
            if (this.inited) return;
            this.inited = true;
            try {
                this.spec = parseChart(this.$el.dataset.chart);
                if (!this.spec) return;
                await import('../../../core/chart.js');
                if (this.gone || !window.Chart) return;
                this.ready = true;
                this.onPrint = () => {
                    this.failed = false;
                    this.redraw();
                };
                PRINT.forEach((e) => window.addEventListener(e, this.onPrint));
                if (typeof MutationObserver === 'function') {
                    this.themeWatch = new MutationObserver(() => {
                        if (this.chart && document.documentElement.classList.contains('dark') !== this.dark) this.redraw();
                    });
                    this.themeWatch.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                }
                if (typeof ResizeObserver === 'function') {
                    this.sizeWatch = new ResizeObserver(() => {
                        if (!this.chart) this.redraw();
                    });
                    this.sizeWatch.observe(this.$el);
                }
                this.$nextTick(() => this.redraw());
            } catch {
                this.drawn = false;
            }
        },

        destroy() {
            this.gone = true;
            if (this.onPrint) PRINT.forEach((e) => window.removeEventListener(e, this.onPrint));
            this.sizeWatch?.disconnect();
            this.themeWatch?.disconnect();
            this.release();
        },

        release() {
            try {
                this.chart?.destroy();
            } catch {}
            this.chart = null;
        },

        visible() {
            return !this.sizeWatch || this.$el.clientWidth > 0;
        },

        redraw() {
            if (this.gone || this.failed || !this.ready || !this.visible()) return;
            if (this.drawn) {
                this.build();
                return;
            }
            this.drawn = true;
            this.$nextTick(() => this.build());
        },

        build() {
            try {
                const canvas = this.$refs.canvas;
                if (this.gone || !canvas || !window.Chart) throw new Error('chart unavailable');
                this.release();
                this.dark = document.documentElement.classList.contains('dark');
                this.chart = new window.Chart(canvas, this.config());
            } catch {
                this.release();
                this.drawn = false;
                this.failed = true;
            }
        },

        config() {
            const { kind, labels, series } = this.spec;
            const fa = this.$el.dataset.lang !== 'en';
            const pie = kind === 'pie';
            const fmt = (n) => formatNumber(n, fa);
            const datasets = pie
                ? [{ label: series[0].name, data: series[0].values, backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]), borderColor: 'var(--md-sys-color-surface)', borderWidth: 1 }]
                : series.map((s, i) => ({ label: s.name, data: s.values, backgroundColor: PALETTE[i % PALETTE.length], borderRadius: 4 }));
            return {
                type: kind,
                data: this.buildData({ labels, datasets }),
                options: this.buildOptions({ options: {
                    animation: false,
                    scales: pie ? {} : {
                        x: { reverse: fa, grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } },
                        y: { beginAtZero: true, ticks: { callback: fmt, font: { size: 11 } } },
                    },
                    plugins: {
                        legend: { display: pie || series.length > 1, position: 'bottom', rtl: fa, textDirection: fa ? 'rtl' : 'ltr', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } },
                        tooltip: { rtl: fa, callbacks: { label: (c) => `${pie ? c.label : (c.dataset.label || c.label)}: ${fmt(c.parsed?.y ?? c.parsed)}` } },
                    },
                } }),
            };
        },
    };
}
