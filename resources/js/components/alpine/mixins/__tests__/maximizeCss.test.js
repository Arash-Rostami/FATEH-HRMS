import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { expect, test } from 'vitest'

test('structural .page-wrapper entrance animations never fill forward, or they trap position:fixed maximize overlays', () => {
    const css = readFileSync(resolve(process.cwd(), 'resources/css/core/dashboard.css'), 'utf8')
    const blocks = css.match(/\.page-wrapper[^{}]*\{[^}]*animation[^}]*\}/g) ?? []

    expect(blocks.length).toBeGreaterThan(0)
    for (const block of blocks) {
        expect(block).not.toMatch(/\b(both|forwards)\b/)
    }
})
