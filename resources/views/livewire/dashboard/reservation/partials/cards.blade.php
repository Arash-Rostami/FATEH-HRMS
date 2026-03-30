@if($this->resources->isEmpty())
    <div
        class="bg-[var(--md-sys-color-surface-container-high)] rounded-[2rem] p-8 md:p-16 border border-[var(--md-sys-color-outline-variant)] relative overflow-hidden flex flex-col items-center text-center shadow-sm"
        style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent);">

        <span
            class="material-symbols-rounded absolute -left-10 -bottom-10 text-[220px] text-[var(--md-sys-color-primary)] pointer-events-none"
            style="opacity: 0.03;">event_busy
        </span>

        <div
            class="w-20 h-20 mb-6 rounded-[1.5rem] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-inner relative">
            <span class="absolute inset-0 rounded-[1.5rem] border border-[var(--md-sys-color-primary)]"
                  style="opacity: 0.2;"></span>
            <span class="material-symbols-rounded text-4xl font-fill">search_off</span>
        </div>

        <h3 class="text-xl md:text-2xl font-black text-[var(--md-sys-color-on-surface)] mb-3 tracking-tight">
            هیچ موردی یافت نشد
        </h3>
        <p class="text-[13px] text-[var(--md-sys-color-on-surface-variant)] max-w-sm mx-auto leading-[2] text-justify font-medium">
            برای تاریخ و فیلترهای انتخاب شده، هیچ موردی جهت رزرو وجود ندارد. لطفاً تاریخ دیگری را
            امتحان کنید یا فیلترها را تغییر دهید.
        </p>
    </div>
@else
    <div x-data="{ zoomImageUrl: null }"
         class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6 w-full">

        @foreach($this->resources as $resource)
            @include('livewire.dashboard.reservation.partials.image')
        @endforeach

        <x-dashboard.modal.base
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
        </x-dashboard.modal.base>
    </div>
@endif
