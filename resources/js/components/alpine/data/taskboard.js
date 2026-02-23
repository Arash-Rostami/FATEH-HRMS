export default () => ({
    dragTask: null,
    isDragging: false,

    init() {
        this.('dragTask', value => {
            this.isDragging = !!value;
        });
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

        // Optimistic UI update could happen here if needed
        // For now, let Livewire handle the state update
        this..updateTaskStatus(taskId, status);

        this.dragTask = null;
        this.isDragging = false;
    }
});
