<div dir="rtl" x-data="project()"
     x-on:keydown.escape.window="if(max) toggleMaximize()"
     x-on:project-open-task.window="$wire.openProjectTask($event.detail.taskId)"
     x-on:project-open-task-create.window="$wire.openCreateModal()"
     x-on:project-duplicate-task.window="$wire.duplicateTask($event.detail.taskId)"
     x-on:project-switch-tab.window="$wire.switchTab($event.detail.tab)"
     class="w-full h-[calc(100dvh-60px)] md:h-[calc(100dvh-80px)] relative px-4 py-4 md:px-6 md:py-8 overflow-hidden animate-fade"
    >

    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col">
        <x-ui.title icon="workspaces" title="پروژه‌ها" :count="$this->myProjects['total']" countLabel="پروژه">
            <x-slot:actions>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'project-badge-legend' })"
                    title="راهنمای نشانگر اعلان"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'project-legend' })"
                    title="راهنمای پروژه‌ها"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="project-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('projects-controller'), \App\Services\Menu\BadgeLegendCatalog::get('projects-controller:edge')]"
        />

        <x-ui.modals.dialog name="project-legend" title="راهنمای پروژه‌ها">
            @include('livewire.dashboard.project.legend')
        </x-ui.modals.dialog>

        @include('components.dashboard.header.focus-chip')

        <x-ui.buttons.tab-selector
            active-tab="projects"
            :tabs="[
                ['id' => 'projects', 'label' => 'پروژه‌ها', 'icon' => 'workspaces', 'route' => route('projects')],
                ['id' => 'tasks', 'label' => 'برد وظایف', 'icon' => 'dashboard', 'route' => route('tasks')],
            ]"
        />

        <x-ui.modals.max-backdrop/>

        <div class="chat-widget flex-1 min-h-0" :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }">

            @island(name: 'sidebar', always: true)
                @include('livewire.dashboard.project.sidebar')
            @endisland

            @island(name: 'workspace', always: true)
                <main class="flex-1 flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] relative bg-[var(--md-sys-color-background)] md:flex"
                      :class="{ 'hidden': !mobileShowChat }">
                    <div wire:ignore x-show="openingProject" x-cloak x-transition.opacity
                         class="absolute inset-0 z-30 bg-[var(--md-sys-color-surface-container-lowest)]/50 flex items-center justify-center">
                        <x-ui.loaders.spin-badge text="در حال باز کردن پروژه..."/>
                    </div>
                    @if($this->activeProject)
                        <div wire:key="pane-active" class="contents">
                        <div class="relative z-20 flex-shrink-0 border-b px-3 md:px-5 py-3 transition-all duration-300 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]">
                            @include('livewire.dashboard.project.header')
                        </div>

                        <div class="flex-1 min-h-0 flex flex-col overflow-hidden p-4 md:p-6">
                            <div class="flex items-center gap-1.5 sm:gap-2 mb-4 sm:mb-6 flex-shrink-0">
                                <x-ui.buttons.tab-selector
                                    :active-tab="$activeTab"
                                    has-a11y
                                    fast-switch
                                    :warm-ms="150"
                                    class="!mb-0"
                                    :tabs="[
                                        ['id' => 'workflow', 'icon' => 'conversion_path', 'label' => 'چرخهٔ کاری'],
                                        ['id' => 'activity', 'icon' => 'timeline', 'label' => 'فعالیت‌ها و نظرات', 'description' => 'رویدادهای سیستمی وظایف به‌همراه نظرات باز؛ قابل مشاهده و مشارکت برای همهٔ اعضای پروژه', 'unread' => $this->tabDirty['activity'] ? 1 : 0],
                                        ['id' => 'teamChat', 'icon' => 'forum', 'label' => 'چت زندهٔ تیم', 'description' => 'پیام‌رسانی آنی بین همهٔ اعضای پروژه، مثل یک گروه گفتگوی گروهی', 'unread' => $this->tabDirty['teamChat'] ? 1 : 0],
                                        ['id' => 'projectCalendar', 'icon' => 'calendar_month', 'label' => 'تقویم', 'unread' => $this->tabDirty['projectCalendar'] ? 1 : 0],
                                        ['id' => 'kanban', 'icon' => 'view_kanban', 'label' => 'برد وظایف'],
                                        ['id' => 'report', 'icon' => 'summarize', 'label' => 'گزارش'],
                                        ['id' => 'analytics', 'icon' => 'monitoring', 'label' => 'تحلیل‌ها'],
                                    ]"
                                />

                                @if($activeTab === 'teamChat' && $this->activeChannel)
                                    <a wire:key="project-open-channel" href="{{ route('channels', ['open' => $this->activeChannel->id]) }}" target="_blank" rel="noopener noreferrer"
                                       title="مشاهده در گروه کامل (پیوست فایل، ویرایش، حذف، پاسخ و امکانات بیشتر)"
                                       class="ms-auto flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-xl shadow-sm bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] hover:brightness-110 transition-colors">
                                        <span class="material-symbols-rounded text-base">open_in_new</span>
                                    </a>
                                @endif

                                @if($activeTab === 'kanban')
                                    <a wire:key="project-open-taskboard" href="{{ route('tasks', array_filter(['project' => $this->activeProjectId, 'open' => $editingTaskId])) }}" target="_blank" rel="noopener noreferrer"
                                       title="مشاهده در برد کامل (آرشیو، محول‌کردن، حذف و امکانات بیشتر)"
                                       class="ms-auto flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-xl shadow-sm bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] hover:brightness-110 transition-colors">
                                        <span class="material-symbols-rounded text-base">open_in_new</span>
                                    </a>
                                @endif
                            </div>

                            <div @class(['flex-1 min-h-0 flex flex-col', 'hidden' => $activeTab !== 'teamChat'])>
                                <livewire:dashboard.project.team-chat wire:key="team-chat-{{ $activeProjectId }}" :active-project-id="$activeProjectId" :initial-active="$activeTab === 'teamChat'" />
                            </div>

                            @if(isset($this->tabs[$activeTab]))
                                <div id="project-tab-pane" wire:key="project-tab-pane" @scroll.passive="$store.activityReactionPicker.close()" class="no-anchor flex-1 min-h-0 overflow-y-auto overscroll-contain custom-scrollbar">
                                    <livewire:dynamic-component
                                        :component="$this->tabs[$activeTab]['component']"
                                        :wire:key="$this->tabs[$activeTab]['key']"
                                        :lazy="$this->tabs[$activeTab]['lazy']"
                                        :active-project-id="$activeProjectId"
                                    />
                                </div>
                            @endif
                        </div>
                        </div>
                    @else
                        <div wire:key="pane-empty" class="contents">
                            <x-ui.empty icon="workspaces" title="پروژه‌ای انتخاب نشده" description="یک پروژه از فهرست کنار انتخاب کنید یا پروژهٔ جدیدی بسازید." fill/>
                        </div>
                    @endif
                </main>
            @endisland
        </div>
    </div>

    @include('livewire.dashboard.project.form')

    @include('livewire.dashboard.taskboard.form', ['presenter' => $taskBoardPresenter])
</div>
