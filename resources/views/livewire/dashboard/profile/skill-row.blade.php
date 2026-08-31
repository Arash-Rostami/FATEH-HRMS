@php
    $canEndorse = $viewer->isNot($owner) && $skillUser->status === \App\Enums\SkillRequestStatus::Approved && !$skillUser->is_private;
    $hasEndorsed = $skillUser->hasEndorser($viewer->id);
    $endorsers = $skillUserPresenter->endorsersAvatarStack($skillUser, 5);
    $totalEndorsements = $skillUserPresenter->endorsementsCount($skillUser);
    $isDormant = $skillUserPresenter->isDormant($skillUser);
@endphp

<div class="group flex items-center justify-between gap-4 p-3 rounded-xl transition-all duration-200 hover:bg-[var(--md-sys-color-on-primary)] {{ $isDormant ? 'opacity-70' : '' }}">
    <div class="flex items-start gap-3.5 min-w-0">
        <div class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0 bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-primary)] group-hover:bg-[var(--md-sys-color-primary-container)] transition-colors">
            <span class="material-symbols-rounded text-[20px]">{{ $skillPresenter->icon($skillUser->skill) }}</span>
        </div>

        <div class="min-w-0 flex-1 space-y-1">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)] truncate">
                    {{ $skillPresenter->displayLabel($skillUser->skill) }}
                </span>
                <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md shrink-0 tracking-wider uppercase {{ $skillUserPresenter->stateTier($skillUser)->badgeClasses() }}">
                    {{ $skillUserPresenter->stateTier($skillUser)->label() }}
                </span>
                @if($isDormant)
                    <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-md shrink-0 tracking-wider uppercase {{ $skillUserPresenter->dormantBadgeClasses($skillUser) }}"
                          title="بیش از {{ \App\Models\SkillUser::ACTIVE_WINDOW_DAYS }} روز از آخرین استفاده گذشته است">
                        کم‌فعالیت
                    </span>
                @endif
                <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-tertiary)] shrink-0" title="تأیید شده">verified</span>
            </div>

            <div class="flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)] flex-wrap">
                @if($totalEndorsements > 0)
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full shrink-0 {{ $skillUserPresenter->endorsementMetalClasses($skillUser) }}"
                          title="{{ $skillUserPresenter->isSoleEndorsement($skillUser) ? 'تأیید تک‌نفره' : 'تأیید چندنفره' }}">
                        <span class="material-symbols-rounded text-[11px]" style="font-variation-settings:'FILL' 1">military_tech</span>
                    </span>
                @endif
                <span class="font-semibold text-[var(--md-sys-color-on-surface)]">{{ $skillUserPresenter->endorsementProgressLabel($skillUser) }}</span>

                @if($skillUser->is_mentoring)
                    <span class="text-[var(--md-sys-color-outline)]">•</span>
                    <span class="inline-flex items-center gap-1 text-[var(--md-sys-color-tertiary)] font-medium">
                        <span class="material-symbols-rounded text-[14px]">school</span>
                        {{ $skillUserPresenter->mentorLabel($skillUser) }}
                    </span>
                @endif

                <span class="text-[var(--md-sys-color-outline)]">•</span>
                <span>{{ $skillUserPresenter->lastUsedLabel($skillUser) }}</span>

                @if($skillUserPresenter->contextLabel($skillUser))
                    <span class="text-[var(--md-sys-color-outline)]">•</span>
                    <span class="italic text-[var(--md-sys-color-outline)]">{{ $skillUserPresenter->contextLabel($skillUser) }}</span>
                @endif
            </div>

            @if($totalEndorsements > 0 && !empty($endorsers))
                <div class="flex items-center gap-2 pt-1">
                    <div class="flex items-center -space-x-1.5 rtl:space-x-reverse">
                        @foreach($endorsers as $endorser)
                            <img src="{{ $endorser->getProfileImageUrl() ?? $endorser->getInitialsAvatarUrl() }}"
                                 title="{{ $endorser->name }}"
                                 alt="{{ $endorser->name }}"
                                 class="w-5 h-5 rounded-full border border-[var(--md-sys-color-surface)] object-cover ring-1 ring-[var(--md-sys-color-outline-variant)]">
                        @endforeach
                    </div>
                    @if($totalEndorsements > count($endorsers))
                        <span class="text-[10px] font-medium text-[var(--md-sys-color-outline)]">
                            +{{ $totalEndorsements - count($endorsers) }} دیگر
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if($canEndorse)
        <div class="shrink-0 flex items-center">
            @if($hasEndorsed)
                <x-ui.buttons.form type="button"
                        wire:click="revokeEndorsement({{ $skillUser->id }})"
                        loading="revokeEndorsement({{ $skillUser->id }})"
                        icon="thumb_up"
                        class="!h-auto !px-3 !py-1.5 !gap-1.5 rounded-lg text-xs font-semibold bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] hover:bg-[var(--md-sys-color-tertiary)] hover:text-[var(--md-sys-color-on-tertiary)] focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-tertiary)]">
                    تأییدشده
                </x-ui.buttons.form>
            @else
                <x-ui.buttons.form type="button"
                        wire:click="endorse({{ $skillUser->id }})"
                        loading="endorse({{ $skillUser->id }})"
                        icon="thumb_up"
                        class="!h-auto !px-3 !py-1.5 !gap-1.5 rounded-lg text-xs font-semibold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
                    تأیید مهارت
                </x-ui.buttons.form>
            @endif
        </div>
    @endif
</div>
