<div
    x-show="$wire.isCreateModalOpen"
    x-transition.opacity.duration.300ms
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
    style="display: none;"
>
    <div
        x-show="$wire.isCreateModalOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-4"
        @click.away="$wire.set('isCreateModalOpen', false)"
        class="bg-[var(--md-sys-color-surface)] w-full max-w-lg rounded-[32px] shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-[var(--md-sys-color-outline-variant)]/50"
    >
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--md-sys-color-outline-variant)]/50 flex items-center justify-between bg-[var(--md-sys-color-surface-container)]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: {{ $eventColor }}20; color: {{ $eventColor }}">
                    <span class="material-symbols-rounded text-2xl">{{ $eventIcon }}</span>
                </div>
                <h2 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] font-yekan">
                    {{ $editingEventId ? 'ویرایش رویداد' : 'رویداد جدید' }}
                </h2>
            </div>
            <button
                wire:click="$set('isCreateModalOpen', false)"
                class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] rounded-full p-2 transition-colors"
            >
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        {{-- Form --}}
        <div class="p-6 space-y-6 overflow-y-auto container-scrollbar custom-scrollbar">

            {{-- Title --}}
            <div class="space-y-2">
                <label class="text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">عنوان رویداد</label>
                <input
                    type="text"
                    wire:model="eventTitle"
                    class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-2xl px-5 py-3.5 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/10 transition-all outline-none font-medium placeholder-[var(--md-sys-color-outline)]"
                    placeholder="مثلاً: جشن تولد..."
                >
                @error('eventTitle') <span class="text-xs text-[var(--md-sys-color-error)] font-medium mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Icon & Color Picker --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Icons --}}
                <div class="space-y-3">
                    <label class="text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">آیکون</label>
                    <div class="grid grid-cols-6 gap-2">
                        @foreach($availableIcons as $iconKey => $iconLabel)
                            <button
                                wire:click="$set('eventIcon', '{{ $iconKey }}')"
                                title="{{ $iconLabel }}"
                                class="aspect-square rounded-xl flex items-center justify-center transition-all hover:scale-110 active:scale-95
                                {{ $eventIcon === $iconKey ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-lg shadow-[var(--md-sys-color-primary)]/30 scale-110 ring-2 ring-offset-2 ring-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]' }}"
                            >
                                <span class="material-symbols-rounded text-[20px]">{{ $iconKey }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Colors --}}
                <div class="space-y-3">
                    <label class="text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">رنگ برچسب</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach($availableColors as $colorHex => $colorName)
                            <button
                                wire:click="$set('eventColor', '{{ $colorHex }}')"
                                title="{{ $colorName }}"
                                class="w-8 h-8 rounded-full transition-all hover:scale-110 active:scale-95 border-2 relative"
                                style="background-color: {{ $colorHex }}; border-color: {{ $eventColor === $colorHex ? 'var(--md-sys-color-surface)' : 'transparent' }}"
                            >
                                @if($eventColor === $colorHex)
                                    <span class="absolute inset-0 ring-2 ring-offset-2 ring-[var(--md-sys-color-on-surface)]/50 rounded-full"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Date & Time --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">تاریخ</label>
                    <div class="relative">
                        <span class="absolute top-1/2 right-4 -translate-y-1/2 text-[var(--md-sys-color-outline)] material-symbols-rounded pointer-events-none">calendar_month</span>
                        <input
                            type="text"
                            wire:model="eventDate"
                            placeholder="1403-01-01"
                            class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-2xl pr-12 pl-4 py-3.5 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/10 transition-all outline-none font-mono text-center ltr font-bold"
                        >
                    </div>
                    @error('eventDate') <span class="text-xs text-[var(--md-sys-color-error)] font-medium mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">زمان</label>
                    <div class="relative">
                        <span class="absolute top-1/2 right-4 -translate-y-1/2 text-[var(--md-sys-color-outline)] material-symbols-rounded pointer-events-none">schedule</span>
                        <input
                            type="time"
                            wire:model="eventTime"
                            class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-2xl pr-12 pl-4 py-3.5 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/10 transition-all outline-none font-mono text-center font-bold"
                        >
                    </div>
                    @error('eventTime') <span class="text-xs text-[var(--md-sys-color-error)] font-medium mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Description --}}
            <div class="space-y-2">
                <label class="text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">توضیحات</label>
                <textarea
                    wire:model="eventDescription"
                    rows="3"
                    class="w-full bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface)] rounded-2xl px-5 py-3.5 border border-transparent focus:border-[var(--md-sys-color-primary)] focus:ring-4 focus:ring-[var(--md-sys-color-primary)]/10 transition-all outline-none resize-none placeholder-[var(--md-sys-color-outline)]"
                    placeholder="جزئیات بیشتر درباره رویداد..."
                ></textarea>
            </div>

            {{-- Private Toggle --}}
            <div class="flex items-center justify-between bg-[var(--md-sys-color-surface-container-low)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/20">
                <div class="flex items-center gap-3 text-[var(--md-sys-color-on-surface)]">
                    <div class="w-10 h-10 rounded-full bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded">lock</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold">رویداد خصوصی</span>
                        <span class="text-xs text-[var(--md-sys-color-outline)]">فقط برای شما قابل مشاهده باشد</span>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="eventPrivate" class="sr-only peer">
                    <div class="w-12 h-7 bg-[var(--md-sys-color-surface-variant)] peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[var(--md-sys-color-primary)]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-[var(--md-sys-color-primary)]"></div>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="p-6 pt-4 flex justify-end gap-3 bg-[var(--md-sys-color-surface)] border-t border-[var(--md-sys-color-outline-variant)]/50">
            <button
                wire:click="$set('isCreateModalOpen', false)"
                class="px-6 py-3 rounded-xl text-[var(--md-sys-color-on-surface-variant)] font-bold hover:bg-[var(--md-sys-color-surface-variant)] transition-colors"
            >
                انصراف
            </button>
            <button
                wire:click="saveEvent"
                class="px-8 py-3 rounded-xl text-white font-bold shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
                style="background-color: {{ $eventColor }};"
            >
                @if($editingEventId)
                    <span class="material-symbols-rounded">save</span>
                    <span>بروزرسانی</span>
                @else
                    <span class="material-symbols-rounded">add</span>
                    <span>افزودن رویداد</span>
                @endif
            </button>
        </div>
    </div>
</div>
