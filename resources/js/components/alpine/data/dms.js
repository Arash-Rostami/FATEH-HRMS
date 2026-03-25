export default () => ({
    baseUrl: window.location.origin + '/',

    init() {
        // Initialize any setup if needed, similar to the old initStyles
        // Currently, modern tailwind and MD3 styles are handled natively in the views.
    },

    /**
     * Confirms reading a document and handles modal opening based on file type
     * @param {number|string} docId - The ID of the document
     * @param {string} file - The file path/name
     */
    confirmAndSend(docId, file) {
        if (!file) {
            console.warn('DMS: No file provided for confirmation.');
            return;
        }

        const isPdf = typeof file === 'string' && file.toLowerCase().includes('.pdf');
        // Route is generated in blade but passed to JS via the template.
        // Assuming your route is 'authorized/{file}' as per old logic or route('secure-file')
        // Using a relative path for flexibility, adjust if your route prefix differs.
        const documentUrl = this.baseUrl + 'authorized/' + file;

        if (isPdf) {
            // Open the rich PDF viewer modal for PDFs
            this.$dispatch('open-pdf-viewer', {
                url: documentUrl,
                docId: docId,
                isPdf: true,
                method: 'confirmRead',
                type: 'livewire'
            });
        } else {
            // Open the standard confirmation modal for non-PDFs
            this.$dispatch('open-confirmation', {
                title: 'تأیید مطالعه سند',
                message: 'با کلیک بر روی این دکمه، شما تأیید می‌کنید که این سند غیر PDF را دریافت کرده، خوانده و از محتوای آن مطلع شدید. آیا ادامه می‌دهید؟',
                method: 'confirmRead',
                params: docId,
                type: 'livewire'
            });
        }
    }
});
