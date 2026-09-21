<x-ui.table
    cardClass="relative min-h-[300px] overflow-hidden bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl shadow-sm"
    wrapClass="overflow-x-auto w-full"
    tableClass="ths-ticket-table min-w-full text-sm text-right whitespace-nowrap lg:whitespace-normal text-[var(--md-sys-color-on-surface)]"
    theadClass="bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)] uppercase font-medium text-xs border-b border-[var(--md-sys-color-outline-variant)]"
    tbodyClass="divide-y divide-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)]"
>
    <x-slot:head>
        @if($listFilter === 'mine')
            <tr wire:key="ths-search-row" x-show="openSearch" x-collapse>
                <th colspan="7" class="px-5 pb-3 py-2">
                    <div class="flex justify-end border-b border-[var(--md-sys-color-outline-variant)]/30 bg-transparent" @click.stop>
                        <div class="w-72">
                            <x-ui.forms.search
                                name="ticket_search"
                                model="ticketSearch"
                                placeholder="جستجو در تیکت‌ها..."
                                debounce="400"
                                icon="manage_search"
                                :clearable="true"
                                class="!bg-[var(--md-sys-color-surface-variant)]/40"
                            />
                        </div>
                    </div>
                </th>
            </tr>
        @endif

        <tr>
            <th scope="col" class="px-6 py-4 rounded-tr-lg">شناسه / تاریخ</th>
            <th scope="col" data-col="status" class="px-6 py-4">وضعیت</th>
            <th scope="col" data-col="domain" class="px-6 py-4 hidden md:table-cell">حوزه درخواست</th>
            <th scope="col" class="px-6 py-4 hidden sm:table-cell w-1/3">موضوع</th>
            <th scope="col" data-col="requester" class="px-6 py-4 hidden lg:table-cell">درخواست‌دهنده</th>
            <th scope="col" data-col="assignee" class="px-6 py-4 hidden lg:table-cell">مسئول</th>
            <th scope="col" class="px-6 py-4 text-center rounded-tl-lg">
                @if($listFilter === 'mine')
                    <div wire:key="ths-actions-toolbar" class="flex items-center justify-end w-full gap-1">
                        <button @click="toggleSearch" title="جستجو"
                                class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case"
                                :class="openSearch
                                        ? 'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]'
                                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]'">
                            <span class="material-symbols-rounded text-[18px]">search</span>
                        </button>
                        <x-ui.forms.date-span-popover :active="$this->dateSpanActive()" :currentBasis="$dateBasis"/>

                        <x-ui.table.density-toggle/>

                        <x-ui.table.col-toggle scope="ths" :columns="['status' => 'وضعیت', 'domain' => 'حوزه درخواست', 'requester' => 'درخواست‌دهنده', 'assignee' => 'مسئول']" />

                        <button @click="toggleMaximize()"
                                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                                :class="{ '!bg-[var(--md-sys-color-primary-container)] !text-[var(--md-sys-color-on-primary-container)]': max }"
                                class="inline-flex items-center justify-center p-1 pr-3 rounded-lg transition-colors normal-case text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]">
                            <span class="material-symbols-rounded text-[18px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
                        </button>
                    </div>
                @else
                    اقدام
                @endif
            </th>
        </tr>
    </x-slot:head>

    @forelse($tickets as $ticket)
        @include('livewire.dashboard.ths.ticket-table-row')
    @empty
        <tr wire:key="ths-empty-row">
            <td colspan="7" class="px-6 py-12">
                @if($listFilter === 'actionable')
                    <div wire:key="ths-empty-actionable" class="contents">
                        <x-ui.empty icon="task_alt" title="فعلاً موردی نیاز به اقدام شما ندارد." description="تیکت‌های بازِ واحد شما یا تیکت‌های محول‌شده به شما اینجا نمایش داده می‌شوند." variant="list" />
                    </div>
                @elseif($this->dateSpanActive())
                    <div wire:key="ths-empty-datespan" class="contents">
                        <x-ui.empty icon="event_busy"
                                    title="تیکتی در این بازه تاریخ یافت نشد"
                                    description="فیلتر بازه تاریخ فعال است"
                                    variant="filtered" />

                        <div class="mt-3 flex justify-center">
                            <button type="button"
                                    wire:click="clearDateSpan"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-4 py-2 text-sm font-medium text-[var(--md-sys-color-primary)] shadow-sm transition hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
                                <span class="material-symbols-rounded text-[16px]">event_available</span>
                                حذف فیلتر تاریخ
                            </button>
                        </div>
                    </div>
                @else
                    <div wire:key="ths-empty-none" class="contents">
                        <x-ui.empty icon="inbox" title="هیچ تیکتی یافت نشد." description="درخواست‌های ارسالی شما در اینجا نمایش داده می‌شوند." variant="list" />
                    </div>
                @endif
            </td>
        </tr>
    @endforelse
</x-ui.table>

<div class="mt-6 flex justify-center rtl" dir="ltr"></div>

@if($tickets->hasMorePages())
    <div wire:key="ths-load-more" class="flex justify-center pb-6 w-full mt-4">
        <x-ui.buttons.load-more
            action="loadMore"
            text="بارگذاری بیشتر"
            loading-text="در حال دریافت..."
            icon="expand_more"
            class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
        />
    </div>
@endif
