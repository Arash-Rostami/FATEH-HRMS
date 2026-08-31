function random(x) {
    return (Math.sin(x * 12.9898) * 43758.5453) % 1;
}

function noise2D(x, y) {
    const i = Math.floor(x);
    const j = Math.floor(y);
    const fx = x - i;
    const fy = y - j;

    const a = random(i + j * 57);
    const b = random(i + 1 + j * 57);
    const c = random(i + (j + 1) * 57);
    const d = random(i + 1 + (j + 1) * 57);

    const ux = fx * fx * (3.0 - 2.0 * fx);
    const uy = fy * fy * (3.0 - 2.0 * fy);

    return a * (1 - ux) * (1 - uy) + b * ux * (1 - uy) + c * (1 - ux) * uy + d * ux * uy;
}

function octavedNoise(x, octaves, lacunarity, gain, baseAmplitude, baseFrequency, time, seed, baseFlatness) {
    let y = 0;
    let amplitude = baseAmplitude;
    let frequency = baseFrequency;

    for (let i = 0; i < octaves; i++) {
        let octaveAmplitude = amplitude;
        if (i === 0) {
            octaveAmplitude *= baseFlatness;
        }
        y += octaveAmplitude * noise2D(frequency * x + seed * 100, time * frequency * 0.3);
        frequency *= lacunarity;
        amplitude *= gain;
    }

    return y;
}

function getCornerPoint(centerX, centerY, radius, startAngle, arcLength, progress) {
    const angle = startAngle + progress * arcLength;
    return {
        x: centerX + radius * Math.cos(angle),
        y: centerY + radius * Math.sin(angle)
    };
}

function getRoundedRectPoint(t, left, top, width, height, radius) {
    const straightWidth = width - 2 * radius;
    const straightHeight = height - 2 * radius;
    const cornerArc = (Math.PI * radius) / 2;
    const totalPerimeter = 2 * straightWidth + 2 * straightHeight + 4 * cornerArc;
    const distance = t * totalPerimeter;

    let accumulated = 0;

    if (distance <= accumulated + straightWidth) {
        const progress = (distance - accumulated) / straightWidth;
        return { x: left + radius + progress * straightWidth, y: top };
    }
    accumulated += straightWidth;

    if (distance <= accumulated + cornerArc) {
        const progress = (distance - accumulated) / cornerArc;
        return getCornerPoint(left + width - radius, top + radius, radius, -Math.PI / 2, Math.PI / 2, progress);
    }
    accumulated += cornerArc;

    if (distance <= accumulated + straightHeight) {
        const progress = (distance - accumulated) / straightHeight;
        return { x: left + width, y: top + radius + progress * straightHeight };
    }
    accumulated += straightHeight;

    if (distance <= accumulated + cornerArc) {
        const progress = (distance - accumulated) / cornerArc;
        return getCornerPoint(left + width - radius, top + height - radius, radius, 0, Math.PI / 2, progress);
    }
    accumulated += cornerArc;

    if (distance <= accumulated + straightWidth) {
        const progress = (distance - accumulated) / straightWidth;
        return { x: left + width - radius - progress * straightWidth, y: top + height };
    }
    accumulated += straightWidth;

    if (distance <= accumulated + cornerArc) {
        const progress = (distance - accumulated) / cornerArc;
        return getCornerPoint(left + radius, top + height - radius, radius, Math.PI / 2, Math.PI / 2, progress);
    }
    accumulated += cornerArc;

    if (distance <= accumulated + straightHeight) {
        const progress = (distance - accumulated) / straightHeight;
        return { x: left, y: top + height - radius - progress * straightHeight };
    }
    accumulated += straightHeight;

    const progress = (distance - accumulated) / cornerArc;
    return getCornerPoint(left + radius, top + radius, radius, Math.PI, Math.PI / 2, progress);
}

