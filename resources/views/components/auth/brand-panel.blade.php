<div
    class="relative hidden lg:flex lg:w-[40%] h-full min-h-screen flex-col justify-end px-12 pb-24 order-1 group cursor-default animate-slide-in-right animate-delay-1750">
    <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-700 ease-out">
        <h1 class="text-6xl font-black text-white mb-4 drop-shadow-2xl tracking-tight opacity-90 group-hover:opacity-100 transition-opacity duration-500"
            title="{{ config('app.name_en') }}">
            {{ config('app.name') }}
        </h1>
        <h2 class="text-3xl font-bold text-[#FF7F6E] mb-3 drop-shadow-lg"
            title="{{ config('app.slogan_en') }}">
            {{ config('app.slogan') }}
        </h2>
        <p class="text-gray-200 text-base font-medium leading-relaxed max-w-md bg-black/20 p-4 rounded-xl border border-white/10 opacity-80 group-hover:opacity-100 transition-all duration-500"
           title="{{ config('app.organization_name_en') }}">
            {{ config('app.organization_name') }}
        </p>
        <x-ui.social-links size="w-9 h-9" icon-size="w-[18px] h-[18px]"
                            link-class="text-white/70 hover:text-white hover:bg-white/10"
                            class="mt-4 opacity-80 group-hover:opacity-100 transition-opacity duration-500"/>
    </div>
</div>
