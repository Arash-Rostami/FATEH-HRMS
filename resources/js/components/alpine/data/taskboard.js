import maximizeMixin from "../mixins/maximize.js";
import kanbanDragMixin from "../mixins/kanbanDrag.js";
import taskFormMixin from "../mixins/taskForm.js";
import spotlightMixin from "../mixins/spotlight.js";
import settings from "./settings.js";

const COLUMN_SELECTOR = "[data-column]";
const COLLAPSED_STORAGE_PREFIX = "taskboard-collapsed-";
const FAVORITES_STORAGE_KEY = "taskboard-favorites";
const FILTERS_OPEN_STORAGE_KEY = "taskboard-filters-open";
const SWIPE_HINT_STORAGE_KEY = "taskboard-swipe-hint-dismissed";

export default function taskboard() {
    const board = {
        ...maximizeMixin(),
        ...kanbanDragMixin(),
        ...spotlightMixin({ storageKey: "taskboard-spotlight" }),

        maximizedColumn: null,
        collapsed: {},
        favorites: [],
        showFavoritesOnly: false,
        filtersOpen: false,
        swipeHintDismissed: false,

        _favSet: null,

        init() {
            this.loadFavorites();
            this.initKanbanDrag();
            this.initSpotlight();
            this.filtersOpen = localStorage.getItem(FILTERS_OPEN_STORAGE_KEY) === "1";
            this.swipeHintDismissed = localStorage.getItem(SWIPE_HINT_STORAGE_KEY) === "1";

            const columns = document.querySelectorAll(COLUMN_SELECTOR);

            for (let i = 0, len = columns.length; i < len; i++) {
                const name = columns[i].dataset.column;

                if (name && this.collapsed[name] === undefined) {
                    this.collapsed[name] = localStorage.getItem(COLLAPSED_STORAGE_PREFIX + name) === "1";
                }
            }
        },

        initPattern() {
            return settings().initPattern();
        },

        loadFavorites() {
            const stored = localStorage.getItem(FAVORITES_STORAGE_KEY);

            if (!stored) {
                this.favorites = [];
                this._favSet = new Set();
                return;
            }

            try {
                this.favorites = JSON.parse(stored) || [];
            } catch {
                this.favorites = [];
            }

            this._favSet = new Set(this.favorites);
        },

        isFavorite(id) {
            return this._favSet.has(id);
        },

        toggleFavorite(id) {
            if (this._favSet.has(id)) {
                this._favSet.delete(id);
                const current = this.favorites;
                const next = [];
                for (let i = 0, len = current.length; i < len; i++) {
                    const fav = current[i];
                    if (fav !== id) next.push(fav);
                }
                this.favorites = next;
            } else {
                this._favSet.add(id);
                this.favorites.push(id);
            }

            try {
                localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(this.favorites));
            } catch {}
        },

        toggleFavoritesOnly() {
            this.showFavoritesOnly = !this.showFavoritesOnly;
        },

        toggleFilters() {
            this.filtersOpen = !this.filtersOpen;
            try { localStorage.setItem(FILTERS_OPEN_STORAGE_KEY, this.filtersOpen ? "1" : "0"); } catch {}
        },

        dismissSwipeHint() {
            this.swipeHintDismissed = true;
            try { localStorage.setItem(SWIPE_HINT_STORAGE_KEY, "1"); } catch {}
        },

        toggleMaximize(name) {
            if (this.maximizedColumn === name) {
                this.maximizedColumn = null;
                this.applyMaximize(false);
                return;
            }
            this.maximizedColumn = name;
            this.clearSpotlight();
            this.applyMaximize(!!name);
        },

        afterSpotlightChange() {
            if (this.spotlightColumn && this.maximizedColumn) {
                this.maximizedColumn = null;
                this.applyMaximize(false);
            }
        },

        isCollapsed(name) {
            return this.collapsed[name] === true;
        },

        toggleCollapsed(name) {
            const isNowCollapsed = !this.isCollapsed(name);

            this.collapsed[name] = isNowCollapsed;

            try {
                localStorage.setItem(COLLAPSED_STORAGE_PREFIX + name, isNowCollapsed ? "1" : "0");
            } catch {}
        },

    };

    return Object.defineProperties(board, Object.getOwnPropertyDescriptors(taskFormMixin()));
}
