<div class="contents">

    @if($variant === 'embedded')
        <div wire:key="reminder-embedded" class="space-y-5" dir="rtl">
            @include('livewire.dashboard.reminder.list')
        </div>
    @elseif($variant === 'corner')
        <button type="button" wire:click="open"
                aria-label="یادآوری‌ها"
                @class([
                    'relative z-20 flex h-10 w-10 items-center justify-center transition-all duration-200 active:scale-95',
                    'opacity-100 text-[var(--md-sys-color-primary)]' => $this->hasActiveReminder,
                    'max-sm:opacity-100 opacity-0 group-hover:opacity-100 text-[var(--md-sys-color-on-surface-variant)]' => ! $this->hasActiveReminder,
                ])>
            <span class="material-symbols-rounded text-[20px]">alarm</span>
            @if($this->dueCount > 0)
                <span wire:key="reminder-due-badge" class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] animate-pulse"></span>
            @endif
        </button>
    @elseif($variant === 'inline')
        <button type="button" wire:click="open"
                aria-label="یادآوری‌ها"
                @class([
                    'relative w-6 h-6 p-0 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-95',
                    'opacity-100 pointer-events-auto text-[var(--md-sys-color-primary)]' => $this->hasActiveReminder,
                    'opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] hover:text-[var(--md-sys-color-on-surface)]' => ! $this->hasActiveReminder,
                ])>
            <span class="material-symbols-rounded text-[16px]">alarm</span>
            @if($this->dueCount > 0)
                <span wire:key="reminder-due-badge" class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-error)] animate-pulse"></span>
            @endif
        </button>
    @else
        <button type="button" wire:click="open"
                class="group relative w-10 h-10 active:scale-95 transition-all duration-200 flex items-center justify-center">
            <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">alarm</span>
            @if($this->dueCount > 0)
                <span wire:key="reminder-due-badge" class="absolute top-2 right-2 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] animate-pulse"></span>
            @endif
            <x-ui.modals.tooltip text="یادآوری‌ها" :position="$tooltipPosition"/>
            <span wire:key="reminder-due-sync-{{ $this->dueCount }}" x-init="$dispatch('reminder-due', { count: {{ (int) $this->dueCount }} })" class="hidden"></span>
        </button>
    @endif

    @unless($variant === 'embedded')
        <x-ui.modals.action
            wire:model="show"
            wire:key="reminder-modal"
            title="یادآوری‌ها"
            action="{{ $editingId ? 'update' : 'create' }}"
            confirm-text="{{ $editingId ? 'بروزرسانی' : 'ثبت یادآوری' }}"
            cancel-text="انصراف"
            :readonly="$activeTab !== 'form'"
            class="!max-w-3xl !w-full"
        >
            @if($show)
            <div wire:key="reminder-modal-body" class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6 space-y-5" dir="rtl">

                @if($this->hostUrl())
                    <a href="{{ $this->hostUrl() }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[var(--md-sys-color-primary)] hover:opacity-80 transition-opacity -mb-2">
                        <span class="material-symbols-rounded text-[14px]">{{ $this->hostIcon() }}</span>
                        مشاهده و ویرایش رکورد
                    </a>
                @endif

                <div class="flex items-center justify-between gap-2">
                    <x-ui.buttons.tab-selector
                        :tabs="[
                            ['id' => 'form', 'icon' => 'add_circle', 'label' => 'یادآوری جدید'],
                            ['id' => 'list', 'icon' => 'list', 'label' => 'لیست'],
                        ]"
                        :active-tab="$activeTab"
                        class="!mb-0"
                    />
                    <button
                        type="button"
                        @click="$dispatch('open-modal', { name: 'reminder-legend' })"
                        title="راهنمای یادآوری‌ها"
                        class="flex items-center justify-center w-8 h-8 shrink-0 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                    >
                        <span class="material-symbols-rounded text-lg">help</span>
                    </button>
                </div>

                @if($activeTab === 'list')
                    <div wire:key="reminder-tab-list" class="mt-5 space-y-5">
                        @include('livewire.dashboard.reminder.list')
                    </div>
                @endif

                @if($activeTab === 'form')
                    @include('livewire.dashboard.reminder.form')
                @endif

            </div>
            @endif
        </x-ui.modals.action>

        @if($show)
            <div wire:key="reminder-legend-dialog" class="contents">
                <x-ui.modals.dialog name="reminder-legend" title="راهنمای یادآوری‌ها">
                    @include('livewire.dashboard.reminder.legend')
                </x-ui.modals.dialog>
            </div>
        @endif
    @endunless

</div>
