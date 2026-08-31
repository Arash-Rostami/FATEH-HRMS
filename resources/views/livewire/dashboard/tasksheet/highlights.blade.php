@php($cards = $presenter->highlightCards($report))

@if(!empty($cards))
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($cards as $card)
            <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-4 flex flex-col gap-2 animate-slide-up-fade animate-delay-{{ $loop->index * 100 }} hover:border-[var(--md-sys-color-primary)]/40 hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] transition-[border-color,box-shadow] duration-300">
                <div class="flex items-center gap-2 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">{{ $card['icon'] }}</span>
                    {{ $card['label'] }}
                </div>
                <a href="{{ route('tasks', ['open' => $card['item']['task_id']]) }}" wire:navigate dir="auto"
                   class="font-bold text-sm text-[var(--md-sys-color-on-surface)] hover:text-[var(--md-sys-color-primary)] transition-colors truncate">
                    {{ superClean($card['item']['title']) }}
                </a>
                <div class="flex flex-wrap items-center gap-2 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                    @if($card['kind'] === 'hardest_close')
                        <span class="tabular-nums">{{ convertToPersian(number_format($card['item']['cycle_time_days'], 1)) }} روز</span>
                        @php($chip = $taskBoardPresenter->priorityChip($card['item']['priority']))
                        @if($chip)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold {{ $chip['class'] }}">{{ $chip['label'] }}</span>
                        @endif
                    @elseif($card['kind'] === 'fastest_turnaround')
                        <span class="tabular-nums">{{ convertToPersian(number_format($card['item']['cycle_time_days'], 1)) }} روز</span>
                    @elseif($card['kind'] === 'most_collaborated')
                        <span class="tabular-nums">{{ convertToPersian($card['item']['comments_count']) }} نظر همکاران</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
