import { ROUTE_PATTERN as ROUTE } from "../../../core/constants.js";

export default function ambientAura() {
    return () => {
        if (!ROUTE.test(location.href)) return;
        document.getElementById('interactive-background')?.remove();

        const canvas = document.createElement('canvas');
        canvas.id = 'interactive-background';
        Object.assign(canvas.style, {
            position: 'fixed', top: 0, left: 0, zIndex: -10,
            pointerEvents: 'none', opacity: '0',
            transition: 'opacity 2.5s ease-in-out'
        });
        document.body.appendChild(canvas);

        setTimeout(() => {
            canvas.style.opacity = '1';
        }, 50);

        const ctx = canvas.getContext('2d', {alpha: true, desynchronized: true});
        const dpr = Math.min(2, devicePixelRatio || 1);

        let w, h, animationId;
        let colors = { primary: '78,95,102', tertiary: '107,87,120' };
        const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
        let visible = true;

        const getThemeColors = () => {
            const s = getComputedStyle(document.documentElement);
            const parseHex = (varName, defaultHex) => {
                let hex = s.getPropertyValue(varName).trim().replace('#', '');
                if (!hex) hex = defaultHex;
                if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                const n = parseInt(hex, 16);
                return `${n >> 16 & 255},${n >> 8 & 255},${n & 255}`;
            };
            return {
                primary: parseHex('--md-sys-color-primary', '4e5f66'),
                tertiary: parseHex('--md-sys-color-tertiary', '6b5778')
            };
        };

        const themeObserver = new MutationObserver(() => { colors = getThemeColors(); });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});
        colors = getThemeColors();

        const orbs = [
            { phase: 0, speed: 0.0004, size: 0.55, colorKey: 'primary' },
            { phase: 2, speed: 0.0003, size: 0.65, colorKey: 'tertiary' },
            { phase: 4, speed: 0.0005, size: 0.50, colorKey: 'primary' }
        ];

        const resize = () => {
            w = innerWidth;
            h = innerHeight;
            canvas.width = w * dpr;
            canvas.height = h * dpr;
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
            mouse.tx = w / 2;
            mouse.ty = h / 2;
            mouse.x = mouse.tx;
            mouse.y = mouse.ty;
        };

        let resizeId;
        window.addEventListener('resize', () => {
            clearTimeout(resizeId);
            resizeId = setTimeout(resize, 150);
        });

        window.addEventListener('mousemove', e => {
            mouse.tx = e.clientX;
            mouse.ty = e.clientY;
        });

        document.addEventListener('visibilitychange', () => {
            visible = document.visibilityState === 'visible';
            if (visible) draw(performance.now());
        });

        const draw = (time) => {
            if (!document.getElementById('interactive-background')) {
                themeObserver.disconnect();
                return;
            }
            if (!visible) return;

            ctx.clearRect(0, 0, w, h);
            ctx.globalCompositeOperation = 'lighter';

            mouse.x += (mouse.tx - mouse.x) * 0.04;
            mouse.y += (mouse.ty - mouse.y) * 0.04;

            const cx = w / 2;
            const cy = h / 2;
            const minDim = Math.max(w, h);

            for (let i = 0; i < orbs.length; i++) {
                const orb = orbs[i];
                const t = time * orb.speed + orb.phase;

                let ox, oy;
                if (i === 2) {
                    ox = mouse.x;
                    oy = mouse.y;
                } else {
                    ox = cx + Math.cos(t) * (w * 0.35);
                    oy = cy + Math.sin(t * 0.8) * (h * 0.35);
                }

                const breathingScale = 1 + Math.sin(time * 0.0015 + orb.phase) * 0.15;
                const radius = minDim * orb.size * breathingScale;

                if (radius <= 0) continue;

                const grad = ctx.createRadialGradient(ox, oy, 0, ox, oy, radius);
                const rgb = colors[orb.colorKey];

                grad.addColorStop(0, `rgba(${rgb}, 0.25)`);
                grad.addColorStop(0.5, `rgba(${rgb}, 0.08)`);
                grad.addColorStop(1, `rgba(${rgb}, 0)`);

                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(ox, oy, radius, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.globalCompositeOperation = 'source-over';
            animationId = requestAnimationFrame(draw);
        };

        resize();
        animationId = requestAnimationFrame(draw);
    };
}
