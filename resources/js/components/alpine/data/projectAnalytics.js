import chartBuilderMixin from '../mixins/chartBuilder.js'

export default function projectAnalytics(chartData, chartConfig, tabs, drillParams = {}, projectId = null, tasksUrl = '/tasks') {
    const tabKeys = Object.keys(tabs);

    return {
        ...chartBuilderMixin(),
        activeTab: tabKeys[0] ?? null,
        chartConfig,
        chartData,
        drillParams,
        projectId,
        tasksUrl,
        charts: {},
        els: {},
        Chart: null,

        async init() {
            await import('../../../core/chart.js');
            this.Chart = window.Chart;
            this.$nextTick(() => this.renderTab(this.activeTab));
        },

        destroy() {
            for (const key in this.charts) {
                this.charts[key]?.destroy();
            }
            this.charts = {};
        },

        switchTab(key) {
            this.activeTab = key;
            this.$nextTick(() => this.renderTab(key));
        },

        renderTab(key) {
            const chartKeys = tabs[key]?.charts;
            if (!chartKeys) return;

            for (let i = 0, len = chartKeys.length; i < len; i++) {
                const chartKey = chartKeys[i];
                if (!this.charts[chartKey]) this.renderChartByKey(chartKey);
            }
        },

        renderChartByKey(chartKey) {
            const el = this.els[chartKey] ??= document.getElementById('analytics-chart-' + chartKey);
            if (!el || !this.Chart) return;

            const config = this.chartConfig[chartKey];
            const data = this.chartData[chartKey];
            if (!config || !data || !data.datasets || !data.labels || !data.labels.length) return;

            const options = this.buildOptions(config);
            const param = this.drillParams[chartKey];
            if (param) {
                options.onClick = (event, elements) => this.drillDown(chartKey, elements);
                options.onHover = (event, elements) => { event.native.target.style.cursor = elements.length ? 'pointer' : 'default'; };
            }

            this.charts[chartKey]?.destroy();
            this.charts[chartKey] = new this.Chart(el, {
                type: config.type,
                data: this.buildData(data),
                options,
            });
        },

        drillDown(chartKey, elements) {
            if (!elements.length || !this.projectId) return;

            const param = this.drillParams[chartKey];
            const value = this.chartData[chartKey]?.values?.[elements[0].index];
            if (!param || value === null || value === undefined) return;

            const url = `${this.tasksUrl}?project=${this.projectId}&${param}=${encodeURIComponent(value)}`;
            if (window.Livewire?.navigate) {
                window.Livewire.navigate(url);
            } else {
                window.location.href = url;
            }
        },
    };
}
