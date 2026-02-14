export default (Alpine) => {
    Alpine.store('theme', {
        current: 'default',
        mode: 'light',
        colors: [
            {name: 'default', color: '#4e5f66', title: 'پیش‌فرض'},
            {name: 'blue', color: '#0061a4', title: 'آبی'},
            {name: 'ocean', color: '#006782', title: 'اقیانوس'},
            {name: 'forest', color: '#006e1c', title: 'جنگل'},
            {name: 'sunset', color: '#c00016', title: 'غروب'}
        ],

        init() {
            this.current = localStorage.getItem('user-theme') || 'default';
            this.mode = localStorage.getItem('user-mode') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            this.set(this.current);

            if (this.mode === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        set(theme) {
            this.current = theme;
            localStorage.setItem('user-theme', theme);

            if (theme === 'default') {
                document.documentElement.removeAttribute('data-theme');
            } else {
                document.documentElement.setAttribute('data-theme', theme);
            }
        },

        toggleMode() {
            this.mode = this.mode === 'dark' ? 'light' : 'dark';
            localStorage.setItem('user-mode', this.mode);

            if (this.mode === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            window.dispatchEvent(new CustomEvent('theme-mode-changed', {
                detail: {
                    dark: this.mode === 'dark'
                }
            }));
        }
    });
};
