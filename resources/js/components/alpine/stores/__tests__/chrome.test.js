import { describe, expect, test } from 'vitest'
import registerChromeStore from '../chrome.js'

describe('chrome store', () => {
    test('registers forceHidden=false under the "chrome" key — no methods to exercise', () => {
        let name, def
        registerChromeStore({ store: (n, d) => { name = n; def = d } })

        expect(name).toBe('chrome')
        expect(def).toEqual({ forceHidden: false })
    })
})
