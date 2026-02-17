<style>
    /* Swiper Feed Container */
    .swiper-feed {
        width: 100%;
        height: 100%;
        max-width: 480px; /* Optimal card width */
        padding-top: 1rem;
        padding-bottom: 2rem;
        overflow: visible !important;
    }

    /* Swiper Slide Styling */
    .swiper-feed .swiper-slide {
        display: flex;
        flex-direction: column;
        border-radius: 24px;
        background-color: var(--md-sys-color-surface);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid rgba(var(--md-sys-color-outline-variant-rgb), 0.2);
        overflow: hidden;
        transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
    }

    .swiper-feed .swiper-slide-shadow {
        background: rgba(0,0,0,0.05) !important;
        border-radius: 24px;
    }

    /* Glassmorphism Utilities */
    .glass-panel {
        background: rgba(var(--md-sys-color-surface-rgb), 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(var(--md-sys-color-outline-variant-rgb), 0.1);
    }

    .glass-header {
        background: linear-gradient(to bottom, rgba(var(--md-sys-color-surface-rgb), 0.95), rgba(var(--md-sys-color-surface-rgb), 0.6));
        backdrop-filter: blur(8px);
    }

    .glass-footer {
        background: rgba(var(--md-sys-color-surface-container-low-rgb), 0.9);
        backdrop-filter: blur(16px);
        border-top: 1px solid rgba(var(--md-sys-color-outline-variant-rgb), 0.15);
    }

    /* Custom Scrollbar for Content */
    .feed-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .feed-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .feed-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(var(--md-sys-color-outline-variant-rgb), 0.3);
        border-radius: 20px;
    }
    .feed-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(var(--md-sys-color-primary-rgb), 0.5);
    }

    /* Animations */
    @keyframes slide-in-bottom {
        0% {
            transform: translateY(20px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .animate-slide-in {
        animation: slide-in-bottom 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    /* Reaction Button Hover Effects */
    .reaction-btn {
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .reaction-btn:active {
        transform: scale(0.9);
    }
    .reaction-btn:hover {
        transform: translateY(-2px);
    }

    /* Floating Navigation Buttons */
    .swiper-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(var(--md-sys-color-surface-container-high-rgb), 0.8);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--md-sys-color-primary);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        cursor: pointer;
        z-index: 20;
        transition: all 0.3s ease;
        border: 1px solid rgba(var(--md-sys-color-outline-variant-rgb), 0.1);
    }
    .swiper-nav-btn:hover {
        background: var(--md-sys-color-primary-container);
        color: var(--md-sys-color-on-primary-container);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 8px 24px rgba(var(--md-sys-color-primary-rgb), 0.25);
    }
    .swiper-nav-btn.swiper-button-disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }
    .swiper-nav-prev { right: 1rem; } /* RTL Logic: Next is Left, Prev is Right usually, but swiper handles dir="rtl" */
    .swiper-nav-next { left: 1rem; }

    /* Mobile adjustments */
    @media (max-width: 640px) {
        .swiper-feed {
            max-width: 100%;
            padding: 0 1rem;
            height: 75vh;
        }
        .swiper-nav-btn {
            display: none; /* Hide nav buttons on mobile, rely on swipe */
        }
    }
</style>
