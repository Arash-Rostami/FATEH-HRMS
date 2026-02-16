export default function sidebar() {
    return {
        isExpanded: false,
        activeTab: window.location.pathname,
        touchStartX: 0,
        touchStartY: 0,
        isDragging: false,
        hoveredItem: null,

        init() {
            this.activeTab = window.location.pathname;
        },

        handleTouchStart(event) {
            this.touchStartX = event.changedTouches[0].screenX;
            this.touchStartY = event.changedTouches[0].screenY;
            this.isDragging = true;
        },

        handleTouchMove(event) {
            if (this.isDragging && Math.abs(event.changedTouches[0].screenY - this.touchStartY) < 30) {
                event.preventDefault();
            }
        },

        handleTouchEnd(event) {
            this.isDragging = false;
            const deltaX = event.changedTouches[0].screenX - this.touchStartX;
            const deltaY = Math.abs(event.changedTouches[0].screenY - this.touchStartY);

            if (deltaY < 50) {
                if (deltaX > 80) {
                    this.isExpanded = true;
                } else if (deltaX < -80) {
                    this.isExpanded = false;
                }
            }
        },

        expand() {
            if (!this.isExpanded && window.innerWidth >= 1024) {
                this.isExpanded = true;
            }
        },

        collapse() {
            if (this.isExpanded && window.innerWidth >= 1024) {
                this.isExpanded = false;
            }
        },

        toggleExpand() {
            this.isExpanded = !this.isExpanded;
        },

        setHover(item) {
            this.hoveredItem = item;
        },

        clearHover() {
            this.hoveredItem = null;
        },

        isHovered(item) {
            return this.hoveredItem === item;
        },

        isActive(item) {
            return this.activeTab.includes(item);
        },

        getTransitionDelay(index) {
             if (!this.isExpanded) return '0ms';
             return `${100 + (index * 50)}ms`;
        }
    }
}
