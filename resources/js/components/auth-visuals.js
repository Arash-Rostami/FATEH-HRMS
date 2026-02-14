import * as THREE from 'three';

export class AuthVisuals {
    constructor(containerId) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);

        if (!this.container) return;

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.particles = null;
        this.mouseX = 0;
        this.mouseY = 0;
        this.windowHalfX = window.innerWidth / 2;
        this.windowHalfY = window.innerHeight / 2;
        this.clock = new THREE.Clock();

        this.init();
        this.animate();
        this.setupEventListeners();
    }

    init() {
        // SCENE
        this.scene = new THREE.Scene();
        // Fog for depth (will be updated by theme)
        this.scene.fog = new THREE.FogExp2(0x000000, 0.0008);

        // CAMERA
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 1, 2000);
        this.camera.position.z = 1000;

        // RENDERER
        this.renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        this.renderer.setPixelRatio(window.devicePixelRatio);
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.container.appendChild(this.renderer.domElement);

        // PARTICLES
        const geometry = new THREE.BufferGeometry();
        const particleCount = 1000;

        const positions = [];
        const colors = [];
        const sizes = [];

        for (let i = 0; i < particleCount; i++) {
            const x = (Math.random() * 2 - 1) * 1000;
            const y = (Math.random() * 2 - 1) * 1000;
            const z = (Math.random() * 2 - 1) * 1000;

            positions.push(x, y, z);
            colors.push(1, 1, 1); // White default, updated later
            sizes.push(20);
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
        geometry.setAttribute('size', new THREE.Float32BufferAttribute(sizes, 1).setUsage(THREE.DynamicDrawUsage));

        const material = new THREE.PointsMaterial({
            size: 3,
            vertexColors: true,
            blending: THREE.AdditiveBlending,
            depthTest: false,
            transparent: true,
            opacity: 0.8,
            map: this.createCircleTexture()
        });

        this.particles = new THREE.Points(geometry, material);
        this.scene.add(this.particles);

        // Initial Theme Update
        this.updateThemeColors();
    }

    createCircleTexture() {
        const size = 64;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;

        const context = canvas.getContext('2d');
        const centerX = size / 2;
        const centerY = size / 2;
        const radius = size / 2;

        const gradient = context.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius);
        gradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
        gradient.addColorStop(0.2, 'rgba(255, 255, 255, 0.5)');
        gradient.addColorStop(0.5, 'rgba(255, 255, 255, 0.1)');
        gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

        context.fillStyle = gradient;
        context.fillRect(0, 0, size, size);

        return new THREE.CanvasTexture(canvas);
    }

    updateThemeColors() {
        const rootStyle = getComputedStyle(document.documentElement);
        const primaryHex = rootStyle.getPropertyValue('--md-sys-color-primary').trim() || '#6750A4';
        const tertiaryHex = rootStyle.getPropertyValue('--md-sys-color-tertiary').trim() || '#7D5260';
        const surfaceHex = rootStyle.getPropertyValue('--md-sys-color-surface').trim() || '#000000';

        const color1 = new THREE.Color(primaryHex);
        const color2 = new THREE.Color(tertiaryHex);

        if (this.particles) {
            const colors = this.particles.geometry.attributes.color.array;
            const count = colors.length / 3;

            for (let i = 0; i < count; i++) {
                // Use index to deterministically mix colors
                const mixFactor = (Math.sin(i * 0.1) + 1) / 2;
                const mixedColor = color1.clone().lerp(color2, mixFactor);

                colors[i * 3] = mixedColor.r;
                colors[i * 3 + 1] = mixedColor.g;
                colors[i * 3 + 2] = mixedColor.b;
            }
            this.particles.geometry.attributes.color.needsUpdate = true;
        }

        // Update Fog to match background surface for seamless blending
        if (this.scene.fog) {
            this.scene.fog.color.set(surfaceHex);
        }
    }

    animate() {
        requestAnimationFrame(this.animate.bind(this));
        this.render();
    }

    render() {
        const time = Date.now() * 0.0001;

        // Smooth Camera Movement based on Mouse
        this.camera.position.x += (this.mouseX - this.camera.position.x) * 0.05;
        this.camera.position.y += (-this.mouseY - this.camera.position.y) * 0.05;
        this.camera.lookAt(this.scene.position);

        // Particle Rotation
        if (this.particles) {
            this.particles.rotation.y = time * 0.5;
            this.particles.rotation.z = time * 0.2;
        }

        this.renderer.render(this.scene, this.camera);
    }

    setupEventListeners() {
        document.addEventListener('mousemove', (event) => {
            this.mouseX = (event.clientX - this.windowHalfX) * 0.5;
            this.mouseY = (event.clientY - this.windowHalfY) * 0.5;
        });

        window.addEventListener('resize', () => {
            this.windowHalfX = window.innerWidth / 2;
            this.windowHalfY = window.innerHeight / 2;
            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });

        // Theme Change Observer
        const observer = new MutationObserver(() => {
            this.updateThemeColors();
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class', 'style', 'data-theme']
        });
    }
}

export default function initAnimation() {
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('auth-canvas-container')) {
            new AuthVisuals('auth-canvas-container');
        }
    });
}
