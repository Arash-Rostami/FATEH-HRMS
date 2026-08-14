import clipboardMixin from "../mixins/clipboard.js";
import persistentStateMixin from "../mixins/persistentState.js";

const STORAGE_KEY = 'calculator_state';
const MAX_HISTORY = 50;

export default function calculator() {
    return {
        ...clipboardMixin(),
        ...persistentStateMixin(),
        display: '',
        formattedDisplay: '',
        history: [],
        modal: null,
        open: false,
        minimized: false,
        calculationDone: false,

        init() {
            this.modal = this.$refs.calculatorModal;
            if (this.modal) {
                window.addEventListener('calculate', () => this.mounted());
                window.addEventListener('keydown', (event) => {
                    if (event.key === "Escape" && this.modal.classList.contains('flex')) this.minimize();
                });
            }
            this.restoreState();
        },

        restoreState() {
            const raw = this._loadState(STORAGE_KEY);
            if (!raw) return;

            this.display = raw.display || '';
            this.formattedDisplay = raw.formattedDisplay || '';
            this.history = Array.isArray(raw.history) ? raw.history.slice(0, MAX_HISTORY) : [];
            this.minimized = !!raw.minimized;
            this.calculationDone = !!raw.calculationDone;
        },

        saveState() {
            this._saveState(STORAGE_KEY, {
                display: this.display,
                formattedDisplay: this.formattedDisplay,
                history: this.history,
                minimized: this.minimized,
                calculationDone: this.calculationDone,
            });
        },

        clearState() {
            this._clearState(STORAGE_KEY);
        },
        appendToDisplay(value) {
            const isOperator = ['/', '*', '+', '-'].includes(value);

            if (this.calculationDone) {
                if (!isOperator) {
                    this.display = '';
                }
                this.calculationDone = false;
            }

            this.display += value;
            this.format();
            this.saveState();
        },
        format() {
            this.formattedDisplay = this.display.replace(/(?<!\.)\d+/g, num => num.replace(/\B(?=(\d{3})+(?!\d))/g, "'"));
        },
        calculate() {
            if (!this.display || this.display === 'Error!') return;
            try {
                let sanitized = this.display.replace(/×/g, '*').replace(/÷/g, '/').replace(/−/g, '-');
                if (/^[\d.\s]+$/.test(sanitized)) return;
                const result = new Function('return ' + sanitized)();
                const equation = this.display;
                this.display = result.toString();
                this.format();
                this.history.unshift({
                    eq: equation,
                    res: this.formattedDisplay
                });
                if (this.history.length > MAX_HISTORY) this.history.length = MAX_HISTORY;
                this.calculationDone = true;
            } catch (error) {
                this.display = 'Error!';
                this.formattedDisplay = 'Error!';
            }
            this.saveState();
        },
        useHistory(val) {
            if (this.calculationDone) {
                this.display = '';
            }
            this.display += val.replace(/'/g, '');
            this.format();
            this.calculationDone = false;
            this.saveState();
        },
        copyLedger() {
            if (this.history.length === 0) return;
            let text = this.history.slice().reverse().map(item => `${item.eq} = ${item.res}`).join('\n');
            this.copyText(text, 'رسید محاسبات کپی شد.');
        },
        copyToClipboard() {
            if (this.display) this.copyText(this.display, 'با موفقیت کپی شد.');
        },
        clearDisplay() {
            if (this.display === '') {
                this.history = [];
            }
            this.display = '';
            this.formattedDisplay = '';
            this.calculationDone = false;
            this.saveState();
        },
        minimize() {
            this.minimized = true;
            this.open = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
            this.saveState();
        },
        restore() {
            this.minimized = false;
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            this.saveState();
        },
        closeModal() {
            this.clearDisplay();
            this.clearState();
            this.destroyed();
        },
        mounted() {
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        destroyed() {
            this.open = false;
            this.minimized = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
        }
    }
}
