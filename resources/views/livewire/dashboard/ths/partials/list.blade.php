@php
    $priorityMap = [
        'low'    => ['color'=>'text-green-500', 'icon' => 'arrow_drop_down', 'title'=>'اولویت پایین'],
        'medium' => ['color'=>'text-[var(--md-sys-color-primary)]', 'icon' => 'commit', 'title'=>'اولویت متوسط'],
        'high'   => ['color'=>'text-[var(--md-sys-color-error)]', 'icon' => 'arrow_drop_up', 'title'=>'اولویت بالا'],
    ];
        $statusMap = [
            'open'        => ['icon'=>'pending', 'color'=>'text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]', 'title'=>'باز', 'pulse' => true],
            'in-progress' => ['icon'=>'sync', 'color'=>'text-[var(--md-sys-color-tertiary)] bg-[var(--md-sys-color-tertiary-container)]', 'title'=>'در حال بررسی', 'spin' => true],
            'closed'      => ['icon'=>'check_circle', 'color'=>'text-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary-container)]', 'title'=>'بسته شده', 'pulse' => false],
        ];
@endphp

<div
    class="overflow-hidden bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl shadow-sm relative">
    <div class="overflow-x-auto w-full">
        <table
            class="min-w-full text-sm text-right whitespace-nowrap lg:whitespace-normal text-[var(--md-sys-color-on-surface)]">
            <thead
                class="bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)] uppercase font-medium text-xs border-b border-[var(--md-sys-color-outline-variant)]">
            <tr>
                <th scope="col" class="px-6 py-4 rounded-tr-lg">شناسه / تاریخ</th>
                <th scope="col" class="px-6 py-4">وضعیت</th>
                <th scope="col" class="px-6 py-4 hidden md:table-cell">حوزه درخواست</th>
                <th scope="col" class="px-6 py-4 hidden sm:table-cell w-1/3">موضوع</th>
                <th scope="col" class="px-6 py-4 hidden lg:table-cell">مسئول</th>
                <th scope="col" class="px-6 py-4 text-center rounded-tl-lg">عملیات</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)]">
            @forelse($this->tickets as $ticket)
                @php
                    $prio = $priorityMap[$ticket->priority] ?? null;
                    $stat = $statusMap[$ticket->status] ?? null;
                    $fId = 'T-' . $ticket->created_at->format('ym') . '-' . str_pad($ticket->id, 4, '0', STR_PAD_LEFT);
                @endphp
                <tr class="hover:bg-[var(--md-sys-color-surface-container-lowest)] transition-colors group cursor-pointer"
                    wire:click="viewTicket({{ $ticket->id }}); $dispatch('ths-modal')">
                    <td class="px-6 py-4 flex items-center gap-3">
                        <div
                            class="flex flex-col items-center justify-center w-10 h-10 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] shrink-0 border border-[var(--md-sys-color-outline-variant)]/30 group-hover:border-[var(--md-sys-color-primary)]/30 transition-colors"
                            title="{{ $prio['title'] ?? '' }}">
                            @if($prio)
                                <span class="material-symbols-rounded text-xl {{ $prio['color'] }}">

                                    {{ $prio['icon'] == 'commit' ? '󠁯•󠁏' : $prio['icon']  }}
                                </span>
                            @endif
                        </div>
                        <div class="flex flex-col cursor-help"
                             title="{{ jdate($ticket->created_at) }}"
                             dir="ltr">
                            <span class="font-bold text-[var(--md-sys-color-on-surface)] text-[13px] tracking-wider font-mono flex-row-reverse flex">{{ $fId }}</span>
                            <span
                                class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1 mt-0.5">
                                    <span class="material-symbols-rounded text-[12px]">schedule</span>
                                 {{ $ticket->created_at->diffForHumans() }}
                                </span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        @if($stat)
                            <div title="{{ jdate($ticket->completion_date ?? now()) }}"
                                 class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide {{ $stat['color'] }}">
                                <span class="material-symbols-rounded text-[14px] {{ isset($stat['pulse']) && $stat['pulse'] ? 'animate-pulse' : '' }} {{ isset($stat['spin']) && $stat['spin'] ? 'animate-spin' : '' }}">{{ $stat['icon'] }}</span>
                                {{ $stat['title'] }}
                            </div>
                        @endif
                    </td>

                    <td class="px-6 py-4 hidden md:table-cell text-[13px] text-[var(--md-sys-color-on-surface-variant)]">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)]"></span>
                            {{ $ticket->getRequestAreaOptions($ticket->request_type, $ticket->request_area) }}
                        </div>
                    </td>

                    <td class="px-6 py-4 hidden sm:table-cell">
                        <div class="flex flex-col">
                                <span
                                    class="font-bold text-[13px] text-[var(--md-sys-color-on-surface)] truncate max-w-[200px] lg:max-w-[300px]"
                                    title="{{ $ticket->request_subject }}">
                                    {{ Str::limit($ticket->request_subject, 40) }}
                                </span>
                            <span
                                class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] truncate max-w-[200px] lg:max-w-[300px] mt-0.5"
                                title="{{ $ticket->description }}">
                                    {{ Str::limit($ticket->description, 50) }}
                                </span>
                        </div>
                    </td>

                    <td class="px-6 py-4 hidden lg:table-cell text-[13px]">
                        @if($ticket->assignee)
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-6 h-6 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center font-bold text-[10px] uppercase">
                                    {{ mb_substr($ticket->assignee->name, 0, 1) }}
                                </div>
                                <span
                                    class="font-medium text-[var(--md-sys-color-on-surface)]">{{ $ticket->assignee->name }}</span>
                            </div>
                        @else
                            <span
                                class="text-[var(--md-sys-color-on-surface-variant)] italic text-[11px] px-2 py-0.5 bg-[var(--md-sys-color-surface-container-low)] rounded border border-dashed border-[var(--md-sys-color-outline-variant)]">در انتظار تخصیص</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-center">
                        <button
                            class="p-2 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors inline-flex group relative"
                            title="مشاهده جزئیات">
                            <span
                                class="material-symbols-rounded text-[20px] group-hover:scale-110 transition-transform">visibility</span>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-[var(--md-sys-color-on-surface-variant)]">
                        <div class="flex flex-col items-center justify-center">
                            <span class="material-symbols-rounded text-4xl mb-3 opacity-50">inbox</span>
                            <p class="text-sm font-medium">هیچ تیکتی یافت نشد.</p>
                            <p class="text-[11px] mt-1 opacity-70">درخواست‌های ارسالی شما در اینجا نمایش داده
                                می‌شوند.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 flex justify-center rtl" dir="ltr"></div>

@if($this->tickets->hasMorePages())
    <div class="flex justify-center pb-6 w-full mt-4">
        <x-dashboard.button.load-more
            action="loadMore"
            text="بارگذاری بیشتر"
            loading-text="در حال دریافت..."
            icon="expand_more"
            class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
        />
    </div>
@endif
