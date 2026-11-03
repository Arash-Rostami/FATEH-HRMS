import clipboardMixin from "../mixins/clipboard.js";

export default function share() {
    return {
        ...clipboardMixin(),
        panelOpen: false,
        sharePopoverOpen: false,
        shareTitle: '',
        shareText: '',
        view: 'card',

        init() {
            this.view = this.$wire.get('view') || 'card';

            this.$watch('panelOpen', value => {
                document.body.style.overflow = value ? 'hidden' : '';
            });

            this._open = () => { this.panelOpen = true; };
            window.addEventListener('open-post-panel', this._open);
        },

        destroy() {
            window.removeEventListener('open-post-panel', this._open);
        },

        togglePanel() {
            this.panelOpen = !this.panelOpen;
        },

        openShare(title, body) {
            this.shareTitle = title;
            this.shareText = body;
            this.sharePopoverOpen = true;
        },

        closeShare() {
            this.sharePopoverOpen = false;
        },

        copyToClipboard() {
            this.copyText(this.shareTitle + '\n\n' + this.shareText);
            this.sharePopoverOpen = false;
        },

        sendEmail() {
            window.location.href = `mailto:?subject=${encodeURIComponent(this.shareTitle)}&body=${encodeURIComponent(this.shareText)}`;
            this.sharePopoverOpen = false;
        }
    }
}
