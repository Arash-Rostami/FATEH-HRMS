<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col"
     dir="rtl"
     x-data="reservations">

    <!-- Section Header (reusing global title component) -->
    <div class="px-4 md:px-12 pt-4 animate-slide-up-fade" style="animation-delay: 0.05s;">
        <x-dashboard.tab.title icon="event_available" title="سامانه رزرواسیون" :count="count($this->resources)" countLabel="مورد" />
    </div>

    <!-- Tab Selector (Segmented Control Style) -->
    <div class="px-4 md:px-12 mb-6 animate-slide-up-fade" style="animation-delay: 0.1s;">
        <div class="inline-flex bg-[var(--md-sys-color-surface-container-high)] p-1.5 rounded-2xl border border-[var(--md-sys-color-outline-variant)] shadow-sm overflow-x-auto max-w-full">
            @foreach([
                'seat' => ['icon' => 'desk', 'label' => 'میز کار'],
                'spot' => ['icon' => 'local_parking', 'label' => 'پارکینگ'],
                'car' => ['icon' => 'directions_car', 'label' => 'خودرو'],
                'appointment' => ['icon' => 'meeting_room', 'label' => 'ملاقات']
            ] as $key => $data)
                <button
                    wire:click="setType('{{ $key }}')"
                    class="relative flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 {{ $type === $key ? 'text-[var(--md-sys-color-on-primary-container)] bg-[var(--md-sys-color-primary-container)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' }}"
                >
                    <span class="material-symbols-rounded text-[20px] {{ $type === $key ? 'font-fill' : '' }}">{{ $data['icon'] }}</span>
                    <span>{{ $data['label'] }}</span>
                    @if($type === $key)
                        <div class="absolute inset-0 rounded-xl border border-[var(--md-sys-color-primary)] pointer-events-none" style="opacity: 0.2;"></div>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="px-4 md:px-12 mb-8 animate-slide-up-fade" style="animation-delay: 0.15s;">
        <div class="bg-[var(--md-sys-color-surface)] p-5 rounded-2xl border border-[var(--md-sys-color-outline-variant)] shadow-sm flex flex-wrap items-end gap-4">

            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1.5">تاریخ رزرو</label>
                <input type="date" wire:model.live="date" class="w-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border-none rounded-xl h-11 px-4 focus:ring-2 focus:ring-[var(--md-sys-color-primary)] transition-shadow" style="--tw-ring-color: color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent);">
            </div>

            @if($type === 'appointment')
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1.5">از ساعت</label>
                    <input type="time" wire:model.live="startTime" class="w-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border-none rounded-xl h-11 px-4 focus:ring-2 focus:ring-[var(--md-sys-color-primary)] transition-shadow" style="--tw-ring-color: color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent);">
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1.5">تا ساعت</label>
                    <input type="time" wire:model.live="endTime" class="w-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border-none rounded-xl h-11 px-4 focus:ring-2 focus:ring-[var(--md-sys-color-primary)] transition-shadow" style="--tw-ring-color: color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent);">
                </div>
            @endif

            @if($type === 'seat' || $type === 'spot')
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)] mb-1.5">فیلتر طبقه</label>
                    <select wire:model.live="filterFloor" class="w-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border-none rounded-xl h-11 px-4 focus:ring-2 focus:ring-[var(--md-sys-color-primary)] transition-shadow" style="--tw-ring-color: color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent);">
                        <option value="">همه طبقات</option>
                        <option value="1">طبقه ۱</option>
                        <option value="2">طبقه ۲</option>
                        <option value="3">طبقه ۳</option>
                    </select>
                </div>
            @endif

            <button wire:click="resetFilters" class="h-11 px-5 rounded-xl text-sm font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-colors flex items-center gap-2">
                <span class="material-symbols-rounded text-[18px]">filter_alt_off</span>
                پاک کردن
            </button>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="px-4 md:px-12 grid grid-cols-1 lg:grid-cols-12 gap-5 md:gap-6 pb-12">

        <!-- Resources Grid (Left/Main side in RTL) -->
        <div class="lg:col-span-8 xl:col-span-9 space-y-5 animate-slide-up-fade" style="animation-delay: 0.2s;">

            <!-- Section Header Pattern -->
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
                    <span class="material-symbols-rounded text-base font-fill">grid_view</span>
                </div>
                <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">موارد قابل رزرو</h2>
                <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]" style="opacity: 0.5;"></div>
            </div>

            @if($this->resources->isEmpty())
                <!-- Enterprise Empty State (Tertiary Banner Pattern) -->
                <div class="bg-[var(--md-sys-color-tertiary-container)] rounded-3xl p-8 md:p-12 border border-[var(--md-sys-color-outline-variant)] relative overflow-hidden flex flex-col items-center text-center" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent);">
                    <!-- Ghost watermark icon -->
                    <span class="material-symbols-rounded absolute -right-10 -bottom-10 text-[180px] text-[var(--md-sys-color-on-tertiary-container)] pointer-events-none" style="opacity: 0.04;">event_busy</span>

                    <div class="w-16 h-16 mb-4 rounded-2xl bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-tertiary)] flex items-center justify-center" style="background-color: color-mix(in srgb, var(--md-sys-color-tertiary) 10%, transparent);">
                        <span class="material-symbols-rounded text-3xl font-fill">search_off</span>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-[var(--md-sys-color-on-tertiary-container)] mb-2">هیچ موردی یافت نشد</h3>
                    <p class="text-sm text-[var(--md-sys-color-on-tertiary-container)] max-w-md mx-auto leading-relaxed" style="opacity: 0.8;">
                        برای تاریخ و فیلترهای انتخاب شده، هیچ موردی جهت رزرو وجود ندارد. لطفاً تاریخ دیگری را امتحان کنید یا فیلترها را تغییر دهید.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5">
                    @foreach($this->resources as $resource)
                        <!-- Card Anatomy Standard -->
                        <div class="group bg-[var(--md-sys-color-surface)] rounded-2xl border border-[var(--md-sys-color-outline-variant)] shadow-[var(--md-sys-elevation-1)] hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] transition-all duration-300 flex flex-col overflow-hidden relative" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 60%, transparent);">
                            <!-- Active Top Stripe -->
                            <div class="absolute top-0 left-0 right-0 h-[3px] bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10"></div>

                            <!-- Resource Image Header (16:9 aspect ratio placeholder) -->
                            <div class="w-full aspect-[16/9] relative bg-[var(--md-sys-color-surface-container-highest)] overflow-hidden">
                                @if($resource->image)
                                    <img src="{{ $resource->image }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="{{ $resource->name }}">
                                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] to-transparent" style="--tw-gradient-from: color-mix(in srgb, var(--md-sys-color-surface) 60%, transparent);"></div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center transition-transform duration-700 group-hover:scale-110" style="opacity: 0.2;">
                                        <span class="material-symbols-rounded text-6xl">photo_camera</span>
                                    </div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] to-transparent" style="--tw-gradient-from: color-mix(in srgb, var(--md-sys-color-surface) 80%, transparent);"></div>
                                @endif

                                <div class="absolute bottom-3 right-4 left-4 flex justify-between items-end">
                                    <h4 class="font-bold text-[var(--md-sys-color-on-surface)] text-base drop-shadow-md z-10">{{ $resource->name }}</h4>
                                    <span class="bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-[10px] uppercase font-bold px-2 py-1 rounded-lg shadow-sm z-10" style="background-color: color-mix(in srgb, var(--md-sys-color-primary-container) 90%, transparent); backdrop-filter: blur(4px);">
                                        {{ $resource->type }}
                                    </span>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-5 flex-1 flex flex-col">
                                <div class="grid grid-cols-2 gap-2 mb-4">
                                    @if(isset($resource->metadata['floor']))
                                        <div class="flex items-center gap-2 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                                            <span class="material-symbols-rounded text-[14px]">layers</span>
                                            <span>طبقه: <strong class="text-[var(--md-sys-color-on-surface)]">{{ $resource->metadata['floor'] }}</strong></span>
                                        </div>
                                    @endif
                                    @if(isset($resource->metadata['extension']))
                                        <div class="flex items-center gap-2 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                                            <span class="material-symbols-rounded text-[14px]">call</span>
                                            <span>داخلی: <strong class="text-[var(--md-sys-color-on-surface)] font-mono" dir="ltr">{{ $resource->metadata['extension'] }}</strong></span>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-auto pt-4 border-t border-[var(--md-sys-color-outline-variant)]" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);">
                                    <button
                                        wire:click="book({{ $resource->id }})"
                                        class="w-full h-11 flex items-center justify-center gap-2 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold text-sm shadow-sm hover:brightness-95 active:scale-[0.98] transition-all"
                                    >
                                        <span class="material-symbols-rounded text-[18px]">add_task</span>
                                        @if($type === 'appointment')
                                            رزرو ({{ $startTime }} تا {{ $endTime }})
                                        @else
                                            رزرو تمام وقت
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- User Reservations Sidebar (Right/Secondary side in RTL) -->
        <div class="lg:col-span-4 xl:col-span-3 space-y-5 animate-slide-up-fade" style="animation-delay: 0.25s;">

            <!-- Section Header Pattern -->
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                    <span class="material-symbols-rounded text-base font-fill">history</span>
                </div>
                <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">رزروهای من</h2>
                <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]" style="opacity: 0.5;"></div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                    {{ count($this->userReservations) }} مورد
                </span>
            </div>

            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl border border-[var(--md-sys-color-outline-variant)] p-1 flex flex-col gap-1 shadow-[var(--md-sys-elevation-1)]" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 60%, transparent);">
                @forelse($this->userReservations as $reservation)
                    <div class="group relative flex items-center justify-between p-4 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)] transition-colors" style="--tw-bg-opacity: 0.5; background-color: color-mix(in srgb, var(--md-sys-color-surface-variant) 50%, transparent);">

                        <!-- Left Stripe Indicator -->
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 h-6 w-[3px] rounded-l-xl bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-opacity shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]"></div>

                        <div class="flex items-center gap-3 pr-2">
                            <div class="w-10 h-10 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0">
                                <span class="material-symbols-rounded text-[20px] font-fill">
                                    {{ $reservation->resource->type === 'seat' ? 'desk' : ($reservation->resource->type === 'spot' ? 'local_parking' : ($reservation->resource->type === 'car' ? 'directions_car' : 'meeting_room')) }}
                                </span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $reservation->resource->name }}</span>
                                <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">
                                    @if($reservation->is_full_day)
                                        {{ \App\Casts\JalaliTimestamp::toJalali($reservation->start_time) }} (تمام روز)
                                    @else
                                        {{ \App\Casts\JalaliTimestamp::toJalali($reservation->start_time) }} تا {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <button
                            wire:click="cancel({{ $reservation->id }})"
                            wire:confirm="آیا از لغو این رزرو اطمینان دارید؟"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] opacity-0 group-hover:opacity-100 transition-all focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)]"
                            title="لغو رزرو"
                        >
                            <span class="material-symbols-rounded text-[18px]">cancel</span>
                        </button>
                    </div>
                @empty
                    <div class="p-8 text-center flex flex-col items-center">
                        <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-on-surface-variant)] mb-2" style="opacity: 0.3;">event_busy</span>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">شما هیچ رزرو فعالی ندارید.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</div>
