const INPUT_TAGS = new Set(['INPUT', 'TEXTAREA', 'SELECT']);
const BYTES_PER_MB = 1048576;
const PIP_WIDTH = 520;
const PIP_HEIGHT = 640;
const SHORTCUTS_DURATION = 8000;

const notify = (title, type, body = null, duration = null) => {
    if (typeof window.FilamentNotification !== 'function') return;
    const n = new window.FilamentNotification().title(title);
    if (body) n.body(body);
    if (type === 'success') n.success();
    else if (type === 'danger') n.danger();
    else if (type === 'warning') n.warning();
    if (duration) n.duration(duration);
    n.send();
};

export default class FilamentMenuManager {
    constructor() {
        this.pipWindow = null;
        this._alpineInitHandler = null;
        this.registerAlpineStore();
        this.registerKeyboardShortcuts();
    }

    registerAlpineStore() {
        const setupStore = () => {
            window.Alpine.store('filamentMenu', {
                fullscreen: false,
                wakeLock: null,
                resetting: false,

                init() {
                    document.addEventListener('fullscreenchange', () => {
                        this.fullscreen = !!document.fullscreenElement;
                    });
                },

                async resetServerCache() {
                    this.resetting = true;
                    try {
                        const response = await fetch('/reset');
                        if (!response.ok) throw new Error();
                        notify('کش سرور بازنشانی شد', 'success');
                        setTimeout(() => location.reload(), 1200);
                    } catch {
                        notify('بازنشانی کش سرور ناموفق بود', 'danger');
                    } finally {
                        this.resetting = false;
                    }
                },

                async toggleFullscreen() {
                    try {
                        if (document.fullscreenElement) {
                            await document.exitFullscreen();
                        } else {
                            await document.documentElement.requestFullscreen();
                        }
                    } catch (e) {}
                },

                async toggleWakeLock() {
                    if (this.wakeLock) {
                        try {
                            await this.wakeLock.release();
                        } catch (e) {}
                        this.wakeLock = null;
                        return;
                    }
                    try {
                        this.wakeLock = await navigator.wakeLock.request('screen');
                        this.wakeLock.addEventListener('release', () => {
                            this.wakeLock = null;
                        });
                    } catch {
                        notify('پشتیبانی نمی‌شود', 'warning');
                    }
                },
            });
        };

        if (window.Alpine) {
            setupStore();
        } else {
            this._alpineInitHandler = () => {
                setupStore();
                document.removeEventListener('alpine:init', this._alpineInitHandler);
                this._alpineInitHandler = null;
            };
            document.addEventListener('alpine:init', this._alpineInitHandler);
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
            const isInput = INPUT_TAGS.has(e.target.tagName) || e.target.isContentEditable;

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
                const page = parseInt(params.get('page')) || 1;
                params.set('page', page + 1);
                url.search = params.toString();
                this.navigate(url.toString());
            }

            if (e.key === 'ArrowUp') {
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                const page = parseInt(params.get('page')) || 1;
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
        if (!navigator.storage?.estimate) return;

        navigator.storage.estimate().then(({ usage }) => {
            const mb = (usage / BYTES_PER_MB).toFixed(1);
            if (confirm(`${mb} مگابایت کش پاک شود؟`)) {
                try { localStorage.clear(); } catch (e) {}
                try { sessionStorage.clear(); } catch (e) {}
                caches?.keys().then(k => k.forEach(c => caches.delete(c)));
                location.reload();
            }
        });
    }

    requestPiP() {
        if (!('documentPictureInPicture' in window)) {
            notify('مرورگر پشتیبانی نمی‌کند', 'warning');
            return;
        }

        if (this.pipWindow && !this.pipWindow.closed) {
            this.pipWindow.focus();
            return;
        }

        window.documentPictureInPicture.requestWindow({ width: PIP_WIDTH, height: PIP_HEIGHT })
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
        const sheets = document.styleSheets;
        for (let i = 0, len = sheets.length; i < len; i++) {
            const sheet = sheets[i];
            try {
                const rules = sheet.cssRules;
                let css = '';
                for (let j = 0, rLen = rules.length; j < rLen; j++) {
                    css += rules[j].cssText + '\n';
                }
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
        }
    }

    printPage() {
        window.print();
    }

    share() {
        const d = { title: document.title, url: location.href };
        const sharePromise = navigator.canShare?.(d)
            ? navigator.share(d)
            : navigator.clipboard?.writeText(d.url);

        if (sharePromise) {
            sharePromise
                .then(() => notify('انجام شد', 'success'))
                .catch(() => {});
        }
    }

    showShortcuts() {
        notify(
            'میانبرها',
            'warning',
            `<ul style="list-style-type: disc; padding-right: 20px; margin-top: 5px;">
                <li>? میانبرها</li>
                <li>/ جستجو</li>
                <li>⌘S ذخیره فرم</li>
                <li>F11 تمام‌صفحه</li>
                <li>⇦/⇨ صفحات قبل و بعد</li>
                <li>⇧/⇩ صفحه‌بندی</li>
            </ul>`,
            SHORTCUTS_DURATION
        );
    }
}

window.filamentMenu = new FilamentMenuManager();
