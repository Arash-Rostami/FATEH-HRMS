export default (initialState = false) => ({
    enabled: initialState,

    init() {
        // Check cookie for translation state (googtrans cookie handles the actual translation)
        const cookie = document.cookie.match(/(^|;)\s*googtrans\s*=\s*([^;]+)/);
        this.enabled = !!cookie;

        if (this.enabled) {
            this.loadGoogleScript();
        }
    },

    toggle() {
        this.enabled = !this.enabled;

        if (this.enabled) {
            // Set cookie to translate from Farsi (fa) to English (en) automatically
            document.cookie = "googtrans=/fa/en; path=/; domain=" + location.hostname;
            document.cookie = "googtrans=/fa/en; path=/; domain=." + location.hostname; // Handling subdomains just in case
            localStorage.setItem('google_translate_enabled', 'true');
            window.location.reload();
        } else {
            // Clear cookies to disable translation
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + location.hostname;
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + location.hostname;
            localStorage.setItem('google_translate_enabled', 'false');
            window.location.reload();
        }
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

        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.body.appendChild(script);
    },

    cleanUI() {
        // Hide the Google widget and branding completely since we are using auto-translate via cookies
        const style = document.createElement('style');
        style.innerHTML = `
            .goog-te-banner-frame { display: none !important; }
            .goog-te-gadget-icon { display: none !important; }
            .goog-te-gadget-simple { display: none !important; }
            #google_translate_element { display: none !important; }
            body { top: 0px !important; }
        `;
        document.head.appendChild(style);
    }
})
