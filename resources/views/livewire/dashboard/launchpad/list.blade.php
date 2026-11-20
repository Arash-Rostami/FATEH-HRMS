<x-ui.quota-meter label="تعداد برنامه‌های دپارتمان من" aria="تعداد برنامه‌های دپارتمان من" bar-key="launchpad-cap-bar" :used="$meter['used']" :cap="$meter['cap']" :pct="$meter['pct']" :tone="$meter['tone']"/>
<x-ui.forms.filters placeholder="جستجو با نام برنامه…" searchModel="search" :declutterHide="true" filterTitle="نمایش برنامه‌ها"
                    activeCondition="this.$wire.get('filter') !== 'all' || this.$wire.get('statusFilter') !== 'all' || this.$wire.get('typeFilter') !== 'all' || this.$wire.get('unseenFilter') !== 'all'" clearAction="this.$wire.call('setFilter', 'all'); this.$wire.call('setStatusFilter', 'all'); this.$wire.call('setTypeFilter', 'all'); this.$wire.call('setUnseenFilter', 'all'); this.$wire.set('search', '')">
    <div class="flex flex-wrap gap-2" role="group" aria-label="فیلتر برنامه‌ها">
        @foreach($filters as $key => $label)
            <button type="button" wire:key="launchpad-filter-{{ $key }}" wire:click="setFilter('{{ $key }}')"
                    :aria-pressed="$wire.filter === '{{ $key }}'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5"
                    :class="$wire.filter === '{{ $key }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                        : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'">
                {{ $label }}
            </button>
        @endforeach
    </div>
    <div class="flex flex-col gap-2">
        <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] px-1">وضعیت</span>
        <div class="flex flex-wrap gap-2" role="group" aria-label="وضعیت برنامه‌ها">
            @foreach($statusFilters as $key => $label)
                <button type="button" wire:key="launchpad-status-filter-{{ $key }}" wire:click="setStatusFilter('{{ $key }}')"
                        :aria-pressed="$wire.statusFilter === '{{ $key }}'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5"
                        :class="$wire.statusFilter === '{{ $key }}'
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                            : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
    @foreach([['نوع', 'type', 'typeFilter', 'setTypeFilter', $typeFilters], ['باز شدن', 'unseen', 'unseenFilter', 'setUnseenFilter', $unseenFilters]] as [$groupLabel, $slug, $prop, $setter, $choices])
        <div wire:key="launchpad-{{ $slug }}-filters" class="flex flex-col gap-2">
            <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $groupLabel }}</span>
            <div class="flex flex-wrap gap-2" role="group" aria-label="{{ $groupLabel }}">
                @foreach($choices as $key => $label)
                    <button type="button" wire:key="launchpad-{{ $slug }}-filter-{{ $key }}" wire:click="{{ $setter }}('{{ $key }}')"
                            :aria-pressed="$wire.{{ $prop }} === '{{ $key }}'"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5"
                            :class="$wire.{{ $prop }} === '{{ $key }}'
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                                : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</x-ui.forms.filters>
<x-ui.modals.max-backdrop/>
<div :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }">
<x-ui.table cardClass="relative rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm" wrapClass="w-full overflow-x-auto min-h-[300px]" tableClass="launchpad-table min-w-full w-full border-separate border-spacing-0 text-sm">
    <x-slot:head><tr>
        <th scope="col" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start first:rounded-tr-2xl"><x-ui.table.sort-label column="title" :sort="$sort" :sortDir="$sortDir">نام برنامه</x-ui.table.sort-label></th>
        <th scope="col"data-col="department" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">دپارتمان</th>
        <th scope="col"data-col="owner" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">مالک</th>
        <th scope="col"data-col="audience" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">تعداد مخاطبان</th>
        <th scope="col"data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start">وضعیت</th>
        <th scope="col"data-col="description" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">توضیحات</th>
        <th scope="col"data-col="dates" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">دسترسی و نگهداری</th>
        <th scope="col" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-center last:rounded-tl-2xl">
            <x-ui.table.tools label="عملیات" wire:key="launchpad-table-tools">
                <x-ui.table.density-toggle/>
                <div wire:key="launchpad-table-sort" class="contents">
                    <x-ui.table.sort-popover :options="['id' => 'زمان ساخت', 'title' => 'نام برنامه']" :sort="$sort" :sortDir="$sortDir" :isDefault="$sort === 'id' && $sortDir === 'desc'"/>
                </div>
                <x-ui.table.col-toggle scope="launchpad" :columns="['department' => 'دپارتمان', 'owner' => 'مالک', 'audience' => 'تعداد مخاطبان', 'status' => 'وضعیت', 'description' => 'توضیحات', 'dates' => 'دسترسی و نگهداری']"/>
            </x-ui.table.tools>
        </th>
    </tr></x-slot:head>
    @forelse($this->rows->take($listLimit) as $item)
        @php($row = $presenter->listRow($item, auth()->user(), $sites))
        <tr wire:key="launchpad-row-{{ $row['id'] }}" class="transition-colors hover:bg-[var(--md-sys-color-surface-variant)]/40">
            <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start"><bdi class="line-clamp-2 break-words">{{ $row['title'] }}</bdi></td>
            <td data-col="department" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell">{{ $row['department'] }}</td>
            <td data-col="owner" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell">{{ $row['owner'] }}</td>
            <td data-col="audience" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell">{{ $row['audience'] }}</td>
            <td data-col="status" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start"><x-ui.badge :variant="$row['variant']">{{ $row['status'] }}</x-ui.badge>
                @if($row['refresh'])
                    <p wire:key="launchpad-refresh-state" class="mt-1 text-xs break-words text-[var(--md-sys-color-on-surface-variant)]">دریافت اطلاعات: {{ $row['refresh']['status'] }}@if($row['refresh']['at']) · <bdi>{{ $row['refresh']['at'] }}</bdi>@endif</p>
                    @if($row['refresh']['reason'])
                        <p wire:key="launchpad-refresh-reason" class="mt-1 text-xs break-words text-[var(--md-sys-color-on-surface-variant)]">{{ $row['refresh']['reason'] }}</p>
                    @endif
                @endif
            </td>
            <td data-col="description" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell"><bdi class="block max-w-[16rem] truncate" title="{{ $row['description'] }}">{{ $row['description'] !== '' ? $row['description'] : '—' }}</bdi></td>
            <td data-col="dates" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell"><bdi class="block whitespace-nowrap">دسترسی: {{ $row['access'] }}</bdi><bdi class="block whitespace-nowrap">نگهداری: {{ $row['expiry'] }}</bdi></td>
            <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-center"><div class="flex flex-wrap justify-center gap-1">
                @if($item->kind === 'link' && $row['block'] === null)
                    <x-ui.buttons.form wire:key="launchpad-open-link" variant="tonal" size="icon" icon="open_in_new" title="باز کردن برنامه" href="{{ route('launchpad.go', $row['id']) }}" target="_blank" rel="noopener"/>
                @elseif($item->kind === 'link')
                    <x-ui.buttons.form wire:key="launchpad-open-link-off" variant="tonal" size="icon" icon="open_in_new" title="برنامه در حال حاضر قابل باز شدن نیست" disabled/>
                @else
                    <x-ui.buttons.form wire:key="launchpad-open-static" variant="tonal" size="icon" icon="open_in_new" title="باز کردن برنامه" wire:click="open({{ $row['id'] }})" x-on:click="if (max) toggleMaximize()"/>
                @endif
                @if(in_array($row['id'], $options, true))
                    <span wire:key="launchpad-workspace-remove"><x-ui.buttons.form variant="primary" size="icon" icon="bookmark_remove" title="حذف از فضای کاری" loading="removeOption" wire:click="removeOption({{ $row['id'] }})"/></span>
                @elseif($row['pinnable'])
                    <span wire:key="launchpad-workspace-add"><x-ui.buttons.form variant="tonal" size="icon" icon="dashboard_customize" title="{{ $row['workspace_title'] }}" :disabled="$row['block'] !== null" loading="addOption" wire:click="addOption({{ $row['id'] }})"/></span>
                @else
                    <span wire:key="launchpad-workspace-add-link"><x-ui.buttons.form variant="danger" size="icon" icon="dashboard_customize" title="افزودن به فضای کاری" wire:click="addOption({{ $row['id'] }})"/></span>
                @endif
                @if($row['refresh'])
                    <span wire:key="launchpad-refresh-action"><x-ui.buttons.form variant="tonal" size="icon" icon="sync" title="دریافت اطلاعات تازه" loading="refreshNow" wire:click="refreshNow({{ $row['id'] }})"/></span>
                @endif
                <span wire:key="launchpad-details-action"><x-ui.buttons.form variant="tonal" size="icon" icon="info" title="جزئیات برنامه" loading="showDetails" x-on:click="if (max) toggleMaximize()" wire:click="showDetails({{ $row['id'] }})"/></span>
                @if($row['can_manage'] && $row['live'])
                    <span wire:key="launchpad-row-deactivate"><x-ui.buttons.form variant="tonal" size="icon" icon="visibility_off" title="غیرفعال‌سازی" loading="deactivate" x-on:click="if (max) toggleMaximize(); {{ confirmDialog('deactivate', 'غیرفعال‌سازی برنامه', 'برنامه برای دیگران بسته می‌شود؛ هر زمان می‌توانید دوباره آن را فعال کنید.', $row['id'], 'dispatch', 'danger') }}"/></span>
                @elseif($row['can_manage'])
                    <span wire:key="launchpad-row-activate"><x-ui.buttons.form variant="tonal" size="icon" icon="visibility" title="فعال‌سازی از امروز" loading="activate" wire:click="activate({{ $row['id'] }})"/></span>
                @endif
                @if($row['can_manage'])
                    <span wire:key="launchpad-edit-action"><x-ui.buttons.form variant="tonal" size="icon" icon="edit" title="ویرایش برنامه" loading="openEdit" x-on:click="if (max) toggleMaximize()" wire:click="openEdit({{ $row['id'] }})"/></span>
                    <span wire:key="launchpad-delete-action"><x-ui.buttons.form variant="tonal" size="icon" icon="delete" title="حذف برنامه" loading="delete" x-on:click="if (max) toggleMaximize(); {{ confirmDialog('delete', 'حذف برنامه', 'برنامه، فایل‌ها و تنظیمات اتصال آن برای همیشه حذف می‌شوند و مخاطبان دیگر به آن دسترسی نخواهند داشت. برنامه حذف شود؟', $row['id'], 'dispatch', 'danger') }}"/></span>
                @endif
            </div></td>
        </tr>
    @empty
        <tr wire:key="launchpad-list-empty">
            <td colspan="8" class="px-4 py-12">
                <x-ui.empty :icon="$filter !== 'all' || $statusFilter !== 'all' || $typeFilter !== 'all' || $unseenFilter !== 'all' || $search !== '' ? 'filter_alt_off' : 'folder_open'" title="برنامه‌ای پیدا نشد" description="برنامه‌ای با جستجو و فیلتر فعلی شما پیدا نشد. جستجو یا فیلتر را تغییر دهید. برای افزودن برنامه، به بخش «افزودن برنامه» بروید." variant="list"/>
            </td>
        </tr>
    @endforelse
</x-ui.table>
</div>
@if($this->rows->count() > $listLimit)
    <div wire:key="launchpad-load-more" class="contents"><x-ui.buttons.load-more action="loadMore" text="نمایش بیشتر" loading-text="در حال بارگذاری…" class="mx-auto my-1 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/></div>
@endif
