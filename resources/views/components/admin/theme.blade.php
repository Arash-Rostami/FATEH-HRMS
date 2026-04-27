<script>
    const getSystemMode = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const getUserMode = () => localStorage.getItem('user-mode') || getSystemMode();
    const getUserTheme = () => localStorage.getItem('user-theme') || 'default';

    const userTheme = getUserTheme();
    const userMode = getUserMode();

    if (userTheme !== 'default') document.documentElement.setAttribute('data-theme', userTheme);

    if (userMode === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    document.addEventListener('alpine:init', () => {
        window.Alpine.store('theme', {
            current: 'default',
            mode: 'light',
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
                localStorage.setItem('theme', this.mode); // sync filament

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

                window.dispatchEvent(new CustomEvent('dark-mode-toggled', { detail: this.mode }));
            }
        });

        localStorage.setItem('theme', getUserMode());
    });
</script>
