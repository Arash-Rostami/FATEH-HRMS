// @vitest-environment node
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import { existsSync, mkdirSync, mkdtempSync, readdirSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import sharp from 'sharp'
import { buildLogos } from '../logos.mjs'

sharp.cache(false)

let root
let dir

const white = { r: 255, g: 255, b: 255, alpha: 1 }

async function logoOnWhite(file, { hole = false } = {}) {
    const layers = [{ input: await sharp({ create: { width: 120, height: 120, channels: 4, background: { r: 20, g: 20, b: 20, alpha: 1 } } }).png().toBuffer(), left: 40, top: 40 }]
    if (hole) layers.push({ input: await sharp({ create: { width: 30, height: 30, channels: 4, background: white } }).png().toBuffer(), left: 85, top: 85 })
    await sharp({ create: { width: 200, height: 200, channels: 4, background: white } }).composite(layers).png().toFile(join(dir, file))
}

async function pixels(file) {
    const { data, info } = await sharp(join(dir, 'optimized', file)).ensureAlpha().raw().toBuffer({ resolveWithObject: true })
    return { data, info }
}

beforeEach(() => {
    root = mkdtempSync(join(tmpdir(), 'logos-'))
    dir = join(root, 'resources', 'assets', 'img', 'acme', 'sibling')
    mkdirSync(dir, { recursive: true })
    vi.spyOn(console, 'log').mockImplementation(() => {})
})

afterEach(() => {
    vi.restoreAllMocks()
    rmSync(root, { recursive: true, force: true, maxRetries: 20, retryDelay: 100 })
})

describe('sibling logo build', () => {
    test('every logo becomes the same 160x160 webp, whatever its source size or extension', async () => {
        await logoOnWhite('wide.png')
        await sharp({ create: { width: 90, height: 40, channels: 3, background: { r: 200, g: 30, b: 30 } } }).jpeg().toFile(join(dir, 'Small.JPG'))

        await buildLogos(root)

        expect(readdirSync(join(dir, 'optimized')).sort()).toEqual(['small.webp', 'wide.webp'])
        for (const file of ['wide.webp', 'small.webp']) {
            const meta = await sharp(join(dir, 'optimized', file)).metadata()
            expect([meta.format, meta.width, meta.height]).toEqual(['webp', 160, 160])
        }
    })

    test('a flat white background becomes transparent but whites inside the logo stay opaque', async () => {
        await logoOnWhite('mark.png', { hole: true })

        await buildLogos(root)

        const { data, info } = await pixels('mark.webp')
        const at = (x, y) => Array.from(data.slice((y * info.width + x) * info.channels, (y * info.width + x) * info.channels + 4))
        expect(at(0, 0)[3]).toBe(0)
        expect(at(info.width - 1, info.height - 1)[3]).toBe(0)
        expect(at(80, 80)[3]).toBeGreaterThan(240)

        let opaqueWhite = 0
        for (let i = 0; i < data.length; i += 4) if (data[i] > 230 && data[i + 1] > 230 && data[i + 2] > 230 && data[i + 3] > 200) opaqueWhite++
        expect(opaqueWhite).toBeGreaterThan(20)
    })

    test('stale outputs are pruned and unchanged sources are not rebuilt', async () => {
        await logoOnWhite('keep.png')
        mkdirSync(join(dir, 'optimized'), { recursive: true })
        writeFileSync(join(dir, 'optimized', 'orphan.webp'), 'x')

        await buildLogos(root)
        expect(existsSync(join(dir, 'optimized', 'orphan.webp'))).toBe(false)
        expect(existsSync(join(dir, 'optimized', 'keep.webp'))).toBe(true)

        console.log.mockClear()
        await buildLogos(root)
        expect(console.log).toHaveBeenCalledWith('sibling logos up to date')
    })

    test('two logos that differ only by extension or case fail the build', async () => {
        await logoOnWhite('twin.png')
        await sharp({ create: { width: 50, height: 50, channels: 3, background: { r: 1, g: 2, b: 3 } } }).jpeg().toFile(join(dir, 'TWIN.jpg'))
        const exit = vi.spyOn(process, 'exit').mockImplementation(() => { throw new Error('exit') })
        vi.spyOn(console, 'error').mockImplementation(() => {})

        await expect(buildLogos(root)).rejects.toThrow('exit')
        expect(exit).toHaveBeenCalledWith(1)
    })
})
