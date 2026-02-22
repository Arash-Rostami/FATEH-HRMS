<div class="space-y-8">
    <!-- Search Bar -->
    <div class="relative max-w-md mx-auto md:mx-0">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            label="جستجوی دسترسی‌ها"
            name="search"
            icon="search"
            class="glass-panel"
        />
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($credentials as $cred)
            <x-ui.card class="relative overflow-hidden group hover:scale-[1.02] hover:-translate-y-1 transition-all duration-300 border-t-4 border-t-[var(--md-sys-color-primary)] hover:shadow-[0_12px_40px_rgba(0,0,0,0.2)]">
                <!-- Background Decoration -->
                <div class="absolute top-0 left-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity duration-500">
                    <span class="material-symbols-rounded text-9xl transform -translate-x-4 -translate-y-4 rotate-12">key</span>
                </div>

                <div class="flex items-center justify-between mb-6 relative z-10">
                    <div class="flex items-center gap-3">
                         <div class="p-2 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                             <span class="material-symbols-rounded text-xl">vpn_key</span>
                         </div>
                         <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] truncate max-w-[150px]" title="{{ $cred->app_name }}">{{ $cred->app_name }}</h3>
                    </div>
                </div>

                <div class="space-y-4 relative z-10">
                    <div class="group/field relative transition-all">
                        <label class="text-[10px] text-[var(--md-sys-color-outline)] uppercase tracking-widest font-bold mb-1 mr-1">نام کاربری</label>
                        <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-variant)]/50 rounded-xl p-2.5 border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors">
                            <span class="font-mono text-sm truncate select-all text-[var(--md-sys-color-on-surface)] font-bold tracking-wide">{{ $cred->username }}</span>
                            <x-ui.copy-button text="{{ $cred->username }}" class="bg-[var(--md-sys-color-surface)] shadow-sm" />
                        </div>
                    </div>

                    <div class="group/field relative transition-all">
                        <label class="text-[10px] text-[var(--md-sys-color-outline)] uppercase tracking-widest font-bold mb-1 mr-1">رمز عبور</label>
                        <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-variant)]/50 rounded-xl p-2.5 border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] transition-colors group-hover:bg-[var(--md-sys-color-surface-variant)]">
                            <span class="font-mono text-sm tracking-[0.2em] text-[var(--md-sys-color-on-surface-variant)]">••••••••</span>
                            <x-ui.copy-button text="{{ $cred->password }}" class="bg-[var(--md-sys-color-surface)] shadow-sm" />
                        </div>
                    </div>

                    @if($cred->link)
                        <div class="pt-2">
                            <a href="{{ $cred->link }}" target="_blank" class="md3-btn w-full">
                                <span class="material-symbols-rounded text-lg ml-2">launch</span> ورود به سامانه
                            </a>
                        </div>
                    @endif

                    @if($cred->note)
                         <div class="mt-4 p-3 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] rounded-xl text-xs border border-[var(--md-sys-color-outline-variant)] flex gap-2">
                            <span class="material-symbols-rounded text-sm mt-0.5 flex-shrink-0 text-[var(--md-sys-color-primary)]">info</span>
                            <p class="leading-relaxed">{{ Str::limit($cred->note, 60) }}</p>
                         </div>
                    @endif
                </div>
            </x-ui.card>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <div class="bg-[var(--md-sys-color-surface-variant)] rounded-full p-6 mb-4 animate-pulse">
                    <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-outline)]">search_off</span>
                </div>
                <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] mb-2">موردی یافت نشد</h3>
                <p class="text-[var(--md-sys-color-on-surface-variant)] max-w-sm mx-auto">دسترسی با عنوان "{{ $search }}" یافت نشد. لطفا عبارت دیگری جستجو کنید.</p>
            </div>
        @endforelse
    </div>
</div>
