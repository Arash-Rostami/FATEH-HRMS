import { describe, expect, test } from 'vitest'
import registerAppStore from '../app.js'

describe('app store', () => {
    test('registers an empty state object under the "app" key — no logic to exercise', () => {
        let name, def
        registerAppStore({ store: (n, d) => { name = n; def = d } })

        expect(name).toBe('app')
        expect(def).toEqual({})
    })
})
