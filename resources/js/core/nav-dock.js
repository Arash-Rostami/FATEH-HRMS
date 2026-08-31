document.addEventListener('alpine:initialized', () => {
    if (window.__navDockBound) return
    window.__navDockBound = true

    const SEL = '.fi-sidebar-item-btn, .fi-sidebar-group-dropdown-trigger-btn'

    const applyNavDock = () => {
        if (!document.documentElement.classList.contains('nav-dock-bottom')) return

        window.Alpine.store('sidebar')?.close()

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                document.querySelectorAll(SEL).forEach((el) => el._tippy?.setProps({ placement: 'top' }))
            })
        })
    }

    applyNavDock()
    document.addEventListener('livewire:navigated', applyNavDock)
})
