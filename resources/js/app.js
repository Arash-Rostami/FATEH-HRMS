import './core/bootstrap.js';
import ThemeManager from './core/theme-manager.js';
import initAlpine from './components/alpine/main.js';
import initRecordFocus from './core/record-focus.js';
import initLivewireErrors from './core/livewire-errors.js';
import initTooltip from './core/tooltip.js';
import initNavTracker from './core/nav-tracker.js';
import { initModulePrefetch } from './components/alpine/module-runtime.js';
import watchIcons from '../icons/guard.js';

ThemeManager.init();
watchIcons();
initAlpine();
initRecordFocus();
initLivewireErrors();
initTooltip();
initNavTracker();
initModulePrefetch();

