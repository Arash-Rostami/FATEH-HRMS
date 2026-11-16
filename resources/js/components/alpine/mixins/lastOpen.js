function lastOpenStorageKey(scope) {
    if (!scope) return null;
    const uid = document.documentElement.dataset.uid;
    if (!uid) return null;
    return `last:${scope}:${uid}${window.self !== window.top ? ':f' : ''}`;
}

export default function lastOpenMixin(scope) {
    return {
        _lastOpenStorageKey: lastOpenStorageKey(scope),

        _persistLastOpen(id) {
            const key = this._lastOpenStorageKey;
            if (key === null || !id) return;

            try {
                localStorage.setItem(key, id);
            } catch (e) {}
        },

        _clearLastOpen() {
            const key = this._lastOpenStorageKey;
            if (key === null) return;

            try {
                localStorage.removeItem(key);
            } catch (e) {}
        },

        _readLastOpenId() {
            const key = this._lastOpenStorageKey;
            if (key === null) return null;

            try {
                const raw = localStorage.getItem(key);
                if (raw === null) return null;

                const parsed = parseInt(raw, 10);
                return parsed ? parsed : null;
            } catch (e) {
                return null;
            }
        },
    };
}
