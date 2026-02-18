<aside class="w-full lg:w-1/3 xl:w-2/5 flex-shrink-0 max-h-[70vh] flex flex-col overflow-hidden">
    <div class="sticky top-0 z-10 h-full flex flex-col">
        <div class="flex items-center gap-2 mb-3 px-1 shrink-0">
            <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">keep</span>
            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">ویژه</h3>
        </div>

        @if($this->pins->isNotEmpty())
            @foreach($this->pins as $pin)
                <div
                    class="relative group cursor-pointer rounded-[32px] overflow-hidden bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-[1.01] flex-grow flex flex-col"
                    wire:click="selectPost({{ $pin->id }})"
                >
                    <div class="relative h-64 lg:h-80 xl:h-96 w-full overflow-hidden shrink-0">
                        <img
                            src="{{ $pin->image }}"
                            alt="{{ superClean($pin->title, 200) }}"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

                        <div class="absolute top-4 right-4 bg-[var(--md-sys-color-tertiary)]/90 backdrop-blur-md text-[var(--md-sys-color-on-tertiary)] text-sm font-bold px-4 py-1.5 rounded-full shadow-lg border border-white/20">
                            مهم
                        </div>
                    </div>

                    <div class="absolute bottom-0 inset-x-0 p-6 lg:p-8 text-white flex flex-col gap-3 bg-gradient-to-t from-black/80 to-transparent pt-24">
                        <h2 class="text-2xl lg:text-3xl font-bold leading-tight drop-shadow-md">
                            {{ superClean($pin->title, 100) }}
                        </h2>
                        <p class="text-white/90 text-sm lg:text-base line-clamp-2 leading-relaxed opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100 transform translate-y-2 group-hover:translate-y-0">
                            {{ superClean($pin->body, 150) }}
                        </p>
                        <div class="flex items-center gap-3 text-white/80 text-xs font-medium mt-1">
                            <div class="flex items-center gap-1 bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm">
                                <span class="material-symbols-rounded text-[16px]">calendar_today</span>
                                <span>{{ $pin->created_at->format('Y/m/d') }}</span>
                            </div>
                            <div class="flex items-center gap-1 bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm">
                                <span class="material-symbols-rounded text-[16px]">person</span>
                                <span>ادمین</span>
                            </div>
                        </div>
                    </div>

                    <div class="absolute inset-0 rounded-[32px] ring-1 ring-white/0 group-hover:ring-white/20 transition-all duration-500 pointer-events-none"></div>
                </div>
            @endforeach
        @else
            <div class="flex-grow flex flex-col items-center justify-center p-8 text-center bg-gradient-to-br from-[var(--md-sys-color-surface-container)] to-[var(--md-sys-color-surface-container-low)] rounded-[32px] border border-[var(--md-sys-color-outline-variant)]/40 shadow-inner">
                <div class="w-20 h-20 rounded-full bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center mb-4 shadow-sm animate-pulse-slow">
                    <span class="material-symbols-rounded text-[40px] text-[var(--md-sys-color-on-secondary-container)]">campaign</span>
                </div>
                <h4 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">خوش آمدید</h4>
                <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm max-w-xs leading-relaxed">
                    در حال حاضر اعلان مهمی پین نشده است.<br>
                    اخبار جدید را در بخش تازه‌ترین‌ها دنبال کنید.
                </p>
            </div>
        @endif
    </div>
</aside>
