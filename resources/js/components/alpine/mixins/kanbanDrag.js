const COLUMN_SELECTOR = "[data-column]";
const DRAG_EFFECT_MOVE = "move";
const DRAG_DATA_TEXT = "text/plain";
const OPACITY_WHILE_DRAGGING = "0.5";
const OPACITY_NORMAL = "1";

export default function kanbanDragMixin() {
    return {
        dragTask: null,
        isDragging: false,

        initKanbanDrag() {
            this.$watch("dragTask", (value) => {
                this.isDragging = !!value;
            });
        },

        col(el) {
            if (!el) return undefined;
            const closest = el.closest(COLUMN_SELECTOR);
            return closest ? closest.dataset.column : undefined;
        },

        handleDragStart(event, taskId) {
            this.dragTask = taskId;

            const dt = event.dataTransfer;
            dt.effectAllowed = DRAG_EFFECT_MOVE;
            dt.setData(DRAG_DATA_TEXT, taskId);

            const el = event.target;
            requestAnimationFrame(() => { el.style.opacity = OPACITY_WHILE_DRAGGING; });
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
            const taskId = this.dragTask;
            if (!taskId) return;

            event.preventDefault();

            this.dragTask = null;
            this.isDragging = false;

            this.$wire.updateTaskStatus(taskId, status);
        },

        handleCardDrop(event, targetTaskId, targetStatus) {
            const taskId = this.dragTask;
            if (!taskId) return;

            event.preventDefault();

            this.dragTask = null;
            this.isDragging = false;

            if (taskId === targetTaskId) return;

            this.$wire.reorderTask(taskId, targetTaskId, targetStatus);
        },
    };
}
