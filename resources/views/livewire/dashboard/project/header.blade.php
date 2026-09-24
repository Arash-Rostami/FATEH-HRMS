@php
    $project = $this->activeProject;
    $summary = $this->reportSummary;
    $memberCount = count($project->member_ids ?? []) + 1;
    $riskChips = $this->presenter->headerRiskChips($project, $summary);
    $settingsSummary = $project->settingsSummary();
    $otherSettings = $project->otherSettings();
@endphp

<div class="flex items-start gap-2 md:gap-4">
    <button x-on:click="backToList()" aria-label="بازگشت به فهرست پروژه‌ها" x-show="!max"
            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95 md:hidden">
        <span class="material-symbols-rounded text-base">arrow_forward</span>
    </button>

    @php($badge = $this->presenter->badgeColors($project->id))
    <div class="h-11 w-11 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm ring-1 text-xl font-bold select-none {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['ring'] }}">
        {{ mb_substr($project->name, 0, 1) }}
    </div>

    <div class="min-w-0 flex-1">
        <div class="hidden md:flex flex-wrap items-center gap-2">
            <h2 class="truncate text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $project->name }}</h2>
            <x-ui.buttons.copy :text="route('projects', ['open' => $project->id, 'tab' => $activeTab])" message="لینک پروژه کپی شد"/>
        </div>

        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-[var(--md-sys-color-on-surface-variant)]">
            <span class="hidden md:flex items-center gap-1">
                <span class="material-symbols-rounded text-[13px]">person</span>
                {{ $project->owner?->name ?? '—' }}
            </span>
            <span class="hidden md:inline h-1 w-1 rounded-full bg-current opacity-40"></span>
            <div class="relative hidden md:block" x-data="{ open: false }">
                <button type="button" x-on:click="open = !open" x-on:click.outside="open = false"
                        class="flex items-center gap-1 hover:text-[var(--md-sys-color-primary)] transition-colors">
                    <span class="material-symbols-rounded text-[13px]">group</span>
                    {{ convertToPersian($memberCount) }} عضو
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute top-full mt-1.5 right-0 z-40 w-64 max-h-72 overflow-y-auto custom-scrollbar p-1.5 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]">
                    @foreach($this->projectMembers as $member)
                        <div class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors">
                            <div class="w-8 h-8 rounded-md overflow-hidden flex-shrink-0 ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
                                <x-ui.avatar :existingImage="$member['avatar_url']" :alt="$member['name']" class="w-full h-full object-cover"/>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $member['name'] }}</span>
                                    @if($member['is_owner'])
                                        <span class="text-[9px] font-bold px-1 py-0.5 rounded-md bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">مالک</span>
                                    @endif
                                    @if($member['presence_label'])
                                        <span class="inline-flex items-center px-1 py-0.5 rounded-full text-[9px] font-bold {{ $member['presence_class'] }}">{{ $member['presence_label'] }}</span>
                                    @endif
                                    @if($member['occasion'])
                                        <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded-md text-[9px] font-bold {{ $member['occasion']['chip'] }}">
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
            </div>
            <span class="hidden md:inline h-1 w-1 rounded-full bg-current opacity-40"></span>
            <span class="flex items-center gap-1">
                <span class="material-symbols-rounded text-[13px]">task_alt</span>
                {{ convertToPersian($summary['done']) }} از {{ convertToPersian($summary['total']) }}
            </span>
            @foreach($riskChips as $chip)
                <span class="hidden md:inline h-1 w-1 rounded-full bg-current opacity-40"></span>
                <span class="hidden md:inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold border {{ $chip['classes'] }}">
                    <span class="material-symbols-rounded text-[11px]">{{ $chip['icon'] }}</span>
                    {{ $chip['text'] }}
                </span>
            @endforeach
        </div>

        @if($settingsSummary !== '—')
            <p class="mt-1 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70 truncate">{{ $settingsSummary }}</p>
        @endif

        @if(!empty($otherSettings))
            <div class="mt-1 hidden md:flex flex-wrap items-center gap-0.5">
                @foreach($otherSettings as $key => $value)
                    <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded-md text-[9px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]"
                          title="{{ $key }}: {{ $value }}">
                        <span class="material-symbols-rounded text-[10px]">tune</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $key }}: {{ $value }}</span>
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-1">
        @if($activeTab === 'teamChat')
            <div wire:key="project-header-pattern-picker" class="contents">
                @include('livewire.dashboard.messaging.pattern-picker')
            </div>
        @endif

        <button type="button" @click="toggleMaximize()"
                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                class="h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                :class="[max ? 'flex' : 'hidden md:flex', max ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]']">
            <span class="material-symbols-rounded text-base" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
        </button>

        <x-dashboard.reminder-trigger :for="$project"/>

        @if($project->owner_id === auth()->id())
            <button type="button" wire:key="project-header-edit" x-on:click="$wire.openEdit()" title="ویرایش پروژه"
                    class="hidden md:flex items-center justify-center h-8 w-8 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                <span class="material-symbols-rounded text-base">edit</span>
            </button>
        @endif

        <div x-data="{ open: false, pos: {} }">
            <button type="button" x-ref="kebabTrigger"
                    x-on:click="pos = $refs.kebabTrigger.getBoundingClientRect().toJSON(); open = !open"
                    aria-label="ابزارهای بیشتر" title="ابزارهای بیشتر"
                    class="md:hidden flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out active:scale-95">
                <span class="material-symbols-rounded text-base">more_horiz</span>
            </button>
            <template x-teleport="body">
                <div x-show="open" x-cloak dir="rtl"
                     x-on:click.away="if (!$refs.kebabTrigger?.contains($event.target)) open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     :style="{ position: 'fixed', top: (pos.bottom + 8) + 'px', left: pos.left + 'px' }"
                     class="p-1.5 rounded-xl z-40 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
                     role="menu" aria-label="ابزارهای بیشتر">
                    <button type="button" wire:key="project-kebab-pattern"
                            x-show="$wire.activeTab === 'teamChat'"
                            x-on:click="open = false; $dispatch('open-pattern', { bottom: $refs.kebabTrigger.getBoundingClientRect().bottom, left: $refs.kebabTrigger.getBoundingClientRect().left })"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                        <span class="material-symbols-rounded text-[16px]">texture</span>
                        <span>پیش زمینه چت</span>
                    </button>
                    <button type="button" wire:key="project-kebab-maximize"
                            x-on:click="open = false; toggleMaximize()"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                        <span class="material-symbols-rounded text-[16px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
                        <span x-text="max ? 'کوچک کردن' : 'بزرگ کردن'"></span>
                    </button>
                    @if($project->owner_id === auth()->id())
                        <button type="button" wire:key="project-kebab-edit"
                                x-on:click="open = false; $wire.openEdit()"
                                class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                            <span class="material-symbols-rounded text-[16px]">edit</span>
                            <span>ویرایش پروژه</span>
                        </button>
                    @endif
                </div>
            </template>
        </div>
    </div>
</div>
