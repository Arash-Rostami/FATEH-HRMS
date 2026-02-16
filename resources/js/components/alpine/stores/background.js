import bg1 from '../../../../assets/img/bg/backdrop1.png';
import bg2 from '../../../../assets/img/bg/backdrop2.png';
import bg3 from '../../../../assets/img/bg/backdrop3.png';
import bg4 from '../../../../assets/img/bg/backdrop4.png';
import bg5 from '../../../../assets/img/bg/backdrop5.png';
import bg6 from '../../../../assets/img/bg/backdrop6.png';
import bg7 from '../../../../assets/img/bg/backdrop7.png';
import bg8 from '../../../../assets/img/bg/backdrop8.png';


export default (Alpine) => {
    Alpine.store('background', {
        enabled: localStorage.getItem('backgroundEnabled') === 'true',

        images: [
            bg1,
            bg2,
            bg3,
            bg4,
            bg5,
            bg6,
            bg7,
            bg8
        ],

        tabsOrder: [
            'overview',
            'post',
            'calendar',
            'gallery',
            'share',
            'analytics',
            'profile',
            'help'
        ],

        toggle(value) {
            this.enabled = value;
            localStorage.setItem('backgroundEnabled', value);
            window.dispatchEvent(new CustomEvent('background-toggled', {detail: value}));
        },

        init() {
            window.addEventListener('background-toggled', (e) => {
                this.enabled = e.detail;
            });
        }
    })
}
