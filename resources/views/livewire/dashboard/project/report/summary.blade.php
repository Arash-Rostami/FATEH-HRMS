@php($s = $summary)
@php($percent = (int) round($s['percent']))
<div class="flex flex-wrap items-center gap-3 justify-between rounded-2xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-container-low)] px-6 py-3 shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)]">
    @if($s['total'] === 0)
        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">هنوز وظیفه‌ای ثبت نشده</p>
        <x-ui.decor.progress-ring :percent="0" :size="36" :stroke="4" :color="'var(--md-sys-color-on-surface-variant)'"/>
    @else
        @php($by = $s['by_status'])
        @php($chips = [
            ['key' => 'todo',        'label' => 'انجام‌نشده',  'icon' => 'radio_button_unchecked', 'class' => 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]'],
            ['key' => 'in-progress', 'label' => 'در حال انجام', 'icon' => 'progress_activity',     'class' => 'bg-[var(--tool-sapphire-bg)] text-[var(--tool-sapphire-text)]'],
            ['key' => 'pending',     'label' => 'در انتظار',   'icon' => 'hourglass_top',         'class' => 'bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)]'],
            ['key' => 'done',        'label' => 'انجام‌شده',   'icon' => 'check_circle',          'class' => 'bg-[var(--tool-sage-bg)] text-[var(--tool-sage-text)]'],
        ])
        <div class="flex items-center flex-wrap gap-2">
            @foreach($chips as $chip)
                @php($count = (int) ($by[$chip['key']] ?? 0))
                @php($isStatusActive = $reportStatusFilter === $chip['key'])
                @if($count > 0)
                    <button type="button" wire:click="setReportStatusFilter('{{ $chip['key'] }}')" aria-pressed="{{ $isStatusActive ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] {{ $chip['class'] }} hover:brightness-110 active:scale-95 transition {{ $isStatusActive ? 'ring-2 ring-[var(--md-sys-color-primary)] outline-none' : '' }}">
                        <span class="material-symbols-rounded text-[14px]">{{ $chip['icon'] }}</span>
                        <span class="tabular-nums">{{ convertToPersian($count) }}</span>
                        <span>{{ $chip['label'] }}</span>
                    </button>
                @endif
            @endforeach
            @if($s['overdue'] > 0)
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] hover:brightness-110 active:scale-95 transition">
                    <span class="material-symbols-rounded text-[14px]">notification_important</span>
                    <span class="tabular-nums">{{ convertToPersian((int) $s['overdue']) }}</span>
                    <span>سررسید گذشته</span>
                </span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <x-ui.decor.progress-ring :percent="$percent" :size="36" :stroke="4" :color="$percent >= 100 ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)'"/>
            <div class="leading-tight">
                <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($percent) }}٪</p>
                <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian((int) $s['done']) }} از {{ convertToPersian((int) $s['total']) }} وظیفه</p>
            </div>
        </div>
    @endif
</div>