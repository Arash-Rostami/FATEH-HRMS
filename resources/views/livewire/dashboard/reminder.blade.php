<div class="contents" wire:poll.visible.60s="$refresh">

    @if($variant === 'corner')
        <button type="button" wire:click="open"
                aria-label="یادآوری‌ها"
                @class([
                    'relative z-20 flex h-10 w-10 items-center justify-center transition-all duration-200 active:scale-95',
                    'opacity-100 text-[var(--md-sys-color-primary)]' => $this->hasActiveReminder,
                    'opacity-0 group-hover:opacity-100 text-[var(--md-sys-color-on-surface-variant)]' => ! $this->hasActiveReminder,
                ])>
            <span class="material-symbols-rounded text-[20px]">alarm</span>
            @if($this->dueCount > 0)
                <span wire:key="reminder-due-badge" class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] animate-pulse"></span>
            @endif
            <x-ui.modals.tooltip text="یادآوری‌ها" :position="$tooltipPosition"/>
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
            <x-ui.modals.tooltip text="یادآوری‌ها" :position="$tooltipPosition"/>
        </button>
    @else
        <button type="button" wire:click="open"
                class="group relative w-10 h-10 active:scale-95 transition-all duration-200 flex items-center justify-center">
            <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">alarm</span>
            @if($this->dueCount > 0)
                <span wire:key="reminder-due-badge" class="absolute top-2 right-2 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] animate-pulse"></span>
            @endif
            <x-ui.modals.tooltip text="یادآوری‌ها" :position="$tooltipPosition"/>
        </button>
    @endif

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
        <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6 space-y-5" dir="rtl">

            @if($this->hostUrl())
                <a href="{{ $this->hostUrl() }}" wire:navigate
                   class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[var(--md-sys-color-primary)] hover:opacity-80 transition-opacity -mb-2">
                    <span class="material-symbols-rounded text-[14px]">{{ $this->hostIcon() }}</span>
                    مشاهده و ویرایش رکورد
                </a>
            @endif

            <x-ui.buttons.tab-selector
                :tabs="[
                    ['id' => 'form', 'icon' => 'add_circle', 'label' => 'یادآوری جدید'],
                    ['id' => 'list', 'icon' => 'list', 'label' => 'لیست'],
                ]"
                :active-tab="$activeTab"
                class="!mb-0"
            />

            @if($activeTab === 'list')
                <div wire:key="reminder-tab-list" class="mt-5 space-y-5">
                    @include('livewire.dashboard.reminder.list')
                </div>
            @endif

            @if($activeTab === 'form')
                @include('livewire.dashboard.reminder.form')
            @endif

        </div>
    </x-ui.modals.action>

</div>
