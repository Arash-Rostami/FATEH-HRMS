import fancyboxMixin from '../mixins/fancybox.js';

const BASE_ITEMS = [
    {id: 'admin-controller', href: '/admin', icon: 'admin_panel_settings', title: 'پنل مدیریت', sub: 'تنظیمات سیستمی', adminOnly: true},
    {id: 'dashboard-controller', href: '/dashboard', icon: 'home', title: 'داشبورد', sub: 'نمای کلی'},
    {id: 'profile-controller', href: '/profile?tab=info', icon: 'person', title: 'پروفایل', sub: 'حساب و اطلاعات', module: 'profile'},
    {id: 'onboarding-controller', href: '/profile?tab=onboarding', icon: 'apartment', title: 'آنبوردینگ', sub: 'آشنایی با شرکت', module: 'profile'},
    {id: 'tasks-controller', href: '/tasks', icon: 'dashboard', title: 'برد وظایف', sub: 'فردی/تیمی', module: 'taskboard'},
    {id: 'projects-controller', href: '/projects', icon: 'workspaces', title: 'پروژه‌ها', sub: 'فضای کاری تیمی', module: 'project'},
    {id: 'tasksheet-controller', href: '/tasksheet', icon: 'assignment_turned_in', title: 'تسک‌شیت', sub: 'گزارش عملکرد شخصی', module: 'tasksheet'},
    {id: 'dms-controller', href: '/dms', icon: 'folder_open', title: 'مدیریت اسناد', sub: 'سرویس', module: 'dms'},
    {id: 'ths-controller', href: '/ths', icon: 'support_agent', title: 'تیکتینگ', sub: 'ثبت و پیگیری', module: 'ths'},
    {id: 'suggestion-controller', href: '/suggestion', icon: 'lightbulb', title: 'پیشنهادات', sub: 'کانون نقاط نظر سازمانی'},
    {id: 'reservation-seat', href: '/reservation?tab=seat', icon: 'chair_alt', title: 'رزرو میز', sub: 'جایگاه اداری', resourceType: 'seat', module: 'reservation'},
    {id: 'reservation-spot', href: '/reservation?tab=spot', icon: 'local_parking', title: 'رزرو پارکینگ', sub: 'جای پارک', resourceType: 'spot', module: 'reservation'},
    {id: 'reservation-car', href: '/reservation?tab=car', icon: 'directions_car', title: 'رزرو خودرو', sub: 'ماشین شرکت', resourceType: 'car', module: 'reservation'},
    {id: 'reservation-appointment', href: '/reservation?tab=meeting', icon: 'event_available', title: 'رزرو ملاقات', sub: 'جلسه کاری', resourceType: 'meeting', module: 'reservation'},
    {id: 'contacts-controller', href: '/contacts', icon: 'perm_contact_calendar', title: 'مخاطبین (پیام‌رسان)', sub: 'پیام‌رسان داخلی', module: 'contact'},
    {id: 'channels', href: '/channels', icon: 'campaign', title: 'کانال‌ها', sub: 'کانال‌های موضوعی', module: 'channel'},
    {id: 'ads-controller', href: '/ads', icon: 'work', title: 'فرصت‌های شغلی', sub: 'استخدامی'},
    {id: 'authority-controller', href: '/authority', icon: 'verified_user', title: 'اختیارات', sub: 'واحدهای سازمانی'},
    {id: 'energy-controller', href: '/energy', icon: 'energy', title: 'پرسشنامه انرژی', sub: 'ارزیابی فردی', module: 'energy'},
    {id: 'calculator-controller', href: '-', icon: 'computer', title: 'ماشین حساب', sub: 'محاسبات شخصی', action: 'calculate'},
    {id: 'stopwatch-controller', href: '-', icon: 'radio', title: 'آلارم', sub: 'تایمر دستی', action: 'stopwatch'},
    {id: 'radio-controller', href: '-', icon: 'radio', title: 'رادیو', sub: 'موسیقی آنلاین', action: 'radio'},
    {id: 'documents-controller', href: '/profile?tab=documents', icon: 'folder_open', title: 'مدارک و اسناد', sub: 'آپلود و دانلود', module: 'profile'},
    {id: 'credentials-controller', href: '/profile?tab=credentials', icon: 'verified_user', title: 'دسترس', sub: 'مجوزها و رمزها', module: 'profile'},
    {id: 'analytics-controller', href: '/analytics', icon: 'analytics', title: 'تحلیل‌های سازمانی', sub: 'آمار منابع انسانی', module: 'analytics'},
];

export default function menu(options = {}) {
    return {
        ...fancyboxMixin(),
        menuOpen: false,
        items: [],
        current: 0,
        perPage: 12,
        _windowResizeListener: null,

        get paginatedData() {
            const pages = [];
            const items = this.items;
            const perPage = this.perPage;

            for (let i = 0, len = items.length; i < len; i += perPage) {
                pages.push(items.slice(i, i + perPage));
            }

            return pages;
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
            this.closeMenu();
        },

        toggleMenu() {
            this.menuOpen = !this.menuOpen;
        },

        closeMenu() {
            this.menuOpen = false;
        },

        prev() {
            if (this.current > 0) this.current--;
        },

        next() {
            if (this.current < this.paginatedData.length - 1) this.current++;
        },

        updatePerPage() {
            const newPerPage = window.matchMedia('(min-width: 640px)').matches ? 12 : 8;

            if (this.perPage !== newPerPage) {
                this.perPage = newPerPage;
                this.current = 0;
            }
        },

        init() {
            const canAdmin = !!options.canAdmin;
            const disabledTypes = options.disabledReservationTypes || [];
            const processedItems = [];

            for (let i = 0, len = BASE_ITEMS.length; i < len; i++) {
                const item = BASE_ITEMS[i];

                if (!canAdmin && item.adminOnly) continue;

                processedItems.push({
                    ...item,
                    disabled: !!item.resourceType && disabledTypes.includes(item.resourceType),
                });
            }

            this.items = processedItems;
            this.initFancybox();

            this.updatePerPage();

            this._windowResizeListener = () => this.updatePerPage();
            window.addEventListener('resize', this._windowResizeListener);
        },

        destroy() {
            if (this._windowResizeListener) {
                window.removeEventListener('resize', this._windowResizeListener);
                this._windowResizeListener = null;
            }
        }
    }
}
