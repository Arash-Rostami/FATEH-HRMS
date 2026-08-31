const root = document.documentElement

const applyPinnedClasses = () => {
    root.classList.toggle('topbar-pinned', localStorage.getItem('topbar-pinned') !== 'false')
    root.classList.toggle('nav-dock-bottom', localStorage.getItem('nav-dock-bottom') === 'true')
}

applyPinnedClasses()

document.addEventListener('livewire:navigated', () => {
    applyPinnedClasses()
    if (root.classList.contains('topbar-pinned')) return

    root.classList.add('topbar-force-hidden')
    setTimeout(() => root.classList.remove('topbar-force-hidden'), 400)
})
