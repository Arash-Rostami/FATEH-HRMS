const ROUTE = /(profile|tasks|dms|ths|reservation)/;

export default function jazzyParallax() {
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

        const elements = Array.from({ length: 30 }, (_, i) => {
            const layer = (i % 3) + 1;
            const typeValue = Math.random();
            let type = 'dot';
            if (typeValue > 0.6) type = 'ring';
            else if (typeValue > 0.3) type = 'line';

            return {
                x: Math.random() * 1.2 - 0.1,
                y: Math.random() * 1.2 - 0.1,
                layer: layer,
                parallaxFactor: layer * -0.04,
                type: type,
                size: (Math.random() * 45 + 15) * layer,
                angle: Math.random() * Math.PI * 2,
                vAngle: (Math.random() - 0.5) * 0.008,
                isPrimary: i % 2 === 0,
                opacity: 0.04 + (0.04 * layer)
            };
        });

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
            if (visible) draw();
        });

        const draw = () => {
            if (!document.getElementById('interactive-background')) {
                themeObserver.disconnect();
                return;
            }
            if (!visible) return;

            ctx.clearRect(0, 0, w, h);

            mouse.x += (mouse.tx - mouse.x) * 0.08;
            mouse.y += (mouse.ty - mouse.y) * 0.08;

            const dx = mouse.x - w / 2;
            const dy = mouse.y - h / 2;

            for (let i = 0; i < elements.length; i++) {
                const el = elements[i];
                el.angle += el.vAngle;

                const px = (el.x * w) + (dx * el.parallaxFactor);
                const py = (el.y * h) + (dy * el.parallaxFactor);
                const colorBase = el.isPrimary ? colors.primary : colors.tertiary;

                ctx.save();
                ctx.translate(px, py);
                ctx.rotate(el.angle);

                if (el.type === 'ring') {
                    ctx.beginPath();
                    ctx.arc(0, 0, el.size, 0, Math.PI * 1.6);
                    ctx.strokeStyle = `rgba(${colorBase}, ${el.opacity})`;
                    ctx.lineWidth = el.layer * 1.5;
                    ctx.lineCap = 'round';
                    ctx.stroke();
                } else if (el.type === 'line') {
                    ctx.beginPath();
                    ctx.moveTo(-el.size, 0);
                    ctx.lineTo(el.size, 0);
                    ctx.strokeStyle = `rgba(${colorBase}, ${el.opacity})`;
                    ctx.lineWidth = el.layer * 2;
                    ctx.lineCap = 'round';
                    ctx.stroke();
                } else {
                    ctx.beginPath();
                    ctx.arc(0, 0, el.size * 0.25, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(${colorBase}, ${el.opacity * 0.9})`;
                    ctx.fill();
                }

                ctx.restore();
            }

            animationId = requestAnimationFrame(draw);
        };

        resize();
        animationId = requestAnimationFrame(draw);
    };
}
