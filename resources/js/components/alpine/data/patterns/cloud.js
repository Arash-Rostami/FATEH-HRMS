let animationId, resizeId, themeObserver, resizeHandler, mouseMoveHandler, visibilityHandler;
let w, h, colors, mouse, visible, cloudArray, cloudPath;

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

        setTimeout(() => {
            canvas.style.opacity = '.6';
        }, 50);

        const ctx = canvas.getContext('2d', {alpha: true, desynchronized: true});
        const dpr = Math.min(2, devicePixelRatio || 1);

        colors = { primary: '78,95,102', secondary: '83,95,112', tertiary: '107,87,120' };
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
                primary: parseHex('--md-sys-color-primary', '4e5f66'),
                secondary: parseHex('--md-sys-color-secondary', '535f70'),
                tertiary: parseHex('--md-sys-color-tertiary', '6b5778')
            };
        };

        themeObserver = new MutationObserver(() => { colors = getThemeColors(); });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});
        colors = getThemeColors();

        cloudPath = new Path2D();
        cloudPath.moveTo(-30, 10);
        cloudPath.arc(-30, -10, 20, Math.PI / 2, Math.PI * 1.5);
        cloudPath.arc(-5, -25, 25, Math.PI, Math.PI * 1.8);
        cloudPath.arc(25, -20, 25, Math.PI * 1.2, Math.PI * 1.9);
        cloudPath.arc(45, -5, 15, Math.PI * 1.5, Math.PI / 2);
        cloudPath.closePath();

        cloudArray = Array.from({ length: 18 }, () => ({
            x: Math.random() * 1.4 - 0.2,
            y: Math.random() * 1.4 - 0.2,
            size: Math.random() * 3 + 1.5,
            vx: (Math.random() * 0.0004) + 0.0001,
            vy: (Math.random() - 0.5) * 0.0001,
            op: Math.random() * 0.25 + 0.1,
            type: Math.floor(Math.random() * 3)
        }));

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
            if (visible) draw();
        };
        document.addEventListener('visibilitychange', visibilityHandler);

        const draw = () => {
            if (!visible) {
                animationId = requestAnimationFrame(draw);
                return;
            }

            ctx.clearRect(0, 0, w, h);

            mouse.x += (mouse.tx - mouse.x) * 0.03;
            mouse.y += (mouse.ty - mouse.y) * 0.03;

            const mouseOffsetX = (mouse.x / w - 0.5) * 80;
            const mouseOffsetY = (mouse.y / h - 0.5) * 80;

            for (let i = 0; i < cloudArray.length; i++) {
                const c = cloudArray[i];

                c.x += c.vx;
                c.y += c.vy;

                if (c.x > 1.3) c.x = -0.3;
                if (c.x < -0.3) c.x = 1.3;
                if (c.y > 1.3) c.y = -0.3;
                if (c.y < -0.3) c.y = 1.3;

                const px = c.x * w + mouseOffsetX * (c.size / 5);
                const py = c.y * h + mouseOffsetY * (c.size / 5);

                let colorStr;
                if (c.type === 0) colorStr = colors.primary;
                else if (c.type === 1) colorStr = colors.secondary;
                else colorStr = colors.tertiary;

                ctx.save();
                ctx.translate(px, py);
                ctx.scale(c.size, c.size);

                ctx.fillStyle = `rgba(${colorStr}, ${c.op})`;
                ctx.fill(cloudPath);

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
