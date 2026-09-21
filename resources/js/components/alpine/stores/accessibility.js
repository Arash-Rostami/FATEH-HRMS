const LS_FONT_SIZE = 'fontSizeLevel';
const LS_READING_RULER = 'readingRuler';
const LS_DOUBLE_CLICK_COPY = 'doubleClickCopy';
const SCALE_STEP = 0.1;

const root = document.documentElement;
const rootStyle = root.style;
const rootClasses = root.classList;
const docBody = document.body;

let rulerHandler = null;
let rulerRaf = null;
let copyHandler = null;

const readInt = (key, fallback) => {
    try {
        const val = localStorage.getItem(key);
        if (val === null) return fallback;
        const parsed = parseInt(val, 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    } catch {
        return fallback;
    }
};

const readBool = (key) => {
    try {
        return localStorage.getItem(key) === 'true';
    } catch {
        return false;
    }
};

const write = (key, value) => {
    try {
        localStorage.setItem(key, value);
    } catch {}
};

const notify = (isSuccess) => {
    const detail = isSuccess
        ? { message: 'کپی شد', type: 'success' }
        : { message: 'کپی ناموفق بود.', type: 'error' };

    window.dispatchEvent(new CustomEvent('toast', { detail }));
};

export default function accessibilityStore(Alpine) {
    Alpine.store('accessibility', {
        fontSizeLevel: 0,
        minScale: -2,
        maxScale: 3,
        readingRuler: false,
        doubleClickCopy: false,

        init() {
            this.fontSizeLevel = readInt(LS_FONT_SIZE, 0);
            this.readingRuler = readBool(LS_READING_RULER);
            this.doubleClickCopy = readBool(LS_DOUBLE_CLICK_COPY);

            this.applyFontSize();

            if (this.readingRuler) {
                this.applyReadingRuler();
            }
            if (this.doubleClickCopy) {
                this.applyDoubleClickCopy();
            }
        },

        applyFontSize() {
            const level = this.fontSizeLevel;
            const scale = 1 + (level * SCALE_STEP);

            rootStyle.setProperty('--app-font-scale', scale);
            rootStyle.fontSize = (scale * 100) + '%';

            write(LS_FONT_SIZE, level);
        },

        increaseFontSize() {
            if (this.fontSizeLevel < this.maxScale) {
                this.fontSizeLevel++;
                this.applyFontSize();
            }
        },

        decreaseFontSize() {
            if (this.fontSizeLevel > this.minScale) {
                this.fontSizeLevel--;
                this.applyFontSize();
            }
        },

        resetFontSize() {
            if (this.fontSizeLevel !== 0) {
                this.fontSizeLevel = 0;
                this.applyFontSize();
            }
        },

        getScaleLabel() {
            const level = this.fontSizeLevel;
            if (level < 0) return 'کوچک';
            if (level === 0) return 'پیش‌فرض';
            if (level === 1) return 'بزرگ';
            return 'خیلی بزرگ';
        },

        toggleReadingRuler() {
            const next = !this.readingRuler;
            this.readingRuler = next;
            write(LS_READING_RULER, next);
            this.applyReadingRuler();
        },

        applyReadingRuler() {
            const isActive = this.readingRuler;

            if (isActive) {
                rootClasses.add('reading-ruler');
            } else {
                rootClasses.remove('reading-ruler');
            }

            if (rulerHandler !== null) {
                document.removeEventListener('mousemove', rulerHandler);
                rulerHandler = null;
            }

            if (rulerRaf !== null) {
                window.cancelAnimationFrame(rulerRaf);
                rulerRaf = null;
            }

            if (isActive) {
                rulerHandler = (e) => {
                    if (rulerRaf !== null) return;

                    const clientY = e.clientY;
                    rulerRaf = window.requestAnimationFrame(() => {
                        rulerRaf = null;
                        rootStyle.setProperty('--ruler-y', clientY + 'px');
                    });
                };
                document.addEventListener('mousemove', rulerHandler, { passive: true });
            }
        },

        toggleDoubleClickCopy() {
            const next = !this.doubleClickCopy;
            this.doubleClickCopy = next;
            write(LS_DOUBLE_CLICK_COPY, next);
            this.applyDoubleClickCopy();
        },

        applyDoubleClickCopy() {
            if (copyHandler !== null) {
                document.removeEventListener('mouseup', copyHandler);
                copyHandler = null;
            }

            if (this.doubleClickCopy) {
                copyHandler = () => {
                    const sel = window.getSelection();
                    if (sel === null) return;

                    const text = sel.toString().trim();
                    if (text === '') return;

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text)
                            .then(() => notify(true))
                            .catch(() => notify(false));
                        return;
                    }

                    try {
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        const taStyle = ta.style;
                        taStyle.position = 'fixed';
                        taStyle.opacity = '0';
                        docBody.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        docBody.removeChild(ta);
                        notify(true);
                    } catch (e) {
                        notify(false);
                    }
                };
                document.addEventListener('mouseup', copyHandler, { passive: true });
            }
        }
    });
}
