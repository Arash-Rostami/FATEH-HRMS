export default function floatingShapes() {
    return {
        shapes: [],
        isDark: false,

        init() {
            this.detectMode();
            this.generateShapes();
            this.$nextTick(() => this.floatShapes());

            window.addEventListener('theme-mode-changed', (e) => {
                if (this.isDark !== e.detail.dark) {
                    this.isDark = e.detail.dark;
                    this.regenerate();
                }
            });
        },

        regenerate() {
            this.shapes = [];
            this.generateShapes();
            this.$nextTick(() => this.floatShapes());
        },

        detectMode() {
            this.isDark = document.documentElement.classList.contains('dark');
        },

        getTypes() {
            return this.isDark
                ? ['circle', 'square', 'triangle', 'blob']
                : ['circle', 'square', 'triangle', 'blob', 'diamond', 'hexagon'];
        },

        generateShapes() {
            const types = this.getTypes();
            const positions = [];

            this.shapes = types.map(type => {
                const size = this.getRandomSize();
                const position = this.getRandomPosition(size, positions);
                positions.push(position);
                return {type, color: this.getRandomColor(), size, position};
            });
        },

        getShapeStyle(shape) {
            const opacity = this.isDark ? '0.03' : '0.08';
            const base = `top:${shape.position.top};left:${shape.position.left};opacity:${opacity};`;

            if (shape.type === 'circle')
                return `width:${shape.size.width}px;height:${shape.size.height}px;border-radius:50%;background:${shape.color};${base}`;

            if (shape.type === 'square')
                return `width:${shape.size.width}px;height:${shape.size.height}px;background:${shape.color};${base}`;

            if (shape.type === 'triangle')
                return `width:0;height:0;border-left:${shape.size.width / 2}px solid transparent;border-right:${shape.size.width / 2}px solid transparent;border-bottom:${shape.size.height}px solid ${shape.color};${base}`;

            if (shape.type === 'blob')
                return `width:${shape.size.width}px;height:${shape.size.height}px;background:${shape.color};border-radius:50% 40% 60% 40% / 40% 60% 40% 60%;${base}`;

            if (shape.type === 'diamond')
                return `width:${shape.size.width}px;height:${shape.size.height}px;background:${shape.color};transform:rotate(45deg);${base}`;

            if (shape.type === 'hexagon')
                return `width:${shape.size.width}px;height:${shape.size.height}px;background:${shape.color};clip-path:polygon(50% 0%,100% 25%,100% 75%,50% 100%,0% 75%,0% 25%);${base}`;

            return '';
        },

        floatShapes() {
            this.$el.querySelectorAll('[data-shape]').forEach((el) => {
                const amp = 8 + Math.random() * 12;
                const ampX = Math.random() * 8;
                const rot = Math.random() * 2;
                const dur = 6000 + Math.random() * 5000;
                const delay = Math.random() * 2000;

                el.animate(
                    [
                        {transform: `translate(0px, 0px) rotate(0deg)`},
                        {transform: `translate(${ampX}px, -${amp}px) rotate(${rot}deg)`},
                        {transform: `translate(0px, 0px) rotate(0deg)`},
                        {transform: `translate(-${ampX}px, ${amp}px) rotate(-${rot}deg)`},
                        {transform: `translate(0px, 0px) rotate(0deg)`},
                    ],
                    {duration: dur, iterations: Infinity, easing: 'ease-in-out', delay}
                );
            });
        },

        getRandomColor() {
            return this.isDark
                ? ['#1e40af', '#1e3a8a', '#312e81', '#1e293b', '#0f172a', '#164e63'][Math.floor(Math.random() * 6)]
                : ['#60a5fa', '#818cf8', '#a78bfa', '#94a3b8', '#64748b', '#06b6d4'][Math.floor(Math.random() * 6)];
        },

        getRandomSize() {
            const sizes = [{w: 80, h: 80}, {w: 120, h: 120}, {w: 180, h: 180}];
            const s = sizes[Math.floor(Math.random() * sizes.length)];
            return {width: s.w, height: s.h};
        },

        getRandomPosition(size, existing = []) {
            const c = this.$el.parentElement;
            const W = c ? c.offsetWidth : window.innerWidth;
            const H = c ? c.offsetHeight : window.innerHeight;

            const existingNumeric = existing.map(p => {
                const left = typeof p.left === 'string' ? parseInt(p.left, 10) : (p.left ?? 0);
                const top = typeof p.top === 'string' ? parseInt(p.top, 10) : (p.top ?? 0);
                return {left, top};
            });

            let desiredMinD = Math.min(400, Math.max(80, Math.floor(Math.min(W, H) / 4)));
            let tries = 0;
            const maxTries = 300;
            let pos;

            while (tries < maxTries) {
                const top = Math.floor(Math.random() * Math.max(0, H - size.height));
                const left = Math.floor(Math.random() * Math.max(0, W - size.width));
                pos = {top, left};

                const tooClose = existingNumeric.some(p =>
                    Math.hypot(pos.left - p.left, pos.top - p.top) < desiredMinD
                );

                if (!tooClose) {
                    return {top: `${pos.top}px`, left: `${pos.left}px`};
                }

                tries++;
                if (tries % 50 === 0) {
                    desiredMinD = Math.max(20, Math.floor(desiredMinD * 0.8));
                }
            }

            const cols = Math.max(1, Math.floor(W / (size.width + 24)));
            const rows = Math.max(1, Math.floor(H / (size.height + 24)));
            const cells = cols * rows;
            const index = existingNumeric.length % Math.max(1, cells);
            const col = index % cols;
            const row = Math.floor(index / cols);
            const cellW = Math.floor(W / cols);
            const cellH = Math.floor(H / rows);

            const left = Math.min(Math.max(0, Math.floor(col * cellW + Math.random() * (cellW - size.width))), W - size.width);
            const top = Math.min(Math.max(0, Math.floor(row * cellH + Math.random() * (cellH - size.height))), H - size.height);

            return {top: `${top}px`, left: `${left}px`};
        }
    };
}
