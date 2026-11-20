@if(!empty($msg['reactions']))
    <div wire:key="messaging-reactions" @class(['flex flex-wrap gap-1 mt-1.5', 'justify-start' => $msg['is_mine'], 'justify-end' => !$msg['is_mine']])>
        @foreach($msg['reactions'] as $r)
            <x-ui.hover-popover wire:key="messaging-reaction-{{ $msg['id'] }}-{{ $r['emoji'] }}" width="w-64" surface="default">
                <x-slot:trigger>
                    <button type="button"
                            x-on:click.prevent.stop="react({{ $msg['id'] }}, '{{ $r['emoji'] }}')"
                            @disabled(!($canReact ?? true))
                            data-reaction-chip="{{ $msg['id'] }}" data-emoji="{{ $r['emoji'] }}"
                            aria-label="واکنش {{ $r['emoji'] }}: {{ $r['names'] }}"
                            x-bind:title="window.matchMedia('(hover: hover) and (pointer: fine)').matches ? null : @js($r['names'])"
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
                </x-slot:trigger>
                <x-slot:body>
                    <div class="p-2.5 flex flex-col gap-1.5 max-h-[280px] overflow-y-auto">
                        <p class="px-1 pb-0.5 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">واکنش نشان داده شده توسط</p>
                        @foreach($r['users'] as $u)
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-md overflow-hidden ring-2 ring-[var(--md-sys-color-surface)] flex-shrink-0">
                                    <x-ui.avatar :existingImage="$u['avatar']" :alt="$u['name']" :inactive="$u['inactive']" icon="person" icon-size="text-[8px]" />
                                </span>
                                <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface)] truncate flex-1 min-w-0">{{ $u['name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-slot:body>
            </x-ui.hover-popover>
        @endforeach
    </div>
@endif
