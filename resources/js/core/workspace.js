const PARK_TTL_MS = 2 * 60 * 1000
const LATE_MOUNT_MS = 5000
const KEEP_PREFIX = 'keep:'
const FIELD_SELECTOR = 'textarea, input:not([type=password],[type=file],[type=hidden],[type=checkbox],[type=radio],[type=submit],[type=button],[type=range],[type=color])'

const pool = new Map()
let parkEl = null
let ttlTimer = null
let restoring = false
let lastWrite
const touched = new Set()
const touchKey = (key, path) => `${storeKey(path)}|${key}`
const KEEP_OFF = 'keepoff'
const KEEP_OFF_MS = 10000

function move(el, to) {
    if (el.parentNode === to) return
    if (to.moveBefore && el.isConnected) {
        try {
            to.moveBefore(el, null)
            return
        } catch (e) {}
    }
    if (el.isConnected) el._ready = false
    to.appendChild(el)
}

function parkRoot() {
    if (!parkEl?.isConnected) {
        parkEl = document.createElement('div')
        parkEl.id = 'frame-park'
        parkEl.hidden = true
        document.documentElement.appendChild(parkEl)
    }
    return parkEl
}

function notify(f, state) {
    try {
        f.contentWindow?.postMessage({ ws: state }, location.origin)
    } catch (e) {}
}

export function initParkedHidden() {
    let parked = false
    const real = Object.getOwnPropertyDescriptor(Document.prototype, 'hidden')
    const realState = Object.getOwnPropertyDescriptor(Document.prototype, 'visibilityState')
    Object.defineProperty(document, 'hidden', {
        configurable: true,
        get: () => parked || real.get.call(document),
    })
    Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        get: () => (parked ? 'hidden' : realState.get.call(document)),
    })
    window.addEventListener('message', (e) => {
        if (e.origin !== location.origin || e.source !== window.parent) return
        const state = e.data?.ws
        if (state !== 'parked' && state !== 'active') return
        parked = state === 'parked'
        document.dispatchEvent(new Event('visibilitychange'))
    })
}

function armTtl() {
    clearTimeout(ttlTimer)
    ttlTimer = setTimeout(() => {
        pool.forEach((f, pane) => {
            if (f.parentNode !== parkEl) return
            f.remove()
            pool.delete(pane)
        })
    }, PARK_TTL_MS)
}

export function mountFrame(pane, slot, mod, onLoad) {
    clearTimeout(ttlTimer)
    let f = pool.get(pane)
    if (f && !f.isConnected) {
        pool.delete(pane)
        f = null
    }
    if (!f) {
        f = document.createElement('iframe')
        f.className = 'w-full h-full border-0'
        f.title = 'ماژول پین‌شده'
        f.addEventListener('load', () => {
            f._ready = true
            f._onLoad?.()
            if (f.parentNode === parkEl) notify(f, 'parked')
        })
        f.src = mod.src
        pool.set(pane, f)
    }
    f._onLoad = onLoad
    if (f.getAttribute('src') !== mod.src) {
        f._ready = false
        f.src = mod.src
    }
    if (f.parentNode !== slot) {
        move(f, slot)
        if (f._ready) notify(f, 'active')
    }
    if ([...pool.values()].some((x) => x.parentNode === parkEl)) armTtl()
    if (f._ready) onLoad?.()
}

export function drop(pane) {
    pool.get(pane)?.remove()
    pool.delete(pane)
}

export function park() {
    if (!pool.size) return
    if (!('moveBefore' in Element.prototype)) {
        pool.forEach((f) => f.remove())
        pool.clear()
        return
    }
    const root = parkRoot()
    pool.forEach((f) => {
        move(f, root)
        notify(f, 'parked')
    })
    armTtl()
}

let parkBooted = false

function initPark() {
    if (parkBooted || !window.Livewire) return
    parkBooted = true
    window.Livewire.hook('morph.removing', ({ el }) => {
        if (pool.size && [...pool.values()].some((f) => el.contains(f))) park()
    })
}

function fieldKey(el) {
    for (const a of el.attributes) {
        if (a.name.startsWith('wire:model')) return `m:${a.value}`
    }
    const k = el.getAttribute('x-model') || el.name || el.id
    return k ? `f:${k}` : null
}

const currentPath = () => location.pathname + location.search

function storeKey(path = currentPath()) {
    return `${KEEP_PREFIX}${document.documentElement.dataset.uid}:${path}`
}

const SENSITIVE_TYPE = /^(search|tel|number|email)$/
const SENSITIVE_AUTOCOMPLETE = /one-time-code|^cc-/
const SENSITIVE_NAME = /(^|[^a-z])(otp|token|card|cvv|cvc|pin|code|secret|pass|passwd|password)([^a-z]|$)/i

function eligible(el) {
    return !el.closest('[data-no-keep]')
        && !SENSITIVE_TYPE.test(el.type)
        && !SENSITIVE_AUTOCOMPLETE.test(el.autocomplete || '')
        && !SENSITIVE_NAME.test(fieldKey(el) || '')
}

export function saveFields(path = currentPath()) {
    try {
        if (Date.now() - Number(sessionStorage.getItem(KEEP_OFF) || 0) < KEEP_OFF_MS) return
    } catch (e) {}
    const sk = storeKey(path)
    let map = {}
    try {
        map = JSON.parse(sessionStorage.getItem(sk) || '{}') || {}
    } catch (e) {}
    document.querySelectorAll(FIELD_SELECTOR).forEach((el) => {
        const key = eligible(el) ? fieldKey(el) : null
        if (!key) return
        if (el.value) {
            map[key] = el.value
            touched.add(touchKey(key, path))
        } else if (touched.has(touchKey(key, path))) {
            delete map[key]
        }
    })
    const json = Object.keys(map).length ? JSON.stringify(map) : null
    const sig = `${sk}\n${json}`
    if (sig === lastWrite) return
    try {
        if (json) sessionStorage.setItem(sk, json)
        else sessionStorage.removeItem(sk)
        lastWrite = sig
    } catch (e) {}
}

