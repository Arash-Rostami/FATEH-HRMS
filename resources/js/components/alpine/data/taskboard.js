import maximizeMixin from "../mixins/maximize.js";
import settings from "./settings.js";

const COLUMN_SELECTOR = "[data-column]";
const COLLAPSED_STORAGE_PREFIX = "taskboard-collapsed-";

const DRAG_EFFECT_MOVE = "move";
const DRAG_DATA_TEXT = "text/plain";

const OPACITY_WHILE_DRAGGING = "0.5";
const OPACITY_NORMAL = "1";

export default function taskboard() {
    return {
        ...maximizeMixin(),

        dragTask: null,
        isDragging: false,
        maximizedColumn: null,
        collapsed: {},

        toggleMaximize(name) {
            this.maximizedColumn = this.maximizedColumn === name ? null : name;
            this.applyMaximize(!!this.maximizedColumn);
        },

        col(el) {
            return el?.closest(COLUMN_SELECTOR)?.dataset.column;
        },

        isCollapsed(name) {
            return this.collapsed[name] === true;
        },

        toggleCollapsed(name) {
            this.collapsed[name] = !this.isCollapsed(name);

            localStorage.setItem(
                COLLAPSED_STORAGE_PREFIX + name,
                this.collapsed[name] ? "1" : "0"
            );
        },

        init() {
            this.$watch("dragTask", value => {
                this.isDragging = !!value;
            });

            for (const el of document.querySelectorAll(COLUMN_SELECTOR)) {
                const name = el.dataset.column;

                if (name && this.collapsed[name] === undefined) {
                    this.collapsed[name] =
                        localStorage.getItem(COLLAPSED_STORAGE_PREFIX + name) === "1";
                }
            }
        },

        initPattern() {
            return settings().initPattern();
        },

        handleDragStart(event, taskId) {
            this.dragTask = taskId;

            event.dataTransfer.effectAllowed = DRAG_EFFECT_MOVE;
            event.dataTransfer.setData(DRAG_DATA_TEXT, taskId);

            event.target.style.opacity = OPACITY_WHILE_DRAGGING;
        },

        handleDragEnd(event) {
            this.dragTask = null;
            this.isDragging = false;

            event.target.style.opacity = OPACITY_NORMAL;
        },

        handleDragOver(event) {
            if (!this.dragTask) return;

            event.preventDefault();
            event.dataTransfer.dropEffect = DRAG_EFFECT_MOVE;
        },

        handleDrop(event, status) {
            if (!this.dragTask) return;

            event.preventDefault();

            const taskId = this.dragTask;

            this.$wire.updateTaskStatus(taskId, status);

            this.dragTask = null;
            this.isDragging = false;
        },
    };
}
