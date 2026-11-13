@php
    $showLoadMore = $presenter->showLoadMore(count($this->reports), $this->listLimit);
@endphp

<div class="flex items-center gap-3 mb-2">
    <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shrink-0">
        <span class="material-symbols-rounded text-[20px] font-fill">article</span>
    </div>
    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)] whitespace-nowrap">گزارش‌های شما</h2>    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)] min-w-[20px] opacity-50"></div>
    <span wire:key="ai-report-list-total" title="مجموع گزارش‌های این زبانه"
          class="text-[11px] font-bold px-2.5 py-1 rounded-lg tabular-nums bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">{{ convertToPersian((string) $this->total) }}</span>
</div>

<div wire:key="ai-report-list-tabs" class="w-fit max-w-full mx-auto md:mx-0 z-1 mb-2">
    <x-ui.buttons.tab-selector
        :tabs="[
            ['id' => 'active', 'label' => 'فعال', 'icon' => 'visibility'],
            ['id' => 'inactive', 'label' => 'غیرفعال', 'icon' => 'visibility_off'],
        ]"
        :activeTab="$listTab"
        class="!mb-0"
        buttonBaseClass="relative px-3 py-2 sm:px-6 sm:py-2.5 lg:px-2 lg:py-2 xl:px-3 rounded-xl text-xs sm:text-sm lg:text-xs font-bold transition-all duration-300 z-10 flex items-center gap-1.5 sm:gap-2 lg:gap-1 shrink-0 whitespace-nowrap"
        iconBaseClass="material-symbols-rounded text-base sm:text-lg lg:text-base"
    />
</div>

<div class="bg-[var(--md-sys-color-surface)] rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] p-2 flex flex-col gap-2 shadow-sm overflow-y-auto max-h-[calc(100vh-15rem)]">
@if($this->reports->isEmpty())
    <div wire:key="ai-report-empty-list-{{ $listTab }}" class="contents">
        @if($listTab === 'inactive')
            <x-ui.empty icon="visibility_off" title="گزارش غیرفعالی ندارید"
                         description="گزارشی که غیرفعال کنید اینجا می‌ماند." variant="list"/>
        @else
            <x-ui.empty icon="description" title="هنوز گزارشی ندارید"
                         description="با نوشتن درخواست در فرم، نخستین گزارش ساخته می‌شود." variant="list"/>
        @endif
    </div>
@else
    <div wire:key="ai-report-list-rows" class="contents">
        @foreach($this->reports as $rowReport)
            @php($row = $presenter->listRow($rowReport, (int) auth()->id()))
            <button type="button" wire:key="ai-report-list-{{ $row['id'] }}" wire:click="open({{ $row['id'] }})"
                    x-on:click="window.innerWidth < 1024 && $el.closest('[dir=rtl]')?.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="group relative w-full text-start flex flex-col gap-1 p-3 rounded-xl border border-transparent transition-all hover:shadow-md hover:border-[var(--md-sys-color-outline-variant)] animate-slide-up-fade {{ $report !== null && $report->id === $row['id'] ? 'bg-[var(--md-sys-color-primary-container)]' : 'bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-high)]' }}">
                <span class="absolute right-0 top-1/2 -translate-y-1/2 h-8 w-[4px] rounded-xl bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-opacity"></span>
                <span class="flex items-center justify-between gap-2 min-w-0">
                    <span class="flex items-center gap-1.5 min-w-0">
                        <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-on-surface-variant)]">{{ $row['chip']['icon'] }}</span>
                        <span dir="auto" class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $row['title'] }}</span>
                    </span>
                    @if($row['is_mine'])<x-dashboard.ai-report.state-badge wire:key="ai-report-chip-state" class="shrink-0" :state="$row['state']"/>@endif
                </span>
                <span class="flex items-center gap-1.5 flex-wrap text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">
                    <span>{{ $row['chip']['label'] }}</span>
                    <span>· {{ $row['stamp'] }}</span>
                    @unless($row['is_mine'])<span wire:key="ai-report-chip-received">· دریافتی</span>@endunless
                    @if($row['is_new'])<span wire:key="ai-report-chip-new" class="font-bold text-[var(--md-sys-color-primary)]">· جدید</span>@endif
                    @if($row['is_fork'])<span wire:key="ai-report-fork-chip">· رونوشت خصوصی</span>@endif
                    @if($row['is_mine'] && $row['shared_count'] > 0)<span wire:key="ai-report-chip-shared">· {{ convertToPersian($row['shared_count']) }} گیرنده</span>@endif
                </span>
            </button>
        @endforeach
    </div>

    @if($showLoadMore)
        <x-ui.buttons.load-more wire:key="ai-report-list-load-more" action="loadMoreReports" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                                class="mx-auto my-1 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
    @endif
@endif
</div>
