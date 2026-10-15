import headerDeclutterMixin from './headerDeclutter.js';
import persistentStateMixin from './persistentState.js';

const KEY_PREFIX = 'table-tools:';

export default function tableToolsMixin(scope) {
    const key = KEY_PREFIX + scope;
    return {
        ...persistentStateMixin(),
        ...headerDeclutterMixin(),
        _tableToolsAc: null,
        initTableTools() {
            this.headerDeclutter = !!this._loadState(key, false);
            this._tableToolsAc = new AbortController();
            this.headerDeclutterMenuBridge(this._tableToolsAc.signal);
        },
        destroyTableTools() {
            clearTimeout(this._declutterTimer);
            this._tableToolsAc?.abort();
        },
        _persistChatSettings() {
            this._saveState(key, this.headerDeclutter && !this._menuForced);
        },
    };
}
