import persistentStateMixin from "./persistentState.js";

export default function recentListMixin({key, max = 6, idOf = (item) => item.id, accept = Array.isArray}) {
    return {
        ...persistentStateMixin(),
        recent: [],

        loadRecent() {
            const parsed = this._loadState(key);
            this.recent = accept(parsed) ? parsed : [];
        },

        pushRecent(entry) {
            const id = idOf(entry);
            this.recent = [entry, ...this.recent.filter((r) => idOf(r) !== id)].slice(0, max);
            this._saveState(key, this.recent);
        },

        clearRecent() {
            this.recent = [];
            this._clearState(key);
        },
    };
}
