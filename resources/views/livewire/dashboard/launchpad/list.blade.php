<x-ui.quota-meter label="سهمیه بخش" aria="سهمیه بخش" bar-key="launchpad-cap-bar" :used="$meter['used']" :cap="$meter['cap']" :pct="$meter['pct']" :tone="$meter['tone']"/>
<x-ui.forms.filters placeholder="جستجو در عنوان برنامه‌ها..." searchModel="search" :declutterHide="true" filterTitle="نمایش برنامه‌ها"
                    activeCondition="this.$wire.get('filter') !== 'all'" clearAction="this.$wire.call('setFilter', 'all'); this.$wire.set('search', '')">
    <div class="flex flex-wrap gap-2" role="group" aria-label="فیلتر برنامه‌ها">
        @foreach($filters as $key => $label)
            <x-ui.buttons.form wire:key="launchpad-filter-{{ $key }}" variant="secondary" wire:click="setFilter('{{ $key }}')" x-bind:aria-pressed="$wire.filter === '{{ $key }}'" class="aria-pressed:bg-[var(--md-sys-color-primary)] aria-pressed:text-[var(--md-sys-color-on-primary)]">{{ $label }}</x-ui.buttons.form>
        @endforeach
    </div>
</x-ui.forms.filters>
<x-ui.table cardClass="relative rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm" tableClass="launchpad-table min-w-full w-full border-separate border-spacing-0 text-sm">
    <x-slot:head><tr>
        <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start"><x-ui.table.sort-label column="title" :sort="$sort" :sortDir="$sortDir">عنوان</x-ui.table.sort-label></th>
        <th data-col="department" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">بخش</th>
        <th data-col="owner" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">مالک</th>
        <th data-col="audience" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">مخاطبان</th>
        <th data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start">وضعیت</th>
        <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-center">
            <x-ui.table.tools :maximize="false" label="عملیات" wire:key="launchpad-table-tools">
                <x-ui.table.density-toggle/>
                <div wire:key="launchpad-table-sort" class="contents">
                    <x-ui.table.sort-popover :options="['id' => 'ترتیب ایجاد', 'title' => 'عنوان']" :sort="$sort" :sortDir="$sortDir" :isDefault="$sort === 'id' && $sortDir === 'desc'"/>
                </div>
                <x-ui.table.col-toggle scope="launchpad" :columns="['department' => 'بخش', 'owner' => 'مالک', 'audience' => 'مخاطبان', 'status' => 'وضعیت']"/>
            </x-ui.table.tools>
        </th>
    </tr></x-slot:head>
    @forelse($this->rows->take($listLimit) as $item)
        @php($row = $presenter->listRow($item, auth()->user()))
        <tr wire:key="launchpad-row-{{ $row['id'] }}">
            <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start"><bdi class="line-clamp-2 break-words">{{ $row['title'] }}</bdi></td>
            <td data-col="department" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start hidden md:table-cell">{{ $row['department'] }}</td>
            <td data-col="owner" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start hidden md:table-cell">{{ $row['owner'] }}</td>
            <td data-col="audience" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start hidden md:table-cell">{{ $row['audience'] }}</td>
            <td data-col="status" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start"><x-ui.badge :variant="$row['variant']">{{ $row['status'] }}</x-ui.badge></td>
            <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-center"><div class="inline-flex gap-1">
                @if($item->kind === 'link')
                    <x-ui.buttons.form wire:key="launchpad-open-link" variant="tonal" size="icon" icon="open_in_new" title="باز کردن" href="{{ route('launchpad.go', $row['id']) }}" target="_blank" rel="noopener"/>
                @else
                    <x-ui.buttons.form wire:key="launchpad-open-static" variant="tonal" size="icon" icon="open_in_new" title="باز کردن" wire:click="open({{ $row['id'] }})"/>
                @endif
                @if($item->kind === 'static')
                    <x-ui.buttons.form wire:key="launchpad-workspace-add" variant="tonal" size="icon" icon="dashboard_customize" title="{{ $row['workspace_title'] }}" :disabled="$row['block'] !== null"
                        data-title="{{ $row['title'] }}" data-icon="apps" data-src="{{ route('launchpad', ['a' => $row['id'], 'embed' => 1]) }}"
                        data-added="برنامه به فضای کاری افزوده شد." data-exists="این برنامه در فضای کاری وجود دارد." data-full="ظرفیت فضای کاری پر است." x-on:click="add($el)"/>
                @endif
                @if($row['can_manage'])
                    <span wire:key="launchpad-delete-action"><x-ui.buttons.form variant="tonal" size="icon" icon="delete" title="حذف" loading="delete" x-on:click="{{ confirmDialog('delete', 'حذف برنامه', 'برنامه برای همیشه حذف می‌شود. مطمئن هستید؟', $row['id'], 'dispatch', 'danger') }}"/></span>
                @endif
            </div></td>
        </tr>
    @empty
        <tr wire:key="launchpad-list-empty"><td colspan="6"><x-ui.empty icon="folder_open" title="برنامه‌ای پیدا نشد" description="فایل index.html و فایل‌های کنار آن را یک‌جا انتخاب کنید و برنامه را بسازید."/></td></tr>
    @endforelse
</x-ui.table>
@if($this->rows->count() > $listLimit)
    <div wire:key="launchpad-load-more" class="contents"><x-ui.buttons.load-more action="loadMore" text="نمایش بیشتر" loading-text="در حال بارگذاری…"/></div>
@endif