export function restoreFields(root = document) {
    let map
    try {
        map = JSON.parse(sessionStorage.getItem(storeKey()) || 'null')
    } catch (e) {}
    if (!map) return
    const els = root.matches?.(FIELD_SELECTOR) ? [root] : []
    root.querySelectorAll?.(FIELD_SELECTOR).forEach((el) => els.push(el))
    els.forEach((el) => {
        if (el.value || el === document.activeElement || !eligible(el)) return
        const v = map[fieldKey(el)]
        if (v === undefined) return
        el.value = v
        touched.add(touchKey(fieldKey(el)))
        restoring = true
        el.dispatchEvent(new Event('input', { bubbles: true }))
        el.dispatchEvent(new Event('change', { bubbles: true }))
        restoring = false
    })
}

function sweepKeep() {
    lastWrite = undefined
    try {
        sessionStorage.setItem(KEEP_OFF, String(Date.now()))
        Object.keys(sessionStorage)
            .filter((k) => k.startsWith(KEEP_PREFIX))
            .forEach((k) => sessionStorage.removeItem(k))
    } catch (e) {}
}

function initKeep() {
    let saveTimer = null
    let quietUntil = 0
    let pending = []
    let frame = 0
    let observer = null
    let observerTimer = null
    let loadedAt = Date.now()

    const flush = () => {
        frame = 0
        const nodes = pending
        pending = []
        if (Date.now() < quietUntil) {
            pending = nodes.concat(pending)
            frame = setTimeout(flush, quietUntil - Date.now() + 10)
            return
        }
        if (Date.now() - loadedAt < LATE_MOUNT_MS) nodes.forEach((n) => n.isConnected && restoreFields(n))
    }
    const observe = () => {
        loadedAt = Date.now()
        observer?.disconnect()
        observer = new MutationObserver((records) => {
            records.forEach((r) => r.addedNodes.forEach((n) => n.nodeType === 1 && pending.push(n)))
            if (pending.length && !frame) frame = requestAnimationFrame(flush)
        })
        observer.observe(document.body, { childList: true, subtree: true })
        clearTimeout(observerTimer)
        observerTimer = setTimeout(() => observer?.disconnect(), LATE_MOUNT_MS)
    }

    document.addEventListener('input', (e) => {
        if (restoring) return
        const key = e.target.matches?.(FIELD_SELECTOR) && eligible(e.target) ? fieldKey(e.target) : null
        if (key && e.target.value) touched.add(touchKey(key))
        clearTimeout(saveTimer)
        saveTimer = setTimeout(() => saveFields(pagePath), 300)
    })
    let pagePath = currentPath()
    window.addEventListener('pagehide', () => saveFields(pagePath))
    document.addEventListener('livewire:navigating', () => {
        clearTimeout(saveTimer)
        saveFields(pagePath)
    })
    document.addEventListener('livewire:navigated', () => {
        pagePath = currentPath()
        observe()
        restoreFields()
    })
    observe()
    document.addEventListener('livewire:initialized', () => {
        restoreFields()
        window.Livewire.hook('commit', ({ succeed }) => succeed(() => {
            quietUntil = Date.now() + 300
            clearTimeout(saveTimer)
            saveTimer = setTimeout(saveFields, 50)
        }))
    })
}

let refreshing = null
let refreshedAt = 0
const FRESH_TOKEN_MS = 5000

async function fetchCsrf() {
    try {
        const res = await fetch(location.href, { credentials: 'same-origin', cache: 'no-store' })
        if (!res.ok || res.redirected) return false
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html')
        if (doc.documentElement.dataset.uid !== document.documentElement.dataset.uid) return false
        const read = (d) => d.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            ?? d.querySelector('[data-csrf]')?.getAttribute('data-csrf')
        const token = read(doc)
        const meta = document.querySelector('meta[name="csrf-token"]')
        const live = document.querySelector('[data-csrf]')
        if (!token || (!meta && !live) || token === read(document)) return false
        meta?.setAttribute('content', token)
        live?.setAttribute('data-csrf', token)
        refreshedAt = Date.now()
        return true
    } catch (e) {
        return false
    }
}

export const currentCsrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    ?? document.querySelector('[data-csrf]')?.getAttribute('data-csrf')

export function refreshCsrf() {
    if (Date.now() - refreshedAt < FRESH_TOKEN_MS) return Promise.resolve(true)
    refreshing ??= fetchCsrf().finally(() => {
        refreshing = null
    })
    return refreshing
}

function isLogoutClick(target) {
    for (let el = target; el?.attributes; el = el.parentElement) {
        if ([...el.attributes].some((a) => a.name.startsWith('wire:click') && a.value === 'logout')) return true
    }
    return false
}

export default function initWorkspace() {
    initPark()
    document.addEventListener('livewire:init', initPark)
    document.addEventListener('livewire:navigating', park)
    document.addEventListener('click', (e) => {
        if (isLogoutClick(e.target)) sweepKeep()
    })
    if (window.self !== window.top) initParkedHidden()
    if (document.documentElement.dataset.uid && window.self !== window.top) initKeep()
}

initWorkspace()
