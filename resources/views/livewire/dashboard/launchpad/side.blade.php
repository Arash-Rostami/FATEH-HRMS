<div class="flex items-center gap-3 mb-2">
    <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shrink-0">
        <span class="material-symbols-rounded text-[20px] font-fill">apps</span>
    </div>
    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)] whitespace-nowrap">فهرست برنامه‌ها</h2>
    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)] min-w-[20px] opacity-50"></div>
    <span wire:key="launchpad-side-total"
          class="text-[11px] font-bold px-2.5 py-1 rounded-lg tabular-nums bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">{{ convertToPersian((string) $this->counts[$listStatus]) }}</span>
</div>

<div wire:key="launchpad-side-tabs" class="w-fit max-w-full mx-auto md:mx-0 z-1 mb-2">
    <x-ui.buttons.tab-selector
        :tabs="[
            ['id' => 'active', 'label' => 'فعال', 'icon' => 'visibility', 'unread' => $this->counts['active']],
            ['id' => 'inactive', 'label' => 'غیرفعال', 'icon' => 'visibility_off', 'unread' => $this->counts['inactive']],
        ]"
        :activeTab="$listStatus"
        class="!mb-0"
        buttonBaseClass="relative px-3 py-2 sm:px-6 sm:py-2.5 lg:px-2 lg:py-2 xl:px-3 rounded-xl text-xs sm:text-sm lg:text-xs font-bold transition-all duration-300 z-10 flex items-center gap-1.5 sm:gap-2 lg:gap-1 shrink-0 whitespace-nowrap"
        iconBaseClass="material-symbols-rounded text-base sm:text-lg lg:text-base"
    />
</div>

<div class="bg-[var(--md-sys-color-surface)] rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] p-2 flex flex-col gap-2 shadow-sm overflow-y-auto max-h-[calc(100vh-15rem)]">
@if($this->rows->isEmpty() && $listStatus === 'inactive')
    <div wire:key="launchpad-side-empty-inactive" class="contents"><x-ui.empty icon="visibility_off" title="برنامه‌ای پیدا نشد" variant="list"/></div>
@elseif($this->rows->isEmpty())
    <div wire:key="launchpad-side-empty-active" class="contents"><x-ui.empty icon="folder_open" title="برنامه‌ای پیدا نشد" variant="list"/></div>
@else
    <div wire:key="launchpad-side-rows" class="contents">
        @foreach($this->rows->take($listLimit) as $item)
            @php($row = $presenter->listRow($item, auth()->user()))
            <div wire:key="launchpad-side-{{ $row['id'] }}" class="flex items-center gap-1">
                @if($item->kind === 'link' && $row['block'] === null)
                    <x-ui.buttons.form wire:key="launchpad-side-link" variant="tonal" icon="open_in_new" class="min-w-0 flex-1 justify-start" href="{{ route('launchpad.go', $row['id']) }}" target="_blank" rel="noopener"><span dir="auto" class="truncate">{{ $row['title'] }}</span></x-ui.buttons.form>
                @elseif($item->kind === 'link')
                    <x-ui.buttons.form wire:key="launchpad-side-link-off" variant="tonal" icon="open_in_new" class="min-w-0 flex-1 justify-start" title="برنامه در حال حاضر قابل باز شدن نیست" disabled><span dir="auto" class="truncate">{{ $row['title'] }}</span></x-ui.buttons.form>
                @else
                    <button type="button" wire:key="launchpad-side-open" wire:click="open({{ $row['id'] }})"
                            x-on:click="window.innerWidth < 1024 && $el.closest('[dir=rtl]')?.scrollTo({ top: 0, behavior: 'smooth' })"
                            class="group relative min-w-0 flex-1 text-start flex flex-col gap-1 p-3 rounded-xl border border-transparent transition-all hover:shadow-md hover:border-[var(--md-sys-color-outline-variant)] animate-slide-up-fade {{ $openId === $row['id'] ? 'bg-[var(--md-sys-color-primary-container)]' : 'bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-high)]' }}">
                        <span class="absolute right-0 top-1/2 -translate-y-1/2 h-8 w-[4px] rounded-xl bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-opacity"></span>
                        <span dir="auto" class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $row['title'] }}</span>
                        <span class="text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">{{ $row['status'] }}</span>
                    </button>
                @endif
                <span wire:key="launchpad-side-details"><x-ui.buttons.form variant="tonal" size="icon" icon="info" title="جزئیات برنامه" loading="showDetails" wire:click="showDetails({{ $row['id'] }})"/></span>
                @if($row['can_manage'] && $row['live'])
                    <span wire:key="launchpad-side-deactivate"><x-ui.buttons.form variant="tonal" size="icon" icon="visibility_off" title="غیرفعال‌سازی" loading="deactivate" x-on:click="{{ confirmDialog('deactivate', 'غیرفعال‌سازی برنامه', 'برنامه برای دیگران بسته می‌شود؛ هر زمان می‌توانید دوباره آن را فعال کنید.', $row['id'], 'dispatch', 'danger') }}"/></span>
                @elseif($row['can_manage'])
                    <span wire:key="launchpad-side-activate"><x-ui.buttons.form variant="tonal" size="icon" icon="visibility" title="فعال‌سازی از امروز" loading="activate" wire:click="activate({{ $row['id'] }})"/></span>
                @endif
            </div>
        @endforeach
    </div>

    @if($this->rows->count() > $listLimit)
        <div wire:key="launchpad-side-load-more" class="contents"><x-ui.buttons.load-more action="loadMore" text="نمایش بیشتر" loading-text="در حال بارگذاری…" class="mx-auto my-1 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/></div>
    @endif
@endif
</div>
