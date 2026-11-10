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

    const HEADER_PEEK_MS = 2500;
    const HEADER_LEAVE_MS = 600;

    let origin = '';
    let hideTimer;

    return {
        open: false,
        maximized: false,
        loaded: false,
        frameReady: false,
        headerShown: true,
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
            this.showHeader();
            this.hideHeaderSoon(HEADER_PEEK_MS);
        },

        close() {
            clearTimeout(hideTimer);
            this.open = false;
            this.maximized = false;
            this.headerShown = true;
        },
        showHeader() {
            clearTimeout(hideTimer);
            this.headerShown = true;
        },
        hideHeaderSoon(ms = HEADER_LEAVE_MS) {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => {
                this.headerShown = false;
            }, ms);
        },
        toggleHeader() {
            if (this.headerShown) this.hideHeaderSoon(0);
            else this.showHeader();
        },
        leaveHeader(event) {
            if (!event?.currentTarget?.contains(event.relatedTarget)) this.hideHeaderSoon();
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
