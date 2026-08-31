export default function registerModuleData(entries) {
    const register = () => {
        for (const name of Object.keys(entries)) window.Alpine.data(name, entries[name])
    }
    window.Alpine ? register() : document.addEventListener('alpine:init', register, { once: true })
}

const prefetched = new Set()

export function prefetchModule(name) {
    const url = window.__moduleChunks?.[name]
    if (!url || prefetched.has(url)) return
    prefetched.add(url)

    const link = document.createElement('link')
    link.rel = 'prefetch'
    link.href = url
    link.as = 'script'
    link.crossOrigin = 'anonymous'
    document.head.appendChild(link)
}

export function initModulePrefetch() {
    const handler = ({ target }) => {
        const el = target?.nodeType === 1 && target.closest('[data-module]')
        if (el) prefetchModule(el.dataset.module)
    }
    document.addEventListener('pointerover', handler, { passive: true })
    document.addEventListener('focusin', handler, { passive: true })
}
