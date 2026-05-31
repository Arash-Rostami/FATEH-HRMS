import './core/bootstrap.js';
import ThemeManager from './core/theme-manager.js';
import initAlpine from './components/alpine/main.js';
import initRecordFocus from './core/record-focus.js';

ThemeManager.init();
initAlpine();

initRecordFocus();
