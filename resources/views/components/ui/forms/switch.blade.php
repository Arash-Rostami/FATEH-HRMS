@props(['name' => null, 'live' => false, 'disabled' => false])

<div class="relative inline-flex items-center">
    <input type="checkbox"
           @if($live) wire:model.live="{{ $name }}" @elseif($name) wire:model="{{ $name }}" @endif
           @if($disabled) disabled @endif
           {{ $attributes->merge(['class' => 'sr-only peer']) }}>

    <div class="relative w-12 h-7 bg-[var(--md-sys-color-surface-variant)] peer-focus:outline-none rounded-xl peer-checked:bg-[var(--md-sys-color-primary)] shadow-inner
                after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border after:border-gray-300 after:rounded-xl after:h-5 after:w-5 after:transition-all
                peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white"></div>
</div>