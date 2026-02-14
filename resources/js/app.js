import './core/bootstrap.js';
import ThemeManager from './core/theme-manager.js';
import initAlpine from './components/alpine/main.js'
import initAnimation from './components/auth-visuals.js';


initAlpine()
ThemeManager.init();
initAnimation();
