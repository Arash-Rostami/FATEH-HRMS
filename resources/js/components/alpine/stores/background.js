const LS_BACKGROUND_ENABLED = 'backgroundEnabled';
const LS_PATTERN_ENABLED = 'patternEnabled';
const LS_ACTIVE_PATTERN = 'activePattern';
const DEFAULT_PATTERN = 'shapes';

const BACKDROP = window.__tenantBackdrop || {};
const IMAGES = BACKDROP.images || [];
const BACKDROP_MODE = BACKDROP.mode || 'time';
const BACKDROP_OPACITY = BACKDROP.opacity ?? 0.85;
const BACKDROP_BLUR = BACKDROP.blur ?? 0;
const BACKDROP_BRIGHTNESS = BACKDROP.brightness ?? 1;
const BACKDROP_CONTRAST = BACKDROP.contrast ?? 1;
const BACKDROP_GRAYSCALE = BACKDROP.grayscale ?? 0;
const BACKDROP_WIDTH = BACKDROP.width || '100%';

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

let storage;

try {
    storage = window.localStorage;
    storage.getItem('');
} catch {
    storage = { getItem: () => null, setItem: () => {} };
}

const readBool = (key) => {
    try {
        return storage.getItem(key) === 'true';
    } catch {
        return false;
    }
};

const readString = (key, fallback) => {
    try {
        return storage.getItem(key) || fallback;
    } catch {
        return fallback;
    }
};

const write = (key, value) => {
    try {
        storage.setItem(key, String(value));
    } catch {}
};

export default (Alpine) => {
    Alpine.store('background', {
        enabled: readBool(LS_BACKGROUND_ENABLED),
        patternEnabled: readBool(LS_PATTERN_ENABLED),
        activePattern: readString(LS_ACTIVE_PATTERN, DEFAULT_PATTERN),
        tabsOrder: TABS_ORDER,
        images: IMAGES,
        backdropMode: BACKDROP_MODE,
        opacity: BACKDROP_OPACITY,
        blur: BACKDROP_BLUR,
        brightness: BACKDROP_BRIGHTNESS,
        contrast: BACKDROP_CONTRAST,
        grayscale: BACKDROP_GRAYSCALE,
        width: BACKDROP_WIDTH,
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
