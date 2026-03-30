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
        patternEnabled: localStorage.getItem('patternEnabled') === 'true',
        activePattern: localStorage.getItem('activePattern') || 'particle',

        patterns: [
            { id: 'particle', name: 'ذرات معلق' },
            { id: 'parallax', name: 'پوشش سه بعدی (پارالاکس)' },
            { id: 'gradient', name: 'طیف رنگی روان' },
            { id: 'geometry', name: 'اشکال هندسی' },
            { id: 'ambient', name: 'نورپردازی محیطی' }
        ],

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
            'home',
            'post',
            'feed',
            'calendar',
            'gallery',
            'reports',
            'links',
            'status',
            'faqs'
        ],

        toggle(value) {
            this.enabled = value;
            if (value) {
                this.patternEnabled = false;
                localStorage.setItem('patternEnabled', 'false');
            }
            localStorage.setItem('backgroundEnabled', value);
            window.dispatchEvent(new CustomEvent('background-toggled', {detail: value}));
        },

        togglePattern(value) {
            this.patternEnabled = value;
            if (value) {
                this.enabled = false;
                localStorage.setItem('backgroundEnabled', 'false');
            }
            localStorage.setItem('patternEnabled', value);
            window.dispatchEvent(new CustomEvent('pattern-toggled', {detail: value}));
        },
        setPattern(patternId) {
            this.activePattern = patternId;
            localStorage.setItem('activePattern', patternId);
            window.dispatchEvent(new CustomEvent('pattern-changed', {detail: patternId}));
        },

        init() {
            window.addEventListener('background-toggled', (e) => {
                this.enabled = e.detail;
                if (this.enabled) this.patternEnabled = false;
            });

            window.addEventListener('pattern-toggled', (e) => {
                this.patternEnabled = e.detail;
                if (this.patternEnabled) this.enabled = false;
            });
        }
    })
}
