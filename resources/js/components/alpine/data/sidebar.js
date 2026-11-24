const TOUCH_Y_THRESHOLD_MOVE = 30;
const TOUCH_Y_THRESHOLD_END = 50;
const TOUCH_X_THRESHOLD_EXPAND = 80;
const DESKTOP_BREAKPOINT = 1024;
const TRANSITION_BASE_DELAY = 100;
const TRANSITION_STEP_DELAY = 50;
const SETTLE_MS = 450;

const isDesktop = () => window.innerWidth >= DESKTOP_BREAKPOINT;

export default function sidebar() {
    let rail, rr = null, raf = 0, until = 0, ty = 0, startX = 0, startY = 0;

    const rightRail = () => rr?.isConnected ? rr : (rr = document.querySelector('.right-rail'));

    const railTop = () => {
        const el = rightRail();
        const first = el?.querySelector('button');
        return first ? el.getBoundingClientRect().top + first.offsetTop : null;
    };

    const railHeight = () => {
        const buttons = rightRail()?.querySelectorAll('button');
        if (!buttons?.length) return null;
        const first = buttons[0];
        const last = buttons[buttons.length - 1];
        return last.offsetTop + last.offsetHeight - first.offsetTop;
    };

    const update = () => {
        if (ty && !rail.style.transform) ty = 0;
        const r = rail.getBoundingClientRect();
        const base = r.top - ty;
        let y;
        if (isDesktop()) {
            const top = railTop();
            if (top === null) return;
            y = top - base;
        } else {
            const footer = document.getElementById('footer');
            y = footer ? -Math.max(0, base + r.height - footer.getBoundingClientRect().top + 8) : 0;
        }
        const dpr = window.devicePixelRatio || 1;
        y = Math.round(y * dpr) / dpr;
        if (y === ty) return;
        ty = y;
        rail.style.transform = `translate3d(0,${y}px,0)`;
    };

    const step = () => {
        update();
        raf = performance.now() < until ? requestAnimationFrame(step) : 0;
    };

    const kick = () => {
        if (isDesktop()) return;
        until = performance.now() + SETTLE_MS;
        if (!raf) raf = requestAnimationFrame(step);
    };

    const resize = () => {
        const h = isDesktop() ? railHeight() : null;
        const value = h === null ? '' : `${h}px`;
        if (rail.style.height !== value) rail.style.height = value;
        update();
        kick();
    };

    const settle = (tries = 40) => {
        resize();
        if (isDesktop() && railHeight() === null && tries > 0) requestAnimationFrame(() => settle(tries - 1));
    };

    return {
        isExpanded: false,
        activeTab: '',
        isDragging: false,
        hoveredItem: null,

        init() {
            rail = this.$refs.rail;
            this.activeTab = window.location.pathname;
            rail.removeAttribute('x-cloak');
            settle();
            this.$nextTick(resize);
        },

        destroy() {
            cancelAnimationFrame(raf);
            raf = 0;
        },

        resize,
        settle,
        upd: update,
        kick,

        handleTouchStart(event) {
            const touch = event.changedTouches[0];
            if (!touch) return;
            startX = touch.screenX;
            startY = touch.screenY;
            this.isDragging = true;
        },

        handleTouchMove(event) {
            if (!this.isDragging) return;
            const touch = event.changedTouches[0];
            if (touch && Math.abs(touch.screenY - startY) < TOUCH_Y_THRESHOLD_MOVE && event.cancelable) {
                event.preventDefault();
            }
        },

        handleTouchEnd(event) {
            this.isDragging = false;
            const touch = event.changedTouches[0];
            if (!touch || Math.abs(touch.screenY - startY) >= TOUCH_Y_THRESHOLD_END) return;
            const deltaX = touch.screenX - startX;
            if (deltaX > TOUCH_X_THRESHOLD_EXPAND) this.isExpanded = true;
            else if (deltaX < -TOUCH_X_THRESHOLD_EXPAND) this.isExpanded = false;
        },

        expand() {
            if (!this.isExpanded && isDesktop()) this.isExpanded = true;
        },

        collapse() {
            if (this.isExpanded && isDesktop()) this.isExpanded = false;
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
            return this.isExpanded ? `${TRANSITION_BASE_DELAY + index * TRANSITION_STEP_DELAY}ms` : '0ms';
        }
    };
}
