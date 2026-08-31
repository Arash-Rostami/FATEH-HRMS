@props([
    'state' => 'view',
    'action' => 'toggleView',
    'responsive' => false,
    'modes' => [
        ['value' => 'card', 'icon' => 'grid_view', 'title' => 'نمای کارتی'],
        ['value' => 'list', 'icon' => 'view_list', 'title' => 'نمای لیستی'],
    ],
])

<div class="{{ $responsive ? 'flex' : 'hidden md:flex' }} bg-[var(--md-sys-color-surface-container-high)] p-0.5 rounded-lg border border-[var(--md-sys-color-outline-variant)]/40">
    @foreach($modes as $mode)
        @php($click = $state . " = '" . $mode['value'] . "'" . ($action ? ";\$wire." . $action . "('" . $mode['value'] . "')" : ''))
        <button
            type="button"
            title="{{ $mode['title'] }}"
            @click="{{ $click }}"
            :class="{{ $state }} === '{{ $mode['value'] }}' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm scale-105' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
            class="flex items-center justify-center w-8 h-8 rounded-md transition-all duration-200">
            <span class="material-symbols-rounded text-[18px]">{{ $mode['icon'] }}</span>
        </button>
    @endforeach
</div>