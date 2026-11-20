<div wire:key="launchpad-freshness" class="me-auto flex min-w-0 flex-wrap items-center gap-1">
    <x-ui.badge :variant="$openFresh['variant']">{{ $openFresh['text'] }}</x-ui.badge>
    @if($openFresh['reason'])
        <span wire:key="launchpad-freshness-reason" class="text-xs break-words text-[var(--md-sys-color-on-surface-variant)]">{{ $openFresh['reason'] }}</span>
    @endif
    @if($openFresh['manage'])
        <x-ui.buttons.form wire:key="launchpad-freshness-retry" type="button" variant="tonal" size="icon" icon="sync" title="دریافت اطلاعات تازه" aria-label="دریافت اطلاعات تازه"
                           class="!h-8 !w-8 !p-0 !rounded-lg" loading="refreshNow" wire:click="refreshNow({{ $openId }})"/>
    @endif
</div>
