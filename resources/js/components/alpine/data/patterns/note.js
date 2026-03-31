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
        const mouse = { x: -1000, y: -1000 };
        let visible = true;

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

        const notesList = ['♪', '♫', '♬', '♭', '♮', '♯', '𝄞', '𝄢'];
        const elements = Array.from({ length: 40 }, () => ({
            char: notesList[Math.floor(Math.random() * notesList.length)],
            x: Math.random(),
            y: Math.random(),
            speed: Math.random() * 0.0005 + 0.0002, // Horizontal speed
            size: Math.random() * 20 + 15,
            opacity: Math.random() * 0.4 + 0.1,
            colorKey: Math.random() > 0.5 ? 'primary' : 'tertiary',
            amplitude: Math.random() * 30 + 10, // How high it bobs
            frequency: Math.random() * 0.002 + 0.001,
            phase: Math.random() * Math.PI * 2,
            offsetY: 0 // Used for mouse scattering
        }));

        const resize = () => {
            w = innerWidth; h = innerHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
        };
        resizeHandler = () => { clearTimeout(resizeId); resizeId = setTimeout(resize, 150); };
        window.addEventListener('resize', resizeHandler);

        mouseMoveHandler = e => { mouse.x = e.clientX; mouse.y = e.clientY; };
        window.addEventListener('mousemove', mouseMoveHandler);
        visibilityHandler = () => { visible = document.visibilityState === 'visible'; if (visible) draw(performance.now()); };
        document.addEventListener('visibilitychange', visibilityHandler);

        const draw = (time) => {
            if (!visible) return animationId = requestAnimationFrame(draw);
            ctx.clearRect(0, 0, w, h);

            const mouseRadius = Math.min(w, h) * 0.15;

            elements.forEach(el => {
                el.x += el.speed;
                if (el.x > 1.1) {
                    el.x = -0.1;
                    el.y = Math.random();
                    el.char = notesList[Math.floor(Math.random() * notesList.length)];
                }

                el.offsetY *= 0.95;

                let px = el.x * w;
                let py = el.y * h + Math.sin(time * el.frequency + el.phase) * el.amplitude + el.offsetY;

                const dx = px - mouse.x;
                const dy = py - mouse.y;
                const distToMouse = Math.sqrt(dx * dx + dy * dy);

                if (distToMouse < mouseRadius) {
                    const force = (mouseRadius - distToMouse) / mouseRadius;
                    el.offsetY += (dy > 0 ? 1 : -1) * force * 5;
                }

                ctx.font = `${el.size}px serif`;
                ctx.fillStyle = `rgba(${colors[el.colorKey]}, ${el.opacity})`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(el.char, px, py);
            });

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
