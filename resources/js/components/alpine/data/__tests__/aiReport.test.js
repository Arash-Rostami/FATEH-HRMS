import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { aiReportChart, aiReportComposer, aiReportCopy, aiReportTable, collectReportText } from '../aiReport.js'

vi.mock('../../../../core/chart.js', () => ({}))

const flush = () => new Promise((r) => setTimeout(r, 0));

const withContext = ({ hasClipboard = true, secure = true } = {}) => {
    Object.defineProperty(window, 'isSecureContext', { value: secure, configurable: true });
    Object.defineProperty(navigator, 'clipboard', {
        value: hasClipboard ? { writeText: vi.fn() } : undefined,
        configurable: true,
    });
};

function mountComposer(dataset, prompt = '') {
    const el = document.createElement('div');
    for (const [k, v] of Object.entries(dataset)) el.dataset[k] = v;
    const ta = document.createElement('textarea');
    ta.value = prompt;
    el.appendChild(ta);
    document.body.appendChild(el);
    const c = aiReportComposer();
    c.$el = el;
    return c;
}

const DOC_FIXTURE = `
<div class="ai-report-doc">
    <div class="print:hidden"><p>toolbar chrome</p></div>
    <h1>Report title</h1>
    <p>Intro paragraph</p>
    <ul><li>First bullet</li><li>Second bullet</li></ul>
    <div class="grid">
        <div><p>Revenue</p><p>1200</p></div>
        <div><p>Growth</p><p>8%</p></div>
    </div>
    <table>
        <thead><tr><th>Month</th><th>Sales</th></tr></thead>
        <tbody><tr><td>Mehr</td><td>45</td></tr></tbody>
    </table>
    <span>mismatch badge</span>
    <p class="ai-report-disclaimer">AI disclaimer footer</p>
    <div aria-hidden="true"><p>hidden chrome</p></div>
</div>`;

beforeEach(() => {
    document.body.innerHTML = '';
    localStorage.clear();
    sessionStorage.clear();
    document.documentElement.dataset.uid = '42';
});

afterEach(() => {
    delete document.documentElement.dataset.uid;
});

describe('aiReportTable — shared table tools', () => {
    test('init loads the declutter state from table-tools:ai-report and destroy releases it', () => {
        localStorage.setItem('table-tools:ai-report', 'true');
        const t = aiReportTable();
        t.init();
        expect(t.headerDeclutter).toBe(true);
        t.destroy();
        expect(t._tableToolsAc.signal.aborted).toBe(true);
    });

    test('a fresh browser starts with the tools expanded and persists a change under the scope key', () => {
        const t = aiReportTable();
        t.init();
        expect(t.headerDeclutter).toBe(false);
        t.headerDeclutter = true;
        t._persistChatSettings();
        expect(JSON.parse(localStorage.getItem('table-tools:ai-report'))).toBe(true);
        t.destroy();
    });

    test('syncPinned sends the pinned ai-report ids to the server only when they differ from the server state', () => {
        const sync = vi.fn();
        const t = aiReportTable();
        t.$store = { pinned: { getPinned: (scope) => (scope === 'ai-report' ? [4, 9] : []) } };
        t.$wire = { pinned: [], syncPinned: sync };
        t.syncPinned();
        expect(sync).toHaveBeenCalledWith([4, 9]);

        t.$wire.pinned = [4, 9];
        t.syncPinned();
        expect(sync).toHaveBeenCalledTimes(1);
    });
});

describe('aiReportComposer — init and dataset wiring', () => {
    test('init reads open, max and the live counter from the DOM', () => {
        const c = mountComposer({ open: '1', max: '4000', canSubmit: '1', action: 'generate' }, 'hello');
        c.init();
        expect(c.open).toBe(true);
        expect(c.max).toBe(4000);
        expect(c.n).toBe(5);
    });

    test('a report already open renders the composer collapsed', () => {
        const c = mountComposer({ open: '0' });
        c.init();
        expect(c.open).toBe(false);
    });

    test('destroy releases the prompt watcher stored by init and tolerates a missing one', () => {
        const unwatch = vi.fn();
        const c = mountComposer({ open: '0' });
        c.$wire = { $watch: vi.fn(() => unwatch) };
        c.init();
        c.destroy();
        expect(unwatch).toHaveBeenCalledTimes(1);

        const bare = mountComposer({ open: '0' });
        bare.init();
        expect(() => bare.destroy()).not.toThrow();
    });
});

