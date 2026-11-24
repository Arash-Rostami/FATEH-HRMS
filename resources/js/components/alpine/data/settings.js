import clipboardMixin from "../mixins/clipboard.js";
import focusModeMixin from "../mixins/focusMode.js";
import chatScaleMixin, { clearScale, readScale, MINE_STEPS } from "../mixins/chatScale.js";

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
const PUSH_SCOPES = Object.freeze(['contact', 'channel']);

const readPermission = () => (typeof Notification === 'undefined' ? 'denied' : Notification.permission);

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

const CHAT_MODULES = Object.freeze([
    { id: 'contact', label: 'پیام‌رسان' },
    { id: 'channel', label: 'گروه‌ها' },
    { id: 'project', label: 'پروژه‌ها' }
]);
const SIDEBAR_MODULES = Object.freeze([...CHAT_MODULES, { id: 'media', label: 'کتابخانهٔ دانش' }]);
const CHAT_RESET_FIELDS = Object.freeze(['messageFont', 'isHighlighted', 'backgroundPattern', 'backgroundPatternType', 'mineOpacity']);
const MENU_VIEW_MODES = Object.freeze(['grid', 'list', 'grouped']);
const LABEL_PRESETS = Object.freeze(['all', 'icons', 'smart']);
const CLOCK_MODES = Object.freeze(['fa', 'en']);
const layoutChoice = (title, setter, field, values, labels) => ({
    title, setter, field, options: values.map((value) => ({ value, label: labels[value] }))
});
const LAYOUT_CHOICES = Object.freeze([
    layoutChoice('نمای منو', 'setMenuViewMode', 'viewMode', MENU_VIEW_MODES, { grid: 'شبکه‌ای', list: 'فهرستی', grouped: 'دسته‌بندی‌شده' }),
    layoutChoice('نمایش نوار پایین در گوشی', 'setBottomLabels', 'labels', LABEL_PRESETS, { all: 'آیکون و نام (پیش‌فرض)', icons: 'فقط آیکون', smart: 'پنهان شدن خودکار نوار' }),
    layoutChoice('زبان نمایش ساعت', 'setClockMode', 'clock', CLOCK_MODES, { fa: 'فارسی', en: 'انگلیسی' })
]);
const LAYOUT_KEYS =Object.freeze(['menu_view_mode', 'menu_sort_alpha', 'btm-labels', 'timer-mode']);
const chatKey = (scope) => `chat-settings:${scope}`;
const sidebarKey = (scope) => `sb-collapsed:${scope}`;

const EXPORT_KEYS = Object.freeze([
    'user-theme', 'user-mode', 'app-density',
    'backgroundEnabled', 'patternEnabled', 'activePattern', 'bgTone',
    'fontSizeLevel', 'readingRuler', 'doubleClickCopy',
    'dms-col-hidden', 'ths-col-hidden', 'reservation-col-hidden', 'taskboard-col-hidden', 'ai-report-col-hidden', 'correspondence-col-hidden', 'launchpad-col-hidden', 'media-col-hidden',
    'table-tools:dms', 'table-tools:ths', 'table-tools:reservation', 'table-tools:taskboard', 'table-tools:ai-report', 'table-tools:correspondence', 'table-tools:launchpad', 'table-tools:media',
    'menu_declutter', 'menu_view_mode', 'menu_sort_alpha', 'btm-labels', 'timer-mode',
    'chat-muted-channels', 'chat-muted-contacts', 'chat-muted-projects', 'notification-sound-muted',
    'chat-push-channel', 'chat-push-contact', 'chat-push-project',
    'chat-ui-scale', 'chat-settings:contact', 'chat-settings:channel', 'chat-settings:project',
    'sb-collapsed:contact', 'sb-collapsed:channel', 'sb-collapsed:project', 'sb-collapsed:media'
]);

let activePatternInstance = null;
let patternInitRunId = 0;
let patternInitAbort = null;
let watcherOwner = null;

