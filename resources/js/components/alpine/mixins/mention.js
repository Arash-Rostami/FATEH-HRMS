function mentionEscape(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

const mentionRegexCache = new Map();
const MENTION_BREAK_RE = /[\s@]/;
const WHITESPACE_RE = /\s/;

function mentionRegexFor(name) {
    let re = mentionRegexCache.get(name);
    if (!re) {
        re = new RegExp('(?<![\\p{L}\\p{N}_])@' + mentionEscape(name) + '(?![\\p{L}\\p{N}_])', 'u');
        mentionRegexCache.set(name, re);
    }
    return re;
}

export default function mentionMixin() {
    return {
        mentionFilter(names, query) {
            const q = (query || '').toLowerCase();
            const list = names || [];
            if (q === '') return list;
            const out = [];
            for (let i = 0, len = list.length; i < len; i++) {
                const name = list[i];
                if ((name || '').toLowerCase().includes(q)) out.push(name);
            }
            return out;
        },

        mentionAtTerm(value, cursor) {
            const before = (value || '').slice(0, cursor);
            const at = before.lastIndexOf('@');
            if (at < 0) return null;
            const term = before.slice(at + 1);
            if (MENTION_BREAK_RE.test(term)) return null;
            if (at > 0 && !WHITESPACE_RE.test(before[at - 1])) return null;
            return { at, term };
        },

        mentionBuild(value, cursor, name) {
            const safeValue = value || '';
            const before = safeValue.slice(0, cursor);
            const at = before.lastIndexOf('@');
            if (at < 0) return null;
            const next = before.slice(0, at) + '@' + name + ' ' + safeValue.slice(cursor);
            return { value: next, caret: at + 1 + name.length + 1 };
        },

        mentionIsNotified(body, name) {
            return mentionRegexFor(name).test(body || '');
        },
    };
}
