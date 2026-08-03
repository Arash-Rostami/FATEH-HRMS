export default function analyticsChart(chartData, chartConfig, categories) {
    const categoryKeys = Object.keys(categories);

    return {
        category: categoryKeys[0],
        activeModule: Object.fromEntries(
            categoryKeys.map(key => [key, Object.keys(categories[key].modules)[0]])
        ),
        chartConfig,
        charts: {},
        Chart: null,

        async init() {
            await import('../../../core/chart.js');
            this.Chart = window.Chart;
            this.$nextTick(() => this.renderChart(this.category, this.activeModule[this.category]));
        },

        switchCategory(key) {
            this.category = key;
            if (!this.charts[key]) {
                this.$nextTick(() => this.renderChart(key, this.activeModule[key]));
            }
        },

        switchModule(category, moduleKey) {
            this.charts[category]?.destroy();
            this.renderChart(category, moduleKey);
        },

        getCssVar(name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        },

        renderChart(category, moduleKey) {
            const el = document.getElementById('analytics-chart-' + category);
            if (!el || !this.Chart) return;

            this.activeModule[category] = moduleKey;

            const config = this.chartConfig[moduleKey];
            const textColor = this.getCssVar('--md-sys-color-on-surface-variant');
            const gridColor = this.getCssVar('--md-sys-color-outline-variant');

            const scales = Object.fromEntries(
                Object.entries(config.options.scales ?? {}).map(([axis, scale]) => [
                    axis,
                    { ...scale, ticks: { ...scale.ticks, color: textColor }, grid: { color: gridColor } },
                ])
            );

            const options = {
                ...config.options,
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700, easing: 'easeOutQuad' },
                scales,
                plugins: {
                    ...config.options.plugins,
                    legend: {
                        ...config.options.plugins?.legend,
                        labels: { ...config.options.plugins?.legend?.labels, color: textColor },
                    },
                    tooltip: { rtl: true },
                },
            };

            this.charts[category] = new this.Chart(el, {
                type: config.type,
                data: chartData[moduleKey],
                options,
            });

        },
    };
}
