@props([
    'icon',
    'label',
    'color' => 'violet',
    'active',
    'action',
])

@php
    $toneClass = match ($color) {
        'emerald' => 'bg-emerald-500',
        'indigo' => 'bg-indigo-500',
        'teal' => 'bg-teal-500',
        'amber' => 'bg-amber-500',
        default => 'bg-violet-500',
    };

    $textToneClass = match ($color) {
        'emerald' => 'group-hover:text-emerald-500',
        'indigo' => 'group-hover:text-indigo-500',
        'teal' => 'group-hover:text-teal-500',
        'amber' => 'group-hover:text-amber-500',
        default => 'group-hover:text-violet-500',
    };
@endphp

<button type="button" @click="{{ $action }}"
        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
             :class="({{ $active }}) ? '{{ $toneClass }} text-white' : 'bg-[var(--md-sys-color-surface-container)] opacity-50'">
            <span class="material-symbols-rounded text-[20px]">{{ $icon }}</span>
        </div>
        <span class="text-sm font-medium {{ $textToneClass }}">{{ $label }}</span>
    </div>
    <div class="relative w-9 h-5 rounded-full transition-colors duration-200"
         :class="({{ $active }}) ? '{{ $toneClass }}' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
        <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200"
             :class="({{ $active }}) ? 'translate-x-4' : 'translate-x-0'"></div>
    </div>
</button>
