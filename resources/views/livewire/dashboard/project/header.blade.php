@php
    $project = $this->activeProject;
    $summary = $this->reportSummary;
    $memberCount = $this->projectMemberUsers->count();
    $riskChips = $this->presenter->headerRiskChips($project, $summary);
    $settingsSummary = $project->settingsSummary();
    $otherSettings = $project->otherSettingChips();
    $badge = $this->presenter->badgeColors($project->id);
    $isOwner = $project->owner_id !== null && (int) $project->owner_id === (int) auth()->id();
    $hasSpecs = $settingsSummary !== '—' || !empty($otherSettings);
    $hasMobilePopoverData = $hasSpecs || !empty($riskChips);
@endphp

<div class="chat-scale flex flex-shrink-0 items-center justify-between gap-2.5 md:gap-4">
    <div class="flex min-w-0 flex-1 items-center gap-2.5 md:gap-3.5">
        <button type="button"
                x-on:click="backToList()"
                aria-label="بازگشت به فهرست پروژه‌ها"
                x-show="!max"
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 md:hidden">
            <span class="material-symbols-rounded text-base">arrow_forward</span>
        </button>

        <div class="flex h-11 w-11 flex-shrink-0 select-none items-center justify-center rounded-2xl text-xl font-bold shadow-sm ring-1 {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['ring'] }}">
            {{ mb_substr($project->name, 0, 1) }}
        </div>

        <div class="relative min-w-0 flex-1"
             x-data="{ membersOpen: false, specsOpen: false }"
             x-on:keydown.escape.window="membersOpen = false; specsOpen = false">

            <div class="flex min-w-0 items-center gap-2">
                <h2 class="truncate text-sm md:text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">
                    {{ $project->name }}
                </h2>

                <div class="hidden md:flex flex-shrink-0 items-center">
                    <x-ui.buttons.copy :text="route('projects', ['open' => $project->id, 'tab' => $activeTab])" message="لینک پروژه کپی شد"/>
                </div>

                @if(!empty($riskChips))
                    <div class="hidden lg:flex min-w-0 items-center gap-1.5 overflow-hidden">
                        <span class="h-3 w-px flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_100%,transparent)] opacity-60"></span>
                        @foreach($riskChips as $chip)
                            <span class="inline-flex flex-shrink-0 items-center gap-0.5 rounded-md border px-1.5 py-0.5 text-[10px] font-bold {{ $chip['classes'] }}">
                                <span class="material-symbols-rounded text-[11px]">{{ $chip['icon'] }}</span>
                                <span>{{ $chip['text'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif

                <button type="button"
                        x-on:click=" specsOpen = false; membersOpen = !membersOpen"
                        :aria-expanded="membersOpen"
                        aria-label="لیست اعضای پروژه"
                        class="inline-flex flex-shrink-0 items-center gap-0.5 rounded-md px-1.5 py-0.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] md:hidden">
                    <span class="material-symbols-rounded text-[13px]" aria-hidden="true">group</span>
                    <span>{{ convertToPersian($memberCount) }}</span>
                </button>

                @if($hasMobilePopoverData)
                    <button type="button"
                            x-on:click="membersOpen = false; specsOpen = !specsOpen"
                            :aria-expanded="specsOpen"
                            title="تنظیمات و مشخصات پروژه"
                            aria-label="تنظیمات و مشخصات پروژه"
                            class=" inline-flex flex-shrink-0 items-center gap-1 rounded-md bg-[var(--md-sys-color-surface-container-highest)]/80 px-1.5 py-0.5 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] {{ $hasSpecs ? '' : 'lg:hidden' }}">
                        <span class="material-symbols-rounded text-[12px]">tune</span>
                        @if(!empty($otherSettings))
                            <span class="hidden sm:inline">{{ convertToPersian(count($otherSettings)) }}</span>
                        @endif
                    </button>
                @endif
            </div>

            <div class="mt-0.5 flex min-w-0 items-center gap-2 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                <span class="hidden md:inline-flex flex-shrink-0 items-center gap-1 font-medium">
                    <span class="material-symbols-rounded text-[13px]">person</span>
                    <span class="max-w-[140px] truncate">{{ $project->owner?->name ?? '—' }}</span>
                </span>

                <span class="hidden md:inline h-1 w-1 flex-shrink-0 rounded-full bg-current opacity-40"></span>

                <button type="button"
                        x-on:click="specsOpen = false; membersOpen = !membersOpen"
                        :aria-expanded="membersOpen"
                        class="hidden md:inline-flex flex-shrink-0 items-center gap-1 font-medium transition-colors hover:text-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-[13px]">group</span>
                    <span>{{ convertToPersian($memberCount) }} عضو</span>
                </button>

                <span class="hidden md:inline h-1 w-1 flex-shrink-0 rounded-full bg-current opacity-40"></span>

                <span class="inline-flex flex-shrink-0 items-center gap-1 font-medium">
                    <span class="material-symbols-rounded text-[13px]">task_alt</span>
                    <span>{{ convertToPersian($summary['done']) }} از {{ convertToPersian($summary['total']) }}</span>
                </span>

                @if(!empty($riskChips))
                    <div class="hidden md:flex lg:hidden min-w-0 items-center gap-1.5 overflow-hidden">
                        @foreach($riskChips as $chip)
                            <span class="h-1 w-1 flex-shrink-0 rounded-full bg-current opacity-40"></span>
                            <span class="inline-flex flex-shrink-0 items-center gap-0.5 rounded-md border px-1.5 py-0.5 text-[10px] font-bold {{ $chip['classes'] }}">
                                <span class="material-symbols-rounded text-[11px]">{{ $chip['icon'] }}</span>
                                <span>{{ $chip['text'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif

                @if($settingsSummary !== '—')
                    <span class="hidden xl:inline h-1 w-1 flex-shrink-0 rounded-full bg-current opacity-40"></span>
                    <span class="hidden xl:inline min-w-0 truncate text-[11px] opacity-75" title="{{ $settingsSummary }}">
                        {{ $settingsSummary }}
                    </span>
                @endif
            </div>

            <div x-show="membersOpen"
                 x-cloak
                 x-on:click.outside="membersOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                 class="absolute top-full mt-2 start-0 z-40 w-64 max-h-72 overflow-y-auto custom-scrollbar p-1.5 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]">
                @foreach($this->projectMembers as $member)
                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors">
                        <div class="flex-shrink-0">
                            <x-ui.avatar :existingImage="$member['avatar_url']" :alt="$member['name']"
                                         size="sm" :online="$member['is_online']" :status="$member['presence']" :focus="$member['focus']"/>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1 flex-wrap">
                                <span class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $member['name'] }}</span>
                                @if($member['is_owner'])
                                    <span class="flex-shrink-0 text-[9px] font-bold px-1 py-0.5 rounded-md bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">مالک</span>
                                @endif
                                @if($member['presence_label'])
                                    <span class="flex-shrink-0 inline-flex items-center px-1 py-0.5 rounded-full text-[9px] font-bold {{ $member['presence_class'] }}">{{ $member['presence_label'] }}</span>
                                @endif
                                @if($member['occasion'])
                                    <span class="flex-shrink-0 inline-flex items-center gap-0.5 px-1 py-0.5 rounded-md text-[9px] font-bold {{ $member['occasion']['chip'] }}">
                                        <span class="material-symbols-rounded text-[11px]">{{ $member['occasion']['icon'] }}</span>
                                        {{ $member['occasion']['label'] }}
                                    </span>
                                @endif
                            </div>
                            @if($member['org_title'])
                                <p class="truncate text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">{{ $member['org_title'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($hasMobilePopoverData)
                <div x-show="specsOpen"
                     x-cloak
                     x-on:click.outside="specsOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                     class="absolute top-full mt-2 start-0 z-40 w-72 max-w-[calc(100vw-2rem)] p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]">
                    <div class="flex flex-col gap-2.5 text-xs">
                        <div class="flex items-center justify-between gap-2 md:hidden">
                            <span class="text-[var(--md-sys-color-on-surface-variant)]">مالک پروژه:</span>
                            <span class="truncate font-bold text-[var(--md-sys-color-on-surface)]">{{ $project->owner?->name ?? '—' }}</span>
                        </div>

                        @if(!empty($riskChips))
                            <div class="flex flex-wrap items-center gap-1 md:hidden">
                                @foreach($riskChips as $chip)
                                    <span class="inline-flex items-center gap-0.5 rounded-md border px-1.5 py-0.5 text-[10px] font-bold {{ $chip['classes'] }}">
                                        <span class="material-symbols-rounded text-[11px]">{{ $chip['icon'] }}</span>
                                        <span>{{ $chip['text'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if($settingsSummary !== '—')
                            <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">
                                {{ $settingsSummary }}
                            </p>
                        @endif

                        @if(!empty($otherSettings))
                            <div class="flex flex-wrap items-center gap-1">
                                @foreach($otherSettings as $key => $chip)
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]"
                                          title="{{ $chip['label'] }}: {{ $chip['text'] }}">
                                        <span class="material-symbols-rounded text-[11px]">tune</span>
                                        <span class="max-w-[180px] truncate" dir="auto">{{ $chip['label'] }}: {{ $chip['text'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="flex flex-shrink-0 items-center gap-1">
        @if($activeTab === 'teamChat')
            <div wire:key="project-header-pattern-picker" class="contents" :class="headerBtnClass(false, 'block')">
                @include('livewire.dashboard.messaging.pattern-picker')
            </div>
        @endif

        <x-dashboard.reminder-trigger :for="$project"/>

        @if($isOwner)
            <button type="button"
                    wire:key="project-header-edit"
                    x-on:click="$wire.openEdit()"
                    title="ویرایش پروژه"
                    aria-label="ویرایش پروژه"
                    :class="headerBtnClass(false)"
                    class="hidden md:flex items-center justify-center h-8 w-8 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-all duration-200 ease-out active:scale-95">
                <span class="material-symbols-rounded text-base">edit</span>
            </button>
        @endif

        <x-ui.modals.maximize-button alpine-class="max ? 'flex' : 'hidden md:flex'"/>

        <button type="button"
                wire:key="project-header-close"
                x-on:click="closeProject()"
                aria-label="بازگشت (بستن پروژه)"
                title="بازگشت (بستن پروژه)"
                :class="headerBtnClass(false)"
                class="hidden md:flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95">
            <span class="material-symbols-rounded text-base">arrow_forward</span>
        </button>

        <button type="button" x-on:click="toggleHeaderDeclutter()" :aria-pressed="headerDeclutter"
                :aria-label="headerDeclutter ? 'نمایش سربرگ' : 'خلوت‌سازی'"
                :title="headerDeclutter ? 'نمایش سربرگ' : 'خلوت‌سازی'"
                class="hidden md:flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95"
                :class="{ 'mx-auto opacity-0': headerDeclutter }">
            <span class="material-symbols-rounded text-base rotate-90" x-text="headerDeclutter ? 'unfold_more' : 'unfold_less'"></span>
        </button>

        <div x-data="{ open: false, pos: {} }" class="md:hidden">
            <button type="button"
                    x-ref="kebabTrigger"
                    x-on:click="pos = $refs.kebabTrigger.getBoundingClientRect().toJSON(); open = !open"
                    aria-label="ابزارهای بیشتر"
                    title="ابزارهای بیشتر"
                    class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out active:scale-95">
                <span class="material-symbols-rounded text-base">more_horiz</span>
            </button>
            <template x-teleport="body">
                <div x-show="open"
                     x-cloak
                     dir="rtl"
                     x-on:click.away="if (!$refs.kebabTrigger?.contains($event.target)) open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     :style="{ position: 'fixed', top: (pos.bottom + 8) + 'px', left: pos.left + 'px' }"
                     class="p-1.5 rounded-xl z-40 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
                     role="menu"
                     aria-label="ابزارهای بیشتر">
                    <button type="button"
                            wire:key="project-kebab-pattern"
                            x-show="$wire.activeTab === 'teamChat'"
                            x-on:click="open = false; $dispatch('open-pattern', { bottom: $refs.kebabTrigger.getBoundingClientRect().bottom, left: $refs.kebabTrigger.getBoundingClientRect().left })"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                        <span class="material-symbols-rounded text-[16px]">texture</span>
                        <span>پیش زمینه چت</span>
                    </button>
                    <button type="button"
                            wire:key="project-kebab-maximize"
                            x-on:click="open = false; toggleMaximize()"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                        <span class="material-symbols-rounded text-[16px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
                        <span x-text="max ? 'کوچک کردن' : 'بزرگ کردن'"></span>
                    </button>
                    @if($isOwner)
                        <button type="button"
                                wire:key="project-kebab-edit"
                                x-on:click="open = false; $wire.openEdit()"
                                class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                            <span class="material-symbols-rounded text-[16px]">edit</span>
                            <span>ویرایش پروژه</span>
                        </button>
                    @endif
                    <button type="button"
                            wire:key="project-kebab-declutter"
                            x-on:click="open = false; toggleHeaderDeclutter()"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                        <span class="material-symbols-rounded text-[16px] rotate-90">unfold_less</span>
                        <span>خلوت‌سازی</span>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
