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

        setTimeout(() => {
            canvas.style.opacity = '1';
        }, 50);

        const ctx = canvas.getContext('2d', {alpha: true, desynchronized: true});
        const dpr = Math.min(2, devicePixelRatio || 1);

        let w, h;
        let colors = { primary: '78,95,102', tertiary: '107,87,120' };
        const mouse = { x: -1000, y: -1000 };
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

        themeObserver = new MutationObserver(() => { colors = getThemeColors(); });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});
        colors = getThemeColors();

        const charSet = '0123456789ABCDEF01010101!@#$<>{}[]|';
        const fontSize = 16;
        let columns = [];

        const setupColumns = () => {
            const colCount = Math.floor(w / fontSize) + 1;
            columns = [];
            for (let i = 0; i < colCount; i++) {
                columns[i] = {
                    y: Math.floor(Math.random() * -(h / fontSize)),
                    speed: Math.floor(Math.random() * 3) + 1,
                    tick: 0,
                    char: '0'
                };
            }
        };

        const resize = () => {
            w = innerWidth;
            h = innerHeight;
            canvas.width = w * dpr;
            canvas.height = h * dpr;
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
            setupColumns();
        };

        resizeHandler = () => {
            clearTimeout(resizeId);
            resizeId = setTimeout(resize, 150);
        };
        window.addEventListener('resize', resizeHandler);

        mouseMoveHandler = e => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
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

            ctx.globalCompositeOperation = 'destination-out';
            ctx.fillStyle = 'rgba(0, 0, 0, 0.08)';
            ctx.fillRect(0, 0, w, h);

            ctx.globalCompositeOperation = 'source-over';
            ctx.font = `bold ${fontSize}px monospace`;
            ctx.textAlign = 'center';

            const mouseRadius = 120;

            for (let i = 0; i < columns.length; i++) {
                const col = columns[i];

                col.tick++;
                if (col.tick >= col.speed) {
                    col.y++;
                    col.tick = 0;
                    col.char = charSet[Math.floor(Math.random() * charSet.length)];
                }

                const px = i * fontSize + (fontSize / 2);
                const py = col.y * fontSize;

                const dx = px - mouse.x;
                const dy = py - mouse.y;
                const distToMouse = Math.sqrt(dx * dx + dy * dy);

                if (distToMouse < mouseRadius) {
                    ctx.fillStyle = `rgb(${colors.tertiary})`;
                    ctx.shadowBlur = 8;
                    ctx.shadowColor = `rgb(${colors.tertiary})`;

                    if (Math.random() > distToMouse / mouseRadius) {
                        col.char = charSet[Math.floor(Math.random() * charSet.length)];
                    }
                } else {
                    ctx.shadowBlur = 0;
                    if (Math.random() > 0.75) {
                        ctx.fillStyle = `rgba(255, 255, 255, 0.5)`;
                    } else {
                        ctx.fillStyle = `rgb(${colors.primary})`;
                    }
                }

                ctx.fillText(col.char, px, py);

                if (py > h && Math.random() > 0.98) {
                    col.y = 0;
                }
            }

            ctx.shadowBlur = 0;
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
