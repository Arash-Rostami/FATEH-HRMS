const KEY = 'chat-ui-scale';
const STEPS = [0.8, 0.9, 1, 1.1, 1.2];
const DEFAULT_INDEX = 2;

const apply = (index) => document.documentElement.style.setProperty('--chat-scale', String(STEPS[index]));

const read = () => {
    try {
        const saved = parseInt(localStorage.getItem(KEY), 10);
        if (saved >= 0 && saved < STEPS.length) return saved;
    } catch {}
    return DEFAULT_INDEX;
};

export default function chatScaleMixin() {
    return {
        scaleIndex: read(),

        initChatScale() {
            apply(this.scaleIndex);
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
            apply(next);

            try {
                localStorage.setItem(KEY, String(next));
            } catch {}
        },
    };
}
