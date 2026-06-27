export default class FilamentMenuManager {
    constructor() {
        this.pipWindow = null;
        this.registerAlpineStore();
        this.registerKeyboardShortcuts();
    }

    registerAlpineStore() {
        const setupStore = () => {
            window.Alpine.store('filamentMenu', {
                fullscreen: false,
                wakeLock: null,

                init() {
                    document.addEventListener('fullscreenchange', () =>
                        this.fullscreen = !!document.fullscreenElement
                    );
                },

                async toggleFullscreen() {
                    document.fullscreenElement
                        ? await document.exitFullscreen()
                        : await document.documentElement.requestFullscreen();
                },

                async toggleWakeLock() {
                    if (this.wakeLock) {
                        await this.wakeLock.release();
                        this.wakeLock = null;
                        return;
                    }
                    try {
                        this.wakeLock = await navigator.wakeLock.request('screen');
                        this.wakeLock.addEventListener('release', () => this.wakeLock = null);
                    } catch {
                        new window.FilamentNotification().title('پشتیبانی نمی‌شود').warning().send();
                    }
                },
            });
        };

        if (window.Alpine) {
            setupStore();
        } else {
            document.addEventListener('alpine:init', setupStore);
        }
    }

    navigate(url) {
        if (typeof window.Livewire !== 'undefined' && typeof window.Livewire.navigate === 'function') {
            window.Livewire.navigate(url);
        } else {
            window.location.href = url;
        }
    }

    registerKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;

            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                const submitBtn = document.querySelector('button[type="submit"]');
                if (submitBtn) {
                    e.preventDefault();
                    submitBtn.click();
                }
                return;
            }

            if (isInput) return;

            if (e.key === '?') {
                e.preventDefault();
                this.showShortcuts();
            }

            if (e.key === '/') {
                const searchInput = document.querySelector('input[type="search"]');
                if (searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            }

            if (e.key === 'F11') {
                e.preventDefault();
                if (window.Alpine) {
                    window.Alpine.store('filamentMenu').toggleFullscreen();
                }
            }

            if (e.key === 'ArrowRight') {
                e.preventDefault();
                window.history.forward();
            }

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                window.history.back();
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                let page = parseInt(params.get('page')) || 1;
                params.set('page', page + 1);
                url.search = params.toString();
                this.navigate(url.toString());
            }

            if (e.key === 'ArrowUp') {
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                let page = parseInt(params.get('page')) || 1;
                if (page > 1) {
                    e.preventDefault();
                    params.set('page', page - 1);
                    url.search = params.toString();
                    this.navigate(url.toString());
                }
            }
        });
    }

    clearStorage() {
        navigator.storage.estimate().then(({ usage }) => {
            const mb = (usage / 1048576).toFixed(1);
            if (confirm(`${mb} مگابایت کش پاک شود؟`)) {
                localStorage.clear();
                sessionStorage.clear();
                caches?.keys().then(k => k.forEach(c => caches.delete(c)));
                location.reload();
            }
        });
    }

    requestPiP() {
        if (!('documentPictureInPicture' in window)) {
            new window.FilamentNotification().title('مرورگر پشتیبانی نمی‌کند').warning().send();
            return;
        }

        if (this.pipWindow && !this.pipWindow.closed) {
            this.pipWindow.focus();
            return;
        }

        window.documentPictureInPicture.requestWindow({ width: 520, height: 640 })
            .then((pip) => {
                this.pipWindow = pip;

                const target = document.querySelector('main');
                if (!target) {
                    pip.close();
                    return;
                }

                const placeholder = document.createElement('div');
                placeholder.dataset.pipPlaceholder = '';
                target.replaceWith(placeholder);

                const srcRoot = document.documentElement;
                const dstRoot = pip.document.documentElement;
                dstRoot.setAttribute('dir', srcRoot.dir || 'rtl');
                if (srcRoot.lang) dstRoot.setAttribute('lang', srcRoot.lang);
                dstRoot.className = srcRoot.className;
                const theme = srcRoot.getAttribute('data-theme');
                if (theme) dstRoot.setAttribute('data-theme', theme);

                pip.document.body.className = document.body.className;
                this.copyStylesInto(pip);
                pip.document.body.append(target);

                pip.addEventListener('pagehide', () => {
                    if (placeholder.isConnected) {
                        placeholder.replaceWith(target);
                    }
                    this.pipWindow = null;
                });
            })
            .catch(() => {});
    }

    copyStylesInto(pip) {
        [...document.styleSheets].forEach((sheet) => {
            try {
                const css = [...sheet.cssRules].map((r) => r.cssText).join('\n');
                const style = pip.document.createElement('style');
                style.textContent = css;
                pip.document.head.appendChild(style);
            } catch {
                if (sheet.href) {
                    const link = pip.document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = sheet.href;
                    pip.document.head.appendChild(link);
                }
            }
        });
    }

    printPage() {
        window.print();
    }

    share() {
        const d = { title: document.title, url: location.href };
        (navigator.canShare?.(d) ? navigator.share(d) : navigator.clipboard.writeText(d.url))
            .then(() => new window.FilamentNotification().title('انجام شد').success().send())
            .catch(() => {});
    }

    showShortcuts() {
        new window.FilamentNotification()
            .title('میانبرها')
            .body(`
                <ul style="list-style-type: disc; padding-right: 20px; margin-top: 5px;">
                    <li>? میانبرها</li>
                    <li>/ جستجو</li>
                    <li>⌘S ذخیره فرم</li>
                    <li>F11 تمام‌صفحه</li>
                    <li>⇦/⇨ صفحات قبل و بعد</li>
                    <li>⇧/⇩ صفحه‌بندی</li>
                </ul>
            `)
            .warning()
            .duration(8000)
            .send();
    }
}

window.filamentMenu = new FilamentMenuManager();
