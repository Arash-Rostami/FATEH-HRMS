const ESCAPE_REGEX_REGEX = /[.*+?^${}()|[\]\\]/g;
const HTML_ESCAPE_REGEX = /[&<>]/g;
const HTML_ESCAPE_MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;' };
const TAG_SPLIT_REGEX = /(<[^>]*>)/g;
const HIGHLIGHT_CLASS = "bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] rounded-sm px-1 py-[1px] font-semibold shadow-[0_2px_8px_color-mix(in_srgb,var(--tool-gold-color)_25%,transparent)] dark:shadow-[0_2px_8px_rgba(0,0,0,0.6)] transition-all duration-300";

const escapeRegex = (s) => s.replace(ESCAPE_REGEX_REGEX, '\\$&');
const WRAPPED_MATCH = `<mark class="${HIGHLIGHT_CLASS}">$1</mark>`;

export default function highlightMatchMixin() {
    return {
        _cachedNeedle: null,
        _cachedRegex: null,

        _getRegex(needle) {
            if (this._cachedNeedle === needle) {
                return this._cachedRegex;
            }
            this._cachedNeedle = needle;
            this._cachedRegex = new RegExp(`(${escapeRegex(needle)})`, 'gi');
            return this._cachedRegex;
        },

        highlight(text, needle) {
            const q = String(needle || '').trim();
            const safe = String(text ?? '').replace(HTML_ESCAPE_REGEX, (c) => HTML_ESCAPE_MAP[c]);
            if (!q) return safe;

            return safe.replace(this._getRegex(q), WRAPPED_MATCH);
        },

        highlightHtml(html, needle) {
            const q = String(needle || '').trim();
            if (!q) return html;

            const re = this._getRegex(q);
            const parts = String(html || '').split(TAG_SPLIT_REGEX);
            let out = '';

            for (let i = 0, len = parts.length; i < len; i++) {
                const part = parts[i];
                out += part.startsWith('<') ? part : part.replace(re, WRAPPED_MATCH);
            }

            return out;
        },
    };
}
