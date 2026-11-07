import fancyboxMixin from '../mixins/fancybox.js';
import highlightMatchMixin from '../mixins/highlightMatch.js';
import persistentStateMixin from '../mixins/persistentState.js';

const withSearchKey = (i) => ({...i, _searchKey: `${i.title || ''} ${i.sub || ''} ${i.module || ''}`.toLowerCase()});

const BASE_ITEMS = [
    {id: 'admin-controller', href: '/admin', icon: 'admin_panel_settings', title: 'پنل مدیریت', sub: 'تنظیمات سیستمی', adminOnly: true, group: 'اصلی'},
    {id: 'dashboard-controller', href: '/dashboard', icon: 'home', title: 'داشبورد', sub: 'نمای کلی', group: 'اصلی'},
    {id: 'profile-controller', href: '/profile?tab=info', icon: 'person', title: 'پروفایل', sub: 'حساب و اطلاعات', module: 'profile', group: 'پروفایل'},
    {id: 'onboarding-controller', href: '/profile?tab=onboarding', icon: 'apartment', title: 'آنبوردینگ', sub: 'آشنایی با شرکت', module: 'profile', group: 'پروفایل'},
    {id: 'tasks-controller', href: '/tasks', icon: 'dashboard', title: 'برد وظایف', sub: 'فردی/تیمی', module: 'taskboard', group: 'فضای کاری'},
    {id: 'projects-controller', href: '/projects', icon: 'workspaces', title: 'پروژه‌ها', sub: 'فضای کاری تیمی', module: 'project', group: 'فضای کاری'},
    {id: 'tasksheet-controller', href: '/tasksheet', icon: 'assignment_turned_in', title: 'تسک‌شیت', sub: 'گزارش عملکرد شخصی', module: 'tasksheet', group: 'فضای کاری'},
    {id: 'ai-report-controller', href: '/ai-report', icon: 'article', title: 'گزارش ساز هوشمند', sub: 'تولید گزارش با هوش مصنوعی', module: 'ai-report', group: 'فضای کاری', ai: true},
    {id: 'ai-assistant', href: '-', icon: 'auto_awesome', title: 'دستیار هوش مصنوعی', sub: 'گفتگو با هوش مصنوعی', module: 'ai-assistant', action: 'ai-assistant', group: 'فضای کاری', ai: true, requires: 'aiAssistant'},
    {id: 'dms-controller', href: '/dms', icon: 'folder_open', title: 'مدیریت اسناد', sub: 'سرویس', module: 'dms', group: 'خدمات'},
    {id: 'ths-controller', href: '/ths', icon: 'support_agent', title: 'تیکتینگ', sub: 'ثبت و پیگیری', module: 'ths', group: 'خدمات'},
    {id: 'suggestion-controller', href: '/suggestion', icon: 'lightbulb', title: 'پیشنهادات', sub: 'کانون نقاط نظر سازمانی', group: 'سازمان'},
    {id: 'reservation-seat', href: '/reservation?tab=seat', icon: 'chair_alt', title: 'رزرو میز', sub: 'جایگاه اداری', resourceType: 'seat', module: 'reservation', group: 'رزرواسیون'},
    {id: 'reservation-spot', href: '/reservation?tab=spot', icon: 'local_parking', title: 'رزرو پارکینگ', sub: 'جای پارک', resourceType: 'spot', module: 'reservation', group: 'رزرواسیون'},
    {id: 'reservation-car', href: '/reservation?tab=car', icon: 'directions_car', title: 'رزرو خودرو', sub: 'ماشین شرکت', resourceType: 'car', module: 'reservation', group: 'رزرواسیون'},
    {id: 'reservation-appointment', href: '/reservation?tab=meeting', icon: 'event_available', title: 'رزرو ملاقات', sub: 'جلسه کاری', resourceType: 'meeting', module: 'reservation', group: 'رزرواسیون'},
    {id: 'contacts-controller', href: '/contacts', icon: 'perm_contact_calendar', title: 'مخاطبین (پیام‌رسان)', sub: 'پیام‌رسان داخلی', module: 'contact', group: 'ارتباطات'},
    {id: 'channels', href: '/channels', icon: 'campaign', title: 'گروه‌ها', sub: 'گروه‌های موضوعی', module: 'channel', group: 'ارتباطات'},
    {id: 'ads-controller', href: '/ads', icon: 'work', title: 'فرصت‌های شغلی', sub: 'استخدامی', group: 'سازمان'},
    {id: 'authority-controller', href: '/authority', icon: 'verified_user', title: 'اختیارات', sub: 'واحدهای سازمانی', group: 'سازمان'},
    {id: 'energy-controller', href: '/energy', icon: 'energy', title: 'پرسشنامه انرژی', sub: 'ارزیابی فردی', module: 'energy', group: 'سازمان'},
    {id: 'calculator-controller', href: '-', icon: 'computer', title: 'ماشین حساب', sub: 'محاسبات شخصی', action: 'calculate', group: 'ابزارها'},
    {id: 'stopwatch-controller', href: '-', icon: 'timer', title: 'آلارم', sub: 'تایمر دستی', action: 'stopwatch', group: 'ابزارها'},
    {id: 'radio-controller', href: '-', icon: 'radio', title: 'رادیو', sub: 'موسیقی آنلاین', action: 'radio', group: 'ابزارها'},
    {id: 'documents-controller', href: '/profile?tab=documents', icon: 'folder_open', title: 'مدارک و اسناد', sub: 'آپلود و دانلود', module: 'profile', group: 'پروفایل'},
    {id: 'credentials-controller', href: '/profile?tab=credentials', icon: 'verified_user', title: 'دسترس', sub: 'مجوزها و رمزها', module: 'profile', group: 'پروفایل'},
    {id: 'analytics-controller', href: '/analytics', icon: 'analytics', title: 'تحلیل‌های سازمانی', sub: 'آمار منابع انسانی', module: 'analytics', group: 'سازمان'},
].map(withSearchKey);

