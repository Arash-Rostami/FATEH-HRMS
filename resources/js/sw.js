import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching'
import { clientsClaim } from 'workbox-core'
import { registerRoute } from 'workbox-routing'

self.skipWaiting()
clientsClaim()

cleanupOutdatedCaches()
precacheAndRoute(self.__WB_MANIFEST)

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open('offline-v1').then((cache) => cache.add('/offline')).catch(() => {}))
})

registerRoute(
    ({ request }) => request.mode === 'navigate' && request.destination === 'document',
    ({ request }) => fetch(request).catch(async () => (await caches.match('/offline', { cacheName: 'offline-v1' })) || Response.error())
)

console.log('✅ Service Worker Loaded & Ready')

const safeUrl = (u) => {
    try {
        const url = new URL(u, self.location.origin)
        if (url.origin === self.location.origin) return url.href
    } catch (e) {}
    return self.location.origin + '/'
}

self.addEventListener('push', (event) => {
    event.waitUntil((async () => {
        let data
        try {
            data = event.data.json()
        } catch (e) {
            return
        }
        if (!data || (data.type !== 'chat' && data.type !== 'confirm')) return

        const wins = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        const visible = wins.some((c) => c.visibilityState === 'visible')
        const target = new URL(safeUrl(data.url)).pathname
        const onThatPage = wins.some((c) => c.visibilityState === 'visible' && new URL(c.url).pathname === target)

        if (data.type === 'confirm') {
            wins.forEach((c) => c.postMessage({ type: 'push-confirmed', endpoint: data.endpoint }))
            if (visible) return
        } else {
            wins.forEach((c) => c.postMessage({ type: 'chat-push', endpoint: data.endpoint, scope: data.scope }))
            if (onThatPage) return
        }

        await self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/favicon.ico',
            tag: data.tag || 'chat',
            renotify: true,
            dir: 'rtl',
            lang: 'fa',
            data: { url: safeUrl(data.url) }
        })

        if (data.type === 'chat' && !visible && Number.isFinite(data.badge)) {
            try {
                const cache = await caches.open('badge-v1')
                const stored = await cache.match('/__badge')
                const counts = stored ? await stored.json() : {}
                counts[data.tag || 'chat'] = data.badge
                const total = Object.values(counts).reduce((sum, n) => sum + (Number(n) || 0), 0)
                await cache.put('/__badge', new Response(JSON.stringify(counts), { headers: { 'Content-Type': 'application/json' } }))
                await (total > 0 ? self.navigator.setAppBadge(total) : self.navigator.clearAppBadge())
            } catch (e) {}
        }
    })())
})

self.addEventListener('notificationclick', (event) => {
    event.notification.close()
    try {
        self.navigator.clearAppBadge?.()
        caches.delete('badge-v1').catch(() => {})
    } catch (e) {}
    const url = safeUrl(event.notification.data?.url)

    event.waitUntil((async () => {
        const wins = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        const target = new URL(url).pathname
        const c = wins.find((w) => w.frameType !== 'nested' && new URL(w.url).pathname === target)

        if (!c) return self.clients.openWindow(url)

        try {
            await c.focus()
            await c.navigate(url)
        } catch (e) {
            await self.clients.openWindow(url)
        }
    })())
})
