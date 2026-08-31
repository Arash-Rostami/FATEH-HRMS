@php($d = $projectPresenter->reportRowDetail($row))
@php($caption = 'text-[10px] uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]/70 font-medium')
@php($uk = $d['kind'])
@php($replies = $d['replies'])
@php($attachments = $d['attachments'])
@php($checkTotal = $d['checkTotal'])
@php($checkDone = $d['checkDone'])
@php($ticketId = $d['ticketId'])
@php($urgClass = $d['urgClass'])
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
    @if(!empty($row['description']))
        <div class="md:col-span-2">
            <p class="{{ $caption }} mb-1">توضیحات</p>
            <p class="text-sm leading-relaxed text-[var(--md-sys-color-on-surface)] line-clamp-4" dir="auto">{{ superClean($row['description'], 1000) }}</p>
        </div>
    @endif

    @if(!empty($row['labels']))
        <div class="md:col-span-2">
            <p class="{{ $caption }} mb-1.5">برچسب‌ها</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($d['labels'] as $label)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]"
                          style="background: var(--tool-{{ $label['tone'] }}-bg); color: var(--tool-{{ $label['tone'] }}-text);">
                        <span class="material-symbols-rounded text-[12px]">sell</span>
                        <span dir="auto">{{ $label['label'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if(!empty($row['meta_chips']))
        <div class="md:col-span-2">
            <p class="{{ $caption }} mb-1.5">دیتای سفارشی</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($row['meta_chips'] as $metaChip)
                    <span title="{{ $metaChip['label'] }}: {{ $metaChip['value'] }}"
                          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded text-[12px]">database</span>
                        <span class="max-w-[100px] truncate" dir="auto">{{ $metaChip['label'] }}</span>
                        <span class="max-w-[120px] truncate" dir="auto">{{ $metaChip['value'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if($checkTotal > 0)
        @php($progressPercent = (int) ($row['progress_percent'] ?? 0))
        <div>
            <p class="{{ $caption }} mb-1.5">زیروظیفه‌ها (چک‌لیست)</p>
            <div class="flex items-center gap-3">
                <x-ui.decor.progress-ring :percent="$progressPercent" :size="40" :stroke="4" :label="$checkDone.'/'.$checkTotal" :color="$progressPercent === 100 ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)'"/>
                <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian($checkDone) }} مورد از {{ convertToPersian($checkTotal) }} تکمیل شد — {{ convertToPersian($progressPercent) }}٪</span>
            </div>
        </div>
    @endif

    <div>
        <p class="{{ $caption }} mb-1.5">وضعیت فوریت</p>
        @if(in_array($uk, ['overdue', 'due', 'idle'], true))
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] {{ $urgClass }}">
                <span class="material-symbols-rounded text-[12px]">{{ $uk === 'idle' ? 'hourglass_top' : 'notification_important' }}</span>
                <span>{{ $row['urgency']['label'] ?? '' }}</span>
            </span>
        @else
            <span class="text-sm text-[var(--md-sys-color-on-surface-variant)]">—</span>
        @endif
    </div>

    <div>
        <p class="{{ $caption }} mb-1">ایجاد توسط</p>
        <p class="text-sm text-[var(--md-sys-color-on-surface)] flex items-center gap-1.5">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">arrow_downward</span>
            <span dir="auto">{{ $row['creator_name'] ?? '—' }}</span>
        </p>
    </div>

    <div>
        <p class="{{ $caption }} mb-1">واگذاری</p>
        <p class="text-sm text-[var(--md-sys-color-on-surface)] flex items-center gap-1.5">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">arrow_upward</span>
            @if(($row['creator_name'] ?? null) === ($row['assignee_name'] ?? null) && $row['assignee_name'])
                <span class="text-[var(--md-sys-color-on-surface-variant)]">بدون واگذاری</span>
            @else
                <span dir="auto">{{ $row['assignee_name'] ?? '—' }}</span>
            @endif
        </p>
    </div>

    <div>
        <p class="{{ $caption }} mb-1">مهلت</p>
        <p class="text-sm text-[var(--md-sys-color-on-surface)]">
            {{ $row['deadline'] ? toJalaliSmart($row['deadline']) : '—' }}
            @if(in_array($uk, ['overdue', 'due'], true))
                <span class="text-xs text-[var(--md-sys-color-error)] mr-1">{{ $row['urgency']['label'] ?? '' }}</span>
            @endif
        </p>
    </div>

    <div>
        <p class="{{ $caption }} mb-1">تاریخ ایجاد</p>
        <p class="text-sm text-[var(--md-sys-color-on-surface)]">{{ $row['created_formatted'] ?? '—' }}</p>
    </div>

    @if($replies > 0 || $attachments > 0)
        <div>
            <p class="{{ $caption }} mb-1">گفتگو و مستندات</p>
            <div class="flex items-center gap-3 text-sm">
                @if($replies > 0)
                    <span class="inline-flex items-center gap-1 text-[var(--tool-sapphire-text)]"><span class="material-symbols-rounded text-[14px]">forum</span>{{ convertToPersian($replies) }} پاسخ</span>
                @endif
                @if($attachments > 0)
                    <span class="inline-flex items-center gap-1 text-[var(--tool-gold-text)]"><span class="material-symbols-rounded text-[14px]">attach_file</span>{{ convertToPersian($attachments) }} فایل</span>
                @endif
            </div>
        </div>
    @endif

    @if($ticketId)
        <div>
            <p class="{{ $caption }} mb-1.5">تیکت مرتبط</p>
            <a href="{{ route('ths', ['open' => $ticketId]) }}" wire:navigate
               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:brightness-110 transition w-fit">
                <span class="material-symbols-rounded text-[12px]">support_agent</span>
                <span>از تیکت #{{ convertToPersian($ticketId) }}</span>
            </a>
        </div>
    @endif

    <div class="md:col-span-2 flex items-center justify-end gap-2 pt-3 mt-1 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]">
        <a href="{{ route('tasks', ['open' => $row['id']]) }}" wire:navigate
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 active:scale-95 transition shadow-sm">
            <span class="material-symbols-rounded text-[16px]">open_in_new</span>
            <span>مشاهده در تسک‌بورد</span>
        </a>
    </div>
</div>