describe('aiReportComposer — collapse, counter, digits', () => {
    test('toggle flips the collapse state', () => {
        const c = aiReportComposer();
        expect(c.open).toBe(false);
        c.toggle();
        expect(c.open).toBe(true);
        c.toggle();
        expect(c.open).toBe(false);
    });

    test('count tracks the textarea length on input', () => {
        const c = aiReportComposer();
        c.count({ target: { value: 'abc' } });
        expect(c.n).toBe(3);
        c.count({ target: { value: '' } });
        expect(c.n).toBe(0);
    });

    test('faCount renders Persian digits with no grouping', () => {
        const c = aiReportComposer();
        c.n = 1234;
        expect(c.faCount()).toBe('۱۲۳۴');
    });
});

describe('aiReportComposer — Ctrl/Cmd+Enter submit', () => {
    test('canSubmit false never calls the wire action', () => {
        const c = aiReportComposer();
        c.$el = { dataset: { canSubmit: '0', action: 'generate' } };
        c.$wire = { generate: vi.fn(), refine: vi.fn() };
        c.submit();
        expect(c.$wire.generate).not.toHaveBeenCalled();
        expect(c.$wire.refine).not.toHaveBeenCalled();
    });

    test('a file upload in flight blocks the shortcut until it finishes', () => {
        const c = aiReportComposer();
        expect(c.uploading).toBe(false);
        c.$el = { dataset: { canSubmit: '1', action: 'generate' } };
        c.$wire = { generate: vi.fn(), refine: vi.fn() };
        c.uploading = true;
        c.submit();
        expect(c.$wire.generate).not.toHaveBeenCalled();
        c.uploading = false;
        c.submit();
        expect(c.$wire.generate).toHaveBeenCalledTimes(1);
    });

    test('a second submit while the first is in flight is ignored until the promise settles', async () => {
        const c = aiReportComposer();
        c.$el = { dataset: { canSubmit: '1', action: 'generate' } };
        let release;
        c.$wire = { generate: vi.fn(() => new Promise((resolve) => { release = resolve; })) };
        c.submit();
        c.submit();
        expect(c.$wire.generate).toHaveBeenCalledTimes(1);
        release();
        await flush();
        expect(c.sending).toBe(false);
        c.submit();
        expect(c.$wire.generate).toHaveBeenCalledTimes(2);
    });

    test('canSubmit true calls the dataset action on the wire', () => {
        const c = aiReportComposer();
        c.$el = { dataset: { canSubmit: '1', action: 'generate' } };
        c.$wire = { generate: vi.fn(), refine: vi.fn() };
        c.submit();
        expect(c.$wire.generate).toHaveBeenCalledTimes(1);

        c.$el = { dataset: { canSubmit: '1', action: 'refine' } };
        c.submit();
        expect(c.$wire.refine).toHaveBeenCalledTimes(1);
    });
});

