export default function initRecordFocus() {
    window.addEventListener('record-focus', (event) => {
        const { type, id } = event.detail;
        if (!type || !id) return;

        const selector = `[data-rf="${type}-${id}"]`;
        let attempts = 0;
        const maxAttempts = 30; // 3 seconds at 100ms intervals

        const findAndFocus = () => {
            const el = document.querySelector(selector);
            if (el) {
                // Wait for Alpine to un-hide elements if needed
                setTimeout(() => {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Remove animation class if present to re-trigger
                    el.classList.remove('record-focus-flash');
                    // Force reflow
                    void el.offsetWidth;
                    el.classList.add('record-focus-flash');
                }, 100);
            } else if (attempts < maxAttempts) {
                attempts++;
                setTimeout(findAndFocus, 100);
            }
        };

        findAndFocus();
    });
}
