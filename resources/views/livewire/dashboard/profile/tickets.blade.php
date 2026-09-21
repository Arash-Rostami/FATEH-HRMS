<div class="space-y-5 animate-[fade-in_0.4s_ease-out]" dir="rtl">

    <x-ui.buttons.tab-selector
        :tabs="[
            ['id' => 'ths', 'icon' => 'support_agent', 'label' => 'تیکت‌های من'],
            ['id' => 'support', 'icon' => 'support', 'label' => 'پشتیبانی و بازخورد'],
        ]"
        :active-tab="$activeSection"
        class="!mb-0"
    />

    @if($activeSection === 'ths')
    <div wire:key="profile-tickets-section-ths" class="space-y-5 mt-5">
    <x-ui.table tbodyClass="divide-y divide-[var(--md-sys-color-outline-variant)]/40">
        <x-slot:head>
            <tr>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold first:rounded-tr-2xl">شناسه</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">موضوع</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold">وضعیت</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold hidden lg:table-cell">مسئول</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold hidden sm:table-cell">پاسخ‌ها</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold hidden md:table-cell">تاریخ</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold last:rounded-tl-2xl">اقدام</th>
            </tr>
        </x-slot:head>

        @forelse($this->tickets as $ticket)
                    @php($stat = $presenter->statusMeta($ticket->status))
                    <tr wire:key="profile-ticket-{{ $ticket->id }}" class="hover:bg-[var(--md-sys-color-primary)]/[0.03] transition-colors">
                        <td class="px-6 py-4 font-mono text-[13px] text-[var(--md-sys-color-on-surface)]" dir="ltr">
                            {{ $presenter->formatId($ticket->toArray()) }}
                        </td>

                        <td class="px-6 py-4">
                            <span class="block truncate max-w-[220px] font-medium text-[13px] text-[var(--md-sys-color-on-surface)]"
                                  title="{{ $ticket->request_subject }}">
                                {{ Str::limit($ticket->request_subject, 40) }}
                            </span>
                        </td>

                        <td data-col="status" class="px-6 py-4 text-center">
                            @if($stat)
                                <div dir="ltr" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-bold tracking-wide {{ $stat['textColor'] }} {{ $stat['bg'] }}">
                                    <span class="material-symbols-rounded text-[14px] {{ $stat['pulse'] ? 'animate-pulse' : '' }} {{ $stat['spin'] ? 'animate-spin' : '' }}">{{ $stat['icon'] }}</span>
                                    {{ $stat['title'] }}
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 hidden lg:table-cell text-[13px] text-[var(--md-sys-color-on-surface-variant)]">
                            {{ $ticket->assignee?->name ?? '—' }}
                        </td>

                        <td class="px-6 py-4 hidden sm:table-cell text-center text-[13px] text-[var(--md-sys-color-on-surface-variant)]">
                            {{ convertToPersian($ticket->replies_count) }}
                        </td>

                        <td class="px-6 py-4 hidden md:table-cell text-[13px] text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">
                            {{ toJalali($ticket->created_at, 'j F Y') }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('ths', ['open' => $ticket->id]) }}"
                               class="inline-flex items-center justify-center p-2 rounded-xl text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                               title="مشاهده در سامانه تیکت">
                                <span class="material-symbols-rounded text-[20px]">visibility</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr wire:key="profile-tickets-empty-row">
                        <td colspan="7" class="py-12">
                            <div wire:key="profile-tickets-empty" class="contents">
                                <x-ui.empty icon="support_agent" title="تیکتی ثبت نشده"
                                            description="درخواست‌های شما از سامانه پشتیبانی اینجا نمایش داده می‌شوند."
                                            variant="list"/>
                            </div>
                        </td>
                    </tr>
        @endforelse
    </x-ui.table>

    @if($this->tickets->hasMorePages())
        <div class="flex justify-center pb-2 pt-2">
            <x-ui.buttons.load-more
                action="loadMore"
                text="بارگذاری بیشتر"
                loading-text="در حال دریافت..."
                icon="expand_more"
                class="rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 font-medium text-[var(--md-sys-color-primary)] shadow-sm transition hover:border-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-md"/>
        </div>
    @endif
    </div>
    @endif

    @if($activeSection === 'support')
    <div wire:key="profile-tickets-section-support" class="space-y-5 mt-5">
    <x-ui.table tbodyClass="divide-y divide-[var(--md-sys-color-outline-variant)]/40">
        <x-slot:head>
            <tr>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold first:rounded-tr-2xl">نوع</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">عنوان</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold">وضعیت</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold hidden md:table-cell">تاریخ</th>
                <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold last:rounded-tl-2xl">پاسخ</th>
            </tr>
        </x-slot:head>

        @forelse($this->supportRequests as $item)
            @php($type = $supportPresenter->typeMeta($item->type))
            @php($stat = $supportPresenter->statusMeta($item->status))
            <tr wire:key="profile-support-{{ $item->id }}" class="hover:bg-[var(--md-sys-color-primary)]/[0.03] transition-colors align-top">
                <td class="px-6 py-4">
                    <span dir="ltr" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold" style="background: color-mix(in srgb, {{ $type['color'] }} 15%, transparent); color: {{ $type['color'] }};">
                        <span class="material-symbols-rounded text-[13px]">{{ $type['icon'] }}</span>
                        {{ $type['label'] }}
                    </span>
                </td>

                <td class="px-6 py-4 max-w-[280px]">
                    <span class="block truncate font-medium text-[13px] text-[var(--md-sys-color-on-surface)]" title="{{ $item->title }}">{{ $item->title }}</span>
                    @if(filled($item->response))
                        <span class="flex items-center gap-1 text-[11px] mt-1" style="color: {{ $stat['responseColor'] }};">
                            <span class="material-symbols-rounded text-[13px]">{{ $stat['responseIcon'] }}</span>
                            پاسخ داده شده
                        </span>
                    @endif
                </td>

                <td class="px-6 py-4 text-center">
                    <span dir="ltr" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-bold tracking-wide" style="background: color-mix(in srgb, {{ $stat['color'] }} 15%, transparent); color: {{ $stat['color'] }};">
                        <span class="material-symbols-rounded text-[14px]">{{ $stat['icon'] }}</span>
                        {{ $stat['label'] }}
                    </span>
                </td>

                <td class="px-6 py-4 hidden md:table-cell text-[13px] text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">
                    {{ toJalali($item->created_at, 'j F Y') }}
                </td>

                <td class="px-6 py-4 max-w-[220px]">
                    @if(filled($item->response))
                        <span class="block text-[12px] leading-relaxed text-[var(--md-sys-color-on-surface-variant)] line-clamp-2" title="{{ $item->response }}">{{ $item->response }}</span>
                    @else
                        <span class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] opacity-60">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr wire:key="profile-support-empty-row">
                <td colspan="5" class="py-12">
                    <div wire:key="profile-support-empty" class="contents">
                        <x-ui.empty icon="inbox" title="درخواستی ثبت نشده"
                                    description="درخواست‌های پشتیبانی، پیشنهاد و گزارش باگ شما اینجا نمایش داده می‌شوند."
                                    variant="list"/>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @if($this->supportRequests->hasMorePages())
        <div class="flex justify-center pb-2 pt-2">
            <x-ui.buttons.load-more
                action="loadMoreSupport"
                text="بارگذاری بیشتر"
                loading-text="در حال دریافت..."
                icon="expand_more"
                class="rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 font-medium text-[var(--md-sys-color-primary)] shadow-sm transition hover:border-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-md"/>
        </div>
    @endif
    </div>
    @endif
</div>
