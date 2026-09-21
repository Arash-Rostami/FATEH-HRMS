let booted = false

function register() {
    if (booted || !window.Livewire) return
    booted = true

    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            if (status === 422 || status === 419) return

            preventDefault()

            const message = content?.message || 'خطایی رخ داد؛ دوباره تلاش کنید'

            console.error('Livewire request failed:', message)
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message, type: 'error' },
            }))
        })
    })
}

export default function initLivewireErrors() {
    register()
    document.addEventListener('livewire:init', register)
}

initLivewireErrors()