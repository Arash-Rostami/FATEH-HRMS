let booted = false
let reloading419 = false

function register() {
    if (booted || !window.Livewire) return
    booted = true

    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            if (status === 419) {
                preventDefault()
                if (reloading419) return
                reloading419 = true
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'نشست شما به پایان رسید؛ در حال بارگذاری مجدد…', type: 'error' },
                }))
                setTimeout(() => window.location.reload(), 1200)
                return
            }
            if (status === 422) return

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
