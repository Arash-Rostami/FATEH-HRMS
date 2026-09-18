@php
    $types = [
        ['key' => 't', 'icon' => 'chat', 'title' => 'متن'],
        ['key' => 'i', 'icon' => 'image', 'title' => 'تصویر'],
        ['key' => 'v', 'icon' => 'movie', 'title' => 'ویدیو'],
        ['key' => 'p', 'icon' => 'picture_as_pdf', 'title' => 'PDF'],
        ['key' => 'f', 'icon' => 'attach_file', 'title' => 'فایل'],
    ];
@endphp
<div class="flex-shrink-0 flex items-center gap-1.5">
    <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-on-surface-variant)] opacity-50" title="فیلتر نوع محتوا">filter_alt</span>
    @foreach($types as $t)
        <button type="button"
                x-on:click="toggleTypeFilter('{{ $t['key'] }}')"
                :class="typeFilter.includes('{{ $t['key'] }}')
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]'
                    : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
                :aria-pressed="typeFilter.includes('{{ $t['key'] }}')"
                title="{{ $t['title'] }}"
                aria-label="{{ $t['title'] }}"
                class="w-7 h-7 rounded-full flex items-center justify-center transition-all duration-150 hover:brightness-95 active:scale-95">
            <span class="material-symbols-rounded text-[15px]">{{ $t['icon'] }}</span>
        </button>
    @endforeach
</div>