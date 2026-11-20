const PAGE_SIZE = 30;
const YEH_REGEX = /ي/g;
const KAF_REGEX = /ك/g;
const ZWNJ_REGEX = /‌/g;

export const normalize = (value) => (value ?? '').toString().trim().toLowerCase()
    .replace(YEH_REGEX, 'ی')
    .replace(KAF_REGEX, 'ک')
    .replace(ZWNJ_REGEX, ' ');

export default function memberPicker({ model, rows = [], server = false, mode = 'multi', page = PAGE_SIZE, disabled = false, hasNone = false, deptModel = '', searchModel = '' } = {}) {
    const meta = rows.map((row) => ({
        id: String(row.id),
        dept: String(row.dept ?? ''),
        key: normalize(row.name),
    }));
    const known = Object.fromEntries(meta.map((row) => [row.id, true]));

    return {
        memberQuery: '',
        dept: '',
        onlySelected: false,
        shown: page,
        busy: false,
        disabled,
        server,
        mode,
        meta,
        visible: {},
        firstId: null,
        matched: 0,
        selected: {},
        loaded: [],
        more: false,

        init() {
            this.syncSelected();
            this.refresh();

            const reset = () => {
                this.shown = page;
                this.refresh();
            };

            this.$watch('memberQuery', reset);
            this.$watch('dept', reset);
            this.$watch('onlySelected', reset);
            this.$watch(() => this.selectedIds.join(','), () => {
                this.syncSelected();
                if (this.onlySelected) this.refresh();
            });
        },

        sync(ids, more, disabled = this.disabled) {
            this.loaded = ids.map(String);
            this.more = more;
            this.disabled = disabled;
        },

        get selectedIds() {
            const raw = this.$wire.get(model);

            if (this.mode === 'single') {
                return raw !== null && raw !== undefined && raw !== '' ? [String(raw)] : [];
            }

            return Array.isArray(raw) ? raw.map(String) : [];
        },

        syncSelected() {
            const selected = {};

            for (const id of this.selectedIds) selected[id] = true;

            this.selected = selected;
        },

        get selectedCount() {
            return this.server ? this.selectedIds.length : this.selectedIds.filter((id) => known[id]).length;
        },

        get currentDept() {
            return this.server ? String(this.$wire.get(deptModel) || '') : this.dept;
        },

        get currentQuery() {
            return this.server ? String(this.$wire.get(searchModel) || '').trim() : this.memberQuery.trim();
        },

        get bulkBlocked() {
            return this.disabled || this.currentQuery !== '';
        },

        isSelected(id) {
            return !!this.selected[id];
        },

        refresh() {
            if (this.server) return;

            const query = normalize(this.memberQuery);
            const dept = this.dept;
            const visible = {};
            let matched = 0;
            let firstId = null;

            for (const row of this.meta) {
                if (dept !== '' && row.dept !== dept) continue;
                if (this.onlySelected && !this.selected[row.id]) continue;
                if (query !== '' && !row.key.includes(query)) continue;

                if (matched === 0 && !hasNone) firstId = row.id;
                if (matched < this.shown) visible[row.id] = true;
                matched++;
            }

            this.visible = visible;
            this.matched = matched;
            this.firstId = firstId;
        },

        isVisible(id) {
            return this.server || !!this.visible[id];
        },

        isFirst(id) {
            return this.firstId === String(id);
        },

        showMore() {
            this.shown += page;
            this.refresh();
        },

        get allSelected() {
            const dept = this.currentDept;
            let total = 0;

            if (this.server) {
                for (const id of this.loaded) {
                    if (!this.selected[id]) return false;
                    total++;
                }

                return total > 0 && (!this.more || this.selectedIds.length > total);
            }

            for (const row of this.meta) {
                if (dept !== '' && row.dept !== dept) continue;
                if (!this.selected[row.id]) return false;
                total++;
            }

            return total > 0;
        },

        toggleBulk() {
            if (this.busy || this.bulkBlocked) return;

            const dept = this.currentDept;
            const clear = this.allSelected;
            const kind = clear ? 'none' : (dept === '' ? 'all' : 'department');

            if (clear) this.onlySelected = false;

            this.busy = true;

            Promise.resolve(this.$wire.bulkSelectPeople(...(dept === '' ? [model, kind] : [model, kind, dept])))
                .finally(() => { this.busy = false; });
        },
    };
}
