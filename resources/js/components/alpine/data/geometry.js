let animationId, resizeId, themeObserver, resizeHandler, mouseMoveHandler, visibilityHandler;
let w, h, colors, mouse, visible, shapesArray;

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
            canvas.style.opacity = '1';
        }, 50);

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

        shapesArray = Array.from({ length: 15 }, () => ({
            x: Math.random(),
            y: Math.random(),
            vx: (Math.random() - 0.5) * 0.0008,
            vy: (Math.random() - 0.5) * 0.0008,
            size: Math.random() * 180 + 60,
            angle: Math.random() * Math.PI * 2,
            vAngle: (Math.random() - 0.5) * 0.005,
            sides: Math.floor(Math.random() * 4) + 3,
            type: Math.random() > 0.4 ? 'fill' : 'stroke'
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

            mouse.x += (mouse.tx - mouse.x) * 0.04;
            mouse.y += (mouse.ty - mouse.y) * 0.04;

            const parallaxX = (mouse.x / w - 0.5) * 80;
            const parallaxY = (mouse.y / h - 0.5) * 80;

            for (let i = 0; i < shapesArray.length; i++) {
                const s = shapesArray[i];

                s.x += s.vx;
                s.y += s.vy;
                s.angle += s.vAngle;

                if (s.x < -0.2) s.x = 1.2;
                if (s.x > 1.2) s.x = -0.2;
                if (s.y < -0.2) s.y = 1.2;
                if (s.y > 1.2) s.y = -0.2;

                const px = s.x * w + parallaxX * (s.size / 150);
                const py = s.y * h + parallaxY * (s.size / 150);

                ctx.save();
                ctx.translate(px, py);
                ctx.rotate(s.angle);

                ctx.beginPath();
                for (let j = 0; j < s.sides; j++) {
                    const theta = (j / s.sides) * Math.PI * 2;
                    const rx = Math.cos(theta) * s.size;
                    const ry = Math.sin(theta) * s.size;
                    if (j === 0) ctx.moveTo(rx, ry);
                    else ctx.lineTo(rx, ry);
                }
                ctx.closePath();

                const grad = ctx.createLinearGradient(-s.size, -s.size, s.size, s.size);
                const c1 = i % 2 === 0 ? colors.primary : colors.tertiary;
                const c2 = i % 2 === 0 ? colors.tertiary : colors.primary;

                if (s.type === 'fill') {
                    grad.addColorStop(0, `rgba(${c1}, 0.06)`);
                    grad.addColorStop(1, `rgba(${c2}, 0.01)`);
                    ctx.fillStyle = grad;
                    ctx.fill();
                } else {
                    grad.addColorStop(0, `rgba(${c1}, 0.15)`);
                    grad.addColorStop(1, `rgba(${c2}, 0.02)`);
                    ctx.strokeStyle = grad;
                    ctx.lineWidth = 1.2;
                    ctx.stroke();
                }

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
