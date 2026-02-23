<div class="space-y-8 animate-[fade-in_0.5s_ease-out]" dir="rtl">

    <div class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] border border-[var(--md-sys-color-error)]/20 shadow-sm p-6">
        <div class="absolute -right-6 -top-6 opacity-10 pointer-events-none">
            <span class="material-symbols-rounded text-[120px]">security</span>
        </div>
        <div class="relative z-10 flex flex-col md:flex-row gap-4 items-start md:items-center">
            <div class="w-12 h-12 flex-shrink-0 rounded-full bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)] flex items-center justify-center shadow-inner">
                <span class="material-symbols-rounded text-[28px]">gpp_bad</span>
            </div>
            <div>
                <h3 class="text-lg font-bold tracking-tight mb-1">هشدار امنیتی بسیار مهم</h3>
                <p class="text-sm opacity-90 leading-relaxed">به‌منظور حفظ امنیت اطلاعات سازمان، لطفاً این داده‌ها را کاملاً محرمانه نگه‌داشته و از اشتراک‌گذاری آن‌ها با سایر همکاران جداً خودداری نمایید.</p>
            </div>
        </div>
    </div>

    @if($hasAny)
        <div class="relative max-w-md mx-auto md:mx-0">
            <x-dashboard.form.input
                wire:model.live.debounce.300ms="search"
                label="جستجو در سامانه‌ها..."
                name="search"
                icon="search"
                class="bg-[var(--md-sys-color-surface)]/80 backdrop-blur-md shadow-sm"
            />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($credentials as $cred)
                <x-dashboard.form.card class="relative overflow-hidden group hover:-translate-y-1 transition-all duration-300 border-t-4 border-t-[var(--md-sys-color-primary)] hover:shadow-xl bg-[var(--md-sys-color-surface)]">

                    <div class="absolute top-0 left-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity duration-500 pointer-events-none">
                        <span class="material-symbols-rounded text-[80px] -rotate-12 -translate-x-4 -translate-y-4">vpn_key</span>
                    </div>

                    <div class="flex items-center justify-between mb-6 relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-inner">
                                <span class="material-symbols-rounded text-[24px]">terminal</span>
                            </div>
                            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] truncate max-w-[150px]" title="{{ $cred->app_name }}">{{ $cred->app_name }}</h3>
                        </div>
                    </div>

                    <div class="space-y-4 relative z-10">
                        <div class="relative transition-all">
                            <label class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-widest font-bold mb-1.5 block">نام کاربری</label>
                            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-container-lowest)] rounded-xl p-2.5 border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)]/50 transition-colors">
                                <span class="font-mono text-sm truncate select-all text-[var(--md-sys-color-on-surface)]" dir="ltr">{{ $cred->username }}</span>
                                <x-dashboard.form.copy-button text="{{ $cred->username }}" class="shadow-sm" />
                            </div>
                        </div>

                        <div class="relative transition-all" x-data="{ showPassword: false }">
                            <label class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-widest font-bold mb-1.5 block">رمز عبور</label>
                            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-container-lowest)] rounded-xl p-2.5 border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)]/50 transition-colors">
                                <div class="flex items-center gap-3 overflow-hidden flex-1">
                                    <button type="button" @click="showPassword = !showPassword" class="flex-shrink-0 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-colors focus:outline-none">
                                        <span class="material-symbols-rounded text-[20px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                    <span x-show="!showPassword" class="font-mono text-sm tracking-[0.3em] text-[var(--md-sys-color-on-surface-variant)] mt-1.5">••••••••</span>
                                    <span x-show="showPassword" x-cloak class="font-mono text-sm truncate select-all text-[var(--md-sys-color-on-surface)]" dir="ltr">{{ $cred->password }}</span>
                                </div>
                                <x-dashboard.form.copy-button text="{{ $cred->password }}" class="shadow-sm flex-shrink-0" />
                            </div>
                        </div>

                        @if($cred->link)
                            <div class="pt-3">
                                <a href="{{ $cred->link }}" target="_blank" class="flex items-center justify-center gap-2 w-full text-sm font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] py-2.5 rounded-xl hover:opacity-90 transition-opacity active:scale-[0.98]">
                                    <span class="material-symbols-rounded text-[18px]">open_in_new</span>
                                    ورود به سامانه
                                </a>
                            </div>
                        @endif

                        @if($cred->note)
                            <div class="mt-4 p-3 bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] rounded-xl text-xs border border-[var(--md-sys-color-secondary)]/10 flex gap-2">
                                <span class="material-symbols-rounded text-[16px] flex-shrink-0 mt-0.5">info</span>
                                <p class="leading-relaxed">{{ Str::limit($cred->note, 80) }}</p>
                            </div>
                        @endif
                    </div>
                </x-dashboard.form.card>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center py-16 text-center bg-[var(--md-sys-color-surface-container-lowest)] rounded-3xl border border-[var(--md-sys-color-outline-variant)] border-dashed">
                    <div class="w-20 h-20 rounded-full bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center mb-4 text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded text-[40px] animate-pulse">search_off</span>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">نتیجه‌ای یافت نشد</h3>
                    <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] max-w-sm">هیچ سامانه‌ای با عبارت جستجو شده "{{ $search }}" مطابقت ندارد. لطفاً عبارت دیگری را امتحان کنید.</p>
                    <button type="button" wire:click="$set('search', '')" class="mt-6 px-6 py-2 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] font-bold text-sm hover:brightness-95 transition-all">
                        پاک کردن جستجو
                    </button>
                </div>
            @endforelse
        </div>
    @else
        <div class="col-span-full flex flex-col items-center justify-center py-20 text-center bg-[var(--md-sys-color-surface-container-lowest)] rounded-3xl border border-[var(--md-sys-color-outline-variant)] border-dashed">
            <div class="w-24 h-24 rounded-full bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center mb-6 text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[48px]">no_accounts</span>
            </div>
            <h3 class="text-2xl font-bold text-[var(--md-sys-color-on-surface)] mb-3">اطلاعاتی برای نمایش وجود ندارد</h3>
            <p class="text-[var(--md-sys-color-on-surface-variant)] max-w-md leading-relaxed">در حال حاضر، هیچ حساب کاربری و رمز عبوری برای سامانه‌های سازمانی توسط ادمین برای شما تعریف نشده است.</p>
        </div>
    @endif
</div>
