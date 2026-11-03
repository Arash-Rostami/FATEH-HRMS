import {existsSync, mkdirSync, readdirSync, readFileSync, statSync, writeFileSync, copyFileSync} from 'node:fs';
import {createHash} from 'node:crypto';
import {dirname, extname, join, relative} from 'node:path';
import {fileURLToPath} from 'node:url';
import * as fontkit from 'fontkit';
import subsetFont from 'subset-font';
import {buildLogos} from './logos.mjs';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..', '..');
const pkg = join(root, 'node_modules', 'material-symbols');
const fullFont = join(pkg, 'material-symbols-rounded.woff2');
const outDir = join(here, 'generated');
const safelistFile = join(here, 'safelist.json');
const cssPath = join(outDir, 'rounded.css');
const subsetPath = join(outDir, 'material-symbols-rounded.woff2');
const report = process.argv.includes('--report');

const ROOTS = ['resources/views', 'resources/js', 'resources/assets/js', 'app', 'lang', 'config', 'routes'];
const EXTENSIONS = new Set(['.php', '.js', '.mjs', '.ts', '.vue', '.html', '.json']);
const SKIP = new Set(['node_modules', 'vendor', 'storage', 'public', '__tests__', '.git']);
const ADMIN = ['app/Filament', 'resources/views/filament'];
const BASE = 'abcdefghijklmnopqrstuvwxyz0123456789_';
const SENTINELS = ['expand_more', 'auto_awesome', 'keyboard_arrow_down', 'close', 'search'];

const full = readFileSync(fullFont);
const font = fontkit.create(full);
const privateUse = new Map();
for (const cp of font.characterSet) {
    if ((cp >= 0xE000 && cp <= 0xF8FF) || cp >= 0xF0000) privateUse.set(font.glyphForCodePoint(cp).id, cp);
}

const verdicts = new Map();
function iconGlyph(token) {
    if (!verdicts.has(token)) {
        let found = null;
        if (token.length <= 40) {
            const run = font.layout(token);
            if (run.glyphs.length === 1 && privateUse.has(run.glyphs[0].id)) found = privateUse.get(run.glyphs[0].id);
        }
        verdicts.set(token, found);
    }
    return verdicts.get(token);
}

function* walk(dir) {
    for (const entry of readdirSync(dir, {withFileTypes: true})) {
        if (SKIP.has(entry.name)) continue;
        const path = join(dir, entry.name);
        if (entry.isDirectory()) yield* walk(path);
        else if (EXTENSIONS.has(extname(entry.name)) && !entry.name.includes('.generated.')) yield path;
    }
}

const used = new Set();
const adminSide = new Set();
const userSide = new Set();
const junk = new Set();
for (const base of ROOTS) {
    const abs = join(root, base);
    if (!existsSync(abs)) continue;
    for (const file of walk(abs)) {
        const rel = relative(root, file).replaceAll('\\', '/');
        const text = readFileSync(file, 'utf8');
        const side = ADMIN.some((p) => rel.startsWith(p)) ? adminSide : userSide;
        for (const [token] of text.matchAll(/[a-z0-9]+(?:_[a-z0-9]+)*/g)) {
            if (iconGlyph(token) === null) continue;
            used.add(token);
            side.add(token);
        }
        if (rel.endsWith('.php')) {
            for (const m of text.matchAll(/material-symbols-rounded[^>]*>\s*([a-z0-9_]+)\s*</g)) {
                if (iconGlyph(m[1]) !== null) continue;
                junk.add(`${m[1]} (${rel})`);
            }
        }
    }
}

const scanned = new Set(used);
const safelist = existsSync(safelistFile) ? JSON.parse(readFileSync(safelistFile, 'utf8')) : [];
const invalid = safelist.filter((name) => iconGlyph(name) === null);
for (const name of safelist) if (iconGlyph(name) !== null) used.add(name);

const names = [...used].sort();
console.log(`icons used: ${names.length} (user-side ${userSide.size}, admin-side ${adminSide.size}, admin-only ${[...adminSide].filter((n) => !userSide.has(n)).length}, safelist ${safelist.length})`);
if (invalid.length) console.warn(`safelist entries that are not icons: ${invalid.join(', ')}`);
if (junk.size) console.warn(`static text inside icon spans that is not an icon: ${[...junk].slice(0, 15).join(', ')}`);
const missingSentinel = SENTINELS.filter((name) => !scanned.has(name));
if (missingSentinel.length) {
    console.error(`FAIL: sentinel icons missing from the scan: ${missingSentinel.join(', ')}`);
    process.exit(1);
}
if (report) process.exit(0);

await buildLogos(root);

const stamp = createHash('sha1')
    .update(names.join(','))
    .update(String(statSync(fullFont).size))
    .update(readFileSync(fileURLToPath(import.meta.url)))
    .digest('hex');
const version = stamp.slice(0, 8);

mkdirSync(outDir, {recursive: true});
if (existsSync(cssPath) && existsSync(subsetPath) && existsSync(join(outDir, 'material-symbols-rounded-full.woff2')) && readFileSync(cssPath, 'utf8').startsWith(`/* icons ${version} */`)) {
    console.log('icon font up to date');
    process.exit(0);
}

const kept = names.map((name) => [name, iconGlyph(name)]);
const text = BASE + kept.map(([, cp]) => String.fromCodePoint(cp)).join('');
const out = await subsetFont(full, text, {
    targetFormat: 'woff2',
    noLayoutClosure: true,
    variationAxes: {GRAD: 0, opsz: 24},
});

const check = fontkit.create(Buffer.from(out));
const broken = names.filter((name) => check.layout(name).glyphs.length !== 1);
if (broken.length) {
    console.error(`FAIL: icons lost in the subset: ${broken.join(', ')}`);
    process.exit(1);
}

const packageCss = readFileSync(join(pkg, 'rounded.css'), 'utf8');
const fullFace = `@font-face{font-family:"Material Symbols Rounded Full";font-style:normal;font-weight:100 700;font-display:block;src:url("./material-symbols-rounded-full.woff2") format("woff2")}\n`;

writeFileSync(subsetPath, out);
copyFileSync(fullFont, join(outDir, 'material-symbols-rounded-full.woff2'));
writeFileSync(cssPath, `/* icons ${version} */\n${packageCss}${fullFace}.ms-full.material-symbols-rounded{font-family:"Material Symbols Rounded Full"}\n`);

console.log(`subset font: ${(out.length / 1024).toFixed(0)} KB (full ${(full.length / 1024).toFixed(0)} KB), ${names.length} icons verified, version ${version}`);
