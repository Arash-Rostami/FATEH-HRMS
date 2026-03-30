const ROUTE = /(profile|tasks|dms|ths|reservation)/;

export default function fluidWaves() {
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

        const waves = [
            { amp: 45, freq: 0.0015, spd: 0.0008, base: 0.75, op: 0.18 },
            { amp: 65, freq: 0.0010, spd: 0.0012, base: 0.82, op: 0.12 },
            { amp: 35, freq: 0.0020, spd: 0.0005, base: 0.88, op: 0.25 }
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

            mouse.x += (mouse.tx - mouse.x) * 0.03;
            mouse.y += (mouse.ty - mouse.y) * 0.03;

            const mouseOffset = (mouse.y / h - 0.5) * 80;
            const mousePhase = (mouse.x / w - 0.5) * 1.5;

            for (let i = 0; i < waves.length; i++) {
                const wave = waves[i];
                ctx.beginPath();
                ctx.moveTo(0, h);

                const gradient = ctx.createLinearGradient(0, 0, w, h);
                gradient.addColorStop(0, `rgba(${colors.primary}, ${wave.op * 1.2})`);
                gradient.addColorStop(1, `rgba(${colors.tertiary}, ${wave.op * 0.1})`);
                ctx.fillStyle = gradient;

                for (let x = 0; x <= w + 40; x += 40) {
                    const phase = time * wave.spd + x * wave.freq + mousePhase;
                    const y = h * wave.base + Math.sin(phase) * wave.amp + mouseOffset * (i + 1) * 0.25;
                    ctx.lineTo(x, y);
                }

                ctx.lineTo(w, h);
                ctx.closePath();
                ctx.fill();
            }

            animationId = requestAnimationFrame(draw);
        };

        resize();
        animationId = requestAnimationFrame(draw);
    };
}
