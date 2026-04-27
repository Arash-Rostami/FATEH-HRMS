(function () {
    const getSystemMode = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const getUserMode = () => localStorage.getItem('user-mode') || getSystemMode();
    const getUserTheme = () => localStorage.getItem('user-theme') || 'default';

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

        // Sync Filament's internal theme key
        localStorage.setItem('theme', userMode);
    };

    // Apply immediately
    applyTheme();

    // Listen for storage changes from other tabs (like the user dashboard)
    window.addEventListener('storage', (e) => {
        if (e.key === 'user-theme' || e.key === 'user-mode') {
            applyTheme();
        }
    });

    // Make the toggle function globally available for the pure JS UI
    window.AdminThemeManager = {
        colors: [
            { name: 'default',  color: '#4e5f66', title: 'پیش‌فرض' },
            { name: 'grey',     color: '#3f3f46', title: 'دودی' },
            { name: 'midnight', color: '#2f3f63', title: 'نیمه‌شب' },
            { name: 'blue',     color: '#0061a4', title: 'آبی' },
            { name: 'nebula',   color: '#5B5BD6', title: 'سحابی' },
            { name: 'silver',   color: '#6f7480', title: 'نقره‌ای' },
            { name: 'ocean',    color: '#006782', title: 'دریایی' },
            { name: 'jade',     color: '#0F766E', title: 'زمردی' },
            { name: 'forest',   color: '#006e1c', title: 'جنگلی' },
            { name: 'sage',     color: '#6B8F71', title: 'مریم‌گلی' },
            { name: 'ember',    color: '#B45309', title: 'اخگر'     },
            { name: 'rosewood', color: '#8B5E74', title: 'چوب‌رز'    },
            { name: 'sunset',   color: '#c00016', title: 'سرخ‌غروب'  },
            { name: 'magneta',  color: '#E91E8D', title: 'صورتی'  },
            { name: 'obsidian', color: '#BFA14A', title: 'کهربا'    },
        ],
        toggleMode: function() {
            const currentMode = getUserMode();
            const newMode = currentMode === 'dark' ? 'light' : 'dark';

            localStorage.setItem('user-mode', newMode);
            applyTheme();

            // Dispatch Filament's internal event just in case
            window.dispatchEvent(new CustomEvent('dark-mode-toggled', { detail: newMode }));

            // Update UI Icons if needed
            this.updateIcons();
        },
        setTheme: function(themeName) {
            localStorage.setItem('user-theme', themeName);
            applyTheme();
            this.updateIcons();
        },
        updateIcons: function() {
            const modeIcon = document.getElementById('admin-mode-icon');
            if (modeIcon) {
                modeIcon.textContent = getUserMode() === 'dark' ? 'dark_mode' : 'light_mode';
            }

            const currentTheme = getUserTheme();
            document.querySelectorAll('.admin-theme-btn').forEach(btn => {
                const check = btn.querySelector('.admin-theme-check');
                if (btn.dataset.theme === currentTheme) {
                    btn.classList.add('scale-110', 'ring-2', 'ring-white/60', 'ring-offset-2', 'ring-offset-[var(--md-sys-color-surface)]', 'shadow-xl', 'animate-pulse');
                    btn.classList.remove('ring-1', 'ring-black/10');
                    if(check) check.style.display = 'block';
                } else {
                    btn.classList.remove('scale-110', 'ring-2', 'ring-white/60', 'ring-offset-2', 'ring-offset-[var(--md-sys-color-surface)]', 'shadow-xl', 'animate-pulse');
                    btn.classList.add('ring-1', 'ring-black/10');
                    if(check) check.style.display = 'none';
                }
            });
        }
    };

    // Initial icon update after DOM loads
    document.addEventListener('DOMContentLoaded', () => {
        window.AdminThemeManager.updateIcons();
    });

})();
