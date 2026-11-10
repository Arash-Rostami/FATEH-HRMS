const CLASSIFICATION = ['targetDepartment', 'requestType', 'requestArea', 'priority'];
const TEXT = ['subject', 'description'];
const RESET = {aiSuggested: false, aiOriginal: null, aiApplied: null, aiQuestion: null, aiBlanks: 0, aiSource: null, aiLast: null};

const snapshot = ({subject, description}) => ({subject, description});

const exceeds = (text, limit) => {
    const value = typeof text === 'string' ? text : '';
    if (value.length <= limit) return false;
    let count = 0;
    for (const _ of value) if (++count > limit) return true;
    return false;
};

const countOf = (text, mark) => {
    let count = 0;
    let at = 0;
    while ((at = text.indexOf(mark, at)) !== -1) {
        count++;
        at += mark.length;
    }
    return count;
};

export default function aiWriterMixin() {
    return {
        aiLoading: false,
        aiSuggested: false,
        aiOriginal: null,
        aiApplied: null,
        aiQuestion: null,
        aiBlanks: 0,
        aiSource: null,
        aiRemaining: null,
        aiLast: null,

        initAiWriter() {
            CLASSIFICATION.forEach((key) => this.$watch(`$wire.ticket.${key}`, () => this.aiRevert()));
            TEXT.forEach((key) => this.$watch(`$wire.ticket.${key}`, (value) => {
                if (this.aiSuggested && this.aiApplied && value !== this.aiApplied[key]) this.aiClear();
            }));
        },

        aiBlocked() {
            return this.aiLoading || this.$wire.ticket.requestArea === '';
        },

        aiOverLimit({dataset}) {
            const {subject, description} = this.$wire.ticket;
            return exceeds(subject, Number(dataset.aiLimitSubject)) || exceeds(description, Number(dataset.aiLimitDescription));
        },

        async aiRun(need, el) {
            if (this.aiBlocked()) {
                if (!this.aiLoading && need) this.$dispatch('toast', {message: need, type: 'warning'});
                return;
            }

            if (el && this.aiOverLimit(el)) {
                this.$dispatch('toast', {message: el.dataset.aiLong, type: 'warning'});
                return;
            }

            const hadFocus = !!document.activeElement && document.activeElement !== document.body;
            const classification = CLASSIFICATION.map((key) => this.$wire.ticket[key]);
            const original = this.aiSuggested && this.aiOriginal ? this.aiOriginal : snapshot(this.$wire.ticket);

            this.aiLoading = true;
            try {
                const result = await this.$wire.suggestTicketText();
                if (!result || result.source === 'none') return;
                if (CLASSIFICATION.some((key, i) => this.$wire.ticket[key] !== classification[i])) {
                    this.$wire.ticket.subject = original.subject;
                    this.$wire.ticket.description = original.description;
                    return;
                }

                this.aiOriginal = original;
                this.aiApplied = snapshot(this.$wire.ticket);
                this.aiSuggested = true;
                this.aiQuestion = result.question;
                this.aiBlanks = result.blanks;
                this.aiSource = result.source;
                this.aiRemaining = result.left;
                this.$nextTick(() => {
                    this.aiLast = null;
                    if (result.source === 'skeleton' && this.aiJumpBlank()) return;
                    this.$refs.aiAccept?.focus();
                });
            } catch {
            } finally {
                this.aiLoading = false;
                this.$nextTick(() => {
                    if (hadFocus && (!document.activeElement || document.activeElement === document.body)) this.aiFocus();
                });
            }
        },

        aiUndo() {
            if (this.aiLoading) return;
            const {aiOriginal} = this;
            if (aiOriginal) {
                this.$wire.ticket.subject = aiOriginal.subject;
                this.$wire.ticket.description = aiOriginal.description;
            }
            this.aiReset();
        },

        aiClear() {
            if (this.aiLoading) return;
            this.aiSuggested = false;
            this.aiQuestion = null;
        },

        aiRevert() {
            const {ticket} = this.$wire;
            const {aiOriginal, aiApplied} = this;

            if (this.aiSuggested && aiOriginal && aiApplied && ticket.subject === aiApplied.subject && ticket.description === aiApplied.description) {
                ticket.subject = aiOriginal.subject;
                ticket.description = aiOriginal.description;
            }
            this.aiReset();
        },

        aiAccept() {
            if (this.aiLoading) return;
            this.aiClear();
            this.aiBlanks = 0;
            this.aiFocus();
        },

        aiReset() {
            Object.assign(this, RESET);
        },

        aiReady() {
            return this.aiBlanks > 0 && this.aiBlankLeft() === 0;
        },

        aiAgain() {
            const el = document.querySelector('[data-ai-need]');
            return el ? this.aiRun(el.dataset.aiNeed, el) : undefined;
        },

        aiGuardSubmit(event, blankText) {
            const blanks = this.aiBlankLeft() > 0;
            if (!this.aiLoading && !blanks) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            if (!this.aiLoading && blankText) this.$dispatch('toast', {message: blankText, type: 'warning'});
        },

        aiJumpBlank() {
            const active = document.activeElement;
            const inField = active?.setSelectionRange && active.closest('[data-ai-blank]');
            const from = inField ? active : (this.aiLast?.isConnected ? this.aiLast : null);
            if (from && this.aiNextBlank(from)) return true;

            const subject = document.getElementById('ticket.subject');
            return subject ? this.aiNextBlank(subject, 0) : false;
        },

        aiNextBlank(el, from = el.selectionEnd) {
            const host = el.closest('[data-ai-blank]');
            if (!host || !el.setSelectionRange) return false;

            const mark = host.dataset.aiBlank;
            const at = el.value.indexOf(mark, from);

            if (at !== -1) {
                el.focus();
                el.setSelectionRange(at, at + mark.length);
                this.aiLast = el;
                return true;
            }

            const next = document.getElementById(host.dataset.aiNext ?? '');
            return next ? this.aiNextBlank(next, 0) : false;
        },

        aiBlankLeft() {
            const mark = document.querySelector('[data-ai-blank]')?.dataset.aiBlank;
            if (!mark) return 0;

            const {subject, description} = this.$wire.ticket;
            return countOf(`${subject} ${description}`, mark);
        },

        aiFocus() {
            const el = document.getElementById('ticket.description');
            el?.focus();
            return el;
        },

        aiAsk() {
            const el = this.aiFocus();
            el?.setSelectionRange(el.value.length, el.value.length);
        }
    };
}
