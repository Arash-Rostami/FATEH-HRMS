const SNAPSHOT_KEY = 'focus-snapshot';
const EVENT_EXPIRED = 'focus-mode-expired';

export default function focusModeMixin() {
    return {
        _focusExpiredHandler: null,

        activateFocus(minutes = null, currentPresence = 'onsite') {
            const store = this.$store;
            const bgStore = store.background;

            const snapshot = {
                presence: currentPresence,
                backgroundEnabled: bgStore ? (bgStore.enabled || false) : false,
                patternEnabled: bgStore ? (bgStore.patternEnabled || false) : false,
                endsAt: minutes !== null ? Date.now() + (minutes * 60000) : null,
            };

            try {
                localStorage.setItem(SNAPSHOT_KEY, JSON.stringify(snapshot));
            } catch (e) {}

            const focusStore = store.focus;
            focusStore.active = true;
            focusStore.until = snapshot.endsAt;

            if (bgStore !== undefined) {
                bgStore.enabled = false;
                bgStore.patternEnabled = false;
            }

            if (store.sound !== undefined) {
                store.sound.globalHush = true;
            }

            const docEl = document.documentElement;
            if (docEl.requestFullscreen !== undefined) {
                docEl.requestFullscreen().catch(() => {});
            }

            if (this.$wire !== undefined) {
                this.$wire.call('activateFocusMode', minutes);
            }

            focusStore.schedule(snapshot.endsAt);
        },

        deactivateFocus() {
            const store = this.$store;
            const focusStore = store.focus;

            if (!focusStore.active) return;

            let snapshot = null;
            try {
                const raw = localStorage.getItem(SNAPSHOT_KEY);
                if (raw !== null) {
                    snapshot = JSON.parse(raw);
                }
            } catch (e) {}

            focusStore.active = false;
            focusStore.until = null;

            if (store.sound !== undefined) {
                store.sound.globalHush = false;
            }

            if (snapshot !== null && snapshot.backgroundEnabled && store.background !== undefined) {
                store.background.enabled = true;
            }

            if (document.exitFullscreen !== undefined) {
                document.exitFullscreen().catch(() => {});
            }

            try {
                localStorage.removeItem(SNAPSHOT_KEY);
            } catch (e) {}

            if (this.$wire !== undefined) {
                this.$wire.call('deactivateFocusMode', snapshot !== null ? snapshot.presence : null);
            }

            focusStore.clear();
        },

        initFocusFromSnapshot() {
            this._focusExpiredHandler = () => this.deactivateFocus();
            window.addEventListener(EVENT_EXPIRED, this._focusExpiredHandler);

            let snapshot = null;
            try {
                const raw = localStorage.getItem(SNAPSHOT_KEY);
                if (raw !== null) {
                    snapshot = JSON.parse(raw);
                }
            } catch (e) {}

            const focusStore = this.$store.focus;

            if (snapshot === null || focusStore.active) return;

            if (snapshot.endsAt !== null && Date.now() >= snapshot.endsAt) {
                focusStore.active = true;
                this.deactivateFocus();
                return;
            }

            focusStore.active = true;
            focusStore.until = snapshot.endsAt;
            focusStore.schedule(snapshot.endsAt);
        },

        destroyFocusMode() {
            if (this._focusExpiredHandler !== null) {
                window.removeEventListener(EVENT_EXPIRED, this._focusExpiredHandler);
                this._focusExpiredHandler = null;
            }
        }
    };
}
