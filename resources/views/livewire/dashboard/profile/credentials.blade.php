<div class="space-y-5 animate-[fade-in_0.4s_ease-out]" dir="rtl">

    {{-- Security Warning Banner --}}
    <div class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] border border-[var(--md-sys-color-error)]/20 p-5">
        <div class="absolute -right-4 -top-4 opacity-[0.08] pointer-events-none">
            <span class="material-symbols-rounded text-[100px]">vpn_key</span>
        </div>
        <div class="relative z-10 flex gap-4 items-start md:items-center">
            <div class="w-10 h-10 flex-shrink-0 rounded-xl bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] flex items-center justify-center shadow-inner">
                <span class="material-symbols-rounded text-[22px]">gpp_bad</span>
            </div>
            <div>
                <h3 class="text-sm font-bold tracking-tight mb-0.5">هشدار امنیتی بسیار مهم</h3>
                <p class="text-xs opacity-90 leading-relaxed">این داده‌ها را کاملاً محرمانه نگه‌داشته و از اشتراک‌گذاری آن‌ها با سایر همکاران جداً خودداری نمایید.</p>
            </div>
        </div>
    </div>

    @if($this->hasAnyCredentials)
        {{-- Search --}}
        <div class="relative max-w-sm">
            <x-dashboard.form.input
                wire:model.live.debounce.300ms="search"
                label="جستجو در سامانه‌ها..."
                name="search"
                icon="search"
            />
        </div>

        {{-- Credentials Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->credentials as $cred)
                <div class="group relative flex flex-col rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] overflow-hidden transition-all duration-200 hover:border-[var(--md-sys-color-primary)]/40 hover:shadow-md hover:-translate-y-0.5 shadow-sm">

                    {{-- Top accent --}}
                    <div class="h-[3px] w-full bg-[var(--md-sys-color-primary)]"
                         style="box-shadow: 0 0 8px color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent)"></div>

                    {{-- Watermark --}}
                    <div class="absolute bottom-2 left-2 opacity-[0.04] pointer-events-none group-hover:opacity-[0.07] transition-opacity duration-500">
                        <span class="material-symbols-rounded text-[80px] -rotate-12">vpn_key</span>
                    </div>

                    <div class="p-5 flex flex-col gap-4 relative z-10 h-full">
                        {{-- App Name --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center flex-shrink-0 shadow-inner">
                                <span class="material-symbols-rounded text-[20px]">terminal</span>
                            </div>
                            <h3 class="font-bold text-base text-[var(--md-sys-color-on-surface)] truncate" title="{{ $cred->app_name }}">{{ $cred->app_name }}</h3>
                        </div>

                        {{-- Username --}}
                        <div>
                            <div class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1.5">نام کاربری</div>
                            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-variant)]/40 rounded-xl px-3 py-2 border border-[var(--md-sys-color-outline-variant)]/60 hover:border-[var(--md-sys-color-primary)]/40 transition-colors">
                                <span class="font-mono text-sm truncate select-all text-[var(--md-sys-color-on-surface)] flex-1" dir="ltr">{{ $cred->username }}</span>
                                <x-dashboard.form.copy-button text="{{ $cred->username }}" />
                            </div>
                        </div>

                        {{-- Password --}}
                        <div x-data="{ show: false }">
                            <div class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1.5">رمز عبور</div>
                            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-variant)]/40 rounded-xl px-3 py-2 border border-[var(--md-sys-color-outline-variant)]/60 hover:border-[var(--md-sys-color-primary)]/40 transition-colors">
                                <div class="flex items-center gap-2 overflow-hidden flex-1">
                                    <button type="button" @click="show = !show"
                                            class="flex-shrink-0 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors focus:outline-none">
                                        <span class="material-symbols-rounded text-[18px]" x-text="show ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                    <span x-show="!show" class="font-mono text-sm tracking-[0.3em] text-[var(--md-sys-color-on-surface-variant)] pt-1">••••••••</span>
                                    <span x-show="show" x-cloak class="font-mono text-sm truncate select-all text-[var(--md-sys-color-on-surface)]" dir="ltr">{{ $cred->password }}</span>
                                </div>
                                <x-dashboard.form.copy-button text="{{ $cred->password }}" class="flex-shrink-0" />
                            </div>
                        </div>

                        {{-- Note --}}
                        @if($cred->note)
                            <div class="flex gap-2 p-3 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border border-[var(--md-sys-color-secondary)]/10">
                                <span class="material-symbols-rounded text-[15px] flex-shrink-0 mt-0.5">info</span>
                                <p class="text-[11px] leading-relaxed">{{ Str::limit($cred->note, 80) }}</p>
                            </div>
                        @endif

                        {{-- Link CTA --}}
                        @if($cred->link)
                            <div class="mt-auto pt-1">
                                <a href="{{ $cred->link }}" target="_blank"
                                   class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-sm font-bold hover:opacity-90 active:scale-[0.98] transition-all shadow-sm">
                                    <span class="material-symbols-rounded text-[16px]">open_in_new</span>
                                    ورود به سامانه
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            @empty
                {{-- Search Empty State --}}
                <div class="col-span-full">
                    <div class="flex flex-col items-center justify-center py-16 text-center bg-[var(--md-sys-color-surface)] rounded-2xl border border-dashed border-[var(--md-sys-color-outline-variant)] shadow-sm">
                        <div class="w-16 h-16 rounded-2xl bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center mb-4 text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-[36px] animate-pulse">search_off</span>
                        </div>
                        <h3 class="text-base font-bold text-[var(--md-sys-color-on-surface)] mb-2">نتیجه‌ای یافت نشد</h3>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] max-w-xs leading-relaxed">هیچ سامانه‌ای با عبارت «{{ $search }}» مطابقت ندارد.</p>
                        <button type="button" wire:click="$set('search', '')"
                                class="mt-5 px-5 py-2 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] font-bold text-sm hover:brightness-95 transition-all">
                            پاک کردن جستجو
                        </button>
                    </div>
                </div>
            @endforelse
        </div>

    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-20 text-center bg-[var(--md-sys-color-surface)] rounded-2xl border border-dashed border-[var(--md-sys-color-outline-variant)] shadow-sm">
            <div class="w-20 h-20 rounded-2xl bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center mb-5 text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[44px]">no_accounts</span>
            </div>
            <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">اطلاعاتی برای نمایش وجود ندارد</h3>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] max-w-sm leading-relaxed">هیچ حساب کاربری برای سامانه‌های سازمانی توسط ادمین برای شما تعریف نشده است.</p>
        </div>
    @endif

</div>
