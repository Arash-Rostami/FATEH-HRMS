const SLIDE_MS = 300;

export default function sidebarRailMixin(scope) {
    const key = `sb-collapsed:${scope}`;
    let timer;

    const read = () => {
        try {
            return localStorage.getItem(key) === '1';
        } catch {
            return false;
        }
    };

    return {
        sbCollapsed: read(),
        sbSlide: false,

        toggleSidebar() {
            this.sbCollapsed = !this.sbCollapsed;
            this.sbSlide = true;
            clearTimeout(timer);
            timer = setTimeout(() => { this.sbSlide = false; }, SLIDE_MS);
            try {
                localStorage.setItem(key, this.sbCollapsed ? '1' : '0');
            } catch {}
        },
    };
}
