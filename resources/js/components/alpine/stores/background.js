const LS_BACKGROUND_ENABLED = 'backgroundEnabled';
const LS_PATTERN_ENABLED = 'patternEnabled';
const LS_ACTIVE_PATTERN = 'activePattern';
const LS_TONE = 'bgTone';
const DEFAULT_PATTERN = 'shapes';

const TONES = [
    { id: 'snow', name: 'خنثی', light: '#FFFFFF', k: '13%', dl: '.25' },
    { id: 'pearl', name: 'گرم', light: '#FBF9F6', k: '10%', dl: '.22' },
    { id: 'mist', name: 'سرد', light: '#F1F4F8', k: '7%', dl: '.19' },
    { id: 'ash', name: 'ملایم', light: '#E9ECEF', k: '5%', dl: '.16' },
];

const DEFAULT_HUE = 210;
const HUE_PATTERN = /^hue:(\d{1,3})$/;

const parseHue = (tone) => {
    const m = HUE_PATTERN.exec(tone || '');
    return m && Number(m[1]) <= 359 ? Number(m[1]) : null;
};

const isValidTone = (tone) => TONES.some((t) => t.id === tone) || parseHue(tone) !== null;

const applyTone = (tone) => {
    const root = document.documentElement;
    const hue = parseHue(tone);

    if (hue !== null) {
        root.dataset.bgTone = 'hue';
        root.style.setProperty('--tone-h', String(hue));
        return;
    }

    root.style.removeProperty('--tone-h');
    if (tone) root.dataset.bgTone = tone;
    else delete root.dataset.bgTone;
};

const BACKDROP = window.__tenantBackdrop || {};
const IMAGES = BACKDROP.images || [];
const BACKDROP_MODE = BACKDROP.mode || 'time';
const BACKDROP_OPACITY = BACKDROP.opacity ?? 0.85;
const BACKDROP_BLUR = BACKDROP.blur ?? 0;
const BACKDROP_BRIGHTNESS = BACKDROP.brightness ?? 1;
const BACKDROP_CONTRAST = BACKDROP.contrast ?? 1;
const BACKDROP_GRAYSCALE = BACKDROP.grayscale ?? 0;
const BACKDROP_WIDTH = BACKDROP.width || '100%';
const BACKDROP_FIT = BACKDROP.fit || 'cover';

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
        tones: TONES,
        tone: isValidTone(readString(LS_TONE, '')) ? readString(LS_TONE, '') : '',
        hue: parseHue(readString(LS_TONE, '')) ?? DEFAULT_HUE,
        tabsOrder: TABS_ORDER,
        images: IMAGES,
        backdropMode: BACKDROP_MODE,
        opacity: BACKDROP_OPACITY,
        blur: BACKDROP_BLUR,
        brightness: BACKDROP_BRIGHTNESS,
        contrast: BACKDROP_CONTRAST,
        grayscale: BACKDROP_GRAYSCALE,
        width: BACKDROP_WIDTH,
        fit: BACKDROP_FIT,
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
        },

        setTone(toneId) {
            const next = TONES.some((t) => t.id === toneId) && toneId !== this.tone ? toneId : '';
            this.tone = next;
            write(LS_TONE, next);
            applyTone(next);
        },

        setHue(value) {
            const n = Math.max(0, Math.min(359, Math.round(Number(value))));
            if (Number.isNaN(n)) return;
            this.hue = n;
            this.tone = `hue:${n}`;
            write(LS_TONE, this.tone);
            applyTone(this.tone);
        },

        toggleTone() {
            if (this.tone) this.setTone('');
            else this.setTone(TONES[0].id);
        }
    });
};
