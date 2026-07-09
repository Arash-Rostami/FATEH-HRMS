(function () {
    var MODE_KEY = 'user-mode';
    var THEME_KEY = 'user-theme';
    var d = document.documentElement;

    var mode = localStorage.getItem(MODE_KEY)
        || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    var theme = localStorage.getItem(THEME_KEY) || 'default';

    d.classList.toggle('dark', mode === 'dark');
    theme === 'default' ? d.removeAttribute('data-theme') : d.setAttribute('data-theme', theme);
})();