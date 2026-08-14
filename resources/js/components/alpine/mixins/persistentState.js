export default function persistentStateMixin() {
    return {
        _loadState(key, fallback = null) {
            try {
                const raw = localStorage.getItem(key);
                return raw === null ? fallback : JSON.parse(raw);
            } catch {
                return fallback;
            }
        },
        _saveState(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch {
            }
        },
        _clearState(key) {
            try {
                localStorage.removeItem(key);
            } catch {
            }
        },
    };
}
