const LAYERS = [
    {maxDist: 110, speed: 0.35, minSz: 0.9, maxSz: 2.2, opacity: 0.40, lw: 0.9, weight: 0.65},
    {maxDist: 68, speed: 0.12, minSz: 0.5, maxSz: 1.2, opacity: 0.20, lw: 0.6, weight: 0.35},
];

const CFG = {density: 0.00010, maxN: 300, mouseR: 150, attract: 0.0009, fps: 60};
const CONNECT_SQ = 0.55;

let rafId, themeObserver, resizeHandler, mouseMoveHandler, mouseLeaveHandler, visibilityHandler;

export default {
    init() {
        this.destroy();

        const canvas = document.createElement('canvas');
        canvas.id = 'interactive-background';
        Object.assign(canvas.style, {position: 'fixed', top: 0, left: 0, zIndex: -10, pointerEvents: 'none'});
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d', {alpha: true});
        const DPR = Math.min(2, devicePixelRatio || 1);
        const rand = (a, b) => a + Math.random() * (b - a);

        const MAX = CFG.maxN;
        const px = new Float32Array(MAX), py = new Float32Array(MAX);
        const pvx = new Float32Array(MAX), pvy = new Float32Array(MAX);
        const psz = new Float32Array(MAX), pb = new Float32Array(MAX);
        const psd = new Float32Array(MAX), pli = new Uint8Array(MAX);
        const pop = new Float32Array(MAX);
        let N = 0, W = 0, H = 0, rgb = '102,126,234';

        let cellSz = 0, cols = 0, rows = 0, cells = [];
        const gi = (x, y) => {
            const c = x / cellSz | 0, r = y / cellSz | 0;
            return (c < 0 || r < 0 || c >= cols || r >= rows) ? -1 : c + r * cols;
        };

        function getThemeRGB() {
            const s = getComputedStyle(document.documentElement);
            let v = s.getPropertyValue('--main-rgb').trim();
            if (v) return v;
            let h = s.getPropertyValue('--md-sys-color-primary').trim().replace('#', '');
            if (!h) return '102,126,234';
            if (h.length === 3) h = h.split('').map(c => c + c).join('');
            const n = parseInt(h, 16);
            return `${n >> 16 & 255},${n >> 8 & 255},${n & 255}`;
        }

        themeObserver = new MutationObserver(() => {
            rgb = getThemeRGB();
        });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});

        function build() {
            cellSz = Math.max(...LAYERS.map(l => l.maxDist)) * 1.15;
            cols = Math.ceil(W / cellSz);
            rows = Math.ceil(H / cellSz);
            cells = Array.from({length: cols * rows}, () => []);
            rgb = getThemeRGB();
            N = Math.min(MAX, Math.max(20, W * H * CFG.density | 0));

            for (let i = 0; i < N; i++) {
                const frac = i / N;
                let li = LAYERS.length - 1, acc = 0;
                for (let l = 0; l < LAYERS.length; l++) {
                    acc += LAYERS[l].weight;
                    if (frac < acc) {
                        li = l;
                        break;
                    }
                }
                const L = LAYERS[li];
                const sz = rand(L.minSz, L.maxSz);
                const a = rand(0, Math.PI * 2), spd = rand(0.2, 1) * L.speed;
                px[i] = rand(sz, W - sz);
                py[i] = rand(sz, H - sz);
                pvx[i] = Math.cos(a) * spd;
                pvy[i] = Math.sin(a) * spd;
                pb[i] = psz[i] = sz;
                psd[i] = rand(0, Math.PI * 2);
                pli[i] = li;
                pop[i] = L.opacity;
            }
        }

        function resize() {
            W = Math.max(300, innerWidth);
            H = Math.max(200, innerHeight);
            canvas.style.width = W + 'px';
            canvas.style.height = H + 'px';
            canvas.width = W * DPR | 0;
            canvas.height = H * DPR | 0;
            ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
            ctx.lineCap = 'round';
            build();
        }

        const debounce = (fn, t) => {
            let id;
            return () => {
                clearTimeout(id);
                id = setTimeout(fn, t);
            };
        };

        resizeHandler = debounce(resize, 120);
        window.addEventListener('resize', resizeHandler);

        const mouse = {x: 0, y: 0, sx: 0, sy: 0, on: false};

        mouseMoveHandler = e => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
            mouse.on = true;
        };
        window.addEventListener('mousemove', mouseMoveHandler);

        mouseLeaveHandler = () => {
            mouse.on = false;
        };
        window.addEventListener('mouseleave', mouseLeaveHandler);

        let visible = true, last = 0, accum = 0;
        const mspf = 1000 / CFG.fps, mr2 = CFG.mouseR ** 2;

        visibilityHandler = () => {
            visible = document.visibilityState === 'visible';
        };
        document.addEventListener('visibilitychange', visibilityHandler);

        const LBUF = MAX * 9;
        const LX1 = new Float32Array(LBUF), LY1 = new Float32Array(LBUF);
        const LX2 = new Float32Array(LBUF), LY2 = new Float32Array(LBUF);
        const LW = new Float32Array(LBUF);

        function frame(now) {
            rafId = requestAnimationFrame(frame);
            if (!visible) {
                last = now;
                return;
            }
            accum += Math.min(40, now - last);
            last = now;
            if (accum < mspf) return;
            const dt = accum / 16.667;
            accum = 0;

            mouse.sx += (mouse.x - mouse.sx) * 0.14;
            mouse.sy += (mouse.y - mouse.sy) * 0.14;
            const {sx: mx, sy: my, on: mOn} = mouse;

            ctx.clearRect(0, 0, W, H);

            for (let i = 0; i < cells.length; i++) cells[i].length = 0;
            for (let i = 0; i < N; i++) {
                const g = gi(px[i], py[i]);
                if (g >= 0) cells[g].push(i);
            }

            let nL = 0;

            for (let i = 0; i < N; i++) {
                const L = LAYERS[pli[i]];
                psz[i] = pb[i] * (1 + Math.sin(psd[i] + now * 0.0016) * 0.12);
                px[i] += pvx[i] * dt;
                py[i] += pvy[i] * dt;
                const pad = psz[i] * 2;
                if (px[i] < pad) {
                    px[i] = pad;
                    pvx[i] = Math.abs(pvx[i]);
                } else if (px[i] > W - pad) {
                    px[i] = W - pad;
                    pvx[i] = -Math.abs(pvx[i]);
                }
                if (py[i] < pad) {
                    py[i] = pad;
                    pvy[i] = Math.abs(pvy[i]);
                } else if (py[i] > H - pad) {
                    py[i] = H - pad;
                    pvy[i] = -Math.abs(pvy[i]);
                }

                if (mOn) {
                    const dx = mx - px[i], dy = my - py[i], d2 = dx * dx + dy * dy;
                    if (d2 < mr2) {
                        const f = (1 - d2 / mr2) * CFG.attract * dt;
                        pvx[i] += dx * f;
                        pvy[i] += dy * f;
                    }
                }
                pvx[i] *= 0.998;
                pvy[i] *= 0.998;

                const cx = px[i] / cellSz | 0, cy = py[i] / cellSz | 0;
                for (let ox = -1; ox <= 1; ox++) for (let oy = -1; oy <= 1; oy++) {
                    const nc = (cx + ox) + (cy + oy) * cols;
                    if (nc < 0 || nc >= cells.length) continue;
                    const cell = cells[nc];
                    for (let k = 0; k < cell.length; k++) {
                        const j = cell[k];
                        if (j <= i) continue;
                        const dx = px[i] - px[j], dy = py[i] - py[j], d2 = dx * dx + dy * dy;
                        const md = Math.min(L.maxDist, LAYERS[pli[j]].maxDist);
                        const th = md * md * CONNECT_SQ;
                        if (d2 < th && nL < LBUF) {
                            const t = 1 - d2 / th;
                            LX1[nL] = px[i];
                            LY1[nL] = py[i];
                            LX2[nL] = px[j];
                            LY2[nL] = py[j];
                            LW[nL] = (L.lw + LAYERS[pli[j]].lw) * 0.5 * (0.3 + t * 0.7);
                            nL++;
                        }
                    }
                }

                if (mOn) {
                    const dx = px[i] - mx, dy = py[i] - my, d2 = dx * dx + dy * dy;
                    if (d2 < mr2 && nL < LBUF) {
                        const t = 1 - d2 / mr2;
                        LX1[nL] = px[i];
                        LY1[nL] = py[i];
                        LX2[nL] = mx;
                        LY2[nL] = my;
                        LW[nL] = L.lw * 0.85 * (0.2 + t * 0.8);
                        nL++;
                    }
                }
            }

            ctx.save();
            ctx.globalCompositeOperation = 'lighter';
            ctx.strokeStyle = `rgba(${rgb},0.045)`;
            for (let pass = 0; pass < 2; pass++) {
                ctx.lineWidth = pass === 0 ? 0.55 : 1.0;
                ctx.beginPath();
                for (let k = 0; k < nL; k++) {
                    if (((LW[k] < 0.75) ? 0 : 1) !== pass) continue;
                    ctx.moveTo(LX1[k], LY1[k]);
                    ctx.lineTo(LX2[k], LY2[k]);
                }
                ctx.stroke();
            }
            ctx.restore();

            for (let li = LAYERS.length - 1; li >= 0; li--) {
                const L = LAYERS[li];
                ctx.save();
                ctx.globalCompositeOperation = 'lighter';
                ctx.fillStyle = `rgba(${rgb},${L.opacity})`;
                ctx.beginPath();
                for (let i = 0; i < N; i++) {
                    if (pli[i] !== li) continue;
                    ctx.moveTo(px[i] + psz[i], py[i]);
                    ctx.arc(px[i], py[i], psz[i], 0, Math.PI * 2);
                }
                ctx.fill();
                ctx.restore();
            }
        }

        resize();
        rafId = requestAnimationFrame(t => {
            last = t;
            rafId = requestAnimationFrame(frame);
        });
    },

    destroy() {
        if (rafId) cancelAnimationFrame(rafId);
        if (themeObserver) themeObserver.disconnect();
        if (resizeHandler) window.removeEventListener('resize', resizeHandler);
        if (mouseMoveHandler) window.removeEventListener('mousemove', mouseMoveHandler);
        if (mouseLeaveHandler) window.removeEventListener('mouseleave', mouseLeaveHandler);
        if (visibilityHandler) document.removeEventListener('visibilitychange', visibilityHandler);
        document.getElementById('interactive-background')?.remove();
    }
}
