<div class="@container w-full">
    <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
        @forelse($this->users as $user)
            @php
                $p = presence($user->presence);
                $obscured = $p->isObscured();
                $sms = $user->sms_number;
                $ext = $user->getTodaysDeskExtension();
                $reserved = $user->getTodaysReservationsLabel();
                $skillTier = ($skillId !== null && $user->skill_tier_endorsements_count !== null)
                    ? (new \App\Models\SkillUser([
                        'endorsements_count' => $user->skill_tier_endorsements_count,
                        'last_used_at' => $user->skill_tier_last_used_at,
                    ]))->stateTier()
                    : null;
                $hasBar = $obscured || $sms || $ext || $reserved || $skillTier !== null;
                $hasCall = !$obscured && ($sms || $ext);
                $deptName = $user->profile?->department?->displayLabel();
                $unitName = $user->profile?->detailsMap()->get('unit');
                $sectionName = $user->profile?->detailsMap()->get('section');
                $orgTitle = collect([$deptName, $unitName, $sectionName])->filter()->implode(' › ');
            @endphp

            <x-ui.decor.status :status="$p" wire:key="user-{{ $user->id }}-{{ $p->effectType() }}"
                               x-data
                               title="{{ $p->sublabel() }}"
                               x-transition:enter="transition ease-out duration-300"
                               x-transition:enter-start="opacity-0 scale-95"
                               x-transition:enter-end="opacity-100 scale-100"
                               x-transition:leave="transition ease-in duration-200"
                               x-transition:leave-start="opacity-100 scale-100"
                               x-transition:leave-end="opacity-0 scale-95"
                               class="group relative flex flex-col justify-between items-center p-3 pt-3.5
                                      border shadow-sm rounded-2xl overflow-hidden cursor-pointer h-52
                                      transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]
                                      {{ $p->cardClasses() }}">

                <div class="flex flex-col items-center w-full gap-2 z-10">
                    <div class="relative {{ $obscured ? 'grayscale-[50%]' : '' }} transition-all duration-300 group-hover:blur-none group-hover:grayscale-0">
                        <img
                            src="{{ $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl() }}"
                            alt="{{ $user->name }}"
                            class="w-16 h-16 rounded-full object-cover
                                   ring-2 ring-{{ $p->color() }}-500 ring-offset-2
                                   ring-offset-[var(--md-sys-color-surface-container)]
                                   group-hover:scale-105 group-hover:ring-offset-4
                                   transition-all duration-300 {{ $p->imageClasses() }}"
                            loading="lazy"
                        >

                        @if($user->profile?->about_me)
                            <button
                                type="button"
                                class="absolute -top-1 -left-1 w-6 h-6 rounded-full
                                       bg-[var(--md-sys-color-primary)] flex items-center justify-center
                                       border-2 border-[var(--md-sys-color-surface)] shadow-sm animate-pulse-slow hover:scale-110 transition-transform z-20"
                                title="درباره من"
                                wire:click="openAboutMe({{ $user->id }})"
                                x-on:click.stop="$dispatch('open-about-me', {
                                    user: {
                                        name: {{ \Illuminate\Support\Js::from($user->name) }},
                                        position: {{ \Illuminate\Support\Js::from($user->profile?->positionLabel ?? 'کارشناس') }},
                                        image: {{ \Illuminate\Support\Js::from($user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl()) }},
                                        department: {{ \Illuminate\Support\Js::from($user->profile?->department?->displayLabel()) }},
                                        division: {{ \Illuminate\Support\Js::from($user->profile?->detailsMap()->get('unit')) }},
                                        section: {{ \Illuminate\Support\Js::from($user->profile?->detailsMap()->get('section')) }},
                                        favoriteColors: {{ \Illuminate\Support\Js::from($user->profile?->favorite_colors ?? []) }}
                                    },
                                    aboutMe: {{ \Illuminate\Support\Js::from(collect($user->profile?->about_me ?? [])->map(fn($v) => is_array($v) ? implode(', ', array_map(fn($x) => is_array($x) ? implode(', ', $x) : $x, $v)) : $v)->toArray()) }}
                                })">
                                <span class="material-symbols-rounded text-white leading-none text-[12px]">auto_stories</span>
                            </button>
                        @endif

                        <div class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full
                                bg-{{ $p->color() }}-500 flex items-center justify-center
                                border-2 border-[var(--md-sys-color-surface)] shadow-sm {{ $p->badgeClasses() }}">
                            <span class="material-symbols-rounded text-white leading-none text-[10px]">{{ $p->icon() }}</span>
                        </div>
                    </div>

                    <div class="w-full text-center space-y-0.5 {{ $obscured ? 'blur-[2px] opacity-60' : '' }} transition-all duration-300 group-hover:blur-none group-hover:opacity-100">
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] truncate leading-tight" title="{{ $user->name }}">
                            {{ $user->name }}
                        </p>
                        <p class="text-[10px] text-{{ $p->color() }}-500/90 truncate font-medium leading-tight" title="{{ $orgTitle ?: ($user->profile?->positionLabel ?? 'کارشناس') }}">
                            {{ $user->profile?->positionLabel ?? 'کارشناس' }}
                        </p>
                        @if($user->last_seen)
                            <p dir="rtl" class="text-[9px] text-[var(--md-sys-color-on-surface-variant)]/70 truncate pt-0.5">
                                {{ toJalaliRelative($user->last_seen) }}
                            </p>
                        @endif
                    </div>
                </div>

                @if($hasBar)
                    <div class="w-full border-t border-{{ $p->color() }}-500/20 z-20
                                flex items-center justify-evenly py-1 px-1.5
                                bg-[var(--md-sys-color-surface-container-high)]/70 rounded-xl
                                transition-all duration-300
                                {{ $obscured ? 'opacity-0 pointer-events-none' : '' }}">

                        @if($skillTier)
                            <div class="flex items-center justify-center px-1.5 py-0.5 rounded-md {{ $skillTier->badgeClasses() }}"
                                 title="{{ $skillTier->label() }} · این مهارت">
                                <span class="material-symbols-rounded text-[13px] block">{{ $skillTier->icon() }}</span>
                            </div>
                        @endif

                        @if($sms && !$obscured)
                            <button
                                type="button"
                                x-on:click.stop="$dispatch('open-sms-modal', { user: {{ \Illuminate\Support\Js::from($user->id) }} })"
                                title="پیامک: {{ $sms }}"
                                class="p-1 rounded-lg text-[var(--md-sys-color-on-surface-variant)]
                                       hover:text-[var(--md-sys-color-primary)]
                                       hover:bg-[var(--md-sys-color-primary)]/10 transition-colors">
                                <span class="material-symbols-rounded text-[15px] block">sms</span>
                            </button>
                        @endif

                        @if(($ext || $reserved) && !$obscured)
                            <div class="flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface)]/60 text-{{ $p->color() }}-500" title="{{ $reserved ? 'رزرو امروز: ' . $reserved : 'داخلی: ' . $ext }}">
                                <span class="material-symbols-rounded text-[12px]">domain</span>
                                @if($ext)
                                    <span class="text-[10px] font-bold tabular-nums">{{ $ext }}</span>
                                @endif
                            </div>
                        @endif

                        @if($hasCall)
                            <a href="tel:{{ $sms ?? $ext }}"
                               x-on:click.stop
                               title="تماس"
                               class="p-1 rounded-lg text-[var(--md-sys-color-on-surface-variant)]
                                      hover:text-emerald-500 hover:bg-emerald-500/10 transition-colors">
                                <span class="material-symbols-rounded text-[15px] block">call</span>
                            </a>
                        @endif
                    </div>
                @else
                    <div class="h-1"></div>
                @endif
            </x-ui.decor.status>

        @empty
            <div class="col-span-full">
                @if($skillId !== null || trim($skillSearch) !== '')
                    <x-ui.empty icon="manage_search" title="همکاری با این مهارت یافت نشد" variant="filtered" />
                @else
                    <x-ui.empty icon="manage_search" title="کاربری یافت نشد" variant="search" />
                @endif
            </div>
        @endforelse
    </div>
</div>
