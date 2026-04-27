const THEME_KEY = 'user-theme';
const MODE_KEY = 'user-mode';
const FILAMENT_MODE_KEY = 'theme';

const colors = [
    { name: 'default', color: '#4e5f66', title: 'پیش‌فرض' },
    { name: 'grey', color: '#3f3f46', title: 'دودی' },
    { name: 'midnight', color: '#2f3f63', title: 'نیمه‌شب' },
    { name: 'blue', color: '#0061a4', title: 'آبی' },
    { name: 'nebula', color: '#5B5BD6', title: 'سحابی' },
    { name: 'silver', color: '#6f7480', title: 'نقره‌ای' },
    { name: 'ocean', color: '#006782', title: 'دریایی' },
    { name: 'jade', color: '#0F766E', title: 'زمردی' },
    { name: 'forest', color: '#006e1c', title: 'جنگلی' },
    { name: 'sage', color: '#6B8F71', title: 'مریم‌گلی' },
    { name: 'ember', color: '#B45309', title: 'اخگر' },
    { name: 'rosewood', color: '#8B5E74', title: 'چوب‌رز' },
    { name: 'sunset', color: '#c00016', title: 'سرخ‌غروب' },
    { name: 'magneta', color: '#E91E8D', title: 'صورتی' },
    { name: 'obsidian', color: '#BFA14A', title: 'کهربا' },
];

const getSystemMode = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
const getUserMode = () => localStorage.getItem(MODE_KEY) || getSystemMode();
const getUserTheme = () => localStorage.getItem(THEME_KEY) || 'default';

const applyTheme = () => {
    const userTheme = getUserTheme();
    const userMode = getUserMode();

    if (userTheme !== 'default') {
        document.documentElement.setAttribute('data-theme', userTheme);
    } else {
        document.documentElement.removeAttribute('data-theme');
    }

    if (userMode === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    // Keep Filament internal key in sync.
    localStorage.setItem(FILAMENT_MODE_KEY, userMode);
};

const updateIcons = () => {
    const modeIcon = document.getElementById('admin-mode-icon');
    if (modeIcon) {
        modeIcon.textContent = getUserMode() === 'dark' ? 'dark_mode' : 'light_mode';
    }

    const currentTheme = getUserTheme();
    document.querySelectorAll('.admin-theme-btn').forEach((btn) => {
        const check = btn.querySelector('.admin-theme-check');

        if (btn.dataset.theme === currentTheme) {
            btn.classList.add('scale-110', 'ring-2', 'ring-white/60', 'ring-offset-2', 'ring-offset-[var(--md-sys-color-surface)]', 'shadow-xl', 'animate-pulse');
            btn.classList.remove('ring-1', 'ring-black/10');

            if (check) {
                check.style.display = 'block';
            }

            return;
        }

        btn.classList.remove('scale-110', 'ring-2', 'ring-white/60', 'ring-offset-2', 'ring-offset-[var(--md-sys-color-surface)]', 'shadow-xl', 'animate-pulse');
        btn.classList.add('ring-1', 'ring-black/10');

        if (check) {
            check.style.display = 'none';
        }
    });
};

const toggleMode = () => {
    const newMode = getUserMode() === 'dark' ? 'light' : 'dark';
    localStorage.setItem(MODE_KEY, newMode);

    applyTheme();
    updateIcons();

    window.dispatchEvent(new CustomEvent('dark-mode-toggled', { detail: newMode }));
    window.dispatchEvent(new CustomEvent('theme-mode-changed', { detail: { dark: newMode === 'dark' } }));
};

const setTheme = (themeName) => {
    localStorage.setItem(THEME_KEY, themeName);

    applyTheme();
    updateIcons();
};

// Expose controls for the Blade palette widget.
window.AdminThemeManager = {
    colors,
    toggleMode,
    setTheme,
    updateIcons,
};

// Apply once immediately (first paint).
applyTheme();

// Sync between browser tabs/windows.
window.addEventListener('storage', (event) => {
    if (event.key === THEME_KEY || event.key === MODE_KEY) {
        applyTheme();
        updateIcons();
    }
});

const reapplyThemeState = () => {
    applyTheme();
    updateIcons();
};

// Keep theme state sticky across full loads and Filament SPA navigations.
document.addEventListener('DOMContentLoaded', reapplyThemeState);
document.addEventListener('livewire:navigated', reapplyThemeState);
document.addEventListener('filament:navigated', reapplyThemeState);
