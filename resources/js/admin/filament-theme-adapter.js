import themeStore from '../components/alpine/stores/theme.js';

const getSystemMode = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
const getUserMode = () => localStorage.getItem('user-mode') || getSystemMode();
const getUserTheme = () => localStorage.getItem('user-theme') || 'default';

const userTheme = getUserTheme();
const userMode = getUserMode();

if (userTheme !== 'default') document.documentElement.setAttribute('data-theme', userTheme);

document.documentElement.classList.toggle('dark', userMode === 'dark');

const initTheme = (Alpine) => {
    if (Alpine.store('theme')) return;

    themeStore(Alpine);

    const theme = Alpine.store('theme');
    const originalToggleMode = theme.toggleMode;

    theme.toggleMode = function () {
        originalToggleMode.call(this);

        const {mode} = this;
        localStorage.setItem('theme', mode);
        window.dispatchEvent(new CustomEvent('dark-mode-toggled', {detail: mode}));
    };

    localStorage.setItem('theme', getUserMode());
};

if (window.Alpine) {
    initTheme(window.Alpine);
} else {
    document.addEventListener('alpine:init', () => initTheme(window.Alpine));
}
