export default function particle(enabled) {
    return () => {
        if (/(profile|tasks)/.test(window.location.href)) {
            let canvas = document.getElementById('interactive-background');
            if (canvas) canvas.remove();

            if (!canvas) {
                if (canvas) canvas.remove();
                canvas = document.createElement('canvas');
                canvas.id = 'interactive-background';
                document.body.appendChild(canvas);
            }
            const ctx = canvas.getContext('2d', {alpha: true});

            const CONFIG = {
                density: 0.00011,
                maxParticles: 380,
                layers: [
                    {
                        scale: 1.0,
                        maxDist: 120,
                        speed: 0.35,
                        size: [0.9, 2.2],
                        opacity: 0.82,
                        lineWidth: 1.0,
                        glow: 8
                    },
                    {
                        scale: 0.55,
                        maxDist: 78,
                        speed: 0.12,
                        size: [0.5, 1.2],
                        opacity: 0.45,
                        lineWidth: 0.8,
                        glow: 6
                    },
                ],
                mouseRadiusBase: 160,
                attraction: 0.0009,
                avoidOverlap: true,
                avoidForce: 0.06,
                frameRateLimit: 60,
            };

            const DPR = Math.max(1, window.devicePixelRatio || 1);
            const rand = (a = 0, b = 1) => a + Math.random() * (b - a);
            const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
            const hexToRgb = (hex) => {
                if (!hex) return null;
                hex = hex.replace('#', '');
                if (hex.length === 3) hex = hex.split('').map(s => s + s).join('');
                const n = parseInt(hex, 16);
                return `${(n >> 16) & 255},${(n >> 8) & 255},${n & 255}`;
            };

            function getThemeRGB() {
                const root = getComputedStyle(document.documentElement);
                let rgb = root.getPropertyValue('--main-rgb').trim();
                if (rgb) return rgb;
                const hex = root.getPropertyValue('--md-sys-color-primary').trim();
                if (hex) {
                    const parsed = hexToRgb(hex);
                    if (parsed) return parsed;
                }
                return '102,126,234';
            }

            let parent = canvas.parentElement || document.body;

            function resizeCanvas() {
                const w = Math.max(300, parent.clientWidth || window.innerWidth);
                const h = Math.max(200, parent.clientHeight || window.innerHeight);
                canvas.style.width = w + 'px';
                canvas.style.height = h + 'px';
                canvas.width = Math.round(w * DPR);
                canvas.height = Math.round(h * DPR);
                ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
                ctx.lineCap = 'round';
                buildParticles();
            }

            const debounce = (fn, t = 150) => {
                let id;
                return (...a) => {
                    clearTimeout(id);
                    id = setTimeout(() => fn(...a), t);
                };
            };
            window.addEventListener('resize', debounce(resizeCanvas, 120));

            let grid = null;
            let cellSize = 140;

            function prepareGrid(width, height) {
                cellSize = Math.max(...CONFIG.layers.map(l => l.maxDist)) * 1.1;
                const cols = Math.ceil(width / cellSize);
                const rows = Math.ceil(height / cellSize);
                grid = {cols, rows, cells: new Array(cols * rows)};
                for (let i = 0; i < grid.cells.length; i++) grid.cells[i] = [];
            }

            function cellIndex(x, y) {
                const cx = Math.floor(x / cellSize);
                const cy = Math.floor(y / cellSize);
                if (cx < 0 || cy < 0 || cx >= grid.cols || cy >= grid.rows) return -1;
                return cx + cy * grid.cols;
            }

            let particleIdSeed = 0;

            class Particle {
                constructor(layer, x, y, vx, vy, size, opacity) {
                    this.id = particleIdSeed++;
                    this.layer = layer;
                    this.x = x;
                    this.y = y;
                    this.vx = vx;
                    this.vy = vy;
                    this.baseSize = size;
                    this.size = size;
                    this.opacity = opacity;
                    this.seed = Math.random() * Math.PI * 2;
                }

                step(dt, t, mouseTarget, bounds) {
                    const osc = Math.sin(this.seed + t * 0.0016) * 0.12;
                    this.size = this.baseSize * (1 + osc);
                    this.x += this.vx * dt;
                    this.y += this.vy * dt;
                    const pad = this.size * 2;
                    if (this.x < pad) {
                        this.x = pad;
                        this.vx = Math.abs(this.vx);
                    } else if (this.x > bounds.w - pad) {
                        this.x = bounds.w - pad;
                        this.vx = -Math.abs(this.vx);
                    }
                    if (this.y < pad) {
                        this.y = pad;
                        this.vy = Math.abs(this.vy);
                    } else if (this.y > bounds.h - pad) {
                        this.y = bounds.h - pad;
                        this.vy = -Math.abs(this.vy);
                    }
                    if (mouseTarget.active) {
                        const dx = mouseTarget.x - this.x;
                        const dy = mouseTarget.y - this.y;
                        const dist2 = dx * dx + dy * dy;
                        const r = (mouseTarget.radius * mouseTarget.radius);
                        if (dist2 < r) {
                            const factor = (1 - dist2 / r) * CONFIG.attraction * (this.layer.scale * 1.6);
                            this.vx += dx * factor * dt;
                            this.vy += dy * factor * dt;
                        }
                    }
                    this.vx *= 0.998;
                    this.vy *= 0.998;
                }

                draw(ctx, color) {
                    ctx.beginPath();
                    ctx.fillStyle = `rgba(${color}, ${this.opacity})`;
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
            }

            let particles = [];

            function buildParticles() {
                particles.length = 0;
                particleIdSeed = 0;
                const w = canvas.clientWidth || (canvas.width / DPR);
                const h = canvas.clientHeight || (canvas.height / DPR);
                prepareGrid(w, h);
                const area = w * h;
                let totalDesired = Math.round(area * CONFIG.density);
                totalDesired = clamp(totalDesired, 20, CONFIG.maxParticles);
                const totalScale = CONFIG.layers.reduce((s, l) => s + l.scale, 0);
                for (let li = 0; li < CONFIG.layers.length; li++) {
                    const layer = CONFIG.layers[li];
                    const count = Math.round(totalDesired * (layer.scale / totalScale));
                    for (let i = 0; i < count; i++) {
                        const size = rand(layer.size[0], layer.size[1]);
                        const x = rand(size, w - size);
                        const y = rand(size, h - size);
                        const angle = rand(0, Math.PI * 2);
                        const speed = rand(0.2, 1) * layer.speed;
                        const vx = Math.cos(angle) * speed;
                        const vy = Math.sin(angle) * speed;
                        particles.push(new Particle(layer, x, y, vx, vy, size, layer.opacity));
                    }
                }
                for (let i = 0; i < grid.cells.length; i++) grid.cells[i].length = 0;
                for (const p of particles) {
                    const idx = cellIndex(p.x, p.y);
                    if (idx >= 0) grid.cells[idx].push(p);
                }
            }

            const mouse = {x: 0, y: 0, active: false, smoothedX: 0, smoothedY: 0, radius: CONFIG.mouseRadiusBase};
            const smoothFactor = 0.14;


            let visible = true;
            document.addEventListener('visibilitychange', () => {
                visible = document.visibilityState === 'visible';
            });

            let lastTime = performance.now();
            let accum = 0;

            function frame(now) {
                if (!visible) {
                    lastTime = now;
                    requestAnimationFrame(frame);
                    return;
                }
                const dtMs = Math.min(40, now - lastTime);
                lastTime = now;
                accum += dtMs;
                const msPerFrame = 1000 / CONFIG.frameRateLimit;
                if (accum < msPerFrame) {
                    requestAnimationFrame(frame);
                    return;
                }
                const dt = accum / 16.6667;
                accum = 0;
                mouse.smoothedX += (mouse.x - mouse.smoothedX) * smoothFactor;
                mouse.smoothedY += (mouse.y - mouse.smoothedY) * smoothFactor;
                const mouseTarget = {
                    x: mouse.smoothedX,
                    y: mouse.smoothedY,
                    active: mouse.active,
                    radius: CONFIG.mouseRadiusBase * (DPR > 1 ? 1.05 : 1.0)
                };

                ctx.clearRect(0, 0, canvas.width / DPR, canvas.height / DPR);
                const w = canvas.clientWidth || (canvas.width / DPR);
                const h = canvas.clientHeight || (canvas.height / DPR);
                const grad = ctx.createLinearGradient(0, 0, w, h);
                grad.addColorStop(0, 'rgba(8,10,20,0.02)');
                grad.addColorStop(1, 'rgba(8,10,20,0.04)');
                ctx.fillStyle = grad;
                ctx.fillRect(0, 0, w, h);
                for (let i = 0; i < grid.cells.length; i++) grid.cells[i].length = 0;
                for (const p of particles) {
                    const idx = cellIndex(p.x, p.y);
                    if (idx >= 0) grid.cells[idx].push(p);
                }
                const rgb = getThemeRGB();
                ctx.save();
                ctx.globalCompositeOperation = 'lighter';
                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.step(dt, now, mouseTarget, {w, h});
                    const cx = Math.floor(p.x / cellSize);
                    const cy = Math.floor(p.y / cellSize);
                    const maxForLayer = p.layer.maxDist;
                    const max2 = maxForLayer * maxForLayer;
                    for (let ox = -1; ox <= 1; ox++) {
                        for (let oy = -1; oy <= 1; oy++) {
                            const ncx = cx + ox;
                            const ncy = cy + oy;
                            if (ncx < 0 || ncy < 0 || ncx >= grid.cols || ncy >= grid.rows) continue;
                            const cell = grid.cells[ncx + ncy * grid.cols];
                            for (let j = 0; j < cell.length; j++) {
                                const q = cell[j];
                                if (q.id <= p.id) continue;
                                const dx = p.x - q.x;
                                const dy = p.y - q.y;
                                const dist2 = dx * dx + dy * dy;
                                const pairMax = Math.min(p.layer.maxDist, q.layer.maxDist);
                                const pairMax2 = pairMax * pairMax;
                                if (dist2 < pairMax2) {
                                    const t = 1 - dist2 / pairMax2;
                                    ctx.beginPath();
                                    ctx.lineWidth = (p.layer.lineWidth + q.layer.lineWidth) * 0.5 * (0.35 + t * 0.65);
                                    ctx.strokeStyle = `rgba(${rgb}, ${0.06 * t * (p.layer.opacity + q.layer.opacity)})`;
                                    ctx.moveTo(p.x, p.y);
                                    ctx.lineTo(q.x, q.y);
                                    ctx.stroke();
                                }
                            }
                        }
                    }
                    if (mouseTarget.active) {
                        const dxm = p.x - mouseTarget.x;
                        const dym = p.y - mouseTarget.y;
                        const d2m = dxm * dxm + dym * dym;
                        const mr2 = (mouseTarget.radius * mouseTarget.radius);
                        if (d2m < mr2) {
                            const tm = 1 - d2m / mr2;
                            ctx.beginPath();
                            ctx.lineWidth = p.layer.lineWidth * 0.9 * (0.25 + tm * 0.75);
                            ctx.strokeStyle = `rgba(${rgb}, ${0.06 * tm * p.layer.opacity})`;
                            ctx.moveTo(p.x, p.y);
                            ctx.lineTo(mouseTarget.x, mouseTarget.y);
                            ctx.stroke();
                        }
                    }
                }
                ctx.restore();
                if (CONFIG.avoidOverlap) {
                    for (const p of particles) {
                        const idx = cellIndex(p.x, p.y);
                        if (idx < 0) continue;
                        const cx = Math.floor(p.x / cellSize);
                        const cy = Math.floor(p.y / cellSize);
                        for (let ox = -1; ox <= 1; ox++) {
                            for (let oy = -1; oy <= 1; oy++) {
                                const ncx = cx + ox;
                                const ncy = cy + oy;
                                if (ncx < 0 || ncy < 0 || ncx >= grid.cols || ncy >= grid.rows) continue;
                                const cell = grid.cells[ncx + ncy * grid.cols];
                                for (let j = 0; j < cell.length; j++) {
                                    const q = cell[j];
                                    if (q === p) continue;
                                    const dx = p.x - q.x;
                                    const dy = p.y - q.y;
                                    const dist2 = dx * dx + dy * dy;
                                    const minSep = (p.size + q.size) * 1.2;
                                    if (dist2 > 0 && dist2 < minSep * minSep) {
                                        const dist = Math.sqrt(dist2);
                                        const nx = dx / (dist || 1);
                                        const ny = dy / (dist || 1);
                                        const push = (minSep - dist) * CONFIG.avoidForce * 0.6;
                                        p.vx += nx * push;
                                        p.vy += ny * push;
                                    }
                                }
                            }
                        }
                    }
                }
                for (const layer of CONFIG.layers.slice().reverse()) {
                    ctx.save();
                    ctx.shadowBlur = layer.glow;
                    ctx.shadowColor = `rgba(${rgb}, ${0.08})`;
                    for (const p of particles) {
                        if (p.layer !== layer) continue;
                        p.draw(ctx, rgb);
                    }
                    ctx.restore();
                }
                requestAnimationFrame(frame);
            }

            resizeCanvas();
            requestAnimationFrame((t) => {
                lastTime = t;
                requestAnimationFrame(frame);
            });
        }
    };
}
