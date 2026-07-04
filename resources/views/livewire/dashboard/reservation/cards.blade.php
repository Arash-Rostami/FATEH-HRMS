@if($this->resources->isEmpty())
    <x-ui.empty icon="search_off" title="هیچ موردی یافت نشد" description="برای تاریخ و فیلترهای انتخاب شده، هیچ موردی جهت رزرو وجود ندارد. لطفاً تاریخ دیگری را امتحان کنید یا فیلترها را تغییر دهید." variant="list" watermark="event_busy" />
@else
    <div x-data="{ zoomImageUrl: null }"
         class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6 w-full">

        @foreach($this->resources as $resource)
            @include('livewire.dashboard.reservation.image', ['cardIndex' => $loop->index])
        @endforeach

        <x-ui.modals.base
            @click="zoomImageUrl = null"
            wire:model="zoomImageUrl" :title="null"
            contentClass="!p-0 !w-screen !max-w-none !bg-transparent !border-none !shadow-none md:!w-auto md:!max-w-7xl"
        >
            <div class="relative flex flex-col items-center gap-6 w-full px-4 md:px-0">

                <img :src="$wire.zoomImageUrl"
                     class="w-full h-auto max-h-[85vh] object-contain rounded-lg md:rounded-2xl shadow-xl">

                <div @click="$wire.zoomImageUrl = null"
                     class="px-6 md:px-8 py-3 bg-white/5 border border-white/10 rounded-xl text-white/80 text-sm font-bold flex items-center gap-2 animate-slide-up text-center max-w-full">
                    <span class="material-symbols-rounded text-lg">info</span>
                    برای بازگشت به صفحه کلیک کنید
                </div>

            </div>
        </x-ui.modals.base>
    </div>

    @if($this->totalResources > count($this->resources))
        <div class="mt-8 flex justify-center relative z-1">
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
