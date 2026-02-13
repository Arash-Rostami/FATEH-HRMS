<aside
    class="hidden lg:flex w-[70px] flex-col items-center gap-2 pt-10 backdrop-blur-sm my-auto shrink-0 z-10 fixed top-1/5 right-1">

    <a href="{{ url('/') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">home</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">خانه</span>
    </a>

    <a href="{{ url('/dashboard') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">grid_view</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">داشبورد</span>
    </a>

    <a href="{{ url('/calendar') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">calendar_month</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">تقویم</span>
    </a>

    <a href="{{ url('/gallery') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">image</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">گالری</span>
    </a>

    <a href="{{ url('/share') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">share</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">اشتراک‌گذاری</span>
    </a>

    <a href="{{ url('/analytics') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">analytics</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">آمار</span>
    </a>

    <a href="{{ url('/profile') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">person</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">پروفایل</span>
    </a>

    <a href="{{ url('/help') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-xl
              bg-[var(--md-sys-color-primary)]
              text-[var(--md-sys-color-on-primary)]
              cursor-pointer transition-all duration-300 ease-out
              hover:bg-[var(--md-sys-color-primary-container)]
              hover:text-[var(--md-sys-color-on-primary-container)]
              hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30
              hover:-translate-y-0.5 active:scale-95">
        <span class="material-symbols-rounded text-[22px] transition-transform duration-300 group-hover:scale-110">help</span>
        <span class="absolute right-14 bg-[var(--md-sys-color-inverse-surface)] text-[var(--md-sys-color-inverse-on-surface)] text-[11px] px-2 py-1 rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">راهنما</span>
    </a>

</aside>
