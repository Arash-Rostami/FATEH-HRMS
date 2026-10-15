<div class="@container w-full">
    <div class="sc-grid grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4">
        @forelse($this->users as $user)
            @php
                $d = $statusPresenter->gridData($user, $skillId);
                $p = $d['p'];
                $focusUntil = $d['focusUntil'];
                $obscured = $d['obscured'];
                $sms = $d['sms'];
                $ext = $d['ext'];
                $reserved = $d['reserved'];
                $summary = $d['summary'];
                $skillTier = $d['skillTier'];
                $orgTitle = $d['orgTitle'];
                $tenure = $d['tenure'];
                $email = $d['email'];
                $smsTel = $d['smsTel'];
                $extTel = $d['extTel'];
                $extCopy = $d['extCopy'];
                $chatUrl = $d['chatUrl'];
                $aboutPayload = $d['aboutPayload'];
            @endphp

            <x-ui.decor.status
                :status="$p"
                wire:key="user-{{ $user->id }}-{{ $p->effectType() }}"
                x-data
                style="--i: {{ $loop->index }}"
                x-bind:class="{ 'sc-open': openId === {{ $user->id }} }"
                title="{{ $p->sublabel() }}{{ $focusUntil ? ' · '.$focusUntil : '' }}"
                class="sc-card group relative flex flex-col items-center w-full aspect-square p-3 pt-4
                       border shadow-sm rounded-2xl overflow-hidden
                       transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]
                       {{ $p->cardClasses() }}">

                @if($user->profile?->about_me)
                    <button
                        type="button"
                        class="absolute top-2 start-2 w-6 h-6 rounded-lg
                               bg-[var(--md-sys-color-primary)] flex items-center justify-center
                               shadow-sm hover:scale-110 transition-transform z-20"
                        title="درباره من"
                        wire:click="openAboutMe({{ $user->id }})"
                        x-on:click.stop="$dispatch('open-about-me', {{ \Illuminate\Support\Js::from($aboutPayload) }})">
                        <span class="material-symbols-rounded text-white dark:text-[var(--md-sys-color-on-primary)] leading-none text-[12px]">auto_stories</span>
                    </button>
                @endif

                <div class="flex flex-col items-center w-full gap-1.5 z-10">
                    <div class="{{ $obscured ? 'grayscale-[50%]' : '' }} transition-all duration-300 group-hover:grayscale-0">
                        <x-dashboard.user-avatar :user="$user" size="lg" class="{{ $p->imageClasses() }}"/>
                    </div>

                    <div class="w-full text-center space-y-0.5 {{ $obscured ? 'blur-[2px] opacity-60' : '' }} transition-all duration-300 group-hover:blur-none group-hover:opacity-100">
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] truncate leading-tight"
                           title="{{ $user->name }}">
                            {{ $user->name }}
                        </p>
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] truncate font-medium leading-tight"
                           title="{{ $orgTitle ?: ($user->profile?->displayPosition ?? 'کارشناس') }}">
                            {{ $user->profile?->displayPosition ?? 'کارشناس' }}
                        </p>
                        <p dir="rtl" class="text-[9px] text-[var(--md-sys-color-on-surface-variant)]/70 truncate pt-0.5">
                            {{ $user->lastSeenLabel() }}
                        </p>
                        <x-ui.presence-tag wire:key="status-card-unreachable" :status="$p"/>
                    </div>
                </div>

                <button
                    type="button"
                    x-on:click.stop="toggleCard({{ $user->id }})"
                    :aria-expanded="openId === {{ $user->id }} ? 'true' : 'false'"
                    aria-label="جزئیات {{ $user->name }}"
                    class="sc-more">
                    <span class="sc-plus"><span class="material-symbols-rounded">expand_less</span></span>
                </button>

                <div
                    x-show="openId === {{ $user->id }}"
                    dir="rtl"
                    x-cloak

                    x-transition:enter="animate-slide-up"
                    x-transition:leave="animate-slide-out-bottom"
                    x-on:click.stop
                    class="sc-sheet {{ $p->ringClass() }}">

                    <div class="flex justify-end">
                        <x-ui.modals.close-button size="sm" close="openId = null" class="!w-5 !h-5 !rounded-md [&>span:last-child]:!text-[13px]"/>
                    </div>

                    @if($focusUntil)
                        <div wire:key="status-card-focus" class="sc-r">
                            <span class="sc-k">تمرکز</span>
                            <span class="sc-v">{{ $focusUntil }}</span>
                        </div>
                    @endif

                    @if($tenure)
                        <div wire:key="status-card-tenure" class="sc-r">
                            <span class="sc-k">سابقه</span>
                            <span class="sc-v">{{ $tenure }}</span>
                        </div>
                    @endif

                    @if($orgTitle)
                        <div wire:key="status-card-org" class="sc-r">
                            <span class="sc-k">سازمان</span>
                            <span class="sc-v sc-2">{{ $orgTitle }}</span>
                        </div>
                    @endif

                    @if($skillTier)
                        <div wire:key="status-card-skill" class="sc-r">
                            <span class="sc-k">مهارت</span>
                            <span class="sc-v flex items-center gap-1 px-1.5 py-0.5 rounded-md {{ $skillTier->badgeClasses() }}">
                                <span class="material-symbols-rounded">{{ $skillTier->icon() }}</span>
                                <span class="truncate">{{ $skillTier->label() }}</span>
                            </span>
                        </div>
                    @endif

                    @if($summary || $reserved)
                        <div wire:key="status-card-reservation" class="sc-r">
                            <span class="sc-k">رزرو</span>
                            <span class="sc-v sc-2">{{ $summary ?: $reserved }}</span>
                        </div>
                    @endif

                    @if($sms)
                        <div wire:key="status-card-mobile" class="sc-r">
                            <span class="sc-k">موبایل</span>
                            <span class="sc-v sc-s" dir="ltr"><span class="sc-t">{{ $sms }}</span></span>
                            @if($smsTel)
                                <a href="tel:{{ $smsTel }}" title="تماس" class="sc-a">
                                    <span class="material-symbols-rounded">call</span>
                                </a>
                            @endif
                            <button
                                type="button"
                                x-on:click.stop="$dispatch('toast', {message: 'ارسال پیامک به‌زودی فعال می‌شود', type: 'info'})"
                                title="ارسال پیامک"
                                class="sc-a">
                                <span class="material-symbols-rounded">sms</span>
                            </button>
                        </div>
                    @endif

                    @if($ext)
                        <div wire:key="status-card-ext" class="sc-r">
                            <span class="sc-k">داخلی</span>
                            <span class="sc-v sc-s" dir="ltr"><span class="sc-t">{{ $ext }}</span></span>
                            @if($extTel)
                                <a href="tel:{{ $extTel }}" title="تماس با شماره دفتر و گرفتن داخلی" class="sc-a">
                                    <span class="material-symbols-rounded">call</span>
                                </a>
                            @endif
                            @if($extCopy)
                                <button
                                    type="button"
                                    x-on:click.stop="navigator.clipboard?.writeText({{ \Illuminate\Support\Js::from($extCopy) }}); $dispatch('toast', {message: 'شماره کپی شد', type: 'success'})"
                                    title="کپی شماره کامل"
                                    class="sc-a">
                                    <span class="material-symbols-rounded">content_copy</span>
                                </button>
                            @endif
                        </div>
                    @endif

                    @if($email || $chatUrl)
                        <div wire:key="status-card-message" class="sc-r">
                            <span class="sc-k">پیام</span>
                            <span class="sc-v sc-s" dir="ltr"><span class="sc-t">{{ $email ?? 'گفتگوی درون‌برنامه' }}</span></span>
                            @if($chatUrl)
                                <a href="{{ $chatUrl }}" wire:navigate title="شروع گفتگو" class="sc-a">
                                    <span class="material-symbols-rounded">chat</span>
                                </a>
                            @endif
                            @if($email)
                                <a href="mailto:{{ $email }}" title="ارسال ایمیل" class="sc-a">
                                    <span class="material-symbols-rounded">mail</span>
                                </a>
                            @endif
                        </div>
                    @endif

                    @if($obscured)
                        <p wire:key="status-card-obscured" class="pt-2 text-[10px] text-[var(--md-sys-color-on-surface-variant)]">
                            اطلاعات تماس در این وضعیت پنهان است
                        </p>
                    @endif
                </div>
            </x-ui.decor.status>
        @empty
            <div class="col-span-full">
                @if($skillId !== null || trim($skillSearch) !== '')
                    <div wire:key="status-empty-filtered" class="contents">
                        <x-ui.empty icon="manage_search" title="همکاری با این مهارت یافت نشد" variant="filtered"/>
                    </div>
                @else
                    <div wire:key="status-empty-none" class="contents">
                        <x-ui.empty icon="manage_search" title="کاربری یافت نشد" variant="search"/>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>
