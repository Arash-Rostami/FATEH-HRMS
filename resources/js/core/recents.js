export const RECENTS_KEY = 'recent_records_admin'
export const MAX_RECENTS = 20

export const readRecents = () => {
    try {
        const entries = JSON.parse(localStorage.getItem(RECENTS_KEY))

        return Array.isArray(entries) ? entries : []
    } catch {
        return []
    }
}

export const matchRecord = (path, config) => {
    if (!config?.path || !config.map) return null

    const match = path.match(new RegExp(`^/${config.path}/([a-z0-9-]+)/(\\d+)(?:/edit)?/?$`))

    return match && config.map[match[1]] ? { slug: match[1], id: match[2], label: config.map[match[1]].label } : null
}

export const recordVisit = (record, title, url) => {
    const entry = { slug: record.slug, id: record.id, label: record.label, title, url }
    const entries = [entry, ...readRecents().filter((e) => e?.url !== url)].slice(0, MAX_RECENTS)

    try {
        localStorage.setItem(RECENTS_KEY, JSON.stringify(entries))
    } catch {
    }

    window.dispatchEvent(new CustomEvent('recents-updated'))
}

const capture = () => {
    const record = matchRecord(location.pathname, window.__adminRecents)

    if (!record) return

    const url = location.pathname

    requestAnimationFrame(() => requestAnimationFrame(() => {
        recordVisit(record, document.querySelector('.fi-header-heading')?.textContent?.trim() ?? '', url)
    }))
}

document.addEventListener('livewire:navigated', capture)
capture()
