@php($summary = $this->reportSummary)
@php($data = $this->reportData)
@php($options = $this->reportFilterOptions)
@php($schemeProgress = $this->reportSchemeProgress)
@php($attachments = $this->reportAttachments)
@php($anyFilter = $this->reportIsFiltered)
@php($groupedReportRows = $projectPresenter->groupedReportRows($data['rows']))

<div class="mt-4 flex flex-col gap-4" wire:key="report-{{ $activeProjectId }}" @project-report-refresh.window="$wire.refreshReport()">
    @include('livewire.dashboard.project.report-summary', ['summary' => $summary])
    @include('livewire.dashboard.project.report-filters', ['options' => $options])
    @include('livewire.dashboard.project.report-scheme-progress', ['schemeProgress' => $schemeProgress])
    @include('livewire.dashboard.project.report-attachments', ['attachments' => $attachments, 'dmsPresenter' => $dmsPresenter])

    <div class="relative overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full text-sm text-right whitespace-nowrap lg:whitespace-normal">
                <thead class="bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)] uppercase font-medium text-xs border-b border-[var(--md-sys-color-outline-variant)]">
                    <tr>
                        <th class="px-6 py-4"></th>
                        <th class="px-6 py-4">عنوان</th>
                        <th class="px-6 py-4 hidden lg:table-cell">دپارتمان</th>
                        <th class="px-6 py-4 hidden md:table-cell">مسئول</th>
                        <th class="px-6 py-4">وضعیت</th>
                        @include('livewire.dashboard.project.report-sort-th', ['field' => 'priority', 'label' => 'اولویت'])
                        @include('livewire.dashboard.project.report-sort-th', ['field' => 'deadline', 'label' => 'مهلت'])
                        @include('livewire.dashboard.project.report-sort-th', ['field' => 'last_activity_at', 'label' => 'آخرین فعالیت'])
                        <th class="px-6 py-4 hidden md:table-cell">پیشرفت</th>
                        <th class="px-6 py-4 text-center">شاخص‌ها</th>
                    </tr>
                </thead>
                <tbody x-data="{ expanded: null }" @keydown.escape.window="expanded = null" wire:loading.class="opacity-60 pointer-events-none" class="divide-y divide-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)]">
                    @forelse($groupedReportRows as $deptKey => $rowsInGroup)
                    <tr class="bg-[var(--md-sys-color-surface-container-low)]">
                        <td colspan="10" class="px-6 py-2 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                            {{ $deptKey === '' ? 'بدون دپارتمان' : ($rowsInGroup->first()['department_label'] ?? $deptKey) }}
                            <span class="opacity-60 font-normal">({{ convertToPersian($rowsInGroup->count()) }})</span>
                        </td>
                    </tr>
                    @foreach($rowsInGroup as $row)
                        @php($rf = $projectPresenter->reportRowFlag($row))
                        <tr wire:key="report-row-{{ $row['id'] }}" @click="expanded = (expanded === {{ $row['id'] }} ? null : {{ $row['id'] }})" :class="{ 'bg-[var(--md-sys-color-primary)]/[0.06]': expanded === {{ $row['id'] }} }" class="hover:bg-[var(--md-sys-color-primary)]/[0.03] transition-colors cursor-pointer">
                            <td class="px-6 py-4">
                                <button type="button" tabindex="0" @click.stop="expanded = (expanded === {{ $row['id'] }} ? null : {{ $row['id'] }})" @keydown.enter.prevent="expanded = (expanded === {{ $row['id'] }} ? null : {{ $row['id'] }})" @keydown.space.prevent="expanded = (expanded === {{ $row['id'] }} ? null : {{ $row['id'] }})" :aria-expanded="expanded === {{ $row['id'] }}" class="flex items-center justify-center w-6 h-6 rounded-lg hover:bg-[var(--md-sys-color-primary-container)]/40 transition-colors">
                                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200" :class="expanded === {{ $row['id'] }} ? 'rotate-90' : ''">chevron_left</span>
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('tasks', ['open' => $row['id']]) }}" wire:navigate dir="auto" @click.stop class="hover:text-[var(--md-sys-color-primary)] hover:underline transition-colors">{{ superClean($row['title']) }}</a>
                                    <x-ui.buttons.copy :text="route('tasks', ['open' => $row['id']])" message="لینک وظیفه کپی شد" @click.stop class="!w-6 !h-6 !p-0"/>
                                </div>
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">{{ $row['department_label'] ?? '—' }}</td>
                            <td class="px-6 py-4 hidden md:table-cell">{{ $row['assignee_name'] ?? '—' }}</td>
                            <td class="px-6 py-4"><x-ui.decor.status-pill :state="$presenter->columnState($row['status'])"/></td>
                            <td class="px-6 py-4">
                                @php($chip = $presenter->priorityChip($row['priority'] ?? null))
                                @if($chip)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] {{ $chip['class'] }}">
                                        <span class="material-symbols-rounded text-[12px] {{ $chip['isUrgent'] ? 'font-fill' : '' }}">flag</span>
                                        <span class="{{ $chip['isUrgent'] ? 'animate-pulse-ring' : '' }}">{{ $chip['label'] }}</span>
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($row['deadline'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]
                                        @if($rf['overdue']) bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]
                                        @elseif($rf['due']) bg-[var(--md-sys-color-error-container)]/60 text-[var(--md-sys-color-on-error-container)]
                                        @else bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] @endif"
                                        title="{{ $row['deadline_formatted'] ?? '' }}">
                                        <span class="material-symbols-rounded text-[12px]">{{ $rf['idle'] ? 'hourglass_top' : 'schedule' }}</span>
                                        <span class="tracking-wide">{{ in_array($rf['kind'], ['overdue', 'due', 'idle'], true) ? ($row['urgency']['label'] ?? '') : toJalaliSmart($row['deadline']) }}</span>
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">{{ $row['last_activity_at'] ? toJalaliSmart($row['last_activity_at']) : '—' }}</td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <x-ui.decor.progress-ring :percent="$row['progress_percent']" :size="30" :stroke="4" :color="$row['progress_percent'] >= 100 ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)'"/>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2 text-[14px]">
                                    @if(($row['replies_count'] ?? 0) > 0)
                                        <span class="inline-flex items-center gap-0.5 text-[var(--tool-sapphire-text)]" title="{{ $row['replies_count'] }} پاسخ">
                                            <span class="material-symbols-rounded text-[14px]">forum</span>
                                            <span class="text-[12px] tabular-nums">{{ convertToPersian((int) $row['replies_count']) }}</span>
                                        </span>
                                    @endif
                                    @if(($row['attachments_count'] ?? 0) > 0)
                                        <span class="inline-flex items-center gap-0.5 text-[var(--tool-gold-text)]" title="{{ $row['attachments_count'] }} فایل">
                                            <span class="material-symbols-rounded text-[14px]">attach_file</span>
                                            <span class="text-[12px] tabular-nums">{{ convertToPersian((int) $row['attachments_count']) }}</span>
                                        </span>
                                    @endif
                                    @if(($row['checklist']['total'] ?? 0) > 0)
                                        @php($ct = (int) $row['checklist']['total'])
                                        @php($cd = (int) $row['checklist']['done'])
                                        <span class="inline-flex items-center gap-0.5 text-[var(--md-sys-color-on-surface-variant)]" title="چک‌لیست: {{ $cd }} از {{ $ct }}">
                                            <span class="material-symbols-rounded text-[14px] @if($cd === $ct) text-[var(--md-sys-color-tertiary)] @endif">task_alt</span>
                                            <span class="text-[12px] tabular-nums">{{ convertToPersian($cd) }}/{{ convertToPersian($ct) }}</span>
                                        </span>
                                    @endif
                                    @if(in_array($rf['kind'], ['overdue', 'due'], true))
                                        <span class="inline-flex h-1.5 w-1.5 rounded-full @if($rf['kind'] === 'overdue') bg-[var(--md-sys-color-error)] animate-pulse-ring @else bg-[var(--tool-gold-text)] @endif" title="{{ $row['urgency']['label'] ?? '' }}"></span>
                                    @elseif($rf['idle'])
                                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-[var(--md-sys-color-on-surface-variant)]/40" title="{{ $row['urgency']['label'] ?? '' }}"></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr wire:key="report-detail-{{ $row['id'] }}" x-show="expanded === {{ $row['id'] }}" x-cloak x-transition class="bg-[var(--md-sys-color-surface-container-lowest)]">
                            <td colspan="10" class="p-0">
                                <div class="px-6 py-5 ring-1 ring-inset ring-[var(--md-sys-color-primary)]/15 animate-bubble-in">
                                    @include('livewire.dashboard.project.report-row-detail', ['row' => $row])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @empty
                        <tr><td colspan="10" class="px-6 py-12">
                            @if($anyFilter)
                                <x-ui.empty icon="filter_alt_off" title="نتیجه‌ای یافت نشد" variant="list" description="با فیلتر فعلی نتیجه‌ای پیدا نشد. فیلترها را پاک کنید."/>
                                <div class="flex justify-center mt-3">
                                    <x-ui.buttons.form wire:click="clearReportFilters" variant="ghost" icon="filter_alt_off">پاک کردن فیلترها</x-ui.buttons.form>
                                </div>
                            @else
                                <x-ui.empty icon="inbox" title="رکوردی یافت نشد" variant="list" description="هنوز هیچ وظیفه‌ای برای این پروژه ثبت نشده است."/>
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($data['hasMore'])
        <div class="flex justify-center pb-2 w-full">
            <x-ui.buttons.load-more action="loadMoreReport" text="بیشتر" loading-text="در حال بارگذاری…"
                                     class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"/>
        </div>
    @endif
</div>