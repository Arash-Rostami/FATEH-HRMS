import fancyboxMixin from '../mixins/fancybox.js';
import highlightMatchMixin from '../mixins/highlightMatch.js';
import persistentStateMixin from '../mixins/persistentState.js';

const BASE_ITEMS = [
    {id: 'admin-controller', href: '/admin', icon: 'admin_panel_settings', title: 'پنل مدیریت', sub: 'تنظیمات سیستمی', adminOnly: true, group: 'اصلی'},
    {id: 'dashboard-controller', href: '/dashboard', icon: 'home', title: 'داشبورد', sub: 'نمای کلی', group: 'اصلی'},
    {id: 'profile-controller', href: '/profile?tab=info', icon: 'person', title: 'پروفایل', sub: 'حساب و اطلاعات', module: 'profile', group: 'پروفایل'},
    {id: 'onboarding-controller', href: '/profile?tab=onboarding', icon: 'apartment', title: 'آنبوردینگ', sub: 'آشنایی با شرکت', module: 'profile', group: 'پروفایل'},
    {id: 'tasks-controller', href: '/tasks', icon: 'dashboard', title: 'برد وظایف', sub: 'فردی/تیمی', module: 'taskboard', group: 'فضای کاری'},
    {id: 'projects-controller', href: '/projects', icon: 'workspaces', title: 'پروژه‌ها', sub: 'فضای کاری تیمی', module: 'project', group: 'فضای کاری'},
    {id: 'tasksheet-controller', href: '/tasksheet', icon: 'assignment_turned_in', title: 'تسک‌شیت', sub: 'گزارش عملکرد شخصی', module: 'tasksheet', group: 'فضای کاری'},
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
    {id: 'stopwatch-controller', href: '-', icon: 'radio', title: 'آلارم', sub: 'تایمر دستی', action: 'stopwatch', group: 'ابزارها'},
    {id: 'radio-controller', href: '-', icon: 'radio', title: 'رادیو', sub: 'موسیقی آنلاین', action: 'radio', group: 'ابزارها'},
    {id: 'documents-controller', href: '/profile?tab=documents', icon: 'folder_open', title: 'مدارک و اسناد', sub: 'آپلود و دانلود', module: 'profile', group: 'پروفایل'},
    {id: 'credentials-controller', href: '/profile?tab=credentials', icon: 'verified_user', title: 'دسترس', sub: 'مجوزها و رمزها', module: 'profile', group: 'پروفایل'},
    {id: 'analytics-controller', href: '/analytics', icon: 'analytics', title: 'تحلیل‌های سازمانی', sub: 'آمار منابع انسانی', module: 'analytics', group: 'سازمان'},
];

const RECENT_KEY = 'menu_recent_items';
const RECENT_MAX = 8;
const LAST_ITEM_KEY = 'menu_last_item_id';
const VIEW_MODE_KEY = 'menu_view_mode';
const SORT_ALPHA_KEY = 'menu_sort_alpha';
const MQ_LARGE = '(min-width: 640px)';
const SWIPE_THRESHOLD = 50;

