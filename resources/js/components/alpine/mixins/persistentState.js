export function smsMemoryMixin({senderId = null, smsYear = null} = {}) {
    const storageKey = `calendar-sms-sent:${senderId}`;
    const validKey = key => typeof key === 'string'
        && /^(birthday|anniversary)-[1-9]\d*-\d{4}$/.test(key)
        && Number(key.slice(-4)) >= Number(smsYear);

    return {
        smsSent: [],

        loadSmsMemory() {
            if (!senderId || !smsYear) return;
            try {
                const stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
                this.smsSent = Array.isArray(stored) ? [...new Set(stored.filter(validKey))] : [];
            } catch {
                this.smsSent = [];
            }
            try {
                localStorage.setItem(storageKey, JSON.stringify(this.smsSent));
            } catch {}
        },

        rememberSms(detail) {
            if (detail?.source !== 'calendar' || Number(detail.senderId) !== Number(senderId) || !validKey(detail.key)) return;
            this.smsSent = [...new Set([...this.smsSent.filter(validKey), detail.key])];
            try {
                localStorage.setItem(storageKey, JSON.stringify(this.smsSent));
            } catch {}
        },

        smsAlreadySent(key) {
            return this.smsSent.includes(key);
        },
    };
}

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
