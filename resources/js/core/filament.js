import registerThemeStore from '../components/alpine/stores/theme.js';

document.addEventListener('alpine:init', () => {
    registerThemeStore(window.Alpine);
});
