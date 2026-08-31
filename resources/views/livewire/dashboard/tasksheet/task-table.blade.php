@php($presenter = $taskBoardPresenter)

<div class="relative overflow-hidden rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)]">
    <div class="w-full overflow-x-auto"
         x-data="{
            sortKey: null, sortDir: 'asc',
            sort(key) {
                this.sortDir = (this.sortKey === key && this.sortDir === 'asc') ? 'desc' : 'asc';
                this.sortKey = key;
                const dir = this.sortDir === 'asc' ? 1 : -1;
                const rows = Array.from(this.$refs.tbody.children);
                rows.sort((a, b) => {
                    const av = a.dataset[key] ?? '', bv = b.dataset[key] ?? '';
                    if (av === '' && bv === '') return 0;
                    if (av === '') return 1;
                    if (bv === '') return -1;
                    return av > bv ? dir : (av < bv ? -dir : 0);
                });
                rows.forEach(r => this.$refs.tbody.appendChild(r));
            }
         }">
        <table class="min-w-full w-full border-separate border-spacing-0 text-sm">
            <thead class="bg-[var(--md-sys-color-surface-container-high)] text-xs uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]">
                <tr>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold first:rounded-tr-2xl transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">عنوان</th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">اولویت</th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">وضعیت</th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">
                        @unless($readOnly)
                            <button type="button" @click="sort('deadline')" class="print:hidden flex w-full items-center justify-center gap-1.5 transition-colors hover:text-[var(--md-sys-color-primary)]" :class="sortKey === 'deadline' ? 'text-[var(--md-sys-color-primary)]' : ''">
                                <span>مهلت</span>
                                <span class="material-symbols-rounded text-[14px]" :class="sortKey === 'deadline' ? '' : 'opacity-40'" x-text="sortKey === 'deadline' ? (sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </button>
                        @endunless
                        <span class="{{ $readOnly ? '' : 'hidden' }} print:inline">مهلت</span>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">
                        @unless($readOnly)
                            <button type="button" @click="sort('completedAt')" class="print:hidden flex w-full items-center justify-center gap-1.5 transition-colors hover:text-[var(--md-sys-color-primary)]" :class="sortKey === 'completedAt' ? 'text-[var(--md-sys-color-primary)]' : ''">
                                <span>تاریخ تکمیل</span>
                                <span class="material-symbols-rounded text-[14px]" :class="sortKey === 'completedAt' ? '' : 'opacity-40'" x-text="sortKey === 'completedAt' ? (sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </button>
                        @endunless
                        <span class="{{ $readOnly ? '' : 'hidden' }} print:inline">تاریخ تکمیل</span>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">
                        @unless($readOnly)
                            <button type="button" @click="sort('cycle')" class="print:hidden flex w-full items-center justify-center gap-1.5 transition-colors hover:text-[var(--md-sys-color-primary)]" :class="sortKey === 'cycle' ? 'text-[var(--md-sys-color-primary)]' : ''">
                                <span>مدت انجام</span>
                                <span class="material-symbols-rounded text-[14px]" :class="sortKey === 'cycle' ? '' : 'opacity-40'" x-text="sortKey === 'cycle' ? (sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more'"></span>
                            </button>
                        @endunless
                        <span class="{{ $readOnly ? '' : 'hidden' }} print:inline">مدت انجام</span>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold last:rounded-tl-2xl transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)]">به‌موقع</th>
                </tr>
            </thead>
            <tbody x-ref="tbody">
                @forelse($tasks as $task)
                    @php($priorityChip = $presenter->priorityChip($task['priority']))
                    @php($statusState = $presenter->columnState($task['status']))
                    <tr wire:key="tasksheet-task-{{ $task['task_id'] }}"
                        data-deadline="{{ $task['deadline'] ?? '' }}"
                        data-completed-at="{{ $task['completed_at'] ?? '' }}"
                        data-cycle="{{ $task['cycle_time_days'] !== null ? sprintf('%015.4f', $task['cycle_time_days']) : '' }}"
                        class="hover:bg-[var(--md-sys-color-surface-container-low)] transition-colors">
                        <td class="border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-right align-middle">
                            <a href="{{ route('tasks', ['open' => $task['task_id']]) }}" wire:navigate dir="auto" class="font-medium text-[var(--md-sys-color-on-surface)] hover:text-[var(--md-sys-color-primary)] hover:underline transition-colors">
                                {{ superClean($task['title']) }}
                            </a>
                        </td>
                        <td class="border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle">
                            @if($priorityChip)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold {{ $priorityChip['class'] }}">{{ $priorityChip['label'] }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle">
                            <x-ui.decor.status-pill :state="$statusState"/>
                        </td>
                        <td class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle">
                            {{ $task['deadline'] ? toJalaliSmart($task['deadline']) : '—' }}
                        </td>
                        <td class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle">
                            {{ $task['completed_at'] ? toJalaliSmart($task['completed_at']) : '—' }}
                        </td>
                        <td class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle tabular-nums">
                            {{ $task['cycle_time_days'] !== null ? convertToPersian(number_format($task['cycle_time_days'], 1)) . ' روز' : '—' }}
                        </td>
                        <td class="border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle">
                            @if($task['on_time'] === true)
                                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-tertiary)]" title="به‌موقع">check_circle</span>
                            @elseif($task['on_time'] === false)
                                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-error)]" title="با تأخیر">cancel</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8">
                            <x-ui.empty icon="checklist" title="وظیفه‌ای در این بازه ثبت نشده" variant="filtered"/>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
