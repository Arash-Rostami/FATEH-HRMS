export const THEME_COLORS = [
    {name: 'default', color: '#4e5f66', title: 'پیش‌فرض'},
    {name: 'grey', color: '#3f3f46', title: 'دودی'},
    {name: 'midnight', color: '#2f3f63', title: 'نیمه‌شب'},
    {name: 'blue', color: '#0061a4', title: 'آبی'},
    {name: 'nebula', color: '#5B5BD6', title: 'سحابی'},
    {name: 'silver', color: '#6f7480', title: 'نقره‌ای'},
    {name: 'ocean', color: '#006782', title: 'دریایی'},
    {name: 'jade', color: '#0F766E', title: 'زمردی'},
    {name: 'forest', color: '#006e1c', title: 'جنگلی'},
    {name: 'sage', color: '#6B8F71', title: 'مریم‌گلی'},
    {name: 'ember', color: '#B45309', title: 'اخگر'},
    {name: 'rosewood', color: '#8B5E74', title: 'چوب‌رز'},
    {name: 'sunset', color: '#c00016', title: 'سرخ‌غروب'},
    {name: 'magneta', color: '#E91E8D', title: 'صورتی'},
    {name: 'obsidian', color: '#BFA14A', title: 'کهربا'},
];

export default (Alpine) => {
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
