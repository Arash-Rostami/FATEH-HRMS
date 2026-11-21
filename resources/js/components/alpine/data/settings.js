import clipboardMixin from "../mixins/clipboard.js";
import focusModeMixin from "../mixins/focusMode.js";

const patternLoaders = {
    shapes: () => import("./patterns/shapes.js"),
    rain: () => import("./patterns/rain.js"),
    particle: () => import("./patterns/particle.js"),
    parallax: () => import("./patterns/parallax.js"),
    gradient: () => import("./patterns/gradient.js"),
    geometry: () => import("./patterns/geometry.js"),
    cloud: () => import("./patterns/cloud.js"),
    flora: () => import("./patterns/flora.js"),
    ambient: () => import("./patterns/ambient.js"),
    cyber: () => import("./patterns/cyber.js"),
    google: () => import("./patterns/google.js"),
    note: () => import("./patterns/note.js"),
    ripple: () => import("./patterns/ripple.js"),
    firefly: () => import("./patterns/firefly.js"),
    snow: () => import("./patterns/snow.js")
};

const BACKGROUND_ELEMENT_IDS = Object.freeze([
    'interactive-background',
    'interactive-background-apple',
    'interactive-background-google'
]);

const PATTERN_SETTLE_DELAY = 50;
const RESET_RELOAD_DELAY = 500;
const TOAST_VISIBLE_MS = 1500;
const TOAST_FADE_MS = 200;
const SOUND_SCOPES = Object.freeze(['channel', 'contact', 'project']);
const NO_PATTERNS = Object.freeze([]);

const TABLE_MODULES = Object.freeze([
    { id: 'dms', label: 'مدیریت اسناد', cols: true },
    { id: 'ths', label: 'تیکت‌های پشتیبانی', cols: true },
    { id: 'reservation', label: 'رزرو امکانات', cols: true },
    { id: 'taskboard', label: 'تسک‌بورد', cols: true, colsLabel: 'بخش کارت' },
    { id: 'ai-report', label: 'گزارش ساز هوشمند', cols: true },
    { id: 'correspondence', label: 'مکاتبات', cols: true },
    { id: 'launchpad', label: 'پیشخوان برنامه‌ها', cols: true },
    { id: 'media', label: 'دانش سازمانی', cols: true }
]);

const MODULES_BY_ID = new Map(TABLE_MODULES.map(mod => [mod.id, mod]));

const EXPORT_KEYS = Object.freeze([
    'user-theme', 'user-mode', 'app-density',
    'backgroundEnabled', 'patternEnabled', 'activePattern', 'bgTone',
    'fontSizeLevel', 'readingRuler', 'doubleClickCopy',
    'dms-col-hidden', 'ths-col-hidden', 'reservation-col-hidden', 'taskboard-col-hidden', 'ai-report-col-hidden', 'correspondence-col-hidden', 'launchpad-col-hidden', 'media-col-hidden',
    'table-tools:dms', 'table-tools:ths', 'table-tools:reservation', 'table-tools:taskboard', 'table-tools:ai-report', 'table-tools:correspondence', 'table-tools:launchpad', 'table-tools:media',
    'menu_declutter',
    'chat-muted-channels', 'chat-muted-contacts', 'chat-muted-projects', 'notification-sound-muted',
    'chat-push-channel', 'chat-push-contact', 'chat-push-project'
]);

let activePatternInstance = null;
let patternInitRunId = 0;
let patternInitAbort = null;
let watcherOwner = null;

