@props([
    'model' => 'search',
    'name' => null,
    'id' => null,
    'debounce' => 200,
    'placeholder' => 'جستجو...',
    'ariaLabel' => null,
    'showLabel' => false,
    'loadingDisabled' => false,
    'wireIgnoreSelf' => true,
    'overlayTitle' => null,
    'refreshSidebarOnClose' => false,
    'inputClass' => 'md3-input peer pr-10 pl-10 h-10 leading-[40px] rounded-xl text-sm outline-none transition-all focus:ring-2 w-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 placeholder-transparent',
])

@php
    $name = $name ?? $model;
    $id = $id ?? $model;
    $ariaLabel = $ariaLabel ?? $placeholder;
    $overlayTitle = $overlayTitle ?? $placeholder;
    $closeExpr = "{$model}Fullscreen = false; \$wire.{$model} = {$model}Value" . ($refreshSidebarOnClose ? "; \$wire.\$island('sidebar').refreshUnread().catch(() => {})" : '');
@endphp

<div class="relative group w-full" @if($wireIgnoreSelf) wire:ignore.self @endif>
    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors z-10">
        <span class="material-symbols-rounded text-[20px]">search</span>
    </div>

    <button type="button" title="تمام صفحه" aria-label="تمام صفحه" x-on:click="{{ $model }}Fullscreen = true; {{ $model }}Value = $wire.{{ $model }}"
            class="absolute inset-y-0 left-0 pl-3 flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:scale-110 transition-all z-10 cursor-pointer">
        <span class="material-symbols-rounded text-[18px]">fullscreen</span>
    </button>

    <input type="text" name="{{ $name }}" id="{{ $id }}"
           wire:model.live.debounce.{{ $debounce }}ms="{{ $model }}"
           @if($loadingDisabled) wire:loading.attr="disabled" @endif
           aria-label="{{ $ariaLabel }}"
           class="{{ $inputClass }}"
           placeholder="{{ $placeholder }}" autocomplete="off" spellcheck="false" />

    <button type="button" title="حذف" aria-label="حذف" wire:click="$set('{{ $model }}', '')" wire:loading.attr="disabled"
            x-show="$wire.{{ $model }} && $wire.{{ $model }}.length > 0" x-transition:opacity style="display: none;"
            class="absolute inset-y-0 left-10 pl-3 flex items-center text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-opacity duration-200">
        <span class="material-symbols-rounded text-[18px]">close</span>
    </button>

    @if($showLabel)
        <label for="{{ $id }}" class="md3-label absolute top-1/2 -translate-y-1/2 pointer-events-none peer-placeholder-shown:right-10 peer-focus:right-4 right-4 transition-all duration-200">{{ $placeholder }}</label>
    @endif
</div>

<template x-teleport="body" wire:key="search-overlay-{{ $model }}">
    <div x-show="{{ $model }}Fullscreen" dir="rtl" style="display: none;"
         x-on:keydown.escape="{{ $closeExpr }}"
         x-effect="if ({{ $model }}Fullscreen) $nextTick(() => $el.querySelector('textarea')?.focus())"
         aria-modal="true"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 !bg-[var(--md-sys-color-primary)]/60 "
         class="fixed inset-0 z-[9999] flex flex-col bg-[var(--md-sys-color-surface)]/95">
        <div class="flex items-center justify-between p-4 md:p-6 border-b border-[var(--md-sys-color-outline-variant)]/30">
            <div class="flex items-center gap-3">
                <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">search</span>
                <span class="text-[var(--md-sys-color-on-surface)] font-medium">{{ $overlayTitle }}</span>
                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)]">fullscreen</span>
            </div>
            <button type="button" title="بستن" aria-label="بستن" x-on:click="{{ $closeExpr }}"
                    class="p-2 !pb-0 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                <span class="material-symbols-rounded text-[20px]">close</span>
            </button>
        </div>
        <div class="flex-1 p-4 md:p-6 md:max-w-5xl md:mx-auto w-full">
            <textarea x-model="{{ $model }}Value"
                      class="w-full h-full resize-none outline-none bg-transparent text-[var(--md-sys-color-on-surface)] text-lg md:text-xl placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 focus:ring-0 border-none"
                      placeholder="{{ $placeholder }}"></textarea>
        </div>
    </div>
</template>
