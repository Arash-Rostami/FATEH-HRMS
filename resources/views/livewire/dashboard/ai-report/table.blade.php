@php
    $filters = $presenter->filterLabels();
    $filtered = $presenter->isFiltered($filter, $search);
    $showLoadMore = $presenter->showLoadMore(count($this->reports), $this->listLimit);
    $viewerId = (int) auth()->id();
@endphp

<x-ui.forms.filters
    placeholder="جستجو در عنوان گزارش‌ها..."
    searchModel="search"
    :declutterHide="true"
    filterTitle="نمایش گزارش‌ها"
    activeCondition="this.$wire.get('filter') !== 'all'"
    clearAction="this.$wire.call('setFilter', 'all'); this.$wire.set('search', '')"
>
    <div class="flex flex-wrap gap-2" role="group" aria-label="فیلتر گزارش‌ها">
        @foreach($filters as $key => $label)
            <button type="button" wire:key="ai-report-filter-{{ $key }}" wire:click="setFilter('{{ $key }}')"
                    :aria-pressed="$wire.filter === '{{ $key }}'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5"
                    :class="$wire.filter === '{{ $key }}'
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                        : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'">
                {{ $label }}
            </button>
        @endforeach
    </div>
</x-ui.forms.filters>

<x-ui.table
    cardClass="relative rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm"
    tableClass="ai-report-table min-w-full w-full border-separate border-spacing-0 text-sm"
