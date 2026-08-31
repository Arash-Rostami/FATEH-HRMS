import './core/bootstrap.js';
import ThemeManager from './core/theme-manager.js';
import initAlpine from './components/alpine/main.js';
import initRecordFocus from './core/record-focus.js';
import { initModulePrefetch } from './components/alpine/module-runtime.js';



ThemeManager.init();
initAlpine();
initRecordFocus();
initModulePrefetch();

