import maximizeMixin from "../mixins/maximize.js";
import settings from "./settings.js";

const RECENT_KEY = 'fateh_dms_recent';
const RECENT_MAX = 6;

export default function dms() {
    return {
        ...maximizeMixin(),
        baseUrl: window.location.origin + '/',
        openSettings: false,
        recent: [],

        init() {
            try {
                this.recent = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
            } catch (e) {
                this.recent = [];
            }
        },

        toggleSettings() {
            this.openSettings = !this.openSettings;
        },

        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        },

        recordClick(item) {
            if (!item || !item.id) return;
            this.recent = this.recent.filter((r) => r.id !== item.id);
            this.recent.unshift({id: item.id, title: item.title || '', url: item.url || ''});
            this.recent = this.recent.slice(0, RECENT_MAX);
            try {
                localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent));
            } catch (e) {
            }
        },

        clearRecent() {
            this.recent = [];
            try {
                localStorage.removeItem(RECENT_KEY);
            } catch (e) {
            }
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
