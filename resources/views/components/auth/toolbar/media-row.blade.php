@props(['kind'])

@php
    $base = 'custom' . ucfirst($kind);
    $url = $kind === 'image' ? $base : $base . 'Url';
    [$label, $accept, $removeTitle] = $kind === 'image'
        ? ['تصویر', 'image/*', 'حذف تصویر شخصی']
        : ['ویدیو', 'video/*', 'حذف ویدیوی شخصی'];
@endphp

<div class="flex items-center justify-between gap-3">
    <div>
        <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ $label }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="{{ $url }}" x-cloak
           x-text="'ذخیره‌شده: ' + ({{ $base }}Size / 1048576).toFixed(1) + ' مگابایت'"></p>
    </div>
    <div class="flex items-center gap-1">
        <label class="px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/20 transition-colors">
            <span x-text="{{ $url }} ? 'جایگزینی' : 'انتخاب'"></span>
            <input type="file" accept="{{ $accept }}" class="hidden" @change="pickMedia('{{ $kind }}', $event)">
        </label>
        <button x-show="{{ $url }}" x-cloak @click="clearMedia('{{ $kind }}')"
                class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-500 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                title="{{ $removeTitle }}">
            <span class="material-symbols-rounded text-[18px]">delete</span>
        </button>
    </div>
</div>
