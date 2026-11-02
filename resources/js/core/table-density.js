const KEY = 'table_density'
const CLASS_COMPACT = 'table-density-compact'
const root = document.documentElement

const isCompact = () => {
    try {
        return localStorage.getItem(KEY) === 'compact'
    } catch {
        return false
    }
}

document.addEventListener('alpine:init', () => {
    if (window.__tableDensityBound) return
    window.__tableDensityBound = true

    const Alpine = window.Alpine

    Alpine.store('tableDensityCompact', isCompact())

    window.setTableDensity = (mode) => {
        const compact = mode === 'compact'

        try {
            localStorage.setItem(KEY, compact ? 'compact' : 'comfortable')
        } catch {
        }

        Alpine.store('tableDensityCompact', compact)
    }

    const apply = () => root.classList.toggle(CLASS_COMPACT, Alpine.store('tableDensityCompact'))

    Alpine.effect(apply)
    document.addEventListener('livewire:navigated', apply)
})
