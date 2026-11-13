@props(['id', 'scope'])

<button
    type="button"
    x-on:click.stop="$store.pinned.togglePin(@js($id), @js($scope))"
    x-on:keydown.enter.stop
    x-on:keydown.space.stop
    :aria-pressed="$store.pinned.isPinned(@js($id), @js($scope))"
    :aria-label="$store.pinned.isPinned(@js($id), @js($scope)) ? 'برداشتن نشان' : 'نشان کردن'"
    :title="$store.pinned.isPinned(@js($id), @js($scope)) ? 'برداشتن نشان' : 'نشان کردن'"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg p-1.5 transition-colors']) }}
    :class="$store.pinned.isPinned(@js($id), @js($scope))
        ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-primary)]'">
    <span class="material-symbols-rounded text-[20px]" aria-hidden="true"
          :class="$store.pinned.isPinned(@js($id), @js($scope)) ? 'font-fill' : ''">bookmark</span>
</button>
