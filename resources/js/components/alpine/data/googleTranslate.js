export default (initialState = false) => ({
    enabled: initialState,

    init() {
        // Check localStorage for persisted state
        const storedState = localStorage.getItem('google_translate_enabled');
        if (storedState !== null) {
            this.enabled = storedState === 'true';
        }

        if (this.enabled) {
            this.loadGoogleScript();
        }
    },

    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('google_translate_enabled', this.enabled);

        if (this.enabled) {
            this.loadGoogleScript();
        } else {
            // Google Translate is persistent once loaded, so we must reload to clear it
            window.location.reload();
        }
    },

    loadGoogleScript() {
        if (window.google && window.google.translate) return;

        // Create the global callback if it doesn't exist
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

        // Inject script
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.body.appendChild(script);
    },

    cleanUI() {
        // Optional: Add styles to hide Google banner
        const style = document.createElement('style');
        style.innerHTML = `
            .goog-te-banner-frame { display: none !important; }
            .goog-te-gadget-icon { display: none !important; }
            body { top: 0px !important; }
        `;
        document.head.appendChild(style);
    }
})