describe('aiReportComposer — session draft survives a 419 expiry', () => {
    test('typing saves the prompt under the per-user key', () => {
        const c = aiReportComposer();
        c.count({ target: { value: 'گزارش فروش' } });
        expect(sessionStorage.getItem('ai-report:draft:42')).toBe('گزارش فروش');
    });

    test('an empty input removes the draft instead of storing an empty string', () => {
        sessionStorage.setItem('ai-report:draft:42', 'old');
        const c = aiReportComposer();
        c.count({ target: { value: '' } });
        expect(sessionStorage.getItem('ai-report:draft:42')).toBeNull();
    });

    test('init restores the draft into an empty textarea and syncs it to the wire', () => {
        sessionStorage.setItem('ai-report:draft:42', 'پیش‌نویس بازیابی‌شده');
        const c = mountComposer({ open: '1', max: '4000' }, '');
        c.$wire = { $set: vi.fn(), $watch: vi.fn() };
        const input = vi.fn();
        c.$el.querySelector('textarea').addEventListener('input', input);
        c.init();
        const textarea = c.$el.querySelector('textarea');
        expect(input).toHaveBeenCalledTimes(1);
        expect(textarea.value).toBe('پیش‌نویس بازیابی‌شده');
        expect(c.n).toBe(20);
        expect(c.$wire.$set).toHaveBeenCalledWith('form.prompt', 'پیش‌نویس بازیابی‌شده', false);
    });

    test('init never overwrites a textarea the server already filled', () => {
        sessionStorage.setItem('ai-report:draft:42', 'پیش‌نویس کهنه');
        const c = mountComposer({ open: '1' }, 'متن زندهٔ سرور');
        c.$wire = { $set: vi.fn(), $watch: vi.fn() };
        c.init();
        expect(c.$el.querySelector('textarea').value).toBe('متن زندهٔ سرور');
        expect(c.$wire.$set).not.toHaveBeenCalled();
    });

    test('the draft is cleared when the server resets the prompt, whichever button sent the request', () => {
        sessionStorage.setItem('ai-report:draft:42', 'متن پیش‌نویس');
        const c = mountComposer({ open: '1' }, 'متن زندهٔ سرور');
        c.$wire = { $set: vi.fn(), $watch: vi.fn() };
        c.init();
        const onPrompt = c.$wire.$watch.mock.calls.find((call) => call[0] === 'form.prompt')[1];

        onPrompt('متن در حال تایپ');
        expect(sessionStorage.getItem('ai-report:draft:42')).toBe('متن پیش‌نویس');

        onPrompt('');
        expect(sessionStorage.getItem('ai-report:draft:42')).toBeNull();
    });

    test('a successful submit clears the draft, a rejected one keeps it for the 419 reload', async () => {
        sessionStorage.setItem('ai-report:draft:42', 'متن پیش‌نویس');
        const c = aiReportComposer();
        c.$el = { dataset: { canSubmit: '1', action: 'generate' } };

        c.$wire = { generate: vi.fn(() => Promise.reject(new Error('419'))) };
        c.submit();
        await flush();
        expect(sessionStorage.getItem('ai-report:draft:42')).toBe('متن پیش‌نویس');

        c.$wire = { generate: vi.fn(() => Promise.resolve()) };
        c.submit();
        await flush();
        expect(sessionStorage.getItem('ai-report:draft:42')).toBeNull();
    });

    test('a throwing or promise-less wire action never breaks submit or the draft', async () => {
        sessionStorage.setItem('ai-report:draft:42', 'متن');
        const c = aiReportComposer();
        c.$el = { dataset: { canSubmit: '1', action: 'generate' } };
        c.$wire = { generate: vi.fn(() => { throw new Error('boom'); }) };
        expect(() => c.submit()).not.toThrow();
        await flush();
        expect(sessionStorage.getItem('ai-report:draft:42')).toBe('متن');
    });

    test('a denied sessionStorage is swallowed on save, restore and clear', () => {
        const real = window.sessionStorage;
        Object.defineProperty(window, 'sessionStorage', {
            configurable: true,
            value: { getItem: () => { throw new Error('denied'); }, setItem: () => { throw new Error('denied'); }, removeItem: () => { throw new Error('denied'); }, clear: () => { throw new Error('denied'); } },
        });
        const c = aiReportComposer();
        expect(() => c.count({ target: { value: 'x' } })).not.toThrow();

        const mounted = mountComposer({ open: '1' }, '');
        mounted.$wire = { $set: vi.fn(), $watch: vi.fn() };
        expect(() => mounted.init()).not.toThrow();

        c.$el = { dataset: { canSubmit: '1', action: 'generate' } };
        c.$wire = { generate: vi.fn(() => Promise.resolve()) };
        expect(() => c.submit()).not.toThrow();
        Object.defineProperty(window, 'sessionStorage', { configurable: true, value: real });
    });
});

describe('collectReportText — document-order collector', () => {
    test('walks the fixture: heading, paragraph, bullets, KPI tiles, TSV table, skipping span/disclaimer/chrome', () => {
        document.body.innerHTML = DOC_FIXTURE;
        const text = collectReportText(document.querySelector('.ai-report-doc'));
        expect(text).toBe([
            'Report title',
            'Intro paragraph',
            '• First bullet',
            '• Second bullet',
            'Revenue: 1200',
            'Growth: 8%',
            'Month\tSales',
            'Mehr\t45',
        ].join('\n'));
    });

    test('an empty document produces an empty string, and copy() never fires the clipboard or toast', () => {
        document.body.innerHTML = '<div class="ai-report-doc"></div>';
        withContext();
        const c = aiReportCopy();
        c.$el = { dataset: { toast: 'copied' } };
        c.$dispatch = vi.fn();
        c.copy();
        expect(collectReportText(document.querySelector('.ai-report-doc'))).toBe('');
        expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
        expect(c.$dispatch).not.toHaveBeenCalled();
    });
});

