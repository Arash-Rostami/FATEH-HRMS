const TOUCH_Y_THRESHOLD_MOVE = 30;
const TOUCH_Y_THRESHOLD_END = 50;
const TOUCH_X_THRESHOLD_EXPAND = 80;
const DESKTOP_BREAKPOINT = 1024;
const TRANSITION_BASE_DELAY = 100;
const TRANSITION_STEP_DELAY = 50;

export default function sidebar() {
    return {
        isExpanded: false,
        activeTab: '',
        touchStartX: 0,
        touchStartY: 0,
        isDragging: false,
        hoveredItem: null,

        init() {
            this.activeTab = window.location.pathname;
        },

        handleTouchStart(event) {
            const touch = event.changedTouches[0];
            if (!touch) return;

            this.touchStartX = touch.screenX;
            this.touchStartY = touch.screenY;
            this.isDragging = true;
        },

        handleTouchMove(event) {
            if (!this.isDragging) return;

            const touch = event.changedTouches[0];
            if (!touch) return;

            if (Math.abs(touch.screenY - this.touchStartY) < TOUCH_Y_THRESHOLD_MOVE) {
                if (event.cancelable) {
                    event.preventDefault();
                }
            }
        },

        handleTouchEnd(event) {
            this.isDragging = false;

            const touch = event.changedTouches[0];
            if (!touch) return;

            const deltaX = touch.screenX - this.touchStartX;
            const deltaY = Math.abs(touch.screenY - this.touchStartY);

            if (deltaY < TOUCH_Y_THRESHOLD_END) {
                if (deltaX > TOUCH_X_THRESHOLD_EXPAND) {
                    this.isExpanded = true;
                } else if (deltaX < -TOUCH_X_THRESHOLD_EXPAND) {
                    this.isExpanded = false;
                }
            }
        },

        expand() {
            if (!this.isExpanded && window.innerWidth >= DESKTOP_BREAKPOINT) {
                this.isExpanded = true;
            }
        },

        collapse() {
            if (this.isExpanded && window.innerWidth >= DESKTOP_BREAKPOINT) {
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
            return `${TRANSITION_BASE_DELAY + (index * TRANSITION_STEP_DELAY)}ms`;
        }
    };
}
