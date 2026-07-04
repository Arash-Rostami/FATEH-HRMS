export default function faq() {
    const escapeRegex = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const wrap = m => `<mark class="bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] rounded-sm px-1 py-[1px] font-semibold shadow-[0_2px_8px_color-mix(in_srgb,var(--tool-gold-color)_25%,transparent)] dark:shadow-[0_2px_8px_rgba(0,0,0,0.6)] transition-all duration-300">${m}</mark>`;

    return {
        active: null,

        init() {
            this.active = this.$wire.get('open');
        },

        toggle(id) {
            this.active = (this.active === id) ? null : id;
        },

        highlight(text) {
            const needle = String(this.$wire.search || '').trim();
            const safe = String(text ?? '').replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
            if (!needle) return safe;
            return safe.replace(new RegExp(`(${escapeRegex(needle)})`, 'gi'), wrap('$1'));
        },

        highlightHtml(html) {
            const needle = String(this.$wire.search || '').trim();
            if (!needle) return html;
            const re = new RegExp(`(${escapeRegex(needle)})`, 'gi');
            return String(html || '').split(/(<[^>]*>)/g)
                .map(part => part.startsWith('<') ? part : part.replace(re, wrap('$1')))
                .join('');
        },
    };
}
