@props(['show' => null])

@if($show ?? appUpdateIsRecent())
    <span class="w-2 h-2 rounded-full bg-[var(--md-sys-color-warning)] animate-pulse shadow-[0_0_8px_color-mix(in_srgb,var(--md-sys-color-warning)_60%,transparent)] mr-auto"></span>
@endif
