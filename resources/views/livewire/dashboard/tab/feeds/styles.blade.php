<style>
    /* Swiper Feed Container */
    .swiper-feed {
        width: 100%;
        height: 100%;
        max-width: 480px;
        padding-top: 1rem;
        padding-bottom: 2rem;
        overflow: visible !important;
        will-change: transform; /* Hint for compositing */
    }

    /* Swiper Slide Styling - Optimized */
    .swiper-feed .swiper-slide {
        display: flex;
        flex-direction: column;
        border-radius: 20px; /* Reduced radius for performance */
        background-color: var(--md-sys-color-surface);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); /* Simpler shadow */
        border: 1px solid rgba(var(--md-sys-color-outline-variant), 0.2);
        overflow: hidden;
        /* Removed transitions on transform to let JS handle it smoothly */
    }

    /* Slide Shadows - Simplified */
    .swiper-feed .swiper-slide-shadow {
        background: rgba(0,0,0,0.05) !important;
        border-radius: 20px;
    }

    /* Glassmorphism Utilities - Reduced Complexity */
    .glass-panel {
        background: rgba(var(--md-sys-color-surface), 0.95); /* More opaque */
        /* backdrop-filter removed for performance on lower-end devices */
        border: 1px solid rgba(var(--md-sys-color-outline-variant), 0.1);
    }

    .glass-header {
        background: var(--md-sys-color-surface); /* Solid color is faster */
        border-bottom: 1px solid rgba(var(--md-sys-color-outline-variant), 0.1);
    }

    .glass-footer {
        background: var(--md-sys-color-surface);
        border-top: 1px solid rgba(var(--md-sys-color-outline-variant), 0.1);
    }

    /* Custom Scrollbar */
    .feed-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(var(--md-sys-color-outline-variant), 0.3) transparent;
    }
    .feed-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .feed-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .feed-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(var(--md-sys-color-outline-variant), 0.3);
        border-radius: 20px;
    }

    /* Animations - Simplified */
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease-out both;
    }

    /* Navigation Buttons */
    .swiper-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--md-sys-color-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--md-sys-color-primary);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        z-index: 20;
        border: 1px solid rgba(var(--md-sys-color-outline-variant), 0.1);
    }
    .swiper-nav-btn:active {
        transform: translateY(-50%) scale(0.95);
    }
    .swiper-nav-prev { right: 1rem; }
    .swiper-nav-next { left: 1rem; }

    @media (max-width: 640px) {
        .swiper-feed {
            max-width: 100%;
            padding: 0 1rem;
            height: 75vh;
        }
        .swiper-nav-btn {
            display: none;
        }
    }
</style>
