<div class="hidden md:flex md:fixed md:top-8 md:left-8 z-30 items-center gap-3 px-2 py-1.5 rounded-2xl
            bg-white/70 dark:bg-zinc-900/70 border border-white/20 dark:border-white/10
            shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5 animate-slide-in-left animate-delay-1500"
     style="backdrop-filter: blur(16px);">

    <img src="{{ asset(config('app.company_logo', 'build/assets/img/logo.svg')) }}"
         alt="{{ config('app.organization_name_en') }}"
         title="{{ config('app.organization_name_en') }}"
         class="h-12 w-auto transition-transform duration-300 hover:scale-105"
         fetchpriority="low"
         decoding="async">
</div>
