import registerThemeStore from '../components/alpine/stores/theme.js';
import Swiper from 'swiper/bundle';
import watchIcons from '../../icons/guard.js';

window.Swiper = Swiper;
watchIcons();
document.addEventListener('alpine:init', () => {
    registerThemeStore(window.Alpine);
});

document.addEventListener('x-modal-opened', (event) => {
    const modalWindow = document.getElementById(event.detail?.id)?.querySelector('.fi-modal-window');
    if (modalWindow?.querySelector('.fi-in-entry')) {
        requestAnimationFrame(() => {
            modalWindow.scrollTop = 0;
        });
    }
});
