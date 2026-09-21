@props(['name' => null, 'value' => null, 'live' => false, 'disabled' => false])

<div class="relative inline-flex items-center justify-center w-5 h-5 shrink-0 group-hover/cb:scale-105 transition-transform duration-300 ease-out">
    <input type="checkbox"
           @if($live)
               wire:model.live="{{ $name }}"
           @elseif($name)
               wire:model="{{ $name }}"
           @endif
           @if($value !== null) value="{{ $value }}" @endif
           @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'peer appearance-none w-5 h-5 rounded-[6px] border-2 border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_80%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-highest)_40%,transparent)] checked:border-[var(--md-sys-color-primary)] checked:bg-[var(--md-sys-color-primary)] hover:border-[color-mix(in_srgb,var(--md-sys-color-primary)_70%,transparent)] focus:outline-none focus:ring-4 focus:ring-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] focus:ring-offset-0 checked:shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all duration-300 ease-out cursor-pointer disabled:cursor-not-allowed disabled:opacity-50']) }}/>

    <span class="material-symbols-rounded absolute text-[var(--md-sys-color-on-primary)] text-[15px] font-black scale-0 opacity-0 peer-checked:scale-100 peer-checked:opacity-100 pointer-events-none transition-all duration-300 ease-out">check</span>
</div>
