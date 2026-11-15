@if(!empty($msg['reactions']))
    <div wire:key="messaging-reactions" @class(['flex flex-wrap gap-1 mt-1.5', 'justify-start' => $msg['is_mine'], 'justify-end' => !$msg['is_mine']])>
        @foreach($msg['reactions'] as $r)
            <button type="button" wire:key="messaging-reaction-{{ $msg['id'] }}-{{ $r['emoji'] }}"
                    x-on:click.prevent="react({{ $msg['id'] }}, '{{ $r['emoji'] }}')"
                    data-reaction-chip="{{ $msg['id'] }}" data-emoji="{{ $r['emoji'] }}"
                    title="{{ $r['names'] }}"
                    style="--burst-emoji: '{{ $r['emoji'] }}'"
                    @class([
                        'relative inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[11px] font-bold border transition-all active:scale-95 animate-spring-pop motion-reduce:!animate-none reaction-burst',
                        'reaction-mine' => $r['mine'],
                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-[color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] hover:brightness-110' => $r['mine'],
                        'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] hover:bg-[var(--md-sys-color-surface-variant)]/60' => !$r['mine'],
                    ])>
                <span class="reaction-emoji text-[13px] leading-none">{{ $r['emoji'] }}</span>
                <span class="reaction-count">{{ convertToPersian($r['count']) }}</span>
            </button>
        @endforeach
    </div>
@endif
