export default {
    container: null,
    shapesData: [],
    isDark: false,
    observer: null,
    resizeId: null,

    init() {
        this.destroy();
        this.detectMode();
        this.setupContainer();
        this.generateShapes();
        this.render();
        this.bindEvents();
    },

    setupContainer() {
        this.container = document.createElement('div');
        this.container.id = 'interactive-background';
        Object.assign(this.container.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            zIndex: '-10',
            pointerEvents: 'none',
            overflow: 'hidden'
        });
        document.body.appendChild(this.container);
    },

    detectMode() {
        this.isDark = document.documentElement.classList.contains('dark');
    },

    bindEvents() {
        this._resizeHandler = () => {
            clearTimeout(this.resizeId);
            this.resizeId = setTimeout(() => {
                this.generateShapes();
                this.render();
            }, 150);
        };
        window.addEventListener('resize', this._resizeHandler);

        this.observer = new MutationObserver(() => {
            const dark = document.documentElement.classList.contains('dark');
            if (this.isDark !== dark) {
                this.isDark = dark;
                this.generateShapes();
                this.render();
            }
        });
        this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    },

    destroy() {
        if (this.resizeId) clearTimeout(this.resizeId);
        if (this._resizeHandler) window.removeEventListener('resize', this._resizeHandler);
        if (this.observer) this.observer.disconnect();

        document.getElementById('interactive-background')?.remove();
        this.container = null;
        this.shapesData = [];
    },

    getTypes() {
        return this.isDark
            ? ['circle', 'square', 'triangle', 'blob']
            : ['circle', 'square', 'triangle', 'blob', 'diamond', 'hexagon'];
    },

    getRandomColor() {
        const colors = this.isDark
            ? ['#1e40af', '#1e3a8a', '#312e81', '#1e293b', '#0f172a', '#164e63']
            : ['#60a5fa', '#818cf8', '#a78bfa', '#94a3b8', '#64748b', '#06b6d4'];
        return colors[Math.floor(Math.random() * colors.length)];
    },

    getRandomSize() {
        const sizes = [{w: 80, h: 80}, {w: 120, h: 120}, {w: 180, h: 180}];
        return sizes[Math.floor(Math.random() * sizes.length)];
    },

    getRandomPosition(size, existing) {
        const W = window.innerWidth;
        const H = window.innerHeight;
        let desiredMinD = Math.min(400, Math.max(80, Math.floor(Math.min(W, H) / 4)));
        let tries = 0;
        const maxTries = 300;

        while (tries < maxTries) {
            const top = Math.floor(Math.random() * Math.max(0, H - size.h));
            const left = Math.floor(Math.random() * Math.max(0, W - size.w));

            const tooClose = existing.some(p =>
                Math.hypot(left - p.leftNum, top - p.topNum) < desiredMinD
            );

            if (!tooClose) {
                return { top: `${top}px`, left: `${left}px`, topNum: top, leftNum: left };
            }

            tries++;
            if (tries % 50 === 0) {
                desiredMinD = Math.max(20, Math.floor(desiredMinD * 0.8));
            }
        }

        const cols = Math.max(1, Math.floor(W / (size.w + 24)));
        const rows = Math.max(1, Math.floor(H / (size.h + 24)));
        const index = existing.length % (cols * rows);
        const col = index % cols;
        const row = Math.floor(index / cols);
        const cellW = Math.floor(W / cols);
        const cellH = Math.floor(H / rows);

        const left = Math.min(Math.max(0, Math.floor(col * cellW + Math.random() * (cellW - size.w))), W - size.w);
        const top = Math.min(Math.max(0, Math.floor(row * cellH + Math.random() * (cellH - size.h))), H - size.h);

        return { top: `${top}px`, left: `${left}px`, topNum: top, leftNum: left };
    },

    generateShapes() {
        const types = this.getTypes();
        const positions = [];

        const count = types.length * 3;

        this.shapesData = Array.from({ length: count }).map((_, i) => {
            const type = types[i % types.length];
            const size = this.getRandomSize();
            const position = this.getRandomPosition(size, positions);
            positions.push(position);

            return { type, color: this.getRandomColor(), size, position };
        });
    },

    render() {
        if (!this.container) return;
        this.container.innerHTML = '';
        const opacity = this.isDark ? '0.03' : '0.08';

        this.shapesData.forEach(shape => {
            const el = document.createElement('div');
            el.className = 'absolute will-change-transform';

            let specificStyle = '';
            if (shape.type === 'circle') specificStyle = `border-radius:50%; background:${shape.color};`;
            if (shape.type === 'square') specificStyle = `background:${shape.color};`;
            if (shape.type === 'triangle') specificStyle = `width:0; height:0; border-left:${shape.size.w/2}px solid transparent; border-right:${shape.size.w/2}px solid transparent; border-bottom:${shape.size.h}px solid ${shape.color}; background:transparent;`;
            if (shape.type === 'blob') specificStyle = `background:${shape.color}; border-radius:50% 40% 60% 40% / 40% 60% 40% 60%;`;
            if (shape.type === 'diamond') specificStyle = `background:${shape.color}; transform:rotate(45deg);`;
            if (shape.type === 'hexagon') specificStyle = `background:${shape.color}; clip-path:polygon(50% 0%,100% 25%,100% 75%,50% 100%,0% 75%,0% 25%);`;

            Object.assign(el.style, {
                position: 'absolute',
                top: shape.position.top,
                left: shape.position.left,
                width: shape.type === 'triangle' ? '0' : `${shape.size.w}px`,
                height: shape.type === 'triangle' ? '0' : `${shape.size.h}px`,
                opacity: opacity,
                transform: 'translateZ(0)'
            });

            el.style.cssText += specificStyle;
            this.container.appendChild(el);
            this.animateShape(el);
        });
    },

    animateShape(el) {
        const amp = 8 + Math.random() * 12;
        const ampX = Math.random() * 8;
        const rot = Math.random() * 2;
        const dur = 6000 + Math.random() * 5000;
        const delay = Math.random() * 2000;

        el.animate(
            [
                { transform: `translate(0px, 0px) rotate(0deg)` },
                { transform: `translate(${ampX}px, -${amp}px) rotate(${rot}deg)` },
                { transform: `translate(0px, 0px) rotate(0deg)` },
                { transform: `translate(-${ampX}px, ${amp}px) rotate(-${rot}deg)` },
                { transform: `translate(0px, 0px) rotate(0deg)` },
            ],
            { duration: dur, iterations: Infinity, easing: 'ease-in-out', delay }
        );
    }
};
