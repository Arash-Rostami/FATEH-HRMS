const COOKIE_REGEX = /(^|;)\s*googtrans\s*=\s*([^;]+)/;
const LS_KEY = 'google_translate_enabled';
const SCRIPT_URL = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
const SCRIPT_ID = 'google-translate-script';
const STYLE_ID = 'google-translate-hide-ui';
const EXPIRY_DATE = 'Thu, 01 Jan 1970 00:00:00 UTC';

const HIDE_UI_CSS = `
    .goog-te-banner-frame { display: none !important; }
    .goog-te-gadget-icon { display: none !important; }
    .goog-te-gadget-simple { display: none !important; }
    #google_translate_element { display: none !important; }
    body { top: 0px !important; }
`;

export default (initialState = false) => ({
    enabled: initialState,

    init() {
        const cookie = document.cookie.match(COOKIE_REGEX);
        this.enabled = !!cookie;

        if (this.enabled) {
            this.loadGoogleScript();
        }
    },

    toggle() {
        this.enabled = !this.enabled;
        this.persist(this.enabled);
        window.location.reload();
    },

    persist(on) {
        const value = on ? '/fa/en' : '';
        const expiry = on ? '' : `; expires=${EXPIRY_DATE}`;

        const domain = location.hostname;
        const baseCookie = `googtrans=${value}${expiry}; path=/`;

        document.cookie = `${baseCookie}; domain=${domain}`;
        document.cookie = `${baseCookie}; domain=.${domain}`;

        localStorage.setItem(LS_KEY, on ? 'true' : 'false');
    },

    loadGoogleScript() {
        if (window.google && window.google.translate) return;

        if (!window.googleTranslateElementInit) {
            window.googleTranslateElementInit = () => {
                new google.translate.TranslateElement({
                    pageLanguage: 'fa',
                    layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                    autoDisplay: false
                }, 'google_translate_element');

                this.cleanUI();
            };
        }

        if (document.getElementById(SCRIPT_ID)) return;

        const script = document.createElement('script');
        script.id = SCRIPT_ID;
        script.type = 'text/javascript';
        script.src = SCRIPT_URL;
        script.onerror = () => {
            script.remove();
            this.enabled = false;
            this.persist(false);
            this.$dispatch('toast', {message: 'ترجمه بدون اینترنت در دسترس نیست.', type: 'warning'});
            window.dispatchEvent(new CustomEvent('internet-unreachable'));
        };
        document.body.appendChild(script);
    },

    cleanUI() {
        if (document.getElementById(STYLE_ID)) return;

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.innerHTML = HIDE_UI_CSS;
        document.head.appendChild(style);
    }
});
