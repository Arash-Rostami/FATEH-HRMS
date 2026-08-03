import maximizeMixin from "../mixins/maximize.js";
import settings from "./settings.js";

export default function energyChart(history, companyAverages, sections, latestTest) {
    return {
        ...maximizeMixin(),
        charts: {},
        maximizedWidget: null,

        async init() {
            if (!history?.length) return;
            await import ('../../../core/chart.js');
            this.$nextTick(() => {
                this.renderHistoryChart();
                this.renderRadarChart();
            });
        },

        toggleMaximize(name) {
            this.maximizedWidget = this.maximizedWidget === name ? null : name;
            this.applyMaximize(!!this.maximizedWidget);
        },

        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
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

            this.charts.history = new Chart(el, {
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
                            max: 4,
                            ticks: { stepSize: 1, color: this.getCssVar('--md-sys-color-on-surface-variant'), font: { size: 11 } },
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
            const labels = dims.map(d => (Object.fromEntries(
                Object.entries(sections).map(([k,v]) => [k, v.replace(/[^\u0600-\u06FF\s]/g,'').trim()])
            ))[d] ?? d);

            const primary = this.getCssVar('--md-sys-color-primary');
            const secondary = this.getCssVar('--md-sys-color-secondary');
            const surface = this.getCssVar('--md-sys-color-on-surface-variant');

            this.charts.radar = new Chart(el, {
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
