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
    {id: 'channels', href: '/channels', icon: 'campaign', title: 'کانال‌ها', sub: 'کانال‌های موضوعی', module: 'channel', group: 'ارتباطات'},
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
const CURRENT_PAGE_KEY = 'menu_current_page';
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
            if (!this.sortAlpha && pins.size === 0) return this.items;

            const key = `${this.sortAlpha ? 1 : 0}|${this._pinsVersion}`;
            if (this._sortedKey === key) return this._sortedCache;

            const sorted = [...this.items].sort((a, b) => {
                const pinDiff = pins.has(b.id) - pins.has(a.id);
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

            for (let i = 0, len = visible.length; i < len; i++) {
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
            for (let i = 0, len = items.length; i < len; i += this.perPage) {
                pages.push(items.slice(i, i + this.perPage));
            }

            this._paginatedSourceRef = items;
            this._paginatedPerPage = this.perPage;
            this._paginatedCache = pages;
            return pages;
        },

        _filteredItems() {
            if (this.showRecentOnly) {
                return this.recentIds.map((id) => this._itemMap.get(id)).filter(Boolean);
            }
            const q = this.search.trim().toLowerCase();
            return q ? this.sortedItems.filter((i) => i._searchKey.includes(q)) : null;
        },

        get activePages() {
            const filtered = this._filteredItems();
            return filtered !== null ? [filtered] : this.paginatedData;
        },

        get allVisibleItems() {
            return this._filteredItems() ?? this.sortedItems;
        },

        get activeIndex() {
            return (this.search.trim() || this.showRecentOnly) ? 0 : this.current;
        },

        get currentFocusableItems() {
            return this.viewMode === 'grid'
                ? (this.activePages[this.activeIndex] || [])
                : this.allVisibleItems;
        },

        get notifiedCount() {
            return this.items.filter((i) => i.hasNotification).length;
        },

        listCols(count) {
            if (count > 12) return 3;
            if (count > 4) return 2;
            return 1;
        },

        pageHasNotification(pageIndex) {
            const page = this.paginatedData[pageIndex];
            return !!page && page.some((i) => i.hasNotification);
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

        togglePinEdit() {
            this.pinEditMode = !this.pinEditMode;
            this.focusIndex = null;
        },

        isPinned(item) {
            return this.$store.pinned.isPinned(item?.id, 'menu');
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

            if (!wasPinned && !this.search.trim() && !this.showRecentOnly) {
                this.current = 0;
            }
        },

        recordRecent(item) {
            if (!item?.id) return;
            this.recentIds = [item.id, ...this.recentIds.filter((id) => id !== item.id)].slice(0, RECENT_MAX);
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
            if (!len) return;

            const canPage = this.viewMode === 'grid' && !this.search.trim() && !this.showRecentOnly;
            let pos = items.findIndex((i) => i.id === this.focusIndex);

            if (pos === -1) {
                this.focusIndex = items[delta > 0 ? 0 : len - 1]?.id ?? null;
                return;
            }

            pos += delta;

            if (pos < 0) {
                if (canPage && this.current > 0) {
                    this.prev();
                    this.$nextTick(() => {
                        const next = this.activePages[this.activeIndex] || [];
                        this.focusIndex = next[next.length - 1]?.id ?? null;
                    });
                    return;
                }
                pos = 0;
            } else if (pos >= len) {
                if (canPage && this.current < this.paginatedData.length - 1) {
                    this.next();
                    this.$nextTick(() => {
                        const next = this.activePages[this.activeIndex] || [];
                        this.focusIndex = next[0]?.id ?? null;
                    });
                    return;
                }
                pos = len - 1;
            }

            this.focusIndex = items[pos]?.id ?? null;
        },

        openFocused() {
            const item = this.currentFocusableItems.find((i) => i.id === this.focusIndex);
            if (item) this.handleItemClick(item, { preventDefault() {}, ctrlKey: false, metaKey: false });
        },

        openTopResult() {
            const item = this.currentFocusableItems[0];
            if (item) this.handleItemClick(item, { preventDefault() {}, ctrlKey: false, metaKey: false });
        },

        handleSwipe() {
            const d = this.touchEndX - this.touchStartX;
            if (Math.abs(d) < SWIPE_THRESHOLD) return;
            d < 0 ? this.next() : this.prev();
        },

        handleGlobalKeydown(e) {
            if (!this.menuOpen) return;

            const inSearch = document.activeElement === this.$refs.searchInput;
            const key = e.key;

            if (key === 'Escape') return this.closeMenu();

            if (key === '/' && !inSearch) {
                e.preventDefault();
                this.$refs.searchInput?.focus();
                return;
            }

            if (key === 'Enter') {
                e.preventDefault();
                inSearch ? this.openTopResult() : this.openFocused();
                return;
            }

            if (!inSearch && !this.search && key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                e.preventDefault();
                this.search = key;
                this.$nextTick(() => this.$refs.searchInput?.focus());
                return;
            }

            if (inSearch) return;

            if (key === 'ArrowLeft') this.moveFocus(1);
            if (key === 'ArrowRight') this.moveFocus(-1);
        },

        toggleMenu() {
            this.menuOpen = !this.menuOpen;
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
            const newPerPage = e.matches ? 12 : 8;
            if (this.perPage !== newPerPage) {
                this.perPage = newPerPage;
                this.current = 0;
            }
        },

        init() {
            const { canAdmin, disabledReservationTypes = [], menuState = {} } = options;
            const disabledTypes = new Set(disabledReservationTypes);

            this.items = BASE_ITEMS.reduce((acc, item) => {
                if (canAdmin || !item.adminOnly) {
                    const parsedItem = {
                        ...item,
                        disabled: !!item.resourceType && disabledTypes.has(item.resourceType),
                        hasNotification: !!menuState[item.id],
                        _searchKey: `${item.title || ''} ${item.sub || ''} ${item.module || ''}`.toLowerCase()
                    };
                    acc.push(parsedItem);
                    this._itemMap.set(item.id, parsedItem);
                }
                return acc;
            }, []);

            this.recentIds = this._loadState(RECENT_KEY, []);
            const savedView = this._loadState(VIEW_MODE_KEY, 'grid');
            this.viewMode = ['grid', 'list', 'grouped'].includes(savedView) ? savedView : 'grid';
            this.sortAlpha = !!this._loadState(SORT_ALPHA_KEY, false);
            this.isDark = window.ThemeManager?.getUserMode?.() === 'dark';
            this.initFancybox();

            this._mql = window.matchMedia(MQ_LARGE);
            this.perPage = this._mql.matches ? 12 : 8;
            this._mediaListener = (e) => this.updatePerPage(e);
            this._mql.addEventListener('change', this._mediaListener);

            const savedPage = this._loadState(CURRENT_PAGE_KEY, 0);
            const maxPage = Math.max(0, Math.ceil(this.items.length / this.perPage) - 1);
            this.current = Math.min(Math.max(savedPage, 0), maxPage);

            this.$watch('search', (val) => {
                this.focusIndex = null;
                if (val) this.showRecentOnly = false;
            });
            this.$watch('current', (val) => this._saveState(CURRENT_PAGE_KEY, val));
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
