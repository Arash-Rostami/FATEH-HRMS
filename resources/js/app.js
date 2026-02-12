import './bootstrap';
import ThemeManager from './theme-manager.js';
ThemeManager.init();
window.setTheme = (theme) => ThemeManager.applyTheme(theme);
window.ThemeManager = ThemeManager;
