@props(['text'])

<button
    x-data="{ copied: false }"
    x-on:click="
        navigator.clipboard.writeText($el.dataset.text);
        copied = true;
        setTimeout(() => copied = false, 2000);
    "
    data-text="{{ $text }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-blue-500 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-blue-400 transition-colors']) }}
    title="Copy to clipboard"
>
    <template x-if="!copied">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
    </template>
    <template x-if="copied">
        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
    </template>
</button>