describe('aiReportCopy — clipboard wiring via the shared clipboardMixin', () => {
    test('secure context copies through the Clipboard API and toasts the dataset message', async () => {
        document.body.innerHTML = DOC_FIXTURE;
        withContext({ secure: true, hasClipboard: true });
        navigator.clipboard.writeText.mockResolvedValue(undefined);
        const c = aiReportCopy();
        c.$el = { dataset: { toast: 'copied text' } };
        c.$dispatch = vi.fn();

        c.copy();
        await flush();

        expect(navigator.clipboard.writeText).toHaveBeenCalledWith('Report title\nIntro paragraph\n• First bullet\n• Second bullet\nRevenue: 1200\nGrowth: 8%\nMonth\tSales\nMehr\t45');
        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: 'copied text', type: 'success' });
    });

    test('insecure context falls back to the hidden-textarea execCommand path and still toasts', async () => {
        document.body.innerHTML = DOC_FIXTURE;
        withContext({ secure: false, hasClipboard: true });
        document.execCommand = vi.fn(() => true);
        const c = aiReportCopy();
        c.$el = { dataset: { toast: 'copied text' } };
        c.$dispatch = vi.fn();

        c.copy();
        await flush();

        expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
        expect(document.execCommand).toHaveBeenCalledWith('copy');
        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: 'copied text', type: 'success' });
    });

    test('no .ai-report-doc on the page is a silent no-op', () => {
        withContext();
        const c = aiReportCopy();
        c.$el = { dataset: { toast: 'copied text' } };
        c.$dispatch = vi.fn();
        c.copy();
        expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
        expect(c.$dispatch).not.toHaveBeenCalled();
    });

    test('copyLetter copies the data-text attribute and toasts the dataset message, an empty one is a no-op', async () => {
        withContext({ secure: true, hasClipboard: true });
        navigator.clipboard.writeText.mockResolvedValue(undefined);
        const c = aiReportCopy();
        c.$dispatch = vi.fn();

        c.$el = { dataset: { text: 'Title\nKey: 12', toast: 'copied text' } };
        c.copyLetter();
        await flush();
        expect(navigator.clipboard.writeText).toHaveBeenCalledWith('Title\nKey: 12');
        expect(c.$dispatch).toHaveBeenCalledWith('toast', { message: 'copied text', type: 'success' });

        navigator.clipboard.writeText.mockClear();
        c.$dispatch.mockClear();
        c.$el = { dataset: { toast: 'copied text' } };
        c.copyLetter();
        await flush();
        expect(navigator.clipboard.writeText).not.toHaveBeenCalled();
        expect(c.$dispatch).not.toHaveBeenCalled();
    });
});

const CHART_SPEC = {
    type: 'chart',
    kind: 'bar',
    labels: ['a', 'b'],
    series: [{ name: 's1', values: [1234, 2] }, { name: 's2', values: [3, 4] }],
};

function mountChart(spec, lang = 'fa', width = 300) {
    const el = document.createElement('div');
    el.dataset.chart = typeof spec === 'string' ? spec : JSON.stringify(spec);
    el.dataset.lang = lang;
    el._w = width;
    Object.defineProperty(el, 'clientWidth', { get: () => el._w, configurable: true });
    const c = aiReportChart();
    c.$el = el;
    c.$refs = { canvas: document.createElement('canvas') };
    c.$nextTick = (fn) => fn();
    return c;
}

class FakeResizeObserver {
    constructor(cb) {
        this.cb = cb;
        this.disconnect = vi.fn();
        FakeResizeObserver.last = this;
    }
    observe() {}
}