const FOLD_MAP = {'ي': 'ی', 'ك': 'ک', '\u200c': ''};

function foldSearch(value) {
    return String(value || '').trim().toLowerCase().replace(/[يك\u200c]/g, (c) => FOLD_MAP[c]);
}

const HOME_TABS = [
    {id: 'home', label: 'خانه', alias: 'home'},
    {id: 'post', label: 'اعلانات', alias: 'post announce', off: ['announce', 'post']},
    {id: 'feed', label: 'اخبار', alias: 'feed news'},
    {id: 'calendar', label: 'تقویم', alias: 'calendar'},
    {id: 'status', label: 'وضعیت', alias: 'status'},
    {id: 'gallery', label: 'گالری', alias: 'gallery'},
    {id: 'reports', label: 'گزارش‌ها', alias: 'report reports'},
    {id: 'links', label: 'لینک‌ها', alias: 'link links'},
    {id: 'faqs', label: 'پرسش‌های متداول', alias: 'faq faqs', off: ['faq', 'faqs']},
].map((tab) => ({...tab, _searchKey: foldSearch(`${tab.label} ${tab.alias}`)}));

const tabIsOff = (tab, off) => (tab.off || [tab.id]).some((key) => off.has(key));

const RECENT_KEY = 'menu_recent_items';
const RECENT_MAX = 8;
const LAST_ITEM_KEY = 'menu_last_item_id';
const VIEW_MODE_KEY = 'menu_view_mode';
const SORT_ALPHA_KEY = 'menu_sort_alpha';
const DECLUTTER_KEY = 'menu_declutter';
const MQ_LARGE = '(min-width: 640px)';
const SWIPE_THRESHOLD = 50;
const VIEW_MODES = new Set(['grid', 'list', 'grouped']);
const COLLATOR = new Intl.Collator('fa');
const SYNTHETIC_EVENT = Object.freeze({preventDefault() {}, ctrlKey: false, metaKey: false});
const perPageFor = (large) => (large ? 12 : 8);

