import registerThemeStore from '../components/alpine/stores/theme.js';
import Swiper from 'swiper/bundle';

window.Swiper = Swiper;
document.addEventListener('alpine:init', () => {
    registerThemeStore(window.Alpine);
});
