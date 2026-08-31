<div class="flex w-full flex-col gap-3">
    <section class="{{ $presenter->toolbarSurface() }}">
        <div class="flex w-full flex-col">
            <div class="flex min-h-[64px] w-full items-center gap-3 px-3 py-2.5 lg:px-4">
                <div class="min-w-0 flex-1">
                    <x-ui.forms.search
                        model="search"
                        placeholder="جستجو در وظایف..."
                    />
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <x-ui.buttons.form
                        wire:click="openCreateModal"
                        aria-label="ایجاد وظیفه جدید"
                        icon="add"
                        class="{{ $presenter->toolbarButtonClass() }} whitespace-nowrap"
                    >
                        وظیفه جدید
                    </x-ui.buttons.form>

                    <x-ui.buttons.form
                        variant="tonal"
                        wire:click="switchTab('{{ $this->isMyTasks ? 'assigned-tasks' : 'my-tasks' }}')"
                        title="{{ $this->isMyTasks ? 'نمایش وظایف محول‌شده' : 'نمایش وظایف من' }}"
                        aria-label="{{ $this->isMyTasks ? 'نمایش وظایف محول‌شده' : 'نمایش وظایف من' }}"
                        class="{{ $presenter->toolbarButtonClass() }} whitespace-nowrap"
                    >
                        <span class="material-symbols-rounded text-[19px] leading-none">
                            {{ $this->isMyTasks ? 'person' : 'assignment_ind' }}
                        </span>

                        <span>
                            {{ $this->isMyTasks ? 'وظایف من' : 'محول شده' }}
                        </span>
                    </x-ui.buttons.form>
                </div>

                <div class="hidden h-7 w-px bg-[var(--md-sys-color-outline-variant)]/30 lg:block"></div>

                <div class="flex shrink-0 items-center gap-1.5">
                    <x-ui.buttons.form
                        variant="tonal"
                        size="icon"
                        @click="$store.density.toggle()"
                        x-bind:class="$store.density.compact ? '{{ $presenter->utilityClass(true) }}' : '{{ $presenter->utilityClass(false) }}'"
                        x-bind:title="$store.density.compact ? 'نمایش فشرده' : 'نمایش عادی'"
                        x-bind:aria-label="$store.density.compact ? 'نمایش فشرده' : 'نمایش عادی'"
                        class="{{ $presenter->toolbarButtonClass() }}"
                    >
                        <span
                            class="material-symbols-rounded text-[20px] leading-none"
                            x-text="$store.density.compact ? 'view_compact' : 'view_comfy'"
                        ></span>
                    </x-ui.buttons.form>

                    <x-ui.buttons.form
                        variant="{{ $selectionMode ? 'primary' : 'tonal' }}"
                        wire:click="toggleSelectionMode"
                        title="انتخاب گروهی"
                        aria-label="انتخاب گروهی"
                        class="{{ $presenter->toolbarButtonClass() }} whitespace-nowrap px-2.5"
                    >
                        <span class="material-symbols-rounded text-[20px] leading-none">
                            {{ $selectionMode ? 'close' : 'checklist' }}
                        </span>

                        @if($selectionMode)
                            <span class="ms-1 text-xs tabular-nums">
                                {{ count($selectedTasks ?? []) }}
                            </span>
                        @endif
                    </x-ui.buttons.form>

                    <x-ui.buttons.form
                        variant="tonal"
                        size="icon"
                        @click="toggleFavoritesOnly"
                        x-bind:class="showFavoritesOnly ? '{{ $presenter->utilityClass(true) }}' : '{{ $presenter->utilityClass(false) }}'"
                        title="فقط پین‌شده‌ها"
                        aria-label="فقط پین‌شده‌ها"
                        icon="push_pin"
                        class="{{ $presenter->toolbarButtonClass() }}"
                    />

                    <x-ui.buttons.form
                        variant="tonal"
                        size="icon"
                        wire:click="exportTasks"
                        title="دریافت خروجی وظایف"
                        aria-label="دریافت خروجی وظایف"
                        icon="download"
                        class="{{ $presenter->toolbarButtonClass() }}"
                    />

                    <a
                        href="{{ route('tasksheet') }}"
                        target="_blank"
                        title="گزارش تسک‌شیت"
                        aria-label="گزارش تسک‌شیت"
                        class="relative inline-flex items-center justify-center transition-all duration-200 transform active:scale-95 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] rounded-xl w-10 h-10 p-0 gap-0 {{ $presenter->toolbarButtonClass() }}"
                    >
                        <span class="material-symbols-rounded text-[20px] leading-none shrink-0">
                            assignment_turned_in
                        </span>
                    </a>

                    <span class="relative inline-flex">
                        <x-ui.buttons.form
                            variant="tonal"
                            size="icon"
                            @click="toggleFilters()"
                            x-bind:class="filtersOpen ? '{{ $presenter->utilityClass(true) }}' : '{{ $presenter->utilityClass(false) }}'"
                            title="فیلترهای بیشتر"
                            aria-label="فیلترهای بیشتر"
                            icon="tune"
                            class="{{ $presenter->toolbarButtonClass() }}"
                        />
                        @if($this->activeFilterCount > 0)
                            <span class="absolute -top-1 -left-1 flex h-4 w-4 min-w-[16px] items-center justify-center rounded-sm bg-[var(--md-sys-color-tertiary)] px-1 text-[9px] font-bold leading-none text-[var(--md-sys-color-on-tertiary)] shadow ring-2 ring-[var(--md-sys-color-surface)]">{{ $this->activeFilterCount > 9 ? '9+' : $this->activeFilterCount }}</span>
                        @endif
                    </span>

                    @if($this->activeFilterCount > 0)
                        <x-ui.buttons.form
                            variant="tonal"
                            size="icon"
                            wire:click="clearFilters"
                            title="پاک‌کردن همهٔ فیلترها"
                            aria-label="پاک‌کردن همهٔ فیلترها"
                            icon="filter_alt_off"
                            class="{{ $presenter->toolbarButtonClass() }}"
                        />
                    @endif
                </div>
            </div>

            <div
                x-show="filtersOpen"
                x-cloak
                class="border-t border-[var(--md-sys-color-outline-variant)]/15"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 px-3 py-4 lg:px-4">
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
                                    {{ $opt['label'] }}@if($opt['count'] > 0) ({{ $opt['count'] }})@endif
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
                        <div class="min-w-0">
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
                        <div class="min-w-0">
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
                        <div class="min-w-0">
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
                        <div class="min-w-0">
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
                        <div class="min-w-0">
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
                        <div class="min-w-0">
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
            </div>
        </div>
    </section>

    <div
        x-show="!swipeHintDismissed"
        x-cloak
        @click="dismissSwipeHint()"
        role="button"
        aria-label="بستن راهنما"
        class="mx-auto flex w-max cursor-pointer items-center gap-2 rounded-full shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-shadow)_4%,transparent)] bg-[var(--md-sys-color-surface-container)] px-3.5 py-1.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] md:hidden"
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
