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

            window.addEventListener('open-post-panel', () => {
                this.panelOpen = true;
            });
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
