let animationId, resizeId, themeObserver, resizeHandler, visibilityHandler;
let w, h, colors, visible, drops, rivers, gradCache;

const RIVER_COLORS = ['255,200,80','140,190,80','220,90,70','190,170,255','160,220,255','255,240,180'];

export default {
    init() {
        this.destroy();

        const canvas = document.createElement('canvas');
        canvas.id = 'interactive-background';
        Object.assign(canvas.style, {
            position: 'fixed', top: 0, left: 0, zIndex: -10,
            pointerEvents: 'none', opacity: '0',
            transition: 'opacity 2.5s ease-in-out'
        });
        document.body.appendChild(canvas);
        setTimeout(() => { canvas.style.opacity = '1'; }, 50);

        const ctx = canvas.getContext('2d', {alpha: true, desynchronized: true});
        const dpr = Math.min(2, devicePixelRatio || 1);

        colors = { primary: '78,95,102', secondary: '83,95,112', tertiary: '107,87,120', container: '209,231,239' };
        visible = true;
        drops = [];
        rivers = [];
        gradCache = new Map();

        const getThemeColors = () => {
            const s = getComputedStyle(document.documentElement);
            const parseHex = (varName, defaultHex) => {
                let hex = s.getPropertyValue(varName).trim().replace('#', '');
                if (!hex) hex = defaultHex;
                if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                if (!hex || isNaN(parseInt(hex, 16))) return defaultHex;
                const n = parseInt(hex, 16);
                return `${n >> 16 & 255},${n >> 8 & 255},${n & 255}`;
            };
            return {
                primary:   parseHex('--md-sys-color-primary',           '4e5f66'),
                secondary: parseHex('--md-sys-color-secondary',         '535f70'),
                tertiary:  parseHex('--md-sys-color-tertiary',          '6b5778'),
                container: parseHex('--md-sys-color-primary-container', 'd1e7ef')
            };
        };

        themeObserver = new MutationObserver(() => { colors = getThemeColors(); gradCache.clear(); });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});
        colors = getThemeColors();

        const resize = () => {
            w = innerWidth; h = innerHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
            drops = []; rivers = []; gradCache.clear();
            const n = Math.floor((w * h) / 8000);
            for (let i = 0; i < n; i++) drops.push({ x: Math.random() * w, y: Math.random() * h, r: Math.random() * 2 + 0.5, vy: 0, vx: 0, isBottom: false });
        };

        resizeHandler = () => { clearTimeout(resizeId); resizeId = setTimeout(resize, 150); };
        window.addEventListener('resize', resizeHandler);

        visibilityHandler = () => { visible = document.visibilityState === 'visible'; if (visible) draw(); };
        document.addEventListener('visibilitychange', visibilityHandler);

        const spawnRiver = (d) => {
            rivers.push({
                pts: [{x: d.x, y: d.y}],
                vx: d.vx * 0.4 + (Math.random() - 0.5) * 0.3,
                vy: Math.max(d.vy, d.r * 0.25),
                w:  d.r * 0.55,
                color: RIVER_COLORS[Math.random() * RIVER_COLORS.length | 0],
                life: 0.9 + Math.random() * 0.3
            });
            d.r = Math.max(d.r * 0.72, 1.5);
        };

        const drawRivers = () => {
            for (let i = rivers.length - 1; i >= 0; i--) {
                const rv = rivers[i];
                const last = rv.pts[rv.pts.length - 1];

                rv.vx += (Math.random() - 0.5) * 0.18;
                rv.vx *= 0.88;
                rv.vy += 0.05;
                rv.vy = Math.min(rv.vy, 8);
                rv.pts.push({x: last.x + rv.vx, y: last.y + rv.vy});
                if (rv.pts.length > 28) rv.pts.shift();

                rv.life -= 0.006;
                rv.w *= 0.998;

                if (last.y > h + 30 || rv.life <= 0 || rv.w < 0.25) { rivers.splice(i, 1); continue; }
                if (rv.pts.length < 3) continue;

                const alpha = rv.life * 0.38;

                ctx.beginPath();
                ctx.moveTo(rv.pts[0].x, rv.pts[0].y);
                for (let p = 1; p < rv.pts.length - 1; p++) {
                    ctx.quadraticCurveTo(rv.pts[p].x, rv.pts[p].y,
                        (rv.pts[p].x + rv.pts[p+1].x) * 0.5,
                        (rv.pts[p].y + rv.pts[p+1].y) * 0.5);
                }
                ctx.lineCap = 'round';

                ctx.lineWidth = rv.w;
                ctx.strokeStyle = `rgba(${rv.color},${alpha})`;
                ctx.stroke();

                ctx.lineWidth = rv.w * 0.28;
                ctx.strokeStyle = `rgba(255,255,255,${rv.life * 0.55})`;
                ctx.stroke();
            }
        };

        const draw = () => {
            if (!visible) { animationId = requestAnimationFrame(draw); return; }

            ctx.clearRect(0, 0, w, h);

            const maxDrops = (w * h) / 4000;
            if (Math.random() < 0.4 && drops.length < maxDrops) {
                drops.push({ x: Math.random() * w, y: Math.random() * h, r: Math.random() * 1.5 + 0.5, vy: 0, vx: 0, isBottom: false });
            }

            // spatial grid for O(n) merge
            const cellSize = 12;
            const cols = Math.ceil(w / cellSize);
            const grid = new Map();
            for (let i = 0; i < drops.length; i++) {
                const k = (Math.floor(drops[i].x / cellSize) | 0) + (Math.floor(drops[i].y / cellSize) | 0) * cols;
                if (!grid.has(k)) grid.set(k, []);
                grid.get(k).push(i);
            }

            let bottomCount = 0;
            for (let i = 0; i < drops.length; i++) if (drops[i].isBottom) bottomCount++;

            for (let i = 0; i < drops.length; i++) {
                const d = drops[i];

                if (d.isBottom) {
                    d.vx *= 0.95;
                    d.x += d.vx;
                    d.r -= 0.008;
                    if (d.r < 0.5 || d.x < -20 || d.x > w + 20) { drops.splice(i--, 1); continue; }
                } else {
                    if (d.vy === 0 && d.r > 2.8 && Math.random() < 0.018) d.vy = d.r * 0.2;

                    if (d.vy > 0) {
                        d.vx += (Math.random() - 0.5) * 0.3;
                        d.vx *= 0.85;
                        d.x += d.vx;
                        d.vy += 0.06;
                        if (d.vy > d.r * 1.2) d.vy = d.r * 1.2;
                        d.y += d.vy;

                        if (Math.random() < 0.3) {
                            drops.push({ x: d.x - d.vx * 2, y: d.y - d.r * 0.8, r: Math.random() * 0.6 + 0.3, vy: 0, vx: 0, isBottom: false });
                            d.r -= 0.015;
                        }

                        if (d.r > 3.8 && Math.random() < 0.055) spawnRiver(d);
                    }

                    if (d.y >= h - d.r) {
                        if (Math.random() < 0.35 && bottomCount < maxDrops * 0.15) {
                            d.isBottom = true; d.y = h; d.vy = 0;
                            d.vx = (Math.random() - 0.5) * 1.2; d.flatten = 1; bottomCount++;
                        } else { drops.splice(i--, 1); continue; }
                    }
                }

                // grid-based merge
                const cx = Math.floor(d.x / cellSize), cy = Math.floor(d.y / cellSize);
                for (let dy2 = -1; dy2 <= 1; dy2++) {
                    for (let dx2 = -1; dx2 <= 1; dx2++) {
                        const cell = grid.get((cx + dx2) + (cy + dy2) * cols);
                        if (!cell) continue;
                        for (let ni = 0; ni < cell.length; ni++) {
                            const j = cell[ni];
                            if (j <= i) continue;
                            const d2 = drops[j];
                            if (!d2) continue;
                            const ddx = d.x - d2.x, ddy = d.y - d2.y;
                            if (Math.abs(ddx) > d.r + d2.r || Math.abs(ddy) > d.r + d2.r) continue;
                            if (Math.sqrt(ddx*ddx + ddy*ddy) < d.r + d2.r) {
                                const totalArea = Math.PI * (d.r * d.r + d2.r * d2.r);
                                d.r = Math.sqrt(totalArea / Math.PI);
                                d.x = (d.x * d.r + d2.x * d2.r) / (d.r + d2.r);
                                d.y = (d.y * d.r + d2.y * d2.r) / (d.r + d2.r);
                                if (d2.vy > d.vy) d.vy = d2.vy;
                                if (d.isBottom || d2.isBottom) {
                                    d.isBottom = true; d.y = h; d.vy = 0;
                                    d.flatten = Math.max(d.flatten || 1, d2.flatten || 1);
                                } else if (d.vy === 0 && d.r > 4) {
                                    d.vy = d.r * 0.2;
                                }
                                if (d.r > 4.2 && Math.random() < 0.32) spawnRiver(d);
                                drops.splice(j, 1);
                                // update grid entry to avoid stale refs
                                const k2 = (Math.floor(d2.x / cellSize) | 0) + (Math.floor(d2.y / cellSize) | 0) * cols;
                                const gc = grid.get(k2);
                                if (gc) { const gi = gc.indexOf(j); if (gi !== -1) gc.splice(gi, 1); }
                                if (j < i) i--;
                            }
                        }
                    }
                }

                // render drop with cached gradient
                ctx.beginPath();
                let rx = d.r, ry = d.r, renderY = d.y;

                if (d.isBottom) {
                    d.flatten = d.flatten ? d.flatten + (2.8 - d.flatten) * 0.08 : 1;
                    rx = d.r * d.flatten; ry = d.r / Math.sqrt(d.flatten);
                    renderY = h - ry * 0.4;
                    ctx.ellipse(d.x, renderY, rx, ry, 0, 0, Math.PI * 2);
                } else if (d.vy > 0) {
                    ry = d.r * (1 + d.vy * 0.15);
                    ctx.ellipse(d.x, d.y, rx * 0.85, ry, 0, 0, Math.PI * 2);
                } else {
                    ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
                }

                const gKey = d.r * 10 | 0;
                let grad = gradCache.get(gKey);
                if (!grad) {
                    const gr = Math.max(rx, ry);
                    grad = ctx.createRadialGradient(-rx * 0.25, -ry * 0.25, 0, 0, 0, gr);
                    grad.addColorStop(0,    `rgba(255,255,255,0.85)`);
                    grad.addColorStop(0.2,  `rgba(255,255,255,0.15)`);
                    grad.addColorStop(0.75, `rgba(${colors.primary},0.2)`);
                    grad.addColorStop(1,    `rgba(${colors.tertiary},0.45)`);
                    gradCache.set(gKey, grad);
                }
                ctx.save();
                ctx.translate(d.x, renderY);
                ctx.fillStyle = grad;
                ctx.fill();
                ctx.restore();
            }

            drawRivers();
            animationId = requestAnimationFrame(draw);
        };

        resize();
        animationId = requestAnimationFrame(draw);
    },

    destroy() {
        if (animationId) cancelAnimationFrame(animationId);
        if (resizeId) clearTimeout(resizeId);
        if (themeObserver) themeObserver.disconnect();
        if (resizeHandler) window.removeEventListener('resize', resizeHandler);
        if (visibilityHandler) document.removeEventListener('visibilitychange', visibilityHandler);
        document.getElementById('interactive-background')?.remove();
    }
}
