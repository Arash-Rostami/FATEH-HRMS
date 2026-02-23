export default class GoogleTranslate {
    constructor() {
        this.enabled = localStorage.getItem('google_translate_enabled') === 'true';
        this.elementId = 'google_translate_element';
        this.scriptSrc = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    }

    init() {
        if (this.enabled) {
            this.loadScript();
        }

        // Expose global callback
        window.googleTranslateElementInit = () => {
            new google.translate.TranslateElement({
                pageLanguage: 'fa', // Default source language
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, this.elementId);

            // Force clean style
            this.cleanUI();
        };
    }

    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('google_translate_enabled', this.enabled);

        if (this.enabled) {
            this.loadScript();
        } else {
            // Reload to clear translation (Google Translate is sticky)
            location.reload();
        }
    }

    loadScript() {
        if (document.querySelector(`script[src="${this.scriptSrc}"]`)) return;

        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = this.scriptSrc;
        document.body.appendChild(script);
    }

    cleanUI() {
        // Remove Google banner/branding after init
        const style = document.createElement('style');
        style.innerHTML = `
            .goog-te-banner-frame { display: none !important; }
            .goog-te-gadget-icon { display: none !important; }
            body { top: 0px !important; }
            #google_translate_element { display: none; } /* Hide the widget itself if using custom toggle */
        `;
        document.head.appendChild(style);
    }
}
