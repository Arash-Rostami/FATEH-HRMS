const CORE_KEYS = new Set(['bio', 'movies', 'music', 'hobbies', 'food', 'sports']);
const PAD = 56;
const DRAG_THRESHOLD = 5;

export default function status(view) {
    return {
        view,
        collapsed: {},
        down: false,
        dragged: false,
        startX: 0,
        startY: 0,
        startScroll: 0,
        startPageY: 0,
        hovering: false,
        mouseX: 0,
        mouseY: 0,
        toolsOpen: false,
        toolsX: 0,
        toolsY: 0,
        vBox: null,
        user: null,
        aboutMe: {},
        tab: 'levels',

        init() {
            this.vBox = this.$root.closest('.custom-scrollbar') || document.scrollingElement || document.documentElement;
        },

        toggleDept(code) {
            this.collapsed[code] = !this.collapsed[code];
        },

        openTools(e) {
            this.toolsOpen = true;
            this.toolsX = Math.min(Math.max(e.clientX, PAD), window.innerWidth - PAD);
            this.toolsY = Math.min(Math.max(e.clientY, PAD), window.innerHeight - PAD);
        },

        pan(dx, dy) {
            if (dx && this.$refs.depts) {
                this.$refs.depts.scrollBy({ left: dx, behavior: 'smooth' });
            }
            if (dy) {
                this.vBox.scrollBy({ top: dy, behavior: 'smooth' });
            }
        },

        resetData() {
            setTimeout(() => { this.user = null; }, 300);
        },

        get extraKeys() {
            return Object.keys(this.aboutMe).filter(k => this.aboutMe[k] && !CORE_KEYS.has(k));
        },

        onMouseDown(e) {
            this.down = true;
            this.dragged = false;
            this.startX = e.clientX;
            this.startY = e.clientY;
            this.startScroll = this.$refs.depts?.scrollLeft ?? 0;
            this.startPageY = this.vBox.scrollTop;
        },

        onMouseMove(e) {
            this.mouseX = e.clientX;
            this.mouseY = e.clientY;

            if (!this.down) {
                return;
            }

            e.preventDefault();

            if (Math.abs(e.clientX - this.startX) > DRAG_THRESHOLD || Math.abs(e.clientY - this.startY) > DRAG_THRESHOLD) {
                this.dragged = true;
            }

            if (this.$refs.depts) {
                this.$refs.depts.scrollLeft = this.startScroll - (e.clientX - this.startX);
            }

            this.vBox.scrollTop = this.startPageY - (e.clientY - this.startY);
        },

        onMouseUp() {
            this.down = false;
        },

        onMouseLeave() {
            this.down = false;
            this.hovering = false;
        },

        onWheel(e) {
            if (this.$refs.depts && this.$refs.depts.scrollWidth > this.$refs.depts.clientWidth) {
                e.preventDefault();
                this.$refs.depts.scrollLeft += e.deltaY;
            }
        },

        onClickCapture(e) {
            if (this.dragged) {
                e.stopPropagation();
                this.dragged = false;
            }
        }
    };
}
