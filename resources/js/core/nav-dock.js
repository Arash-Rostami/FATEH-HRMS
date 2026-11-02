document.addEventListener('alpine:initialized', () => {
    if (window.__navDockBound) return
    window.__navDockBound = true

    const SEL = '.fi-sidebar-item-btn, .fi-sidebar-group-dropdown-trigger-btn'
    const MIN_KEY = 'nav-dock-min'
    const CLASS_MIN = 'nav-dock-min'
    const root = document.documentElement

    const setMin = (min) => {
        root.classList.toggle(CLASS_MIN, min)

        try {
            localStorage.setItem(MIN_KEY, String(min))
        } catch {
        }
    }

    const restoreMin = () => {
        try {
            root.classList.toggle(CLASS_MIN, localStorage.getItem(MIN_KEY) === 'true')
        } catch {
        }
    }

    const ARROW_STEP = 0.6

    const dockNav = () => document.querySelector('.fi-sidebar-nav')

    const updateArrows = () => {
        const nav = dockNav()

        if (!nav) return

        const max = nav.scrollWidth - nav.clientWidth
        const scrolled = Math.abs(nav.scrollLeft)

        document.querySelectorAll('.dock-arrow').forEach((arrow) => {
            arrow.hidden = arrow.dataset.edge === 'start'
                ? !(max > 1 && scrolled > 1)
                : !(max > 1 && scrolled < max - 1)
        })
    }

    const bindArrows = () => {
        const nav = dockNav()

        if (nav && !nav._dockArrowsBound) {
            nav._dockArrowsBound = true
            nav.addEventListener('scroll', updateArrows, { passive: true })
        }

        updateArrows()
    }

    const applyNavDock = () => {
        restoreMin()

        if (!root.classList.contains('nav-dock-bottom')) return

        window.Alpine.store('sidebar')?.close()
        bindArrows()

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                document.querySelectorAll(SEL).forEach((el) => el._tippy?.setProps({ placement: 'top' }))
                document.querySelector('.fi-sidebar-item.fi-active')?.scrollIntoView?.({ inline: 'center', block: 'nearest' })
                updateArrows()
            })
        })
    }

    applyNavDock()
    document.addEventListener('livewire:navigated', applyNavDock)
    window.addEventListener('resize', updateArrows)

    document.addEventListener('wheel', (e) => {
        if (e.defaultPrevented || !root.classList.contains('nav-dock-bottom') || root.classList.contains(CLASS_MIN)) return

        const nav = e.target.closest?.('.fi-sidebar-nav')

        if (!nav || nav.scrollWidth <= nav.clientWidth || Math.abs(e.deltaX) >= Math.abs(e.deltaY)) return

        e.preventDefault()
        nav.scrollLeft += getComputedStyle(nav).direction === 'rtl' ? -e.deltaY : e.deltaY
    }, { passive: false })

    document.addEventListener('click', (e) => {
        if (!root.classList.contains('nav-dock-bottom')) return

        const arrow = e.target.closest('.dock-arrow')

        if (arrow) {
            const nav = dockNav()

            if (e.defaultPrevented || !nav) return

            e.preventDefault()

            const sign = (arrow.dataset.edge === 'end' ? 1 : -1) * (getComputedStyle(nav).direction === 'rtl' ? -1 : 1)
            const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

            return nav.scrollBy?.({ left: sign * nav.clientWidth * ARROW_STEP, behavior: reduced ? 'auto' : 'smooth' })
        }

        if (e.target.closest('.dock-min')) return setMin(true)

        if (root.classList.contains(CLASS_MIN) && e.target.closest('.fi-sidebar')) setMin(false)
    })
})
