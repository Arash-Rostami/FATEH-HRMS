const LOCAL_CLASS = 'declutter-local';
let navCleanupBound = false;

function syncLocalClass(on) {
    document.documentElement.classList.toggle(LOCAL_CLASS, !!on);
}

function bindNavCleanup() {
    if (navCleanupBound) return;
    navCleanupBound = true;
    document.addEventListener('livewire:navigating', () => syncLocalClass(false));
}

export default function headerDeclutterMixin() {
    return {
        headerDeclutter: false,
        headerDeclutterExit: false,
        _declutterTimer: null,
        _menuForced: false,

        headerDeclutterMenuBridge(signal) {
            const apply = (on) => {
                this._menuForced = !!on;
                this.headerDeclutter = !!on;
                if (!on) this._persistChatSettings();
            };

            bindNavCleanup();
            syncLocalClass(this.headerDeclutter);
            this.$watch?.('headerDeclutter', syncLocalClass);
            signal?.addEventListener('abort', () => syncLocalClass(false));

            window.addEventListener('declutter', (e) => {
                clearTimeout(this._declutterTimer);
                this.headerDeclutterExit = false;
                if (e?.detail?.on !== undefined) apply(e.detail.on);
            }, { signal });

            if (localStorage.getItem('menu_declutter') === 'true') apply(true);
        },

        toggleHeaderDeclutter() {
            clearTimeout(this._declutterTimer);
            this._menuForced = false;
            if (this.headerDeclutterExit) {
                this.headerDeclutterExit = false;
                return;
            }
            if (!this.headerDeclutter) {
                this.headerDeclutterExit = true;
                this._declutterTimer = setTimeout(() => {
                    this.headerDeclutterExit = false;
                    this.headerDeclutter = true;
                    this._persistChatSettings();
                }, 400);
                return;
            }
            this.headerDeclutter = false;
            this._persistChatSettings();
        },

        headerBtnClass(open, display = 'flex') {
            if (this.headerDeclutterExit) return `md:${display} animate-slide-fade-to-left`;
            if (this.headerDeclutter) return 'hidden';
            if (open) return display;
            return `hidden md:${display}`;
        },
    };
}