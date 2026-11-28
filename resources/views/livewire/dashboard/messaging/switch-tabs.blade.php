<div wire:key="messaging-switch" class="flex items-center gap-1">
    @if(count($tabs) > 1)
        <x-ui.buttons.module-links :tabs="$tabs" :active="$active"/>
    @endif
</div>