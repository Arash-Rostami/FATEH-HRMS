const STORAGE_KEY = 'btm-labels';
const TOUCH_THRESHOLD = 50;
const HIDE_DELAY_MS = 500;
const PRESET_SMART = 'smart';
const PRESET_ICONS = 'icons';

export default function mobile(initialPage = 0, pageTwoKeys = []) {
    let initialPreset = 'all';

    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored !== null) initialPreset = stored;
    } catch (e) {
    }

    return {
        page: initialPage,
        isAtBottom: false,
        pageTwoKeys,
        observer: null,
        touchStartX: null,
        labelPreset: initialPreset,
        labelsOpen: false,
        barHidden: initialPreset === PRESET_SMART,
        smartHideTimer: null,

        toggle() {
            this.page = this.page === 0 ? 1 : 0;
            this.labelsOpen = false;
        },

        toggleBar() {
            this.barHidden = !this.barHidden;
        },

        scheduleSmartHide() {
            if (this.labelPreset !== PRESET_SMART) return;

            if (this.smartHideTimer !== null) {
                clearTimeout(this.smartHideTimer);
            }
            this.smartHideTimer = setTimeout(() => {
                this.barHidden = true;
            }, HIDE_DELAY_MS);
        },

        setLabelPreset(preset) {
            this.labelPreset = preset;
            this.labelsOpen = false;
            this.barHidden = preset === PRESET_SMART;

            try {
                localStorage.setItem(STORAGE_KEY, preset);
            } catch (e) {}
        },

        showLabel(key) {
            return this.labelPreset !== PRESET_ICONS;
        },

        handleTouchStart(e) {
            const touch = e.changedTouches[0];
            this.touchStartX = touch !== undefined ? touch.screenX : null;
        },

        handleTouchEnd(e) {
            const start = this.touchStartX;
            this.touchStartX = null;

            if (start === null) return;

            const touch = e.changedTouches[0];
            if (touch === undefined) return;

            const delta = touch.screenX - start;

            if (Math.abs(delta) < TOUCH_THRESHOLD) return;

            if (delta < 0 && this.page === 0) {
                this.page = 1;
                this.labelsOpen = false;
            } else if (delta > 0 && this.page === 1) {
                this.page = 0;
                this.labelsOpen = false;
            }
        },

        syncPage() {
            const keys = this.pageTwoKeys;
            this.page = (keys !== null && keys !== undefined && keys.includes(this.activeTab)) ? 1 : 0;
        },

        init() {
            const el = document.getElementById('footer') || document.querySelector('footer');

            if (el !== null && window.IntersectionObserver !== undefined) {
                this.observer = new IntersectionObserver((entries) => {
                    const entry = entries[0];
                    if (entry !== undefined) {
                        this.isAtBottom = entry.isIntersecting;
                    }
                });
                this.observer.observe(el);
            }

            this.$watch('activeTab', () => this.syncPage());
        },

        destroy() {
            if (this.observer !== null) {
                this.observer.disconnect();
                this.observer = null;
            }
        }
    };
}
