export const THEME_COLORS = [
    {name: 'default', color: '#4e5f66', title: 'پیش‌فرض'},
    {name: 'grey', color: '#2F2525', title: 'زغالی'},
    {name: 'midnight', color: '#2f3f63', title: 'نیمه‌شب'},
    {name: 'blue', color: '#375E90', title: 'آبی'},
    {name: 'nebula', color: '#496580', title: 'سحابی'},
    {name: 'silver', color: '#79746A', title: 'نقره‌ای'},
    {name: 'graphite', color: '#5A5A5A', title: 'گرافیتی'},
    {name: 'ocean', color: '#6B6B47', title: 'زیتونی'},
    {name: 'jade', color: '#0F766E', title: 'زمردی'},
    {name: 'sage', color: '#5f7d66', title: 'جنگلی'},
    {name: 'ember', color: '#B45309', title: 'اخگر'},
    {name: 'rosewood', color: '#8B5E74', title: 'چوب‌رز'},
    {name: 'sunset', color: '#9B4050', title: 'سرخ‌غروب'},
    {name: 'magneta', color: '#E8D59E', title: 'گندمی'},
    {name: 'obsidian', color: '#BFA14A', title: 'کهربا'},
];

export default (Alpine) => {
    const root = document.documentElement;
    const live = getComputedStyle(root).getPropertyValue('--md-sys-color-primary').trim();
    if (live && !root.hasAttribute('data-theme') && !root.classList.contains('dark')) THEME_COLORS[0].color = live;

    Alpine.store('appTheme', {
        current: window.ThemeManager ? window.ThemeManager.getUserTheme() : 'default',
        mode: window.ThemeManager ? window.ThemeManager.getUserMode() : 'light',
        colors: THEME_COLORS,

        set(theme) {
            window.ThemeManager?.setTheme(theme);
        },

        toggleMode() {
            window.ThemeManager?.toggleMode();
        },

        updateState(theme, mode) {
            this.current = theme;
            this.mode = mode;
        }
    });
};
