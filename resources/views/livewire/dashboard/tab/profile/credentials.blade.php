<div class="space-y-6">
    <!-- Search Bar -->
    <div class="relative max-w-md mx-auto md:mx-0">
        <x-dashboard.form.input
            wire:model.live.debounce.300ms="search"
            label="جستجو در نام‌های کاربری"
            name="search"
            icon="search"
            class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-lg rounded-xl"
        />
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($credentials as $cred)
            <div class="group relative overflow-hidden bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] rounded-2xl transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                <!-- Decorative BG -->
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity duration-500 pointer-events-none">
                    <span class="material-symbols-rounded text-9xl transform rotate-12 translate-x-4 -translate-y-4 text-[var(--md-sys-color-primary)]">vpn_key</span>
                </div>

                <div class="p-6 relative z-10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-2xl">security</span>
                        </div>
                        <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] truncate" title="{{ $cred->app_name }}">{{ $cred->app_name }}</h3>
                    </div>

                    <div class="space-y-4">
                        <div class="group/field relative">
                            <label class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-widest font-bold mb-1 ml-1 block">نام کاربری</label>
                            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-variant)]/30 rounded-xl p-3 border border-transparent hover:border-[var(--md-sys-color-outline)] transition-colors">
                                <span class="font-mono text-sm truncate select-all text-[var(--md-sys-color-on-surface)] dir-ltr">{{ $cred->username }}</span>
                                <x-dashboard.form.copy-button text="{{ $cred->username }}" class="bg-[var(--md-sys-color-surface)] shadow-sm rounded-lg p-1" />
                            </div>
                        </div>

                        <div class="group/field relative">
                            <label class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-widest font-bold mb-1 ml-1 block">رمز عبور</label>
                            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-variant)]/30 rounded-xl p-3 border border-transparent hover:border-[var(--md-sys-color-outline)] transition-colors">
                                <span class="font-mono text-sm tracking-[0.2em] text-[var(--md-sys-color-on-surface-variant)]">••••••••</span>
                                <x-dashboard.form.copy-button text="{{ $cred->password }}" class="bg-[var(--md-sys-color-surface)] shadow-sm rounded-lg p-1" />
                            </div>
                        </div>
                    </div>

                    @if($cred->link)
                        <div class="mt-6 pt-4 border-t border-[var(--md-sys-color-outline-variant)]">
                            <a href="{{ $cred->link }}" target="_blank" class="flex items-center justify-center w-full gap-2 py-2.5 rounded-xl bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] text-sm font-bold hover:shadow-lg hover:scale-[1.02] transition-all duration-300">
                                <span class="material-symbols-rounded text-lg">open_in_new</span>
                                ورود به سامانه
                            </a>
                        </div>
                    @endif
                </div>

                @if($cred->note)
                    <div class="bg-[var(--md-sys-color-surface-variant)]/50 px-6 py-3 border-t border-[var(--md-sys-color-outline-variant)]">
                        <div class="flex gap-2 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-base shrink-0 mt-0.5">info</span>
                            <p class="leading-relaxed line-clamp-2">{{ $cred->note }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <div class="bg-[var(--md-sys-color-surface-variant)] rounded-full p-6 mb-4 animate-pulse">
                    <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-surface-variant)]">search_off</span>
                </div>
                <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">موردی یافت نشد</h3>
                <p class="text-[var(--md-sys-color-on-surface-variant)] max-w-sm mx-auto">هیچ نام کاربری با عبارت "{{ $search }}" یافت نشد.</p>
            </div>
        @endforelse
    </div>
</div>
