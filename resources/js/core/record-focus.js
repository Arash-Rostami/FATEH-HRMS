export default function initRecordFocus() {
    document.addEventListener('livewire:init', () => {
        window.Livewire.on('record-focus', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload
            if (!data || data.id == null || !data.type) return

            const type = String(data.type)
            const id = Number(data.id)

            window.dispatchEvent(new CustomEvent('record-focus', { detail: { type, id } }))
            scrollToRecord(`${type}-${id}`)
        })
    })
}

const MAX_ATTEMPTS = 40
const RETRY_MS = 75
const escapeCSS = window.CSS?.escape ? (v) => CSS.escape(v) : (v) => v.replace(/"/g, '\\"')

function scrollToRecord(key, attempt = 0) {
    const el = document.querySelector(`[data-rf="${escapeCSS(key)}"]`)

    if (!el) {
        if (attempt < MAX_ATTEMPTS) setTimeout(() => scrollToRecord(key, attempt + 1), RETRY_MS)
        return
    }

    document.querySelectorAll('.record-focus-flash').forEach((n) => n.classList.remove('record-focus-flash'))
    el.style.animation = 'none'
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    el.classList.add('record-focus-flash')
}
