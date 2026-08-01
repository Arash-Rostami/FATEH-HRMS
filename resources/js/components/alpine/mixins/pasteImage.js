export default function pasteImageMixin() {
    return {
        pasteImage(e, inputId) {
            const items = e.clipboardData?.items || [];
            const files = [];
            for (const it of items) {
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
            [...input.files].forEach(f => dt.items.add(f));
            files.forEach(f => dt.items.add(f));
            input.files = dt.files;
            input.dispatchEvent(new Event('change', {bubbles: true}));
        }
    };
}