export default function menu(options = {}) {
    return {
        ...fancyboxMixin(),
        ...highlightMatchMixin(),
        ...persistentStateMixin(),
        menuOpen: false,
        items: [],
        homeTabs: [],
        current: 0,
        perPage: 12,
        search: '',
        focusIndex: null,
        recentIds: [],
        showRecentOnly: false,
        pinEditMode: false,
        viewMode: 'grid',
        sortAlpha: false,
        declutter: false,
        isDark: false,
        touchStartX: 0,
        touchEndX: 0,

        _itemMap: new Map(),
        _pinsVersion: 0,

        _sortedKey: '',
        _sortedCache: null,
        _groupedSourceRef: null,
        _groupedCache: null,
        _paginatedSourceRef: null,
        _paginatedPerPage: null,
        _paginatedCache: null,

        _mql: null,
        _mediaListener: null,

        get sortedItems() {
            const pins = this.$store.pinned.sets.menu;
            const alpha = this.sortAlpha;
            if (!alpha && (!pins || pins.size === 0)) return this.items;

            const key = (alpha ? '1|' : '0|') + this._pinsVersion;
            if (this._sortedKey === key) return this._sortedCache;

            const pinned = (item) => (pins && pins.has(item.id) ? 1 : 0);
            this._sortedCache = this.items.slice().sort((a, b) =>
                pinned(b) - pinned(a) || (alpha ? COLLATOR.compare(a.title, b.title) : 0)
            );
            this._sortedKey = key;
            return this._sortedCache;
        },

        get groupedItems() {
            const visible = this.allVisibleItems;
            if (this._groupedSourceRef === visible) return this._groupedCache;

            const groups = new Map();
            for (const item of visible) {
                const label = item.group || 'سایر';
                const group = groups.get(label);
                if (group) group.items.push(item);
                else groups.set(label, {label, items: [item]});
            }

            this._groupedSourceRef = visible;
            this._groupedCache = [...groups.values()];
            return this._groupedCache;
        },

        get paginatedData() {
            const items = this.sortedItems;
            const pp = this.perPage;
            if (this._paginatedSourceRef === items && this._paginatedPerPage === pp) return this._paginatedCache;

            const pages = [];
            for (let i = 0; i < items.length; i += pp) pages.push(items.slice(i, i + pp));

            this._paginatedSourceRef = items;
            this._paginatedPerPage = pp;
            this._paginatedCache = pages;
            return pages;
        },

        _filteredItems() {
            if (this.showRecentOnly) {
                return this.recentIds.map((id) => this._itemMap.get(id)).filter(Boolean);
            }

            const q = this.search.trim().toLowerCase();
            if (!q) return null;

            return this.sortedItems.filter((item) => item._searchKey.includes(q));
        },

        get activePages() {
            const filtered = this._filteredItems();
            return filtered !== null ? [filtered] : this.paginatedData;
        },

        get allVisibleItems() {
            return this._filteredItems() ?? this.sortedItems;
        },

        get tabHints() {
            const q = foldSearch(this.search);
            if (!q || this.showRecentOnly) return [];
            return this.homeTabs.filter((tab) => tab._searchKey.includes(q));
        },

        get tabHintText() {
            const names = this.tabHints.map((tab) => tab.label).join('، ');
            if (!names) return '';
            return names + ' از قبل در صفحهٔ خانه فهرست شده. نوار سمت راست را ببینید؛ در موبایل، نوار پایین.';
        },

        get activeIndex() {
            return this.search.trim() !== '' || this.showRecentOnly ? 0 : this.current;
        },

        get currentFocusableItems() {
            return this.viewMode === 'grid'
                ? this.activePages[this.activeIndex] || []
                : this.allVisibleItems;
        },

        get searchEl() {
            const mobile = this.$refs.searchInputMobile;
            return mobile && mobile.offsetParent !== null ? mobile : this.$refs.searchInput;
        },

        get notifiedCount() {
            return this.items.reduce((count, item) => count + (item.hasNotification ? 1 : 0), 0);
        },

        _pageForItem(id) {
            if (id == null) return 0;
            const idx = this.sortedItems.findIndex((item) => item.id === id);
            return idx === -1 ? 0 : Math.floor(idx / this.perPage);
        },

        listCols(count) {
            return count > 12 ? 3 : count > 4 ? 2 : 1;
        },

        pageHasNotification(pageIndex) {
            return !!this.paginatedData[pageIndex]?.some((item) => item.hasNotification);
        },

        toggleTheme() {
            window.ThemeManager?.toggleMode?.();
            this.isDark = !this.isDark;
        },

        toggleSortAlpha() {
            this.sortAlpha = !this.sortAlpha;
            this._saveState(SORT_ALPHA_KEY, this.sortAlpha);
            this.focusIndex = null;
        },

        toggleDeclutter() {
            this.declutter = !this.declutter;
            document.documentElement.classList.toggle('declutter', this.declutter);
            this._saveState(DECLUTTER_KEY, this.declutter);
            window.dispatchEvent(new CustomEvent('declutter', {detail: {on: this.declutter}}));
        },

        togglePinEdit() {
            this.pinEditMode = !this.pinEditMode;
            this.focusIndex = null;
        },

        isPinned(item) {
            return item?.id ? this.$store.pinned.isPinned(item.id, 'menu') : false;
        },

        togglePin(item, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            if (!item?.id) return;

            const wasPinned = this.isPinned(item);
            this.$store.pinned.togglePin(item.id, 'menu');
            this._pinsVersion++;
            this.focusIndex = null;

            if (!wasPinned && this.search.trim() === '' && !this.showRecentOnly) {
                this.current = 0;
            }
        },

        recordRecent(item) {
            const id = item?.id;
            if (!id) return;

            this.recentIds = [id, ...this.recentIds.filter((r) => r !== id)].slice(0, RECENT_MAX);
            this._saveState(RECENT_KEY, this.recentIds);
        },

        toggleRecentOnly() {
            this.showRecentOnly = !this.showRecentOnly;
            this.search = '';
            this.current = 0;
            this.focusIndex = null;
        },

        handleItemClick(item, event) {
            if (item.disabled) {
                event.preventDefault();
                return;
            }
            if (item.href === '-') {
                event.preventDefault();
                if (item.action) this.$dispatch(item.action);
            }
            this.recordRecent(item);
            if (event.ctrlKey || event.metaKey) return;
            this.closeMenu();
        },

        _focusPageEdge(last) {
            this.$nextTick(() => {
                const page = this.currentFocusableItems;
                this.focusIndex = (last ? page[page.length - 1] : page[0])?.id ?? null;
            });
        },

        moveFocus(delta) {
            const items = this.currentFocusableItems;
            const len = items.length;
            if (len === 0) return;

            const canPage = this.viewMode === 'grid' && this.search.trim() === '' && !this.showRecentOnly;
            const pos = items.findIndex((item) => item.id === this.focusIndex);

            if (pos === -1) {
                this.focusIndex = items[delta > 0 ? 0 : len - 1].id;
                return;
            }

            const target = pos + delta;

            if (target < 0 && canPage && this.current > 0) {
                this.prev();
                this._focusPageEdge(true);
                return;
            }

            if (target >= len && canPage && this.current < this.paginatedData.length - 1) {
                this.next();
                this._focusPageEdge(false);
                return;
            }

            this.focusIndex = items[Math.min(Math.max(target, 0), len - 1)].id;
        },

        openFocused() {
            const item = this.currentFocusableItems.find((i) => i.id === this.focusIndex);
            if (item) this.handleItemClick(item, SYNTHETIC_EVENT);
        },

        openTopResult() {
            const item = this.currentFocusableItems[0];
            if (item) this.handleItemClick(item, SYNTHETIC_EVENT);
        },

        handleSwipe() {
            const d = this.touchEndX - this.touchStartX;
            if (Math.abs(d) < SWIPE_THRESHOLD) return;
            if (d < 0) this.next();
            else this.prev();
        },

        handleGlobalKeydown(e) {
            if (!this.menuOpen) return;

            const input = this.searchEl;
            const inSearch = document.activeElement === input;
            const {key} = e;

            if (key === 'Escape') return this.closeMenu();

            if (key === '/' && !inSearch) {
                e.preventDefault();
                input?.focus();
                return;
            }

            if (key === 'Enter') {
                e.preventDefault();
                if (inSearch) this.openTopResult();
                else this.openFocused();
                return;
            }

            if (inSearch) return;

            if (this.search === '' && key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                this.search = key;
                this.$nextTick(() => this.searchEl?.focus());
                return;
            }

            if (key === 'ArrowLeft' || key === 'ArrowRight') {
                e.stopImmediatePropagation();
                this.moveFocus(key === 'ArrowLeft' ? 1 : -1);
            }
        },

        toggleMenu() {
            this.menuOpen = !this.menuOpen;
            if (this.menuOpen) {
                this.perPage = perPageFor(this._mql.matches);
                this.current = this._pageForItem(this._loadState(LAST_ITEM_KEY, null));
            }
        },

        closeMenu() {
            this.menuOpen = false;
            this.search = '';
            this.showRecentOnly = false;
            this.pinEditMode = false;
            this.focusIndex = null;
        },

        prev() {
            if (this.current > 0) this.current--;
        },

        next() {
            if (this.current < this.paginatedData.length - 1) this.current++;
        },

        updatePerPage(e) {
            const perPage = perPageFor(e.matches);
            if (this.perPage !== perPage) {
                this.perPage = perPage;
                this.current = 0;
            }
        },

        init() {
            const canAdmin = options.canAdmin === true;
            const disabledTypes = new Set(options.disabledReservationTypes || []);
            const off = new Set(options.off || []);
            const menuState = options.menuState || {};

            const items = [];
            for (const base of BASE_ITEMS) {
                if ((!base.adminOnly || canAdmin) && !off.has(base.module) && !off.has(base.id.split('-')[0]) && (!base.requires || options[base.requires] === true)) {
                    const {id} = base;
                    const item = {
                        ...base,
                        disabled: !!base.resourceType && disabledTypes.has(base.resourceType),
                        get hasNotification() {
                            return globalThis.Alpine?.store('menuBadges')?.on(id, menuState[id]) ?? !!menuState[id];
                        },
                    };
                    items.push(item);
                    this._itemMap.set(id, item);
                }
            }
            this.items = items;

            this.homeTabs = HOME_TABS
                .filter((tab) => !tabIsOff(tab, off))
                .map(({id, label, _searchKey}) => ({id, label, _searchKey}));

            this.recentIds = this._loadState(RECENT_KEY, []);

            const savedView = this._loadState(VIEW_MODE_KEY, 'grid');
            this.viewMode = VIEW_MODES.has(savedView) ? savedView : 'grid';

            this.sortAlpha = !!this._loadState(SORT_ALPHA_KEY, false);
            this.declutter = !!this._loadState(DECLUTTER_KEY, false);
            this._declutterListener = (e) => { this.declutter = !!e?.detail?.on; };
            window.addEventListener('declutter', this._declutterListener);

            if (window.ThemeManager?.getUserMode) {
                this.isDark = window.ThemeManager.getUserMode() === 'dark';
            }

            this.initFancybox();

            this._mql = window.matchMedia(MQ_LARGE);
            this.perPage = perPageFor(this._mql.matches);
            this._mediaListener = (e) => this.updatePerPage(e);
            this._mql.addEventListener('change', this._mediaListener);

            this.current = this._pageForItem(this._loadState(LAST_ITEM_KEY, null));

            this.$watch('search', (val) => {
                this.focusIndex = null;
                if (val !== '') this.showRecentOnly = false;
            });

            this.$watch('current', (val) => {
                this._saveState(LAST_ITEM_KEY, this.paginatedData[val]?.[0]?.id ?? null);
            });

            this.$watch('viewMode', (val) => {
                this._saveState(VIEW_MODE_KEY, val);
                this.focusIndex = null;
            });
        },

        destroy() {
            if (this._declutterListener) window.removeEventListener('declutter', this._declutterListener);
            if (this._mql && this._mediaListener) this._mql.removeEventListener('change', this._mediaListener);
        },
    };
}
