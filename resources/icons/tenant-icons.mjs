import {existsSync, readFileSync, readdirSync} from 'node:fs';
import {join} from 'node:path';

const OUTPUTS = [['icon-512', 512], ['icon-192', 192], ['apple-touch-icon', 180], ['icon-maskable-512', 512], ['icon-maskable-192', 192]];
const WHITE = '#ffffff';
const DEFAULT_HEADER_BG = '#08191e';
const MARK = 410;
const MASKABLE_MARK = 289;
const CANVAS = 512;
const WIDE = 1.2;
const MARK_SHARE = 1.1;

function headerBg(root, tenant) {
    try {
        const source = readFileSync(join(root, 'config', 'tenants.php'), 'utf8');
        const start = source.indexOf(`\n    '${tenant}' => [`);
        const block = start < 0 ? '' : source.slice(start + 1).split(/\n    '/)[0];
        return block.match(/'header_bg'\s*=>\s*'(#[0-9a-fA-F]{3,8})'/)?.[1] ?? DEFAULT_HEADER_BG;
    } catch {
        return DEFAULT_HEADER_BG;
    }
}

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
        const bases = {};

        for (const [name, size] of missing) {
            const maskable = name.includes('maskable');
            const mark = maskable ? MASKABLE_MARK : MARK;
            bases[mark] ??= await sharp({create: {width: CANVAS, height: CANVAS, channels: 3, background: maskable ? headerBg(root, tenant.name) : WHITE}})
                .composite([{input: await sharp(flat).resize(mark, mark, {fit: 'contain', background: WHITE}).png().toBuffer(), gravity: 'centre'}])
                .png({compressionLevel: 9})
                .toBuffer();
            await sharp(bases[mark]).resize(size, size).png({compressionLevel: 9}).toFile(join(dir, `${name}.png`));
            made++;
        }
        console.log(`tenant icons: ${tenant.name} -> ${missing.map(([n]) => n).join(', ')}`);
    }

    if (!made) console.log('tenant icons up to date');
}
