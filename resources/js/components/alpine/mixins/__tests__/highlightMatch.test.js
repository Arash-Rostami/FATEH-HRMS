import { describe, expect, test } from 'vitest'
import highlightMatchMixin from '../highlightMatch.js'

describe('highlightMatchMixin.highlight — escape then wrap', () => {
    test('escapes &, <, > in the source text even when there is no match', () => {
        const h = highlightMatchMixin();
        expect(h.highlight('<b>A & B</b>', '')).toBe('&lt;b&gt;A &amp; B&lt;/b&gt;');
    });

    test('wraps a single case-insensitive match in <mark>, preserving the matched text\'s own casing', () => {
        const h = highlightMatchMixin();
        const out = h.highlight('Hello World', 'hello');
        expect(out).toMatch(/<mark[^>]*>Hello<\/mark> World/);
    });

    test('wraps every occurrence of the needle (global match)', () => {
        const h = highlightMatchMixin();
        const out = h.highlight('foo bar foo', 'foo');
        expect(out.match(/<mark/g)).toHaveLength(2);
    });

    test('a multi-word needle wraps each word independently, even when the words are not adjacent in the text', () => {
        const h = highlightMatchMixin();
        const out = h.highlight('bar some text foo', 'foo bar');
        expect(out).toMatch(/<mark[^>]*>bar<\/mark>/);
        expect(out).toMatch(/<mark[^>]*>foo<\/mark>/);
    });

    test('a needle containing regex metacharacters is matched literally, not as a pattern', () => {
        const h = highlightMatchMixin();
        expect(h.highlight('axb and a.b', 'a.b')).not.toMatch(/<mark[^>]*>axb<\/mark>/);
        expect(h.highlight('axb and a.b', 'a.b')).toMatch(/<mark[^>]*>a\.b<\/mark>/);
    });

    test('an empty/whitespace-only needle returns the escaped text completely untouched', () => {
        const h = highlightMatchMixin();
        expect(h.highlight('hello', '   ')).toBe('hello');
    });
});

describe('highlightMatchMixin.highlightHtml — tag-aware splitting (must not highlight inside existing HTML tags)', () => {
    test('wraps a match in plain text content but leaves the surrounding tag untouched', () => {
        const h = highlightMatchMixin();
        const out = h.highlightHtml('<a href="x">hi there</a>', 'hi');
        expect(out.startsWith('<a href="x">')).toBe(true);
        expect(out.endsWith('</a>')).toBe(true);
        expect(out).toMatch(/<mark[^>]*>hi<\/mark> there/);
    });

    test('a needle that only appears inside a tag attribute is never highlighted — the real correctness risk this mixin guards against', () => {
        const h = highlightMatchMixin();
        const out = h.highlightHtml('<a href="word">text</a>', 'word');
        expect(out).toBe('<a href="word">text</a>');
        expect(out).not.toContain('<mark');
    });

    test('highlights matches in text segments on both sides of an inline tag, leaving the tag itself untouched', () => {
        const h = highlightMatchMixin();
        const out = h.highlightHtml('find me <b>bold</b> and find me again', 'find');
        expect(out.match(/<mark/g)).toHaveLength(2);
        expect(out).toContain('<b>bold</b>');
    });

    test('an empty needle returns the html completely unchanged — no escaping, since input is assumed already-safe HTML', () => {
        const h = highlightMatchMixin();
        expect(h.highlightHtml('<b>raw</b>', '')).toBe('<b>raw</b>');
    });

    test('a multi-word needle wraps matches across several text segments, still skipping tag tokens', () => {
        const h = highlightMatchMixin();
        const out = h.highlightHtml('<p>alpha</p><p>beta</p>', 'alpha beta');
        expect(out).toMatch(/<p><mark[^>]*>alpha<\/mark><\/p><p><mark[^>]*>beta<\/mark><\/p>/);
    });
});

describe('highlightMatchMixin regex caching (shared by highlight and highlightHtml)', () => {
    test('the compiled regex is reused across calls with the same needle, and rebuilt when the needle changes', () => {
        const h = highlightMatchMixin();
        h.highlight('a', 'foo');
        const first = h._cachedRegex;

        h.highlight('b', 'foo');
        expect(h._cachedRegex).toBe(first);

        h.highlight('c', 'bar');
        expect(h._cachedRegex).not.toBe(first);
        expect(h._cachedNeedle).toBe('bar');
    });

    test('highlight and highlightHtml share the same needle/regex cache', () => {
        const h = highlightMatchMixin();
        h.highlight('a', 'shared');
        const first = h._cachedRegex;

        h.highlightHtml('<b>shared</b>', 'shared');

        expect(h._cachedRegex).toBe(first);
    });
});
