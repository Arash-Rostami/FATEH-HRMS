@php
    $rows = $this->letters;
    $filters = $presenter->filterLabels();
@endphp

<x-ui.forms.filters
    placeholder="جستجو در شماره و موضوع نامه‌ها..."
    searchModel="search"
    :declutterHide="true"
    filterTitle="نمایش نامه‌ها"
    activeCondition="this.$wire.get('filter') !== 'all' || this.$wire.get('listTab') !== 'waiting'"
    clearAction="this.$wire.call('clearFilters')"
>
    <div class="flex flex-col gap-2">
        <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] px-1">فهرست</span>
        <div class="flex flex-wrap gap-2" role="group" aria-label="فهرست نامه‌ها">
            @foreach($listTabs as $tab)
                <button type="button" wire:key="correspondence-list-tab-{{ $tab['id'] }}" wire:click="switchTab('{{ $tab['id'] }}')" class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5 {{ $listTab === $tab['id'] ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent' : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)]' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
    </div>
    <div class="flex flex-wrap gap-2" role="group" aria-label="فیلتر نامه‌ها">
        @foreach($filters as $key => $label)
            <button type="button" wire:key="correspondence-filter-{{ $key }}" wire:click="setFilter('{{ $key }}')" class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5 {{ $filter === $key ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent' : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)]' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>
</x-ui.forms.filters>

<x-ui.table
    cardClass="relative rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm"
    tableClass="correspondence-table min-w-full w-full border-separate border-spacing-0 text-sm"
>
    <x-slot:head>
        <tr>
            <th scope="col" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start first:rounded-tr-2xl"><x-ui.table.sort-label column="subject" :sort="$sort" :sortDir="$sortDir">{{ __('resources/correspondence/strings.fields.subject') }}</x-ui.table.sort-label></th>
            <th scope="col" data-col="number" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start"><x-ui.table.sort-label column="number" :sort="$sort" :sortDir="$sortDir">{{ __('resources/correspondence/strings.fields.number') }}</x-ui.table.sort-label></th>
            <th scope="col" data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start">{{ __('resources/correspondence/strings.fields.status') }}</th>
            <th scope="col" data-col="type" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">{{ __('resources/correspondence/strings.fields.type') }}</th>
            <th scope="col" data-col="step" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden md:table-cell">مرحله</th>
            <th scope="col" data-col="awaiting" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden lg:table-cell">در انتظار</th>
            <th scope="col" data-col="updated" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-start hidden sm:table-cell"><x-ui.table.sort-label column="updated" :sort="$sort" :sortDir="$sortDir">آخرین تغییر</x-ui.table.sort-label></th>
            <th scope="col" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 font-bold text-center last:rounded-tl-2xl">
                <x-ui.table.tools :maximize="false" wire:key="correspondence-table-tools">
                    <x-ui.table.density-toggle/>
                    <x-ui.table.sort-popover :options="['updated' => 'آخرین تغییر', 'subject' => 'موضوع', 'number' => 'شماره']" :sort="$sort" :sortDir="$sortDir" :isDefault="$sort === 'updated'"/>
                    <x-ui.table.col-toggle scope="correspondence" :columns="['number' => 'شماره', 'status' => 'وضعیت', 'type' => 'نوع', 'step' => 'مرحله', 'awaiting' => 'در انتظار', 'updated' => 'آخرین تغییر']"/>
                </x-ui.table.tools>
            </th>
        </tr>
    </x-slot:head>

    @forelse($rows as $row)
        <tr wire:key="correspondence-table-{{ $row['id'] }}" wire:click="open({{ (int) $row['id'] }})" tabindex="0" x-on:keydown.enter.self.prevent="activate($el)" class="group relative isolate cursor-pointer transition-colors hover:bg-[var(--md-sys-color-surface-container-low)] {{ $letterId === $row['id'] ? 'bg-[var(--md-sys-color-primary-container)]' : '' }}">
            <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start max-w-[22rem]">
                <div class="absolute inset-0 -z-10 pointer-events-none transition-colors duration-200"
                     :style="{ 'background-color': $store.tagged.tagBg(@js((int) $row['id']), @js('correspondence')) }"></div>
                <span class="flex items-center gap-1.5 min-w-0">
                    <span class="h-2.5 w-2.5 rounded-full shrink-0 {{ $row['dot'] }}"></span>
                    <span class="font-bold truncate {{ $row['overdue'] ? 'text-[var(--md-sys-color-error)]' : '' }}"><bdi>{{ $row['subject'] }}</bdi></span>
                    @if($row['urgent'])
                        <x-ui.badge wire:key="correspondence-table-urgent-{{ $row['id'] }}" variant="danger" class="!px-1.5 !py-0.5 !text-[10px] shrink-0">{{ $row['urgency'] }}</x-ui.badge>
                    @endif
                    @if($row['wet'])
                        <x-ui.badge wire:key="correspondence-table-wet-{{ $row['id'] }}" class="!px-1.5 !py-0.5 !text-[10px] shrink-0">{{ __('resources/correspondence/strings.sign_mode.wet') }}</x-ui.badge>
                    @endif
                    <span x-show="$store.pinned.isPinned({{ (int) $row['id'] }}, 'correspondence')" x-cloak class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)] font-fill">push_pin</span>
                </span>
            </td>
            <td data-col="number" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start text-xs"><bdi>{{ $row['number'] !== '' ? $row['number'] : '—' }}</bdi></td>
            <td data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start text-xs">{{ $row['status_label'] }}</td>
            <td data-col="type" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start text-xs hidden md:table-cell"><bdi>{{ $row['type'] }}</bdi></td>
            <td data-col="step" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start text-xs hidden md:table-cell">{{ $row['step'] !== '' ? $row['step'] : '—' }}</td>
            <td data-col="awaiting" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start text-xs hidden lg:table-cell"><bdi>{{ $row['awaiting'] !== '' ? $row['awaiting'] : '—' }}</bdi></td>
            <td data-col="updated" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-start text-xs hidden sm:table-cell">{{ $row['stamp'] }}</td>
            <td class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 align-middle text-center">
                <div class="inline-flex items-center gap-1">
                    <div x-data="{ tagOpen: false }" class="relative shrink-0" x-on:click.away="tagOpen = false" x-on:keydown.escape.window="tagOpen = false">
                        <button
                            type="button"
                            x-on:click.stop="tagOpen = !tagOpen"
                            :aria-expanded="tagOpen"
                            :class="$store.tagged.isTagged(@js((int) $row['id']), @js('correspondence')) ? '!opacity-100' : 'max-sm:opacity-100 opacity-0 group-hover:opacity-100 focus-visible:!opacity-100 group-focus-within:opacity-100'"
                            class="w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                            title="رنگ‌آمیزی نامه"
                            aria-label="رنگ‌آمیزی نامه"
                        >
                            <span
                                class="material-symbols-rounded text-[16px]"
                                :style="$store.tagged.isTagged(@js((int) $row['id']), @js('correspondence')) ? { color: $store.tagged.solid($store.tagged.getTag(@js((int) $row['id']), @js('correspondence'))) } : null"
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
                                    x-on:click.stop="$store.tagged.setTag(@js((int) $row['id']), i, @js('correspondence')); tagOpen = false"
                                    :aria-label="'رنگ ' + (i + 1)"
                                    class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                                    :class="$store.tagged.getTag(@js((int) $row['id']), @js('correspondence')) === i ? 'border-[var(--md-sys-color-on-surface)]' : 'border-transparent'"
                                    :style="{ 'background-color': col }"
                                ></button>
                            </template>
                            <button
                                type="button"
                                x-on:click.stop="$store.tagged.clearTag(@js((int) $row['id']), @js('correspondence')); tagOpen = false"
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
                        x-on:click.stop="$store.pinned.togglePin({{ (int) $row['id'] }}, 'correspondence'); syncPinned()"
                        :class="$store.pinned.isPinned({{ (int) $row['id'] }}, 'correspondence') ? '!opacity-100 text-[var(--md-sys-color-tertiary)]' : 'max-sm:opacity-100 opacity-0 group-hover:opacity-100 focus-visible:!opacity-100 group-focus-within:opacity-100 text-[var(--md-sys-color-on-surface-variant)]'"
                        class="w-6 h-6 p-0 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                        title="سنجاق کردن نامه"
                        aria-label="سنجاق کردن نامه"
                    >
                        <span class="material-symbols-rounded text-[16px]" :class="{ 'font-fill': $store.pinned.isPinned({{ (int) $row['id'] }}, 'correspondence') }">push_pin</span>
                    </button>
                </div>
            </td>
        </tr>
    @empty
        <tr wire:key="correspondence-table-empty-row">
            <td colspan="8" class="px-4 py-6">
                <div wire:key="correspondence-table-empty" class="contents">
                    <x-ui.empty icon="mail" title="نامه‌ای برای این فیلتر نیست"/>
                </div>
            </td>
        </tr>
    @endforelse
</x-ui.table>

@if($this->hasMore)
    <x-ui.buttons.load-more wire:key="correspondence-table-load-more" action="loadMore" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                            class="mx-auto my-1 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
@endif
