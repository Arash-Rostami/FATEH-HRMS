let styleRef;
const rootStyle = () => styleRef || (styleRef = getComputedStyle(document.documentElement));
const withDefault = (def, caller) => (caller === false ? false : { ...def, ...(caller ?? {}) });

export default function chartBuilderMixin() {
    return {
        getCssVar(name, style = rootStyle()) {
            return style.getPropertyValue(name).trim();
        },

        resolveColor(value, style = rootStyle()) {
            if (typeof value === 'string' && value.startsWith('var(--')) {
                return this.getCssVar(value.slice(4, -1), style);
            }
            return Array.isArray(value) ? value.map(v => this.resolveColor(v, style)) : value;
        },

        buildOptions(config) {
            const style = rootStyle();
            const textColor = this.getCssVar('--md-sys-color-on-surface-variant', style);
            const gridColor = this.getCssVar('--md-sys-color-outline-variant', style);
            const opts = config.options ?? {};

            const scales = {};
            for (const axis in opts.scales) {
                const scale = opts.scales[axis];
                scales[axis] = { ...scale, ticks: withDefault({ color: textColor }, scale.ticks), grid: withDefault({ color: gridColor }, scale.grid) };
            }

            const plugins = opts.plugins;
            const legend = plugins?.legend;

            return {
                ...opts,
                responsive: true,
                maintainAspectRatio: false,
                animation: withDefault({ duration: 700, easing: 'easeOutQuad' }, opts.animation),
                scales,
                plugins: {
                    ...plugins,
                    legend: { ...legend, labels: { ...legend?.labels, color: textColor } },
                    tooltip: withDefault({ rtl: true }, plugins?.tooltip),
                },
            };
        },

        buildData(data) {
            const style = rootStyle();
            return {
                ...data,
                datasets: (data.datasets ?? []).map(ds => ({
                    ...ds,
                    backgroundColor: this.resolveColor(ds.backgroundColor, style),
                    borderColor: this.resolveColor(ds.borderColor, style),
                })),
            };
        },
    };
}
