import maximizeMixin from "../mixins/maximize.js";

const TYPING = /^(INPUT|TEXTAREA|SELECT)$/;
const INFO_LEAVE_MS = 380;
const CLOSE_MS = 300;
const SWIPE_CLOSE_PX = 80;

export default function thsModal() {
    return {
        ...maximizeMixin('ths-modal'),
        isClosing: false,
        info: false,
        infoTab: 'info',
        infoGen: 0,
        dragStart: null,
        dragY: 0,

        init() {
            document.getElementById('content-shell')?.setAttribute('inert', '');
            this.initMaximize();
            this.$nextTick(() => {
                const wide = window.matchMedia?.('(min-width:640px)').matches ?? true;
                this.$root.querySelector(wide ? '[data-ths-composer], [data-ths-close]' : '[data-ths-close]')?.focus();
            });
        },

        destroy() {
            this.releasePage();
        },

        restoreMaximize() {
            if (!window.matchMedia('(min-width:640px)').matches) return;
            this.max = true;
            this.applyMaximize(true);
        },

        releasePage() {
            document.getElementById('content-shell')?.removeAttribute('inert');
            if (this.max || this.maxLeaving) this.applyMaximize(false);
        },

        closeModal() {
            if (this.isClosing) return;
            this.isClosing = true;
            const id = this.$wire.selectedTicket?.id;
            setTimeout(() => {
                if (this.$wire.selectedTicket?.id !== id) {
                    this.isClosing = false;
                    return;
                }
                this.releasePage();
                this.$wire.set('selectedTicket', null);
            }, CLOSE_MS);
        },

        openInfo() {
            this.infoGen++;
            this.info = true;
        },

        closeInfo() {
            if (!this.info) return;
            const gen = ++this.infoGen;
            this.info = false;
            setTimeout(() => { if (gen === this.infoGen) this.infoTab = 'info'; }, INFO_LEAVE_MS);
        },

        confirming() {
            return !!document.querySelector('.custom-modal.active');
        },

        onEscape() {
            if (this.confirming()) return;
            const picker = this.$root.querySelector('[data-ths-picker]');
            const pickerData = picker && window.Alpine?.$data(picker);
            if (pickerData?.open) { pickerData.open = false; return; }
            if (this.info) { this.closeInfo(); return; }
            if (this.max) { this.toggleMaximize(); return; }
            this.closeModal();
        },

        onKey(e) {
            if (e.key !== 'm' && e.key !== 'M') return;
            if (TYPING.test(e.target.tagName) || e.target.isContentEditable || this.confirming()) return;
            this.toggleMaximize();
        },

        startDrag(e) {
            this.dragStart = e.clientY;
            this.dragY = 0;
            e.target.setPointerCapture?.(e.pointerId);
        },

        moveDrag(e) {
            if (this.dragStart !== null) this.dragY = Math.max(0, e.clientY - this.dragStart);
        },

        endDrag() {
            if (this.dragStart === null) return;
            const y = this.dragY;
            this.dragStart = null;
            this.dragY = 0;
            if (y >= SWIPE_CLOSE_PX) this.closeModal();
        }
    };
}
