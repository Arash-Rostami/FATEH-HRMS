<div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden print:hidden">
    <button type="button" wire:click="toggleActivity" class="w-full flex items-center gap-2 px-4 py-3 text-sm font-bold text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-low)] transition-colors">
        <span class="material-symbols-rounded text-[18px] transition-transform duration-200 {{ $activityOpen ? 'rotate-90' : '' }}">chevron_left</span>
        <span class="flex-1 flex items-center gap-2">
            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">timeline</span>
            فعالیت‌ها
        </span>
    </button>

    @if($activityOpen)
        @php($feed = $this->activityFeed())
        <div class="border-t border-[var(--md-sys-color-outline-variant)]/60 flex flex-col gap-4 p-4">
            @forelse($feed['days'] as $day)
                <div class="flex items-center gap-3" wire:key="tasksheet-activity-day-{{ $day['date'] }}">
                    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)] opacity-30"></div>
                    <span class="text-[10px] font-bold tracking-wider bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] px-2 py-0.5 rounded-lg">{{ $day['date_jalali'] }}</span>
                    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)] opacity-30"></div>
                </div>

                @foreach($day['items'] as $item)
                    <div class="flex items-start gap-3" wire:key="tasksheet-activity-{{ $item['reply_id'] }}">
                        <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)] mt-1 shrink-0">{{ $item['icon'] }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5 mb-0.5">
                                <span class="text-xs font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $item['actor_name'] }}</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]">{{ $item['label'] }}</span>
                                @if($item['task_title'])
                                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">·</span>
                                    <a href="{{ route('tasks', ['open' => $item['task_id']]) }}" wire:navigate class="text-[10px] text-[var(--md-sys-color-primary)] hover:underline" dir="auto">{{ superClean($item['task_title']) }}</a>
                                @endif
                            </div>
                            @if($item['body'])
                                <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] leading-6" dir="auto">{{ superClean($item['body']) }}</p>
                            @endif
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-60 cursor-help" dir="ltr" title="{{ toJalali($item['created_at']) }}"><span dir="rtl">{{ toJalaliRelative($item['created_at']) }}</span></span>
                        </div>
                    </div>
                @endforeach
            @empty
                <x-ui.empty icon="timeline" title="فعالیتی در این بازه ثبت نشده" variant="list"/>
            @endforelse

            @if($feed['has_more'])
                <div class="flex justify-center print:hidden">
                    <x-ui.buttons.load-more action="loadMoreActivity" text="بارگذاری بیشتر" loading-text="در حال بارگذاری…" icon="expand_more"
                        class="rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 text-xs font-medium text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition"/>
                </div>
            @endif
        </div>
    @endif
</div>
