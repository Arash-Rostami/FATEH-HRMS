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
        activePattern: localStorage.getItem('activePattern') || 'shapes',

        patterns: [
            { id: 'shapes', name: 'اشکال شناور' },
            { id: 'particle', name: 'ستاره های‌ کهکشان' },
            { id: 'parallax', name: 'فضای بی‌کران' },
            { id: 'gradient', name: 'امواج متحرک' },
            { id: 'geometry', name: 'کریستال‌های معلق' },
            { id: 'ambient', name: 'گوی متحرک' },
            { id: 'apple', name: 'طیف شناور' },
            { id: 'google', name: 'توپ شناور' }
        ],

        images: [bg1, bg2, bg3, bg4, bg5, bg6, bg7, bg8],

        toggle(value) {
            this.enabled = value;
            if (value) {
                this.patternEnabled = false;
                localStorage.setItem('patternEnabled', 'false');
            }
            localStorage.setItem('backgroundEnabled', value);
        },

        togglePattern(value) {
            this.patternEnabled = value;
            if (value) {
                this.enabled = false;
                localStorage.setItem('backgroundEnabled', 'false');
                localStorage.setItem('activePattern', this.activePattern || 'shapes');
            }
            localStorage.setItem('patternEnabled', value);
        },

        setPattern(patternId) {
            this.activePattern = patternId;
            localStorage.setItem('activePattern', patternId);
        }
    })
}
