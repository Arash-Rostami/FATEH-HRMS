@php
    $user = auth()->user();
    $preferences = $user?->extra['preferences'] ?? [];
    $isScreenSaverEnabled = $user && ($preferences['screen_saver'] ?? true);
    $timeout = isset($preferences['screen_saver_timeout']) ? (int)$preferences['screen_saver_timeout'] : 60;
@endphp

@if($isScreenSaverEnabled)

    <script>
        (((timeout) => {
            let idleTimer, raf, resizeTimer, active = false;
            let overlay, grid, glow, stripe, canvas, ctx, clockEl, dateEl, hint, particles;
            let pColor, lastTime = 0, lastSec = -1;

            const N = 28, LINK = 130;
            const cssVar = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();

            const applyColors = () => {
                const c = cssVar('--md-sys-color-primary-container');
                const on = cssVar('--md-sys-color-on-primary');
                pColor = c;
                if (!overlay) return;
                overlay.style.background = cssVar('--md-sys-color-primary');
                grid.style.backgroundImage = `radial-gradient(circle, color-mix(in srgb, ${on} 14%, transparent) 1px, transparent 1px)`;
                glow.style.background = `radial-gradient(circle, color-mix(in srgb, ${c}, transparent 55%) 0%, transparent 70%)`;
                stripe.style.background = c;
                stripe.style.boxShadow = `0 0 12px color-mix(in srgb, ${c}, transparent 30%)`;
                clockEl.style.color = on;
                clockEl.style.textShadow = `0 0 60px color-mix(in srgb, ${c}, transparent 40%)`;
                dateEl.style.color = `color-mix(in srgb, ${on}, transparent 50%)`;
                hint.style.background = `color-mix(in srgb, ${c}, transparent 70%)`;
                hint.style.color = `color-mix(in srgb, ${on}, transparent 30%)`;
                hint.style.border = `1px solid color-mix(in srgb, ${c}, transparent 55%)`;
            };

            const build = () => {
                overlay = document.createElement('div');
                overlay.id = 'fs-overlay';
                Object.assign(overlay.style, {
                    position: 'fixed', inset: 0, zIndex: 99999, display: 'none',
                    alignItems: 'center', justifyContent: 'center',
                    flexDirection: 'column', cursor: 'none', userSelect: 'none',
                    overflow: 'hidden',
                });

                grid = document.createElement('div');
                Object.assign(grid.style, {
                    position: 'absolute', inset: 0, zIndex: 0,
                    pointerEvents: 'none', backgroundSize: '28px 28px',
                });

                glow = document.createElement('div');
                Object.assign(glow.style, {
                    position: 'absolute', width: '60vw', height: '60vw',
                    borderRadius: '50%', top: '-15vw', left: '-15vw',
                    pointerEvents: 'none', zIndex: 0,
                });

                stripe = document.createElement('div');
                Object.assign(stripe.style, {
                    position: 'absolute', top: 0, left: 0, right: 0, height: '3px', zIndex: 2,
                });

                canvas = document.createElement('canvas');
                Object.assign(canvas.style, {position: 'absolute', inset: 0, zIndex: 1});

                clockEl = document.createElement('div');
                Object.assign(clockEl.style, {
                    position: 'relative', zIndex: 2,
                    fontFamily: '"SF Mono","Fira Code","Consolas",monospace',
                    fontSize: 'clamp(3.5rem,9vw,8rem)', fontWeight: 100, letterSpacing: '.1em',
                });

                dateEl = document.createElement('div');
                Object.assign(dateEl.style, {
                    position: 'relative', zIndex: 2,
                    fontFamily: '"SF Mono","Fira Code","Consolas",monospace',
                    fontSize: 'clamp(.65rem,1.3vw,.85rem)',
                    letterSpacing: '.45em', textTransform: 'uppercase', marginTop: '.9rem',
                });

                hint = document.createElement('div');
                Object.assign(hint.style, {
                    position: 'relative', zIndex: 2, marginTop: '2.5rem',
                    fontFamily: 'system-ui,sans-serif', fontSize: '.62rem',
                    letterSpacing: '.3em', textTransform: 'uppercase',
                    padding: '.35rem 1rem', borderRadius: '999px',
                });
                hint.textContent = 'Move mouse or press any key to unlock';

                overlay.append(grid, glow, stripe, canvas, clockEl, dateEl, hint);
                document.body.appendChild(overlay);
                applyColors();
                sync();
            };

            const sync = () => {
                if (!canvas) return;
                canvas.width = innerWidth;
                canvas.height = innerHeight;
            };

            const mkP = () => {
                const {width: w, height: h} = canvas;
                particles = Array.from({length: N}, () => ({
                    x: Math.random() * w, y: Math.random() * h,
                    vx: (Math.random() - .5) * .25, vy: (Math.random() - .5) * .25,
                    r: Math.random() * 1.4 + .3, o: Math.random() * .45 + .12,
                }));
            };

            const tick = (ts) => {
                raf = requestAnimationFrame(tick);
                if (ts - lastTime < 33) return;
                lastTime = ts;

                const {width: w, height: h} = canvas;
                ctx.clearRect(0, 0, w, h);

                for (const p of particles) {
                    p.x = (p.x + p.vx + w) % w;
                    p.y = (p.y + p.vy + h) % h;
                    ctx.globalAlpha = p.o;
                    ctx.fillStyle = pColor;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, 6.2832);
                    ctx.fill();
                }

                ctx.lineWidth = .5;
                ctx.strokeStyle = pColor;
                for (let i = 0; i < N - 1; i++) for (let j = i + 1; j < N; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const d2 = dx * dx + dy * dy;
                    if (d2 < LINK * LINK) {
                        ctx.globalAlpha = .09 * (1 - d2 / (LINK * LINK));
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
                ctx.globalAlpha = 1;

                const now = new Date();
                const s = now.getSeconds();
                if (s !== lastSec) {
                    lastSec = s;
                    clockEl.textContent = now.toLocaleTimeString('en-GB', {hour12: false});
                    dateEl.textContent = now.toLocaleDateString('en-GB', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                }
            };

            const show = () => {
                if (active) return;
                active = true;
                if (!overlay) build();
                ctx = canvas.getContext('2d');
                sync();
                mkP();
                overlay.style.display = 'flex';
                overlay.classList.remove('animate-slide-out-top');
                overlay.classList.add('animate-slide-down');
                lastTime = 0;
                lastSec = -1;
                raf = requestAnimationFrame(tick);
                document.documentElement.style.overflow = 'hidden';
            };

            const hide = () => {
                if (!active) return;
                active = false;
                overlay.classList.remove('animate-slide-down');
                overlay.classList.add('animate-fade-out');
                cancelAnimationFrame(raf);
                document.documentElement.style.overflow = '';
                setTimeout(() => {
                    overlay.style.display = 'none';
                }, 500);
                reset();
            };

            const reset = () => {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(show, timeout * 1000);
            };
            const onActivity = () => active ? hide() : reset();

            ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'wheel']
                .forEach(e => document.addEventListener(e, onActivity, {passive: true}));

            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(sync, 150);
            }, {passive: true});

            window.addEventListener('theme-system-updated', applyColors);
            document.addEventListener('livewire:navigated', reset);
            document.addEventListener('livewire:request', reset);
            reset();
        }))({{ $timeout ?? 60 }});
    </script>
@endif
