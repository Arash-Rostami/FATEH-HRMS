export default function initRecordFocus() {
    document.addEventListener('livewire:init', () => {
        window.Livewire.on('record-focus', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            if (!data || data.id == null || !data.type) return;

            // Let Alpine components react immediately (set activeId / open / flip).
            window.dispatchEvent(new CustomEvent('record-focus', {
                detail: {type: String(data.type), id: Number(data.id)},
            }));

            scrollToRecord(`${data.type}-${data.id}`);
        });
    });
}

function scrollToRecord(key, attempt = 0) {
    const el = document.querySelector(`[data-rf="${cssEscape(key)}"]`);

    if (!el) {
        // Up to ~3s: wait for navigate / lazy load / pagination to render the row.
        if (attempt < 40) {
            setTimeout(() => scrollToRecord(key, attempt + 1), 75);
        }
        return;
    }

    document.querySelectorAll('.record-focus-flash').forEach(n => n.classList.remove('record-focus-flash'));
    el.style.animation = 'none';
    el.scrollIntoView({behavior: 'smooth', block: 'center'});
    el.classList.add('record-focus-flash');
}

function cssEscape(value) {
    return window.CSS && CSS.escape ? CSS.escape(value) : value.replace(/"/g, '\\"');
}
