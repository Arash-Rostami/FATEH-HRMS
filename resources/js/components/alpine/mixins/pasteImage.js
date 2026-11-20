export default function pasteImageMixin() {
    return {
        pasteImage(e, inputId) {
            const data = e.clipboardData;
            const items = data?.items;
            if (!items) return;

            const fileItems = [];
            let imagesOnly = true;
            for (let i = 0, len = items.length; i < len; i++) {
                const it = items[i];
                if (it.kind !== 'file') continue;
                fileItems.push(it);
                if (!(it.type && it.type.startsWith('image/'))) imagesOnly = false;
            }
            if (!fileItems.length || (imagesOnly && clipboardHasText(data))) return;

            const files = [];
            for (let i = 0, len = fileItems.length; i < len; i++) {
                const f = fileItems[i].getAsFile();
                if (f) files.push(f);
            }
            if (!files.length) return;
            const input = document.getElementById(inputId);
            if (!input) return;
            e.preventDefault();

            const dt = new DataTransfer();
            const existing = input.files;
            for (let i = 0, len = existing.length; i < len; i++) dt.items.add(existing[i]);
            for (let i = 0, len = files.length; i < len; i++) dt.items.add(files[i]);

            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };
}

function clipboardHasText(data) {
    if (typeof data.getData !== 'function') return false;
    if (!visibleText(data.getData('text/plain'))) return false;
    return htmlHasVisibleText(data.getData('text/html'));
}

function visibleText(raw) {
    if (typeof raw !== 'string') return '';
    return raw.replace(/\u0000/g, '').replace(/&nbsp;|&#160;/gi, ' ').trim();
}

function htmlHasVisibleText(html) {
    if (typeof html !== 'string' || html === '') return false;
    const stripped = html
        .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, ' ')
        .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, ' ')
        .replace(/<[^>]+>/g, ' ');
    return visibleText(stripped).length > 0;
}
