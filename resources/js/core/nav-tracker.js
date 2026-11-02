export const VISITS_KEY = 'nav.visits'
export const MAX_VISITS = 8

export const visitIdForPath = (path) => {
    const clean = (path || '').replace(/\/+$/, '') || '/'

    return clean === '/' || clean === '/dashboard' ? null : `route:${clean}`
}

export const visitIdForTab = (tab) => (tab && tab !== 'home' ? `tab:${tab}` : null)

export const visitIdForItem = (item) => {
    if (item?.type === 'tab') return visitIdForTab(item.target)
    if (!item?.url) return null

    try {
        return visitIdForPath(new URL(item.url, location.origin).pathname)
    } catch {
        return null
    }
}

export const readVisits = () => {
    try {
        const visits = JSON.parse(localStorage.getItem(VISITS_KEY))

        return Array.isArray(visits) ? visits : []
    } catch {
        return []
    }
}

export const recordVisit = (id) => {
    if (!id) return

    const visits = readVisits()
    if (visits[0] === id) return

    try {
        localStorage.setItem(VISITS_KEY, JSON.stringify([id, ...visits.filter((v) => v !== id)].slice(0, MAX_VISITS)))
    } catch {
    }
}

export const clearVisits = () => {
    try {
        localStorage.removeItem(VISITS_KEY)
    } catch {
    }
}

export default function initNavTracker() {
    const onPage = () => recordVisit(visitIdForPath(location.pathname))
    const onTab = (e) => recordVisit(visitIdForTab(e.detail?.tab))

    document.addEventListener('livewire:navigated', onPage)
    window.addEventListener('tab-switched', onTab)
    onPage()

    return () => {
        document.removeEventListener('livewire:navigated', onPage)
        window.removeEventListener('tab-switched', onTab)
    }
}
