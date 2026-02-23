<div class="@container w-full">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-4 px-1">
        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center shadow-sm">
             <span class="material-symbols-rounded text-base font-fill">groups</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">وضعیت همکاران</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    </div>

    <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8 gap-4">

        @forelse($this->users as $user)
            @php($p = presence($user->presence))
            {{-- Determine Status Color from Helper --}}
            @php
                // Map presence helper colors to MD3 token-like classes or use CSS vars if available
                // This is a simplified mapping based on typical status colors
                $statusColorVar = match($p->color()) {
                    'green' => 'var(--md-sys-color-primary)', // Online/Active
                    'amber', 'yellow' => 'var(--md-sys-color-tertiary)', // Away/Busy
                    'red' => 'var(--md-sys-color-error)', // DND/Offline
                    default => 'var(--md-sys-color-outline)', // Offline/Unknown
                };
            @endphp

            <div wire:key="user-{{ $user->id }}"
                 x-data
                 class="group relative flex flex-col items-center gap-3 p-4
                         bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden cursor-pointer h-48
                         shadow-sm border border-[var(--md-sys-color-outline-variant)]/20
                         transition-all duration-300
                         hover:-translate-y-1 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]
                         hover:border-[var(--md-sys-color-primary)]/30">

                {{-- Status Indicator Ring --}}
                <div class="relative mt-2">
                    <div class="absolute inset-0 rounded-full animate-pulse opacity-20" style="background-color: {{ $statusColorVar }}"></div>
                    <img
                        src="{{ $user->profile?->image ? asset('storage/'.$user->profile->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random&color=fff' }}"
                        alt="{{ $user->name }}"
                        class="w-14 h-14 rounded-full object-cover
                               ring-[3px] ring-offset-2 ring-offset-[var(--md-sys-color-surface)]
                               group-hover:scale-105 transition-transform duration-300"
                        style="--tw-ring-color: {{ $statusColorVar }}"
                        loading="lazy"
                    >
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full
                                flex items-center justify-center
                                border-2 border-[var(--md-sys-color-surface)] shadow-sm"
                         style="background-color: {{ $statusColorVar }}">
                        <span class="material-symbols-rounded text-white text-[10px] font-bold">{{ $p->icon() }}</span>
                    </div>
                </div>

                <div class="w-full text-center px-1 space-y-1">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate">
                        {{ $user->name }}
                    </p>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] truncate font-medium opacity-80">
                        {{ $user->profile?->position ?? 'کارشناس' }}
                    </p>
                </div>

                {{-- Action Overlay (Slide Up) --}}
                <div class="absolute inset-x-0 bottom-0 h-12 z-20
                            flex items-center justify-around px-2
                            bg-[var(--md-sys-color-surface-container)]/95 backdrop-blur-sm
                            border-t border-[var(--md-sys-color-outline-variant)]/20
                            translate-y-full opacity-0
                            group-hover:translate-y-0 group-hover:opacity-100
                            transition-all duration-300 cubic-bezier(0.4, 0, 0.2, 1)">

                    @if($user->sms_number)
                        <button
                            @click.stop="$dispatch('open-sms-modal', { user: '{{ $user->id }}' })"
                            title="پیامک"
                            class="p-2 rounded-lg text-[var(--md-sys-color-on-surface-variant)]
                                   hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] transition-colors">
                            <span class="material-symbols-rounded text-lg">sms</span>
                        </button>
                    @endif

                    <div class="flex items-center gap-1 text-[var(--md-sys-color-on-surface)]">
                        @if($user->getTodaysDeskExtension())
                            <span class="material-symbols-rounded text-[14px] opacity-60">call</span>
                            <span class="text-xs font-bold font-mono">{{ $user->getTodaysDeskExtension() }}</span>
                        @endif
                    </div>

                    @if($user->sms_number || $user->getTodaysDeskExtension())
                        <a href="tel:{{ $user->sms_number ?? $user->getTodaysDeskExtension() }}"
                           @click.stop
                           class="p-2 rounded-lg text-[var(--md-sys-color-on-surface-variant)]
                                  hover:text-[var(--md-sys-color-tertiary)] hover:bg-[var(--md-sys-color-tertiary-container)] transition-colors">
                            <span class="material-symbols-rounded text-lg">call</span>
                        </a>
                    @endif
                </div>
            </div>

        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-16 gap-3
                        bg-[var(--md-sys-color-surface)]/50 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20">
                <div class="w-16 h-16 rounded-2xl bg-[var(--md-sys-color-surface-variant)] flex items-center justify-center mb-2">
                    <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-outline)] opacity-50">person_off</span>
                </div>
                <p class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">کاربری یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>
