@props(['title' => 'دستیار هوش مصنوعی'])

@php
    $aiAccess = config('app.ai_assistant_access');
@endphp

@if(auth()->check() && $aiAccess === true && config('services.ai_assistant.url'))
    <button @click="$dispatch('ai-assistant')"
            class="group relative w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-on-primary)]/10 active:bg-[var(--md-sys-color-on-primary)]/20 active:scale-95 transition-all duration-200 flex items-center justify-center animate-flash"
            {{ $attributes->merge(['class' => '']) }}>
        <span class="material-symbols-rounded text-[22px] text-[var(--header-border-color)]">auto_awesome</span>
        <x-ui.modals.tooltip :text="$title" position="bottom" />
    </button>
@elseif(auth()->check() && $aiAccess === 'lock')
    <button @click="$dispatch('toast', {message: 'برای دسترسی به دستیار هوش مصنوعی باید طرح خود را ارتقا دهید', type: 'warning'})"
            class="group relative w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 flex items-center justify-center"
            {{ $attributes->merge(['class' => '']) }}>
        <span class="material-symbols-rounded text-[22px] text-[var(--header-border-color)] opacity-50 transition-opacity duration-200 group-hover:opacity-70">auto_awesome</span>
        <span class="absolute -bottom-1 -left-1 flex items-center justify-center rounded-md bg-[var(--md-sys-color-tertiary)] shadow-[0_2px_6px_color-mix(in_srgb,var(--md-sys-color-tertiary)_40%,transparent)] transition-transform duration-200 group-hover:scale-110"
              style="width:17px;height:17px;">
            <span class="material-symbols-rounded text-[var(--md-sys-color-on-tertiary)]" style="font-size:10px;">lock</span>
        </span>
        <x-ui.modals.tooltip text="ارتقا طرح برای دسترسی" position="bottom" />
    </button>
@endif
