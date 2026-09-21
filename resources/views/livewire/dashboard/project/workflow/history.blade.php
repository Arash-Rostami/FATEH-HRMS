<div wire:key="workflow-history" class="space-y-2">
    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">چرخه‌های پایان‌یافته</p>
    @foreach($historyWorkflows as $wf)
        <div wire:key="workflow-history-{{ $wf->id }}" class="flex items-center justify-between gap-2 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 px-3 py-2">
            <div class="min-w-0 flex items-center gap-2">
                <x-ui.decor.status-pill :title="$wf->isCompleted() ? 'تکمیل‌شده' : 'لغوشده'" :color="$wf->isCompleted() ? 'tertiary' : 'error'"/>
                <span class="text-xs text-[var(--md-sys-color-on-surface)] truncate">{{ $wf->name }}</span>
            </div>
            @if($isOwner)
                <x-ui.buttons.form wire:click="runAgain({{ $wf->id }})" variant="ghost" icon="replay" size="icon" title="اجرای دوباره"/>
            @endif
        </div>
    @endforeach
</div>
