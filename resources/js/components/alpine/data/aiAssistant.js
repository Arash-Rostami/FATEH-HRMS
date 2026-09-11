export default function aiAssistant(baseSrc) {
    const readSetting = (managerFn, storageKey, fallback) =>
        window.ThemeManager?.[managerFn]?.() ?? localStorage.getItem(storageKey) ?? fallback;

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
            if (!this.src) {
                const theme = readSetting('getUserTheme', 'user-theme', 'default');
                const mode = readSetting('getUserMode', 'user-mode', 'light');
                this.src = `${baseSrc}&theme=${theme}&mode=${mode}`;
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
