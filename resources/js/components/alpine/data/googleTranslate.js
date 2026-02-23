export default (initialState = false) => ({
    enabled: initialState,

    init() {
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
            window.location.reload();
        }
    },

    loadGoogleScript() {
        // If script is already loaded and widget initialized, just ensure UI is clean
        if (window.google && window.google.translate && window.google.translate.TranslateElement) {
             // Re-run init if needed or just let it be
             return;
        }

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
        const style = document.createElement('style');
        style.innerHTML = `
            .goog-te-banner-frame { display: none !important; }
            .goog-te-gadget-icon { display: none !important; }
            .goog-te-gadget-simple { background-color: transparent !important; border: none !important; padding: 0 !important; font-size: 14px !important; }
            body { top: 0px !important; }
        `;
        document.head.appendChild(style);
    }
})
