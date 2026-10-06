import maximizeMixin from "../mixins/maximize.js";

const MAX_WIDGET_CLOSE_MS = 350;
const WIDGET_SELECTOR = '[data-widget]';

export default function energyChart(history, companyAverages, sections, latestTest) {
    return {
        ...maximizeMixin('energy'),
        charts: {},
        maximizedWidget: null,
        Chart: null,

        async init() {
            this.initMaximize();
            if (!history?.length && !latestTest) return;
            await import ('../../../core/chart.js');
            this.Chart = window.Chart;
            this.$nextTick(() => {
                if (history?.length) this.renderHistoryChart();
                if (latestTest) this.renderRadarChart();
            });
        },

        destroy() {
            for (const key in this.charts) {
                this.charts[key]?.destroy();
            }
            this.charts = {};
        },

        toggleMaximize(name) {
            if (name === null) name = this.maximizedWidget;
            if (!name) return;

            if (this.maximizedWidget === name) {
                this.maxLeaving = true;
                this._persistMaximize(null);
                this.applyMaximize(false);
                clearTimeout(this.maxTimer);
                this.maxTimer = setTimeout(() => {
                    this.maximizedWidget = null;
                    this.maxLeaving = false;
                }, MAX_WIDGET_CLOSE_MS);
                return;
            }

            clearTimeout(this.maxTimer);
            this.maxLeaving = false;
            this.maximizedWidget = name;
            this._persistMaximize(name);
            this.applyMaximize(true);
        },

        restoreMaximize(value) {
            const widgets = document.querySelectorAll(WIDGET_SELECTOR);
            let found = false;
            for (let i = 0, len = widgets.length; i < len; i++) {
                if (widgets[i].dataset.widget === value) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                this._persistMaximize(null);
                return;
            }
            this.maximizedWidget = value;
            this.applyMaximize(true);
        },

        getCssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        renderHistoryChart() {
            const el = document.getElementById('historyChart');
            if (!el) return;

            const dims = ['overall', 'physique', 'emotion', 'mind', 'soul'];
            const colors = [
                this.getCssVar('--md-sys-color-primary'),
                this.getCssVar('--md-sys-color-secondary'),
                this.getCssVar('--md-sys-color-tertiary'),
                this.getCssVar('--md-sys-color-error'),
                this.getCssVar('--md-sys-color-on-surface-variant'),
            ];
            const labels = Object.fromEntries(
                Object.entries(sections).map(([k,v]) => [k, v.replace(/[^\u0600-\u06FF\s]/g,'').trim()])
            );
            const dimLabels = { overall:'کل', ...labels };

            this.charts.history = new this.Chart(el, {
                type: 'line',
                data: {
                    labels: history.map(r => r.date),
                    datasets: dims.map((d, i) => ({
                        label: dimLabels[d] ?? d,
                        data: history.map(r => r[d]),
                        borderColor: colors[i],
                        backgroundColor: colors[i] + '22',
                        borderWidth: i === 0 ? 2.5 : 1.5,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 700, easing: 'easeOutQuad' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: this.getCssVar('--md-sys-color-on-surface-variant'),
                                font: { family: 'Yekan, system-ui', size: 11 },
                                boxWidth: 12,
                                padding: 12,
                            },
                        },
                        tooltip: { rtl: true },
                    },
                    scales: {
                        y: {
                            min: 0,
                            ticks: { color: this.getCssVar('--md-sys-color-on-surface-variant'), font: { size: 11 } },
                            grid: { color: this.getCssVar('--md-sys-color-outline-variant') + '40' },
                        },
                        x: {
                            ticks: { color: this.getCssVar('--md-sys-color-on-surface-variant'), font: { size: 10 }, maxRotation: 45 },
                            grid: { display: false },
                        },
                    },
                },
            });
        },

        renderRadarChart() {
            const el = document.getElementById('radarChart');
            if (!el || !latestTest) return;

            const dims = ['physique', 'emotion', 'mind', 'soul'];
            const sectionLabels = Object.fromEntries(
                Object.entries(sections).map(([k,v]) => [k, v.replace(/[^\u0600-\u06FF\s]/g,'').trim()])
            );
            const labels = dims.map(d => sectionLabels[d] ?? d);

            const primary = this.getCssVar('--md-sys-color-primary');
            const secondary = this.getCssVar('--md-sys-color-secondary');
            const surface = this.getCssVar('--md-sys-color-on-surface-variant');

            this.charts.radar = new this.Chart(el, {
                type: 'radar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'شما',
                            data: dims.map(d => latestTest[d + '_score'] ?? 0),
                            borderColor: primary,
                            backgroundColor: primary + '33',
                            borderWidth: 2,
                            pointBackgroundColor: primary,
                            pointRadius: 4,
                        },
                        {
                            label: 'میانگین سازمان',
                            data: dims.map(d => parseFloat(companyAverages[d] ?? 0).toFixed(1)),
                            borderColor: secondary,
                            backgroundColor: secondary + '22',
                            borderWidth: 1.5,
                            pointBackgroundColor: secondary,
                            pointRadius: 3,
                            borderDash: [4, 3],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 700, easing: 'easeOutQuad' },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: surface,
                                font: { family: 'Yekan, system-ui', size: 11 },
                                boxWidth: 12,
                                padding: 12,
                            },
                        },
                    },
                    scales: {
                        r: {
                            min: 0,
                            max: 4,
                            ticks: { stepSize: 1, color: surface, font: { size: 9 }, backdropColor: 'transparent' },
                            pointLabels: { color: surface, font: { family: 'Yekan, system-ui', size: 11 } },
                            grid: { color: this.getCssVar('--md-sys-color-outline-variant') + '50' },
                            angleLines: { color: this.getCssVar('--md-sys-color-outline-variant') + '50' },
                        },
                    },
                },
            });
        },
    }
}
