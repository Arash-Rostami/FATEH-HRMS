let animationId, resizeId, themeObserver, resizeHandler, visibilityHandler;
let w, h, visible, flies, flyColor;

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
        setTimeout(() => { canvas.style.opacity = '1'; }, 50);

        const ctx = canvas.getContext('2d', {alpha: true, desynchronized: true});
        const dpr = Math.min(2, devicePixelRatio || 1);
        visible = true;

        const getColor = () => {
            let hex = getComputedStyle(document.documentElement)
                .getPropertyValue('--header-border-color').trim().replace('#', '');
            if (hex.length === 3) hex = hex.split('').map(c => c+c).join('');
            if (!hex || isNaN(parseInt(hex, 16))) return '255,165,0';
            const n = parseInt(hex, 16);
            return `${n >> 16 & 255},${n >> 8 & 255},${n & 255}`;
        };

        flyColor = getColor();
        themeObserver = new MutationObserver(() => { flyColor = getColor(); });
        themeObserver.observe(document.documentElement, {attributes: true, attributeFilter: ['class', 'data-theme']});

        const mkFly = () => ({
            x:     Math.random() * w,
            y:     Math.random() * h,
            rw:    Math.random() * 2.8 + 1.4,
            rh:    Math.random() * 1.2 + 0.5,
            angle: Math.random() * Math.PI,
            phase: Math.random() * Math.PI * 2,
            ps:    Math.random() * 0.006 + 0.003,
            life:  Math.random(),
            ls:    Math.random() * 0.003 + 0.001,
        });

        const resize = () => {
            w = innerWidth; h = innerHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
            ctx.scale(dpr, dpr);
            flies = Array.from({ length: 90 }, mkFly);
        };

        resizeHandler = () => { clearTimeout(resizeId); resizeId = setTimeout(resize, 150); };
        window.addEventListener('resize', resizeHandler);

        visibilityHandler = () => { visible = document.visibilityState === 'visible'; if (visible) draw(); };
        document.addEventListener('visibilitychange', visibilityHandler);

        const draw = () => {
            if (!visible) { animationId = requestAnimationFrame(draw); return; }
            ctx.clearRect(0, 0, w, h);

            for (let i = 0; i < flies.length; i++) {
                const f = flies[i];

                f.phase += f.ps;
                f.life  -= f.ls;

                if (f.life <= 0) {
                    f.x     = Math.random() * w;
                    f.y     = Math.random() * h;
                    f.rw    = Math.random() * 2.8 + 1.4;
                    f.rh    = Math.random() * 1.2 + 0.5;
                    f.angle = Math.random() * Math.PI;
                    f.phase = Math.random() * Math.PI * 2;
                    f.ps    = Math.random() * 0.006 + 0.003;
                    f.life  = 0.8 + Math.random() * 0.2;
                    f.ls    = Math.random() * 0.003 + 0.001;
                    continue;
                }

                const pulse = (Math.sin(f.phase) + 1) * 0.5;
                if (pulse < 0.05) continue;

                const envelope = Math.min(f.life * 6, 1) * Math.min((1 - f.life) * 5 + 0.3, 1);
                const alpha = pulse * envelope * 0.82;
                if (alpha < 0.01) continue;

                const glowR = f.rw * (2.2 + pulse * 2.0);

                ctx.save();
                ctx.translate(f.x, f.y);
                ctx.rotate(f.angle);
                ctx.scale(1, f.rh / f.rw);
                const grad = ctx.createRadialGradient(0, 0, 0, 0, 0, glowR);
                grad.addColorStop(0,    `rgba(${flyColor},${(alpha * 0.52).toFixed(3)})`);
                grad.addColorStop(0.25, `rgba(${flyColor},${(alpha * 0.28).toFixed(3)})`);
                grad.addColorStop(0.6,  `rgba(${flyColor},${(alpha * 0.08).toFixed(3)})`);
                grad.addColorStop(1,    `rgba(${flyColor},0)`);
                ctx.beginPath();
                ctx.arc(0, 0, glowR, 0, Math.PI * 2);
                ctx.fillStyle = grad;
                ctx.fill();
                ctx.restore();

                ctx.save();
                ctx.translate(f.x, f.y);
                ctx.rotate(f.angle);
                ctx.beginPath();
                ctx.ellipse(0, 0, f.rw * pulse, f.rh * pulse, 0, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${flyColor},${alpha})`;
                ctx.fill();
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
        if (visibilityHandler) document.removeEventListener('visibilitychange', visibilityHandler);
        document.getElementById('interactive-background')?.remove();
    }
}
