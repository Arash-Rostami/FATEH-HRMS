@if(count($schemeProgress))
    <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-container-low)] px-6 py-3">
        @foreach($schemeProgress as $row)
            @php($percent = $row['total'] > 0 ? (int) round($row['done'] / $row['total'] * 100) : 0)
            <div class="flex items-center gap-3">
                <x-ui.decor.progress-ring :percent="$percent" :size="30" :stroke="4" :color="$percent >= 100 ? 'var(--md-sys-color-tertiary)' : 'var(--md-sys-color-primary)'"/>
                <div class="leading-tight">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $row['scheme'] }}</p>
                    <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian($row['done']) }} از {{ convertToPersian($row['total']) }} وظیفه</p>
                </div>
            </div>
        @endforeach
    </div>
@endif
