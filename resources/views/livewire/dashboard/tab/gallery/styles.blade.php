<style>
    .snap-center {
        scroll-snap-align: center;
    }

    .timeline-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 56px;
        height: 56px;
        background: var(--md-sys-color-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--md-sys-color-primary);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        cursor: pointer;
        z-index: 48;
        border: 1px solid rgba(var(--md-sys-color-outline-variant), 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .timeline-nav-btn:hover {
        background: var(--md-sys-color-primary);
        color: var(--md-sys-color-on-primary);
        scale: 1.1;
    }

    .timeline-nav-btn:active {
        scale: 0.95;
    }


    @media (max-width: 640px) {
        .timeline-nav-btn {
            display: none;
        }
    }

    .animate-timeline-node {
        animation: pulse-node 2s infinite;
    }

    @keyframes pulse-node {
        0% {
            box-shadow: 0 0 0 0 rgba(var(--md-sys-color-primary), 0.4);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(var(--md-sys-color-primary), 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(var(--md-sys-color-primary), 0);
        }
    }
</style>
