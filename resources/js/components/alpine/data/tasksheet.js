import persistentStateMixin from "../mixins/persistentState.js";

const RECIPIENT_KEY = 'tasksheet_last_recipient';

export default function tasksheet(readOnly = false) {
    return {
        ...persistentStateMixin(),
        expanded: { projects: null, standalone: null },
        printMode: false,

        init() {
            const remembered = this._loadState(RECIPIENT_KEY);
            if (!readOnly && remembered) {
                this.$wire.set('shareRecipientId', remembered);
            }
            window.addEventListener('afterprint', () => { this.printMode = false; });
        },

        rememberRecipient(id) {
            this._saveState(RECIPIENT_KEY, Number(id));
        },

        isExpanded(group, key) {
            if (readOnly || this.printMode) return true;
            const state = this.expanded[group];
            return state === key || state === 'all';
        },

        toggleExpanded(group, key) {
            const expanded = this.expanded;
            expanded[group] = expanded[group] === key ? null : key;
        },

        printExpandAll() {
            this.printMode = true;
            this.$nextTick(() => window.print());
        },
    };
}
