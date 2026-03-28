<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col relative z-10 pb-24"
     dir="rtl"
     x-data="reservations"
     style="--tw-bg-opacity: 1;"
>
    <!-- Background Pattern -->
    <div class="absolute inset-0 pointer-events-none opacity-[0.03] z-0 overflow-hidden"
         style="background-image: radial-gradient(color-mix(in srgb, var(--md-sys-color-primary) 80%, transparent) 1px, transparent 1px); background-size: 24px 24px;"></div>

    <div class="relative z-10 w-full">
        <!-- Section Header -->
        <div class="px-4 md:px-12 pt-4 animate-slide-up-fade" style="animation-delay: 0.05s;">
            <x-dashboard.tab.title icon="event_available" title="سامانه رزرواسیون هوشمند" :count="count($this->resources)" countLabel="مورد" />
        </div>

        <!-- M3 Segmented Tab Selector -->
        <div class="px-4 md:px-12 mb-8 animate-slide-up-fade" style="animation-delay: 0.1s;">
            <div class="inline-flex bg-[var(--md-sys-color-surface-container-high)] p-1.5 rounded-[2rem] border border-[var(--md-sys-color-outline-variant)] shadow-sm overflow-x-auto max-w-full no-scrollbar" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);">
                @foreach([
                    'seat' => ['icon' => 'desk', 'label' => 'میز کار'],
                    'spot' => ['icon' => 'local_parking', 'label' => 'پارکینگ'],
                    'car' => ['icon' => 'directions_car', 'label' => 'خودرو'],
                    'appointment' => ['icon' => 'meeting_room', 'label' => 'ملاقات']
                ] as $key => $data)
                    <button
                        wire:click="setType('{{ $key }}')"
                        class="relative flex items-center gap-2.5 px-6 py-3 rounded-full text-sm font-bold transition-all duration-300 whitespace-nowrap outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] {{ $type === $key ? 'text-[var(--md-sys-color-on-primary-container)] bg-[var(--md-sys-color-primary-container)] shadow-sm' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' }}"
                    >
                        <span class="material-symbols-rounded text-[22px] {{ $type === $key ? 'font-fill text-[var(--md-sys-color-primary)]' : '' }}">{{ $data['icon'] }}</span>
                        <span>{{ $data['label'] }}</span>
                        @if($type === $key)
                            <div class="absolute inset-0 rounded-full border border-[var(--md-sys-color-primary)] pointer-events-none" style="opacity: 0.15;"></div>
                            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Horizontal Scroll Date Selector -->
        <div class="px-4 md:px-12 mb-8 animate-slide-up-fade group/scrollable" style="animation-delay: 0.15s;">
            <div class="flex items-center justify-between mb-3">
                <label class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-primary)] flex items-center gap-2">
                    <span class="material-symbols-rounded text-[16px] font-fill">calendar_month</span>
                    تاریخ رزرو
                </label>
            </div>

            <div class="relative w-full">
                <!-- Navigation Buttons (Dates) -->
                <button @click="scrollRight('dateContainer')" class="absolute right-0 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-[var(--md-sys-color-surface-container-highest)]/90 backdrop-blur text-[var(--md-sys-color-on-surface)] shadow-md flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/scrollable:opacity-100 focus:opacity-100 translate-x-3 -mr-3 hidden md:flex" style="border: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent);">
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>
                <button @click="scrollLeft('dateContainer')" class="absolute left-0 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-[var(--md-sys-color-surface-container-highest)]/90 backdrop-blur text-[var(--md-sys-color-on-surface)] shadow-md flex items-center justify-center hover:bg-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary)] transition-all opacity-0 group-hover/scrollable:opacity-100 focus:opacity-100 -translate-x-3 -ml-3 hidden md:flex" style="border: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent);">
                    <span class="material-symbols-rounded">chevron_left</span>
                </button>

                <!-- Left/Right Fade Masks -->
                <div class="absolute right-0 inset-y-0 w-8 bg-gradient-to-l from-[var(--md-sys-color-surface)] to-transparent z-10 pointer-events-none"></div>
                <div class="absolute left-0 inset-y-0 w-8 bg-gradient-to-r from-[var(--md-sys-color-surface)] to-transparent z-10 pointer-events-none"></div>

                <div x-ref="dateContainer" class="flex gap-3 overflow-x-auto pb-4 pt-1 px-1 snap-x scrollbar-hide no-scrollbar relative z-0" dir="rtl">
                    @foreach($this->availableDates as $dt)
                        <button
                            wire:click="setDate('{{ $dt['value'] }}')"
                            class="relative shrink-0 w-[85px] h-[105px] flex flex-col items-center justify-center gap-1 rounded-[1.5rem] border transition-all duration-300 snap-center outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--md-sys-color-primary)]
                            {{ $date === $dt['value']
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] scale-105 z-10'
                                : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] shadow-sm' }}"
                            style="{{ $date !== $dt['value'] ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
                        >
                            <span class="text-[11px] font-medium opacity-80">{{ $dt['day'] }}</span>
                            <span class="text-3xl font-black tracking-tighter">{{ App\Helpers\PersianNumber::convert($dt['date']) ?? $dt['date'] }}</span>
                            <span class="text-xs font-bold">{{ $dt['month'] }}</span>

                            @if($dt['isToday'])
                                <div class="absolute top-2 right-2 w-2 h-2 rounded-full {{ $date === $dt['value'] ? 'bg-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-primary)]' }} animate-pulse shadow-sm"></div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Dynamic Time & Filters Row -->
        <div class="px-4 md:px-12 mb-8 animate-slide-up-fade flex flex-col gap-8" style="animation-delay: 0.2s;">

            @if($type === 'appointment')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Start Time -->
                    <div class="group/scrollable">
                        <label class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-secondary)] flex items-center gap-2 mb-3">
                            <span class="material-symbols-rounded text-[16px] font-fill">schedule</span>
                            زمان شروع (ملاقات)
                        </label>
                        <div class="relative w-full">
                            <!-- Start Time Nav -->
                            <button @click="scrollRight('startContainer')" class="absolute right-0 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-highest)]/90 backdrop-blur text-[var(--md-sys-color-on-surface)] shadow-md flex items-center justify-center hover:bg-[var(--md-sys-color-secondary)] hover:text-[var(--md-sys-color-on-secondary)] transition-all opacity-0 group-hover/scrollable:opacity-100 focus:opacity-100 translate-x-2 -mr-2 hidden md:flex border border-transparent">
                                <span class="material-symbols-rounded text-[18px]">chevron_right</span>
                            </button>
                            <button @click="scrollLeft('startContainer')" class="absolute left-0 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-highest)]/90 backdrop-blur text-[var(--md-sys-color-on-surface)] shadow-md flex items-center justify-center hover:bg-[var(--md-sys-color-secondary)] hover:text-[var(--md-sys-color-on-secondary)] transition-all opacity-0 group-hover/scrollable:opacity-100 focus:opacity-100 -translate-x-2 -ml-2 hidden md:flex border border-transparent">
                                <span class="material-symbols-rounded text-[18px]">chevron_left</span>
                            </button>

                            <div class="absolute right-0 inset-y-0 w-6 bg-gradient-to-l from-[var(--md-sys-color-surface)] to-transparent z-10 pointer-events-none"></div>
                            <div class="absolute left-0 inset-y-0 w-6 bg-gradient-to-r from-[var(--md-sys-color-surface)] to-transparent z-10 pointer-events-none"></div>

                            <div x-ref="startContainer" class="flex gap-2 overflow-x-auto pb-3 pt-1 px-1 snap-x scrollbar-hide no-scrollbar relative z-0" dir="rtl">
                                @foreach($this->availableTimeSlots as $time)
                                    <button
                                        wire:click="setStartTime('{{ $time }}')"
                                        class="shrink-0 px-5 py-2.5 rounded-[1.25rem] text-sm font-bold font-mono transition-all duration-300 snap-center border outline-none
                                        {{ $startTime === $time
                                            ? 'bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] border-transparent shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-secondary)_40%,transparent)] scale-105'
                                            : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:-translate-y-0.5' }}"
                                        style="{{ $startTime !== $time ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
                                    >
                                        {{ App\Helpers\PersianNumber::convert($time) ?? $time }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- End Time -->
                    <div class="group/scrollable">
                        <label class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-tertiary)] flex items-center gap-2 mb-3">
                            <span class="material-symbols-rounded text-[16px] font-fill">hourglass_bottom</span>
                            زمان پایان (ملاقات)
                        </label>
                        <div class="relative w-full">
                            <!-- End Time Nav -->
                            <button @click="scrollRight('endContainer')" class="absolute right-0 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-highest)]/90 backdrop-blur text-[var(--md-sys-color-on-surface)] shadow-md flex items-center justify-center hover:bg-[var(--md-sys-color-tertiary)] hover:text-[var(--md-sys-color-on-tertiary)] transition-all opacity-0 group-hover/scrollable:opacity-100 focus:opacity-100 translate-x-2 -mr-2 hidden md:flex border border-transparent">
                                <span class="material-symbols-rounded text-[18px]">chevron_right</span>
                            </button>
                            <button @click="scrollLeft('endContainer')" class="absolute left-0 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-highest)]/90 backdrop-blur text-[var(--md-sys-color-on-surface)] shadow-md flex items-center justify-center hover:bg-[var(--md-sys-color-tertiary)] hover:text-[var(--md-sys-color-on-tertiary)] transition-all opacity-0 group-hover/scrollable:opacity-100 focus:opacity-100 -translate-x-2 -ml-2 hidden md:flex border border-transparent">
                                <span class="material-symbols-rounded text-[18px]">chevron_left</span>
                            </button>

                            <div class="absolute right-0 inset-y-0 w-6 bg-gradient-to-l from-[var(--md-sys-color-surface)] to-transparent z-10 pointer-events-none"></div>
                            <div class="absolute left-0 inset-y-0 w-6 bg-gradient-to-r from-[var(--md-sys-color-surface)] to-transparent z-10 pointer-events-none"></div>

                            <div x-ref="endContainer" class="flex gap-2 overflow-x-auto pb-3 pt-1 px-1 snap-x scrollbar-hide no-scrollbar relative z-0" dir="rtl">
                                @foreach($this->availableTimeSlots as $time)
                                    <button
                                        wire:click="setEndTime('{{ $time }}')"
                                        class="shrink-0 px-5 py-2.5 rounded-[1.25rem] text-sm font-bold font-mono transition-all duration-300 snap-center border outline-none
                                        {{ $endTime === $time
                                            ? 'bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] border-transparent shadow-[0_4px_12px_color-mix(in_srgb,var(--md-sys-color-tertiary)_40%,transparent)] scale-105'
                                            : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:-translate-y-0.5' }}"
                                        style="{{ $endTime !== $time ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
                                    >
                                        {{ App\Helpers\PersianNumber::convert($time) ?? $time }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($type === 'seat' || $type === 'spot')
                <div>
                    <label class="text-[11px] uppercase tracking-widest font-bold text-[var(--md-sys-color-secondary)] flex items-center gap-2 mb-3">
                        <span class="material-symbols-rounded text-[16px] font-fill">filter_alt</span>
                        فیلتر طبقه (اختیاری)
                    </label>
                    <div class="flex gap-2 flex-wrap">
                        @foreach($this->availableFloors as $floor)
                            <button
                                wire:click="setFloor('{{ $floor['value'] }}')"
                                class="px-5 py-2.5 rounded-[1.25rem] text-sm font-bold transition-all duration-300 border outline-none flex items-center gap-2
                                {{ $filterFloor === $floor['value']
                                    ? 'bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] border-transparent shadow-sm'
                                    : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' }}"
                                style="{{ $filterFloor !== $floor['value'] ? 'border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);' : '' }}"
                            >
                                @if($filterFloor === $floor['value'])
                                    <span class="material-symbols-rounded text-[18px]">check_circle</span>
                                @else
                                    <span class="material-symbols-rounded text-[18px] opacity-60">layers</span>
                                @endif
                                {{ $floor['label'] }}
                            </button>
                        @endforeach

                        @if($filterFloor !== null)
                            <button wire:click="resetFilters" class="px-4 py-2.5 rounded-[1.25rem] text-sm font-bold text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] transition-colors flex items-center gap-1.5 ml-2 border border-transparent">
                                <span class="material-symbols-rounded text-[16px]">cancel</span>
                                حذف فیلتر
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Divider -->
        <div class="px-4 md:px-12 mb-8">
            <div class="w-full h-px bg-[var(--md-sys-color-outline-variant)]" style="opacity: 0.2;"></div>
        </div>

        <!-- Main Grid Layout -->
        <div class="px-4 md:px-12 grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12">

            <!-- Resources Grid (Left/Main side in RTL) -->
            <div class="lg:col-span-8 xl:col-span-9 space-y-6 animate-slide-up-fade" style="animation-delay: 0.25s;">

                @if($this->resources->isEmpty())
                    <!-- Enterprise Empty State -->
                    <div class="bg-[var(--md-sys-color-surface-container-high)] rounded-[2rem] p-10 md:p-16 border border-[var(--md-sys-color-outline-variant)] relative overflow-hidden flex flex-col items-center text-center shadow-sm" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 30%, transparent);">
                        <span class="material-symbols-rounded absolute -left-10 -bottom-10 text-[220px] text-[var(--md-sys-color-primary)] pointer-events-none" style="opacity: 0.03;">event_busy</span>

                        <div class="w-20 h-20 mb-6 rounded-[1.5rem] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-inner relative">
                            <span class="absolute inset-0 rounded-[1.5rem] border border-[var(--md-sys-color-primary)]" style="opacity: 0.2;"></span>
                            <span class="material-symbols-rounded text-4xl font-fill">search_off</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-black text-[var(--md-sys-color-on-surface)] mb-3 tracking-tight">هیچ موردی یافت نشد</h3>
                        <p class="text-[13px] text-[var(--md-sys-color-on-surface-variant)] max-w-sm mx-auto leading-[2] text-justify font-medium">
                            برای تاریخ و فیلترهای انتخاب شده، هیچ موردی جهت رزرو وجود ندارد. لطفاً تاریخ دیگری را امتحان کنید یا فیلترها را تغییر دهید.
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6">
                        @foreach($this->resources as $resource)
                            <!-- Enhanced Card Anatomy Standard -->
                            <div class="group bg-[var(--md-sys-color-surface)] rounded-[2rem] border border-[var(--md-sys-color-outline-variant)] shadow-[var(--md-sys-elevation-1)] hover:shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] hover:-translate-y-1 transition-all duration-500 flex flex-col overflow-hidden relative" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 50%, transparent);">

                                <div class="w-full aspect-[16/9] relative bg-[var(--md-sys-color-surface-container-highest)] overflow-hidden rounded-t-[2rem]">
                                    @if($resource->image)
                                        <img src="{{ $resource->image }}" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" alt="{{ $resource->name }}">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-[var(--md-sys-color-surface-variant)] transition-transform duration-1000 group-hover:scale-105" style="background-image: radial-gradient(color-mix(in srgb, var(--md-sys-color-on-surface) 10%, transparent) 1px, transparent 1px); background-size: 16px 16px;">
                                            <span class="material-symbols-rounded text-[80px] text-[var(--md-sys-color-on-surface-variant)]" style="opacity: 0.15;">camera</span>
                                        </div>
                                    @endif

                                    <div class="absolute inset-0 bg-gradient-to-t from-[var(--md-sys-color-surface)] to-transparent" style="--tw-gradient-from: color-mix(in srgb, var(--md-sys-color-surface) 90%, transparent);"></div>

                                    <div class="absolute bottom-4 right-5 left-5 flex justify-between items-end">
                                        <h4 class="font-black text-[var(--md-sys-color-on-surface)] text-xl drop-shadow-sm z-10">{{ $resource->name }}</h4>
                                        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm z-10" style="backdrop-filter: blur(8px);">
                                            <span class="material-symbols-rounded text-[18px] font-fill">{{ $resource->type === 'seat' ? 'desk' : ($resource->type === 'spot' ? 'local_parking' : ($resource->type === 'car' ? 'directions_car' : 'meeting_room')) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 flex-1 flex flex-col relative bg-[var(--md-sys-color-surface)]">
                                    <div class="grid grid-cols-2 gap-3 mb-6">
                                        @if(isset($resource->metadata['floor']))
                                            <div class="flex items-center gap-2 text-xs text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container)] px-3 py-2 rounded-[1rem]">
                                                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">layers</span>
                                                <span class="font-bold">طبقه {{ App\Helpers\PersianNumber::convert($resource->metadata['floor']) ?? $resource->metadata['floor'] }}</span>
                                            </div>
                                        @endif
                                        @if(isset($resource->metadata['extension']))
                                            <div class="flex items-center gap-2 text-xs text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-container)] px-3 py-2 rounded-[1rem]">
                                                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">call</span>
                                                <span class="font-bold font-mono" dir="ltr">{{ App\Helpers\PersianNumber::convert($resource->metadata['extension']) ?? $resource->metadata['extension'] }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-auto">
                                        <button
                                            wire:click="book({{ $resource->id }})"
                                            class="w-full h-12 flex items-center justify-center gap-2 rounded-[1.25rem] bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold text-sm shadow-md hover:shadow-lg hover:brightness-110 active:scale-[0.97] transition-all overflow-hidden relative"
                                        >
                                            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></span>
                                            <span class="material-symbols-rounded text-[20px] font-fill">add_circle</span>
                                            @if($type === 'appointment')
                                                ثبت زمان ({{ App\Helpers\PersianNumber::convert($startTime) }} - {{ App\Helpers\PersianNumber::convert($endTime) }})
                                            @else
                                                ثبت رزرو کامل
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- User Reservations Sidebar -->
            <div class="lg:col-span-4 xl:col-span-3 space-y-6 animate-slide-up-fade" style="animation-delay: 0.3s;">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                        <span class="material-symbols-rounded text-[20px] font-fill">history</span>
                    </div>
                    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">تاریخچه من</h2>
                    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]" style="opacity: 0.5;"></div>
                    <span class="text-[11px] font-bold px-3 py-1.5 rounded-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                        {{ App\Helpers\PersianNumber::convert(count($this->userReservations)) }} مورد
                    </span>
                </div>

                <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-[2rem] border border-[var(--md-sys-color-outline-variant)] p-2 flex flex-col gap-2 shadow-sm" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 40%, transparent);">
                    @forelse($this->userReservations as $reservation)
                        <div class="group relative flex items-center justify-between p-4 rounded-[1.5rem] bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] border border-transparent hover:border-[var(--md-sys-color-outline-variant)] transition-all shadow-[var(--md-sys-elevation-1)] hover:shadow-md" style="border-color: color-mix(in srgb, var(--md-sys-color-outline-variant) 20%, transparent);">
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 h-8 w-[4px] rounded-l-full bg-[var(--md-sys-color-tertiary)] opacity-0 group-hover:opacity-100 transition-opacity shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-tertiary)_50%,transparent)]"></div>

                            <div class="flex items-center gap-4 pr-2">
                                <div class="w-12 h-12 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center shrink-0 shadow-inner">
                                    <span class="material-symbols-rounded text-[24px] font-fill">
                                        {{ $reservation->resource->type === 'seat' ? 'desk' : ($reservation->resource->type === 'spot' ? 'local_parking' : ($reservation->resource->type === 'car' ? 'directions_car' : 'meeting_room')) }}
                                    </span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-black text-[var(--md-sys-color-on-surface)] tracking-tight">{{ $reservation->resource->name }}</span>
                                    <span class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1">
                                        <span class="material-symbols-rounded text-[14px]">event</span>
                                        @if($reservation->is_full_day)
                                            {{ App\Helpers\PersianNumber::convert(\Morilog\Jalali\Jalalian::fromCarbon($reservation->start_time)->format('Y/m/d')) }} (کامل)
                                        @else
                                            {{ App\Helpers\PersianNumber::convert(\Morilog\Jalali\Jalalian::fromCarbon($reservation->start_time)->format('m/d H:i')) }} تا {{ App\Helpers\PersianNumber::convert($reservation->end_time->format('H:i')) }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <button
                                wire:click="cancel({{ $reservation->id }})"
                                wire:confirm="آیا از لغو این رزرو اطمینان دارید؟"
                                class="w-10 h-10 rounded-[1.25rem] flex items-center justify-center text-[var(--md-sys-color-error)] bg-[var(--md-sys-color-error-container)]/0 hover:bg-[var(--md-sys-color-error-container)] opacity-0 group-hover:opacity-100 transition-all focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-error)]"
                                title="لغو رزرو"
                            >
                                <span class="material-symbols-rounded text-[20px] font-fill">delete</span>
                            </button>
                        </div>
                    @empty
                        <div class="p-10 text-center flex flex-col items-center">
                            <div class="w-16 h-16 rounded-[1.5rem] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] flex items-center justify-center mb-3" style="opacity: 0.5;">
                                <span class="material-symbols-rounded text-3xl">event_available</span>
                            </div>
                            <h4 class="font-bold text-sm text-[var(--md-sys-color-on-surface)] mb-1">صندوق خالی است</h4>
                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">شما هیچ رزرو فعالی ندارید.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
