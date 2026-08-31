@props(['icon', 'actions' => []])

<div {{ $attributes->merge(['class' => 'bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] dark:bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] rounded-2xl p-4 sm:p-5']) }}
     x-transition:enter="animate-toast-in"
     x-transition:leave="animate-fade-out">
    <div class="flex items-start gap-3">
        <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)] flex-shrink-0 mt-0.5">{{ $icon }}</span>
        <div class="min-w-0 flex-1">
            {{ $slot }}
        </div>
    </div>
    @if(count($actions))
        <div class="flex items-center gap-2.5 mt-4">
            @foreach($actions as $action)
                <button type="button"
                        @if(isset($action['onClick'])) x-on:click="{{ $action['onClick'] }}" @endif
                        @class([
                            'flex-1 px-3 py-2.5 rounded-xl text-[12.5px] font-bold whitespace-nowrap active:scale-[0.97] transition-all flex items-center justify-center',
                            'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90' => ($action['variant'] ?? 'primary') === 'primary',
                            'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]' => ($action['variant'] ?? 'primary') === 'secondary',
                        ])>
                    {{ $action['label'] }}
                </button>
            @endforeach
        </div>
    @endif
</div>