>
    <x-slot:head>
        <tr>
            <th scope="col" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start first:rounded-tr-2xl"><x-ui.table.sort-label column="title" :sort="$sort" :sortDir="$sortDir">عنوان</x-ui.table.sort-label></th>
            <th scope="col" data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start">وضعیت</th>
            <th scope="col" data-col="type" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">نوع</th>
            <th scope="col" data-col="length" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">میزان توضیحات</th>
            <th scope="col" data-col="audience" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">گروه مخاطب</th>
            <th scope="col" data-col="owner" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">مالک</th>
            <th scope="col" data-col="recipients" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden lg:table-cell">گیرندگان</th>
            <th scope="col" data-col="updated" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden sm:table-cell"><x-ui.table.sort-label column="updated" :sort="$sort" :sortDir="$sortDir">آخرین تغییر</x-ui.table.sort-label></th>
            <th scope="col" data-col="state" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">ذخیره</th>
            <th scope="col" data-col="active" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">دسترسی</th>
            <th scope="col" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-center last:rounded-tl-2xl">
                <x-ui.table.tools :maximize="false" wire:key="ai-report-table-tools">
                    <x-ui.table.density-toggle/>
                    <div wire:key="ai-report-table-sort" class="contents">
                        <x-ui.table.sort-popover :options="['updated' => 'آخرین تغییر', 'title' => 'عنوان']"
                                                 :sort="$sort" :sortDir="$sortDir" :isDefault="$sort === 'id'"/>
                    </div>
                    <x-ui.table.col-toggle scope="ai-report" :columns="['status' => 'وضعیت', 'type' => 'نوع', 'length' => 'میزان توضیحات', 'audience' => 'گروه مخاطب', 'owner' => 'مالک', 'recipients' => 'گیرندگان', 'updated' => 'آخرین تغییر', 'state' => 'ذخیره', 'active' => 'دسترسی']"/>
                </x-ui.table.tools>
            </th>
        </tr>
    </x-slot:head>

    @forelse($this->reports as $rowReport)
        @php($row = $presenter->listRow($rowReport, $viewerId))
        <tr wire:key="ai-report-table-{{ $row['id'] }}" wire:click="open({{ $row['id'] }})" tabindex="0"
            x-on:keydown.enter.self.prevent="$el.click()"
            class="group relative isolate cursor-pointer transition-colors hover:bg-[var(--md-sys-color-surface-variant)]/40 focus-visible:outline-none focus-visible:bg-[var(--md-sys-color-surface-variant)]/40 {{ $report !== null && $report->id === $row['id'] ? 'bg-[var(--md-sys-color-primary-container)]/40' : '' }}">
            <td class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start max-w-[22rem]">
                <div class="absolute inset-0 -z-10 pointer-events-none transition-colors duration-200"
                     :style="{ 'background-color': $store.tagged.tagBg(@js($row['id']), @js('ai-report')) }"></div>
                <span class="flex items-center gap-1.5 min-w-0">
                    <span class="font-bold truncate"><bdi>{{ $row['title'] }}</bdi></span>
                    <span x-show="$store.pinned.isPinned({{ $row['id'] }}, 'ai-report')" x-cloak class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)] font-fill">push_pin</span>
                    @if($row['is_new'])<span wire:key="ai-report-table-new" class="shrink-0 text-[10.5px] font-bold text-[var(--md-sys-color-primary)]">جدید</span>@endif
                    @if($row['is_fork'])<span wire:key="ai-report-table-fork" class="shrink-0 text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">رونوشت خصوصی</span>@endif
                </span>
            </td>
            <td data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start">
                <span class="inline-flex items-center gap-1 text-xs">
                    <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-on-surface-variant)]">{{ $row['chip']['icon'] }}</span>
                    {{ $row['chip']['label'] }}
                </span>
            </td>
            <td data-col="type" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell text-xs"><bdi>{{ $row['presets']['type'] }}</bdi></td>
            <td data-col="length" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell text-xs"><bdi>{{ $row['presets']['length'] }}</bdi></td>
            <td data-col="audience" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell text-xs"><bdi>{{ $row['presets']['audience'] }}</bdi></td>
            <td data-col="owner" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell text-xs"><bdi>{{ $row['is_mine'] ? 'شما' : $row['owner'] }}</bdi></td>
            <td data-col="recipients" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden lg:table-cell text-xs tabular-nums">{{ $row['is_mine'] && $row['shared_count'] > 0 ? convertToPersian($row['shared_count']) . ' نفر' : '—' }}</td>
            <td data-col="updated" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden sm:table-cell text-xs">{{ $row['stamp'] }}</td>
            <td data-col="state" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell text-xs"><x-dashboard.ai-report.state-badge wire:key="ai-report-table-state" :state="$row['is_draft'] ? 'draft' : 'saved'"/></td>
            <td data-col="active" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-start hidden md:table-cell text-xs">@if($row['active'])<span wire:key="ai-report-table-access-active">فعال</span>@else<x-dashboard.ai-report.state-badge wire:key="ai-report-table-access-inactive" state="inactive"/>@endif</td>
            <td class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-center">
                <div class="inline-flex items-center gap-1">
                    <div x-data="{ tagOpen: false }" class="relative shrink-0" x-on:click.away="tagOpen = false" x-on:keydown.escape.window="tagOpen = false">
                        <button
                            type="button"
                            x-on:click.stop="tagOpen = !tagOpen"
                            :aria-expanded="tagOpen"
                            :class="$store.tagged.isTagged(@js($row['id']), @js('ai-report')) ? '!opacity-100' : 'max-sm:opacity-100 opacity-0 group-hover:opacity-100 focus-visible:!opacity-100 group-focus-within:opacity-100'"
                            class="w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                            title="رنگ‌آمیزی گزارش"
                            aria-label="رنگ‌آمیزی گزارش"
                        >
                            <span
                                class="material-symbols-rounded text-[16px]"
                                :style="$store.tagged.isTagged(@js($row['id']), @js('ai-report')) ? { color: $store.tagged.solid($store.tagged.getTag(@js($row['id']), @js('ai-report'))) } : null"
                            >palette</span>
                        </button>
                        <div
                            x-show="tagOpen"
                            x-cloak
                            x-transition
                            style="display: none;"
                            class="absolute top-1/2 -translate-y-1/2 left-full ml-2 z-50 flex items-center gap-1 p-1 rounded-lg bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-lg"
                        >
                            <template x-for="(col, i) in $store.tagged.palette" :key="i">
                                <button
                                    type="button"
                                    x-on:click.stop="$store.tagged.setTag(@js($row['id']), i, @js('ai-report')); tagOpen = false"
                                    :aria-label="'رنگ ' + (i + 1)"
                                    class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                                    :class="$store.tagged.getTag(@js($row['id']), @js('ai-report')) === i ? 'border-[var(--md-sys-color-on-surface)]' : 'border-transparent'"
                                    :style="{ 'background-color': col }"
                                ></button>
                            </template>
                            <button
                                type="button"
                                x-on:click.stop="$store.tagged.clearTag(@js($row['id']), @js('ai-report')); tagOpen = false"
                                aria-label="برداشتن رنگ"
                                title="برداشتن رنگ"
                                class="w-5 h-5 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)]"
                            >
                                <span class="material-symbols-rounded text-[14px]">close</span>
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        x-on:click.stop="$store.pinned.togglePin({{ $row['id'] }}, 'ai-report'); syncPinned()"
                        :class="$store.pinned.isPinned({{ $row['id'] }}, 'ai-report') ? '!opacity-100 text-[var(--md-sys-color-tertiary)]' : 'max-sm:opacity-100 opacity-0 group-hover:opacity-100 focus-visible:!opacity-100 group-focus-within:opacity-100 text-[var(--md-sys-color-on-surface-variant)]'"
                        class="w-6 h-6 p-0 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                        title="سنجاق کردن گزارش"
                        aria-label="سنجاق کردن گزارش"
                    >
                        <span class="material-symbols-rounded text-[16px]" :class="{ 'font-fill': $store.pinned.isPinned({{ $row['id'] }}, 'ai-report') }">push_pin</span>
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr wire:key="ai-report-table-empty">
            <td colspan="11" class="px-4 py-12">
                <x-ui.empty :icon="$filtered ? 'filter_alt_off' : 'description'"
                            :title="$filtered ? 'گزارشی با این شرایط پیدا نشد' : 'هنوز گزارشی ندارید'"
                            :description="$filtered ? 'فیلتر یا عبارت جست‌وجو را تغییر دهید.' : 'کنار عنوان، «گزارش و فرم» را بزنید و درخواستتان را بنویسید تا نخستین گزارش ساخته شود.'"
                            variant="list"/>
            </td>
        </tr>
    @endforelse
</x-ui.table>

@if($showLoadMore)
    <x-ui.buttons.load-more wire:key="ai-report-table-load-more" action="loadMoreReports" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                            class="mx-auto my-1 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
@endif
