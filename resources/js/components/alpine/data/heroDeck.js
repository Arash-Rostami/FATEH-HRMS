import maximizeMixin from "../mixins/maximize.js";

const STORAGE_KEY = 'hero_gadgets';
const MAX_PANES = 6;
const NEW_PANE = '__new__';

export default function heroDeck() {
    return {
        ...maximizeMixin(),

        slide: 0,
        panes: [],
        modules: {},
        loaded: {},
        frameReady: {},
        maximizedPane: null,
        pickerFor: null,

        init() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                if (raw !== null) {
                    const saved = JSON.parse(raw);
                    const savedPanes = saved.panes;

                    this.panes = Array.isArray(savedPanes) ? savedPanes : [];
                    this.modules = saved.modules || {};
                }
            } catch (e) {
                try { localStorage.removeItem(STORAGE_KEY); } catch (e2) {}
            }

            this.$watch('slide', (id) => {
                if (id !== 0 && this.modules[id] !== undefined) {
                    this.loaded[id] = true;
                }
            });
        },

        goto(target) {
            this.slide = target;
        },

        canAddPane() {
            return this.panes.length < MAX_PANES;
        },

        openAddPicker() {
            if (this.canAddPane()) this.pickerFor = NEW_PANE;
        },

        pick(mod) {
            const picker = this.pickerFor;
            if (picker === null) return;

            const isNew = picker === NEW_PANE;
            const id = isNew ? 'p_' + Date.now() : picker;

            if (isNew) {
                this.panes.push(id);
            }

            this.modules[id] = { title: mod.title, icon: mod.icon, src: mod.src };
            this.loaded[id] = true;
            this.frameReady[id] = false;

            if (isNew) {
                this.slide = id;
            }

            this.pickerFor = null;
            this.persist();
        },

        remove(pane) {
            const arr = this.panes;
            const idx = arr.indexOf(pane);

            if (idx !== -1) {
                arr.splice(idx, 1);
            }

            Reflect.deleteProperty(this.modules, pane);
            Reflect.deleteProperty(this.loaded, pane);
            Reflect.deleteProperty(this.frameReady, pane);

            if (this.maximizedPane === pane) {
                this.maximizedPane = null;
                this.applyMaximize(false);
            }

            if (this.slide === pane) this.slide = 0;

            this.persist();
        },

        onFrameLoad(pane) {
            this.frameReady[pane] = true;
        },

        toggleMaximize(pane) {
            const isMax = this.maximizedPane === pane;
            this.maximizedPane = isMax ? null : pane;
            this.applyMaximize(!isMax);
        },

        persist() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify({
                    panes: this.panes,
                    modules: this.modules
                }));
            } catch (e) {}
        }
    };
}
