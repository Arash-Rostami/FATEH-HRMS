export default function calculator() {
    return {
        display: '',
        formattedDisplay: '',
        modal: null,

        init() {
            this.modal = this.$refs.calculatorModal;
            if (this.modal) {
                window.addEventListener('calculate', () => this.mounted());
                window.addEventListener('keydown', (event) => {
                    if (event.key === "Escape" && this.modal.classList.contains('flex')) this.destroyed();
                });
            }
        },
        appendToDisplay(value) {
            this.display += value;
            this.formattedDisplay += value;
        },
        calculate() {
            try {
                const result = new Function('return ' + this.display)();
                this.display = result.toString();
                this.formattedDisplay = (this.display === 'Error!')
                    ? this.display
                    : result.toLocaleString('en', {
                        useGrouping: true,
                        maximumFractionDigits: 20
                    }).replace(/,/g, "'");
            } catch (error) {
                this.display = 'Error!';
            }
        },
        copyToClipboard() {
            if (this.display) {
                navigator.clipboard.writeText(this.display)
                    .then(() => this.$dispatch('toast', {message: 'با موفقیت کپی شد.', type: 'success'}))
                    .catch(err => console.error('Failed to copy text: ', err));
            }
        },
        clearDisplay() {
            this.display = '';
            this.formattedDisplay = '';
        },
        closeModal() {
            this.clearDisplay();
            this.destroyed();
        },
        mounted() {
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        },
        destroyed() {
            this.modal.classList.remove('flex');
            this.modal.classList.add('hidden');
        }
    }
}
