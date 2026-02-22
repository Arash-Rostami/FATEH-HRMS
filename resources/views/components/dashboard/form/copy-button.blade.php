@props(['text'])

<button
    x-data="{ copied: false }"
    x-on:click="
        navigator.clipboard.writeText($el.dataset.text);
        copied = true;
        setTimeout(() => copied = false, 2000);
    "
    data-text="{{ $text }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg p-2 text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-primary)] transition-colors']) }}
    title="کپی در کلیپ‌بورد"
>
    <template x-if="!copied">
        <span class="material-symbols-rounded text-[20px]">content_copy</span>
    </template>
    <template x-if="copied">
        <span class="material-symbols-rounded text-[20px] text-green-500">check</span>
    </template>
</button>
