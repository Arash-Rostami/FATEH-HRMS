import bg1 from '../../../../assets/img/bg/backdrop1.png';
import bg2 from '../../../../assets/img/bg/backdrop2.png';
import bg3 from '../../../../assets/img/bg/backdrop3.png';
import bg4 from '../../../../assets/img/bg/backdrop4.png';
import bg5 from '../../../../assets/img/bg/backdrop5.png';
import bg6 from '../../../../assets/img/bg/backdrop6.png';
import bg7 from '../../../../assets/img/bg/backdrop7.png';
import bg8 from '../../../../assets/img/bg/backdrop8.png';

const LS_BACKGROUND_ENABLED = 'backgroundEnabled';
const LS_PATTERN_ENABLED = 'patternEnabled';
const LS_ACTIVE_PATTERN = 'activePattern';
const DEFAULT_PATTERN = 'shapes';

const IMAGES = [bg1, bg2, bg3, bg4, bg5, bg6, bg7, bg8];

const TABS_ORDER = ['home', 'post', 'feed', 'calendar', 'status', 'gallery', 'reports', 'links', 'faqs'];

const PATTERNS = [
    { id: 'shapes', name: 'اشکال شناور' },
    { id: 'rain', name: 'نم نم باران' },
    { id: 'particle', name: 'ذرات مغناطیستی' },
    { id: 'parallax', name: 'فضای بی‌کران' },
    { id: 'gradient', name: 'امواج متحرک' },
    { id: 'geometry', name: 'کریستال‌های معلق' },
    { id: 'cloud', name: 'ابرهای روان' },
    { id: 'flora', name: 'گندم‌زار طلایی' },
    { id: 'ambient', name: 'گوی متحرک' },
    { id: 'cyber', name: 'هک سایبری' },
    { id: 'google', name: 'توپ شناور' },
    { id: 'note', name: 'نت موسیقی' },
    { id: 'ripple', name: 'آب مواج' },
    { id: 'firefly', name: 'شب تاب رنگی' },
    { id: 'snow', name: 'بلور برف' },
];

const readBool = (key) => {
    try {
        return localStorage.getItem(key) === 'true';
    } catch (e) {
        return false;
    }
};

const readString = (key, fallback) => {
    try {
        return localStorage.getItem(key) || fallback;
    } catch (e) {
        return fallback;
    }
};

const write = (key, value) => {
    try {
        localStorage.setItem(key, String(value));
    } catch (e) {}
};

export default (Alpine) => {
    Alpine.store('background', {
        enabled: readBool(LS_BACKGROUND_ENABLED),
        patternEnabled: readBool(LS_PATTERN_ENABLED),
        activePattern: readString(LS_ACTIVE_PATTERN, DEFAULT_PATTERN),
        tabsOrder: TABS_ORDER,
        images: IMAGES,
        patterns: PATTERNS,

        toggleBackground(value) {
            this.enabled = value;
            if (value) {
                this.patternEnabled = false;
                write(LS_PATTERN_ENABLED, false);
            }
            write(LS_BACKGROUND_ENABLED, value);
        },

        togglePattern(value) {
            this.patternEnabled = value;
            if (value) {
                this.enabled = false;
                write(LS_BACKGROUND_ENABLED, false);
                write(LS_ACTIVE_PATTERN, this.activePattern || DEFAULT_PATTERN);
            }
            write(LS_PATTERN_ENABLED, value);
        },

        setPattern(patternId) {
            this.activePattern = patternId;
            write(LS_ACTIVE_PATTERN, patternId);
        }
    });
};
