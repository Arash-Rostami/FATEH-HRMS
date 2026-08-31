let animationId, resizeId, themeObserver, resizeHandler, mouseMoveHandler, mouseOutHandler, visibilityHandler;

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

        const mouse = { x: -1000, y: -1000, vx: 0, vy: 0 };
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

        const drawLeaf = (ctx, size) => {
            ctx.beginPath();
            ctx.moveTo(0, -size);
            ctx.quadraticCurveTo(size * 0.6, 0, 0, size);
            ctx.quadraticCurveTo(-size * 0.6, 0, 0, -size);
            ctx.fill();
        };

        const drawWheat = (ctx, size) => {
            ctx.beginPath();
            ctx.moveTo(0, size * 1.5);
            ctx.quadraticCurveTo(size * 0.2, 0, 0, -size * 1.5);
            ctx.stroke();

            for (let i = -size; i < size; i += size * 0.4) {
                ctx.beginPath();
                ctx.ellipse(size * 0.2, i, size * 0.15, size * 0.35, Math.PI / 4, 0, Math.PI * 2);
                ctx.fill();
                ctx.beginPath();
                ctx.ellipse(-size * 0.2, i - (size * 0.2), size * 0.15, size * 0.35, -Math.PI / 4, 0, Math.PI * 2);
                ctx.fill();
            }
        };

        const drawStraw = (ctx, size) => {
            ctx.beginPath();
            ctx.moveTo(0, size * 2.5);
            ctx.lineTo(0, -size * 2.5);
            ctx.lineWidth = 1.5;
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(-size * 0.2, size * 0.5);
            ctx.lineTo(size * 0.2, size * 0.4);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(-size * 0.2, -size * 0.8);
            ctx.lineTo(size * 0.2, -size * 0.7);
            ctx.stroke();
        };

        const elements = Array.from({length: 65}, () => {
            const rand = Math.random();
            let type;
            if (rand > 0.6) type = 'pollen';
            else if (rand > 0.4) type = 'wheat';
            else if (rand > 0.2) type = 'straw';
            else type = 'leaf';

            return {
                type: type,
                x: Math.random(),
                y: Math.random(),
                size: Math.random() * 15 + 10,
                baseAngle: Math.random() * Math.PI * 2,
                swaySpeed: Math.random() * 0.001 + 0.0005,
                swayAmp: Math.random() * 0.3 + 0.1,
                driftX: (Math.random() - 0.5) * 0.0003,
                driftY: (Math.random() - 0.5) * 0.0003,
                opacity: Math.random() * 0.3 + 0.15,
                colorKey: Math.random() > 0.5 ? 'primary' : 'tertiary',
                phase: Math.random() * Math.PI * 2,
                windBend: 0
            };
        });

        const resize = () => {
            w = innerWidth; h = innerHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
        };
        resizeHandler = () => { clearTimeout(resizeId); resizeId = setTimeout(resize, 150); };
        window.addEventListener('resize', resizeHandler);

        let lastMouse = { x: -1000, y: -1000 };
        mouseMoveHandler = e => {
            mouse.vx = e.clientX - lastMouse.x;
            mouse.vy = e.clientY - lastMouse.y;
            mouse.x = e.clientX; mouse.y = e.clientY;
            lastMouse.x = e.clientX; lastMouse.y = e.clientY;
        };
        window.addEventListener('mousemove', mouseMoveHandler);
        mouseOutHandler = () => { mouse.x = -1000; mouse.y = -1000; mouse.vx = 0; mouse.vy = 0; };
        window.addEventListener('mouseout', mouseOutHandler);
        visibilityHandler = () => { visible = document.visibilityState === 'visible'; if (visible) draw(performance.now()); };
        document.addEventListener('visibilitychange', visibilityHandler);

        const draw = (time) => {
            if (!visible) return animationId = requestAnimationFrame(draw);
            ctx.clearRect(0, 0, w, h);

            const windRadius = Math.min(w, h) * 0.25;

            elements.forEach(el => {
                el.x += el.driftX;
                el.y += el.driftY;

                if (el.x > 1.1) el.x = -0.1; if (el.x < -0.1) el.x = 1.1;
                if (el.y > 1.1) el.y = -0.1; if (el.y < -0.1) el.y = 1.1;

                let px = el.x * w;
                let py = el.y * h;

                const dx = px - mouse.x;
                const dy = py - mouse.y;
                const distToMouse = Math.sqrt(dx * dx + dy * dy);

                if (distToMouse < windRadius) {
                    const force = (windRadius - distToMouse) / windRadius;

                    if (el.type === 'pollen' || el.type === 'leaf') {
                        px += (mouse.vx * force * 0.8) + (dx / distToMouse * force * 15);
                        py += (mouse.vy * force * 0.8) + (dy / distToMouse * force * 15);
                    } else {
                        el.windBend += (mouse.vx * 0.015 * force);
                    }
                    ctx.globalAlpha = Math.min(1, el.opacity + force * 0.5);
                } else {
                    ctx.globalAlpha = el.opacity;
                }

                el.windBend *= 0.90;

                const rgb = colors[el.colorKey];
                ctx.fillStyle = `rgb(${rgb})`;
                ctx.strokeStyle = `rgb(${rgb})`;
                ctx.lineWidth = 1.5;

                ctx.save();
                ctx.translate(px, py);

                const currentAngle = el.baseAngle + Math.sin(time * el.swaySpeed + el.phase) * el.swayAmp + el.windBend;
                ctx.rotate(currentAngle);

                if (el.type === 'wheat') {
                    drawWheat(ctx, el.size);
                } else if (el.type === 'straw') {
                    drawStraw(ctx, el.size);
                } else if (el.type === 'leaf') {
                    drawLeaf(ctx, el.size);
                } else {
                    ctx.beginPath();
                    ctx.arc(0, 0, el.size * 0.15, 0, Math.PI * 2);
                    ctx.fill();
                }

                ctx.restore();
            });

            ctx.globalAlpha = 1;
            mouse.vx *= 0.9;
            mouse.vy *= 0.9;

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
        window.removeEventListener('mouseout', mouseOutHandler);
        document.removeEventListener('visibilitychange', visibilityHandler);
        document.getElementById('interactive-background')?.remove();
    }
}
