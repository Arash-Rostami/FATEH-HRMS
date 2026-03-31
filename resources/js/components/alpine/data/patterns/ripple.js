let animationId, resizeId, themeObserver, resizeHandler, mouseMoveHandler, visibilityHandler;

export default {
    init() {
        this.destroy();
        const canvas = document.createElement('canvas');
        canvas.id = 'interactive-background';
        Object.assign(canvas.style, {
            position: 'fixed', top: 0, left: 0, zIndex: -10,
            pointerEvents: 'none', opacity: '0', transition: 'opacity 2.5s ease-in-out'
        });
        document.body.appendChild(canvas);
        setTimeout(() => canvas.style.opacity = '1', 50);

        const ctx = canvas.getContext('2d', {alpha: true, desynchronized: true});
        const dpr = Math.min(2, devicePixelRatio || 1);
        let w, h;
        let colors = { primary: '78,95,102', tertiary: '107,87,120' };
        let visible = true;

        let ripples = [];
        const maxRipples = 40; // Prevent memory leak

        const getThemeColors = () => {
            const s = getComputedStyle(document.documentElement);
            const parseHex = (v, d) => {
                let hex = s.getPropertyValue(v).trim().replace('#', '') || d;
                if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                const n = parseInt(hex, 16); return `${n >> 16 & 255},${n >> 8 & 255},${n & 255}`;
            };
            return { primary: parseHex('--md-sys-color-primary', '4e5f66'), tertiary: parseHex('--md-sys-color-tertiary', '6b5778') };
        };
        themeObserver = new MutationObserver(() => colors = getThemeColors());
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});
        colors = getThemeColors();

        const addRipple = (x, y, isMouse = false) => {
            if (ripples.length > maxRipples) ripples.shift();
            ripples.push({
                x, y,
                radius: 0,
                maxRadius: isMouse ? Math.random() * 50 + 50 : Math.random() * 100 + 80,
                speed: isMouse ? 1.5 : 0.8,
                opacity: isMouse ? 0.4 : 0.2,
                colorKey: Math.random() > 0.5 ? 'primary' : 'tertiary'
            });
        };

        const resize = () => {
            w = innerWidth; h = innerHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
        };
        resizeHandler = () => { clearTimeout(resizeId); resizeId = setTimeout(resize, 150); };
        window.addEventListener('resize', resizeHandler);

        let lastMouseTime = 0;
        mouseMoveHandler = e => {
            const now = performance.now();
            if (now - lastMouseTime > 50) {
                addRipple(e.clientX, e.clientY, true);
                lastMouseTime = now;
            }
        };
        window.addEventListener('mousemove', mouseMoveHandler);
        visibilityHandler = () => { visible = document.visibilityState === 'visible'; if (visible) draw(); };
        document.addEventListener('visibilitychange', visibilityHandler);

        const draw = () => {
            if (!visible) return animationId = requestAnimationFrame(draw);
            ctx.clearRect(0, 0, w, h);

            if (Math.random() > 0.98) {
                addRipple(Math.random() * w, Math.random() * h, false);
            }

            for (let i = ripples.length - 1; i >= 0; i--) {
                const r = ripples[i];
                r.radius += r.speed;

                const currentOpacity = r.opacity * (1 - (r.radius / r.maxRadius));

                if (currentOpacity <= 0) {
                    ripples.splice(i, 1);
                    continue;
                }

                ctx.beginPath();
                ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2);
                ctx.strokeStyle = `rgba(${colors[r.colorKey]}, ${currentOpacity})`;
                ctx.lineWidth = 1.5;
                ctx.stroke();
            }

            animationId = requestAnimationFrame(draw);
        };
        resize(); animationId = requestAnimationFrame(draw);
    },
    destroy() {
        if (animationId) cancelAnimationFrame(animationId);
        if (resizeId) clearTimeout(resizeId);
        if (themeObserver) themeObserver.disconnect();
        window.removeEventListener('resize', resizeHandler);
        window.removeEventListener('mousemove', mouseMoveHandler);
        document.removeEventListener('visibilitychange', visibilityHandler);
        document.getElementById('interactive-background')?.remove();
    }
}
