const KEY = 'chat-ui-scale';
const EVENT = 'chat-scale-changed';
const STEPS = [0.8, 0.9, 1, 1.1, 1.2];
const DEFAULT_INDEX = 2;

const apply = (index) => document.documentElement.style.setProperty('--chat-scale', String(STEPS[index]));

const announce = (index) => window.dispatchEvent(new CustomEvent(EVENT, { detail: index }));

export const readScale = () => {
    try {
        const saved = parseInt(localStorage.getItem(KEY), 10);
        if (saved >= 0 && saved < STEPS.length) return saved;
    } catch {}
    return DEFAULT_INDEX;
};

const writeScale = (index) => {
    apply(index);

    try {
        localStorage.setItem(KEY, String(index));
    } catch {}

    announce(index);
};

export const clearScale = () => {
    document.documentElement.style.removeProperty('--chat-scale');

    try {
        localStorage.removeItem(KEY);
    } catch {}

    announce(DEFAULT_INDEX);
};

export default function chatScaleMixin() {
    return {
        scaleIndex: readScale(),

        initChatScale(signal) {
            apply(this.scaleIndex);

            const onChange = (event) => {
                if (this.$root && !this.$root.isConnected) return window.removeEventListener(EVENT, onChange);
                this.scaleIndex = event.detail;
            };
            window.addEventListener(EVENT, onChange, { signal });
        },

        canShrinkScale() {
            return this.scaleIndex > 0;
        },

        canGrowScale() {
            return this.scaleIndex < STEPS.length - 1;
        },

        isDefaultScale() {
            return this.scaleIndex === DEFAULT_INDEX;
        },

        resetScale() {
            this.stepScale(DEFAULT_INDEX - this.scaleIndex);
        },

        scaleLabel() {
            return new Intl.NumberFormat('fa-IR').format(Math.round(STEPS[this.scaleIndex] * 100)) + '٪';
        },

        stepScale(delta) {
            const next = Math.min(STEPS.length - 1, Math.max(0, this.scaleIndex + delta));
            if (next === this.scaleIndex) return;

            this.scaleIndex = next;
            writeScale(next);
        },
    };
}
