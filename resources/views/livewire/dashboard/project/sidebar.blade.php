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
                    <span class="flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold tabular-nums bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]" title="دعوت‌های در انتظار">
                        {{ count($this->pendingInviteProjects) }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-1.5">
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

        <div class="relative mt-3" wire:ignore.self>
            <span class="material-symbols-rounded absolute right-3 top-1/2 -translate-y-1/2 text-[16px] text-[var(--md-sys-color-on-surface-variant)] pointer-events-none">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجوی پروژه..."
                   aria-label="جستجوی پروژه"
                   class="md3-input w-full rounded-xl text-sm outline-none focus:ring-2 pr-9 pl-9 h-10 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 placeholder-transparent"
                   autocomplete="off" spellcheck="false"/>
            @if($search !== '')
                <button type="button" title="حذف" aria-label="حذف" wire:click="$set('search', '')"
                        class="absolute inset-y-0 left-0 pl-3 flex items-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                    <span class="material-symbols-rounded text-[18px]">close</span>
                </button>
            @endif
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

                <x-ui.row-actions :id="$project['id']" scope="project" pin-noun="پروژه" mute-noun="پروژه"/>

                <span class="relative flex-shrink-0">
                    <span @class([
                            'w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold select-none shadow-sm ring-1',
                            'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] ring-[var(--md-sys-color-tertiary)]' => $activeProjectId === $project['id'],
                            'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]' => $activeProjectId !== $project['id'],
                        ])>{{ mb_substr($project['name'], 0, 1) }}</span>
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
