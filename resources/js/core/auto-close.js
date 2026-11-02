const CLOSE_ON_METHODS = new Set(['applyTableFilters', 'applyTableColumnManager'])
const SELECTOR_DROPDOWN = '[x-data="filamentDropdown"]'

document.addEventListener('alpine:init', () => {
    if (window.__tablePanelAutocloseBound) return
    window.__tablePanelAutocloseBound = true

    window.Livewire.hook('commit', ({ commit, succeed }) => {
        if (!commit.calls?.some((call) => CLOSE_ON_METHODS.has(call.method))) return

        succeed(() => {
            for (const el of document.querySelectorAll(SELECTOR_DROPDOWN)) {
                window.Alpine?.$data(el)?.close?.()
            }
        })
    })
})
