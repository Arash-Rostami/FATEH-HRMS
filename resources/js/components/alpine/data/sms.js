const MAX_LENGTH = 140;
const SINGLE_PART = 70;
const MULTI_PART = 67;
let commitHooked = false;

export default function smsModal(value, templates, scope, frame) {
    return {
        t: value,
        templates: templates || {},
        scope: scope || 'status',
        frame: frame || {},
        view: 'write',
        name: '',
        fresh: true,
        templateIndex: '',
        choices: [],

        init() {
            if (commitHooked || !window.Livewire) return;
            commitHooked = true;
            window.Livewire.hook('commit', ({ succeed }) => succeed(() => window.dispatchEvent(new CustomEvent('sms-committed'))));
        },

        open(detail) {
            if ((detail?.scope || 'status') !== this.scope) return;

            this.name = detail.name || '';
            this.$wire.smsUserId = detail.id;
            this.$wire.smsType = detail.type || '';
            this.$wire.smsKey = detail.key || '';
            this.choices = (this.templates[detail.type] || []).map((text) => text.replaceAll('{name}', this.name).slice(0, MAX_LENGTH));
            this.templateIndex = '';
            this.t = '';
            this.view = 'write';
            this.fresh = true;
            this.$wire.showSmsModal = true;
        },

        next() {
            if (!this.choices.length) return;

            const index = this.templateIndex === '' ? 0 : (Number(this.templateIndex) + 1) % this.choices.length;
            this.templateIndex = String(index);
            this.t = this.choices[index];
        },

        preview() {
            const body = (this.t || '').trim().slice(0, MAX_LENGTH);
            if (!body) return '';

            const head = this.frame.sender ? this.frame.sender + ': ' : '';

            return head + body + '\n' + (this.frame.footer || '');
        },

        segments() {
            const length = this.preview().length;

            return length === 0 ? 0 : length <= SINGLE_PART ? 1 : Math.ceil(length / MULTI_PART);
        },

        custom() {
            this.templateIndex = '';
            this.t = '';
        },

        smart() {
            return this.templateIndex !== '';
        },

        committed() {
            this.fresh = false;
        },
    };
}
