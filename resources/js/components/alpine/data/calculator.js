export default function calculator() {
    return {
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
                this.calculationDone = true;
            } catch (error) {
                this.display = 'Error!';
                this.formattedDisplay = 'Error!';
            }
        },
        useHistory(val) {
            if (this.calculationDone) {
                this.display = '';
            }
            this.display += val.replace(/'/g, '');
            this.format();
            this.calculationDone = false;
        },
        copyLedger() {
            if (this.history.length === 0) return;
            let text = this.history.slice().reverse().map(item => `${item.eq} = ${item.res}`).join('\n');
            navigator.clipboard.writeText(text)
                .then(() => this.$dispatch('toast', {message: 'رسید محاسبات کپی شد.', type: 'success'}))
                .catch(err => console.error('Failed to copy text: ', err));
        },
        copyToClipboard() {
            if (this.display) {
                navigator.clipboard.writeText(this.display)
                    .then(() => this.$dispatch('toast', {message: 'با موفقیت کپی شد.', type: 'success'}))
                    .catch(err => console.error('Failed to copy text: ', err));
            }
        },
        clearDisplay() {
            if (this.display === '') {
                this.history = [];
            }
            this.display = '';
            this.formattedDisplay = '';
            this.calculationDone = false;
        },
        minimize() {
            this.minimized = true;
            this.open = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
        },
        restore() {
            this.minimized = false;
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        closeModal() {
            this.clearDisplay();
            this.destroyed();
        },
        mounted() {
            this.open = true;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        destroyed() {
            this.open = false;
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
        }
    }
}
