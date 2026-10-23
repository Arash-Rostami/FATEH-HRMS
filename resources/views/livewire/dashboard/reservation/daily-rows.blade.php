@use('App\Enums\ReservationStatus')
@php($data = $this->dailyData)
<div wire:key="reservation-daily-list" class="flex flex-col gap-5 p-4">
    @if($data['groups'] !== [])
        <x-ui.table wire:key="reservation-daily-table" cardClass="relative rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">
            <x-slot:head>
                <tr>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 text-right font-bold first:rounded-tr-2xl">منبع</th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 text-right font-bold">همکار</th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 text-right font-bold">زمان</th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 text-center font-bold last:rounded-tl-2xl">ارتباط</th>
                </tr>
            </x-slot:head>

            @foreach($data['groups'] as $group)
                <tr wire:key="reservation-daily-day-{{ $group['key'] }}" class="bg-[var(--md-sys-color-surface-container)]">
                    <td colspan="4" class="border-b border-[var(--md-sys-color-outline-variant)] px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">event</span>
                            <span class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $group['heading'] }}</span>
                            <x-ui.badge>{{ convertToPersian((string) count($group['rows'])) }} رزرو</x-ui.badge>
                        </div>
                    </td>
                </tr>

                @foreach($group['rows'] as $row)
                    @php($resource = $row['resource'])
                    <tr wire:key="reservation-daily-row-{{ $row['reservation']->id }}" class="transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-low)]">
                        <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 align-middle">
                            <div class="flex items-center gap-3">
                                @if($row['image'])
                                    <button type="button"
                                            x-on:click="src = @js($row['image']); caption = @js($resource->name); viewer = true"
                                            aria-label="مشاهده تصویر {{ $resource->name }}"
                                            title="مشاهده تصویر"
                                            class="group/img relative h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-[var(--md-sys-color-surface-container-high)] ring-1 ring-[var(--md-sys-color-outline-variant)] transition hover:ring-2 hover:ring-[var(--md-sys-color-primary)]">
                                        <img src="{{ $row['image'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        <span class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover/img:opacity-100">
                                            <span class="material-symbols-rounded text-[18px] text-white">zoom_in</span>
                                        </span>
                                    </button>
                                @else
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-container-high)]">
                                        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">{{ $row['type']?->getMaterialIcon() ?? 'category' }}</span>
                                    </div>
                                @endif
                                <div class="flex min-w-0 flex-col">
                                    <span class="truncate text-[13px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $resource->name }}</span>
                                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $row['type']?->getLabel() ?? $resource->type }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 align-middle">
                            @if($row['person'])
                                <div class="flex items-center gap-3">
                                    <x-dashboard.user-avatar :user="$row['person']" size="sm" />
                                    <div class="flex min-w-0 flex-col">
                                        <span class="truncate text-[13px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $row['person']->name }}</span>
                                        @if($row['dept'])
                                            <span class="truncate text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $row['dept'] }}</span>
                                        @endif
                                    </div>
                                    @if($row['mine'])
                                        <x-ui.badge variant="info">رزرو شما</x-ui.badge>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ $row['anonymous'] }}</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 align-middle">
                            <div class="flex flex-col items-start gap-1">
                                <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $row['time'] }}</span>
                                @if($row['reservation']->status === ReservationStatus::Released->value)
                                    <x-ui.badge variant="warning">آزادشده</x-ui.badge>
                                @endif
                            </div>
                        </td>

                        <td class="border-b border-[var(--md-sys-color-outline-variant)] px-4 py-3 text-center align-middle">
                            @if($row['contact'])
                                <x-ui.modals.popover alignment="bottom-full left-0 origin-bottom-left">
                                    <x-slot:trigger>
                                        <x-ui.buttons.form variant="tonal" icon="forum" class="!h-9 !px-3 text-xs">ارتباط</x-ui.buttons.form>
                                    </x-slot:trigger>
                                    <x-slot:content>
                                        <div class="px-4 pb-0.5 pt-2">
                                            <x-ui.badge variant="info" class="!px-1.5 !py-0.5 !text-[10px]">درخواست دستی</x-ui.badge>
                                        </div>
                                        <x-ui.modals.popover-item icon="chat" label="پیام در چت" :href="$row['contact']['chatUrl']" />
                                        @if($row['contact']['extTel'])
                                            <x-ui.modals.popover-item icon="dialpad" label="تماس با داخلی" :href="'tel:'.$row['contact']['extTel']" />
                                        @endif
                                        @if($row['contact']['mobileTel'])
                                            <x-ui.modals.popover-item icon="call" label="تماس با موبایل" :href="'tel:'.$row['contact']['mobileTel']" />
                                        @endif
                                        <div class="px-4 pb-0.5 pt-2">
                                            <x-ui.badge variant="warning" class="!px-1.5 !py-0.5 !text-[10px]">درخواست سیستمی</x-ui.badge>
                                        </div>
                                        @foreach($data['presets'] as $key => $preset)
                                            <x-ui.modals.popover-item wire:key="reservation-daily-preset-{{ $row['reservation']->id }}-{{ $key }}" :icon="$preset['icon']" :label="$preset['label']" :last="$loop->last" wire:click="reachOut({{ $row['reservation']->id }}, '{{ $key }}')" />
                                        @endforeach
                                    </x-slot:content>
                                </x-ui.modals.popover>
                            @else
                                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </x-ui.table>
    @else
        <div wire:key="reservation-daily-empty" class="contents">
            <x-ui.empty icon="event_busy" title="رزروی در این بازه پیدا نشد" description="بازه، نوع منبع یا عبارت جستجو را تغییر دهید." />
        </div>
    @endif
</div>
