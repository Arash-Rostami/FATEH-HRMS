@php
    $allProjectIds = collect($this->myProjects['rows'])->pluck('id')->map(fn($id) => (int) $id)->values()->toJson();
@endphp
<aside class="flex-shrink-0 flex flex-col border-l overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] md:flex w-full md:w-[320px] lg:w-[360px] bg-[var(--md-sys-color-surface)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"
       :class="{ 'hidden': mobileShowChat }"
       aria-label="لیست پروژه‌ها">

    <div class="flex-shrink-0 px-4 pt-4 pb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <span class="material-symbols-rounded text-[18px] font-fill">workspaces</span>
                </div>
                <div>
                    <h1 class="text-sm font-semibold tracking-tight text-[var(--md-sys-color-on-surface)]">پروژه‌ها</h1>
                    <p class="text-[10px] text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]">فضای‌کاری تیمی</p>
                </div>
                @if(count($this->pendingInviteProjects))
                    <span class="flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]" title="دعوت‌های در انتظار">
                        {{ convertToPersian(count($this->pendingInviteProjects)) }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-1.5">
                @if(count($this->myProjects['rows']))
                    <button type="button"
                            x-on:click="$store.sound.toggleAll({{ $allProjectIds }}, 'project')"
                            :aria-pressed="$store.sound.isAllMuted({{ $allProjectIds }}, 'project')"
                            :aria-label="$store.sound.isAllMuted({{ $allProjectIds }}, 'project') ? 'باصدا کردن همه پروژه‌ها' : 'بی‌صدا کردن همه پروژه‌ها'"
                            :title="$store.sound.isAllMuted({{ $allProjectIds }}, 'project') ? 'باصدا کردن همه پروژه‌ها' : 'بی‌صدا کردن همه پروژه‌ها'"
                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] active:scale-90"
                            :class="$store.sound.isAllMuted({{ $allProjectIds }}, 'project') ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
                        <span class="material-symbols-rounded text-[18px]" x-text="$store.sound.isAllMuted({{ $allProjectIds }}, 'project') ? 'volume_off' : 'volume_up'"></span>
                    </button>
                @endif
                <a href="{{ route('tasksheet') }}" target="_blank" rel="noopener noreferrer" title="تسک‌شیت" aria-label="تسک‌شیت"
                   class="w-8 h-8 rounded-lg flex items-center justify-center transition-all bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] active:scale-90">
                    <span class="material-symbols-rounded text-[18px]">assignment_turned_in</span>
                </a>
                <button type="button" x-on:click="$wire.openCreate()" title="ایجاد پروژه جدید" aria-label="ایجاد پروژه جدید"
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-all bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] active:scale-90">
                    <span class="material-symbols-rounded text-[18px]">add</span>
                </button>
            </div>
        </div>

        <div class="mt-3">
            <x-ui.forms.search-field model="search" name="search" id="search" debounce="200"
                                     placeholder="جستجوی پروژه..." aria-label="جستجوی پروژه" overlay-title="جستجوی پروژه"
                                     show-label />
        </div>
    </div>

    <div class="h-px mx-4 flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"></div>

    <div class="flex flex-col overflow-y-auto py-1 contact-scrollbar" role="listbox" aria-label="فهرست پروژه‌ها">
        @forelse($this->myProjects['rows'] as $project)
            <div wire:key="project-row-{{ $project['id'] }}" x-data="{ tagOpen: false }"
                    x-on:click="selectProject({{ $project['id'] }})"
                    x-on:keydown.enter.prevent="selectProject({{ $project['id'] }})"
                    x-on:keydown.space.prevent="selectProject({{ $project['id'] }})"
                    data-rf="project-{{ $project['id'] }}"
                    role="option" tabindex="0" aria-selected="{{ $activeProjectId === $project['id'] ? 'true' : 'false' }}"
                    :style="{ order: $store.pinned.isPinned({{ $project['id'] }}, 'project') ? -1 : 0 }"
                    @class([
                        'group ripple-effect relative isolate w-full shrink-0 flex items-center gap-3 px-4 py-2.5 text-right transition-all duration-200 cursor-pointer rounded-md',
                        'bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_40%,transparent)] border-r-2 border-[var(--md-sys-color-primary)]' => $activeProjectId === $project['id'],
                        'hover:bg-[var(--md-sys-color-surface-variant)]' => $activeProjectId !== $project['id'],
                    ])>

                <x-ui.row-actions :id="$project['id']" scope="project" pin-noun="پروژه" mute-noun="پروژه" :for="\App\Models\Project::make(['id' => $project['id']])"/>

                @php($badge = $this->presenter->badgeColors($project['id']))
                <span class="relative flex-shrink-0">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold select-none shadow-sm ring-1 {{ $badge['bg'] }} {{ $badge['text'] }} {{ $activeProjectId === $project['id'] ? $badge['ring'] : 'ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]' }}">{{ mb_substr($project['name'], 0, 1) }}</span>
                    @if($project['pending'])
                        <span class="absolute -top-0.5 -left-0.5 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] ring-2 ring-[var(--md-sys-color-surface)]" title="دعوت در انتظار"></span>
                    @endif
                </span>

                <div class="flex items-center gap-1.5 flex-1 min-w-0">
                    <span @class([
                            'truncate text-[13px] leading-tight',
                            'font-bold text-[var(--md-sys-color-on-surface)]' => $activeProjectId === $project['id'],
                            'font-medium text-[var(--md-sys-color-on-surface-variant)]' => $activeProjectId !== $project['id'],
                        ]) title="{{ $project['name'] }}" dir="auto">{{ $project['name'] }}</span>

                    <x-ui.decor.progress-ring :percent="$project['percent']" :size="24" :stroke="4" class="shrink-0"
                        :color="$project['percent'] >= 100 ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)'"/>

                    @if($project['workflowCount'] > 0)
                        <span wire:key="project-workflow-badge-{{ $project['id'] }}"
                              class="inline-flex items-center gap-0.5 h-[22px] px-1.5 rounded-md bg-[color-mix(in_srgb,var(--md-sys-color-secondary)_14%,transparent)] text-[var(--md-sys-color-secondary)] text-[10px] font-bold tabular-nums shrink-0"
                              title="{{ $project['workflowCount'] === 1 ? 'چرخهٔ کاری — مرحلهٔ '.convertToPersian($project['workflowStep']).' از '.convertToPersian($project['workflowTotal']) : convertToPersian($project['workflowCount']).' چرخهٔ کاری فعال' }}">
                            <span class="material-symbols-rounded text-[13px]">conversion_path</span>
                            {{ $project['workflowCount'] === 1 ? convertToPersian($project['workflowStep']).'/'.convertToPersian($project['workflowTotal']) : '×'.convertToPersian($project['workflowCount']) }}
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-4 py-6">
                @if($search !== '')
                    <x-ui.empty icon="search_off" title="چیزی یافت نشد" description="پروژه‌ای با این نام پیدا نشد." variant="search"/>
                @else
                    <x-ui.empty icon="workspaces" title="پروژه‌ای ندارید" description="با دکمهٔ بالا اولین پروژهٔ خود را بسازید." variant="list"/>
                @endif
            </div>
        @endforelse

        @if($this->myProjects['hasMore'])
            <x-ui.buttons.load-more action="loadMoreProjects" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                                     class="mx-auto my-2 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
        @endif
    </div>
</aside>
