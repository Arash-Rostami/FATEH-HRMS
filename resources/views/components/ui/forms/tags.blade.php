@props([
    'label',
    'name',
    'tags' => [],
    'max' => null,
    'maxLength' => null,
    'addAction' => 'addTag',
    'removeAction' => 'removeTag',
    'disabled' => false,
    'prefix' => 'tags',
    'placeholder' => 'افزودن برچسب جدید و فشردن Enter…',
    'addLabel' => 'افزودن برچسب',
    'removeLabel' => 'حذف برچسب',
    'empty' => null,
])

@php
    $full = $max !== null && count($tags) >= $max;
@endphp

<div {{ $attributes->class('space-y-3.5') }}>
    <div class="flex items-center gap-2.5">
        <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]">label</span>
        <label for="{{ $name }}" class="text-xs sm:text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $label }}</label>
        @if(count($tags) > 0)
            <span class="px-2 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-[11px] font-semibold">{{ convertToPersian(count($tags)) }}</span>
        @endif
    </div>

    @unless($disabled || $full)
        <div
            wire:key="{{ $prefix }}-adder"
            x-data="tagInput()"
            data-add="{{ $addAction }}"
            class="relative flex items-center rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-lowest)] p-1 transition-all focus-within:border-[var(--md-sys-color-primary)] focus-within:ring-2 focus-within:ring-[var(--md-sys-color-primary)]/20">
            <input type="text"
                   id="{{ $name }}"
                   x-model="value"
                   @keydown.enter.prevent="add()"
                   @if($maxLength) maxlength="{{ $maxLength }}" @endif
                   placeholder="{{ $placeholder }}"
                   class="w-full bg-transparent px-3 py-1.5 text-xs sm:text-sm text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-outline)] outline-none border-none focus:ring-0"/>
            <button type="button"
                    @click="add()"
                    x-bind:disabled="blank"
                    class="shrink-0 flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] transition-all hover:opacity-90 active:scale-95 disabled:opacity-40 disabled:pointer-events-none"
                    aria-label="{{ $addLabel }}">
                <span class="material-symbols-rounded text-base sm:text-lg">add</span>
            </button>
        </div>
    @endunless

    @error($name)
        <p wire:key="{{ $prefix }}-error" class="text-xs text-[var(--md-sys-color-error)] animate-pulse">{{ $message }}</p>
    @enderror

    @if(count($tags) > 0)
        <div wire:key="{{ $prefix }}-chips" class="flex flex-wrap gap-2">
            @foreach($tags as $index => $tag)
                <span wire:key="{{ $prefix }}-{{ $index }}-{{ $tag }}"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/40 transition-all">
                    <span>{{ $tag }}</span>
                    @unless($disabled)
                        <button type="button"
                                wire:click="{{ $removeAction }}({{ $index }})"
                                class="w-4 h-4 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] transition-colors"
                                aria-label="{{ $removeLabel }}">
                            <span class="material-symbols-rounded text-[13px]">close</span>
                        </button>
                    @endunless
                </span>
            @endforeach
        </div>
    @elseif($empty)
        <div wire:key="{{ $prefix }}-empty" class="w-full py-4 text-center">
            <p class="text-xs text-[var(--md-sys-color-outline)] font-medium">{{ $empty }}</p>
        </div>
    @endif
</div>
