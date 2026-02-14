export default function menu() {
    return {
        menuOpen: false,
        items: [
            {href: '/settings/profile', icon: 'person', title: 'پروفایل', sub: 'حساب و اطلاعات'},
            {href: '/settings/preferences', icon: 'tune', title: 'تنظیمات', sub: 'شخصی‌سازی'},
            {href: '/tools/export', icon: 'download', title: 'خروجی', sub: 'دریافت داده‌ها'},
            {href: '/tools/import', icon: 'upload', title: 'ورودی', sub: 'بارگذاری فایل'},
            {href: '/dashboard', icon: 'dashboard', title: 'داشبورد', sub: 'نمای کلی'},
            {href: '/reports', icon: 'bar_chart', title: 'گزارشات', sub: 'تحلیل آماری'},
            {href: '/projects', icon: 'folder_open', title: 'پروژه‌ها', sub: 'مدیریت کارها'},
            {href: '/contacts', icon: 'perm_contact_calendar', title: 'مخاطبین', sub: 'دفترچه تلفن'},
            {href: '/notifications', icon: 'notifications_active', title: 'پیام‌ها', sub: 'اعلانات جدید'},
            {href: '/help', icon: 'support_agent', title: 'پشتیبانی', sub: 'مرکز راهنما'},
            {href: '/security', icon: 'security', title: 'امنیت', sub: 'مجوزها و رمز'},
            {href: '/logout', icon: 'power_settings_new', title: 'خروج', sub: 'پایان نشست'},
            {href: '#', icon: 'api', title: 'API', sub: 'اتصالات'},
            {href: '#', icon: 'history', title: 'تاریخچه', sub: 'لاگ سیستم'},
            {href: '#', icon: 'cloud', title: 'فضای ابری', sub: 'مدیریت فایل'}
        ],
        current: 0,
        perPage: 12,
        get pages() {
            return Math.max(1, Math.ceil(this.items.length / this.perPage));
        },
        pageItems(index) {
            const start = index * this.perPage;
            return this.items.slice(start, start + this.perPage);
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
            if (this.current < this.pages - 1) this.current++;
        },
        updatePerPage() {
            let newPerPage = 12;
            if (window.matchMedia('(min-width:1024px)').matches) newPerPage = 12;
            else if (window.matchMedia('(min-width:640px)').matches) newPerPage = 12;
            else newPerPage = 8;

            if (this.perPage !== newPerPage) {
                this.perPage = newPerPage;
                this.current = 0;
            }
        },
        init() {
            this.updatePerPage();
        }
    }
}
