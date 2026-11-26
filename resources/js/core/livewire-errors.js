import { currentCsrf, refreshCsrf } from './workspace.js'

let booted = false
let reloading419 = false

function register() {
    if (booted || !window.Livewire) return
    booted = true

    window.Livewire.hook('request', ({ fail }) => {
        const sent = currentCsrf()
        fail(({ status, content, preventDefault }) => {
            if (status === 419) {
                preventDefault()
                if (reloading419) return
                const retry = () => window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'دوباره تلاش کنید', type: 'error' },
                }))
                if (sent && currentCsrf() !== sent) {
                    retry()
                    return
                }
                refreshCsrf().then((ok) => {
                    if (ok) {
                        retry()
                        return
                    }
                    if (reloading419) return
                    reloading419 = true
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'نشست شما به پایان رسید؛ در حال بارگذاری مجدد…', type: 'error' },
                    }))
                    setTimeout(() => window.location.reload(), 1200)
                })
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

window.addEventListener('form-invalid', ({ detail }) => {
    const field = detail?.field
    if (!field) return
    setTimeout(() => {
        const el = document.getElementById(field) ?? document.getElementById(field.replace(/\.\d+$/, ''))
        if (!el) return
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
        el.focus({ preventScroll: true })
    }, 0)
})

export default function initLivewireErrors() {
    register()
    document.addEventListener('livewire:init', register)
}

initLivewireErrors()
