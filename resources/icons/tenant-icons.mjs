import {existsSync, readdirSync} from 'node:fs';
import {join} from 'node:path';

const OUTPUTS = [['icon-512', 512], ['icon-192', 192], ['apple-touch-icon', 180]];
const WHITE = '#ffffff';
const MARK = 410;
const CANVAS = 512;
const WIDE = 1.2;
const MARK_SHARE = 1.1;

export async function buildTenantIcons(root) {
    const imgRoot = join(root, 'resources', 'assets', 'img');
    if (!existsSync(imgRoot)) return;

    let sharp;
    try {
        sharp = (await import('sharp')).default;
    } catch {
        console.warn('tenant icons: sharp is not installed, skipped');
        return;
    }

    let made = 0;
    for (const tenant of readdirSync(imgRoot, {withFileTypes: true})) {
        if (!tenant.isDirectory()) continue;
        const dir = join(imgRoot, tenant.name);
        const files = readdirSync(dir);
        const logo = files.find((f) => /^logo\.(png|jpe?g|webp)$/i.test(f));
        const missing = OUTPUTS.filter(([name]) => !files.some((f) => f.startsWith(`${name}.`)));
        if (!logo || !missing.length) continue;

        let image = sharp(join(dir, logo));
        const {width, height} = await image.metadata();
        if (width > height * WIDE) {
            image = image.extract({left: 0, top: 0, width: Math.round(Math.min(width, height * MARK_SHARE)), height});
        }

        const flat = await image.flatten({background: WHITE}).trim({background: WHITE, threshold: 12}).toBuffer();
        const inner = await sharp(flat).resize(MARK, MARK, {fit: 'contain', background: WHITE}).png().toBuffer();
        const base = await sharp({create: {width: CANVAS, height: CANVAS, channels: 3, background: WHITE}})
            .composite([{input: inner, gravity: 'centre'}])
            .png({compressionLevel: 9})
            .toBuffer();

        for (const [name, size] of missing) {
            await sharp(base).resize(size, size).png({compressionLevel: 9}).toFile(join(dir, `${name}.png`));
            made++;
        }
        console.log(`tenant icons: ${tenant.name} -> ${missing.map(([n]) => n).join(', ')}`);
    }

    if (!made) console.log('tenant icons up to date');
}
