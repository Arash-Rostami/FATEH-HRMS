export default function menu() {
    return {
        menuOpen: false,
        items: [
            {id: 'dashboard-controller', href: '/dashboard', icon: 'home', title: 'داشبورد', sub: 'نمای کلی'},
            {id: 'profile-controller', href: '/profile', icon: 'person', title: 'پروفایل', sub: 'حساب و اطلاعات'},
            {id: 'tasks-controller', href: '/tasks', icon: 'dashboard', title: 'برد وظایف', sub: 'فردی/تیمی'},
            {id: 'calculator-controller', href: '-', icon: 'computer', title: 'ماشین حساب', sub: 'محاسبات شخصی', action: 'calculate'},
            {id: 'stopwatch-controller', href: '-', icon: 'alarm', title: 'آلارم', sub: 'تایمر دستی', action: 'stopwatch'},
            {id: 'dms-controller', href: '/dms', icon: 'folder_open', title: 'مدیریت اسناد', sub: 'سرویس'},
            {id: 'ths-controller', href: '/ths', icon: 'support_agent', title: 'تیکتینگ', sub: 'ثبت و پیگیری'},

            // New Reservation Navigation Buttons
            {id: 'reservation-seat', href: '/dashboard?tab=reservation&type=seat', icon: 'chair_alt', title: 'رزرو میز', sub: 'جایگاه اداری'},
            {id: 'reservation-spot', href: '/dashboard?tab=reservation&type=spot', icon: 'local_parking', title: 'رزرو پارکینگ', sub: 'جای پارک'},
            {id: 'reservation-car', href: '/dashboard?tab=reservation&type=car', icon: 'directions_car', title: 'رزرو خودرو', sub: 'ماشین شرکت'},
            {id: 'reservation-appointment', href: '/dashboard?tab=reservation&type=appointment', icon: 'event_available', title: 'رزرو ملاقات', sub: 'جلسه کاری'},

            {id: 'contacts-controller', href: '/contacts', icon: 'perm_contact_calendar', title: 'مخاطبین', sub: 'دفترچه تلفن'},
            {id: 'notifications-controller', href: '/notifications', icon: 'notifications_active', title: 'پیام‌ها', sub: 'اعلانات جدید'},
            {id: 'security-controller', href: '/security', icon: 'security', title: 'امنیت', sub: 'مجوزها و رمز'},
            {id: '#-controller', href: '#', icon: 'api', title: 'API', sub: 'اتصالات'},

        ],
        current: 0,
        perPage: 12,
        get pages() {
            return Math.max(1, Math.ceil(this.items.length / this.perPage));
        },
        pageItems(index) {
            const start = index * this.perPage;
            return this.items.filter(item => item.href !== '-').slice(start, start + this.perPage);
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
        clickItems() {
            return this.items.filter(item => item.action === 'calculate' || item.action === 'stopwatch');
        },
        init() {
            this.updatePerPage();
        }
    }
}
