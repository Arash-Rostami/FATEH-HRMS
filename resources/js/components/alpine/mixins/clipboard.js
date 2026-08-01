export default function clipboardMixin() {
    return {
        copyText(text, message = 'با موفقیت کپی شد.', type = 'success') {
            if (!text) return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => this._copyToast(message, type))
                    .catch(() => this.fallbackCopyText(text, message, type));
            } else {
                this.fallbackCopyText(text, message, type);
            }
        },
        fallbackCopyText(text, message = 'با موفقیت کپی شد.', type = 'success') {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.top = '0';
            ta.style.left = '0';
            ta.style.opacity = '0';
            ta.setAttribute('readonly', '');
            document.body.appendChild(ta);
            ta.select();

            try {
                document.execCommand('copy');
                this._copyToast(message, type);
            } catch (err) {
                this._copyToast('کپی ناموفق بود.', 'error');
            } finally {
                document.body.removeChild(ta);
            }
        },
        _copyToast(message, type) {
            this.$dispatch('toast', {message, type});
        }
    };
}