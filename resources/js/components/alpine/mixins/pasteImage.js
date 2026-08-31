export default function pasteImageMixin() {
    return {
        pasteImage(e, inputId) {
            const items = e.clipboardData?.items;
            if (!items) return;

            const files = [];
            for (let i = 0, len = items.length; i < len; i++) {
                const it = items[i];
                if (it.kind === 'file' && it.type.startsWith('image/')) {
                    const f = it.getAsFile();
                    if (f) files.push(f);
                }
            }
            if (!files.length) return;
            e.preventDefault();

            const input = document.getElementById(inputId);
            if (!input) return;

            const dt = new DataTransfer();
            const existing = input.files;
            for (let i = 0, len = existing.length; i < len; i++) dt.items.add(existing[i]);
            for (let i = 0, len = files.length; i < len; i++) dt.items.add(files[i]);

            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };
}
