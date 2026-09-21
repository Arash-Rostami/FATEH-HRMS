import './core/bootstrap.js';
import ThemeManager from './core/theme-manager.js';
import initAlpine from './components/alpine/main.js';
import initRecordFocus from './core/record-focus.js';
import initLivewireErrors from './core/livewire-errors.js';
import initTooltip from './core/tooltip.js';
import { initModulePrefetch } from './components/alpine/module-runtime.js';



ThemeManager.init();
initAlpine();
initRecordFocus();
initLivewireErrors();
initTooltip();
initModulePrefetch();

