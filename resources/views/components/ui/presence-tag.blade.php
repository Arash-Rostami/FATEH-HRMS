@props([
    'status' => null,
])

@php
    $enum = $status === null ? null : presence($status);
@endphp

@if ($enum && ! $enum->isReachable())
<span title="{{ $enum->label() }}: ممکن است پاسخ‌گو نباشد"
      {{ $attributes->merge(['class' => 'inline-flex items-center shrink-0 px-1.5 py-0.5 rounded-md bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[10px] font-bold leading-none']) }}>پاسخ‌گو نیست</span>
@endif
