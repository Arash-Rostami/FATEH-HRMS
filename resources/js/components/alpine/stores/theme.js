export default (Alpine) => {
    Alpine.store('theme', {
        current: 'default',
        colors: [
            {name: 'default', color: '#4e5f66', title: 'پیش‌فرض'},
            {name: 'blue', color: '#0061a4', title: 'آبی'},
            {name: 'ocean', color: '#006782', title: 'اقیانوس'},
            {name: 'forest', color: '#006e1c', title: 'جنگل'},
            {name: 'sunset', color: '#c00016', title: 'غروب'}
        ],

        init() {
            const saved = localStorage.getItem('app-theme');
            if (saved) {
                this.set(saved);
            } else {
                this.set(this.current);
            }
        },

        set(theme) {
            this.current = theme;
            localStorage.setItem('app-theme', theme);

            if (window.ThemeManager && typeof window.ThemeManager.setTheme === 'function') {
                window.ThemeManager.setTheme(theme);
            }
            document.documentElement.setAttribute('data-theme', theme);
        }
    });
};
