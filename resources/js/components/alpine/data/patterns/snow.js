let animationId, resizeId, themeObserver, resizeHandler, mouseMoveHandler, visibilityHandler;
let w, h, colors, mouse, visible, flakes;

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

        colors = { primary: '78,95,102', container: '209,231,239', onPrimary: '255,255,255' };
        mouse = { x: 0, y: 0, tx: 0, ty: 0 };
        visible = true;

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
                container: parseHex('--md-sys-color-primary-container', 'd1e7ef'),
                onPrimary: parseHex('--md-sys-color-on-primary',        'ffffff')
            };
        };

        themeObserver = new MutationObserver(() => { colors = getThemeColors(); });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});
        colors = getThemeColors();

        const colorKeys = ['onPrimary', 'container', 'primary'];

        const buildFlake = (r, style) => {
            const arms = style === 2 ? 8 : 6;
            const step = (Math.PI * 2) / arms;
            const path = new Path2D();

            for (let j = 0; j < arms; j++) {
                const a = step * j;
                const cos = Math.cos(a), sin = Math.sin(a);

                path.moveTo(0, 0);
                path.lineTo(cos * r, sin * r);

                if (style === 0 || style === 3) {
                    const mx = cos * r * 0.5, my = sin * r * 0.5;
                    const blen = r * 0.32;
                    path.moveTo(mx, my);
                    path.lineTo(mx + Math.cos(a + Math.PI / 3) * blen, my + Math.sin(a + Math.PI / 3) * blen);
                    path.moveTo(mx, my);
                    path.lineTo(mx + Math.cos(a - Math.PI / 3) * blen, my + Math.sin(a - Math.PI / 3) * blen);
                }

                if (style === 1 || style === 3) {
                    const tx = cos * r, ty = sin * r;
                    const flen = r * 0.28;
                    path.moveTo(tx, ty);
                    path.lineTo(tx + Math.cos(a + Math.PI / 4) * flen, ty + Math.sin(a + Math.PI / 4) * flen);
                    path.moveTo(tx, ty);
                    path.lineTo(tx + Math.cos(a - Math.PI / 4) * flen, ty + Math.sin(a - Math.PI / 4) * flen);
                }

                if (style === 2) {
                    const mx = cos * r * 0.55, my = sin * r * 0.55;
                    const blen = r * 0.22;
                    path.moveTo(mx, my);
                    path.lineTo(mx + Math.cos(a + Math.PI / 4) * blen, my + Math.sin(a + Math.PI / 4) * blen);
                    path.moveTo(mx, my);
                    path.lineTo(mx + Math.cos(a - Math.PI / 4) * blen, my + Math.sin(a - Math.PI / 4) * blen);
                }
            }

            const dot = new Path2D();
            dot.arc(0, 0, r * 0.1, 0, Math.PI * 2);

            return { path, dot };
        };

        flakes = Array.from({ length: 110 }, () => ({
            x:     Math.random() * 1.4 - 0.2,
            y:     Math.random() * 1.4 - 0.2,
            r:     Math.random() * 5 + 3,
            vy:    Math.random() * 0.0004 + 0.0001,
            vx:    (Math.random() - 0.5) * 0.00015,
            sw:    Math.random() * 0.00025 + 0.00005,
            sf:    Math.random() * 15 + 6,
            op:    0.55 + Math.random() * 0.38,
            angle: Math.random() * Math.PI * 2,
            va:    (Math.random() - 0.5) * 0.006,
            style: Math.floor(Math.random() * 4),
            color: colorKeys[Math.floor(Math.random() * 3)]
        }));
        flakes.forEach(s => Object.assign(s, buildFlake(s.r, s.style)));

        const resize = () => {
            w = innerWidth; h = innerHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
            mouse.tx = w / 2; mouse.ty = h / 2;
            mouse.x = mouse.tx; mouse.y = mouse.ty;
        };

        resizeHandler = () => { clearTimeout(resizeId); resizeId = setTimeout(resize, 150); };
        window.addEventListener('resize', resizeHandler);

        mouseMoveHandler = e => { mouse.tx = e.clientX; mouse.ty = e.clientY; };
        window.addEventListener('mousemove', mouseMoveHandler);

        visibilityHandler = () => { visible = document.visibilityState === 'visible'; if (visible) draw(); };
        document.addEventListener('visibilitychange', visibilityHandler);

        const draw = () => {
            if (!visible) { animationId = requestAnimationFrame(draw); return; }

            ctx.clearRect(0, 0, w, h);

            mouse.x += (mouse.tx - mouse.x) * 0.015;
            mouse.y += (mouse.ty - mouse.y) * 0.015;
            const mox = (mouse.x / w - 0.5) * 35;
            const moy = (mouse.y / h - 0.5) * 18;

            for (let i = 0; i < flakes.length; i++) {
                const s = flakes[i];

                s.y += s.vy;
                s.x += s.vx + Math.sin(s.y * s.sf) * s.sw;
                s.angle += s.va;

                if (s.y > 1.2)  { s.y = -0.2; s.x = Math.random() * 1.4 - 0.2; }
                if (s.x > 1.2)  s.x = -0.2;
                if (s.x < -0.2) s.x = 1.2;

                const px = s.x * w + mox * (s.r / 5);
                const py = s.y * h + moy * (s.r / 5);
                const c = `rgba(${colors[s.color]},${s.op})`;

                ctx.save();
                ctx.translate(px, py);
                ctx.rotate(s.angle);
                ctx.strokeStyle = c;
                ctx.fillStyle = c;
                ctx.lineWidth = s.r * 0.13;
                ctx.lineCap = 'round';
                ctx.stroke(s.path);
                ctx.fill(s.dot);
                ctx.restore();
            }

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
        if (mouseMoveHandler) window.removeEventListener('mousemove', mouseMoveHandler);
        if (visibilityHandler) document.removeEventListener('visibilitychange', visibilityHandler);
        document.getElementById('interactive-background')?.remove();
    }
}
