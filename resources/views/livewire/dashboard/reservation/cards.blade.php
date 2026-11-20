@if($this->resources->isEmpty())
    <div wire:key="reservation-empty-resources" class="mt-6">
        <x-ui.empty icon="search_off" title="هیچ موردی یافت نشد" description="در این تاریخ و با این فیلترها، موردی برای رزرو پیدا نشد. تاریخ یا فیلترها را عوض کنید." variant="list" watermark="event_busy" />
    </div>
@else
    <div wire:key="reservation-resources-grid"
         class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6 w-full">

        @foreach($this->resources as $resource)
            @include('livewire.dashboard.reservation.image', ['cardIndex' => $loop->index])
        @endforeach
    </div>

    @if($this->totalResources > count($this->resources))
        <div wire:key="reservation-load-more" class="mt-8 flex justify-center relative z-1">
            <x-ui.buttons.load-more
                action="loadMoreResources"
                text="نمایش موارد بیشتر"
                loadingText="در حال بارگذاری..."
                icon="expand_more"
                class="px-6 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-sm font-semibold shadow-sm"
            />
        </div>
    @endif
@endif
