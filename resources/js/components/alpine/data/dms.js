import settings from "./settings.js";

export default function dms() {
    return {
        baseUrl: window.location.origin + '/',

        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        },

        confirmAndSend(docId, file) {
            if (!file) {
                console.warn('DMS: No file provided for confirmation.');
                return;
            }

            const isPdf = file.toLowerCase().endsWith('.pdf');
            const documentUrl = `${this.baseUrl}authorized/${file}`;

            if (isPdf) {
                this.$dispatch('open-pdf-viewer', {
                    url: documentUrl,
                    docId: docId,
                    isPdf: true,
                    method: 'confirmRead',
                    type: 'livewire'
                });
            } else {
                this.$dispatch('open-confirmation', {
                    title: 'تأیید مطالعه سند',
                    message: 'با کلیک بر روی این دکمه، شما تأیید می‌کنید که این سند غیر PDF را دریافت کرده، خوانده و از محتوای آن مطلع شدید. آیا ادامه می‌دهید؟',
                    method: 'confirmRead',
                    params: docId,
                    type: 'livewire'
                });
            }
        },
    }
};
