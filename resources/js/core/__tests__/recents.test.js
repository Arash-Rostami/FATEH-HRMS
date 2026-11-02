import { describe, expect, test, vi, beforeEach, afterEach } from 'vitest'

const config = {
    path: 'admin',
    map: {
        tickets: { label: 'تیکت‌ها', group: 'پشتیبانی' },
        'f-a-q-s': { label: 'پرسش‌ها', group: 'محتوا' },
    },
}

const load = async () => {
    vi.resetModules()
    return import('../recents.js')
}

const flushFrames = async () => {
    await new Promise((r) => setTimeout(r, 0))
    await new Promise((r) => setTimeout(r, 0))
}

beforeEach(() => {
    localStorage.clear()
    document.body.innerHTML = ''
    window.__adminRecents = config
    global.requestAnimationFrame = (cb) => setTimeout(cb, 0)
    history.pushState({}, '', '/')
})

afterEach(() => {
    delete window.__adminRecents
})

describe('recents — which admin pages count as a record (2026-11-02)', () => {
    test('a record view or edit page of a known resource is matched, with or without a trailing slash', async () => {
        const { matchRecord } = await load()

        expect(matchRecord('/admin/tickets/42/edit', config)).toEqual({ slug: 'tickets', id: '42', label: 'تیکت‌ها' })
        expect(matchRecord('/admin/tickets/42', config)).toMatchObject({ id: '42' })
        expect(matchRecord('/admin/tickets/42/edit/', config)).toMatchObject({ id: '42' })
        expect(matchRecord('/admin/f-a-q-s/7', config)).toMatchObject({ slug: 'f-a-q-s' })
    })

    test('list, create, unknown resource, other panel and missing config are all ignored', async () => {
        const { matchRecord } = await load()

        expect(matchRecord('/admin/tickets', config)).toBeNull()
        expect(matchRecord('/admin/tickets/create', config)).toBeNull()
        expect(matchRecord('/admin/unknown/5/edit', config)).toBeNull()
        expect(matchRecord('/other/tickets/5/edit', config)).toBeNull()
        expect(matchRecord('/admin/tickets/5/edit', null)).toBeNull()
    })
})

describe('recents — storage', () => {
    test('a visit goes to the front, the same url is never listed twice, and the list is capped', async () => {
        const { recordVisit, readRecents, MAX_RECENTS } = await load()
        const record = { slug: 'tickets', id: '1', label: 'تیکت‌ها' }

        recordVisit(record, 'A', '/admin/tickets/1/edit')
        recordVisit({ ...record, id: '2' }, 'B', '/admin/tickets/2/edit')
        recordVisit(record, 'A again', '/admin/tickets/1/edit')

        expect(readRecents().map((e) => e.url)).toEqual(['/admin/tickets/1/edit', '/admin/tickets/2/edit'])
        expect(readRecents()[0].title).toBe('A again')

        for (let i = 0; i < MAX_RECENTS + 5; i++) recordVisit({ ...record, id: String(i + 10) }, 't', `/admin/tickets/${i + 10}/edit`)
        expect(readRecents()).toHaveLength(MAX_RECENTS)
    })

    test('corrupt or non-array storage reads as an empty list', async () => {
        const { readRecents, RECENTS_KEY } = await load()

        localStorage.setItem(RECENTS_KEY, '{not json')
        expect(readRecents()).toEqual([])

        localStorage.setItem(RECENTS_KEY, '"text"')
        expect(readRecents()).toEqual([])
    })

    test('recording a visit announces recents-updated so open popovers refresh', async () => {
        const { recordVisit } = await load()
        const seen = vi.fn()
        window.addEventListener('recents-updated', seen)

        recordVisit({ slug: 'tickets', id: '1', label: 'x' }, 't', '/admin/tickets/1/edit')

        expect(seen).toHaveBeenCalledTimes(1)
        window.removeEventListener('recents-updated', seen)
    })
})

describe('recents — capture on page load and navigation', () => {
    test('landing on a record page stores it with the page heading as its title', async () => {
        history.pushState({}, '', '/admin/tickets/9/edit')
        document.body.innerHTML = '<h1 class="fi-header-heading">  ویرایش تیکت ۹  </h1>'

        const { readRecents } = await load()
        await flushFrames()

        expect(readRecents()).toEqual([
            { slug: 'tickets', id: '9', label: 'تیکت‌ها', title: 'ویرایش تیکت ۹', url: '/admin/tickets/9/edit' },
        ])
    })

    test('landing on a list page stores nothing', async () => {
        history.pushState({}, '', '/admin/tickets')

        const { readRecents } = await load()
        await flushFrames()

        expect(readRecents()).toEqual([])
    })

    test('a Livewire navigation to a record page is captured too', async () => {
        const { readRecents } = await load()
        history.pushState({}, '', '/admin/f-a-q-s/3/edit')

        document.dispatchEvent(new Event('livewire:navigated'))
        await flushFrames()

        expect(readRecents().map((e) => e.url)).toEqual(['/admin/f-a-q-s/3/edit'])
    })
})
