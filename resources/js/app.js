import './core/bootstrap.js';
import ThemeManager from './core/theme-manager.js';
import initAlpine from './components/alpine/main.js'


initAlpine()
ThemeManager.init();



import quickSettings from './components/quick-settings';
import commandPalette from './components/command-palette';
import statusSwitcher from './components/status-switcher';

document.addEventListener('alpine:init', () => {
    Alpine.data('quickSettings', quickSettings);
    Alpine.data('commandPalette', commandPalette);
    Alpine.data('statusSwitcher', statusSwitcher);
});
