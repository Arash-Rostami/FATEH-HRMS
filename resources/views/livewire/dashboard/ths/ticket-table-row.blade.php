@php
    $prio = $presenter->priorityMeta($ticket->priority);
    $stat = $presenter->statusMeta($ticket->status);
    $deadlineChip = $presenter->deadlineChip($ticket->completion_deadline, $ticket->completion_date, $ticket->status);
    $fId = $presenter->formatId($ticket->toArray());
    $isMine = $ticket->requester_id === auth()->id();
    $urgency = $ticket->urgencyState;
    $isUrgent = $urgency['score'] > 0;
@endphp
<tr wire:key="ticket-{{ $ticket->id }}" data-rf="ticket-{{ $ticket->id }}" class="relative isolate hover:bg-[var(--md-sys-color-primary)]/[0.03] active:bg-[var(--md-sys-color-primary)]/[0.06] transition-colors group cursor-pointer {{ $isUrgent ? 'taskboard-card--urgent' : '' }}"
    style="{{ $isUrgent ? '--urgency:' . $urgency['score'] . ';' : '' }}"
    @if($isUrgent) title="{{ $urgency['label'] }}" @endif
    wire:click="viewTicket({{ $ticket->id }}); $dispatch('ths-modal')">
    <td class="px-6 py-4 flex items-center gap-3">
        <div class="absolute inset-0 -z-10 pointer-events-none transition-colors duration-200"
             :style="{ 'background-color': $store.tagged.tagBg(@js($ticket->id), @js('ths')) }"></div>
        <div
            class="flex flex-col items-center justify-center w-10 h-10 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] shrink-0 ring-1 ring-[var(--md-sys-color-outline-variant)]/20 group-hover:ring-[var(--md-sys-color-primary)]/30 transition-colors"
            title="{{ $prio['title'] ?? '' }}">
            @if($prio)
                <span class="material-symbols-rounded text-xl {{ $prio['color'] }}">
                    {{ $prio['icon'] == 'commit' ? '󠁯•󠁏' : $prio['icon']  }}
                </span>
            @endif
        </div>
        <div class="flex flex-col cursor-help" title="{{ jdate($ticket->created_at) }}" dir="ltr">
            <span class="font-bold text-[var(--md-sys-color-on-surface)] text-[13px] tracking-wider font-mono flex-row-reverse flex">{{ $fId }}</span>
            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1 mt-0.5">
                <span class="material-symbols-rounded text-[12px]">schedule</span>
                {{ toJalali($ticket->created_at, 'j F Y') }}
            </span>
        </div>
    </td>

    <td data-col="status" class="px-6 py-4">
        @if($stat)
            <div
                dir="ltr"
                title="{{ jdate($ticket->completion_date ?? now()) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-bold tracking-wide {{ $stat['textColor'] }} {{ $stat['bg'] }}">
                <span
                    class="material-symbols-rounded text-[14px] {{ isset($stat['pulse']) && $stat['pulse'] ? 'animate-pulse' : '' }} {{ isset($stat['spin']) && $stat['spin'] ? 'animate-spin' : '' }}">{{ $stat['icon'] }}</span>
                {{ $stat['title'] }}
            </div>
        @endif
        @if($deadlineChip)
            <div wire:key="ths-row-deadline" class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border text-[10px] font-bold {{ $deadlineChip['classes'] }}">
                <span class="material-symbols-rounded text-[12px]">{{ $deadlineChip['icon'] }}</span>
                {{ $deadlineChip['text'] }}
            </div>
        @endif
        @if($ticket->replies_count || !empty($ticket->requester_files) || !empty($ticket->assignee_files))
            <div wire:key="ths-row-meta" class="mt-1.5 flex items-center gap-2 text-[10px] text-[var(--md-sys-color-on-surface-variant)]">
                @if($ticket->replies_count)
                    <span wire:key="ths-row-replies" class="inline-flex items-center gap-0.5" title="تعداد پاسخ‌ها">
                        <span class="material-symbols-rounded text-[12px]">forum</span>
                        {{ convertToPersian($ticket->replies_count) }}
                    </span>
                @endif
                @if(!empty($ticket->requester_files) || !empty($ticket->assignee_files))
                    <span wire:key="ths-row-files" class="inline-flex items-center gap-0.5" title="فایل پیوست دارد">
                        <span class="material-symbols-rounded text-[12px]">attach_file</span>
                        {{ convertToPersian(count($ticket->requester_files ?? []) + count($ticket->assignee_files ?? [])) }}
                    </span>
                @endif
            </div>
        @endif
    </td>

    <td data-col="domain" class="px-6 py-4 hidden md:table-cell text-[13px] text-[var(--md-sys-color-on-surface-variant)]">
        <div class="flex items-center gap-2">
            <span class="material-symbols-rounded">{{ $presenter->requestAreaIcon($ticket->request_area) }}</span>
            {{ $ticket->getRequestAreaOptions($ticket->request_type, $ticket->request_area) }}
        </div>
    </td>

    <td class="px-6 py-4 hidden sm:table-cell">
        <div class="flex flex-col">
            <span
                class="inline-flex items-center gap-2 font-bold text-[13px] text-[var(--md-sys-color-on-surface)] truncate max-w-[200px] lg:max-w-[300px]"
                title="{{ $ticket->request_subject }}">
                <span class="shrink-0 w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)]"></span>
                <span class="truncate">{{ Str::limit($ticket->request_subject, 40) }}</span>
            </span>
            <span
                class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] truncate max-w-[200px] lg:max-w-[300px] mt-0.5"
                title="{{ $ticket->description }}">
                {{ Str::limit($ticket->description, 50) }}
            </span>
        </div>
    </td>

    <td data-col="requester" class="px-6 py-4 hidden lg:table-cell text-[13px]">
        {{ $isMine ? 'شما' : ($ticket->requester?->name ?? '—') }}
    </td>

    <td data-col="assignee" class="px-6 py-4 hidden lg:table-cell text-[13px]">
        @if($ticket->assignee)
            <div wire:key="ths-row-assignee" class="flex items-center gap-2">
                <div
                    class="w-6 h-6 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center font-bold text-[10px] uppercase">
                    {{ mb_substr($ticket->assignee->name, 0, 1) }}
                </div>
                <span class="font-medium text-[var(--md-sys-color-on-surface)]">{{ $ticket->assignee->name }}</span>
            </div>
        @else
            <span wire:key="ths-row-unassigned"
                class="inline-flex items-center gap-1.5 text-[var(--md-sys-color-on-surface-variant)] italic text-[11px] px-2 py-0.5 bg-[var(--md-sys-color-surface-container-low)] rounded border border-dashed border-[var(--md-sys-color-outline-variant)]">
                <span class="material-symbols-rounded text-[12px]">hourglass_empty</span> در انتظار تخصیص
            </span>
        @endif
    </td>

    <td class="relative px-6 py-4 text-center">
        <div class="absolute left-2 top-2 z-20 flex items-center gap-1">
            <div x-data="{ tagOpen: false }" class="relative shrink-0" x-on:click.away="tagOpen = false">
                <button
                    type="button"
                    x-on:click.stop="tagOpen = !tagOpen"
                    :class="$store.tagged.isTagged(@js($ticket->id), @js('ths')) ? '!opacity-100' : 'max-sm:opacity-100 opacity-0 group-hover:opacity-100'"
                    class="w-6 h-6 p-0 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)] transition-all duration-200 active:scale-95 flex items-center justify-center"
                    title="رنگ‌آمیزی تیکت"
                    aria-label="رنگ‌آمیزی تیکت"
                >
                    <span
                        class="material-symbols-rounded text-[16px]"
                        :style="$store.tagged.isTagged(@js($ticket->id), @js('ths')) ? { color: $store.tagged.solid($store.tagged.getTag(@js($ticket->id), @js('ths'))) } : null"
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
                            x-on:click.stop="$store.tagged.setTag(@js($ticket->id), i, @js('ths')); tagOpen = false"
                            :aria-label="'رنگ ' + (i + 1)"
                            class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110"
                            :class="$store.tagged.getTag(@js($ticket->id), @js('ths')) === i ? 'border-[var(--md-sys-color-on-surface)]' : 'border-transparent'"
                            :style="{ 'background-color': col }"
                        ></button>
                    </template>
                    <button
                        type="button"
                        x-on:click.stop="$store.tagged.clearTag(@js($ticket->id), @js('ths')); tagOpen = false"
                        aria-label="برداشتن رنگ"
                        title="برداشتن رنگ"
                        class="w-5 h-5 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-error)]"
                    >
                        <span class="material-symbols-rounded text-[14px]">close</span>
                    </button>
                </div>
            </div>

            <span @click.stop>
                <x-dashboard.reminder-trigger :for="$ticket" variant="corner" tooltip-position="bottom"/>
            </span>
        </div>

        <div class="inline-flex items-center gap-1">
            <button
                class="p-2 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors inline-flex group relative"
                title="مشاهده جزئیات">
                <span
                    class="material-symbols-rounded text-[20px] group-hover:scale-110 transition-transform">visibility</span>
            </button>
        </div>
    </td>
</tr>
