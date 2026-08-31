export default function occasion() {
    return {
        show: true,
        canvas: null,
        ctx: null,
        particles: [],
        animationId: null,
        colors: ['#FFC107', '#FF5722', '#4CAF50', '#2196F3', '#9C27B0'],

        init() {
            setTimeout(() => {
                this.startConfetti();
            }, 100);
        },

        close() {
            this.show = false;
            this.stopConfetti();
        },

        startConfetti() {
            this.canvas = document.getElementById('confetti-canvas');
            if (!this.canvas) return;

            this.ctx = this.canvas.getContext('2d');
            this.resize();
            window.addEventListener('resize', () => this.resize());

            this.createParticles();
            this.animate();
        },

        stopConfetti() {
            if (this.animationId) cancelAnimationFrame(this.animationId);
            this.particles = [];
            if (this.ctx) this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        },

        resize() {
            if (!this.canvas) return;
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        },

        createParticles() {
            for (let i = 0; i < 150; i++) {
                this.particles.push({
                    x: Math.random() * this.canvas.width,
                    y: Math.random() * this.canvas.height - this.canvas.height,
                    w: Math.random() * 10 + 5,
                    h: Math.random() * 10 + 5,
                    color: this.colors[Math.floor(Math.random() * this.colors.length)],
                    speed: Math.random() * 3 + 2,
                    angle: Math.random() * 360,
                    spin: Math.random() < 0.5 ? 1 : -1
                });
            }
        },

        animate() {
            if (!this.ctx || !this.show) return;
            const ctx = this.ctx;
            const canvas = this.canvas;
            const particles = Alpine.raw(this.particles);

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (let i = 0, len = particles.length; i < len; i++) {
                const p = particles[i];
                p.y += p.speed;
                p.angle += p.spin;

                if (p.y > canvas.height) {
                    p.y = -10;
                    p.x = Math.random() * canvas.width;
                }

                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate((p.angle * Math.PI) / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            }

            this.animationId = requestAnimationFrame(() => this.animate());
        }
    }
}
