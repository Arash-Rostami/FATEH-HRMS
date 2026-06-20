<div class="@container w-full">
    <div
        class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8 gap-2">

        @forelse($this->users as $user)
            @php($p = presence($user->presence))

            <x-ui.decor.status :status="$p" wire:key="user-{{ $user->id }}-{{ $p->effectType() }}"
                               x-data
                               title="{{ $p->sublabel() }}"
                               x-transition:enter="transition ease-out duration-300"
                               x-transition:enter-start="opacity-0 scale-95"
                               x-transition:enter-end="opacity-100 scale-100"
                               x-transition:leave="transition ease-in duration-200"
                               x-transition:leave-start="opacity-100 scale-100"
                               x-transition:leave-end="opacity-0 scale-95"
                               class="group relative flex flex-col items-center gap-2 p-3 pt-4
                     border shadow-sm rounded-2xl overflow-hidden cursor-pointer h-40
                     transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]
                     {{ $p->cardClasses() }}">

                <div class="relative z-10 {{ $p->isObscured() ? 'blur-[1px] grayscale-[50%]' : '' }} transition-all duration-300 group-hover:blur-none group-hover:grayscale-0">
                    <img
                        src="{{ $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl() }}"
                        alt="{{ $user->name }}"
                        class="w-18 h-18 rounded-full object-cover
                           ring-2 ring-{{ $p->color() }}-500 ring-offset-2
                           ring-offset-[var(--md-sys-color-surface-container)]
                           group-hover:scale-105 group-hover:ring-offset-4
                           transition-all duration-300 {{ $p->imageClasses() }}"
                        loading="lazy"
                    >

                    @if($user->profile?->about_me)
                        <div class="absolute -top-4 -left-6 w-8 h-8 rounded-full
                                bg-[var(--md-sys-color-primary)] flex items-center justify-center
                                border-2 border-[var(--md-sys-color-surface)] shadow-sm animate-pulse-slow cursor-pointer hover:scale-110 transition-transform z-20"
                             title="درباره من"
                             @click.stop="$dispatch('open-about-me', {
                             user: { name: '{{ $user->name }}', position: '{{ $user->profile?->positionLabel ?? "کارشناس" }}', image: '{{ $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl() }}' },
                             aboutMe: @js($user->profile?->about_me ?? [])
                         })">
                            <span class="material-symbols-rounded text-white leading-none text-[12px]">auto_stories</span>
                        </div>
                    @endif

                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full
                            bg-{{ $p->color() }}-500 flex items-center justify-center
                            border-2 border-[var(--md-sys-color-surface)] shadow-sm {{ $p->badgeClasses() }}">
                        <span
                            class="material-symbols-rounded text-white leading-none text-[10px]">{{ $p->icon() }}</span>
                    </div>
                </div>

                <div class="w-full text-center px-1 z-10 space-y-0.5 {{ $p->isObscured() ? 'blur-[2px] opacity-60' : '' }} transition-all duration-300 group-hover:blur-none group-hover:opacity-100">
                    <p class="text-[11px] font-semibold text-[var(--md-sys-color-on-surface)] truncate leading-snug">
                        {{ $user->name }}
                    </p>
                    <p class="text-[9px] text-{{ $p->color() }}-400/70 truncate font-medium pt-3">
                        {{ $user->profile?->positionLabel ?? 'کارشناس' }}
                    </p>
                </div>

                <div class="absolute inset-x-0 bottom-0 h-10 z-20
                        flex items-center justify-between px-2
                        bg-[var(--md-sys-color-surface-container-high)]/95
                        border-t border-{{ $p->color() }}-500/20
                        transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]
                        {{ $p->isObscured() ? 'opacity-0 pointer-events-none' : '' }}">

                    @if($user->sms_number && !$p->isObscured())
                        <button
                            @click.stop="$dispatch('open-sms-modal', { user: '{{ $user->id }}' })"
                            title="پیامک: {{ $user->sms_number }}"
                            class="p-1.5 rounded-lg text-[var(--md-sys-color-on-surface-variant)]
                               hover:text-[var(--md-sys-color-primary)]
                               hover:bg-[var(--md-sys-color-primary)]/10 transition-colors">
                            <span class="material-symbols-rounded text-[15px]">sms</span>
                        </button>
                    @else
                        <div class="w-7"></div>
                    @endif

                    <div class="flex items-center gap-0.5 text-{{ $p->color() }}-400">
                        @if($user->getTodaysDeskExtension() && !$p->isObscured())
                            <span class="material-symbols-rounded text-[11px]">domain</span>
                            <span
                                class="text-[11px] font-bold tabular-nums">{{ $user->getTodaysDeskExtension() }}</span>
                        @elseif(!$user->sms_number || $p->isObscured())
                            <span class="text-[10px] text-[var(--md-sys-color-outline)]">---</span>
                        @endif
                    </div>

                    @if(($user->sms_number || $user->getTodaysDeskExtension()) && !$p->isObscured())
                        <a href="tel:{{ $user->sms_number ?? $user->getTodaysDeskExtension() }}"
                           @click.stop
                           class="p-1.5 rounded-lg text-[var(--md-sys-color-on-surface-variant)]
                              hover:text-emerald-500 hover:bg-emerald-500/10 transition-colors">
                            <span class="material-symbols-rounded text-[15px]">call</span>
                        </a>
                    @else
                        <div class="w-7"></div>
                    @endif
                </div>
            </x-ui.decor.status>

        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-16 gap-3
                    text-[var(--md-sys-color-on-surface-variant)]/35">
                <span class="material-symbols-rounded text-5xl">manage_search</span>
                <p class="text-sm font-medium">کاربری یافت نشد</p>
            </div>
        @endforelse
    </div>
</div>
