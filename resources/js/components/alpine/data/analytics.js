import chartBuilderMixin from '../mixins/chartBuilder.js'

export default function analytics(chartData, chartConfig, categories) {
    const categoryKeys = Object.keys(categories);

    return {
        ...chartBuilderMixin(),
        category: categoryKeys[0],
        activeModule: Object.fromEntries(
            categoryKeys.map(key => [key, Object.keys(categories[key].modules)[0]])
        ),
        chartConfig,
        chartData,
        charts: {},
        els: {},
        Chart: null,

        async init() {
            await import('../../../core/chart.js');
            this.Chart = window.Chart;
            this.$nextTick(() => this.renderChart(this.category, this.activeModule[this.category]));
        },

        destroy() {
            for (const key in this.charts) {
                this.charts[key]?.destroy();
            }
            this.charts = {};
        },

        switchCategory(key) {
            this.category = key;
            if (!this.charts[key]) {
                this.$nextTick(() => this.renderChart(key, this.activeModule[key]));
            }
        },

        switchModule(category, moduleKey) {
            this.charts[category]?.destroy();
            delete this.charts[category];
            this.renderChart(category, moduleKey);
        },

        renderChart(category, moduleKey) {
            const el = this.els[category] ??= document.getElementById('analytics-chart-' + category);
            if (!el || !this.Chart) return;

            const config = this.chartConfig[moduleKey];
            const data = this.chartData[moduleKey];
            if (!config || !data) return;

            this.activeModule[category] = moduleKey;
            this.charts[category] = new this.Chart(el, {
                type: config.type,
                data,
                options: this.buildOptions(config),
            });
        },
    };
}