describe('aiReportChart — hardened draw lifecycle', () => {
    beforeEach(() => {
        document.documentElement.classList.remove('dark');
        window.Chart = vi.fn(function (el, cfg) {
            this.cfg = cfg;
            this.destroy = vi.fn();
        });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
        document.documentElement.classList.remove('dark');
    });

    test('bar builds one dataset per series, reverses the x axis for fa and formats ticks with Persian digits', async () => {
        const c = mountChart(CHART_SPEC);
        await c.init();
        expect(window.Chart).toHaveBeenCalledTimes(1);
        const cfg = window.Chart.mock.calls[0][1];
        expect(cfg.type).toBe('bar');
        expect(cfg.data.labels).toEqual(['a', 'b']);
        expect(cfg.data.datasets).toHaveLength(2);
        expect(cfg.options.scales.x.reverse).toBe(true);
        expect(cfg.options.scales.y.ticks.callback(1234)).toBe('۱٬۲۳۴');
        expect(cfg.options.plugins.legend.rtl).toBe(true);
        expect(cfg.options.maintainAspectRatio).toBe(false);
        expect(cfg.options.animation).toBe(false);
        expect(c.drawn).toBe(true);
        c.destroy();
    });

    test('english keeps the axis, Latin digits, and a quote-laden label survives the data attribute', async () => {
        const label = `O'Neil "Q1"`;
        const c = mountChart({ ...CHART_SPEC, labels: [label, 'b'] }, 'en');
        await c.init();
        const cfg = window.Chart.mock.calls[0][1];
        expect(cfg.data.labels[0]).toBe(label);
        expect(cfg.options.scales.x.reverse).toBe(false);
        expect(cfg.options.scales.y.ticks.callback(1234)).toBe('1,234');
        expect(cfg.options.plugins.legend.rtl).toBe(false);
        c.destroy();
    });

    test('pie uses one dataset with a colour per slice, print redraws without animation, destroy releases everything', async () => {
        const c = mountChart({ type: 'chart', kind: 'pie', labels: ['a', 'b', 'c'], series: [{ name: 's', values: [1, 2, 3] }, { name: 'ignored', values: [9, 9, 9] }] });
        const removed = vi.spyOn(window, 'removeEventListener');
        await c.init();
        const cfg = window.Chart.mock.calls[0][1];
        expect(cfg.data.datasets).toHaveLength(1);
        expect(cfg.data.datasets[0].backgroundColor).toHaveLength(3);
        expect(cfg.options.plugins.legend.display).toBe(true);
        const first = window.Chart.mock.instances[0];

        window.dispatchEvent(new Event('beforeprint'));
        expect(window.Chart).toHaveBeenCalledTimes(2);
        expect(window.Chart.mock.calls[1][1].options.animation).toBe(false);
        expect(first.destroy).toHaveBeenCalledTimes(1);

        const live = window.Chart.mock.instances[1];
        c.destroy();
        expect(live.destroy).toHaveBeenCalledTimes(1);
        expect(c.chart).toBeNull();
        expect(removed.mock.calls.map((a) => a[0]).sort()).toEqual(['afterprint', 'beforeprint']);
        window.dispatchEvent(new Event('beforeprint'));
        expect(window.Chart).toHaveBeenCalledTimes(2);
    });

    test('bad JSON, empty data, all-invalid points and a missing Chart leave the table fallback without throwing', async () => {
        const bad = mountChart('{oops');
        await expect(bad.init()).resolves.toBeUndefined();
        expect(bad.drawn).toBe(false);

        const empty = mountChart({ ...CHART_SPEC, labels: [], series: [] });
        await empty.init();
        expect(empty.drawn).toBe(false);

        const junk = mountChart({ ...CHART_SPEC, series: [{ name: 'x', values: ['abc', null, 'NaN'] }, 'junk'] });
        await junk.init();
        expect(junk.drawn).toBe(false);

        const zeroPie = mountChart({ type: 'chart', kind: 'pie', labels: ['a'], series: [{ name: 's', values: [0] }] });
        await zeroPie.init();
        expect(zeroPie.drawn).toBe(false);
        expect(window.Chart).not.toHaveBeenCalled();

        delete window.Chart;
        const missing = mountChart(CHART_SPEC);
        await expect(missing.init()).resolves.toBeUndefined();
        expect(missing.drawn).toBe(false);
    });

    test('bad points are coerced to gaps and a throwing Chart constructor leaves drawn false', async () => {
        const c = mountChart({ ...CHART_SPEC, series: [{ name: 's', values: ['5', 'abc'] }] });
        await c.init();
        expect(window.Chart.mock.calls[0][1].data.datasets[0].data).toEqual([5, null]);
        c.destroy();

        window.Chart = vi.fn(() => {
            throw new Error('boom');
        });
        const broken = mountChart(CHART_SPEC);
        await expect(broken.init()).resolves.toBeUndefined();
        expect(broken.drawn).toBe(false);
        expect(broken.chart).toBeNull();
        broken.destroy();
    });

    test('a zero-width box does not draw until the ResizeObserver reports a width', async () => {
        vi.stubGlobal('ResizeObserver', FakeResizeObserver);
        const c = mountChart(CHART_SPEC, 'fa', 0);
        await c.init();
        expect(window.Chart).not.toHaveBeenCalled();
        expect(c.drawn).toBe(false);

        c.$el._w = 240;
        FakeResizeObserver.last.cb();
        expect(window.Chart).toHaveBeenCalledTimes(1);
        expect(c.drawn).toBe(true);

        FakeResizeObserver.last.cb();
        expect(window.Chart).toHaveBeenCalledTimes(1);

        c.destroy();
        expect(FakeResizeObserver.last.disconnect).toHaveBeenCalledTimes(1);
    });

    test('a Chart constructor that throws is not retried by the ResizeObserver', async () => {
        vi.stubGlobal('ResizeObserver', FakeResizeObserver);
        window.Chart = vi.fn(() => {
            throw new Error('boom');
        });
        const c = mountChart(CHART_SPEC);
        await c.init();
        expect(window.Chart).toHaveBeenCalledTimes(1);
        expect(c.failed).toBe(true);

        FakeResizeObserver.last.cb();
        FakeResizeObserver.last.cb();
        expect(window.Chart).toHaveBeenCalledTimes(1);
        c.destroy();
    });

    test('a second init does not double-register listeners or draw twice', async () => {
        const added = vi.spyOn(window, 'addEventListener');
        const c = mountChart(CHART_SPEC);
        await c.init();
        await c.init();
        const names = added.mock.calls.map((a) => a[0]);
        expect(names.filter((n) => n === 'beforeprint')).toHaveLength(1);
        expect(names.filter((n) => n === 'afterprint')).toHaveLength(1);
        expect(window.Chart).toHaveBeenCalledTimes(1);
        c.destroy();
    });

    test('toggling the dark class redraws once and stops after destroy', async () => {
        const c = mountChart(CHART_SPEC);
        await c.init();
        expect(window.Chart).toHaveBeenCalledTimes(1);

        document.documentElement.classList.add('dark');
        await flush();
        expect(window.Chart).toHaveBeenCalledTimes(2);

        c.destroy();
        document.documentElement.classList.remove('dark');
        await flush();
        expect(window.Chart).toHaveBeenCalledTimes(2);
    });

    test('a draw that throws during print is recovered by the afterprint redraw', async () => {
        let n = 0;
        window.Chart = vi.fn(function () {
            n += 1;
            if (n === 2) throw new Error('print draw failed');
            this.destroy = vi.fn();
        });
        const c = mountChart(CHART_SPEC);
        await c.init();
        expect(c.drawn).toBe(true);

        window.dispatchEvent(new Event('beforeprint'));
        expect(c.drawn).toBe(false);
        expect(c.chart).toBeNull();

        window.dispatchEvent(new Event('afterprint'));
        expect(window.Chart).toHaveBeenCalledTimes(3);
        expect(c.drawn).toBe(true);
        expect(c.chart).not.toBeNull();
        c.destroy();
    });
});

describe('collectReportText — chart block', () => {
    test('a chart contributes its heading, its data table as TSV and its unit line', () => {
        document.body.innerHTML = `
<div class="ai-report-doc">
    <div class="ai-report-chart">
        <h2>Sales chart</h2>
        <div><canvas role="img"></canvas></div>
        <div style="display:none"><table>
            <thead><tr><th>Category</th><th>Sales</th></tr></thead>
            <tbody><tr><td><bdi>Mehr</bdi></td><td><bdi>45</bdi></td></tr></tbody>
        </table></div>
        <p>Million</p>
    </div>
</div>`;
        expect(collectReportText(document.querySelector('.ai-report-doc'))).toBe('Sales chart\nCategory\tSales\nMehr\t45\nMillion');
    });
});

describe('aiReport module — no Persian literal inside the JS', () => {
    test('the module sources carry no hardcoded Persian text (toast text comes from data-toast)', () => {
        const src = readFileSync(resolve(process.cwd(), 'resources/js/components/alpine/data/aiReport.js'), 'utf8');
        expect(src).not.toMatch(/[؀-ۿ]/);
    });
});
