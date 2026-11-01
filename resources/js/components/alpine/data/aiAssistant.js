export default function aiAssistant(baseSrc) {
    const PAGE_RE = /^[a-z0-9_-]{1,40}$/i;

    const readSetting = (managerFn, storageKey, fallback) =>
        window.ThemeManager?.[managerFn]?.() ?? localStorage.getItem(storageKey) ?? fallback;

    const currentPage = () => {
        const segment = window.location.pathname.split('/').filter(Boolean)[0] || '';
        const page = new URLSearchParams(window.location.search).get('tab')
            || (segment === 'dashboard' || segment === 'admin' ? '' : segment);
        return PAGE_RE.test(page) ? page.toLowerCase() : '';
    };

    let origin = '';

    return {
        open: false,
        maximized: false,
        loaded: false,
        frameReady: false,
        src: '',

        init() {
            window.addEventListener('ai-assistant', () => this.openChat());
        },

        openChat() {
            const page = currentPage();

            if (!this.src) {
                const theme = readSetting('getUserTheme', 'user-theme', 'default');
                const mode = readSetting('getUserMode', 'user-mode', 'light');
                this.src = `${baseSrc}&theme=${theme}&mode=${mode}&page=${page}`;
            } else {
                if (!origin) origin = new URL(this.src).origin;
                this.$root.querySelector('iframe')?.contentWindow?.postMessage(
                    { winterraPage: page },
                    origin
                );
            }

            this.open = true;
            this.loaded = true;
        },

        close() {
            this.open = false;
            this.maximized = false;
        },
        toggleMaximized() {
            this.maximized = !this.maximized;
        },
        handleEscape() {
            if (this.maximized) this.maximized = false;
            else if (this.open) this.close();
        },
    };
}
