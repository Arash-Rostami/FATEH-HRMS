export default function spotlightMixin({ storageKey = 'spotlight-column' } = {}) {
    return {
        spotlightColumn: null,
        _spotlightStorageKey: storageKey,

        initSpotlight() {
            try {
                this.spotlightColumn = localStorage.getItem(this._spotlightStorageKey) || null;
            } catch {}
        },

        isSpotlight(name) {
            return this.spotlightColumn === name;
        },

        toggleSpotlight(name) {
            this._setSpotlight(this.spotlightColumn === name ? null : name);
        },

        clearSpotlight() {
            this._setSpotlight(null);
        },

        _setSpotlight(value) {
            if (this.spotlightColumn === value) return;
            this.spotlightColumn = value;
            try {
                value
                    ? localStorage.setItem(this._spotlightStorageKey, value)
                    : localStorage.removeItem(this._spotlightStorageKey);
            } catch {}
            this.afterSpotlightChange?.();
        },
    };
}
