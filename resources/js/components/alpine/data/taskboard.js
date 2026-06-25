import maximizeMixin from "../mixins/maximize.js";
import settings from "./settings.js";

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
            return el?.closest('[data-column]')?.dataset.column;
        },

        isCollapsed(name) {
            return this.collapsed[name] === true;
        },

        toggleCollapsed(name) {
            this.collapsed[name] = !this.isCollapsed(name);
            localStorage.setItem('taskboard-collapsed-' + name, this.collapsed[name] ? '1' : '0');
        },

        init() {
            this.$watch('dragTask', value => {
                this.isDragging = !!value;
            });

            document.querySelectorAll('[data-column]').forEach(el => {
                const name = el.dataset.column;
                if (name && this.collapsed[name] === undefined) {
                    this.collapsed[name] = localStorage.getItem('taskboard-collapsed-' + name) === '1';
                }
            });
        },

        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        },

        handleDragStart(event, taskId) {
            this.dragTask = taskId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', taskId);
            event.target.style.opacity = '0.5';
        },

        handleDragEnd(event) {
            this.dragTask = null;
            this.isDragging = false;
            event.target.style.opacity = '1';
        },

        handleDragOver(event) {
            if (this.dragTask) {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
            }
        },

        handleDrop(event, status) {
            if (!this.dragTask) return;

            event.preventDefault();
            const taskId = this.dragTask;

            this.$wire.updateTaskStatus(taskId, status);

            this.dragTask = null;
            this.isDragging = false;
        }
    }
}
