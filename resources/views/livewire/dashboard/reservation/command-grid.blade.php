<div>
    <x-ui.modals.max-backdrop/>

    <div :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }">
        <div class="mb-4 flex items-center gap-2">
            <div class="flex-1">
                <x-ui.forms.filters
                    placeholder="جستجو در رزروها..."
                    searchModel="gridSearch"
                    filterTitle="وضعیت رزرو"
                    activeCondition="this.$wire.get('historyShowAll') !== true"
                    clearAction="this.$wire.call('showAllHistory'); this.$wire.set('gridSearch', '')"
                >
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                wire:click="showAllHistory"
                                class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5"
                                :class="$wire.historyShowAll
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                                    : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                            >
                                <span class="material-symbols-rounded text-[16px]">apps</span>
                                همه
                            </button>
                            @foreach($historyTabs as $tab)
                                <button
                                    type="button"
                                    wire:click="switchTab('{{ $tab['id'] }}')"
                                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors border inline-flex items-center gap-1.5"
                                    :class="(!$wire.historyShowAll && $wire.activeHistoryTab === '{{ $tab['id'] }}')
                                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                                        : 'bg-transparent text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                                >
                                    <span class="material-symbols-rounded text-[16px]">{{ $tab['icon'] }}</span>
                                    {{ $tab['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </x-ui.forms.filters>
            </div>
        </div>

        @if(count($gridPinned) > 50)
            <div wire:key="reservation-grid-pin-overflow-hint" class="mb-3 text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                سنجاق‌های بیش از {{ convertToPersian('50') }} مورد در ترتیب رزروها اثر ندارند.
            </div>
        @endif

        <x-ui.table
            cardClass="relative min-h-[300px] rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm"
            tableClass="reservation-command-table min-w-full w-full border-separate border-spacing-0 text-sm"
        >
            <x-slot:head>
                <tr>
                    <th data-col="resource" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-right font-bold first:rounded-tr-2xl">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">meeting_room</span>
                            <span>منبع</span>
                        </div>
                    </th>
                    <th data-col="time" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-right font-bold">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">calendar_today</span>
                            <span>زمان</span>
                        </div>
                    </th>
                    <th data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-center font-bold">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">info</span>
                            <span>وضعیت</span>
                        </div>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-3.5 text-center font-bold last:rounded-tl-2xl">
                        @php($gridColumns = ['resource' => 'منبع', 'time' => 'زمان', 'status' => 'وضعیت'])
                        <div class="flex items-center justify-end w-full gap-1">
                            <x-ui.forms.date-span-popover :active="$this->dateSpanActive()" :basisOptions="null"/>

                            <x-ui.table.density-toggle/>

                            <x-ui.table.col-toggle :scope="'reservation'" :columns="$gridColumns"/>

                            <button type="button"
                                    @click="toggleMaximize()"
                                    :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                                    :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': max, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !max }"
                                    class="inline-flex items-center justify-center p-1 pr-3 rounded-lg transition-colors normal-case">
                                <span class="material-symbols-rounded text-[18px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
                            </button>
                        </div>
                    </th>
                </tr>
            </x-slot:head>

            @forelse($this->gridReservations as $reservation)
                <tr wire:key="command-grid-row-{{ $reservation->id }}" data-rf="reservation-{{ $reservation->id }}" class="group relative isolate transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-low)]">

                    <td data-col="resource" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-4 align-middle">
                        <div class="absolute inset-0 -z-10 pointer-events-none transition-colors duration-200"
                             :style="{ 'background-color': $store.tagged.tagBg(@js($reservation->id), @js('reservation')) }"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-surface-container-high)] flex items-center justify-center shrink-0">
                                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">{{ $reservation->resource->icon }}</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-bold text-[13px] text-[var(--md-sys-color-on-surface)] truncate flex items-center gap-1">
                                    {{ $reservation->resource->name }}
                                    <span x-show="$store.pinned.isPinned({{ $reservation->id }}, 'reservation')" x-cloak class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)] font-fill">push_pin</span>
                                </span>
                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ \App\Enums\ResourceType::tryFrom($reservation->resource->type)?->getLabel() ?? $reservation->resource->type }}</span>
                            </div>
                        </div>
                    </td>

                    <td data-col="time" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-4 align-middle text-[12px] text-[var(--md-sys-color-on-surface-variant)]">
                        {{ $reservation->display_time }}
                        @if(($reservation->series_count ?? 1) > 1)
                            <span wire:key="grid-series-count" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-[9px] font-bold ms-1">
                                <span class="material-symbols-rounded text-[12px]">repeat</span>{{ convertToPersian((string) $reservation->series_count) }}
                            </span>
                        @endif
                    </td>

                    <td data-col="status" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-4 align-middle text-center">
                        @if($reservation->history_bucket === 'upcoming')
                            <span wire:key="grid-status-upcoming" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                                <span class="material-symbols-rounded text-[14px]">event_upcoming</span>پیش‌رو
                            </span>
                        @elseif($reservation->history_bucket === 'previous')
                            <span wire:key="grid-status-previous" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-[var(--md-sys-color-primary-container)]/30 text-[var(--md-sys-color-primary)]">
                                <span class="material-symbols-rounded text-[14px]">check</span>قبلی
                            </span>
                        @elseif($reservation->history_bucket === 'cancelled')
                            <span wire:key="grid-status-cancelled" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                                <span class="material-symbols-rounded text-[14px]">block</span>{{ $reservation->status === 'cancelled_admin' ? 'لغو مدیریت' : 'لغو شخصی' }}
                            </span>
                            @if($reservation->cancel_reason || $reservation->cancelledBy)
                                <div wire:key="grid-cancel-reason" class="mt-1 text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] truncate max-w-[160px] mx-auto" title="{{ $reservation->cancelReasonLabel() }}">
                                    @if($reservation->cancelReasonLabel())
                                        {{ $reservation->cancelReasonLabel() }}@if($reservation->cancelledBy) · @endif
                                    @endif
                                    @if($reservation->cancelledBy)
                                        لغو توسط {{ $reservation->cancelledBy->name }}
                                    @endif
                                </div>
                            @endif
                        @elseif($reservation->history_bucket === 'released')
                            <span wire:key="grid-status-released" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                                <span class="material-symbols-rounded text-[14px]">autorenew</span>آزادشده
                            </span>
                        @endif
                    </td>

                    <td class="relative border-b border-[var(--md-sys-color-outline-variant)] px-4 md:px-6 py-4 text-center align-middle">
                        <div class="reservation-action-cluster absolute left-2 top-0.5 z-20 flex items-center gap-1">
                            <div x-data="{ tagOpen: false }" class="relative shrink-0" x-on:click.away="tagOpen = false">
                                <button
                                    type="button"
                                    x-on:click.stop="tagOpen = !tagOpen"
                                    :class="$store.tagged.isTagged(@js($reservation->id), @js('reservation')) ? '!opacity-100' : 'max-sm:opacity-100 opacity-0 group-hover:opacity-100'"
                                    class="w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                                    title="رنگ‌آمیزی رزرو"
                                    aria-label="رنگ‌آمیزی رزرو"
                                >
                                    <span
                                        class="material-symbols-rounded text-[16px]"
                                        :style="$store.tagged.isTagged(@js($reservation->id), @js('reservation')) ? { color: $store.tagged.solid($store.tagged.getTag(@js($reservation->id), @js('reservation'))) } : null"
                                    >palette</span>
                                </button>
                                <div
                                    x-show="tagOpen"
                                    x-cloak
                                    x-transition
                                    style="display: none;"
                                    class="absolute top-full mt-2 left-0 z-50 flex items-center gap-1 p-1 rounded-lg bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-lg"
                                >
                                    <template x-for="(col, i) in $store.tagged.palette" :key="i">
                                        <button
                                            type="button"
                                            x-on:click.stop="$store.tagged.setTag(@js($reservation->id), i, @js('reservation')); tagOpen = false"
                                            :aria-label="'رنگ ' + (i + 1)"
                                            class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                                            :class="$store.tagged.getTag(@js($reservation->id), @js('reservation')) === i ? 'border-[var(--md-sys-color-on-surface)]' : 'border-transparent'"
                                            :style="{ 'background-color': col }"
                                        ></button>
                                    </template>
                                    <button
                                        type="button"
                                        x-on:click.stop="$store.tagged.clearTag(@js($reservation->id), @js('reservation')); tagOpen = false"
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
                                x-on:click.stop="$store.pinned.togglePin({{ $reservation->id }}, 'reservation'); $wire.set('gridPinned', $store.pinned.getPinned('reservation'))"
                                :class="$store.pinned.isPinned({{ $reservation->id }}, 'reservation') ? '!opacity-100 text-[var(--md-sys-color-tertiary)]' : 'max-sm:opacity-100 opacity-0 group-hover:opacity-100 text-[var(--md-sys-color-on-surface-variant)]'"
                                class="w-6 h-6 p-0 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                                title="سنجاق کردن رزرو"
                                aria-label="سنجاق کردن رزرو"
                            >
                                <span class="material-symbols-rounded text-[16px]" :class="{ 'font-fill': $store.pinned.isPinned({{ $reservation->id }}, 'reservation') }">push_pin</span>
                            </button>

                            <span @click.stop>
                                <x-dashboard.reminder-trigger :for="$reservation" variant="corner" tooltip-position="top"/>
                            </span>
                        </div>

                        <div class="inline-flex items-center gap-1">
                            <button
                                type="button"
                                wire:click="viewInCanvas({{ $reservation->id }})"
                                class="p-2 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors inline-flex group relative"
                                title="مشاهده جزئیات">
                                <span class="material-symbols-rounded text-[20px] group-hover:scale-110 transition-transform">visibility</span>
                            </button>

                            @if($reservation->history_bucket === 'upcoming')
                                <button
                                    type="button"
                                    wire:key="grid-cancel-button"
                                    x-data
                                    @click="$dispatch('open-confirmation', {
                                        title: @json($reservation->cancel_warning ? 'لغو سری تکرارشونده' : 'لغو رزرو'),
                                        message: @json($reservation->cancel_warning ?? 'آیا از لغو این رزرو اطمینان دارید؟'),
                                        method: 'cancel',
                                        params: {{ $reservation->id }},
                                        type: 'dispatch'
                                    })"
                                    class="p-2 rounded-xl text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] transition-colors inline-flex"
                                    title="لغو رزرو">
                                    <span class="material-symbols-rounded text-[20px]">delete</span>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr wire:key="reservation-history-empty">
                    <td colspan="4" class="py-8">
                        <x-ui.empty icon="{{ $historyShowAll ? 'inbox' : match($activeHistoryTab) { 'upcoming' => 'event_available', 'previous' => 'history_toggle_off', 'cancelled' => 'block', 'released' => 'autorenew', default => 'done_all' } }}" title="صندوق خالی است" description="موردی برای نمایش وجود ندارد." variant="list" />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    @if($this->totalHistoryReservations > count($this->gridReservations))
        <div wire:key="grid-load-more" class="mt-4 flex justify-center w-full">
            <x-ui.buttons.load-more
                action="loadMoreGrid"
                text="موارد بیشتر"
                loadingText="..."
                icon="expand_more"
                class="px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-xs font-semibold shadow-sm"
            />
        </div>
    @endif
</div>
