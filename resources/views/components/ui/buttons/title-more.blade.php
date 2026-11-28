@use('App\Models\Media')
@props(['guide' => null])

@php
    $guideOnly = $guide && !isset($menu) && !isset($actions);
    $hasGuide = $guideOnly && !tenantOff('knowledge:media') && Media::guideFor($guide, auth()->user());
@endphp

<div class="hidden md:flex items-center gap-2">
    {{ $actions ?? '' }}
    @if($guide)
        <x-ui.video-guide :module="$guide"/>
    @endif
</div>
@if(isset($menu) || $hasGuide)
    <div class="md:hidden">
        <x-ui.modals.popover alignment="top-full left-0 origin-top-left">
        <x-slot:trigger>
            <button
                type="button"
                aria-label="ابزارهای بیشتر"
                title="ابزارهای بیشتر"
                aria-haspopup="menu"
                :aria-expanded="open"
                x-on:close-title-more.window="open = false"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">more_horiz</span>
            </button>
        </x-slot:trigger>
        <x-slot:content>
            {{ $menu ?? '' }}
            @if($guide)
                <x-ui.video-guide :module="$guide" variant="row"/>
            @endif
        </x-slot:content>
    </x-ui.modals.popover>
    </div>
@elseif(!$guideOnly)
    <div class="md:hidden" {{ $attributes }}></div>
@endif
