export default class ThemeManager {
    static init() {
        const storedTheme = localStorage.getItem('user-theme') || 'default';
        const storedMode = localStorage.getItem('user-mode');
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        this.setTheme(storedTheme);

        if (storedMode === 'dark' || (!storedMode && systemDark)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        window.ThemeManager = this;
    }

    static setTheme(themeName) {
        if (themeName === 'default') {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', themeName);
        }
        localStorage.setItem('user-theme', themeName);
    }

    static toggleMode() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('user-mode', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('user-mode', 'dark');
        }
    }
}
