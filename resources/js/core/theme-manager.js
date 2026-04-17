export default class ThemeManager {
    static init() {
        this.applySavedPreferences();

        document.addEventListener('livewire:navigated', () => {
            this.applySavedPreferences();
        });

        window.ThemeManager = this;
    }

    static applySavedPreferences() {
        const storedTheme = localStorage.getItem('user-theme') || 'default';
        const storedMode = localStorage.getItem('user-mode');
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const activeMode = storedMode || (systemDark ? 'dark' : 'light');

        this.setTheme(storedTheme);

        if (activeMode === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        if (window.Alpine?.store('theme')) {
            window.Alpine.store('theme').current = storedTheme;
            window.Alpine.store('theme').mode = activeMode;
        }
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
        const isDark = document.documentElement.classList.contains('dark');

        if (isDark) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('user-mode', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('user-mode', 'dark');
        }

        window.dispatchEvent(new CustomEvent('theme-mode-changed', {
            detail: {
                dark: document.documentElement.classList.contains('dark')
            }
        }));
    }
}
