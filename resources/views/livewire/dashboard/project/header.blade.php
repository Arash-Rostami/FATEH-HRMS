@php
    $presenter = new \App\Livewire\Dashboard\Project\Presentation\ProjectPresenter();
    $project = $this->activeProject;
    $summary = $this->reportSummary;
    $memberCount = count($project->member_ids ?? []) + 1;
    $riskChips = $presenter->headerRiskChips($project, $summary);
    $settingsSummary = $project->settingsSummary();
    $otherSettings = $project->otherSettings();
@endphp

<div class="flex items-start gap-3">
    <button x-on:click="backToList()" aria-label="بازگشت به فهرست پروژه‌ها"
            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] active:scale-95 md:hidden">
        <span class="material-symbols-rounded text-base">arrow_forward</span>
    </button>

    <div class="w-12 h-12 rounded-2xl overflow-hidden flex items-center justify-center flex-shrink-0 shadow-sm ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]"
         style="background: color-mix(in srgb, var(--md-sys-color-primary) 14%, transparent);">
        @if($project->owner?->getProfileImageUrl())
            <x-ui.avatar :existingImage="$project->owner->getProfileImageUrl()" :alt="$project->owner->name" class="w-full h-full object-cover"/>
        @else
            <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)] font-fill">workspaces</span>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="truncate text-lg font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $project->name }}</h2>
            <x-ui.buttons.copy :text="route('projects', ['open' => $project->id, 'tab' => $activeTab])" message="لینک پروژه کپی شد"/>
        </div>

        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-[var(--md-sys-color-on-surface-variant)]">
            <span class="flex items-center gap-1">
                <span class="material-symbols-rounded text-[13px]">person</span>
                {{ $project->owner?->name ?? '—' }}
            </span>
            <span class="h-1 w-1 rounded-full bg-current opacity-40"></span>
            <div class="relative" x-data="{ open: false }">
                <button type="button" x-on:click="open = !open" x-on:click.outside="open = false"
                        class="flex items-center gap-1 hover:text-[var(--md-sys-color-primary)] transition-colors">
                    <span class="material-symbols-rounded text-[13px]">group</span>
                    {{ $memberCount }} عضو
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute top-full mt-1.5 right-0 z-40 w-64 max-h-72 overflow-y-auto custom-scrollbar p-1.5 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]">
                    @foreach($this->projectMembers as $member)
                        <div class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors">
                            <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
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
            <span class="h-1 w-1 rounded-full bg-current opacity-40"></span>
            <span class="flex items-center gap-1">
                <span class="material-symbols-rounded text-[13px]">task_alt</span>
                {{ $summary['done'] }} از {{ $summary['total'] }}
            </span>
            @foreach($riskChips as $chip)
                <span class="h-1 w-1 rounded-full bg-current opacity-40"></span>
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold border {{ $chip['classes'] }}">
                    <span class="material-symbols-rounded text-[11px]">{{ $chip['icon'] }}</span>
                    {{ $chip['text'] }}
                </span>
            @endforeach
        </div>

        @if($settingsSummary !== '—')
            <p class="mt-1 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70 truncate">{{ $settingsSummary }}</p>
        @endif

        @if(!empty($otherSettings))
            <div class="mt-1 flex flex-wrap items-center gap-0.5">
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

    <div class="flex items-center gap-1.5 flex-shrink-0">
        @if($activeTab === 'teamChat')
            <button type="button" @click="toggleHighlight()" aria-label="پیش زمینه چت" title="پیش زمینه چت"
                    :class="isHighlighted ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]'"
                    class="flex items-center justify-center w-9 h-9 rounded-xl transition-colors">
                <span class="material-symbols-rounded text-lg" x-text="isHighlighted ? 'hide_image' : 'texture'"></span>
            </button>
        @endif

        <button type="button" @click="toggleMaximize()"
                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': max }"
                class="flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
            <span class="material-symbols-rounded text-lg" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
        </button>

        @if($project->owner_id === auth()->id())
            <button type="button" x-on:click="$wire.openEdit()" title="ویرایش پروژه"
                    class="flex items-center justify-center w-9 h-9 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                <span class="material-symbols-rounded text-lg">edit</span>
            </button>
        @endif
    </div>
</div>
