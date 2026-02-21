<div class="@container w-full">
    <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8 gap-2 md:gap-3">
        @forelse($this->users as $user)
            @php
                // Using exact colors from status-switcher.blade.php
                // Onsite: Emerald
                // Remote: Sky
                // Busy: Rose
                // Mission: Amber

                $colorClasses = match($user->presence) {
                    'onsite' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20 hover:border-emerald-500/40',
                    'remote' => 'text-sky-400 bg-sky-500/10 border-sky-500/20 hover:border-sky-500/40',
                    'busy' => 'text-rose-400 bg-rose-500/10 border-rose-500/20 hover:border-rose-500/40',
                    'mission' => 'text-amber-400 bg-amber-500/10 border-amber-500/20 hover:border-amber-500/40',
                    default => 'text-gray-400 bg-gray-500/10 border-gray-500/20 hover:border-gray-500/40'
                };

                // Ring colors for avatar
                $ringClass = match($user->presence) {
                    'onsite' => 'ring-emerald-500',
                    'remote' => 'ring-sky-500',
                    'busy' => 'ring-rose-500',
                    'mission' => 'ring-amber-500',
                    default => 'ring-gray-500'
                };

                // Icon background colors (solid)
                $iconBgClass = match($user->presence) {
                    'onsite' => 'bg-emerald-500',
                    'remote' => 'bg-sky-500',
                    'busy' => 'bg-rose-500',
                    'mission' => 'bg-amber-500',
                    default => 'bg-gray-500'
                };

                $statusIcon = match($user->presence) {
                    'onsite' => 'apartment',
                    'remote' => 'laptop_chromebook',
                    'busy' => 'do_not_disturb_on',
                    'mission' => 'flight_takeoff',
                    default => 'help'
                };
            @endphp

            <div
                wire:key="user-{{ $user->id }}"
                class="group relative flex flex-col items-center p-3 rounded-xl border transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] overflow-hidden cursor-pointer h-40 hover:shadow-lg hover:-translate-y-1 {{ $colorClasses }}"
            >
                {{-- Status Ring & Avatar --}}
                <div class="relative mb-2 z-10 mt-1">
                    <img
                        src="{{ $user->profile?->image ? asset('storage/'.$user->profile->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random&color=fff' }}"
                        alt="{{ $user->name }}"
                        class="w-12 h-12 rounded-full object-cover ring-2 ring-offset-2 ring-offset-[var(--md-sys-color-surface)] transition-transform duration-300 group-hover:scale-105 {{ $ringClass }}"
                        loading="lazy"
                    >
                    {{-- Status Icon Badge --}}
                    <div
                        class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-white shadow-sm border border-[var(--md-sys-color-surface)] {{ $iconBgClass }}"
                    >
                        <span class="material-symbols-rounded text-[12px]">{{ $statusIcon }}</span>
                    </div>
                </div>

                {{-- Name & Role (Ultra Compact) --}}
                <div class="text-center w-full px-0.5 z-10">
                    <h3 class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate w-full leading-tight">{{ $user->name }}</h3>
                    <p class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] truncate w-full opacity-70 mt-0.5">{{ $user->profile?->position ?? 'کارمند' }}</p>
                </div>

                {{-- Quick Actions (Slide Up Overlay - Ultra Compact) --}}
                <div class="absolute bottom-0 left-0 right-0 py-1.5 px-2 translate-y-full opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] bg-[var(--md-sys-color-surface-container)]/95 backdrop-blur-md flex justify-between items-center border-t border-white/5 z-20 h-10">
                    {{-- SMS --}}
                    @if($user->sms_number)
                        <button
                            @click.stop="$dispatch('open-sms-modal', { user: '{{ $user->id }}' })"
                            class="p-1 rounded-full hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors transform hover:scale-110"
                            title="پیامک: {{ $user->sms_number }}"
                        >
                            <span class="material-symbols-rounded text-[16px]">sms</span>
                        </button>
                    @else
                        <div class="w-6 h-6"></div>
                    @endif

                    {{-- Extension & Cell Indicator (Tiny Center Stack) --}}
                    <div class="flex flex-col items-center justify-center leading-none">
                        @if($user->getTodaysDeskExtension())
                            <div class="flex items-center gap-0.5 text-[var(--md-sys-color-primary)] opacity-90" title="Extension">
                                <span class="material-symbols-rounded text-[10px]">domain</span>
                                <span class="text-[9px] font-black">{{ $user->getTodaysDeskExtension() }}</span>
                            </div>
                        @endif

                        @if($user->sms_number)
                            <div class="flex items-center gap-0.5 text-[var(--md-sys-color-tertiary)] opacity-90" title="Mobile">
                                 <span class="material-symbols-rounded text-[10px]">smartphone</span>
                                 <span class="text-[8px] font-bold dir-ltr">{{ $user->sms_number }}</span>
                            </div>
                        @endif

                        @if(!$user->sms_number && !$user->getTodaysDeskExtension())
                            <span class="text-[8px] text-[var(--md-sys-color-outline)]">---</span>
                        @endif
                    </div>

                    {{-- Call --}}
                    @if($user->sms_number || $user->getTodaysDeskExtension())
                         <a
                            href="tel:{{ $user->sms_number ?? $user->getTodaysDeskExtension() }}"
                            @click.stop
                            class="p-1 rounded-full hover:bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-tertiary-container)] transition-colors transform hover:scale-110"
                            title="تماس"
                        >
                            <span class="material-symbols-rounded text-[16px]">call</span>
                        </a>
                    @else
                        <div class="w-6 h-6"></div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-[var(--md-sys-color-on-surface-variant)] opacity-50">
                <span class="material-symbols-rounded text-4xl mb-2">search_off</span>
                <p class="text-sm">کاربری یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>
