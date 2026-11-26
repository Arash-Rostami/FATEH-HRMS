export default function profile() {
    return {
        step: 1, maxSteps: 3,
        nextStep() {
            if (this.step < this.maxSteps) {
                this.step++;
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        },
        prevStep() {
            if (this.step > 1) {
                this.step--;
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        },
        setStep(s) {
            if (s >= 1 && s <= this.maxSteps) {
                this.step = s;
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        },
        jumpToError({step, field}) {
            this.step = step;
            this.$nextTick(() => {
                const el = field ? document.getElementById(field) : null;
                if (!el) return window.scrollTo({top: 0, behavior: 'smooth'});
                el.scrollIntoView({behavior: 'smooth', block: 'center'});
                el.focus({preventScroll: true});
            });
        },
        setDirection(e) {
            const element = e.target || e;
            element.style.direction = /[\u0591-\u07FF\uFB00-\uFDFF\uFE70-\uFEFF]/.test(element.value) ? 'rtl' : 'ltr';
        }
    }
}
