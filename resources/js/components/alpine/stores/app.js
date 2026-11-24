const isStandalone = () => {
    try {
        return !!window.matchMedia?.('(display-mode: standalone)').matches || navigator.standalone === true;
    } catch (e) {
        return false;
    }
};

const isIos = () => {
    const ua = navigator.userAgent ?? '';
    return /iPad|iPhone|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
};

export default (Alpine) => {
    let deferred = null;

    const state = {
        installable: false,
        installed: isStandalone(),
        ios: isIos(),
        windows: /Windows/.test(navigator.userAgent ?? ''),
        persisted: false,
        badgeSupported: 'setAppBadge' in navigator,

        async install() {
            if (!deferred) return 'unavailable';
            const prompt = deferred;
            deferred = null;
            this.installable = false;
            try {
                await prompt.prompt();
                const { outcome } = await prompt.userChoice;
                return outcome === 'accepted' ? 'accepted' : 'dismissed';
            } catch (e) {
                return 'unavailable';
            }
        },

        async persist() {
            try {
                if (!navigator.storage?.persist) return false;
                this.persisted = !!(await navigator.storage.persist());
            } catch (e) {
                this.persisted = false;
            }
            return this.persisted;
        },

        clearBadge() {
            try {
                navigator.clearAppBadge?.()?.catch?.(() => {});
            } catch (e) {}
            try {
                window.caches?.delete('badge-v1')?.catch?.(() => {});
            } catch (e) {}
        }
    };

    Alpine.store('app', state);
    const app = Alpine.store('app') ?? state;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferred = e;
        app.installable = true;
    });

    window.addEventListener('appinstalled', () => {
        deferred = null;
        app.installed = true;
        app.installable = false;
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) app.clearBadge();
    });

    if (!document.hidden) app.clearBadge();

    try {
        if ('serviceWorker' in navigator && window.self === window.top) {
            const hadController = !!navigator.serviceWorker.controller;
            let notified = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!hadController || notified) return;
                notified = true;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: 'نسخهٔ جدید آماده است',
                        type: 'info',
                        timeout: 15000,
                        action: { label: 'تازه کردن صفحه', handler: () => window.location.reload() }
                    }
                }));
            });
        }
    } catch (e) {}

    try {
        navigator.storage?.persisted?.().then((value) => { app.persisted = !!value; }).catch(() => {});
    } catch (e) {}
};