export default function settings() {
    return {
        ...clipboardMixin(),
        ...focusModeMixin(),
        ...chatScaleMixin(),
        open: false,
        focusDuration: null,
        currentPresence: 'onsite',

        _isDestroyed: false,
        _resetPending: false,
        _onConfirmed: null,
        tableModules: TABLE_MODULES,
        tableState: { modules: {}, compact: false, menuDeclutter: false },
        chatModules: CHAT_MODULES,
        sidebarModules: SIDEBAR_MODULES,
        chat: {},
        sidebar: {},
        layout: {},
        layoutChoices: LAYOUT_CHOICES,
        appBusy: false,
        permission: readPermission(),

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

        chatSettings(scope) {
            try {
                const data = JSON.parse(localStorage.getItem(chatKey(scope)));
                return data && typeof data === 'object' && !Array.isArray(data) ? data : {};
            } catch { return {}; }
        },

        _storeChat(scope, data) {
            try {
                if (Object.keys(data).length) localStorage.setItem(chatKey(scope), JSON.stringify(data));
                else localStorage.removeItem(chatKey(scope));
            } catch {}
        },

        saveChatSetting(scope, field, value) {
            this._storeChat(scope, { ...this.chatSettings(scope), [field]: value });
            if (this.chat[scope]) this.chat[scope][field] = value;
        },

        toggleChatLook(scope) {
            const on = !this.chat[scope]?.isHighlighted;
            this._storeChat(scope, { ...this.chatSettings(scope), isHighlighted: on, backgroundPattern: on ? 'on' : 'off' });
            if (this.chat[scope]) {
                this.chat[scope].isHighlighted = on;
                this.chat[scope].backgroundPattern = on ? 'on' : 'off';
            }
        },

        stepMine(scope, delta) {
            const next = MINE_STEPS[MINE_STEPS.indexOf(this.chat[scope].mineOpacity) + delta];
            if (next) this.saveChatSetting(scope, 'mineOpacity', next);
        },

        mineLabel(scope) {
            return new Intl.NumberFormat('fa-IR').format(this.chat[scope].mineOpacity) + '٪';
        },

        toggleSidebarDefault(scope) {
            const collapsed = !this.sidebar[scope];
            try { localStorage.setItem(sidebarKey(scope), collapsed ? '1' : '0'); } catch {}
            this.sidebar[scope] = collapsed;
        },

        refreshChatState() {
            this.scaleIndex = readScale();
            this.chat = Object.fromEntries(CHAT_MODULES.map(({ id }) => {
                const data = this.chatSettings(id);
                return [id, {
                    messageFont: typeof data.messageFont === 'string' ? data.messageFont : 'default',
                    isHighlighted: data.isHighlighted === true,
                    backgroundPattern: data.backgroundPattern === 'on' ? 'on' : 'off',
                    backgroundPatternType: typeof data.backgroundPatternType === 'string' ? data.backgroundPatternType : 'mesh',
                    mineOpacity: MINE_STEPS.includes(data.mineOpacity) ? data.mineOpacity : 100
                }];
            }));
            this.sidebar = Object.fromEntries(SIDEBAR_MODULES.map(({ id }) => [id, this._readFlag(sidebarKey(id), '1')]));
        },

        resetChat() {
            clearScale();
            for (const { id } of CHAT_MODULES) {
                const data = this.chatSettings(id);
                for (const field of CHAT_RESET_FIELDS) delete data[field];
                this._storeChat(id, data);
            }
            for (const { id } of SIDEBAR_MODULES) this._drop(sidebarKey(id));
            this.refreshChatState();
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
            this.setDeclutter(false);
        },

        _read(key) {
            try { return localStorage.getItem(key); } catch { return null; }
        },

        _write(key, value) {
            try { localStorage.setItem(key, value); } catch {}
        },

        _readJson(key) {
            try { return JSON.parse(this._read(key)); } catch { return null; }
        },

        refreshLayoutState() {
            const view = this._readJson('menu_view_mode');
            const labels = this._read('btm-labels');
            this.layout = {
                viewMode: MENU_VIEW_MODES.includes(view) ? view : 'grid',
                sortAlpha: !!this._readJson('menu_sort_alpha'),
                declutter: this._readFlag('menu_declutter', 'true'),
                labels: LABEL_PRESETS.includes(labels) ? labels : 'all',
                clock: this._read('timer-mode') === 'en' ? 'en' : 'fa'
            };
        },

        setMenuViewMode(mode) {
            if (!MENU_VIEW_MODES.includes(mode)) return;
            this._write('menu_view_mode', JSON.stringify(mode));
            this.layout.viewMode = mode;
        },

        toggleMenuSortAlpha() {
            const on = !this.layout.sortAlpha;
            this._write('menu_sort_alpha', JSON.stringify(on));
            this.layout.sortAlpha = on;
        },

        setDeclutter(on) {
            on = !!on;
            this._write('menu_declutter', on ? 'true' : 'false');
            document.documentElement.classList.toggle('declutter', on);
            window.dispatchEvent(new CustomEvent('declutter', { detail: { on } }));
            this.refreshLayoutState();
        },

        setBottomLabels(preset) {
            if (!LABEL_PRESETS.includes(preset)) return;
            this._write('btm-labels', preset);
            this.layout.labels = preset;
        },

        setClockMode(mode) {
            if (!CLOCK_MODES.includes(mode)) return;
            this._write('timer-mode', mode);
            this.layout.clock = mode;
        },

        resetLayout() {
            this.setDeclutter(false);
            for (const key of LAYOUT_KEYS) this._drop(key);
            this.refreshLayoutState();
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

        appRows() {
            const app = this.$store.app;
            const push = this.$store.push;
            const permission = readPermission();
            const iosPending = app.ios && !app.installed;
            const notificationsOn = PUSH_SCOPES.every((scope) => push.isEnabled(scope));

            let install = { status: 'todo', hint: 'برنامه را نصب کنید تا از نماد آن روی دستگاه بازش کنید.' };
            if (app.installed) install = { status: 'ok', hint: 'برنامه نصب شده است.' };
            else if (iosPending) install = { status: 'todo', hint: 'در آیفون نصب از منوی اشتراک‌گذاری مرورگر انجام می‌شود.' };
            else if (!app.installable) install = { status: 'unsupported', hint: 'اگر گزینهٔ نصب در منوی مرورگر دیده می‌شود، آن را انتخاب کنید.' };

            let notifications = { status: 'todo', hint: 'با بسته بودن برگهٔ سامانه هم می‌توانید اعلان پیام‌های خصوصی و گروهی را بگیرید.' };
            if (!push.supported) notifications = { status: 'unsupported', hint: 'این مرورگر از اعلان پشتیبانی نمی‌کند.' };
            else if (permission === 'denied') notifications = { status: 'blocked', hint: this.permissionHelp() };
            else if (iosPending) notifications = { status: 'todo', hint: 'در آیفون ابتدا برنامه را به صفحهٔ اصلی اضافه کنید، سپس اعلان‌ها را فعال کنید.' };
            else if (notificationsOn) notifications = { status: 'ok', hint: 'اعلان پیام‌های خصوصی و گروهی فعال است.' };

            let persisted = { status: 'todo', hint: 'از مرورگر بخواهید تنظیمات و داده‌های این برنامه را خودکار پاک نکند.' };
            if (typeof navigator === 'undefined' || !navigator.storage?.persist) persisted = { status: 'unsupported', hint: 'این مرورگر از نگهداری تنظیمات و داده‌های برنامه پشتیبانی نمی‌کند.' };
            else if (app.persisted) persisted = { status: 'ok', hint: 'مرورگر داده‌های این برنامه را برای مدت طولانی‌تر نگه می‌دارد.' };

            let badge = { status: 'todo', hint: 'پس از نصب، مجموع پیام‌های خوانده‌نشدهٔ گفتگوهای تازه را روی نماد برنامه ببینید.' };
            if (!app.badgeSupported) badge = { status: 'unsupported', hint: 'این مرورگر عدد روی نماد برنامه را پشتیبانی نمی‌کند.' };
            else if (app.installed) badge = { status: 'ok', hint: 'مجموع پیام‌های خوانده‌نشدهٔ گفتگوهای تازه روی نماد برنامه نمایش داده می‌شود.' };

            return [
                { id: 'install', icon: 'download', label: 'نصب برنامه', ...install, action: () => app.install() },
                { id: 'notifications', icon: 'notifications_active', label: 'اعلان پیام‌ها', ...notifications, granted: permission === 'granted', action: () => this.enableNotifications() },
                { id: 'persist', icon: 'database', label: 'نگهداری داده‌ها', ...persisted, action: () => app.persist() },
                { id: 'badge', icon: 'counter_1', label: 'عدد روی نماد برنامه', ...badge, action: null }
            ];
        },

        permissionHelp() {
            return this.$store.app.ios
                ? 'تنظیمات ← اعلان‌ها ← نام برنامه را باز کنید و اعلان‌ها را مجاز کنید.'
                : 'روی نماد قفل کنار نوار آدرس بزنید، اعلان‌ها را روی «اجازه» بگذارید و صفحه را دوباره باز کنید.';
        },

        windowsShortcutCommand(baseUrl) {
            return `"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe" --app=${(baseUrl || window.location.origin).replace(/\/+$/, '')}/`;
        },

        async _enableNotifications() {
            const push = this.$store.push;
            if (!push.supported) return false;
            for (const scope of PUSH_SCOPES) {
                if (!push.isEnabled(scope)) await push.toggle(scope);
                this.permission = readPermission();
                if (!push.isEnabled(scope)) return false;
            }
            return true;
        },

        async enableNotifications() {
            if (this.appBusy) return;
            this.appBusy = true;
            try {
                await this._enableNotifications();
                this.permission = readPermission();
            } finally {
                this.appBusy = false;
            }
        },

        async enableEverything() {
            if (this.appBusy) return;
            this.appBusy = true;
            try {
                const app = this.$store.app;
                if (!app.installed && !app.ios && await app.install() === 'dismissed') return;
                if (!(app.ios && !app.installed)) await this._enableNotifications();
                await app.persist();
            } finally {
                this.appBusy = false;
            }
        },

        async sendLocalTest() {
            try {
                if (readPermission() !== 'granted') return false;
                const registration = await navigator.serviceWorker.ready;
                await registration.showNotification('اعلان آزمایشی', {
                    body: 'اگر این پیام را می‌بینید، اعلان‌ها روی این دستگاه درست کار می‌کنند.',
                    icon: '/favicon.ico',
                    dir: 'rtl',
                    lang: 'fa',
                    tag: 'app-test'
                });
                return true;
            } catch (e) {
                return false;
            }
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
