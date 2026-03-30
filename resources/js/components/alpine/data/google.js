let animationId, resizeId, themeObserver, resizeHandler, mouseMoveHandler, visibilityHandler;
let w, h, colors, mouse, visible, forms;

export default {
    init() {
        this.destroy();

        const canvas = document.createElement('canvas');
        canvas.id = 'interactive-background-google';
        Object.assign(canvas.style, {
            position: 'fixed', top: 0, left: 0, zIndex: -10,
            pointerEvents: 'none', opacity: '0',
            transition: 'opacity 2.5s cubic-bezier(0.2, 0, 0, 1)'
        });
        document.body.appendChild(canvas);

        setTimeout(() => { canvas.style.opacity = '1'; }, 50);

        const ctx = canvas.getContext('2d', {alpha: true, desynchronized: true});
        const dpr = Math.min(2, devicePixelRatio || 1);

        colors = { primary: '78,95,102', tertiary: '107,87,120' };
        mouse = { x: 0, y: 0, tx: 0, ty: 0 };
        visible = true;

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

        themeObserver = new MutationObserver(() => { colors = getThemeColors(); });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});
        colors = getThemeColors();

        forms = [
            { points: 4, radiusMult: 0.55, speed: 0.0005, amp: 0.15, phaseMult: 1.2, dir: 1, colorKey: 'primary', op: 0.07, offsetX: -0.1, offsetY: -0.1 },
            { points: 5, radiusMult: 0.45, speed: 0.0004, amp: 0.12, phaseMult: 1.5, dir: -1, colorKey: 'tertiary', op: 0.09, offsetX: 0.2, offsetY: 0.1 },
            { points: 4, radiusMult: 0.60, speed: 0.0006, amp: 0.18, phaseMult: 0.8, dir: 1, colorKey: 'primary', op: 0.05, offsetX: 0.0, offsetY: 0.2 }
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

        resizeHandler = () => {
            clearTimeout(resizeId);
            resizeId = setTimeout(resize, 150);
        };
        window.addEventListener('resize', resizeHandler);

        mouseMoveHandler = e => {
            mouse.tx = e.clientX;
            mouse.ty = e.clientY;
        };
        window.addEventListener('mousemove', mouseMoveHandler);

        visibilityHandler = () => {
            visible = document.visibilityState === 'visible';
            if (visible) draw(performance.now());
        };
        document.addEventListener('visibilitychange', visibilityHandler);

        const draw = (time) => {
            if (!visible) {
                animationId = requestAnimationFrame(draw);
                return;
            }

            ctx.clearRect(0, 0, w, h);

            mouse.x += (mouse.tx - mouse.x) * 0.05;
            mouse.y += (mouse.ty - mouse.y) * 0.05;

            const maxDim = Math.max(w, h);

            for (let i = 0; i < forms.length; i++) {
                const f = forms[i];

                const cx = w / 2 + (w * f.offsetX) + (mouse.x - w / 2) * 0.08 * (i + 1);
                const cy = h / 2 + (h * f.offsetY) + (mouse.y - h / 2) * 0.08 * (i + 1);

                const baseRadius = maxDim * f.radiusMult;
                const rotation = time * 0.00008 * f.dir;
                const pts = [];

                for (let j = 0; j < f.points; j++) {
                    const angle = (j / f.points) * Math.PI * 2 + rotation;
                    const radiusOffset = Math.sin(time * f.speed + j * f.phaseMult) * (baseRadius * f.amp);
                    const r = baseRadius + radiusOffset;
                    pts.push({
                        x: cx + Math.cos(angle) * r,
                        y: cy + Math.sin(angle) * r
                    });
                }

                ctx.beginPath();
                const midX0 = (pts[0].x + pts[f.points - 1].x) / 2;
                const midY0 = (pts[0].y + pts[f.points - 1].y) / 2;
                ctx.moveTo(midX0, midY0);

                for (let j = 0; j < f.points; j++) {
                    const p1 = pts[j];
                    const p2 = pts[(j + 1) % f.points];
                    const midX = (p1.x + p2.x) / 2;
                    const midY = (p1.y + p2.y) / 2;
                    ctx.quadraticCurveTo(p1.x, p1.y, midX, midY);
                }
                ctx.closePath();

                ctx.fillStyle = `rgba(${colors[f.colorKey]}, ${f.op})`;
                ctx.fill();
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
        document.getElementById('interactive-background-google')?.remove();
    }
}
