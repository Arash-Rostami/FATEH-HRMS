@props(['text' => 'در حال بارگذاری...'])

<div class="grid place-items-center gap-3 text-[var(--md-sys-color-on-surface-variant)]">
    <div class="size-12 animate-spin rounded-full bg-[conic-gradient(var(--md-sys-color-primary)_0%,transparent_35%,var(--md-sys-color-primary)_50%,transparent_85%)] shadow-[0_0_12px_var(--md-sys-color-primary)] [mask-image:radial-gradient(farthest-side,transparent_calc(100%-3px),#000_calc(100%-3px))] [-webkit-mask-image:radial-gradient(farthest-side,transparent_calc(100%-3px),#000_calc(100%-3px))]"></div>
    <span class="text-xs font-bold opacity-70">{{ $text }}</span>
</div>
