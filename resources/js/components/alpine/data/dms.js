import maximizeMixin from "../mixins/maximize.js";
import tableToolsMixin from "../mixins/tableTools.js";
import recentListMixin from "../mixins/recentList.js";
import settings from "./settings.js";

export default function dms() {
    return {
        ...maximizeMixin('dms'),
        ...tableToolsMixin('dms'),
        ...recentListMixin({key: 'fateh_dms_recent', max: 6}),
        baseUrl: window.location.origin + '/',

        init() {
            this.initMaximize();
            this.initTableTools();
            this.loadRecent();
        },

        destroy() {
            this.destroyTableTools();
        },

        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        },

        recordClick(item) {
            if (!item || !item.id) return;
            this.pushRecent({id: item.id, title: item.title || '', url: item.url || ''});
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
                    method: 'incrementRead',
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