export default function menu(options = {}) {
    return {
        ...fancyboxMixin(),
        ...highlightMatchMixin(),
        ...persistentStateMixin(),
        menuOpen: false,
        items: [],
        current: 0,
        perPage: 12,
        search: '',
        focusIndex: null,
        recentIds: [],
        showRecentOnly: false,
        pinEditMode: false,
        viewMode: 'grid',
        sortAlpha: false,
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
            if (!this.sortAlpha && (!pins || pins.size === 0)) return this.items;

            const key = (this.sortAlpha ? '1|' : '0|') + this._pinsVersion;
            if (this._sortedKey === key) return this._sortedCache;

            const sorted = this.items.slice().sort((a, b) => {
                let pinDiff = 0;
                if (pins) {
                    const hasB = pins.has(b.id) ? 1 : 0;
                    const hasA = pins.has(a.id) ? 1 : 0;
                    pinDiff = hasB - hasA;
                }
                if (pinDiff !== 0) return pinDiff;
                return this.sortAlpha ? a.title.localeCompare(b.title, 'fa') : 0;
            });

            this._sortedKey = key;
            this._sortedCache = sorted;
            return sorted;
        },

        get groupedItems() {
            const visible = this.allVisibleItems;
            if (this._groupedSourceRef === visible) return this._groupedCache;

            const groupsMap = new Map();
            const len = visible.length;

            for (let i = 0; i < len; i++) {
                const item = visible[i];
                const label = item.group || 'سایر';
                let g = groupsMap.get(label);
                if (!g) {
                    g = { label, items: [] };
                    groupsMap.set(label, g);
                }
                g.items.push(item);
            }

            this._groupedSourceRef = visible;
            this._groupedCache = Array.from(groupsMap.values());
            return this._groupedCache;
        },

        get paginatedData() {
            const items = this.sortedItems;
            if (this._paginatedSourceRef === items && this._paginatedPerPage === this.perPage) {
                return this._paginatedCache;
            }

            const pages = [];
            const len = items.length;
            const pp = this.perPage;

            for (let i = 0; i < len; i += pp) {
                pages.push(items.slice(i, i + pp));
            }

            this._paginatedSourceRef = items;
            this._paginatedPerPage = pp;
            this._paginatedCache = pages;
            return pages;
        },

        _filteredItems() {
            if (this.showRecentOnly) {
                const recent = [];
                const rIds = this.recentIds;
                const rLen = rIds.length;
                const map = this._itemMap;

                for (let i = 0; i < rLen; i++) {
                    const item = map.get(rIds[i]);
                    if (item) recent.push(item);
                }
                return recent;
            }

            const q = this.search.trim().toLowerCase();
            if (!q) return null;

            const sorted = this.sortedItems;
            const filtered = [];
            const len = sorted.length;

            for (let i = 0; i < len; i++) {
                const item = sorted[i];
                if (item._searchKey.includes(q)) {
                    filtered.push(item);
                }
            }
            return filtered;
        },

        get activePages() {
            const filtered = this._filteredItems();
            return filtered !== null ? [filtered] : this.paginatedData;
        },

        get allVisibleItems() {
            const filtered = this._filteredItems();
            return filtered !== null ? filtered : this.sortedItems;
        },

        get activeIndex() {
            const q = this.search.trim();
            if (q !== '' || this.showRecentOnly) return 0;
            return this.current;
        },

        get currentFocusableItems() {
            if (this.viewMode === 'grid') {
                const pages = this.activePages;
                const active = this.activeIndex;
                return pages[active] || [];
            }
            return this.allVisibleItems;
        },

        get searchEl() {
            const mobile = this.$refs.searchInputMobile;
            return mobile && mobile.offsetParent !== null ? mobile : this.$refs.searchInput;
        },

        get notifiedCount() {
            let count = 0;
            const items = this.items;
            const len = items.length;

            for (let i = 0; i < len; i++) {
                if (items[i].hasNotification) count++;
            }
            return count;
        },

        _pageForItem(id) {
            if (id == null) return 0;

            const sorted = this.sortedItems;
            const len = sorted.length;
            let idx = -1;

            for (let i = 0; i < len; i++) {
                if (sorted[i].id === id) {
                    idx = i;
                    break;
                }
            }

            return idx === -1 ? 0 : Math.floor(idx / this.perPage);
        },

        listCols(count) {
            if (count > 12) return 3;
            if (count > 4) return 2;
            return 1;
        },

        pageHasNotification(pageIndex) {
            const page = this.paginatedData[pageIndex];
            if (!page) return false;

            const len = page.length;
            for (let i = 0; i < len; i++) {
                if (page[i].hasNotification) return true;
            }
            return false;
        },

        toggleTheme() {
            if (window.ThemeManager && window.ThemeManager.toggleMode) {
                window.ThemeManager.toggleMode();
            }
            this.isDark = !this.isDark;
        },

        toggleSortAlpha() {
            this.sortAlpha = !this.sortAlpha;
            this._saveState(SORT_ALPHA_KEY, this.sortAlpha);
            this.focusIndex = null;
        },

        togglePinEdit() {
            this.pinEditMode = !this.pinEditMode;
            this.focusIndex = null;
        },

        isPinned(item) {
            if (!item || !item.id) return false;
            return this.$store.pinned.isPinned(item.id, 'menu');
        },

        togglePin(item, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            if (!item || !item.id) return;

            const wasPinned = this.isPinned(item);
            this.$store.pinned.togglePin(item.id, 'menu');
            this._pinsVersion++;
            this.focusIndex = null;

            const q = this.search.trim();
            if (!wasPinned && q === '' && !this.showRecentOnly) {
                this.current = 0;
            }
        },

        recordRecent(item) {
            if (!item || !item.id) return;

            const id = item.id;
            const next = [id];
            const current = this.recentIds;
            const len = current.length;

            for (let i = 0; i < len; i++) {
                if (current[i] !== id) {
                    next.push(current[i]);
                    if (next.length === RECENT_MAX) break;
                }
            }

            this.recentIds = next;
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

        moveFocus(delta) {
            const items = this.currentFocusableItems;
            const len = items.length;
            if (len === 0) return;

            const q = this.search.trim();
            const canPage = this.viewMode === 'grid' && q === '' && !this.showRecentOnly;
            let pos = -1;

            const fid = this.focusIndex;
            for (let i = 0; i < len; i++) {
                if (items[i].id === fid) {
                    pos = i;
                    break;
                }
            }

            if (pos === -1) {
                const item = items[delta > 0 ? 0 : len - 1];
                this.focusIndex = item ? item.id : null;
                return;
            }

            pos += delta;

            if (pos < 0) {
                if (canPage && this.current > 0) {
                    this.prev();
                    this.$nextTick(() => {
                        const active = this.activeIndex;
                        const pages = this.activePages;
                        const next = pages[active] || [];
                        const last = next[next.length - 1];
                        this.focusIndex = last ? last.id : null;
                    });
                    return;
                }
                pos = 0;
            } else if (pos >= len) {
                const pData = this.paginatedData;
                if (canPage && this.current < pData.length - 1) {
                    this.next();
                    this.$nextTick(() => {
                        const active = this.activeIndex;
                        const pages = this.activePages;
                        const next = pages[active] || [];
                        const first = next[0];
                        this.focusIndex = first ? first.id : null;
                    });
                    return;
                }
                pos = len - 1;
            }

            const nextItem = items[pos];
            this.focusIndex = nextItem ? nextItem.id : null;
        },

        openFocused() {
            const items = this.currentFocusableItems;
            const len = items.length;
            const fid = this.focusIndex;

            for (let i = 0; i < len; i++) {
                if (items[i].id === fid) {
                    this.handleItemClick(items[i], { preventDefault() {}, ctrlKey: false, metaKey: false });
                    break;
                }
            }
        },

        openTopResult() {
            const item = this.currentFocusableItems[0];
            if (item) {
                this.handleItemClick(item, { preventDefault() {}, ctrlKey: false, metaKey: false });
            }
        },

        handleSwipe() {
            const d = this.touchEndX - this.touchStartX;
            if (Math.abs(d) < SWIPE_THRESHOLD) return;
            if (d < 0) {
                this.next();
            } else {
                this.prev();
            }
        },

        handleGlobalKeydown(e) {
            if (!this.menuOpen) return;

            const input = this.searchEl;
            const inSearch = document.activeElement === input;
            const key = e.key;

            if (key === 'Escape') return this.closeMenu();

            if (key === '/' && !inSearch) {
                e.preventDefault();
                if (input) input.focus();
                return;
            }

            if (key === 'Enter') {
                e.preventDefault();
                if (inSearch) {
                    this.openTopResult();
                } else {
                    this.openFocused();
                }
                return;
            }

            if (!inSearch && this.search === '' && key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                this.search = key;
                this.$nextTick(() => {
                    if (this.searchEl) this.searchEl.focus();
                });
                return;
            }

            if (inSearch) return;

            if (key === 'ArrowLeft') this.moveFocus(1);
            if (key === 'ArrowRight') this.moveFocus(-1);
        },

        toggleMenu() {
            this.menuOpen = !this.menuOpen;
            if (this.menuOpen) {
                this.perPage = this._mql.matches ? 12 : 8;
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
            const pData = this.paginatedData;
            if (this.current < pData.length - 1) this.current++;
        },

        updatePerPage(e) {
            const newPerPage = e.matches ? 12 : 8;
            if (this.perPage !== newPerPage) {
                this.perPage = newPerPage;
                this.current = 0;
            }
        },

        init() {
            const canAdmin = options.canAdmin === true;
            const disabledTypes = new Set(options.disabledReservationTypes || []);
            const menuState = options.menuState || {};

            const map = this._itemMap;
            const outItems = [];
            const len = BASE_ITEMS.length;

            for (let i = 0; i < len; i++) {
                const item = BASE_ITEMS[i];
                if (canAdmin || !item.adminOnly) {
                    const title = item.title || '';
                    const sub = item.sub || '';
                    const mod = item.module || '';

                    const parsedItem = {
                        id: item.id,
                        href: item.href,
                        icon: item.icon,
                        title: title,
                        sub: sub,
                        adminOnly: item.adminOnly,
                        group: item.group,
                        module: item.module,
                        resourceType: item.resourceType,
                        action: item.action,
                        disabled: !!item.resourceType && disabledTypes.has(item.resourceType),
                        hasNotification: !!menuState[item.id],
                        _searchKey: (title + ' ' + sub + ' ' + mod).toLowerCase()
                    };

                    outItems.push(parsedItem);
                    map.set(item.id, parsedItem);
                }
            }
            this.items = outItems;

            this.recentIds = this._loadState(RECENT_KEY, []);

            const savedView = this._loadState(VIEW_MODE_KEY, 'grid');
            if (savedView === 'grid' || savedView === 'list' || savedView === 'grouped') {
                this.viewMode = savedView;
            } else {
                this.viewMode = 'grid';
            }

            this.sortAlpha = !!this._loadState(SORT_ALPHA_KEY, false);

            if (window.ThemeManager && window.ThemeManager.getUserMode) {
                this.isDark = window.ThemeManager.getUserMode() === 'dark';
            }

            this.initFancybox();

            this._mql = window.matchMedia(MQ_LARGE);
            this.perPage = this._mql.matches ? 12 : 8;
            this._mediaListener = (e) => this.updatePerPage(e);
            this._mql.addEventListener('change', this._mediaListener);

            this.current = this._pageForItem(this._loadState(LAST_ITEM_KEY, null));

            this.$watch('search', (val) => {
                this.focusIndex = null;
                if (val !== '') this.showRecentOnly = false;
            });

            this.$watch('current', (val) => {
                const pData = this.paginatedData;
                const page = pData[val];
                this._saveState(LAST_ITEM_KEY, page && page[0] ? page[0].id : null);
            });

            this.$watch('viewMode', (val) => {
                this._saveState(VIEW_MODE_KEY, val);
                this.focusIndex = null;
            });
        },

        destroy() {
            if (this._mql && this._mediaListener) {
                this._mql.removeEventListener('change', this._mediaListener);
            }
        }
    };
}
