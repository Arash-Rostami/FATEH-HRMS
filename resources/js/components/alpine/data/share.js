export default function share() {
    return {
        panelOpen: false,
        sharePopoverOpen: false,
        shareTitle: '',
        shareText: '',

        init() {
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
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.shareTitle + '\n\n' + this.shareText)
                    .then(() => {
                        this.sharePopoverOpen = false;
                    })
                    .catch(err => {
                        console.error('Failed to copy text: ', err);
                    });
            }
        },

        sendEmail() {
            window.location.href = `mailto:?subject=${encodeURIComponent(this.shareTitle)}&body=${encodeURIComponent(this.shareText)}`;
            this.sharePopoverOpen = false;
        }
    }
}
