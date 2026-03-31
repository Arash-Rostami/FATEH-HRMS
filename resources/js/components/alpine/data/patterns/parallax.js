let animationId, resizeId, themeObserver, resizeHandler, mouseMoveHandler, visibilityHandler;

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
        setTimeout(() => canvas.style.opacity = '1', 50);

        const ctx = canvas.getContext('2d', { alpha: true, desynchronized: true });
        const dpr = Math.min(2, devicePixelRatio || 1);

        let w, h;
        let colors = { primary: '78,95,102', tertiary: '107,87,120' };
        const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
        let visible = true;

        const getThemeColors = () => {
            const s = getComputedStyle(document.documentElement);
            const parseHex = (varName, fallback) => {
                let hex = s.getPropertyValue(varName).trim().replace('#', '') || fallback;
                if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                const n = parseInt(hex, 16);
                return `${n >> 16 & 255},${n >> 8 & 255},${n & 255}`;
            };
            return {
                primary: parseHex('--md-sys-color-primary', '4e5f66'),
                tertiary: parseHex('--md-sys-color-tertiary', '6b5778')
            };
        };

        themeObserver = new MutationObserver(() => { colors = getThemeColors(); });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
        colors = getThemeColors();

        // Richer element set: dot | ring | arc | line | cross | triangle
        const TYPES = ['dot', 'ring', 'arc', 'line', 'cross', 'triangle'];
        const elements = Array.from({ length: 36 }, (_, i) => {
            const layer = (i % 3) + 1;
            const type = TYPES[Math.floor(Math.random() * TYPES.length)];
            const baseSize = (Math.random() * 40 + 18) * layer;
            return {
                x: Math.random() * 1.3 - 0.15,
                y: Math.random() * 1.3 - 0.15,
                layer,
                parallaxFactor: layer * -0.035,
                type,
                size: baseSize,
                sweepStart: Math.random() * Math.PI * 2,
                sweepEnd: (Math.random() * 1.2 + 0.4) * Math.PI,
                angle: Math.random() * Math.PI * 2,
                vAngle: (Math.random() - 0.5) * 0.006,
                isPrimary: Math.random() > 0.45,
                opacity: 0.03 + 0.035 * layer,
                lineWidth: layer * 1.2 + Math.random() * 1.2,
            };
        });

        const resize = () => {
            w = innerWidth; h = innerHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
            mouse.tx = mouse.x = w / 2;
            mouse.ty = mouse.y = h / 2;
        };

        resizeHandler = () => { clearTimeout(resizeId); resizeId = setTimeout(resize, 150); };
        window.addEventListener('resize', resizeHandler);

        mouseMoveHandler = e => { mouse.tx = e.clientX; mouse.ty = e.clientY; };
        window.addEventListener('mousemove', mouseMoveHandler);

        visibilityHandler = () => {
            visible = document.visibilityState === 'visible';
            if (visible) draw();
        };
        document.addEventListener('visibilitychange', visibilityHandler);

        const drawElement = (el, colorBase) => {
            const s = el.size;
            ctx.strokeStyle = `rgba(${colorBase},${el.opacity})`;
            ctx.fillStyle   = `rgba(${colorBase},${el.opacity * 0.85})`;
            ctx.lineWidth   = el.lineWidth;
            ctx.lineCap     = 'round';

            switch (el.type) {
                case 'ring':
                    ctx.beginPath();
                    ctx.arc(0, 0, s, 0, Math.PI * 2);
                    ctx.stroke();
                    break;

                case 'arc':
                    ctx.beginPath();
                    ctx.arc(0, 0, s, el.sweepStart, el.sweepStart + el.sweepEnd);
                    ctx.stroke();
                    break;

                case 'line':
                    ctx.beginPath();
                    ctx.moveTo(-s, 0);
                    ctx.lineTo(s, 0);
                    ctx.stroke();
                    break;

                case 'cross': {
                    const a = s * 0.55;
                    ctx.beginPath();
                    ctx.moveTo(-a, 0); ctx.lineTo(a, 0);
                    ctx.moveTo(0, -a); ctx.lineTo(0, a);
                    ctx.stroke();
                    break;
                }

                case 'triangle': {
                    const r = s * 0.6;
                    ctx.beginPath();
                    for (let v = 0; v < 3; v++) {
                        const a = (v / 3) * Math.PI * 2 - Math.PI / 2;
                        v === 0 ? ctx.moveTo(Math.cos(a) * r, Math.sin(a) * r)
                            : ctx.lineTo(Math.cos(a) * r, Math.sin(a) * r);
                    }
                    ctx.closePath();
                    ctx.stroke();
                    break;
                }

                default: // dot
                    ctx.beginPath();
                    ctx.arc(0, 0, s * 0.22, 0, Math.PI * 2);
                    ctx.fill();
            }
        };

        const draw = () => {
            if (!visible) { animationId = requestAnimationFrame(draw); return; }

            ctx.clearRect(0, 0, w, h);
            mouse.x += (mouse.tx - mouse.x) * 0.06;
            mouse.y += (mouse.ty - mouse.y) * 0.06;

            const dx = mouse.x - w / 2;
            const dy = mouse.y - h / 2;

            for (let i = 0; i < elements.length; i++) {
                const el = elements[i];
                el.angle += el.vAngle;

                ctx.save();
                ctx.translate(
                    el.x * w + dx * el.parallaxFactor,
                    el.y * h + dy * el.parallaxFactor
                );
                ctx.rotate(el.angle);
                drawElement(el, el.isPrimary ? colors.primary : colors.tertiary);
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
};
