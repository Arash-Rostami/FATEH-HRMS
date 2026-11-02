<button type="button" hidden class="dock-arrow" data-edge="{{ $edge }}"
        aria-label="{{ $edge === 'start' ? 'پیمایش به ابتدای نوار' : 'پیمایش به ادامهٔ نوار' }}">
    <x-filament::icon :icon="$edge === 'start' ? 'heroicon-o-chevron-left' : 'heroicon-o-chevron-right'"/>
</button>
