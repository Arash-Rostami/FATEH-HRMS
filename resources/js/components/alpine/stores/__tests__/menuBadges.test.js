import { describe, expect, test } from 'vitest'
import registerMenuBadges from '../menuBadges.js'

function fresh() {
    let captured
    registerMenuBadges({ store: (name, def) => { captured = def } })
    return captured
}

describe('menuBadges', () => {
    test('falls back to the server value until a key is set', () => {
        const s = fresh()
        expect(s.on('contacts-controller', true)).toBe(true)
        expect(s.on('contacts-controller', false)).toBe(false)
        expect(s.on('unknown', undefined)).toBe(false)
    })

    test('set overrides the server value in both directions', () => {
        const s = fresh()
        s.set('contacts-controller', false)
        expect(s.on('contacts-controller', true)).toBe(false)
        s.set('contacts-controller', true)
        expect(s.on('contacts-controller', false)).toBe(true)
    })

    test('setting one key leaves other keys on their server value', () => {
        const s = fresh()
        s.set('contacts-controller', false)
        expect(s.on('tasks-controller', true)).toBe(true)
    })

    test('registers under the menuBadges name', () => {
        let name
        registerMenuBadges({ store: (n) => { name = n } })
        expect(name).toBe('menuBadges')
    })
})
