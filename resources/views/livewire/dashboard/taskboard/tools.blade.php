<div class="relative flex w-full flex-col gap-3">
    <section class="relative z-30 {{ $presenter->toolbarSurface() }} transition-all duration-300"
             :class="headerDeclutter ? 'h-0! py-0! overflow-hidden! pointer-events-none! shadow-none!' : ''">
        <div class="flex w-full flex-col">
            <div class="flex w-full items-center justify-end px-3 py-2 lg:px-4">
                    <x-ui.table.tools :maximize="false" class="!w-auto">
                        <x-ui.table.density-toggle/>

                        <x-ui.table.sort-popover :options="['created' => 'تاریخ ایجاد', 'updated' => 'آخرین بروزرسانی', 'deadline' => 'مهلت', 'priority' => 'اولویت', 'title' => 'عنوان']"
                                                 :sort="$sort" :sortDir="$sortDir" :isDefault="$sort === ''"/>

                        <x-ui.table.col-toggle :scope="'taskboard'" icon="dashboard_customize" title="نمایش و مخفی کردن بخش‌های کارت" heading="بخش‌های کارت" :columns="$presenter->cardFieldOptions()"/>

                        <x-ui.forms.date-span-popover :active="$this->dateSpanActive()" :currentBasis="$dateBasis"/>

                        <span aria-hidden="true" class="mx-1 h-4 w-px shrink-0 bg-[var(--md-sys-color-outline-variant)]/40 max-sm:hidden"></span>

                        <button type="button"
                                wire:click="toggleSelectionMode"
                                title="انتخاب گروهی" aria-label="انتخاب گروهی" aria-pressed="{{ $selectionMode ? 'true' : 'false' }}"
                                class="inline-flex items-center justify-center gap-0.5 p-1 rounded-lg transition-colors normal-case {{ $selectionMode ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]' }}">
                            <span class="material-symbols-rounded text-[18px] leading-none">{{ $selectionMode ? 'close' : 'checklist' }}</span>
                            @if($selectionMode)
                                <span wire:key="taskboard-selection-count" class="text-[10px] font-bold leading-none">{{ convertToPersian(count($selectedTasks ?? [])) }}</span>
                            @endif
                        </button>

                        <button type="button"
                                wire:click="exportTasks" wire:loading.attr="disabled" wire:target="exportTasks"
                                title="دریافت خروجی وظایف" aria-label="دریافت خروجی وظایف"
                                class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]">
                            <span class="material-symbols-rounded text-[18px] leading-none">download</span>
                        </button>

                        <a href="{{ route('tasksheet') }}" target="_blank" rel="noopener noreferrer"
                           title="گزارش تسک‌شیت" aria-label="گزارش تسک‌شیت"
                           class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]">
                            <span class="material-symbols-rounded text-[18px] leading-none">assignment_turned_in</span>
                        </a>

                        <span aria-hidden="true" class="mx-1 h-4 w-px shrink-0 bg-[var(--md-sys-color-outline-variant)]/40 max-sm:hidden"></span>

                        <button type="button"
                                wire:click="switchTab('{{ $this->isMyTasks ? 'assigned-tasks' : 'my-tasks' }}')"
                                title="{{ $this->isMyTasks ? 'نمایش وظایف محول‌شده' : 'نمایش وظایف من' }}"
                                aria-label="{{ $this->isMyTasks ? 'نمایش وظایف محول‌شده' : 'نمایش وظایف من' }}"
                                class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case {{ $this->isMyTasks ? 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]' : 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' }}">
                            <span class="material-symbols-rounded text-[18px] leading-none">{{ $this->isMyTasks ? 'person' : 'assignment_ind' }}</span>
                        </button>

                        <button type="button"
                                @click="toggleFavoritesOnly()" :aria-pressed="showFavoritesOnly"
                                :title="showFavoritesOnly ? 'نمایش همهٔ وظایف' : 'نمایش فقط پین‌شده‌ها'"
                                :aria-label="showFavoritesOnly ? 'نمایش همهٔ وظایف' : 'نمایش فقط پین‌شده‌ها'"
                                :class="showFavoritesOnly ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
                                class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
                            <span class="material-symbols-rounded text-[18px] leading-none" :class="{ 'font-fill': showFavoritesOnly }">push_pin</span>
                        </button>
                    </x-ui.table.tools>
            </div>

            <x-ui.forms.filters placeholder="جستجو در وظایف..." searchModel="search"
                                activeCondition="this.$wire.get('activeFilterCount') > 0"
                                clearAction="this.$wire.call('clearFilters'); this.$wire.set('search', '')"
                                :declutterHide="true">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 px-1 py-2">
                    <div class="min-w-0">
                        <x-ui.forms.select
                            label="مهلت"
                            name="deadlineFilter"
                            wire:model.live="deadlineFilter"
                            icon="schedule"
                        >
                            @php($deadlineOptions = $presenter->deadlineFilterOptions($this->deadlineFilterCounts))

                            <option value="">همه مهلت‌ها</option>

                            @foreach($deadlineOptions as $opt)
                                <option value="{{ $opt['value'] }}">
                                    {{ $opt['label'] }}@if($opt['count'] > 0) ({{ convertToPersian($opt['count']) }})@endif
                                </option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.forms.select
                            label="پروژه"
                            name="projectFilter"
                            wire:model.live="projectFilter"
                            icon="workspaces"
                        >
                            <option value="">همه پروژه‌ها</option>

                            @foreach($this->projectOptions as $id => $name)
                                <option value="{{ $id }}">
                                    {{ $name }}
                                </option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.forms.select
                            label="اولویت"
                            name="priorityFilter"
                            wire:model.live="priorityFilter"
                            icon="flag"
                        >
                            <option value="">همه اولویت‌ها</option>

                            @foreach(\App\Filament\Resources\TaskResource\Enums\TaskPriority::cases() as $p)
                                <option value="{{ $p->value }}">
                                    {{ $p->getLabel() }}
                                </option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.forms.select
                            label="جوابگو"
                            name="responsibleFilter"
                            wire:model.live="responsibleFilter"
                            icon="support_agent"
                        >
                            <option value="">همه جوابگوها</option>

                            @foreach($staffMembers as $staff)
                                <option value="{{ $staff['id'] }}">
                                    {{ $staff['full_name'] }}
                                </option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>

                    <div class="min-w-0">
                        <x-ui.forms.select
                            label="دپارتمان"
                            name="departmentFilter"
                            wire:model.live="departmentFilter"
                            icon="corporate_fare"
                        >
                            <option value="">همه دپارتمان‌ها</option>

                            @foreach($departmentOptions as $code => $label)
                                <option value="{{ $code }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-ui.forms.select>
                    </div>

                    @if(count($this->assigneeOptions))
                        <div wire:key="taskboard-filter-assignee" class="min-w-0">
                            <x-ui.forms.select
                                label="مسئول انجام"
                                name="assigneeFilter"
                                wire:model.live="assigneeFilter"
                                icon="assignment_ind"
                            >
                                <option value="">همه مسئول‌ها</option>

                                @foreach($this->assigneeOptions as $id => $name)
                                    <option value="{{ $id }}">
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    @endif

                    @if(count($this->labelOptions))
                        <div
                            wire:key="taskboard-filter-labels"
                            class="relative min-w-0 md3-input-group"
                            x-data="{ open: false }"
                            @click.away="open = false"
                        >
                            <button
                                type="button"
                                @click="open = !open"
                                :aria-expanded="open"
                                class="md3-input flex w-full appearance-none items-center pr-10 text-right text-sm"
                            >
                                <span class="truncate">
                                    {{ count($labelFilter) ? 'برچسب‌ها (' . count($labelFilter) . ')' : 'برچسب‌ها' }}
                                </span>
                            </button>

                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-[var(--md-sys-color-on-surface-variant)]">
                                <span class="material-symbols-rounded text-[20px]">
                                    sell
                                </span>
                            </div>

                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[var(--md-sys-color-on-surface-variant)]">
                                <span
                                    class="material-symbols-rounded text-[18px]"
                                    :class="{ 'rotate-180': open }"
                                >
                                    expand_more
                                </span>
                            </div>

                            <div
                                x-show="open"
                                x-cloak
                                class="absolute z-40 mt-1 w-full overflow-hidden rounded-lg border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-high)] shadow-xl"
                            >
                                <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                    @foreach($this->labelOptions as $label)
                                        @php($isChecked = in_array($label, $labelFilter, true))

                                        <button
                                            type="button"
                                            wire:click="toggleLabelFilter({{ \Illuminate\Support\Js::from($label) }})"
                                            @class([
                                                'flex w-full items-center gap-2 px-3 py-2.5 text-right text-xs',
                                                'bg-[var(--md-sys-color-primary-container)]/50 text-[var(--md-sys-color-on-surface)] font-medium' => $isChecked,
                                                'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container)]' => !$isChecked,
                                            ])
                                        >
                                            <span class="material-symbols-rounded text-[17px]">
                                                {{ $isChecked ? 'check_box' : 'check_box_outline_blank' }}
                                            </span>

                                            <span class="min-w-0 truncate" dir="auto">
                                                {{ $label }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(count($this->schemeOptions))
                        <div wire:key="taskboard-filter-scheme" class="min-w-0">
                            <x-ui.forms.select
                                label="طرح"
                                name="schemeFilter"
                                wire:model.live="schemeFilter"
                                icon="schema"
                            >
                                <option value="">همه طرح‌ها</option>

                                @foreach($this->schemeOptions as $scheme)
                                    <option value="{{ $scheme }}">
                                        {{ $scheme }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    @endif

                    @if(count($this->unitOptions))
                        <div wire:key="taskboard-filter-unit" class="min-w-0">
                            <x-ui.forms.select
                                label="واحد"
                                name="unitFilter"
                                wire:model.live="unitFilter"
                                icon="account_tree"
                            >
                                <option value="">همه واحدها</option>

                                @foreach($this->unitOptions as $unit)
                                    <option value="{{ $unit }}">
                                        {{ $unit }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    @endif

                    @if(count($this->sectionOptions))
                        <div wire:key="taskboard-filter-section" class="min-w-0">
                            <x-ui.forms.select
                                label="بخش"
                                name="sectionFilter"
                                wire:model.live="sectionFilter"
                                icon="segment"
                            >
                                <option value="">همه بخش‌ها</option>

                                @foreach($this->sectionOptions as $section)
                                    <option value="{{ $section }}">
                                        {{ $section }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    @endif

                    @if(count($this->actionSourceDomainOptions))
                        <div wire:key="taskboard-filter-source-domain" class="min-w-0">
                            <x-ui.forms.select
                                label="حوزهٔ منبع اقدام"
                                name="actionSourceDomainFilter"
                                wire:model.live="actionSourceDomainFilter"
                                icon="hub"
                            >
                                <option value="">همه حوزه‌ها</option>

                                @foreach($this->actionSourceDomainOptions as $domain)
                                    <option value="{{ $domain }}">
                                        {{ $domain }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    @endif

                    @if(count($this->actionSourceOptions))
                        <div wire:key="taskboard-filter-source" class="min-w-0">
                            <x-ui.forms.select
                                label="منبع اقدام"
                                name="actionSourceFilter"
                                wire:model.live="actionSourceFilter"
                                icon="input"
                            >
                                <option value="">همه منابع</option>

                                @foreach($this->actionSourceOptions as $source)
                                    <option value="{{ $source }}">
                                        {{ $source }}
                                    </option>
                                @endforeach
                            </x-ui.forms.select>
                        </div>
                    @endif
                </div>
            </x-ui.forms.filters>
        </div>
    </section>

    <button type="button" x-on:click="toggleHeaderDeclutter()" :aria-pressed="headerDeclutter"
            :aria-label="headerDeclutter ? 'نمایش نوار ابزار' : 'خلوت‌سازی سربرگ'"
            :title="headerDeclutter ? 'نمایش نوار ابزار' : 'خلوت‌سازی سربرگ'"
            class="absolute top-2 md:-top-8 end-5 z-30 h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all duration-200 ease-out active:scale-95"
            :class="headerDeclutter ? 'flex' : 'hidden'">
        <span class="material-symbols-rounded text-base rotate-90">unfold_more</span>
    </button>

    <div
        x-show="!swipeHintDismissed"
        x-cloak
        @click="dismissSwipeHint()"
        role="button"
        aria-label="بستن راهنما"
        class="mx-auto flex w-max cursor-pointer items-center gap-2 rounded-xl shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-shadow)_4%,transparent)] bg-[var(--md-sys-color-surface-container)] px-3.5 py-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] md:hidden"
    >
        <span class="material-symbols-rounded text-[14px]">
            chevron_right
        </span>

        <span>
            برای دیدن سایر ستون‌ها بکشید
        </span>

        <span class="material-symbols-rounded text-[14px]">
            chevron_left
        </span>
    </div>
</div>
