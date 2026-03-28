<div class="space-y-6" x-data="reservations" dir="rtl">
    <!-- Header & Type Selection -->
    <div class="flex items-center justify-between bg-surface-100 p-4 rounded-xl shadow-sm md3-elevation-1">
        <h2 class="text-xl font-bold text-on-surface">سامانه رزرواسیون</h2>
        <div class="flex gap-2">
            @foreach(['seat' => 'میز کار', 'spot' => 'پارکینگ', 'car' => 'خودرو', 'appointment' => 'ملاقات'] as $key => $label)
                <button
                    wire:click="setType('{{ $key }}')"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 {{ $type === $key ? 'bg-primary text-on-primary shadow-md' : 'bg-surface-200 text-on-surface-variant hover:bg-surface-300' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-surface-50 p-4 rounded-lg border border-outline-variant flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-on-surface-variant mb-1">تاریخ</label>
            <input type="date" wire:model.live="date" class="w-full bg-surface-100 border-none rounded-md focus:ring-primary focus:ring-2">
        </div>

        @if($type === 'appointment')
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-on-surface-variant mb-1">زمان شروع</label>
                <input type="time" wire:model.live="startTime" class="w-full bg-surface-100 border-none rounded-md focus:ring-primary focus:ring-2">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-on-surface-variant mb-1">زمان پایان</label>
                <input type="time" wire:model.live="endTime" class="w-full bg-surface-100 border-none rounded-md focus:ring-primary focus:ring-2">
            </div>
        @endif

        @if($type === 'seat' || $type === 'spot')
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-on-surface-variant mb-1">طبقه (اختیاری)</label>
                <select wire:model.live="filterFloor" class="w-full bg-surface-100 border-none rounded-md focus:ring-primary focus:ring-2">
                    <option value="">همه</option>
                    <option value="1">طبقه ۱</option>
                    <option value="2">طبقه ۲</option>
                    <option value="3">طبقه ۳</option>
                </select>
            </div>
        @endif
        <div>
            <button wire:click="resetFilters" class="px-4 py-2 text-primary hover:bg-primary/10 rounded-md transition-colors">پاک کردن</button>
        </div>
    </div>

    <!-- Main Content Split -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Resources List -->
        <div class="lg:col-span-2 space-y-4">
            <h3 class="font-semibold text-lg text-on-surface">موارد قابل رزرو</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($this->resources as $resource)
                    <div class="bg-surface-100 rounded-xl p-4 shadow-sm border border-outline-variant flex flex-col justify-between hover:shadow-md transition-shadow">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-on-surface">{{ $resource->name }}</h4>
                                <span class="bg-primary/10 text-primary text-xs px-2 py-1 rounded-full">{{ $resource->type }}</span>
                            </div>
                            <div class="text-sm text-on-surface-variant space-y-1">
                                @if(isset($resource->metadata['floor']))
                                    <p>طبقه: {{ $resource->metadata['floor'] }}</p>
                                @endif
                                @if(isset($resource->metadata['extension']))
                                    <p>داخلی: {{ $resource->metadata['extension'] }}</p>
                                @endif
                                @if(isset($resource->metadata['card']))
                                    <p>کارت: {{ $resource->metadata['card'] }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-outline-variant/50 flex justify-end">
                            <button
                                wire:click="book({{ $resource->id }})"
                                class="bg-primary text-on-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors shadow-sm"
                            >
                                @if($type === 'appointment')
                                    رزرو ({{ $startTime }} تا {{ $endTime }})
                                @else
                                    رزرو تمام وقت
                                @endif
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-on-surface-variant bg-surface-50 rounded-xl border border-dashed border-outline-variant">
                        <p>موردی یافت نشد.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- User Reservations Sidebar -->
        <div class="space-y-4">
            <h3 class="font-semibold text-lg text-on-surface">رزروهای من</h3>
            <div class="bg-surface-100 rounded-xl p-4 shadow-sm space-y-4">
                @forelse($this->userReservations as $reservation)
                    <div class="bg-surface-50 border border-outline-variant rounded-lg p-3 flex justify-between items-center group hover:border-error/50 transition-colors">
                        <div>
                            <p class="font-medium text-sm text-on-surface">{{ $reservation->resource->name }}</p>
                            @if($reservation->is_full_day)
                                <p class="text-xs text-on-surface-variant mt-1">{{ \App\Casts\JalaliTimestamp::toJalali($reservation->start_time) }} (تمام وقت)</p>
                            @else
                                <p class="text-xs text-on-surface-variant mt-1">{{ \App\Casts\JalaliTimestamp::toJalali($reservation->start_time) }} تا {{ \Carbon\Carbon::parse($reservation->end_time)->format('H:i') }}</p>
                            @endif
                        </div>
                        <button
                            wire:click="cancel({{ $reservation->id }})"
                            wire:confirm="آیا از لغو این رزرو اطمینان دارید؟"
                            class="text-error hover:bg-error/10 p-2 rounded-full opacity-0 group-hover:opacity-100 transition-all focus:opacity-100"
                            title="لغو رزرو"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-on-surface-variant text-center py-4">شما هیچ رزرو فعالی ندارید.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
