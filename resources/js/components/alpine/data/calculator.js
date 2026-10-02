import clipboardMixin from "../mixins/clipboard.js";
import persistentStateMixin from "../mixins/persistentState.js";

const STORAGE_KEY = 'calculator_state';
const OPERATORS = ['/', '*', '+', '-'];
const MAX_HISTORY = 50;
const FA_DIGITS = {'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9'};

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
                this._windowCalculateListener = () => this.mounted();
                this._windowKeydownListener = (event) => {
                    if (event.key === "Escape" && this.modal.classList.contains('flex')) {
                        this.minimize();
                        return;
                    }

                    if (!this.open) return;
                    if (event.target !== this.$refs.display) {
                        const tag = event.target?.tagName;
                        if (tag === 'INPUT' || tag === 'TEXTAREA' || event.target?.isContentEditable) return;
                    }

                    const faDigit = FA_DIGITS[event.key];
                    if (/^[0-9.]$/.test(event.key) || faDigit) {
                        event.preventDefault();
                        this.appendToDisplay(faDigit ?? event.key);
                    } else if (OPERATORS.includes(event.key)) {
                        event.preventDefault();
                        this.appendToDisplay(event.key);
                    } else if (event.key === 'Enter' || event.key === '=') {
                        event.preventDefault();
                        this.calculate();
                    } else if (event.key === 'Backspace') {
                        event.preventDefault();
                        this.backspace();
                    }
                };
                window.addEventListener('calculate', this._windowCalculateListener);
                window.addEventListener('keydown', this._windowKeydownListener);
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

        appendToDisplay(value) {
            const isOperator = OPERATORS.includes(value);

            if (this.calculationDone) {
                if (!isOperator) {
                    this.display = '';
                }
                this.calculationDone = false;
            }

            if (isOperator && OPERATORS.includes(this.display.slice(-1))) {
                this.display = this.display.slice(0, -1);
            }

            this.display += value;
            this.format();
            this.saveState();
        },
        format() {
            this.formattedDisplay = this.display.replace(/(^|[^\d.])(\d+)/g, (match, prefix, num) => prefix + num.replace(/\B(?=(\d{3})+(?!\d))/g, "'"));
        },
        calculate() {
            if (!this.display || this.display === 'Error!') return;
            try {
                let sanitized = this.display.replace(/×/g, '*').replace(/÷/g, '/').replace(/−/g, '-');
                if (/^[\d.\s]+$/.test(sanitized)) return;
                const result = new Function('return ' + sanitized)();
                if (Number.isFinite(result)) {
                    const equation = this.display;
                    this.display = result.toString();
                    this.format();
                    this.history.unshift({
                        eq: equation,
                        res: this.formattedDisplay
                    });
                    if (this.history.length > MAX_HISTORY) this.history.length = MAX_HISTORY;
                    this.calculationDone = true;
                } else {
                    this.display = 'Error!';
                    this.formattedDisplay = 'Error!';
                }
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
            this.display = '';
            this.formattedDisplay = '';
            this.calculationDone = false;
            this.saveState();
        },
        backspace() {
            if (this.calculationDone) {
                this.clearDisplay();
                return;
            }
            this.display = this.display.slice(0, -1);
            this.format();
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
            this._hideModal();
            this.saveState();
        },
        mounted() {
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        _hideModal() {
            this.open = false;
            this.minimized = false;
            if (this.modal) {
                this.modal.classList.remove('flex');
                this.modal.classList.add('hidden');
            }
        },
        destroy() {
            if (this._windowCalculateListener) window.removeEventListener('calculate', this._windowCalculateListener);
            if (this._windowKeydownListener) window.removeEventListener('keydown', this._windowKeydownListener);
            this._hideModal();
        }
    }
}
