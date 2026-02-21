<div class="@container w-full">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3 md:gap-4">
        @forelse($this->users as $user)
            @php
                $statusColor = match($user->presence) {
                    'onsite' => 'var(--md-sys-color-success)',
                    'remote' => 'var(--md-sys-color-primary)',
                    'busy' => 'var(--md-sys-color-error)',
                    'mission' => 'var(--md-sys-color-tertiary)',
                    default => 'var(--md-sys-color-outline)'
                };

                $statusBg = match($user->presence) {
                    'onsite' => 'var(--md-sys-color-success-container)',
                    'remote' => 'var(--md-sys-color-primary-container)',
                    'busy' => 'var(--md-sys-color-error-container)',
                    'mission' => 'var(--md-sys-color-tertiary-container)',
                    default => 'var(--md-sys-color-surface-variant)'
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
                class="group relative flex flex-col items-center p-4 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] border border-white/5 shadow-sm hover:shadow-lg transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] overflow-hidden cursor-pointer h-48"
                style="--status-color: {{ $statusColor }};"
            >
                {{-- Status Glow Background (More subtle) --}}
                <div class="absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-[var(--status-color)]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                {{-- Status Ring & Avatar --}}
                <div class="relative mb-2 z-10">
                    <div
                        class="absolute inset-0 rounded-full blur-md opacity-20 group-hover:opacity-60 transition-opacity duration-300 bg-[var(--status-color)]"
                    ></div>
                    <img
                        src="{{ $user->profile?->image ? asset('storage/'.$user->profile->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random&color=fff' }}"
                        alt="{{ $user->name }}"
                        class="w-16 h-16 rounded-full object-cover ring-2 ring-offset-2 ring-offset-[var(--md-sys-color-surface-container-low)] transition-transform duration-300 group-hover:scale-105"
                        style="--tw-ring-color: var(--status-color);"
                        loading="lazy"
                    >
                    {{-- Status Icon Badge (Smaller) --}}
                    <div
                        class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center text-white shadow-sm border-2 border-[var(--md-sys-color-surface-container-low)]"
                        style="background-color: var(--status-color);"
                    >
                        <span class="material-symbols-rounded text-[14px]">{{ $statusIcon }}</span>
                    </div>
                </div>

                {{-- Name & Role (Compact) --}}
                <div class="text-center transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:-translate-y-1 z-10 w-full px-1">
                    <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate w-full">{{ $user->name }}</h3>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] truncate w-full opacity-80">{{ $user->profile?->position ?? 'کارمند' }}</p>

                    {{-- Department Tag (Subtle) --}}
                    <div class="flex items-center justify-center gap-1 mt-1 text-[9px] text-[var(--md-sys-color-outline)] opacity-60 group-hover:opacity-100 transition-opacity">
                        <span class="material-symbols-rounded text-[10px]">domain</span>
                        <span class="truncate max-w-[80px]">{{ $user->profile?->department?->name ?? '---' }}</span>
                    </div>
                </div>

                {{-- Quick Actions (Slide Up Overlay - Compact) --}}
                <div class="absolute bottom-0 left-0 right-0 py-2 px-3 translate-y-full opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] bg-[var(--md-sys-color-surface-container)]/95 backdrop-blur-md flex justify-between items-center border-t border-white/5 z-20 h-14">
                    {{-- SMS --}}
                    @if($user->sms_number)
                        <button
                            @click.stop="$dispatch('open-sms-modal', { user: '{{ $user->id }}' })"
                            class="p-1.5 rounded-full hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors transform hover:scale-110"
                            title="پیامک: {{ $user->sms_number }}"
                        >
                            <span class="material-symbols-rounded text-[18px]">sms</span>
                        </button>
                    @else
                        <div class="w-8 h-8"></div> {{-- Spacer --}}
                    @endif

                    {{-- Extension & Cell Indicator (Center Stack) --}}
                    <div class="flex flex-col items-center justify-center gap-0.5 min-w-[80px]">
                        @if($user->getTodaysDeskExtension())
                            <div class="flex items-center gap-1 text-[var(--md-sys-color-primary)]" title="Extension">
                                <span class="material-symbols-rounded text-[12px]">domain</span>
                                <span class="text-xs font-black">{{ $user->getTodaysDeskExtension() }}</span>
                            </div>
                        @endif

                        @if($user->sms_number)
                            <div class="flex items-center gap-1 text-[var(--md-sys-color-tertiary)]" title="Mobile">
                                 <span class="material-symbols-rounded text-[12px]">smartphone</span>
                                 <span class="text-[10px] font-bold dir-ltr">{{ $user->sms_number }}</span>
                            </div>
                        @endif

                        @if(!$user->sms_number && !$user->getTodaysDeskExtension())
                            <span class="text-[10px] text-[var(--md-sys-color-outline)]">---</span>
                        @endif
                    </div>

                    {{-- Call --}}
                    @if($user->sms_number || $user->getTodaysDeskExtension())
                         <a
                            href="tel:{{ $user->sms_number ?? $user->getTodaysDeskExtension() }}"
                            @click.stop
                            class="p-1.5 rounded-full hover:bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-tertiary-container)] transition-colors transform hover:scale-110"
                            title="تماس"
                        >
                            <span class="material-symbols-rounded text-[18px]">call</span>
                        </a>
                    @else
                        <div class="w-8 h-8"></div> {{-- Spacer --}}
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-[var(--md-sys-color-on-surface-variant)] opacity-50">
                <span class="material-symbols-rounded text-5xl mb-3">search_off</span>
                <p class="text-base">کاربری یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>
