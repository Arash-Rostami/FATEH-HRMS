import {copyFileSync, existsSync, mkdirSync, readdirSync, rmSync, statSync} from 'node:fs';
import {extname, join, parse} from 'node:path';

const SOURCE_EXTENSIONS = new Set(['.png', '.jpg', '.jpeg', '.webp', '.gif', '.avif', '.svg']);
const BOX = 144;
const PAD = 8;
const TRANSPARENT = {r: 0, g: 0, b: 0, alpha: 0};

const LIGHT = 200;
const NEUTRAL = 24;
const PAPER_MARGIN = 45;
const DENSITY = 192;

const percentile = (histogram, total, share) => {
    const target = Math.floor(total * share);
    let seen = 0;
    for (let value = 0; value < 256; value++) {
        seen += histogram[value];
        if (seen > target) return value;
    }
    return 255;
};

async function withoutWhiteBackground(sharp, input) {
    const {data, info} = await sharp(input, {density: DENSITY}).ensureAlpha().raw().toBuffer({resolveWithObject: true});
    const {width, height} = info;
    const px = (x, y) => (y * width + x) * 4;
    const luminance = (o) => 0.299 * data[o] + 0.587 * data[o + 1] + 0.114 * data[o + 2];
    const neutral = (o) => Math.max(data[o], data[o + 1], data[o + 2]) - Math.min(data[o], data[o + 1], data[o + 2]) <= NEUTRAL;

    const corners = [[0, 0], [width - 1, 0], [0, height - 1], [width - 1, height - 1]].map(([x, y]) => px(x, y));
    if (!corners.every((o) => data[o + 3] >= 250 && luminance(o) >= LIGHT && neutral(o))) return null;

    const cornerShades = corners.map(luminance).sort((a, b) => a - b);
    const threshold = Math.max(LIGHT, (cornerShades[1] + cornerShades[2]) / 2 - PAPER_MARGIN);
    const isWhite = (o) => data[o + 3] >= 250 && luminance(o) >= threshold && neutral(o);

    const background = new Uint8Array(width * height);
    const queue = new Int32Array(width * height);
    let tail = 0;
    const push = (x, y) => {
        const i = y * width + x;
        if (!background[i] && isWhite(i * 4)) {
            background[i] = 1;
            queue[tail++] = i;
        }
    };
    for (let x = 0; x < width; x++) { push(x, 0); push(x, height - 1); }
    for (let y = 0; y < height; y++) { push(0, y); push(width - 1, y); }
    for (let head = 0; head < tail; head++) {
        const i = queue[head];
        const x = i % width;
        const y = (i - x) / width;
        if (x > 0) push(x - 1, y);
        if (x < width - 1) push(x + 1, y);
        if (y > 0) push(x, y - 1);
        if (y < height - 1) push(x, y + 1);
    }

    const paperHistogram = new Uint32Array(256);
    const solidHistogram = new Uint32Array(256);
    let paperCount = 0;
    let solidCount = 0;
    for (let i = 0; i < width * height; i++) {
        const shade = Math.round(luminance(i * 4));
        if (background[i]) { paperHistogram[shade]++; paperCount++; } else { solidHistogram[shade]++; solidCount++; }
    }
    const paper = paperCount ? percentile(paperHistogram, paperCount, 0.5) : 255;
    const foreground = solidCount ? percentile(solidHistogram, solidCount, 0.15) : 0;
    const span = Math.max(1, paper - foreground);

    const touchesBackground = (x, y) => {
        for (let dy = -1; dy <= 1; dy++) for (let dx = -1; dx <= 1; dx++) {
            const nx = x + dx;
            const ny = y + dy;
            if (nx >= 0 && ny >= 0 && nx < width && ny < height && background[ny * width + nx]) return true;
        }
        return false;
    };

    for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
        const i = y * width + x;
        const o = i * 4;
        if (background[i]) { data[o + 3] = 0; continue; }
        if (!touchesBackground(x, y)) continue;
        const alpha = Math.min(1, Math.max(0, (paper - luminance(o)) / span));
        if (alpha >= 1) continue;
        for (let c = 0; c < 3; c++) data[o + c] = alpha > 0 ? Math.min(255, Math.max(0, Math.round((data[o + c] - paper * (1 - alpha)) / alpha))) : 0;
        data[o + 3] = Math.round(alpha * 255);
    }

    return sharp(data, {raw: {width, height, channels: 4}}).png().toBuffer();
}

export async function buildLogos(root) {
    const imgRoot = join(root, 'resources', 'assets', 'img');
    if (!existsSync(imgRoot)) return;

    let sharp = null;
    try {
        sharp = (await import('sharp')).default;
    } catch {
        console.warn('sibling logos: sharp is not installed, the originals are copied unoptimized');
    }

    let made = 0;
    for (const tenant of readdirSync(imgRoot, {withFileTypes: true})) {
        const dir = join(imgRoot, tenant.name, 'sibling');
        if (!tenant.isDirectory() || !existsSync(dir)) continue;

        const outDir = join(dir, 'optimized');
        const expected = new Set();
        const slugs = new Set();

        for (const file of readdirSync(dir)) {
            if (!SOURCE_EXTENSIONS.has(extname(file).toLowerCase())) continue;

            const source = join(dir, file);
            const slug = parse(file).name.toLowerCase();
            if (slugs.has(slug)) {
                console.error(`FAIL: two logos share the name "${slug}" in ${tenant.name}/sibling (differ only by extension or case)`);
                process.exit(1);
            }
            slugs.add(slug);

            const targetName = `${slug}.${sharp ? 'webp' : extname(file).toLowerCase().slice(1)}`;
            expected.add(targetName);
            const target = join(outDir, targetName);
            if (existsSync(target) && statSync(target).mtimeMs >= statSync(source).mtimeMs) continue;

            mkdirSync(outDir, {recursive: true});
            if (!sharp) {
                copyFileSync(source, target);
                made++;
                continue;
            }
            const keyed = await withoutWhiteBackground(sharp, source).catch(() => null);
            const trimmed = await sharp(keyed ?? source, {density: DENSITY}).trim({threshold: 8}).toBuffer().catch(() => null);

            await sharp(trimmed ?? keyed ?? source, {density: DENSITY})
                .resize({width: BOX, height: BOX, fit: 'contain', background: TRANSPARENT, kernel: 'lanczos3'})
                .extend({top: PAD, bottom: PAD, left: PAD, right: PAD, background: TRANSPARENT})
                .webp({quality: 88, alphaQuality: 100, effort: 6, smartSubsample: true})
                .toFile(target);

            made++;
            console.log(`sibling logo: ${tenant.name}/${file} -> ${statSync(target).size} bytes (${BOX + PAD * 2}x${BOX + PAD * 2} webp)`);
        }

        if (existsSync(outDir)) {
            for (const entry of readdirSync(outDir)) if (!expected.has(entry)) rmSync(join(outDir, entry), {recursive: true, force: true});
        }
    }

    if (!made) console.log('sibling logos up to date');
}
