const ThemeManager = {
    init() {
        const storedTheme = localStorage.getItem('user-theme') || 'default';
        this.applyTheme(storedTheme);
        window.ThemeManager = this;
    },
    applyTheme(theme) {
        if (theme === 'default') {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', theme);
        }
        localStorage.setItem('user-theme', theme);
    },
    get currentTheme() {
        return localStorage.getItem('user-theme') || 'default';
    }
};
export default ThemeManager;