export default function ambient(type, color) {
    return {
        type: type,
        color: color,
        animationRef: null,
        resizeObserver: null,
        intersectionObserver: null,
        visibilityHandler: null,
        paused: false,
        time: 0,
        lastFrameTime: 0,

        init() {
            if (this.type === 'angry') {
                this.$nextTick(() => {
                    this.initAngryEffect();
                });
            }
        },

        destroy() {
            if (this.animationRef) {
                cancelAnimationFrame(this.animationRef);
            }
            if (this.resizeObserver && this.$refs.container) {
                this.resizeObserver.disconnect();
            }
            if (this.intersectionObserver) {
                this.intersectionObserver.disconnect();
            }
            if (this.visibilityHandler) {
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            }
        },

        initAngryEffect() {
            // Respect reduced motion
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            const canvas = this.$refs.canvas;
            const container = this.$refs.container;

            if (!canvas || !container) return;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            // Configuration
            const speed = 1;
            const chaos = 0.12;
            const borderRadius = 16; // matched to rounded-2xl
            const octaves = 10;
            const lacunarity = 1.6;
            const gain = 0.7;
            const amplitude = chaos;
            const frequency = 10;
            const baseFlatness = 0;
            const displacement = 20; // Reduced for card size
            const borderOffset = 10; // Reduced for card size

            const updateSize = () => {
                const rect = container.getBoundingClientRect();
                const width = rect.width + borderOffset * 2;
                const height = rect.height + borderOffset * 2;

                const dpr = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = width * dpr;
                canvas.height = height * dpr;

                // Keep the canvas visual size exactly matching the container
                // We use negative margins instead of style width/height to center the canvas
                canvas.style.width = `${width}px`;
                canvas.style.height = `${height}px`;
                canvas.style.marginLeft = `-${borderOffset}px`;
                canvas.style.marginTop = `-${borderOffset}px`;

                ctx.scale(dpr, dpr);

                return { width, height };
            };

            let { width, height } = updateSize();
            let lastDpr = Math.min(window.devicePixelRatio || 1, 2);
            let intersecting = true;

            const drawElectricBorder = (currentTime) => {
                if (!canvas || !ctx) return;

                if (this.paused || !intersecting) {
                    this.animationRef = requestAnimationFrame(drawElectricBorder);
                    return;
                }

                const dpr = Math.min(window.devicePixelRatio || 1, 2);
                if (dpr !== lastDpr) {
                    lastDpr = dpr;
                    const newSize = updateSize();
                    width = newSize.width;
                    height = newSize.height;
                }

                const deltaTime = (currentTime - this.lastFrameTime) / 1000;
                // Handle initial large delta
                if (deltaTime > 1) {
                    this.time += 0.016 * speed;
                } else {
                    this.time += deltaTime * speed;
                }

                this.lastFrameTime = currentTime;
                const time = this.time;

                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.scale(dpr, dpr);

                ctx.strokeStyle = this.color;
                ctx.lineWidth = 1.5;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                const scale = displacement;
                const left = borderOffset;
                const top = borderOffset;
                const borderWidth = width - 2 * borderOffset;
                const borderHeight = height - 2 * borderOffset;
                const maxRadius = Math.min(borderWidth, borderHeight) / 2;
                const radius = Math.min(borderRadius, maxRadius);

                const approximatePerimeter = 2 * (borderWidth + borderHeight) + 2 * Math.PI * radius;
                // Increase sample count for smoother path on small sizes
                const sampleCount = Math.floor(approximatePerimeter / 1.5);

                ctx.beginPath();

                for (let i = 0; i <= sampleCount; i++) {
                    const progress = i / sampleCount;
                    const point = getRoundedRectPoint(progress, left, top, borderWidth, borderHeight, radius);

                    const xNoise = octavedNoise(
                        progress * 8, octaves, lacunarity, gain, amplitude, frequency, time, 0, baseFlatness
                    );

                    const yNoise = octavedNoise(
                        progress * 8, octaves, lacunarity, gain, amplitude, frequency, time, 1, baseFlatness
                    );

                    const displacedX = point.x + xNoise * scale;
                    const displacedY = point.y + yNoise * scale;

                    if (i === 0) {
                        ctx.moveTo(displacedX, displacedY);
                    } else {
                        ctx.lineTo(displacedX, displacedY);
                    }
                }

                ctx.closePath();
                ctx.stroke();

                this.animationRef = requestAnimationFrame(drawElectricBorder);
            };

            // Handle resize
            this.resizeObserver = new ResizeObserver(() => {
                const newSize = updateSize();
                width = newSize.width;
                height = newSize.height;
            });
            this.resizeObserver.observe(container);

            this.paused = document.visibilityState !== 'visible';
            this.visibilityHandler = () => {
                this.paused = document.visibilityState !== 'visible';
            };
            document.addEventListener('visibilitychange', this.visibilityHandler);

            this.intersectionObserver = new IntersectionObserver(([entry]) => {
                intersecting = entry.isIntersecting;
            });
            this.intersectionObserver.observe(container);

            // Start animation
            this.animationRef = requestAnimationFrame(drawElectricBorder);
        }
    };
}
