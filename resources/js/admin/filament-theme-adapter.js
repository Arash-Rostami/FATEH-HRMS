import themeStore from '../components/alpine/stores/theme.js';

document.addEventListener('alpine:init', () => {
    // Register our theme store
    themeStore(window.Alpine);

    // Filament uses a global theme setting. We can optionally sync our user-mode to Filament's 'theme' key
    // so that Filament's own components (if any rely on it) are in sync.
    const originalToggleMode = window.Alpine.store('theme').toggleMode;
    window.Alpine.store('theme').toggleMode = function() {
        originalToggleMode.call(this);
        localStorage.setItem('theme', this.mode);

        // Filament also dispatches a dark-mode-toggled event
        window.dispatchEvent(new CustomEvent('dark-mode-toggled', { detail: this.mode }));
    };

    // Sync on initial load
    const currentMode = localStorage.getItem('user-mode') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    localStorage.setItem('theme', currentMode);
});

// To ensure our theme is applied even before Alpine initializes completely:
const storedTheme = localStorage.getItem('user-theme') || 'default';
const storedMode = localStorage.getItem('user-mode') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

if (storedTheme !== 'default') {
    document.documentElement.setAttribute('data-theme', storedTheme);
}

if (storedMode === 'dark') {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}
