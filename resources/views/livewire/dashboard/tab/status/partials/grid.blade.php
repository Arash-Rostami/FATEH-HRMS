<div class="@container w-full">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
        @forelse($this->users as $user)
            @php
                $statusColor = match($user->presence) {
                    'onsite' => 'var(--md-sys-color-success)',
                    'remote' => 'var(--md-sys-color-primary)',
                    'busy' => 'var(--md-sys-color-error)',
                    'mission' => 'var(--md-sys-color-tertiary)',
                    default => 'var(--md-sys-color-outline)'
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
                class="group relative flex flex-col items-center p-6 rounded-3xl bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] border border-white/5 shadow-sm hover:shadow-xl transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] overflow-hidden cursor-pointer h-64"
                style="--status-color: {{ $statusColor }};"
            >
                {{-- Status Glow Background --}}
                <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[var(--status-color)]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                {{-- Status Ring --}}
                <div class="relative mb-4 z-10">
                    <div
                        class="absolute inset-0 rounded-full blur-md opacity-0 group-hover:opacity-40 transition-opacity duration-500 bg-[var(--status-color)]"
                    ></div>
                    <img
                        src="{{ $user->profile?->image ? asset('storage/'.$user->profile->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}"
                        alt="{{ $user->name }}"
                        class="w-24 h-24 rounded-full object-cover ring-2 ring-offset-2 ring-offset-[var(--md-sys-color-surface-container)] transition-all duration-500 group-hover:scale-105"
                        style="--tw-ring-color: var(--status-color);"
                        loading="lazy"
                    >
                    {{-- Status Icon Badge --}}
                    <div
                        class="absolute bottom-0 right-0 w-8 h-8 rounded-full flex items-center justify-center text-white shadow-sm border-2 border-[var(--md-sys-color-surface-container)]"
                        style="background-color: var(--status-color);"
                    >
                        <span class="material-symbols-rounded text-[18px]">{{ $statusIcon }}</span>
                    </div>
                </div>

                {{-- Name & Role (Slide Up Interaction) --}}
                <div class="text-center transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:-translate-y-2 z-10 w-full">
                    <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] truncate mx-auto px-2">{{ $user->name }}</h3>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] truncate mx-auto px-2">{{ $user->profile?->position ?? 'کارمند' }}</p>
                    <div class="flex items-center justify-center gap-1 mt-1 text-[10px] text-[var(--md-sys-color-outline)] opacity-70">
                        <span class="material-symbols-rounded text-[12px]">domain</span>
                        <span>{{ $user->profile?->department?->name ?? '---' }}</span>
                    </div>
                </div>

                {{-- Quick Actions (Slide In) --}}
                <div class="absolute bottom-0 left-0 right-0 p-3 translate-y-full opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] bg-[var(--md-sys-color-surface-container-highest)]/95 backdrop-blur-md flex justify-around items-center border-t border-white/10 z-20">
                    {{-- SMS --}}
                    @if($user->sms_number)
                        <button
                            @click.stop="$dispatch('open-sms-modal', { user: '{{ $user->id }}' })"
                            class="p-2 rounded-full hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors transform hover:scale-110"
                            title="پیامک: {{ $user->sms_number }}"
                        >
                            <span class="material-symbols-rounded">sms</span>
                        </button>
                    @endif

                    {{-- Extension --}}
                    <div class="flex flex-col items-center">
                        <span class="text-[9px] font-bold text-[var(--md-sys-color-primary)] uppercase tracking-wider">داخلی</span>
                        <span class="text-sm font-black text-[var(--md-sys-color-on-surface)]">{{ $user->getTodaysDeskExtension() ?? '---' }}</span>
                    </div>

                    {{-- Call --}}
                    @if($user->profile?->cellphone)
                         <a
                            href="tel:{{ $user->profile->cellphone }}"
                            @click.stop
                            class="p-2 rounded-full hover:bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-tertiary-container)] transition-colors transform hover:scale-110"
                            title="تماس"
                        >
                            <span class="material-symbols-rounded">call</span>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-[var(--md-sys-color-on-surface-variant)] opacity-50">
                <span class="material-symbols-rounded text-6xl mb-4">search_off</span>
                <p class="text-lg">کاربری یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>