export default function settings() {
    return {
        ...clipboardMixin(),
        ...focusModeMixin(),
        open: false,
        focusDuration: null,
        currentPresence: 'onsite',

        _isDestroyed: false,
        _resetPending: false,
        _onConfirmed: null,
        tableModules: TABLE_MODULES,
        tableState: { modules: {}, compact: false, menuDeclutter: false },

        get fontSizeLevel() { return this.$store.accessibility.fontSizeLevel; },
        get minScale() { return this.$store.accessibility.minScale; },
        get maxScale() { return this.$store.accessibility.maxScale; },
        get readingRuler() { return this.$store.accessibility.readingRuler; },
        get doubleClickCopy() { return this.$store.accessibility.doubleClickCopy; },

        get availablePatterns() {
            return this.$store?.background?.patterns || NO_PATTERNS;
        },

        init() {
            this._isDestroyed = false;
            const ds = this.$root?.dataset;
            this.currentPresence = ds?.currentPresence || 'onsite';

            this._onConfirmed = (e) => {
                if (!this._resetPending || e.detail?.method !== 'resetApp') return;
                this._resetPending = false;
                this.performReset();
            };
            window.addEventListener('confirmation-confirmed', this._onConfirmed);

            this.initFocusFromSnapshot();
            this.refreshTableState();

            if (!this.$store.focus.active) {
                const serverUntil = parseInt(ds?.focusUntil || '', 10);
                if (serverUntil && serverUntil > Date.now()) {
                    const endsAt = serverUntil - Date.now() > 86400000 ? null : serverUntil;
                    this.$store.focus.active = true;
                    this.$store.focus.until = endsAt;
                    this.$store.focus.schedule(endsAt);
                }
            }

            this.$nextTick(() => this.initPattern());

            if (watcherOwner === null) {
                watcherOwner = this;
                this.$watch('$store.background.patternEnabled + "|" + $store.background.activePattern', () => this.initPattern());
                this.$watch('$store.background.enabled', (value) => {
                    if (value) this.clearVisuals();
                });
            }
        },

        increaseFontSize() { this.$store.accessibility.increaseFontSize(); },
        decreaseFontSize() { this.$store.accessibility.decreaseFontSize(); },
        resetFontSize() { this.$store.accessibility.resetFontSize(); },
        getScaleLabel() { return this.$store.accessibility.getScaleLabel(); },
        toggleReadingRuler() { this.$store.accessibility.toggleReadingRuler(); },
        toggleDoubleClickCopy() { this.$store.accessibility.toggleDoubleClickCopy(); },

        _copyToast() {
            const toast = document.createElement('div');
            toast.textContent = 'کپی شد';
            toast.classList.add('toast-floating');
            document.body.appendChild(toast);

            window.requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), TOAST_FADE_MS);
            }, TOAST_VISIBLE_MS);
        },

        clearVisuals() {
            if (typeof activePatternInstance?.destroy === 'function') {
                try { activePatternInstance.destroy(); } catch (e) {}
            }
            activePatternInstance = null;

            for (let i = 0; i < BACKGROUND_ELEMENT_IDS.length; i++) {
                document.getElementById(BACKGROUND_ELEMENT_IDS[i])?.remove();
            }
        },

        async initPattern() {
            patternInitAbort?.abort();
            const controller = new AbortController();
            patternInitAbort = controller;
            const runId = ++patternInitRunId;

            this.clearVisuals();

            const store = this.$store?.background;
            if (!store?.patternEnabled) return;

            const loader = patternLoaders[store.activePattern || 'shapes'];
            if (!loader) return;

            try {
                const module = await loader();
                if (controller.signal.aborted || runId !== patternInitRunId || this._isDestroyed) return;

                await new Promise(r => setTimeout(r, PATTERN_SETTLE_DELAY));
                if (controller.signal.aborted || runId !== patternInitRunId || this._isDestroyed) return;

                const patternObj = module.default;
                if (typeof patternObj?.init === 'function') {
                    patternObj.init();
                    activePatternInstance = patternObj;
                } else if (typeof patternObj === 'function') {
                    activePatternInstance = patternObj();
                    if (typeof activePatternInstance === 'function') activePatternInstance();
                }
            } catch (e) {}
        },

        setPattern(patternId) {
            this.$store?.background?.setPattern?.(patternId);
        },

        toggleBackground() {
            const store = this.$store?.background;
            store?.toggleBackground?.(!store.enabled);
        },

        togglePattern() {
            const store = this.$store?.background;
            store?.togglePattern?.(!store.patternEnabled);
        },

        setFocusDuration(minutes) {
            this.focusDuration = minutes;
        },

        toggleFocus() {
            if (!this.$store.focus.active) {
                this.activateFocus(this.focusDuration ?? null, this.currentPresence);
            } else {
                this.deactivateFocus();
            }
        },

        resetAppearance() {
            window.ThemeManager?.setTheme('default');
            if (this.$store.density?.compact) this.$store.density.toggle();
            this.$store.background?.toggleBackground(false);
            this.$store.background?.togglePattern(false);
            this.$store.background?.setTone?.('');
            this.resetFontSize();
            if (this.readingRuler) this.toggleReadingRuler();
            if (this.doubleClickCopy) this.toggleDoubleClickCopy();
        },

        resetNotifications() {
            const sound = this.$store.sound;
            for (const scope of SOUND_SCOPES) sound?.clearAll(scope);
            if (sound?.notificationMuted) sound.toggleNotification();
        },

        _readFlag(key, expected) {
            try {
                const value = localStorage.getItem(key);
                return expected === undefined ? value !== null : value === expected;
            } catch { return false; }
        },

        _drop(key) {
            try { localStorage.removeItem(key); } catch {}
        },

        _maxKey(id) {
            const uid = document.documentElement.dataset.uid;
            return uid ? `max:${id}:${uid}` : null;
        },

        refreshTableState() {
            const uid = document.documentElement.dataset.uid;
            const maps = this.$store.colVisibility?.maps;
            const modules = {};
            for (const { id, cols } of TABLE_MODULES) {
                modules[id] = {
                    hiddenCols: cols ? (maps?.[id]?.length ?? 0) : 0,
                    toolbarCollapsed: this._readFlag(`table-tools:${id}`, 'true'),
                    maximized: !!uid && this._readFlag(`max:${id}:${uid}`)
                };
            }
            this.tableState = {
                modules,
                compact: !!this.$store.density?.compact,
                menuDeclutter: this._readFlag('menu_declutter', 'true')
            };
        },

        _resetModule(id, uid) {
            const mod = MODULES_BY_ID.get(id);
            if (!mod) return false;
            if (mod.cols) this.$store.colVisibility?.reset(id);
            this._drop(`table-tools:${id}`);
            if (uid) this._drop(`max:${id}:${uid}`);
            return true;
        },

        _resetDensity() {
            if (this.$store.density?.compact) this.$store.density.toggle();
        },

        _resetDeclutter() {
            try { localStorage.setItem('menu_declutter', 'false'); } catch {}
            document.documentElement.classList.remove('declutter');
            window.dispatchEvent(new CustomEvent('declutter', { detail: { on: false } }));
        },

        resetTableModule(id) {
            if (this._resetModule(id, document.documentElement.dataset.uid)) this.refreshTableState();
        },

        resetDensity() {
            this._resetDensity();
            this.refreshTableState();
        },

        resetDeclutter() {
            this._resetDeclutter();
            this.refreshTableState();
        },

        resetTables() {
            const uid = document.documentElement.dataset.uid;
            for (const { id } of TABLE_MODULES) this._resetModule(id, uid);
            this._resetDensity();
            this._resetDeclutter();
            this.refreshTableState();
        },

        exportSettings() {
            const data = {};
            for (const key of EXPORT_KEYS) {
                const value = localStorage.getItem(key);
                if (value !== null) data[key] = value;
            }

            const url = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }));
            const a = document.createElement('a');
            a.href = url;
            a.download = 'fateh-settings.json';
            a.click();
            URL.revokeObjectURL(url);
        },

        importSettings(file) {
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                try {
                    const data = JSON.parse(reader.result);
                    if (!data || typeof data !== 'object' || Array.isArray(data)) return;

                    for (const key of EXPORT_KEYS) {
                        if (typeof data[key] === 'string') localStorage.setItem(key, data[key]);
                    }

                    location.reload();
                } catch (e) {}
            };
            reader.readAsText(file);
        },

        resetApp() {
            this._resetPending = true;
            window.dispatchEvent(new CustomEvent('open-confirmation', {
                detail: { title: 'بازنشانی تنظیمات', message: 'همه تنظیمات بازنشانی شود؟', method: 'resetApp', type: 'dispatch', tone: 'danger' }
            }));
        },

        performReset() {
            if ('caches' in window) {
                caches.keys().then(names => names.forEach(n => caches.delete(n)));
            }
            navigator.serviceWorker?.getRegistrations().then(regs => regs.forEach(r => r.unregister()));
            try { localStorage.clear(); } catch (e) {}
            setTimeout(() => location.reload(), RESET_RELOAD_DELAY);
        },

        destroy() {
            this._isDestroyed = true;

            window.removeEventListener('confirmation-confirmed', this._onConfirmed);

            patternInitAbort?.abort();
            patternInitAbort = null;

            if (watcherOwner === this) watcherOwner = null;

            this.destroyFocusMode();
            this.clearVisuals();
        }
    };
}
