<div
    class="flex flex-col h-full bg-[var(--md-sys-color-surface)] rounded-2xl overflow-hidden shadow-sm border border-[var(--md-sys-color-outline-variant)]/20 relative group transition-all duration-300"
    :class="{
        'shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] ring-1 ring-[var(--md-sys-color-primary)]/30': activeId == {{ $feed->id }}
    }"
>
    <div
        x-show="activeId == {{ $feed->id }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-x-0"
        x-transition:enter-end="opacity-100 scale-x-100"
        class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] z-20"
        style="box-shadow: 0 2px 8px color-mix(in srgb, var(--md-sys-color-primary) 60%, transparent);"
    ></div>

    @include('livewire.dashboard.tab.feeds.header', ['feed' => $feed])

    <div class="flex-1 overflow-y-auto feed-scrollbar p-5 md:p-6 space-y-5 pb-6">
        @if(!empty($feed?->content))
            <div class="text-sm leading-[2] text-[var(--md-sys-color-on-surface)] text-right text-justify" dir="rtl">
                {!! superClean($feed->content) !!}
            </div>
        @endif

        @php $flags = $presenter->feedFlags($feed); @endphp

        @if($flags['isPoll'] && !empty($feed?->poll_options))
            @php $poll = $presenter->pollData($feed); @endphp
            <div class="space-y-2 pt-1" dir="rtl">
                @foreach($poll['options'] as $index => $option)
                    @php $state = $presenter->optionState((int) $index, $poll); @endphp
                    <button
                        type="button"
                        @guest disabled @endguest
                        wire:click="vote({{ $feed->id }}, {{ $index }})"
                        wire:key="poll-opt-{{ $feed->id }}-{{ $index }}"
                        class="relative w-full overflow-hidden rounded-xl border px-3 py-2.5 text-right transition-all duration-200 disabled:opacity-100 disabled:cursor-default {{ $state['isMine'] ? 'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]/30' : 'border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-variant)]/40' }} {{ auth()->check() ? 'cursor-pointer hover:border-[var(--md-sys-color-primary)]/40 active:scale-[0.99]' : '' }}"
                    >
                        <span class="absolute inset-y-0 right-0 bg-[var(--md-sys-color-primary)]/15 transition-all duration-500" style="width: {{ $state['pct'] }}%"></span>
                        <span class="relative flex items-center justify-between gap-2">
                            <span class="flex items-center gap-1.5 text-[13px] font-medium text-[var(--md-sys-color-on-surface)]">
                                @if($state['isMine'])
                                    <span class="material-symbols-rounded !text-[15px] text-[var(--md-sys-color-primary)]">check_circle</span>
                                @endif
                                {!! superClean($option) !!}
                            </span>
                            <span class="shrink-0 text-[11px] font-bold tabular-nums text-[var(--md-sys-color-on-surface-variant)]">
                                {{ $state['count'] }}@if($poll['total'] > 0)<span class="opacity-50 mx-0.5">·</span>%{{ $state['pct'] }}@endif
                            </span>
                        </span>
                    </button>
                @endforeach

                <div class="flex items-center justify-between px-1 pt-0.5">
                    <span class="flex items-center gap-1.5">
                        <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ $poll['total'] }} رأی</span>
                        @if($poll['isMultiple'])
                            <span class="text-[10px] font-medium rounded px-1.5 py-0.5 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">چندانتخابی</span>
                        @endif
                    </span>
                    @auth
                        @if(!empty($poll['userVotes']))
                            <span class="flex items-center gap-1 text-[11px] font-medium text-[var(--md-sys-color-primary)]">
                                <span class="material-symbols-rounded !text-[13px]">how_to_vote</span>
                                رأی شما ثبت شد
                            </span>
                        @endif
                    @endauth
                </div>
            </div>
        @endif

        @if(!empty($feed?->media_paths))
            <div class="rounded-xl overflow-hidden border border-[var(--md-sys-color-outline-variant)]/10 bg-[var(--md-sys-color-surface-variant)] shadow-sm">
                @include('livewire.dashboard.tab.feeds.media', ['media' => $feed->media_paths])
            </div>
        @endif

        @if($flags['showComments'])
        <div x-data="{ open: false, loaded: false }" class="mt-4">
            <button
                @click="open = !open; if (open && !loaded) { $wire.openComments({{ $feed->id }}); loaded = true; }"
                class="w-full flex items-center justify-between p-3 rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 text-[var(--md-sys-color-primary)] text-sm font-bold transition-all hover:bg-[var(--md-sys-color-primary-container)]/30"
            >
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-xl">forum</span>
                    <span>نظرات ({{ $feed?->comments?->count() ?? 0 }})</span>
                </div>
                <span
                    class="material-symbols-rounded transition-transform duration-200"
                    :class="open ? 'rotate-180' : ''"
                >expand_more</span>
            </button>

            <div x-show="open" x-collapse>
                @if($openedCommentFeeds[$feed->id] ?? false)
                    @include('livewire.dashboard.tab.feeds.comments', ['comments' => $feed?->comments, 'feed' => $feed])
                @endif
            </div>
        </div>
        @endif
    </div>

    @if($flags['showReactions'])
    <div class="px-3 md:px-4 py-2.5 border-t border-[var(--md-sys-color-outline-variant)]/20 bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] rounded-b-lg overflow-visible">
        @if($feed?->reactions?->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-2">
                @foreach($feed->reactions->groupBy('emoji') as $emoji => $reactions)
                    <button
                        wire:click="toggleReaction({{ $feed->id }}, '{{ $emoji }}')"
                        title="کسانی که واکنش دادند: {{ $reactions->pluck('user.name')->filter()->implode('، ') ?: '—' }}"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-[var(--md-sys-color-surface-variant)]/50 border border-[var(--md-sys-color-outline-variant)]/20 text-xs transition-all cursor-help hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]"
                    >
                        <span>{!! superClean($emoji) !!}</span>
                        <span class="font-bold">{{ $reactions?->count() ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        @include('livewire.dashboard.tab.feeds.actions', ['feed' => $feed])
    </div>
    @endif
</div>
